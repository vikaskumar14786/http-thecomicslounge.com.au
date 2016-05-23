<?php
/**
 * @package		JCalPro
 * @subpackage	mod_jcalpro_calendar

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
JLoader::register('JCalPro', JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('date');
JCalPro::registerHelper('filter');
JCalPro::registerHelper('log');
JCalPro::registerHelper('path');
JCalPro::registerHelper('theme');
JCalPro::registerHelper('url');

// set the date
$reqdate = $app->input->get('date', '', 'string');
$moddate = $params->get('date', JCalProHelperDate::getTodayTime()->toRequest());
$app->input->set('date', $moddate);

// ensure our language files are properly loaded
JCalPro::language('mod_jcalpro_calendar');

// Include the helper only once
require_once dirname(__FILE__).'/helper.php';

// add the module css
JCalProHelperTheme::addStyleSheet('common', 'modules/calendar/css', 'default');
JCalProHelperTheme::addStyleSheet('default', 'modules/calendar/css');

// get our data
$list            = modJCalProCalendarHelper::getList($params);
$dates           = modJCalProCalendarHelper::getDates();
$showtip         = (bool) (int) $params->get('display_tooltip', 1);
$limit           = max(0, (int) $params->get('tooltip_length', 0));
$moduleclass_sfx = JCalProHelperFilter::escape($params->get('moduleclass_sfx'));
$urlparams       = array();
$Itemid          = (int) $params->get('itemid', 0);
if ($Itemid) {
	$urlparams['Itemid'] = $Itemid;
}

$default_format = 'F Y';
$header_format  = $params->get('header_date_format', $default_format);
try {
	$header = $dates->date->format($header_format);
}
catch (Exception $ex) {
	$header = $dates->date->format($default_format);
}

// register the tooltip behavior
if ($showtip) {
	JHtml::_('behavior.tooltip', '.jcalpro_calendar_tip_' . $module->id);
}

// render the module
require JModuleHelper::getLayoutPath('mod_jcalpro_calendar', $params->get('display_mode', 'default'));

// reset the state
$app->input->set('date', $reqdate);
$app->setUserState('com_jcalpro.events.jcalpro.module', false);
