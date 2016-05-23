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

jimport('joomla.application.component.model');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');
JCalProBaseModel::addIncludePath(JPATH_SITE.'/components/com_jcalpro/models', 'JCalProModel');

abstract class modJCalProCalendarHelper
{
	public static function getList(&$params) {
		
		$profiler = JProfiler::getInstance('Application');
		$profiler->mark('onJCalProCalendarModuleGetListStart');
		
		// Get an instance of the events model
		$model = JCalProBaseModel::getInstance('Events', 'JCalProModel', array('ignore_request' => true));
		$model->clearModelCache();
		
		// we need to store some of the state so we can reset it
		$layout       = $model->getState('filter.layout');
		$categories   = $model->getState('filter.category');
		$catid        = $model->getState('filter.catid');
		$search       = $model->getState('filter.search');
		$location     = $model->getState('prepare.location');
		$registration = $model->getState('prepare.registration');
		$refresh      = $model->getState('prepare.categories.refresh');
		
		// set the state based on the module params
		/*if ('' == $params->get('date', '')) {
			$model->setState('filter.date_range', 6);
		}*/
		$model->setState('filter.layout', 'month');
		$model->setState('filter.category', $params->get('filter_category', array()));
		$model->setState('filter.catid', 0);
		$model->setState('filter.search', '');
		$model->setState('prepare.location', false);
		$model->setState('prepare.registration', false);
		$model->setState('prepare.categories.refresh', true);
		
		// handle filters
		$filters = $model->getCategoryFilters();
		$invert  = $model->getCategoryFiltersInvert();
		$model->setCategoryFilters($params->get('filter_category', array()));
		$model->setCategoryFiltersInvert($params->get('filter_category_invert', false));
		
		// get the events from the model
		$items = $model->getItems();
		
		// add module date to the cell
		$default = 'l, F d, Y';
		$format  = $params->get('tooltip_date_format', $default);
		foreach ($items as &$item) {
			try {
				$item['mod_calendar_date'] = $item['user_datetime']->format($format);
			}
			catch (Exception $ex) {
				$item['mod_calendar_date'] = $item['user_datetime']->format($default);
			}
		}
		
		// reset the state
		// TODO: is this necessary?
		$model->setState('filter.layout', $layout);
		$model->setState('filter.category', $categories);
		$model->setState('filter.catid', $catid);
		$model->setState('filter.search', $search);
		$model->setState('prepare.location', $location);
		$model->setState('prepare.registration', $registration);
		$model->setState('prepare.categories.refresh', $refresh);
		$model->setCategoryFilters($filters);
		$model->setCategoryFiltersInvert($invert);
		
		$profiler->mark('onJCalProCalendarModuleGetListEnd');
		
		return $items;
	}
	
	public static function getDates() {
		static $dates;
		
		if (!isset($dates)) {
			// Get an instance of the events model
			$model = JCalPro::getModelInstance('Events', 'JCalProModel', array('ignore_request' => true));
			
			$dates = $model->getAllTheDates();
		}
		
		return $dates;
	}
	
	public static function addScripts($module, $params) {
		// get the variables we'll need
		$script  = array('');
		$dates   = self::getDates();
		$urlbase = array('format' => 'raw', 'id' => $module->id, 'module' => 'mod_jcalpro_calendar');
		$prevurl = JCalProHelperUrl::task('module', false, array_merge($urlbase, array('params[date]' => $dates->prev_month->toRequest())));
		$nexturl = JCalProHelperUrl::task('module', false, array_merge($urlbase, array('params[date]' => $dates->next_month->toRequest())));
		
		// start building the dynamic parts
		
		// start/continue using this module's global object
		$script[] = 'window.mod_jcalpro_calendar = window.mod_jcalpro_calendar||{};';
		// create a new module instance
		$script[] = 'window.mod_jcalpro_calendar.mod' . $module->id . ' = {';
		$script[] = '	prev:"' . JCalProHelperFilter::escape_js($prevurl) . '"';
		$script[] = ',	next:"' . JCalProHelperFilter::escape_js($nexturl) . '"';
		$script[] = ',	tips: false';
		$script[] = '};';
		$script[] = '';
		
		// try to add it to the document
		$document = JFactory::getDocument();
		if (method_exists($document, 'addScript') && 'raw' != JFactory::getApplication()->input->get('format', '', 'cmd')) {
			$document->addScript(JCalProHelperUrl::media() . '/js/jcalpro.js');
			$document->addScript(JCalProHelperUrl::media() . '/modules/calendar/js/ajax.js');
			$document->addScriptDeclaration(implode("\n", $script));
		}
		else {
			echo '<script type="text/javascript">' . implode("\n", $script) . '</script>';
		}
	}
}
