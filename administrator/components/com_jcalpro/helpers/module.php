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

abstract class JCalProHelperModule
{
	static public function render($type, $id = 0, $params = array()) {
		// check what module we're loading
		// right now, we ONLY support mod_jcalpro_(events|calendar|locations) explicitly :)
		switch ($type) {
			case 'mod_jcalpro_calendar':
			case 'mod_jcalpro_events':
			case 'mod_jcalpro_locations':
				break;
			default:
				return false;
		}
		// if id = 0 here, we assume it's because we're using a "dynamic" module
		// like those used in mod_jcalpro_flex
		if (empty($id)) {
			// read the default values from the xml file
			$xmlfile = JPATH_ROOT . "/modules/{$type}/{$type}.xml";
			if (!JFile::exists($xmlfile)) {
				return false;
			}
			// use our custom JForm override just to get the defaults
			require_once JCalProHelperPath::library() . '/form.php';
			$form = new JCalProForm($type, array('control' => $type));
			$form->loadFile($xmlfile, true, 'config');
			$defaults = $form->getData();
			$module = new stdClass();
			$module->id = 0;
			$module->module = $type;
			$module->title  = '';
			$module->params = $defaults->get('params');
		}
		else {
			// we need the database object
			$db = JFactory::getDbo();
			// load the module info from the database
			$db->setQuery((string) $db->getQuery(true)
					->select('*')
					->from('#__modules')
					->where("id = $id")
					->where("module = " . $db->Quote($type))
					->where('published=1')
			);
			// NOTE: assignment, NOT equality check!
			if (!($module = $db->loadObject())) return false;
		}
		// overload params, if required
		if (!empty($params) && is_array($params)) {
			$registry = new JRegistry();
			$registry->loadArray($params);
			// ensure we HAVE params
			if (!property_exists($module, 'params')) {
				$module->params = new JRegistry();
			}
			else if (!is_object($module->params)) {
				$obj = new JRegistry();
				$obj->loadString($module->params);
				$module->params = $obj;
			}
			foreach ($params as $key => $value) {
				if (!is_numeric($key)) $registry->set($key, $value);
			}
			$module->params->merge($registry);
		}
		// import the module helper, as it won't be available yet
		jimport('joomla.application.module.helper');
		// render module
		return JModuleHelper::renderModule($module, array());
	}
}