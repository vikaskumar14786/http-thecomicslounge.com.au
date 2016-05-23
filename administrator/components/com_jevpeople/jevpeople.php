<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */

defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

//error_reporting(E_ALL);

jimport('joomla.filesystem.path');

if (!defined("JEVEX_COM_COMPONENT")){
	define("JEVEX_COM_COMPONENT","com_jevpeople");
	define("JEVEX_COMPONENT",str_replace("com_","",JEVEX_COM_COMPONENT));
	define("JEV_COM_COMPONENT","com_jevents");
	define("JEV_COMPONENT",str_replace("com_","",JEV_COM_COMPONENT));	
}
JLoader::register('JEVHelper',JPATH_SITE."/components/com_jevents/libraries/helper.php");
JLoader::register('JevPeopleHelper',JPATH_ADMINISTRATOR."/components/com_jevpeople/libraries/helper.php");
JLoader::register('JevJoomlaVersion',JPATH_ADMINISTRATOR."/components/com_jevents/libraries/version.php");
JLoader::register('JevCfForm',JPATH_SITE."/plugins/jevents/jevcustomfields/customfields/jevcfform.php");
$option=JRequest::getCmd("option");
$lang = JFactory::getLanguage();
$lang->load($option, JPATH_COMPONENT_ADMINISTRATOR);
$lang->load($option, JPATH_COMPONENT);

$cmd = JRequest::getCmd('task', 'cpanel.list');
$view = JRequest::getCmd('view', 'cpanel');
$layout = JRequest::getCmd('layout', '');

if (strpos($cmd, '.') != false) {
	// We have a defined controller/task pair -- lets split them out
	list($controllerName, $task) = explode('.', $cmd);
	
	// Define the controller name and path
	$controllerName	= strtolower($controllerName);
	$controllerPath	= JPATH_COMPONENT."/".'controllers'."/".$controllerName.'.php';
	$controllerName = "Admin".$controllerName;
	
	// If the controller file path exists, include it ... else lets die with a 500 error
	if (file_exists($controllerPath)) {
		require_once($controllerPath);
	} else {
		JError::raiseError(500, 'Invalid Controller');
	}
} else {
	// Base controller, just set the task 
	$controllerName = $view;
	$task = $layout;
}

// Set the name for the controller and instantiate it
$controllerClass = ucfirst($controllerName).'Controller';
if (class_exists($controllerClass)) {
	$controller = new $controllerClass();
} else {
	JError::raiseError(500, 'Invalid Controller Class - '.$controllerClass );
}

$config	= JFactory::getConfig();

// Perform the Request task
$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();
