<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevents.php 1402 2009-04-03 13:00:38Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */


defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

if (version_compare(phpversion(), '5.0.0', '<')===true) {
	echo  '<div style="font:12px/1.35em arial, helvetica, sans-serif;"><div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;"><h3 style="margin:0; font-size:1.7em; font-weight:normal; text-transform:none; text-align:left; color:#2f2f2f;">'.JText::_("RSVP_INVALID_PHP1").'</h3></div>'.JText::_("RSVP_INVALID_PHP2").'</div>';
	return;
}

jimport('joomla.filesystem.path');
$option = JRequest::getCmd("option"); 
 include_once(JPATH_SITE."/components/com_jevents/jevents.defines.php");
include_once(JPATH_COMPONENT_ADMINISTRATOR.'/'.str_replace("com_","",$option).".defines.php");

$registry	= JRegistry::getInstance("jevents");

// In Joomla 1.6 JComponentHelper::getParams(JEV_COM_COMPONENT) is a clone so the menu params do not propagate so we force this here!
if (version_compare(JVERSION, "1.6.0", 'ge')){
	$newparams	= JFactory::getApplication('site')->getParams();
	$component = JComponentHelper::getComponent(RSVP_COM_COMPONENT);
	$component->params =& $newparams;
}

$jevparams = JComponentHelper::getParams(JEV_COM_COMPONENT);
// See http://www.php.net/manual/en/timezones.php
$tz=$jevparams->get("icaltimezonelive","");
if ($tz!="" && is_callable("date_default_timezone_set")){
	$timezone= date_default_timezone_get();
	date_default_timezone_set($tz);
	$registry->set("jevents.timezone",$timezone);
}

$lang = JFactory::getLanguage();
$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);
// also load the plugin language file!
$lang->load( 'plg_jevents_jevrsvppro',JPATH_ADMINISTRATOR );

$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

// disable Zend php4 compatability mode
@ini_set("zend.ze1_compatibility_mode","Off");

$cmd = JRequest::getCmd('task', 'sessions.overview');

// Load JEvents custom css file if its created
jimport('joomla.filesystem.file');
if (JFile::exists(JPATH_SITE . "/components/com_jevents/assets/css/jevcustom.css"))
{
	// It is definitely now created, lets load it!
	JEVHelper::stylesheet('jevcustom.css', 'components/' . JEV_COM_COMPONENT . '/assets/css/');
}

if (strpos($cmd, '.') != false) {
	// We have a defined controller/task pair -- lets split them out
	list($controllerName, $task) = explode('.', $cmd);

	// Define the controller name and path
	$controllerName	= strtolower($controllerName);
	$controllerPath	= JPATH_COMPONENT.'/'.'controllers'.'/'.$controllerName.'.php';
	$controllerName = "Front".$controllerName;

	// If the controller file path exists, include it ... else lets die with a 500 error
	if (file_exists($controllerPath)) {
		require_once($controllerPath);
	} else {
		JError::raiseError(500, 'Invalid Controller');
	}
} else {
	// Base controller, just set the task
	$controllerName = null;
	$task = $cmd;
}

JPluginHelper::importPlugin("jevents");

// Set the name for the controller and instantiate it
$controllerClass = ucfirst($controllerName).'Controller';
if (class_exists($controllerClass)) {
	$controller = new $controllerClass();

	// Perform the Request task
	$controller->execute($task);

	// Must reset the timezone back!!
	if ($tz && is_callable("date_default_timezone_set")){
		date_default_timezone_set($timezone);
	}
	
	// Redirect if set by the controller
	$controller->redirect();

} else {
	echo "missing controllerClass $controllerClass";
	exit();
}
