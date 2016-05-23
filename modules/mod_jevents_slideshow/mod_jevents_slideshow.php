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

defined( '_JEXEC' ) or die( 'Restricted access' );

// Make sure images are displayable in the files plugin
$jevfilesplugin = JPluginHelper::getPlugin('jevents', 'jevfiles');
if (!$jevfilesplugin) {
	//JFactory::getApplication()->enqueueMessage("JEV_MAKE_SURE_IMAGE_DISPLAY_IN_LIST_VIEWS_IS_ENABLED_IN_STANDARD_IMAGES_PLUGIN","error");
	echo JText::_("JEV_MAKE_SURE_IMAGE_DISPLAY_IN_LIST_VIEWS_IS_ENABLED_IN_STANDARD_IMAGES_PLUGIN");
	return;
}
$jevfilesparams = new JRegistry($jevfilesplugin->params);
$inlist = $jevfilesparams->get("inlist",0);
if (!$inlist) {
	//JFactory::getApplication()->enqueueMessage("JEV_MAKE_SURE_IMAGE_DISPLAY_IN_LIST_VIEWS_IS_ENABLED_IN_STANDARD_IMAGES_PLUGIN","error");
	echo JText::_("JEV_MAKE_SURE_IMAGE_DISPLAY_IN_LIST_VIEWS_IS_ENABLED_IN_STANDARD_IMAGES_PLUGIN");
	return;
}

require_once (dirname(__FILE__).'/'.'helper.php');

$jevhelper = new modJeventsSlideshowHelper();
$theme = JEV_CommonFunctions::getJEventsViewName();
$modtheme = $params->get("com_calViewName", "default");
if ($modtheme=="" || $modtheme=="global"){
	$modtheme=$theme;
}
$theme=$modtheme;

JPluginHelper::importPlugin("jevents");

// record what is running - used by the filters
$registry	= JRegistry::getInstance("jevents");
$registry->set("jevents.activeprocess","mod_jevents_slideshow");
$registry->set("jevents.moduleid", $module->id);
$registry->set("jevents.moduleparams", $params);

$viewclass = $jevhelper->getViewClass($theme, 'mod_jevents_slideshow',$theme.'/'."slideshow", $params);

$registry	= JRegistry::getInstance("jevents");
// See http://www.php.net/manual/en/timezones.php
$compparams = JComponentHelper::getParams(JEV_COM_COMPONENT);
$tz=$compparams->get("icaltimezonelive","");
if ($tz!="" && is_callable("date_default_timezone_set")){
	$timezone= date_default_timezone_get();
	//echo "timezone is ".$timezone."<br/>";
	date_default_timezone_set($tz);
	$registry->set("jevents.timezone",$timezone);
}

$modview = new $viewclass($params, $module->id);
$modview->jevlayout = $theme;
echo $modview->displaySlideshowEvents();

// Must reset the timezone back!!
if ($tz && is_callable("date_default_timezone_set")){
	date_default_timezone_set($timezone);
}

