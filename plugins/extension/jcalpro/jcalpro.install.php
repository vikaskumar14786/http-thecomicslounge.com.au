<?php
/**
 * @package		JCalPro
 * @subpackage	plg_extension_jcalpro

**********************************************
JCal Pro
Copyright (c) 2006-2012 Anything-Digital.com
**********************************************
JCalPro is a native Joomla! calendar component for Joomla!

JCal Pro was once a fork of the existing Extcalendar component for Joomla!
(com_extcal_0_9_2_RC4.zip from mamboguru.com).
Extcal (http://sourceforge.net/projects/extcal) was renamed
and adapted to become a Mambo/Joomla! component by
Matthew Friedman, and further modified by David McKinnis
(mamboguru.com) to repair some security holes.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This header must not be removed. Additional contributions/changes
may be added to this header as long as no information is deleted.
**********************************************
Get the latest version of JCal Pro at:
http://anything-digital.com/
**********************************************

 */

defined('JPATH_PLATFORM') or die;

class plgExtensionJCalProInstallerScript
{
	public function postflight($type, $parent) {
		$app = JFactory::getApplication();
		$db  = JFactory::getDbo();
		
		// find this plugin in the database, if possible...
		$db->setQuery($db->getQuery(true)
			->select('extension_id')
			->from('#__extensions')
			->where($db->quoteName('element') . ' = ' . $db->quote('jcalpro'))
			->where($db->quoteName('folder') . ' = ' . $db->quote('extension'))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
		);
		
		try {
			$eid = $db->loadResult();
			if (!$eid) {
				throw new Exception('Could not enable plugin! ' . __METHOD__);
			}
		}
		catch (Exception $e) {
			if (defined('JDEBUG') && JDEBUG) {
				$app->enqueueMessage(htmlspecialchars($e->getMessage()));
			}
			return;
		}
		
		// force-enable this plugin
		$db->setQuery($db->getQuery(true)
			->update('#__extensions')
			->set($db->quoteName('enabled') . ' = 1')
			->where($db->quoteName('extension_id') . ' = ' . (int) $eid)
		);
		
		try {
			$db->query();
		}
		catch (Exception $e) {
			if (defined('JDEBUG') && JDEBUG) {
				$app->enqueueMessage(htmlspecialchars($e->getMessage()));
			}
			return;
		}
		
		// once done, force-load this plugin so it fires events during the rest of this request
		// do this by manually loading the class file & inject it into the event dispatcher
		jimport('joomla.filesystem.file');
		$pluginFile = JPATH_PLUGINS . "/extension/jcalpro/jcalpro.php";
		
		if (!JFile::exists($pluginFile)) {
			if (defined('JDEBUG') && JDEBUG) {
				$app->enqueueMessage('No plugin file to inject!');
			}
			return;
		}
		
		if (!class_exists('plgExtensionJCalPro')) {
			require_once $pluginFile;
		}
		
		// get the dispatcher
		// this looks funny due to something weird with the 2.5 autoloader
		jimport('joomla.filesystem.file');
		$dispatcherFile = JPATH_LIBRARIES . '/legacy/dispatcher/dispatcher.php';
		if (JFile::exists($dispatcherFile)) {
			require_once $dispatcherFile;
		}
		else {
			jimport('joomla.event.dispatcher');
		}
		
		$dispatcher = JDispatcher::getInstance();
		
		$config = array();		
		$plugin = new plgExtensionJCalPro($dispatcher, $config);
		
	}
}
