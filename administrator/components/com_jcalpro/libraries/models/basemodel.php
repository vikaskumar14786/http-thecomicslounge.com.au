<?php
/**
 * @package		JCalPro
 * @subpackage	com_jcalpro

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

JLoader::register('JCalProHelperLog', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/log.php');

if (jimport('joomla.application.component.model')) {
	class JCalProBaseModelCommon extends JModel
	{
		static public function addIncludePath($path = '', $prefix = '') {
			return parent::addIncludePath($path, $prefix);
		}
	}
}
else {
	jimport('legacy.model.legacy');
	class JCalProBaseModelCommon extends JModelLegacy
	{
		static public function addIncludePath($path = '', $prefix = '') {
			return parent::addIncludePath($path, $prefix);
		}
	}
}

class JCalProBaseModel extends JCalProBaseModelCommon
{
	/**
	 * Override for extra logging
	 * 
	 * @param unknown_type $path
	 * @param unknown_type $prefix
	 */
	public static function addIncludePath($path = '', $prefix = '') {
		static $paths;
		if (!is_array($paths)) $paths = array();
		$key = md5($path . '-' . $prefix);
		if (!array_key_exists($key, $paths)) {
			$paths[$key] = parent::addIncludePath($path, $prefix);
		}
		return $paths[$key];
	}
	
	/**
	 * Override for extra logging and exception throwing on error
	 * 
	 * @param unknown_type $type
	 * @param unknown_type $prefix
	 * @param unknown_type $config
	 */
	public static function getInstance($type, $prefix = '', $config = array()) {
		$class = $prefix . $type;
		$instance = parent::getInstance($type, $prefix, $config);
		// if we don't have an instance, we'd like to know why...
		if (empty($instance)) {
			if (class_exists($class)) {
				$error = JText::_('COM_JCALPRO_MODEL_UNKNOWN_CONDITION');
			}
			else {
				$paths = parent::addIncludePath(null, $prefix);
				$error = JText::_('COM_JCALPRO_MODEL_CLASS_NOT_FOUND') . ', ' . JText::sprintf('COM_JCALPRO_MODEL_PATHS', print_r($paths, 1));
			}
			JCalProHelperLog::toss(JText::sprintf('COM_JCALPRO_MODEL_NOT_FOUND', $class, $error));
		}
		return $instance;
	}
}
