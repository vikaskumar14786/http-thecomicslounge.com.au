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

jimport('joomla.plugin.plugin');

/**
 * Extension plugin for JCalPro to handle themes
 * 
 * 
 * BIG WARNING HERE!!!!!!!!!!!!!!!
 * 
 * DO NOT use JCalPro-specific classes in this plugin!!!!!!
 * 
 * This is designed to run BEFORE the component is installed for the first time,
 * so NO JCAL CLASSES WILL BE AVAILABLE!!!
 * 
 * 
 * @author jeff
 *
 */
class plgExtensionJCalPro extends JPlugin
{
	/**
	 * Constructor
	 * 
	 * @param unknown_type $subject
	 * @param unknown_type $config
	 */
	public function __construct(&$subject, $config) {
		parent::__construct($subject, $config);
	}
	
	public function loadLanguage($extension = 'plg_extension_jcalpro.sys', $basePath = JPATH_ADMINISTRATOR) {
		parent::loadLanguage($extension, $basePath);
	}
	
	public function onExtensionAfterInstall($installer, $eid) {
		$this->_handleJCalThemes('install', $installer, $eid);
	}
	
	public function onExtensionAfterUpdate($installer, $eid) {
		$this->_handleJCalThemes('update', $installer, $eid);
	}
	
	/**
	 * Update the parameters for this theme on install or update
	 * 
	 * @param unknown_type $context
	 * @param unknown_type $installer
	 * @param unknown_type $eid
	 * @throws Exception
	 */
	private function _handleJCalThemes($context, $installer, $eid) {
		// we hope this doesn't happen
		if (!(is_object($installer) && property_exists($installer, 'manifest') && is_object($installer->manifest))) {
			return false;
		}
		foreach (array('type', 'group') as $var) {
			$$var = method_exists($installer->manifest, 'getAttribute') ? $installer->manifest->getAttribute($var) : $installer->manifest->attributes()->{$var};
		}
		// bail if this isn't one of ours
		if ('file' !== "$type" && 'jcalpro' !== "$group") {
			return false;
		}
		// get parameters from the installer
		$params = $installer->getParams();
		// hmm, emptyish? bail
		if (empty($params) || '{}' == $params) {
			return false;
		}
		// start working the db
		$db = JFactory::getDbo();
		// check existing parameters to see if we have some set - if so, bail
		$db->setQuery($db->getQuery(true)
			->select('params')
			->from('#__extensions')
			->where($db->quoteName('extension_id') . '=' . (int) $eid)
		);
		try {
			$existing = $db->loadResult();
			if (!empty($existing)) {
				throw new Exception("Theme parameters found, not updating");
			}
		}
		catch (Exception $e) {
			return false;
		}
		// update the extension record with parameters
		$db->setQuery($db->getQuery(true)
			->update('#__extensions')
			->set($db->quoteName('params') . '=' . $db->quote($params))
			->where($db->quoteName('extension_id') . '=' . (int) $eid)
		);
		try {
			$db->query();
		}
		catch (Exception $e) {
			return false;
		}
		
		return true;
	}
}
