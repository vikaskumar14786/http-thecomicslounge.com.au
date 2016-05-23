<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

$option = JRequest::getVar('option');
$task = JRequest::getVar('task');

// Only show the register component if we're not on the component register page.
if(!($option == 'com_user' &&  ($task == 'register_save' || $task == 'register')))
{
	$user = JFactory::getUser();
	if ($user->guest || !$params->get('guest_only'))
		require(JModuleHelper::getLayoutPath('mod_osregister'));
}

?>
