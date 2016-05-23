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

JLoader::register('JCalPro', JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/jcalpro.php');

abstract class JCalProHelperPath
{
	static private function _buildPath($root, $file = '') {
		return $root . (empty($file) ? '' : "/$file");
	}
	
	/**
	 * static method to get the media path
	 * 
	 * @param string $file
	 * 
	 * @return string
	 */
	static public function media($file = '') {
		return self::_buildPath(JPATH_ROOT . '/media/jcalpro', $file);
	}
	
	/**
	 * static method to get the admin path
	 * 
	 * @param string $file
	 * 
	 * @return string
	 */
	static public function admin($file = '') {
		return self::_buildPath(JPATH_ADMINISTRATOR . '/components/' . JCalPro::COM, $file);
	}
	
	/**
	 * static method to get the site path
	 * 
	 * @return string
	 */
	static public function site($file = '') {
		return self::_buildPath(JPATH_ROOT . '/components/' . JCalPro::COM, $file);
	}
	
	/**
	 * static method to get the helper path
	 * 
	 * @return string
	 */
	static public function helper($helper = null) {
		static $base;
		
		if (empty($base)) {
			$base = self::admin('helpers');
		}
		
		$file = '';
		if (!empty($helper)) {
			jimport('joomla.filesystem.file');
			$file = '/' . preg_replace('/[^a-z]/', '', $helper) . '.php';
		}
		
		return $base . $file;
	}
	
	/**
	 * static method to get the library path
	 * 
	 * @param string $file
	 * 
	 * @return string
	 */
	static public function library($file = '') {
		return self::_buildPath(self::admin('libraries'), $file);
	}
	
	/**
	 * static method to get the themes asset path
	 * 
	 * @param string $file
	 * 
	 * @return string
	 */
	static public function theme($file = '') {
		return self::_buildPath(self::media('themes'), $file);
	}
	
	/**
	 * static method to get the user's uploaded files asset path
	 * this method will create an empty folder if none is found
	 * 
	 * @return string
	 */
	static public function uploads() {
		jimport('joomla.filesystem.folder');
		// build the base for the user
		$user = JFactory::getUser();
		$base = self::media('uploads/' . $user->id);
		// if our user base does not yet exist, go ahead and create it
		if (!JFolder::exists($base)) {
			JFolder::create($base);
			// go ahead and create a blank index, too
			jimport('joomla.filesystem.file');
			$html = '<html></html>';
			JFile::write($base . '/index.html', $html);
		}
		return $base;
	}
}