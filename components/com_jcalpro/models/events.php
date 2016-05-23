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

JLoader::register('JCalProListEventsModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodelevents.php');

/**
 * This models supports retrieving lists of events.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelEvents extends JCalProListEventsModel
{
	private $_parent = null;

	private $_items = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = 'Event.start_date', $direction = 'ASC')
	{
		parent::populateState($ordering, $direction);
		$app  = JFactory::getApplication();
		$user = JFactory::getUser();
		// only set the published/approved explicitly if the extmode layout is not admin
		// TODO: use the user state to control this instead
		$layout = $app->input->get('layout', '', 'cmd');
		$ismodule = $app->getUserState($this->context.'.jcalpro.module', false);
		
		if ($app->getLanguageFilter()) {
			$this->setState('filter.language', JFactory::getLanguage()->getTag());
		}
		
		if ('admin' != $layout && !$ismodule) {
			$this->setState('filter.published',	1);
			$this->setState('filter.approved',	1);
		}
		else {
			if ('admin' == $layout) {
				$value = $this->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
				if (empty($value)) {
					$value = $app->getCfg('list_limit');
				}
				if (empty($value)) {
					$value = 20;
				}
				$this->setState('list.limit', $value);
			}
			$value = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
			$this->setState('filter.published',	$value);
			
			if (JCalPro::canModerateEvents()) {
				$value = $app->getUserStateFromRequest($this->context.'.filter.approved', 'filter_approved', '');
				$this->setState('filter.approved', $value);
			}
			else {
				$this->setState('filter.approved', 1);
				$this->setState('filter.created_by', $user->id);
			}
			
			$value = $app->getUserStateFromRequest($this->context.'.filter.date_range', 'filter_date_range', '');
			$this->setState('filter.date_range', $value);
		}
		
		$this->setState('filter.layout', $layout);
		
		$catid = $app->getUserStateFromRequest($this->context.'.filter.catid', 'filter_catid', '');
		$this->setState('filter.catid', $catid);
		
		$value = $this->getUserStateFromRequest($this->context.'.filter.registration', 'filter_registration', '');
		$this->setState('filter.registration', $value);
		
		$params = JCalPro::config();
		$this->setState('params', $params);
		
		// we have to set this AFTER the component params
		//$catfilter = $params->get('filter_category');
		//$invertcatfilter = $params->get('filter_category_invert');
		$catfilter = $this->getCategoryFilters();
		$invertcatfilter = $this->getCategoryFiltersInvert();
		// check if the category filter is empty
		// if it is, just set the state as an empty array
		// TODO: test if "Root" by itself comes in as a real array or not
		if (empty($catfilter)) {
			$this->setState('filter.category', array());
		}
		// we have values!
		else {
			// this is either going to be an integer value or an array
			if (!is_array($catfilter)) {
				$catfilter = array($catfilter);
			}
			// ok, we should have an array now - force it to be integers
			JArrayHelper::toInteger($catfilter);
			// before we can set into the state, we need to ensure we DON'T have "0" in there
			$cats = array();
			foreach ($catfilter as $cf) if ($cf) $cats[] = $cf;
			// before we can set the final filter, we should check the invert param
			// if we're inverting, we'll replace the current array with a new one
			// this way, we don't have to adjust the base model as it may cause issues in admin
			if ($invertcatfilter) {
				$categories = $this->getCategories();
				$foundcats = array();
				foreach ($categories as $category) {
					if (in_array($category->id, $cats)) continue;
					$foundcats[] = $category->id;
				}
				$cats = $foundcats;
			}
			// set our filtered array into the state
			$this->setState('filter.category', $cats);
		}
		
		$this->setState('prepare.categories', true);
		$this->setState('prepare.location', true);
		$this->setState('prepare.registration', true);
		
		$this->setState('list.featured', 1);
	}
	
	/**
	 * Method to generate the database query
	 * 
	 * overriding this to filter by dates (in case archive mode is off)
	 * 
	 */
	protected function getListQuery() {
		$db = $this->getDbo();
		
		// get the query from our base model
		$query = parent::getListQuery();
		
		// only allow private events to be seen by their owners
		$user = JFactory::getUser();
		if ($user->id) {
			$query->where('(Event.private = 0 OR (Event.private = 1 AND Event.created_by = ' . (int) $user->id . '))');
		}
		else {
			$query->where('Event.private = 0');
		}
		
		// filter by user
		$filter = $this->getState('filter.created_by');
		if (is_numeric($filter)) {
			$query->where('Event.created_by = ' . (int) $filter);
		}

		// Filter by published & approved state
		if ('admin' != JFactory::getApplication()->input->get('layout', '', 'cmd')) {
			$query->where('Event.published = 1');
			$query->where('Event.approved = 1');
		}
		else {
			$published = $this->getState('filter.published', '');
			if ('' === $published) {
				$query->where('(Event.published = 1 OR Event.published = 0)');
			}
			else {
				$query->where('Event.published = ' . (int) $published);
			}
		}
		
		// Filter by dates
		$start_date   = $this->getState('filter.start_date');
		$end_date     = $this->getState('filter.end_date');
		
		// start by loading the requested day from the helper and/or state
		// check for empties & rectify
		if (empty($start_date)) {
			$start_date = JCalProHelperDate::JCL_DATE_MIN;
			// take note this will contain today's date data if the helper couldn't parse the request date
			$day        = JCalProHelperDate::getDate();
		}
		else {
			// set the day based on the start date filter, if possible
			try {
				jimport('jcaldate.date');
				$day = new JCalDate($start_date);
			}
			catch (Exception $e) {
				$day = JCalProHelperDate::getDate();
			}
		}
		
		// check that end is empty (or not)
		if (empty($end_date)) {
			$end_date = JCalProHelperDate::JCL_DATE_MAX;
		}
		
		// the different start & end date filters are dependent upon the layout being accessed
		// go ahead and check each layout type
		// later, we will be re-calculating the start_date if archive is OFF
		$layout = $this->getState('filter.layout');
		// if archive mode is active, we want to alter the query to account for this
		// archive = show past events
		$archive = JCalPro::config('archive') && false === $this->getState('filter.ignore_archive', false);
		// we need a DateTime object that represents the beginning of the day
		// or, in the case of month/flat, the first day of the month
		$datetime = JCalProHelperDate::getDateTimeFromParts(0, 0, 0, $day->month(), (in_array($layout, array('month', 'flat')) ? 1 : $day->day()), $day->year());
		// the "week" layout is the only one that requires a subtracted interval
		// UPDATE: now using week_start from dates
		if ('week' == $layout) {
			// we need to figure out what day of the week it is so we can adjust the DateTime
			// this will output 0 (Sunday) through 6 (Saturday)
			$dayofweek = $datetime->weekday();
			// adjust the dayofweek variable based on the config
			if ((int) JCalPro::config('day_start', 0)) { // if monday is the first day
				$dayofweek = $dayofweek - 1; // weekday as a decimal number [0,6], with 0 representing Monday
				$dayofweek = ($dayofweek == -1) ? 6 : $dayofweek;
			}
			// we only want to actually subtract if the requested day is not the beginning of the week
			if ($dayofweek) {
				// subtract our interval from the DateTime object
				$datetime->subDay($dayofweek);
			}
		}
		// force this DateTime object to use a UTC timezone
		$datetime->toUtc();
		// set the start_date
		$start_date = $datetime->toSql();
		// clone the DateTime object for use later :)
		$start_datetime = clone $datetime;
		// handle daily layout
		// we're only looking at one day - get the day's date from the date helper,
		// calculate the beginning and end of the day,
		// and use those to set the start and end dates
		if ('day' == $layout) {
			// we need to move this DateTime to the end of the day
			// however, this will give us the wrong time, depending on the user's timezone
			// so what we need to do is convert the DateTime back to user time before moving,
			// THEN go back to UTC
			$end_date = $datetime->toUser()->toDayEnd()->toUtc()->toSql();
		}
		// handle weekly layout - we need to know the beginning and ending of the week
		else if ('week' == $layout) {
			// now comes the easy part - make a new interval that is 6 days, 23 hours, 59 minutes and 59 seconds
			$end_date = $datetime->toUser()->toDayEnd()->addDay(6)->toUtc()->toSql();
		}
		// handle month/flat layout
		else if ('month' == $layout || 'flat' == $layout) {
			$end_date = $datetime->toUser()->toDayEnd()->addMonth()->toUtc()->toSql();
		}
		// special case for "all"
		else if ('all' == $layout && 'ical' == $this->getState('filter.format')) {
			$this->setState('filter.date_range', $archive ? JCalPro::RANGE_ALL : JCalPro::RANGE_UPCOMING_END);
		}
		
		// now that we have our start_date and end_date filters, reset the filter
		$this->setState('filter.start_date', $start_date);
		$this->setState('filter.end_date',   $end_date);
		
		// make our dates sql-safe (they should already be safe, but they need quoted)
		$start_sql = $db->Quote($start_date);
		$end_sql   = $db->Quote($end_date);
		
		if (!$archive) {
			// we need a NEW DateTime for today *sigh*
			// TODO: should we also use hours and minutes here?
			$datetime = JCalProHelperDate::getToday()->toUtc();
			if ($start_datetime < $datetime) {
				// today's timestamp quoted for sql
				$start_sql = $db->Quote($datetime->toSql());
			}
		}
		
		// define this now
		$event_condition = '';
		
		// add ranges for events layouts
		$search = $this->getState('filter.search');
		// date ranges, admin, category don't require filtering
		// nor does search if past events are allowed
		if (is_numeric($this->getState('filter.date_range')) || in_array($layout, array('admin', 'category')) || (!empty($search) && $archive)) {
			// here we're doing nothing :)
		}
		else {
			// conditions on date of the event
			$event_condition  = "(( Event.start_date <= $start_sql AND Event.end_date >= $end_sql"
			." AND Event.end_date != " . $db->Quote(JCalProHelperDate::JCL_ALL_DAY_EVENT_END_DATE)
			." AND Event.end_date != " . $db->Quote(JCalProHelperDate::JCL_ALL_DAY_EVENT_END_DATE_LEGACY)
			." AND Event.end_date != " . $db->Quote(JCalProHelperDate::JCL_ALL_DAY_EVENT_END_DATE_LEGACY_2)
			. ") ";
			$event_condition .= " OR ((Event.start_date >= $start_sql AND Event.start_date < $end_sql) "
			." AND (Event.end_date = " . $db->Quote(JCalProHelperDate::JCL_ALL_DAY_EVENT_END_DATE)
			." OR Event.end_date = "   . $db->Quote(JCalProHelperDate::JCL_ALL_DAY_EVENT_END_DATE_LEGACY)
			." OR Event.end_date = "   . $db->Quote(JCalProHelperDate::JCL_ALL_DAY_EVENT_END_DATE_LEGACY_2)
			. ")) ";
			$event_condition .= "  OR ( Event.start_date > $start_sql AND Event.start_date <= $end_sql)";
			$event_condition .= "  OR ( Event.end_date > $start_sql AND Event.end_date <= $end_sql ) )";
		}
		// add condition
		if (!empty($event_condition)) {
			$query->where("($event_condition)");
		}
		
		return $query;
	}
	
	public function getLinkData() {
		JCalPro::registerHelper('filter');
		$input    = JFactory::getApplication()->input;
		$dates    = $this->getAllTheDates();
		$layout   = $this->getState('filter.layout');
		$ajax     = JCalPro::config('enable_ajax_features');
		$archive  = JCalPro::config('archive'); // show PAST events
		$archived = false;
		// start building our link data
		$link = array('href' => '#', 'text' => '', 'shorttext' => '');
		$data = array('prev' => $link, 'next' => $link, 'current' => '');
		// we may need to pass an Itemid, so build an $extra array
		$extra = array();
		// we need to override the Itemid in these views, in case there are multiple Itemids
		$Itemid = $input->get('Itemid', 0, 'int');
		if ($Itemid) $extra['Itemid'] = $Itemid;
		// build our link data
		switch ($layout) {
			case 'month':
			case 'flat':
				// attempt to use the custom formats, fall back to original defaults
				$default = JText::_('COM_JCALPRO_DATE_FORMAT_MONTH_YEAR');
				$format  = JCalPro::config($layout . '_format_navigation', $default);
				$layout  = 'month'; // force this for flat layout
				try {
					$current = $dates->date->format($format);
					$next    = $dates->next_month->format($format);
					$prev    = $dates->prev_month->format($format);
				}
				catch (Exception $e) {
					$current = $dates->date->format($default);
					$next    = $dates->next_month->format($default);
					$prev    = $dates->prev_month->format($default);
				}
				// set formatted text
				$data['current']           = JCalProHelperFilter::escape($current);
				$data['next']['text']      = JCalProHelperFilter::escape($next);
				// NOTE: no longer using shorttext, fill for BC only
				$data['next']['shorttext'] = $data['next']['text'];
				if (!(!$archive && $dates->date_month == $dates->today_month && $dates->date_year == $dates->today_year)) {
					$data['prev']['text']      = JCalProHelperFilter::escape($prev);
					$data['prev']['shorttext'] = $data['prev']['text'];
				}
				break;
			case 'week':
				$data['current']      = JText::sprintf('COM_JCALPRO_WEEK_SELECTED_WEEK', $dates->week_number);
				$data['next']['text'] = JText::_('COM_JCALPRO_WEEK_NEXT_WEEK');
				$today_week_start = clone $dates->today;
				$today_week_start->toWeekStart();
				if (!(!$archive && $dates->week_start <= $today_week_start)) {
					$data['prev']['text'] = JText::_('COM_JCALPRO_WEEK_PREVIOUS_WEEK');
				}
				break;
			case 'day':
				// attempt to use the custom formats, fall back to original defaults
				$default = JText::_('COM_JCALPRO_DATE_FORMAT_FULL_DATE');
				$format  = JCalPro::config('day_format_header', $default);
				try {
					$current = $dates->date->format($format);
				}
				catch (Exception $e) {
					$current = $dates->date->format($default);
				}
				$data['current']      = $current;
				$data['next']['text'] = JText::_('COM_JCALPRO_DAY_NEXT_DAY');
				if (!(!$archive && $dates->today >= $dates->date)) {
					$data['prev']['text'] = JText::_('COM_JCALPRO_DAY_PREVIOUS_DAY');
				}
				break;
			// this is for event selection
			case 'modal':
			// this is for admin
			case 'admin':
			// special case for ical
			case 'all':
				return array();
			// no known layout
			default:
				$this->setError(JText::_('COM_JCALPRO_LAYOUT_NOT_FOUND'));
				return false;
		}
		// we can safely assume that raw requests are for ajax mode, so save the client the extra processing
		if ($ajax && 'raw' == $input->get('format', '', 'cmd')) {
			$data['prev']['href'] = ($archived ? 'javascript:void(0);' : '#' . $dates->{"prev_$layout"}->toRequest());
			$data['next']['href'] = '#' . $dates->{"next_$layout"}->toRequest();
		}
		else {
			$data['prev']['href'] = $archived ? 'javascript:void(0);' : JCalProHelperUrl::events($dates->{"prev_$layout"}->toRequest(), $this->getState('filter.layout'), true, $extra);
			$data['next']['href'] = JCalProHelperUrl::events($dates->{"next_$layout"}->toRequest(), $this->getState('filter.layout'), true, $extra);
		}
		// toolbar
		
		
		return $data;
	}
	
	public function getPending() {
		// ask the db what events are pending
		$db = JFactory::getDbo();
		$db->setQuery((string) $db->getQuery(true)
			->select('Event.id')
			->select('Xref.category_id AS catid')
			->from('#__jcalpro_events AS Event')
			->leftJoin('#__jcalpro_event_categories AS Xref ON Xref.event_id = Event.id AND Xref.canonical = 1')
			->where('Event.approved = 0')
			->where('(Event.published = 1 OR Event.published = 0)')
			->group('Event.id')
		);
		$results = $db->loadObjectList();
		// if no results, return 0
		if (empty($results)) return 0;
		// now we need to figure out which ones we are allowed to moderate
		$count   = 0;
		$allowed = array();
		$checked = array();
		foreach ($results as $result) {
			// we start by checking if we're allowed to moderate this category
			if (!in_array($result->catid, $checked)) {
				$checked[] = $result->catid;
				// if we're allowed to moderate this category, mark it as such
				if (JCalPro::canModerateEvents($result->catid)) {
					$allowed[] = $result->catid;
				}
			}
			// by the time we get here, the category should be checked :)
			if (in_array($result->catid, $allowed)) {
				$count++;
			}
		}
		return $count;
	}
}
