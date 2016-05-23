<?php
/**
 * @package GalleryAholic
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @copyright (c) 2013 MicroJoom. All Rights Reserved.
 * @author MicroJoom http://www.microjoom.com
 * @modified by GalleryAholic
 */
defined('_JEXEC') or die;

if( !class_exists('GA_Helper_Abstract') ){
	abstract class GA_Helper_Abstract{

		const VERSION = '1.0.0';
		
		public static function linkTarget($type='_self'){
			$target = '';
			switch($type){
				default:
				case '_self':
					break;

			}
			return $target;
		}
		
		public static function createThumbs($item, $params, $prefix='imgf'){
			$images_path = array();
			$image = self::getAImage($item, $params, $prefix);
			$mode_resize = (int)$params->get($prefix.'_function');
			if(file_exists($image['src']) || @getimagesize($image['src'])){
				switch($mode_resize){
					case 0:
						$images_path = $image;
						break;

					default:
						break;
				}
			}
			return $images_path;
		}

		public static function _cleanText($text, $params){
			$text = strip_tags($text, '<a><b><blockquote><code><del><dd><dl><dt><em><h1><h2><h3><i><kbd><p><pre><s><sup><strong><strike><br><hr>');
			//$text = strip_tags($text, '<a><b>');
			$text = trim($text);
			return $text;
		}
		
		public static function _trimEncode($text){
			$str = strip_tags($text);
			$str = str_replace(' ', '' , $str);
			$str = trim( $str, "\xC2\xA0\n" );
			return $str;
		}

		public static function truncate($string, $length, $etc='...'){
			return defined('MB_OVERLOAD_STRING')
			? self::_mb_truncate($string, $length, $etc)
			: self::_truncate($string, $length, $etc);
		}

		private static function _truncate($string, $length, $etc='...'){
			if ($length>0 && $length<strlen($string)){
				$buffer = '';
				$buffer_length = 0;
				$parts = preg_split('/(<[^>]*>)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
				$self_closing_tag = split(',', 'area,base,basefont,br,col,frame,hr,img,input,isindex,link,meta,param,embed');
				$open = array();

				foreach($parts as $i => $s){
					if( false===strpos($s, '<') ){
						$s_length = strlen($s);
						if ($buffer_length + $s_length < $length){
							$buffer .= $s;
							$buffer_length += $s_length;
						} else if ($buffer_length + $s_length == $length) {
							if ( !empty($etc) ){
								$buffer .= ($s[$s_length - 1]==' ') ? $etc : " $etc";
							}
							break;
						} else {
							$words = preg_split('/([^\s]*)/', $s, - 1, PREG_SPLIT_DELIM_CAPTURE);
							$space_end = false;
							foreach ($words as $w){
								if ($w_length = strlen($w)){
									if ($buffer_length + $w_length < $length){
										$buffer .= $w;
										$buffer_length += $w_length;
										$space_end = (trim($w) == '');
									} else {
										if ( !empty($etc) ){
											$more = $space_end ? $etc : " $etc";
											$buffer .= $more;
											$buffer_length += strlen($more);
										}
										break;
									}
								}
							}
							break;
						}
					} else {
						preg_match('/^<([\/]?\s?)([a-zA-Z0-9]+)\s?[^>]*>$/', $s, $m);
						//$tagclose = isset($m[1]) && trim($m[1])=='/';
						if (empty($m[1]) && isset($m[2]) && !in_array($m[2], $self_closing_tag)){
							array_push($open, $m[2]);
						} else if (trim($m[1])=='/') {
							$tag = array_pop($open);
							if ($tag != $m[2]){
								// uncomment to to check invalid html string.
								// die('invalid close tag: '. $s);
							}
						}
						$buffer .= $s;
					}
				}
				
				while(count($open)>0){
					$tag = array_pop($open);
					$buffer .= "</$tag>";
				}
				return $buffer;
			}
			return $string;
		}

		private static function _mb_truncate($string, $length, $etc='...'){
			$encoding = mb_detect_encoding($string);
			if ($length>0 && $length<mb_strlen($string, $encoding)){
				$buffer = '';
				$buffer_length = 0;
				$parts = preg_split('/(<[^>]*>)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
				$self_closing_tag = explode(',', 'area,base,basefont,br,col,frame,hr,img,input,isindex,link,meta,param,embed');
				$open = array();

				foreach($parts as $i => $s){
					if (false === mb_strpos($s, '<')){
						$s_length = mb_strlen($s, $encoding);
						if ($buffer_length + $s_length < $length){
							$buffer .= $s;
							$buffer_length += $s_length;
						} else if ($buffer_length + $s_length == $length) {
							if ( !empty($etc) ){
								$buffer .= ($s[$s_length - 1]==' ') ? $etc : " $etc";
							}
							break;
						} else {
							$words = preg_split('/([^\s]*)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
							$space_end = false;
							foreach ($words as $w){
								if ($w_length = mb_strlen($w, $encoding)){
									if ($buffer_length + $w_length < $length){
										$buffer .= $w;
										$buffer_length += $w_length;
										$space_end = (trim($w) == '');
									} else {
										if ( !empty($etc) ){
											$more = $space_end ? $etc : " $etc";
											$buffer .= $more;
											$buffer_length += mb_strlen($more);
										}
										break;
									}
								}
							}
							break;
						}
					} else {
						preg_match('/^<([\/]?\s?)([a-zA-Z0-9]+)\s?[^>]*>$/', $s, $m);
						//$tagclose = isset($m[1]) && trim($m[1])=='/';
						if (empty($m[1]) && isset($m[2]) && !in_array($m[2], $self_closing_tag)){
							array_push($open, $m[2]);
						} else if (trim($m[1])=='/') {
							$tag = array_pop($open);
							if ($tag != $m[2]){
								// uncomment to to check invalid html string.
								// die('invalid close tag: '. $s);
							}
						}
						$buffer .= $s;
					}
				}
				
				while(count($open)>0){
					$tag = array_pop($open);
					$buffer .= "</$tag>";
				}
				return $buffer;
			}
			return $string;
		}

		private static function getAImage($item, $params, $prefix='imgf'){
			$mark = array();  $images_path = array();
			$images_cfg = $params->get($prefix.'_cfg');
			if(count($images_cfg) > 0){
				foreach($images_cfg as $image){
					$mark[] = str_replace($prefix.'_','',$image);
				}
			}
			
			if ( count($mark) > 0 ){
				$images_data = null;
				if (in_array('image_intro', $mark) || in_array('image_fulltext', $mark)){
					$images_data = json_decode($item->images, true);
				}

				foreach($mark as $type){
					switch ($type){
						

						case 'inline_introtext':
							$text = $item->introtext;

							$inline_images = self::getInlineImages($text);
							for ($i=0; $i<count($inline_images); $i++){
								$inline_images[$i]['alt'] = $item->title;
								$inline_images[$i]['title'] = $item->title;
								if(file_exists($inline_images[$i]['src']) || @getimagesize($inline_images[$i]['src'])) {
									array_push($images_path, $inline_images[$i]);
								}
							}
							break;
								

						default:
							break;
					}
				}
			}
				
			if ( count($images_path) == 0 && $params->get($prefix.'_placeholder', 1)==1){
				$placeholder_path = $params->get($prefix.'_placeholder_path','modules/mod_galleryaholic/images/no_photo.jpg');
				if(file_exists($placeholder_path) || @getimagesize($placeholder_path)){
					$images_path[] = array('src'=> $placeholder_path,'title'=>$item->title, 'alt'=>$item->title, 'class'=>'placeholder');
				}
			}
			return is_array($images_path) && count($images_path) ? $images_path[0] : null;
		}

		private static function getInlineImages($text){
			$images = array();
			$searchTags = array(
					'img'	=> '/<img[^>]+>/i',
					'input'	=> '/<input[^>]+type\s?=\s?"image"[^>]+>/i'
			);
			foreach ($searchTags as $tag => $regex){
				preg_match_all($regex, $text, $m);
				if ( is_array($m) && isset($m[0]) && count($m[0])){
					foreach ($m[0] as $htmltag){
						$tmp = JUtility::parseAttributes($htmltag);
						if ( isset($tmp['src']) ){
							if ($tag == 'input'){
								array_push( $images, array('src' => $tmp['src']) );
							} else {
								array_push( $images, $tmp );
							}
						}
					}
				}
			}
			return $images;
		}

		private static function getExternalImages($path){
			jimport('joomla.filesystem.folder');
			$files = array();
			// check if $path is url
			$path = trim($path);
			$isHttp = stripos($path, 'http') === 0;
			if ($isHttp){
				if ( !JUri::isInternal($path) ){
					// is external, test if is valid
					if ( version_compare(JVERSION, '3.0.0', '>=') ){
						// is Joomla 3
						$http = JHttpFactory::getHttp();
						$head = $http->head($path);
						if ($head->code == 200 || $head->code == 302 || $head->code == 304){
							// is valid url
							if (preg_match('/image/', $head->headers['Content-Type'])){
								// is true image
								$files[] = $path;
							}
						}
					} else {
						// for Joomla 3 older
						$files[] = $path;
					}
					if (!count($files)){ var_dump('Url is not valid'); }
					return $files;
				} else {
					$uri = JUri::getInstance($path);
					$uri_path = (string)$uri->getPath();
					$uri_base = (string)JURI::base(true);
					if (stripos($uri_path, $uri_base)===0 && ($baselen = strlen($uri_base))){
						$uri_path = substr($uri_path, $baselen);
					}
					$path = JPATH_BASE.$uri_path;
				}
			}
			
			if ( ($realpath = realpath($path))===false ){
				return $files;
			}

			if ( is_file($realpath) ){
				$files[] = $realpath;
			} else if ( is_dir($realpath) ){
				$files = JFolder::files($path, '.jpg|.png|.gif|.JPG|.PNG|.GIF', false, true);
			}
			return $files;
		}
	}
}