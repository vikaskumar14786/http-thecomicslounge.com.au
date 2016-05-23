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

jimport('joomla.application.component.model');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');
JCalProBaseModel::addIncludePath(JPATH_SITE.'/components/com_jcalpro/models', 'JCalProModel');

abstract class modJCalProEventsHelper
{
	/**
	 * use constants from JCalPro helper instead of these (values match)
	 * 
	 * @deprecated
	 */
	const RANGE_PAST_EVENTS     = 1;
	const RANGE_UPCOMING_EVENTS = 2;
	const RANGE_THIS_WEEK       = 3;
	const RANGE_LAST_WEEK       = 4;
	const RANGE_NEXT_WEEK       = 5;
	const RANGE_THIS_MONTH      = 6;
	const RANGE_LAST_MONTH      = 7;
	const RANGE_NEXT_MONTH      = 8;
	const RANGE_TODAY           = 9;
	const RANGE_TOMORROW        = 10;
	const RANGE_YESTERDAY       = 11;
	// TODO add 12 & 13 (next/last 30)?
	const RANGE_ONGOING         = 14;
	
	/**
	 * Start time display options
	 */
	const TIME_OFF   = 0;
	const TIME_ON    = 1;
	const TIME_START = 2;
	
	/**
	 * Featured display options
	 */
	const FEATURED_IGNORE    = 0;
	const FEATURED_HIGHLIGHT = 1;
	const FEATURED_ONLY      = 2;
	
	/**
	 * Gets a list of events based on the given params
	 * 
	 * @param object $params
	 * @return array list of events
	 * @return bool  false if there was an issue?
	 */
	public static function getList(&$params) {
		
		$profiler = JProfiler::getInstance('Application');
		$profiler->mark('onJCalProCalendarModuleGetListStart');
		
		// Get the dbo
		$db = JFactory::getDbo();
		
		$app = JFactory::getApplication();
		
		// wtf?!?
		$oldsearch = $app->input->get('filter_search', '');
		$app->input->set('filter_search', '');

		// Get an instance of the events model
		$model = JCalProBaseModel::getInstance('Events', 'JCalProModel', array('ignore_request' => true));
		$model->clearModelCache();
		
		$range    = (int) $params->get('filter_date_range', JCalPro::RANGE_UPCOMING);
		$featured = (int) $params->get('featured', modJCalProEventsHelper::FEATURED_HIGHLIGHT);
		
		$lfeatured    = $model->getState('list.featured');
		$ffeatured    = $model->getState('filter.featured');
		$flocation    = $model->getState('filter.location');
		$layout       = $model->getState('filter.layout');
		$categories   = $model->getState('filter.category');
		$catid        = $model->getState('filter.catid');
		$search       = $model->getState('filter.search');
		$location     = $model->getState('prepare.location');
		$registration = $model->getState('prepare.registration');
		$refresh      = $model->getState('prepare.categories.refresh');
		
		// set the state based on the module params
		$model->setState('list.limit', $params->get('list_limit', 5));
		$model->setState('filter.category', $params->get('filter_category', array()));
		$model->setState('filter.catid', 0);
		$model->setState('filter.location', $params->get('filter_location', array()));
		$model->setState('filter.search', $params->get('filter_search', ''));
		$model->setState('prepare.location', (bool) $params->get('display_location', 0));
		$model->setState('prepare.registration', (bool) $params->get('display_registration', 0));
		$model->setState('prepare.categories.refresh', true);
		
		$highlight = true;
		switch ($featured) {
			case modJCalProEventsHelper::FEATURED_HIGHLIGHT:
				$model->setState('list.featured', 1);
				break;
			case modJCalProEventsHelper::FEATURED_ONLY:
				$model->setState('filter.featured', 1);
				break;
			case modJCalProEventsHelper::FEATURED_IGNORE:
			default:
				$highlight = false;
				break;
		}
		
		switch ($range) {
			// events from the past should be ordered in reverse
			case JCalPro::RANGE_PAST:
			case JCalPro::RANGE_PAST_END:
			case JCalPro::RANGE_LAST_WEEK:
			case JCalPro::RANGE_LAST_MONTH:
			case JCalPro::RANGE_YESTERDAY:
			case JCalPro::RANGE_LAST_30:
			case JCalPro::RANGE_LAST_YEAR:
				$model->setState('list.ordering', 'Event.start_date');
				$model->setState('list.direction', 'DESC');
				// NOTE: no break here!!!
			default:
				$model->setState('filter.date_range', $range);
		}
				
		// handle filters
		$filters = $model->getCategoryFilters();
		$invert  = $model->getCategoryFiltersInvert();
		$model->setCategoryFilters($params->get('filter_category', array()));
		$model->setCategoryFiltersInvert($params->get('filter_category_invert', false));
		
		// get the events from the model
		$items = $model->getItems();
		
		JCalPro::debugger('Model', $model, 'mod_jcalpro_events');
		JCalPro::debugger('Items', $items, 'mod_jcalpro_events');
		
		// we're going to alter the items based on these params
		$display_date = (int) $params->get('display_date', 1);
		$display_time = (int) $params->get('display_time', 1);
		$date_format  = $params->get('date_format', '');
		$time_format  = $params->get('time_format', '');
		$default_date = JText::_('COM_JCALPRO_DATE_FORMAT_MINI_DATE');
		$default_time = JCalProHelperDate::getUserTimeFormat();
		
		// if no formats given, force to defaults
		if (empty($date_format)) {
			$date_format = $default_date;
		}
		if (empty($time_format)) {
			$time_format = $default_time;
		}
		
		// loop items and prepare them for the module
		if (!empty($items)) {
			foreach ($items as &$item) {
				// set initial values
				$item->mod_events_date  = '';
				$item->mod_events_class = 'jcalpro_events_event';
				if ($highlight && $item->featured) {
					$item->mod_events_class .= ' jcalpro_events_featured';
				}
				// set the display date
				if ($display_date || $display_time) {
					// we shouldn't use minidisplay here - it doesn't account for future end dates
					// instead we'll just build our own based on that
					// there are a couple different formats to consider:
					// * event that occurs on a single day with no end time
					// * event that occurs on a single day with start & end times
					// * event that occurs over multiple days
					$sd = $item->user_datetime;
					// BUGFIX: not all events have user_end_datetime
					$ed = property_exists($item, 'user_end_datetime') ? $item->user_end_datetime : false;
					
					// test the formatting to ensure it's correct
					// NOTE only need to test the date/time formats once
					try {
						$sdd = $sd->format($date_format);
					}
					catch (Exception $e) {
						$date_format = $default_date;
						$sdd = $sd->format($date_format);
					}
					
					try {
						$sdt = $sd->format($time_format);
					}
					catch (Exception $e) {
						$time_format = $default_time;
						$sdt = $sd->format($time_format);
					}
					
					// if we're only showing start times, go ahead and just set that no matter what
					if (modJCalProEventsHelper::TIME_START == $display_time) {
						$item->mod_events_date = trim(($display_date ? $sdd . ' ' : '') . $sdt);
					}
					// easy to check for multiple days
					else if (!empty($item->multidays)) {
						// this event spans more than one day
						// add the date & time format for both
						$item->mod_events_date = trim(($display_date ? $sdd : '') . ($display_time ? ' ' . $sdt : ''));
						// if the end exists and we're adding the date, we can simply add
						if ($ed && $display_date) {
							$item->mod_events_date .= ' - ' . $ed->format($date_format) . ($display_time ? ' ' . $ed->format($time_format) : '');
						}
						// if the end exists and we are only adding the time, we have to check the duration
						else if ($ed && $display_time) {
							// if the duration is less than 24 hours, then it's okay to just show times
							// but durations over 24 hours without dates cannot accurately display
							// for example, an event that lasts 25 hours and starts at 1pm
							// might end up displaying 1pm - 2pm, which would make it appear to only last one hour
							$sdcheck = clone $sd;
							// TODO DST? edge case here?
							if ($sdcheck->addDay() > $ed) {
								$item->mod_events_date .= ' - ' . $ed->format($time_format);
							}
						}
					}
					// no end should just show start
					else if (JCalPro::JCL_EVENT_DURATION_NONE == $item->duration_type) {
						$item->mod_events_date = trim(($display_date ? $sdd : '') . ($display_time ? ' ' . $sdt : ''));
					}
					// all day should just show start
					else if (JCalPro::JCL_EVENT_DURATION_ALL == $item->duration_type) {
						$item->mod_events_date = trim(($display_date ? $sdd : '') . ($display_date && $display_time ? ' (' : '') . ($display_time ? JText::_('COM_JCALPRO_ALL_DAY') : '') . ($display_date && $display_time ? ')' : ''));
					}
					// has a definite end time that is on the same day
					// show without time
					else if (modJCalProEventsHelper::TIME_OFF == $display_time) {
						$item->mod_events_date = ($display_date ? $sdd : '');
					}
					// show with time
					else {
						if ($display_date) {
							if ($ed) {
								$item->mod_events_date = JText::sprintf('COM_JCALPRO_DATE_TEMPLATE_RANGE', $sdd, $sdt, $ed->format($time_format));
							}
							else {
								$item->mod_events_date = JText::sprintf('COM_JCALPRO_DATE_TEMPLATE_DISPLAY', $sdd, $sdt);
							}
						}
						else {
							if ($ed) {
								$item->mod_events_date = $sdt . ' - ' . $ed->format($time_format);
							}
							else {
								$item->mod_events_date = $sdt;
							}
						}
					}
				}
			}
		}
		
		// reset state
		$model->setState('list.featured', $lfeatured);
		$model->setState('filter.featured', $ffeatured);
		$model->setState('filter.layout', $layout);
		$model->setState('filter.location', $flocation);
		$model->setState('filter.category', $categories);
		$model->setState('filter.catid', $catid);
		$model->setState('filter.search', $search);
		$model->setState('prepare.location', $location);
		$model->setState('prepare.registration', $registration);
		$model->setState('prepare.categories.refresh', $refresh);
		$model->setCategoryFilters($filters);
		$model->setCategoryFiltersInvert($invert);
		
		$app->input->set('filter_search', $oldsearch);
		
		$profiler->mark('onJCalProCalendarModuleGetListEnd');

		return $items;
	}
	
	public static function renderFields($fields, $item) {
		static $loaded;
		if (is_null($loaded)) {
			jimport('joomla.html.html');
			JCalPro::registerHelper('site');
			JHtml::addIncludePath(JCalProHelperPath::site() . '/helpers/html');
			$loaded = true;
		}
		// start our output
		$html = array();
		// parse fields, if needed
		if (is_string($fields) && !empty($fields)) {
			$fields = explode(',', $fields);
		}
		// no fields? done...
		if (!is_array($fields) || empty($fields)) {
			return implode("\n", $html);
		}
		// get the field objects from the database
		JArrayHelper::toInteger($fields);
		$db = JFactory::getDbo();
		try {
			$fieldObjs = $db->setQuery($db->getQuery(true)
				->select('*')
				->from('#__jcalpro_fields')
				->where($db->quoteName('id') . ' IN(' . implode(',', $fields) . ')')
				->where($db->quoteName('published') . ' = 1')
			)->loadObjectList();
		}
		catch (Exception $e) {
			return implode("\n", $html);
		}
		// no fields found? done...
		if (empty($fieldObjs)) {
			return implode("\n", $html);
		}
		// add the fields to the output
		foreach ($fieldObjs as $field) {
			if (!(is_array($item->params) && array_key_exists($field->name, $item->params))) {
				continue;
			}
			$html[] = '<span class="jcalpro_events_custom_field">';
			$html[] = JHtml::_('jcalpro.formfieldvalue', $field, $item->params[$field->name]);
			$html[] = '</span>';
		}
		
		return implode("\n", $html);
	}
}
