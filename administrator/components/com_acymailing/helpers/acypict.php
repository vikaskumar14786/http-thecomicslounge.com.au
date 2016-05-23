<?php
/**
 * @package	AcyMailing for Joomla!
 * @version	4.9.1
 * @author	acyba.com
 * @copyright	(C) 2009-2015 ACYBA S.A.R.L. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php

class acypictHelper{

	var $error;
	var $maxHeight;
	var $maxWidth;
	var $destination;

	function acypictHelper(){
		jimport('joomla.filesystem.file');
	}

	function removePictures($text){
		$return = preg_replace('#< *img[^>]*>#Ui','',$text);
		$return = preg_replace('#< *div[^>]*class="jce_caption"[^>]*>[^<]*(< *div[^>]*>[^<]*<\/div>)*[^<]*<\/div>#Ui','',$return);
		return $return;
	}

	function available(){
		if(!function_exists('gd_info')){
			$this->error = 'The GD library is not installed.';
			return false;
		}
		if(!function_exists('getimagesize')){
			$this->error = 'Cound not find getimagesize function';
			return false;
		}
		if(!function_exists('imagealphablending')){
			$this->error = "Please make sure you're using GD 2.0.1 or later version";
			return false;
		}
		return true;
	}

	function resizePictures($input){
		$this->destination = ACYMAILING_MEDIA.'resized'.DS;
		acymailing_createDir($this->destination);
		$content = acymailing_absoluteURL($input);

		preg_match_all('#<img([^>]*)>#Ui',$content,$results);
		if(empty($results[1])) return $input;

		$replace = array();

		foreach($results[1] as $onepicture){
			if(strpos($onepicture,'donotresize') !== false) continue;

			if(!preg_match('#src="([^"]*)"#Ui',$onepicture,$path)) continue;
			$imageUrl = $path[1];

			$base = str_replace(array('http://www.','https://www.','http://','https://'),'',ACYMAILING_LIVE);
			$replacements = array('https://www.'.$base,'http://www.'.$base,'https://'.$base,'http://'.$base);
			foreach($replacements as $oneReplacement){
				if(strpos($imageUrl,$oneReplacement) === false) continue;
				$imageUrl = str_replace(array($oneReplacement,'/'),array(ACYMAILING_ROOT,DS),urldecode($imageUrl));
				break;
			}

			$newPicture = $this->generateThumbnail($imageUrl);

			if(!$newPicture){
				$newDimension = 'max-width:'.$this->maxWidth.'px;max-height:'.$this->maxHeight.'px;';
				if(strpos($onepicture, 'style="') !== false){
					$replace[$onepicture] = preg_replace('#style="([^"]*)"#Uis', 'style="'.$newDimension.'$1"', $onepicture);
				}else{
					$replace[$onepicture] = ' style="'.$newDimension.'" '.$onepicture;
				}
				continue;
			}

			$newPicture['file'] = preg_replace('#^'.preg_quote(ACYMAILING_ROOT,'#').'#i',ACYMAILING_LIVE,$newPicture['file']);
			$newPicture['file'] = str_replace(DS,'/',$newPicture['file']);
			$replaceImage = array();
			$replaceImage[$path[1]] = $newPicture['file'];
			if(preg_match_all('#(width|height)(:|=) *"?([0-9]+)#i',$onepicture,$resultsSize)){
				foreach($resultsSize[0] as $i => $oneArg){
					$newVal = (strtolower($resultsSize[1][$i]) == 'width') ? $newPicture['width'] : $newPicture['height'];
					if($newVal > $resultsSize[3][$i]) continue;
					$replaceImage[$oneArg] = str_replace($resultsSize[3][$i],$newVal,$oneArg);
				}
			}

			$replace[$onepicture] = str_replace(array_keys($replaceImage),$replaceImage,$onepicture);

		}

		if(!empty($replace)){
			$input = str_replace(array_keys($replace),$replace,$content);
		}

		return $input;
	}

	function generateThumbnail($picturePath){

 		list($currentwidth, $currentheight) = getimagesize($picturePath);
 		if(empty($currentwidth) || empty($currentheight)) return false;
 		$factor = min($this->maxWidth/$currentwidth,$this->maxHeight/$currentheight);
		if($factor>=1) return false;
		$newWidth = round($currentwidth*$factor);
		$newHeight = round($currentheight*$factor);

		if(strpos($picturePath,'http') === 0){
			$filename = substr($picturePath,strrpos($picturePath,'/')+1);
		}else{
			$filename = basename($picturePath);
		}

		if(substr($picturePath,0,10) == 'data:image'){
			preg_match('#data:image/([^;]{1,5});#',$picturePath,$resultextension);
			if(empty($resultextension[1])) return false;
			$extension = $resultextension[1];
			$name = md5($picturePath);
		}else{
			$extension = strtolower(substr($filename,strrpos($filename,'.')+1));
			$name = strtolower(substr($filename,0,strrpos($filename,'.')));
			$name .= substr(@filemtime($picturePath),-4);
		}

		$newImage = $name.'thumb'.$this->maxWidth.'x'.$this->maxHeight.'.'.$extension;
		if(empty($this->destination)){
			$newFile = dirname($picturePath).DS.$newImage;
		}else{
			$newFile = $this->destination.$newImage;
		}

		if(file_exists($newFile)) return array('file' => $newFile,'width' => $newWidth,'height' => $newHeight);

		switch($extension){
			case 'gif':
				$img = ImageCreateFromGIF($picturePath);
				break;
			case 'jpg':
			case 'jpeg':
				$img = ImageCreateFromJPEG($picturePath);
				break;
			case 'png':
				$img = ImageCreateFromPNG($picturePath);
				break;
			default:
				return false;
		}

		$thumb = ImageCreateTrueColor($newWidth, $newHeight);

		if(in_array($extension,array('gif','png'))){
			imagealphablending($thumb, false);
			imagesavealpha($thumb,true);
		}

		if(function_exists("imagecopyresampled")){
			imagecopyresampled($thumb, $img, 0, 0, 0, 0, $newWidth, $newHeight,$currentwidth, $currentheight);
		}else{
			ImageCopyResized($thumb, $img, 0, 0, 0, 0, $newWidth, $newHeight,$currentwidth, $currentheight);
		}
		ob_start();
		switch($extension){
			case 'gif':
				$status = imagegif($thumb);
				break;
			case 'jpg':
			case 'jpeg':
				$status = imagejpeg($thumb,null,100);
				break;
			case 'png':
				$status = imagepng($thumb,null,0);
				break;
		}
		$imageContent = ob_get_clean();
		$status = $status && JFile::write($newFile,$imageContent);
		imagedestroy($thumb);
		imagedestroy($img);

		if(!$status) $newFile = $picturePath;

		return array('file' => $newFile,'width' => $newWidth,'height' => $newHeight);
	}

	function uploadThumbnail(&$element){
		$config =& acymailing_config();
		$app = JFactory::getApplication();
		$files = JRequest::getVar( 'pictures', array(), 'files', 'array' );
		if(empty($files)) return;
		jimport('joomla.filesystem.file');

		$uploadFolder = JPath::clean(html_entity_decode($config->get('uploadfolder')));
		$uploadFolder = trim($uploadFolder,DS.' ').DS;
		$uploadPath = JPath::clean(ACYMAILING_ROOT.$uploadFolder);

		acymailing_createDir($uploadPath,true);

		if(!is_writable($uploadPath)){
			@chmod($uploadPath,'0755');
			if(!is_writable($uploadPath)){
				$app->enqueueMessage(JText::sprintf( 'WRITABLE_FOLDER',$uploadPath), 'notice');
			}
		}

		$allowedExtensions = array('jpg','gif','png','jpeg','ico','bmp');

		foreach($files['name'] as $id => $filename){
			if(empty($filename)) continue;
			$extension = strtolower(substr($filename,strrpos($filename,'.')+1));
			if(!in_array($extension,$allowedExtensions)){
				$app->enqueueMessage(JText::sprintf('ACCEPTED_TYPE',$extension,implode(', ',$allowedExtensions)), 'notice');
				continue;
			}

			$pictname = strtolower(substr(JFile::makeSafe($filename),0,strrpos($filename,'.')+1));
			$pictname = preg_replace('#[^0-9a-z]#i','_',$pictname);
			$pictfullname = $pictname.'.'.$extension;
			if(file_exists($uploadPath.$pictfullname)){
				$pictfullname = $pictname.time().'.'.$extension;
			}

			if(!JFile::upload($files['tmp_name'][$id], $uploadPath.$pictfullname)){
				if(!move_uploaded_file($files['tmp_name'][$id], $uploadPath . $pictfullname)){
					$app->enqueueMessage(JText::sprintf('FAIL_UPLOAD','<b><i>'.$files['tmp_name'][$id].'</i></b>','<b><i>'.$uploadPath . $pictfullname.'</i></b>'), 'error');
					continue;
				}
			}

			$thumbField = str_replace(DS,'/',$uploadFolder).$pictfullname;
		}
		if(!empty($thumbField)) $element->thumb = $thumbField;
	}
}