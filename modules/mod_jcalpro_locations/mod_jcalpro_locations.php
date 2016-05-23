<?php
/**
 * @package		JCalPro
 * @subpackage	mod_jcalpro_locations

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

$app = JFactory::getApplication();

// set the state for the module
$app->setUserState('com_jcalpro.events.jcalpro.module', true);

// register the component helpers
JLoader::register('JCalProHelperPath', JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/path.php');
JLoader::register('JCalPro', JCalProHelperPath::helper() . '/jcalpro.php');
JLoader::register('JCalProHelperFilter', JCalProHelperPath::helper() . '/filter.php');
JLoader::register('JCalProHelperTheme', JCalProHelperPath::helper() . '/theme.php');
JLoader::register('JCalProHelperUrl', JCalProHelperPath::helper() . '/url.php');

// ensure our language files are properly loaded
JCalPro::language('mod_jcalpro_locations');

// Include the helper only once
require_once dirname(__FILE__).'/helper.php';

// handle module callbacks
if ('com_jcalpro' == $app->input->get('option', '', 'cmd') && 'module' == $app->input->get('task', '', 'cmd') && $app->input->get('id', 0, 'int') == $module->id) {
	switch ($app->input->get('action', '', 'cmd')) {
		case 'json':
		default:
			// we only want to send back JSON if this is an ajax request
			echo json_encode(modJCalProLocationsHelper::getList($params, $module));
			die;
	}
}

// add the scripts needed
modJCalProLocationsHelper::prepareDocument($params, $module);

$moduleclass_sfx = JCalProHelperFilter::escape($params->get('moduleclass_sfx'));

require JModuleHelper::getLayoutPath('mod_jcalpro_locations', $params->get('layout', 'default'));

// reset the state
$app->setUserState('com_jcalpro.events.jcalpro.module', false);