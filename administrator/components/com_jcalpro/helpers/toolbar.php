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

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('array');

abstract class JCalProHelperToolbar
{
	static protected $buttons = array();
	
	/**
	 * creates an array to represent a toolbar button
	 * 
	 * @param unknown_type $name
	 * @param unknown_type $params
	 * 
	 * @return array
	 * @since  3.2.3
	 */
	static public function addButton($name, $params = array()) {
		self::$buttons[$name] = JCalProHelperArray::merge(array(
			// name of the element
			'name'  => $name
			// array of html classes
		,	'class' => array("jcl_toolbar_button", "jcl_toolbar_button_$name")
			// url to direct to
		,	'href'  => '#'
			// text that is shown on the element
		,	'title' => JText::_('COM_JCALPRO_MAINMENU_' . strtoupper($name))
			// alternative html, instead of title
		,	'html' => false
			// an extra array for html attributes (rel, onclick, etc)
		,	'attr'  => array()
		), $params);
		
		return self::$buttons[$name];
	}
	
	/**
	 * get a specific button from the toolbar
	 * 
	 * @param string $name
	 * 
	 * @return array button details
	 * @return bool false if no button exists
	 */
	static public function getButton($name) {
		if (array_key_exists((string) $name, self::$buttons)) {
			return self::$buttons[$name];
		}
		return false;
	}
	
	/**
	 * delete a button from the toolbar
	 * 
	 * @param string $name
	 * 
	 * @return bool true deletion status
	 */
	static public function deleteButton($name) {
		if (self::getButton($name)) {
			unset(self::$buttons[$name]);
			return true;
		}
		return false;
	}
	
	/**
	 * get all the buttons
	 * 
	 * @return array
	 */
	static public function getButtons() {
		return self::$buttons;
	}
	
	
}