<?php
/**
 * @package		JCalPro
 * @subpackage	mod_jcalpro_events

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

// set the state for the module
JFactory::getApplication()->setUserState('com_jcalpro.events.jcalpro.module', true);

// register the component helpers
JLoader::register('JCalProHelperPath', JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/path.php');
JLoader::register('JCalPro', JCalProHelperPath::helper() . '/jcalpro.php');
JLoader::register('JCalProHelperFilter', JCalProHelperPath::helper() . '/filter.php');
JLoader::register('JCalProHelperTheme', JCalProHelperPath::helper() . '/theme.php');
JLoader::register('JCalProHelperUrl', JCalProHelperPath::helper() . '/url.php');

// ensure our language files are properly loaded
JCalPro::language('mod_jcalpro_events');

// load scripts
JCalPro::loadJsFramework();

// Include the helper only once
require_once dirname(__FILE__).'/helper.php';

// add the module css
JCalProHelperTheme::addStyleSheet('default', 'modules/events/css');

$list                  = modJCalProEventsHelper::getList($params);
$moduleclass_sfx       = JCalProHelperFilter::escape($params->get('moduleclass_sfx'));
$urlparams             = array();
$display_date          = (int) $params->get('display_date', 1);
$display_time          = (int) $params->get('display_time', 1);
$display_category      = (bool) (int) $params->get('display_category', 1);
$display_title         = (bool) (int) $params->get('display_title', 1);
$display_description   = (bool) (int) $params->get('display_description', 1);
$display_location      = (bool) (int) $params->get('display_location', 0);
$display_location_text = (bool) (int) $params->get('display_location_text', 0);
$display_registration  = (bool) (int) $params->get('display_registration', 0);
$display_readmore      = (bool) (int) $params->get('display_readmore', 1);
$limit_title           = max(0, (int) $params->get('limit_title', 0));
$limit_description     = max(0, (int) $params->get('limit_description', 0));
$filter_description    = (bool) (int) $params->get('filter_description', true);
$show_months           = (bool) (int) $params->get('show_months', false); // TODO add this to xml
$featured              = (int) $params->get('featured', 1);
$Itemid                = (int) $params->get('itemid', 0);
$top_fields            = $params->get('top_fields', false);
$bottom_fields         = $params->get('bottom_fields', false);
$empty_html            = $params->get('empty_html', '');
// only set in params if defined
if ($Itemid) $urlparams['Itemid'] = $Itemid;
// define these, $this_month will change as events are rendered
$first_month = $last_month = $this_month = (($show_months && !empty($list)) ? $list[0]->user_datetime->monthName() : false);

JCalPro::debugger('Params', $params, 'mod_jcalpro_events');
JCalPro::debugger('Module', $module, 'mod_jcalpro_events');

require JModuleHelper::getLayoutPath('mod_jcalpro_events', $params->get('layout', 'default'));

// reset the state
JFactory::getApplication()->setUserState('com_jcalpro.events.jcalpro.module', false);