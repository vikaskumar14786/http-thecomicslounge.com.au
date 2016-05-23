<?php
/**
 * SmartResizer Content Plugin
 *
 * @package		Joomla
 * @subpackage	SmartResizer Content Plugin
 * @copyright Copyright (C) 2009 LoT studio. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @author igort
 *
 */

// no direct access
defined( '_JEXEC' ) or die();

if (!defined( 'DS' )) define ('DS','/');

jimport( 'joomla.plugin.plugin' );
require_once(dirname(__FILE__) . '/smartresizer/smartimagehandler.php');
// require_once(dirname(__FILE__) . '/smartresizer/idna_convert.class.php');

//safe_glob() by BigueNique at yahoo dot ca
//Function glob() is prohibited on some servers for security reasons as stated on:
//http://seclists.org/fulldisclosure/2005/Sep/0001.html
//(Message "Warning: glob() has been disabled for security reasons in (script) on line (line)")
//safe_glob() intends to replace glob() for simple applications
//using readdir() & fnmatch() instead.
//Since fnmatch() is not available on Windows or other non-POSFIX, I rely
//on soywiz at php dot net fnmatch clone.
//On the final hand, safe_glob() supports basic wildcards on one directory.
//Supported flags: GLOB_MARK. GLOB_NOSORT, GLOB_ONLYDIR
//Return false if path doesn't exist, and an empty array is no file matches the pattern
function safe_glob($pattern, $flags=0) {
    $split=explode('/',$pattern);
    $match=array_pop($split);
    $path=implode('/',$split);
    if (($dir=opendir($path))!==false) {
        $glob=array();
        while(($file=readdir($dir))!==false) {
            if (fnmatch($match,$file)) {
                if ((is_dir("$path/$file"))||(!($flags&GLOB_ONLYDIR))) {
                    if ($flags&GLOB_MARK) $file.='/';
                    $glob[]=$file;
                }
            }
        }
        closedir($dir);
        if (!($flags&GLOB_NOSORT)) sort($glob);
        return $glob;
    } else {
        return false;
    }   
}

function initHighslideSmartResizer($addslideshow = 0) {

if (!defined('IP_HIGHSLIDE')) {
	define('IP_HIGHSLIDE','1');
	$doc = JFactory::getDocument();
	if(version_compare(JVERSION,'1.6.0','<')) $paddpath = ''; else $paddpath = 'smartresizer/';
	$urljs = 'plugins/content/smartresizer/'.$paddpath.'js/highslide/highslide-with-gallery.packed.js';
	$initjs= "
hs.graphicsDir = '/plugins/content/smartresizer/".$paddpath."js/highslide/graphics/';
hs.align = 'center';
hs.transitions = ['expand', 'crossfade'];
hs.outlineType = 'rounded-white';
hs.fadeInOut = true;
hs.lang.nextText = '".JText::_('Next')."';
hs.lang.nextTitle = '".JText::_('Next')."';
hs.lang.creditsText = '';
hs.lang.creditsTitle = '';
hs.lang.loadingText = '".JText::_('Loading')."';
hs.lang.loadingTitle = '".JText::_('Click_to_cancel')."';
hs.lang.focusTitle = '".JText::_('Click_to_bring_to_front')."';
hs.lang.fullExpandTitle = '".JText::_('Expand_to_actual_size')."';
hs.lang.previousText  = '".JText::_('Previous')."';
hs.lang.moveText  = '".JText::_('Move')."';
hs.lang.closeText = '".JText::_('Close')."';
hs.lang.closeTitle = '".JText::_('Close')."';
hs.lang.resizeTitle = '".JText::_('Resize')."';
hs.lang.playText = '".JText::_('Play')."';
hs.lang.playTitle = '".JText::_('Play_slideshow')."';
hs.lang.pauseText = '".JText::_('Pause')."';
hs.lang.pauseTitle  = '".JText::_('Pause_slideshow')."';
hs.lang.previousTitle = '".JText::_('Previous')."';
hs.lang.moveTitle = '".JText::_('Move')."';
hs.lang.fullExpandText  = '".JText::_('Original_size')."';
hs.lang.number = '".JText::_('Image_counter')."';
hs.lang.restoreTitle = '';

//hs.dimmingOpacity = 0.75;
";
if ($addslideshow) 
$initjs .= "
// Add the controlbar
hs.addSlideshow({
	//slideshowGroup: 'group1',
	interval: 5000,
	repeat: false,
	useControls: true,
	fixedControls: 'fit',
	overlayOptions: {
		opacity: 0.75,
		position: 'bottom center',
		hideOnMouseOut: true
	}
});
";
	$doc->addScriptDeclaration($initjs);
	$doc->addScript($urljs);
	$doc->addStyleSheet('plugins/content/smartresizer/'.$paddpath.'js/highslide/highslide.css' );
	
}	

}

class plgContentSmartResizer extends JPlugin
{
	
    function plgContentSmartResizer( &$subject, $params )
	{
		parent::__construct( $subject, $params );
	}

	// for J17
	function onContentPrepare( $context, &$article, &$params, $limitstart=0 ) {
	
		if (($option = JRequest::getVar('option', '')) != 'com_content')
			$this->onPrepareContent( $article, $params, $limitstart );
	}	
	
	// for J17
	function onContentBeforeDisplay( $context, &$article, &$params, $limitstart=0 ) {
		if (($option = JRequest::getVar('option', '')) == 'com_content')
			$this->onPrepareContent( $article, $params, $limitstart );
	}
	
	// for J15
	function onPrepareContent( &$article, &$params, $limitstart=0 )
	{
	
	
		$mainframe = JFactory::getApplication();
		if (get_class($mainframe) === "JAdministrator" )
			return true;

		$plugin = JPluginHelper::getPlugin('content', 'smartresizer');		
		$option = JRequest::getVar('option', '');
		if(version_compare(JVERSION,'1.6.0','<')) {
	    	$pluginParams = new JParameter( $plugin->params );
			if ($option)
				$mergeparams		= $mainframe->getParams($option);
			if (isset($mergeparams))
				$pluginParams->merge($mergeparams);
		} else {
	        $version = new JVersion();		
			$pluginParams = new JRegistry();
			if ( version_compare($version->getShortVersion(), '3.0.0', '>=') ) {
				$pluginParams->loadString($plugin->params);
			} else {
				$pluginParams->loadJSON($plugin->params);
			}
		}
		
		$processall	= (int) $pluginParams->def( 'processall', '0');

		
		//for J1.7
		$isblogintro=0;
		if(!version_compare(JVERSION,'1.6.0','<'))
		{
			$view		= JRequest::getCmd('view');
			if ($option == 'com_content') {
				if ($view == 'article') {
					if (empty($article->text))
						$article->text = $article->introtext . $article->fulltext;
				}
				else {
					if ($article->introtext)
						$isblogintro=1;
						if (empty($article->text))
							$article->text = $article->introtext;
				}
			}
		}
		
    	if ( strpos( $article->text, 'smartresize' ) === false && !$processall)
 			return true;
		if ($processall && strpos( $article->text, 'img' ) === false && strpos( $article->text, 'IMG' ) === false)
 			return true;
    	
		if ($processall)
			$runword = "";
		else
			$runword = "smartresize";
		$regex_img = "|<[\s\v]*img[\s\v]([^>]*".$runword."[^>]*)>|Ui";
		preg_match_all( $regex_img, $article->text, $matches_img);
		$count_img = count( $matches_img[0] );

     	// plugin only processes if there are any instances of the plugin in the text
     	if ( $count_img ) {

     		$this->plgContentProcessSmartResizeImages( $article, $pluginParams, $matches_img, $count_img );
			
			if ($isblogintro)
				$article->introtext = $article->text;
    	}
	}
	
	function getThumbPath($onsite, $src, $juribase, $uhost, $upath, $aththumb_ext, $just_path, $just_name, $extension, $thumb_subfolder_name, $storethumb)
	{
		$jpath = str_replace('/', DS , $just_path);
		if ($onsite) {
			$full_path = JPATH_ROOT . DS . $upath;
			if ($storethumb == 1) {
				$aththumb_ext_img = '_' . str_replace(array("\\","/"),"_", $just_path) . $aththumb_ext;
				$thumb_path = JPATH_ROOT . DS . "cache" . DS . $just_name . $aththumb_ext_img . $extension;
				$thethumb = $uhost . "/" .  "cache" . "/" . $just_name .  $aththumb_ext_img . $extension;
			} elseif ($storethumb == 2) {
				$thumb_path = JPATH_ROOT . DS . $jpath . DS . $thumb_subfolder_name . DS . $just_name . $aththumb_ext . $extension;
				$thethumb = $uhost . "/" . $just_path . "/" . $thumb_subfolder_name . "/" . $just_name .  $aththumb_ext . $extension;						
											
			} else {
				$thumb_path = JPATH_ROOT . DS . $jpath . DS . $just_name . $aththumb_ext . $extension;
				$thethumb = $uhost . "/" . $just_path ."/". $just_name .  $aththumb_ext . $extension;
			}
		} else {

			$full_path = $src;
								
			if ($storethumb == 1) {
				$reparr = array("\\","/",'http:',".");
				$aththumb_ext_img = str_replace($reparr,"", $uhost . $upath) . $aththumb_ext;
				$thumb_path = JPATH_ROOT . DS . "cache" . DS . $aththumb_ext_img . $extension;
				$thethumb = $juribase . "/" .  "cache" . "/" . $aththumb_ext_img . $extension;
			
			} elseif ($storethumb == 2) {
				$thumb_path = JPATH_ROOT . DS . "images" . DS . $thumb_subfolder_name . DS . $just_name . $aththumb_ext . $extension;
				$thethumb = $juribase . "/images/" . $thumb_subfolder_name . "/" . $just_name .  $aththumb_ext . $extension;						
				
			} else {
				$thumb_path = JPATH_ROOT . DS . "images" . DS . $just_name . $aththumb_ext . $extension;
				$thethumb = $juribase . "/images/" . $just_name .  $aththumb_ext . $extension;
			}
		}
	
		return array($full_path, $thumb_path, $thethumb);
	}
	
	function makeDir($onsite,$just_path, $thumb_subfolder_name )
	{
		if ($onsite)
			$jpath = str_replace('/', DS , $just_path);
		else
			$jpath = "images";
		if (!is_dir(JPATH_ROOT . DS . $jpath . DS . $thumb_subfolder_name)) {
			if (!mkdir(JPATH_ROOT . DS . $jpath . DS . $thumb_subfolder_name,0755)) {
				return false;
			}
		}
		return true;
	}
	
	
	function plgContentProcessSmartResizeImages( &$row, &$botParams, &$matches_img, $count_img ) {
		
		$view		= JRequest::getCmd('view');
		$option = JRequest::getVar('option', '');

		
		$processall	= (int) $botParams->def( 'processall', '0');
		$readmorelink	= (int) $botParams->def( 'readmorelink', '1');
		$ignoreindividual = (int) $botParams->def( 'ignoreindividual', '0');
		$openstyle = (int) $botParams->def( 'openstyle', '0');

		if ($openstyle == 2)
			initHighslideSmartResizer(0);
		
		$storethumb	= (int) $botParams->def( 'storethumb', '0');
		$thumb_ext	= $botParams->def( 'thumb_ext', '_thumb');				
		
		$thumb_subfolder_name = "smart_thumbs";
		
		$imgstyleblog = $botParams->def( 'imgstyleblog', '');
		$imgstylearticle = $botParams->def( 'imgstylearticle', '');
		$imgstyleother = $botParams->def( 'imgstyleother', '');
		
    	$thumb_width = $botParams->def( 'thumb_width', '');
    	$thumb_height = $botParams->def( 'thumb_height', '');
		if (!$thumb_width && !$thumb_height)
		 	$thumb_width = "100";
			
    	$thumb_quality = $botParams->def( 'thumb_quality', '90');
    	$compatibility = $botParams->def( 'compatibility', 'rokbox');
		

		$defthumb_medium_width =  (int) $botParams->def( 'thumb_medium_width', '');
		$defthumb_medium_height = (int) $botParams->def( 'thumb_medium_height', '');
		
		if (!$defthumb_medium_width && !$defthumb_medium_height)
			$defthumb_medium_width = 250;
			
		$defthumb_other_width =  (int) $botParams->def( 'thumb_other_width', '');
		$defthumb_other_height = (int) $botParams->def( 'thumb_other_height', '');
		
		if (!$defthumb_other_width && !$defthumb_other_height)
			$defthumb_other_width = 250;

// variables for large thumbnail			
		$laththumb_ext = $thumb_ext.'_large';
		$lathwidth =  (int) $botParams->def( 'thumb_large_width', '');
		$lathheight = (int) $botParams->def( 'thumb_large_height', '');	
		if ($lathwidth || $lathheight)
			$laththumb_ext .= $lathwidth.'_'.$lathheight;
		if (!$lathwidth && !$lathheight)
			$lathwidth = 640;				

    	$improve_thumbnails = false; // Auto Contrast, Unsharp Mask, Desaturate,  White Balance
		$is_com_content = 0;
    	
		$createcapt = 0;
		if ($option == 'com_content') {
			$is_com_content = 1;
			if ($view == 'article' || !isset($row->slug) || !$row->slug) {
		    	$athwidth = $defthumb_medium_width;
		    	$athheight = $defthumb_medium_height;
				$aththumb_ext = $thumb_ext.'_medium';
				$imgstyle=$imgstylearticle;
				$is_blog = 0;
				$createcapt = (int)$botParams->def( 'createcaptart', '0');
			}
			else {
		    	$athwidth = $thumb_width;
		    	$athheight = $thumb_height;
				$aththumb_ext = $thumb_ext;
				$imgstyle=$imgstyleblog;
				$is_blog = 1;
				$createcapt = (int)$botParams->def( 'createcaptblog', '0');
			}
		} else {
	    	$athwidth = $defthumb_other_width;
	    	$athheight = $defthumb_other_height;
			$aththumb_ext = $thumb_ext.'_other';
			$imgstyle=$imgstyleother;
			$is_blog = 0;
			$createcapt = (int)$botParams->def( 'createcaptother', '0');
		}
		
		if ($athwidth || $athheight)
			$aththumb_ext .= $athwidth.'_'.$athheight;
		
		$imgstyle=trim($imgstyle);
		
		$juribase = rtrim(JURI::base(),"/");
		
		for ( $i=0; $i < $count_img; $i++ )
		{
			if (strpos( $matches_img[0][$i], 'nosmartresize' ))
	    		continue;		

    	    if (!@$matches_img[1][$i]) 
				continue;
				
			$image_width = 0;
			$image_height = 0;
				
			$inline_params = $matches_img[1][$i];

			$src = array();
			preg_match( "#src=\"(.*?)\"#si", $inline_params, $src );
			if (isset($src[1])) $src = trim($src[1]);
			  else $src = "";

			// Prevent thumbs of thumbs
			if ( strpos( $src, $thumb_ext ) )	  
				continue;
			  
// echo "==================== ".$urlbase . " ======================";
			$onsite=-1;

			$uri = JURI::getInstance($juribase);
			$juribasew = $uri->toString(array('host','path'));
			
			$juribasew = str_replace('www.','',str_replace('WWW.','',$juribasew));
			
			$uri = JURI::getInstance($src);
			$uscheme = $uri->toString(array('scheme'));
			$uhostpath = $uri->toString(array('host','path'));
			$uhostpath = str_replace('www.','',str_replace('WWW.','',$uhostpath));
			$upath =  $uri->toString(array('path'));
			$uhost = $uri->toString(array('host'));
			
			if ($uhost ==="" || !(strpos(JString::strtolower($juribase), JString::strtolower($uhost))===false)) {
				$onsite=1;
				$upath = JString::str_ireplace($juribasew,"", $uhostpath);
				$uhost = $juribase;
			} else {
				$onsite=0;
				if (substr($uhost, strlen($uhost)-1) == "/") $uhost = substr($uhost,0, strlen($uhost)-1);
			}

			$upath = ltrim($upath,"/");			
			
			$extension = substr($upath,strrpos($upath,"."));
				
			$isimage = ($extension == '.jpg' || $extension == '.jpeg' || $extension == '.png' || $extension == '.gif' ||
					$extension == '.JPG' || $extension == '.JPEG' || $extension == '.PNG' || $extension == '.GIF');
			if (!$isimage)
				  continue;
				  
			$image_name = substr($upath,0,strrpos($upath, "."));
			
			$a=strrpos($image_name,"/");

			$just_name = substr($image_name,$a+1);
			$just_path = substr($image_name,0,$a);
			
			list($full_path, $thumb_path, $thethumb) = $this->getThumbPath($onsite, $src, $juribase, $uhost, $upath, $aththumb_ext, $just_path, $just_name, $extension, $thumb_subfolder_name, $storethumb);
			
//echo $full_path. ' : '. $thumb_path . ' : '. $thethumb;

			if (!file_exists($thumb_path)) {
			
				// for editors includes width and height in style property
				$awidth = array();
				preg_match( "#[\s\;\"]width:(.*?)px*[\s\;\"]#si", $inline_params, $awidth );
				if (isset($awidth[1])) $individ_width = trim($awidth[1]);
				  else $individ_width="";
				
				$aheight = array();
				preg_match( "#[\s\;\"]height:(.*?)px*[\s\;\"]#si", $inline_params, $aheight );
				if (isset($aheight[1])) $individ_height = trim($aheight[1]);
				  else $individ_height="";	
				// end for editors		
				  
				$awidth = array();
				preg_match( "#width=\"(.*?)\"#si", $inline_params, $awidth );
				if (isset($awidth[1])) $individ_width = trim($awidth[1]);
				
				$aheight = array();
				preg_match( "#height=\"(.*?)\"#si", $inline_params, $aheight );
				if (isset($aheight[1])) $individ_height = trim($aheight[1]);
				  
				$awidth = array();
				preg_match( "#blogwidth:(.*?)[\s\;\"]#si", $inline_params, $awidth );
				if (isset($awidth[1])) $individ_blogwidth = trim($awidth[1]);
				  else $individ_blogwidth="";
				
				$aheight = array();
				preg_match( "#blogheight:(.*?)[\s\;\"]#si", $inline_params, $aheight );
				if (isset($aheight[1])) $individ_blogheight = trim($aheight[1]);
				  else $individ_blogheight="";
				  
				  
				if (!$ignoreindividual || strpos( $matches_img[0][$i], 'smartresizeindividual' ) ) {
					if (!$is_blog  && ($individ_width || $individ_height)) { // this is article or other
						$athwidth = $individ_width;
						$athheight = $individ_height;
					} elseif ($is_blog  && ($individ_blogwidth || $individ_blogheight)) {
						$athwidth = $individ_blogwidth;
						$athheight = $individ_blogheight;
					}
				}
				
				$calcthumb_width = (int)$athwidth;
				$calcthumb_height = (int)$athheight;
				
				list($image_width,$image_height)=getimagesize($src);
				if ($image_width==0 || $image_height==0)
					  continue;
				$thesize = "[" . $image_width . " " . $image_height . "]";
				if ($calcthumb_width  && !$calcthumb_height)
					$calcthumb_height = round($calcthumb_width * ($image_height/$image_width));
				else
				if (!$calcthumb_width  && $calcthumb_height)
					$calcthumb_width = round($calcthumb_height * ($image_width/$image_height));

				$thesize = "[" . $image_width . " " . $image_height . "]";
				
				$text = '';
				
				if ( $image_width > $calcthumb_width || $image_height > $calcthumb_height ) {
					if ($storethumb == 2)	{
						if (!$this->makeDir($onsite,$just_path, $thumb_subfolder_name )) {
							 $storethumb = 0;
							 list($full_path, $thumb_path, $thethumb) = $this->getThumbPath($onsite, $src, $juribase, $uhost, $upath, $aththumb_ext, $just_path, $just_name, $extension, $thumb_subfolder_name, $storethumb);
						}
					}
					$fit = (int) $botParams->get('croporfit','1');
					$rd = new ismartresimgRedim(true, $improve_thumbnails, JPATH_CACHE);
					$rd->loadImage($full_path);
					
					$rd->redimToSize($calcthumb_width, $calcthumb_height, ($fit == 0), ($fit != 0));
					$rd->saveImage($thumb_path, $thumb_quality);
				} else 
					continue;
			}
			
			//check or create large thumb
			if ((int) $botParams->def( 'uselargethumb', '0')) {
					
				list($full_path, $lthumb_path, $lthethumb) = $this->getThumbPath($onsite, $src, $juribase, $uhost, $upath, $laththumb_ext, $just_path, $just_name, $extension, $thumb_subfolder_name, $storethumb);	
				
				if (!file_exists($lthumb_path)) {
					if (!($image_width && $image_height))
						list($image_width,$image_height)=getimagesize($src);
					$calcthumb_width = (int)$lathwidth;
					$calcthumb_height = (int)$lathheight;
					if ($image_width!=0 && $image_height!=0) {
						if ($calcthumb_width  && !$calcthumb_height)
							$calcthumb_height = round($calcthumb_width * ($image_height/$image_width));
						else
						if (!$calcthumb_width  && $calcthumb_height)
							$calcthumb_width = round($calcthumb_height * ($image_width/$image_height));

						if ( $image_width > $calcthumb_width || $image_height > $calcthumb_height ) {
							if ($storethumb == 2)	{
								if (!$this->makeDir($onsite,$just_path, $thumb_subfolder_name )) {
									 $storethumb = 0;
									 list($full_path, $lthumb_path, $lthethumb) = $this->getThumbPath($onsite, $src, $juribase, $uhost, $upath, $laththumb_ext, $just_path, $just_name, $extension, $thumb_subfolder_name, $storethumb);
								}
							}

							$rd = new ismartresimgRedim(true, $improve_thumbnails, JPATH_CACHE);
							$rd->loadImage($full_path);
							$rd->redimToSize($calcthumb_width, $calcthumb_height, 0, 1);
							$rd->saveImage($lthumb_path, $thumb_quality);
							
							$image_width = $calcthumb_width;
							$image_height = $calcthumb_height;
						} else
							$lthethumb = "";
					}
				}
			}

			// replace image file name
			$text = str_replace($src, $thethumb, $matches_img[0][$i]);
			//$text = str_replace("smartresize", "nosmartresize", $text);
			$text = preg_replace( "#width=\".*?\"#si", "", $text );
			$text = preg_replace( "#height=\".*?\"#si", "", $text );

			$aheight = array();			
			preg_match( "#[\s\;\"](width:.*?px*)[\s\;\"]#si", $inline_params, $aheight );
			if (isset($aheight[1])) 
				$text = str_replace($aheight[1],'',$text);
			
			$aheight = array();
			preg_match( "#[\s\;\"](height:.*?px*)[\s\;\"]#si", $inline_params, $aheight );
			if (isset($aheight[1])) 
				$text = str_replace($aheight[1],'',$text);
			
			if ($createcapt) {
				$text = preg_replace( "#class=\".*?\"#si", "", $text );
				$text = preg_replace( "#style=\".*?\"#si", "", $text );				
			}
			
		
			$thetitle = array();
			preg_match( "#title=\"(.*?)\"#si", $inline_params, $thetitle );
			if (isset($thetitle[1])) $thetitle = trim($thetitle[1]);
			  else $thetitle = "";
			  
			$alt = array();
			preg_match( "#alt=\"(.*?)\"#si", $inline_params, $alt );
			if (isset($alt[1])) $alt = trim($alt[1]);
			  else $alt = "";
			  
			$astyle = array();
			preg_match( "#style=\"(.*?)\"#si", $inline_params, $astyle );
			$styleword = isset($astyle[0]);
			if ($styleword) $styleorigin = $astyle[0]; else $styleorigin = "";
			if (isset($astyle[1])) $astyle = trim($astyle[1]);
			  else $astyle="";
			  
			$class = array();
			preg_match( "#class=\"(.*?)\"#si", $inline_params, $class );
			if (isset($class[1])) $class = trim($class[1]);
			  else $class = "";
			  
			  
		
			if ($alt && $thetitle) $thetitle = $thetitle . ' - '. $alt;
				else if ($alt) $thetitle = $alt;

// if large thumb needed
				
			if (!($is_blog && $readmorelink)) {
						
				if (isset($lthethumb) && $lthethumb)
					$src = $lthethumb;
				elseif (!$uri->toString(array('host')))
					$src = rtrim(JURI::base(),'/') . '/' . ltrim($src,'/');

				if ($openstyle == 0) {
					$doc = JFactory::getDocument();
					if (!($image_width && $image_height))
						list($image_width,$image_height)=getimagesize($src);
					if(version_compare(JVERSION,'1.6.0','<')) $paddpath = ''; else $paddpath = 'smartresizer/';
					$doc->addScript( "plugins/content/smartresizer/".$paddpath."js/multithumb.js" );
					$text = '<a href="javascript:void(0)" onclick = "smartthumbwindow(\''.$src.'\',\''.$alt.'\','.$image_width.','.$image_height.',0,0);" title="' . $thetitle . '">'.$text.'</a>';
				}
				elseif ($openstyle == 1) {
					JHTML::_('behavior.modal');
					if (!($image_width && $image_height))
						list($image_width,$image_height)=getimagesize($src);
					$text = '<a style="background:none;" rel="{handler: \'iframe\', size: {x: '.$image_width.', y: '.$image_height.'}}" target="_blank"  href="'.$src.'" onclick="SqueezeBox.fromElement(this,{parse: \'rel\'});return false;" >'.$text.'</a>';
				}
				elseif ($openstyle == 2) {
					$lang = JFactory::getLanguage();
					$lang->load('plg_content_smartresizer',JPATH_ADMINISTRATOR);		
					$text = '<a href="'.$src.'" style="background:none;" onclick="return hs.expand(this)" >'.$text.'</a>'."\n";
					if ($thetitle)
						$text .= '<div class="highslide-caption">'.$thetitle.'</div>';
				}
			}
			else if ($readmorelink) {
				if(version_compare(JVERSION,'1.6.0','<'))
					$link = JRoute::_(ContentHelperRoute::getArticleRoute($row->slug, $row->catslug, $row->sectionid));
				else
					$link = JRoute::_(ContentHelperRoute::getArticleRoute($row->slug, $row->catid));
				$text = '<a href="' . $link . '" title="' . $thetitle . '">'.$text.'</a>';
			}

			if ($imgstyle) {
				$imgstyle = rtrim($imgstyle,'; ').';';
				$insstyle = ' style="'.$imgstyle.$astyle.'"';
				if ($styleorigin)
					$text = str_replace($styleorigin, $insstyle, $text);
				else {
					$text = preg_replace( "#<[\s\v]*img#si", "<img ".$insstyle, $text );
				}
			}
			if ($createcapt) {
				if ($astyle)
					$astyle = rtrim($astyle,'; ').';';
				$insstyle = $astyle;
				if ($imgstyle) 
					$insstyle = $imgstyle.$astyle;
					
				$insstyle = preg_replace( "#height:.*?px*[\s\;]#si", "", $insstyle );
				$insstyle = preg_replace( "#width:.*?px*[\s\;]#si", "", $insstyle );				

				if	($class)
					$class = 'class="'.$class.'"';
				if (!(int)$botParams->def( 'captpos', '0')) 
					$text = '<div '.$class.' style="'.$insstyle.'display:inline-block;text-align:center;">'.$text.'<br/><span style="display:block;'.$botParams->def( 'captstyle', '').'">'.$thetitle.'</span></div>';
				else
					$text = '<div '.$class.' style="'.$insstyle.'display:inline-block;text-align:center;"><span style="'.$botParams->def( 'captstyle', '').'">'.$thetitle.'</span><br/>'.$text.'</div>';
			}

			$row->text = str_replace( $matches_img[0][$i], $text, $row->text );
		}
    }
}

?>
