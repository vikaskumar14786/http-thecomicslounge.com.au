<?php
/**
 * JEvents Component for Joomla!
 *
 * @version     $Id: mod_jevents_slideshow.php 3309 2012-03-01 10:07:50Z geraintedwards $
 * @package     JEvents
 * @subpackage  Module Slideshow JEvents
 * @copyright   Copyright (C) 2006-2014 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

class modJeventsSlideshowHelper
{

	public function __construct(){
		// setup for all required function and classes
		$file = JPATH_SITE . '/components/com_jevents/mod.defines.php';
		if (file_exists($file) ) {
			include_once($file);
			include_once(JPATH_SITE . "/components/com_jevents/libraries/modfunctions.php");

		} else {
			die (JText::_('JEV_LATEST_NEEDS_COMPONENT'));
		}
 
		// load language constants
		JEVHelper::loadLanguage('modlatest');
	}

	function getViewClass($theme, $module, $layout, $params=false){

		// If we have a specified over ride then use it here
		if ($params && strlen($params->get("layout",""))>0){
			$speciallayout = strtolower($params->get("layout",""));
			// Build the template and base path for the layout
			$tPath = JPATH_SITE.'/'.'templates'.'/'.JFactory::getApplication()->getTemplate().'/'.'html'.'/'.$module.'/'.$theme.'/'.$speciallayout.'.php';

			// If the template has a layout override use it
			if (file_exists($tPath)) {
				$viewclass = "Override".ucfirst($theme)."ModSlideshowView".ucfirst($speciallayout);
				require_once($tPath);
				if (class_exists($viewclass)){
					return $viewclass;
				}
			}
		}
		if ($layout=="" || $layout=="global"){
			$layout=JEV_CommonFunctions::getJEventsViewName();;
		}
		
		// Build the template and base path for the layout
		$tPath = JPATH_SITE.'/'.'templates'.'/'.JFactory::getApplication()->getTemplate().'/'.'html'.'/'.$module.'/'.$layout.'.php';
		$bPath = JPATH_SITE.'/'.'modules'.'/'.$module.'/'.'tmpl'.'/'.$layout.'.php';

		jimport('joomla.filesystem.file');
		// If the template has a layout override use it
		if (JFile::exists($tPath)) {
			require_once($tPath);
			$viewclass = "Override".ucfirst($theme)."ModSlideshowView";
			if (class_exists($viewclass)){
				return $viewclass;
			}
			else {
				// fall back to badly declared template override!
				$viewclass = ucfirst($theme)."ModSlideshowView";
				if (class_exists($viewclass)){
					return $viewclass;
				}				
			}
		}
		if (JFile::exists($bPath)) {
			require_once($bPath);
			$viewclass = ucfirst($theme)."ModSlideshowView";
			return $viewclass;
		}
		else {
			echo "<strong>".JText::sprintf("JEV_PLEASE_REINSTALL_LAYOUT",$theme)."</strong>";
			$bPath = JPATH_SITE.'/'.'modules'.'/'.$module.'/'.'tmpl'.'/'.'default'.'/'.'slideshow.php';
			require_once($bPath);
			$viewclass = "DefaultModSlideshowView";
			return $viewclass;

		}
	}
}
