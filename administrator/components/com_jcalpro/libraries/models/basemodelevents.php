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

jimport('joomla.application.categories');
jimport('joomla.error.profiler');

$base    = JPATH_ADMINISTRATOR . '/components/com_jcalpro';
$models  = "$base/libraries/models";

JLoader::register('JCalPro', "$base/helpers/jcalpro.php");
JCalPro::registerHelper('filter');
JCalPro::registerHelper('tags');
JCalPro::registerHelper('url');
JLoader::register('JCalProBaseModel', "$models/basemodel.php");
JLoader::register('JCalProListModel', "$models/basemodellist.php");

// load the event-specific language file
JCalPro::language('com_jcalpro.event', JPATH_ADMINISTRATOR);

// load content plugins
JPluginHelper::importPlugin('content');

/**
 * This model supports retrieving lists of events.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProListEventsModel extends JCalProListModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $context  = 'com_jcalpro.events';

	/**
	 * The category context (allows other extensions to derived from this model).
	 *
	 * @var		string
	 */
	protected $_extension = 'com_jcalpro';

	private $_parent = null;

	private $_items = null;
	
	private $_itemcount = null;
	
	private $_xref = null;
	
	private $_categoryFilters = array();
	
	private $_categoryFiltersInvert = false;
	
	/**
	 * used to translate the various dates used in layouts
	 * 
	 * @var array
	 */
	protected $format_layouts = array('month', 'flat', 'week', 'day', 'search', 'category', 'event', 'location');
	
	/**
	 * Constructor.
	 *
	 * @param       array   An optional associative array of configuration settings.
	 * @see         JController
	 */
	function __construct($config = array()) {
		$profiler = JProfiler::getInstance('Application');
		$profiler->mark('onJCalProEventsModelConstructStart');
		// set filter fields
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'Event.id'
			,	'Event.title'
			,	'Event.timezone'
			,	'Event.start_date'
			,	'Event.end_date'
			,	'Event.recur_type'
			,	'Event.created_by'
			,	'Event.published'
			,	'Event.approved'
			,	'Event.featured'
			);
		}
		// get data for adding categories
		$this->_categoryFilters       = $this->setCategoryFilters();
		$this->_categoryFiltersInvert = JCalPro::config('filter_category_invert');
		//$this->_xref                  = $this->getCategoryXref();
		parent::__construct($config);
		$profiler->mark('onJCalProEventsModelConstructEnd');
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = 'Event.start_date', $direction = 'ASC') {
		parent::populateState($ordering, $direction);
		$this->setState('filter.start_date', 0);
		$this->setState('filter.end_date', 0);
		$this->setState('filter.access', true);
		
		$this->setState('prepare.categories', true);
		$this->setState('prepare.categories.refresh', false);
		$this->setState('prepare.location', true);
		$this->setState('prepare.registration', true);
		
		$this->setState('parent.category', false);
		
		
		$this->setState('filter.location', JCalPro::config('filter_location'));
		
		
		foreach (array('link', 'title', 'start_date', 'end_date', 'description', 'itemid') as $insert) {
			$value = $this->getUserStateFromRequest($this->context.'.insert.'.$insert, 'insert_'.$insert, '', 'int');
			$this->setState('insert.'.$insert, $value);
		}
		
		$catid = $this->getUserStateFromRequest($this->context.'.jcal.catid', 'filter_catid', '', 'int');
		$this->setState('jcal.catid', $catid);
		
		$this->setState('list.featured', 0);
	}
	
	/**
	 * Method to retrieve items from the database
	 * 
	 * This is overloaded in the base model so we can alter the item data
	 */
	public function getItems() {
		$profiler   = JProfiler::getInstance('Application');
		$profiler->mark('onJCalProEventsModelGetItemsStart');
		// item variables
		$items      = parent::getItems();
		$itemcount  = 0;
		// after getting our items, we need to build our xref lookup
		// TODO: check the rest of the codebase & see if this will break elsewhere
		if (is_null($this->_xref)) {
			$this->_xref = $this->getCategoryXref($items);
		}
		// build our stack array now, so when we loop later we can just add to the stack
		// we have to be careful how we build the stack, as we can have major issues if we try
		// using a "month" based stack in the week layout, or any stack in day layout
		$event_stack = array();
		$final_items = array();
		$layout      = $this->getState('filter.layout');
		//$catid       = (int) $this->getState('filter.catid');
		// check if we're filtering by category
		// this is different than the catid filter, so take note
		// "catid" is a user-changeable filter for the frontend and only supports a single catid
		// "categories" is an array of categories to show
		// TODO: should we ensure "catid" is in "category"?
		//$catfilters  = $this->getState('filter.category');
		//if (is_array($catfilters)) {
			// "fix" root
			//asort($catfilters);
			//$catfilters = array_values($catfilters);
			//if (!empty($catfilters) && 0 == $catfilters[0]) array_shift($catfilters);
		//}
		// we need to ONLY add to the stack if we're not in admin or in a stacked layout
		// because these need the generic array, not the full stack
		$stack_add   = !(JFactory::getApplication()->isAdmin() || !in_array($layout, array('month', 'flat', 'week')));
		// fix our stack_add for ical creation :)
		if ('ical' == strtolower($this->getState('filter.format'))) {
			$stack_add = false;
		}
		// fix our stack_add for rss too
		if ('feed' == strtolower($this->getState('filter.format'))) {
			$stack_add = false;
		}
		// here's where we build the stack array
		// we don't want to waste the resources building the stack if we're not going to use it though!!
		if ($stack_add) {
			// now we build the stack
			// the week stack is different than the month/flat stack
			if ('week' == $layout) {
				// preload the master date storage object so we don't have to calculate our weeks
				$dates = $this->getAllTheDates();
				// we're going to use the week_start DateTime object from our dates as the starting point
				// this DateTime object will remain static throughout our loop
				// NOTE: week_start is in USER TIME
				$basedatetime = clone $dates->week_start;
				// we need to make sure we're in UTC here
				//$basedatetime->toUtc();
				// for this layout we're going to loop X days
				for ($i=1; $i<=$basedatetime->daysInWeek(); $i++) {
					// go ahead and set our key
					$key = (int) $basedatetime->day();
					// make sure this is an array with an internal events array
					if (!isset($event_stack[$key])) $event_stack[$key] = array('events' => array());
					// save the user DateTime object
					$event_stack[$key]['user_datetime'] = clone $basedatetime;
					// we already have our base DateTime object so clone & add
					$event_stack[$key]['utc_datetime'] = clone $basedatetime;
					$event_stack[$key]['utc_datetime']->toUtc();
					// now that we have our stack DateTime objects, increment the base by one day
					$basedatetime->addDay();
				}
				// all done with our limited stack, free up some resources
				unset($basedatetime);
			}
			// month/flat stack
			else {
				// we'll want to check which day in our stack is today, if any
				$today = JCalProHelperDate::getToday();
				// base the stack of requested date
				$date = JCalProHelperDate::getDate();
				// start the loop
				for ($i=1; $i<=$date->daysInMonth(); $i++) {
					// make sure this is an array with an internal events array
					if (!isset($event_stack[$i])) $event_stack[$i] = array('events' => array());
					// get the week number for the stack
					$event_stack[$i]['week_number'] = (int) JCalProHelperDate::getWeekNumber($i, $date->month(), $date->year());
					// in order to prevent having to do calculations in our templates later,
					// go ahead and create DateTime objects for each day
					$stackdatetime = JCalProHelperDate::getDateTimeFromParts(0, 0, 0, $date->month(), $i, $date->year(), JCalTimeZone::user());
					// save user DateTime
					$event_stack[$i]['user_datetime'] = clone $stackdatetime;
					$event_stack[$i]['dayNum'] = $stackdatetime->dayNum();
					$event_stack[$i]['weekday'] = $stackdatetime->weekday();
					// we only need these classes for month layout
					if ('month' == $layout) {
						// set the day cell class, default is weekday
						$class = 'weekdayclr';
						// this may be today - if so set the today class
						if ($today == $stackdatetime) {
							$class = 'todayclr';
						}
						// this may be Sunday - if so set the Sunday class
						else if (0 == $stackdatetime->weekday()) {
							$class = 'sundayemptyclr';
						}
						// set the day class
						$event_stack[$i]['class'] = $class;
					}
					// force the DateTime to use UTC
					$stackdatetime->toUtc();
					// save this object
					$event_stack[$i]['utc_datetime'] = clone $stackdatetime;
					// delete the original DateTime object
					unset($stackdatetime);
				}
			}
		}
		// loop items to alter them and possibly add to the stack
		if (!empty($items)) {
			// check if we're filtering by registration
			$checkreg = $this->getState('filter.registration');
			// let's see how many we're skipping
			$skipcount = array('catfilter' => 0, /*'catid' => 0,*/ 'registration' => 0, 'canonical' => 0, 'badday' => array());
			// bah humbug - category filter is fucking things up here
			// so let's get the filter, then reset it
			// we'll reset it again once we're done
			$originalfilter = $this->getCategoryFilters();
			$this->setCategoryFilters(array());
			// loop the items
			for ($i=0; $i<count($items); $i++) {
				// use the item, not a copy
				$item = &$items[$i];
				// prepare the event
				self::prepareEvent($item);
				// if we're filtering by registration and the event does not allow it, bail
				if ($checkreg && empty($item->allow_registration)) {
					$skipcount['registration']++;
					continue;
				}
				// The core Categories model does checks for access, and won't return categories
				// that the user cannot access. So we don't have to actually check the access levels
				// because the model itself does this for us. So what we need to do is ensure we HAVE
				// a canonical category, because if we don't then it's likely the category is restricted.
				if (empty($item->categories->canonical)) {
					$skipcount['canonical']++;
					continue;
				}
				// check if we need to add this item to the stack
				if ($stack_add) {
					// if we don't have the day in the stack, we are going to skip this event
					// however, multiday events could start on a day that's not in the stack
					// case in point: an event starting on the 31st of January spans 90 days
					// the event's day will be "31" and will not appear in February
					$stack_key = $item->user_day;
					// get a stack key, regardless
					if (!array_key_exists($stack_key, $event_stack)) {
						$stack_keys = array_keys($event_stack);
						$stack_key  = array_shift($stack_keys);
					}
					
					// make sure the day, month and year all match :)
					$stackdatetime = $event_stack[$stack_key]['user_datetime'];
					$stackday      = $stackdatetime->day();
					$stackmonth    = $stackdatetime->month();
					$stackyear     = $stackdatetime->year();
					// set the icon for the event
					$item->icon = '';
					// private events always have a private icon
					if ($item->private) {
						$item->icon = 'private';
					}
					// featured events are always featured
					else if ($item->featured) {
						$item->icon = 'featured';
					}
					else {
						// we have to do this as part of the stack creation because events that span multiple days
						// will require different icons depending on the day they are displayed
						// single day event
						if (empty($item->multidays)) {
							$item->icon = 'onedate';
						}
						// this is an event that spans multiple days
						// go ahead and assign the start icon, and adjust in the multiday stack below
						else {
							$item->icon = 'startdate';
						}
						// add additional icon info
						if ($item->detached_from_rec) {
							$item->icon .= '-detached';
						}
						else if ($item->rec_id) {
							$item->icon .= '-child';
						}
					}
					// add item to the stack
					if ($item->user_day == $stackday && $item->user_month == $stackmonth && $item->user_year == $stackyear) {
						$event_stack[$item->user_day]['events'][] = $item;
						$itemcount++;
					}
					// check if this is a multiday item and add to any other days in the stack
					if (!empty($item->multidays)) {
						foreach ($item->multidays as $k => $multi) {
							// compare the day key AND the stack user_datetime - they should be equal!
							$key = $multi->day();
							if (array_key_exists($key, $event_stack) && $event_stack[$key]['user_datetime'] == $multi) {
								// we have to make a copy of this item before changing it
								$stackitem = clone $item;
								// we cannot change the icon if the event is private
								if (!$item->private) {
									// before adding to the stack, re-check the icon
									$stackitem->icon = (count($item->multidays) - 1 == $k ? 'end' : 'mid') . 'date';
									// add additional icon info
									if ($stackitem->detached_from_rec) $stackitem->icon .= '-detached';
									else if ($stackitem->rec_id) $stackitem->icon .= '-child';
								}
								// add this item to the keyed stack as well
								$event_stack[$key]['events'][] = $stackitem;
								$itemcount++;
							}
						}
					}
					
				}
				// not adding to stack - add to final events
				else {
					$itemcount++;
					$final_items[] = $item;
				}
			}
			$this->setCategoryFilters($originalfilter);
		}
		// debug skipped items
		if (isset($skipcount) && !empty($skipcount)) {
			JCalPro::debugger('Skipped Item Counts', $skipcount);
			$profiler->mark('onJCalProEventsModelGetItemsSkipped ' . print_r($skipcount, 1));
		}
		
		// side effect - reset count
		$this->_itemcount = $itemcount;
		
		$profiler->mark('onJCalProEventsModelGetItemsEnd (' . count($items) . ')');
		// return the stack
		return $stack_add ? $event_stack : $final_items;
	}
	
	public function getItemsCount() {
		return (int) $this->_itemcount;
	}
	
	/**
	 * Adds different url variables to the event
	 * TODO: consolidate into a single object
	 * 
	 * @param object $item
	 * @param array $values
	 */
	protected function _prepareEventUrls($item, &$values) {
		// TODO: find any code that uses adminhref and nuke it, because it's stupid
		// @deprecated
		$values['adminhref'] = JCalProHelperUrl::toFull(JCalProHelperUrl::events('', 'admin', false), false);
		if ($item->id) {
			$values['href']          = JCalProHelperUrl::event($item->id, true, array('slug' => $item->alias));
			$values['fullhref']      = JCalProHelperUrl::toFull($values['href'], false);
			$values['qrcode']        = JCalProHelperUrl::toFull(JCalProHelperUrl::task('event.qrcode', false, array('id' => $item->id)), false);
			$values['edithref']      = JCalProHelperUrl::toFull(JCalProHelperUrl::task('event.edit', false, array('id' => $item->id)), false);
			$values['adminedithref'] = JCalProHelperUrl::toFull(JCalProHelperUrl::task('event.edit', false, array('id' => $item->id)), true);
		}
	}
	
	/**
	 * Prepares the duration string
	 * 
	 * @param object $item
	 * @param array $values
	 */
	protected function _prepareDurationString($item, &$values) {
		switch ((int) $item->duration_type) {
			case JCalPro::JCL_EVENT_DURATION_TIME:
				$values['duration_string'] = '';
				$durationStart = JCalProHelperDate::getDateTimeFromParts($item->hour, $item->minute, 0, $item->month, $item->day, $item->year, $item->timezone);
				$durationEnd   = JCalProHelperDate::getDateTimeFromParts($item->end_hour, $item->end_minute, 0, $item->end_month, $item->end_day, $item->end_year, $item->timezone);
				try {
					$interval = $durationStart->diff($durationEnd, true);
					if ($interval) {
						$values['duration_string'] = JCalProHelperDate::translateInterval($interval);
					}
				}
				catch (Exception $e) {
					JCalPro::registerHelper('log');
					JCalProHelperLog::debugMessage($e);
				}
				break;
			case JCalPro::JCL_EVENT_DURATION_ALL:
				$values['duration_string'] = JText::_('COM_JCALPRO_DURATION_TYPE_OPTION_ALL_DAY');
				break;
			case JCalPro::JCL_EVENT_DURATION_DATE:
				try {
					$duration = JCalProHelperDate::getDateIntervalFromParts($item->end_hours, $item->end_minutes, 0, 0, $item->end_days, 0, true);
					if ($duration) {
						$values['duration_string'] = JCalProHelperDate::translateInterval($duration);
						break;
					}
				}
				catch (Exception $e) {
					JCalPro::registerHelper('log');
					JCalProHelperLog::debugMessage($e);
					// do nothing - just don't break :)
				}
			case JCalPro::JCL_EVENT_DURATION_NONE:
			default: // no end time
				$values['duration_string'] = '';
		}
	}
	
	/**
	 * Prepare registration
	 * 
	 * @param object $item
	 * @param array $values
	 */
	protected function _prepareEventRegistration($item, &$values) {
		$values['allow_registration'] = (1 == JCalPro::config('registration'));
		// if the event can be registered for...
		if ($item->registration) {
			$translate = (bool) (int) JCalPro::config('translate_usertime', 1);
			// we'll need today as a reference, but not the helper's today
			$today = JCalDate::_()->toTimezone($item->timezone);
			if ($translate) {
				$today->toUser();
			}
			// start an object specifically for registration data
			$reg = new stdClass;
			// we have to be careful with zero dates here
			try {
				// go ahead and get the registration start & end dates and convert to objects if needed
				$reg->start_date = JCalProHelperDate::getDateTimeFromParts($item->registration_start_hour, $item->registration_start_minute, 0, $item->registration_start_month, $item->registration_start_day, $item->registration_start_year, $item->timezone);
				if ($translate) {
					$reg->start_date->toUser();
				}
				// we will only need an end date if configured - otherwise use the event start date
				if ($item->registration_until_event) {
					$reg->end_date = clone $values['datetime'];
				}
				else {
					$reg->end_date = JCalProHelperDate::getDateTimeFromParts($item->registration_end_hour, $item->registration_end_minute, 0, $item->registration_end_month, $item->registration_end_day, $item->registration_end_year, $item->timezone);
				}
				if ($translate) {
					$reg->end_date->toUser();
				}
				// registration has started
				$reg->started = (bool) ($today >= $reg->start_date);
				// registration has ended
				$reg->ended = (bool) ($today >= $reg->end_date);
				// get the registered users from the database
				$db = JFactory::getDbo();
				$db->setQuery($db->getQuery(true)
					->select('Reg.*')
					->from('#__jcalpro_registration AS Reg')
					->where('Reg.event_id = ' . intval($item->id))
					->group('Reg.id')
					// go ahead and get information about the user too
					->select('User.id AS user_id')
					->select('User.name AS user_name')
					->select('User.username AS user_username')
					->select('User.email AS user_email')
					->leftJoin('#__users AS User ON User.id = Reg.created_by')
				);
				$reg->entries = $db->loadObjectList();
				$reg->entries_count = empty($reg->entries) ? 0 : count($reg->entries);
				// we should know if the user is already registered
				$user = JFactory::getUser();
				// NOTE: we cannot check based solely on user id
				$reg->already_registered = false;
				if ($reg->entries_count) {
					foreach ($reg->entries as $entry) {
						if ($user->id && $user->id == $entry->user_id) {
							$reg->already_registered = true;
							break;
						}
					}
				}
				// capacity data
				$reg->capacity_percent = $item->registration_capacity ? number_format($reg->entries_count / $item->registration_capacity * 100, 2) : 0;
				$reg->capacity_full = $item->registration_capacity ? $item->registration_capacity <= $reg->entries_count : false;
				// registration allowance
				$reg->can_register = $user->authorise('core.create', 'com_jcalpro.registrations') && ($reg->started && !$reg->ended && !$reg->capacity_full && !$reg->already_registered);
				// go ahead and set the text that says why a user cannot register
				$reg->register_error = '';
				if (!$reg->can_register) {
					if (!$reg->started) {
						$reg->register_error = JText::_('COM_JCALPRO_CANNOT_REGISTER_NOT_STARTED');
					}
					else if ($reg->ended) {
						$reg->register_error = JText::_('COM_JCALPRO_CANNOT_REGISTER_REGISTRATION_ENDED');
					}
					else if ($reg->capacity_full) {
						$reg->register_error = JText::_('COM_JCALPRO_CANNOT_REGISTER_EVENT_FULL');
					}
					else if ($reg->already_registered) {
						$reg->register_error = JText::_('COM_JCALPRO_CANNOT_REGISTER_ALREADY_REGISTERED');
					}
					else {
						$reg->register_error = JText::_('COM_JCALPRO_CANNOT_REGISTER_NOT_ALLOWED');
					}
				}
			}
			catch (Exception $e) {
				$values['registration'] = 0;
				$reg->can_register = false;
				$reg->register_error = JText::sprintf('COM_JCALPRO_REGISTRATION_SETUP_ERROR', $e->getCode(), $e->getMessage());
				JFactory::getApplication()->enqueuemessage($reg->register_error, 'error');
			}
			// set the registration object into the event
			$values['registration_data'] = $reg;
		}
	}
	
	
	/**
	 * Adds location info to an event
	 * 
	 * @param type $item
	 * @param array $values
	 */
	protected function _prepareEventLocations($item, &$values) {
		JCalProBaseModel::addIncludePath(JCalProHelperPath::admin() . '/models');
		$locModel = JCalPro::getModelInstance('Location', 'JCalProModel');
		$loc = $locModel->getItem($item->location);
		$values['location_data'] = $loc;
	}
	
	
	/**
	 * Adds tags to events
	 * 
	 * @param type $item
	 * @param type $values
	 */
	protected function _prepareEventTags($item, &$values) {
		if ($item->id && JCalProHelperTags::useTags()) {
			$values['tags'] = JCalProHelperTags::getHelper();
			$values['tags']->getTagIds($item->id, JCalPro::COM . '.event');
			$values['metadata']['tags'] = $values['tags'];
		}
	}
	
	protected function _prepareEventCategories($item, &$values) {
		// assign default color - this may get overwritten later ;)
		$default_color   = JCalPro::config('category_default_color', 'c6c6c6');
		if (empty($default_color)) {
			$default_color = '545454';
		}
		$values['color'] = $default_color;
		// create a new object to hold our categories
		$catobj = new stdClass;
		$catobj->canonical  = false;
		$catobj->categories = array();
		$catobj->catids     = array();
		// add category
		// we need to get ALL the categories here, no filtering...
		$categories = $this->getCategories($this->getState('prepare.categories.refresh'));
		if (is_array($this->_xref) && array_key_exists($item->id, $this->_xref)) {
			$xref = array("{$item->id}" => $this->_xref[$item->id]);
		}
		else {
			$xref = $this->getCategoryXref(array(&$item));
		}
		if (!empty($categories) && is_array($xref) && array_key_exists($item->id, $xref)) {
			foreach ($categories as $cat) {
				// skip this category of it is not in our xref array
				if (!in_array($cat->id, $xref[$item->id])) {
					continue;
				}
				// this one is in our xref array - yay!
				// if the category is first in the xref array, it is the canonical category
				// so we need to find the key that corresponds to this cat->id
				if (0 === array_search($cat->id, $xref[$item->id])) {
					// assign this as the canonical category
					$catobj->canonical = clone $cat;
					// use this category to determine the color
					$cat_color = $cat->params->get('jcalpro_color', $default_color);
					if (empty($cat_color)) {
						$cat_color = $default_color;
					}
					$values['color'] = $cat_color;
				}
				else {
					$catobj->categories[] = clone $cat;
				}
				// always add both the id and the parent_id to our catids
				$catobj->catids[] = $cat->id;
				$catobj->catids[] = $cat->parent_id;
			}
			// make sure we have uniques in catids
			$catobj->catids = array_unique($catobj->catids);
		}
		// set categories
		$values['categories'] = $catobj;
		// fix color
		if (false !== strpos($values['color'], '#')) {
			$values['color'] = '#' . trim($values['color'], '#');
		}
	}
	
	protected function _prepareRepeatString($item, &$values) {
		$translate = (bool) (int) JCalPro::config('translate_usertime', 1);
		// default to "this event does not repeat"
		// @deprecated
		$values['repeat_display'] = JText::_('COM_JCALPRO_THIS_EVENT_DOES_NOT_REPEAT');
		// start building the strings for each format
		$repeat_display_default = JText::_('COM_JCALPRO_DATE_FORMAT_DATE');
		$repeat_display_values  = array();
		foreach ($this->format_layouts as $flayout) {
			$repeat_display_values[$flayout] = $values['repeat_display'];
		}

		// if it DOES repeat, we need to change this
		if ($item->recur_type) {
			// detached events have a generic text
			if ($item->detached_from_rec) {
				$values['repeat_display'] = JText::_('COM_JCALPRO_DETACHED_FROM_RECURRENCE');
				foreach ($this->format_layouts as $flayout) {
					$repeat_display_values[$flayout] = $values['repeat_display'];
				}
			}
			// now we have to figure out wtf the recurrence is :)
			else {
				try {
					// this will always be the same - we saved this when the event was created!
					$enddisplaydatetime = JCalDate::createFromMySQLFormat($item->recur_end_datetime, 'UTC');
					if ($translate) {
						$enddisplaydatetime->toUser();
					}
					$enddisplay = $enddisplaydatetime->format($repeat_display_default);

					foreach ($this->format_layouts as $flayout) {
						$fvar = $flayout . '_format_events_repeat';
						$fvalue = JCalPro::config($fvar, $repeat_display_default);
						if (empty($fvalue)) {
							$fvalue = $repeat_display_default;
						}
						$repeat_display_values[$flayout] = $enddisplaydatetime->format($fvalue);
					}
				}
				catch (Exception $e) {
					$enddisplay = '-';
				}
				// process
				$rec_jtext  = false;
				$rec_period = false;
				// switch through the recurrence types and add the correct display
				switch ((int) $item->recur_type) {
					case JCalPro::RECUR_TYPE_DAILY :
						// NOTE: this ends up showing a different end date than v2 depending on period
						// because v3+ does not show the end as being greater than the last event
						// for example, in v2 the last event occurrence may be on Jan. 3 with a 3 day interval
						// but the end display would show the true end as "every 3 days until Jan. 5"
						// v3 instead shows "every 3 days until Jan. 3" which is actually correct,
						// because that is the last occurrence of the event, not the end of the interval
						// this can be confusing to end users, as they would see the end of the interval
						// and not the end of the event recurrence, which may lead to them thinking the event will
						// continue to recur until Jan. 5 instead of Jan. 3
						// base this off rec_daily_period only
						if (1 < $item->rec_daily_period) {
							$rec_jtext  = 'COM_JCALPRO_EVERY_X_DAYS_UNTIL_X';
							$rec_period = $item->rec_daily_period;
						}
						else {
							$rec_jtext  = 'COM_JCALPRO_EVERY_DAY_UNTIL_X';
						}
						break;

					case JCalPro::RECUR_TYPE_WEEKLY :
						// base this off rec_weekly_period only
						if (1 < $item->rec_weekly_period) {
							$rec_jtext  = 'COM_JCALPRO_EVERY_X_WEEKS_UNTIL_X';
							$rec_period = $item->rec_weekly_period;
						}
						else {
							$rec_jtext  = 'COM_JCALPRO_EVERY_WEEK_UNTIL_X';
						}
						break;

					case JCalPro::RECUR_TYPE_MONTHLY :
						// base this off rec_monthly_period only
						if (1 < $item->rec_monthly_period) {
							$rec_jtext  = 'COM_JCALPRO_EVERY_X_MONTHS_UNTIL_X';
							$rec_period = $item->rec_monthly_period;
						}
						else {
							$rec_jtext  = 'COM_JCALPRO_EVERY_MONTH_UNTIL_X';
						}
						break;

					case JCalPro::RECUR_TYPE_YEARLY :
						$values['repeat_display'] = $enddisplay;
						break;
				}

				if ($rec_jtext) {
					$values['repeat_display'] = (false !== $rec_period ? JText::sprintf($rec_jtext, $rec_period, $enddisplay) : JText::sprintf($rec_jtext, $enddisplay));
					foreach ($this->format_layouts as $flayout) {
						$repeat_display_values[$flayout] = (false !== $rec_period ? JText::sprintf($rec_jtext, $rec_period, $repeat_display_values[$flayout]) : JText::sprintf($rec_jtext, $repeat_display_values[$flayout]));
					}
				}
			}
		}

		// assign repeat values
		$values['repeat_display_values'] = (object) $repeat_display_values;
	}
	
	protected function _prepareFormattedDateStrings($item, &$values) {
		if (empty($item->id)) {
			return;
		}
		$translate = (bool) (int) JCalPro::config('translate_usertime', 1);
		// string prefixes
		$prefixes = array('', 'utc_', 'user_');
		// each item currently has information for creator-supplied date & time
		// create a new DateTime object for this event
		$datetime    = JCalProHelperDate::getDateTimeFromParts($item->hour, $item->minute, 0, $item->month, $item->day, $item->year, $item->timezone);
		$enddatetime = false;
		// begin building the date display object
		// NOTE: all code should eventually be transitioned to use this object
		// instead of the others that have been already added
		// these values are generated using the configuration options
		$values['user_date_display'] = new stdClass;
		// sometimes an item should appear in more than one day
		// for example, an event that lasts 3 days should appear on all 3 days it occurs
		$values['multidays'] = array();
		// strings without configuration values use this as a default
		$format_default = JText::_('COM_JCALPRO_DATE_FORMAT_MINI_DATE');
		// events that have a specific ending will have a start time and an end time
		$nostarttime = false;
		$noend = false;
		// all day events don't have a start time
		if (JCalPro::JCL_EVENT_DURATION_ALL == $item->duration_type) {
			$nostarttime = $noend = true;
			$values['end'] = JText::_('COM_JCALPRO_ALL_DAY');
		}
		// no end events don't have an end
		else if (JCalPro::JCL_EVENT_DURATION_NONE == $item->duration_type) {
			$noend = true;
			$values['end'] = JText::_('COM_JCALPRO_NO_END');
		}
		// time based durations can use a generated end
		else if (JCalPro::JCL_EVENT_DURATION_TIME == $item->duration_type) {
			$enddatetime = JCalProHelperDate::getDateTimeFromParts($item->end_hour, $item->end_minute, 0, $item->end_month, $item->end_day, $item->end_year, $item->timezone);
		}
		// date based durations modify a copy of the start date
		else if (JCalPro::JCL_EVENT_DURATION_DATE == $item->duration_type) {
			$enddatetime = clone $datetime;
			// add the interval to our DateTime object
			$enddatetime->addDay($item->end_days)->addHour($item->end_hours)->addMin($item->end_minutes);
		}
		// save our datetimes
		$starts = array('');
		$values['datetime']          = clone $datetime;
		$values['utc_datetime']      = clone $datetime;
		$values['user_datetime']     = clone $datetime;
		if ($enddatetime) {
			$starts[] = 'end_';
			$values['end_datetime']      = clone $enddatetime;
			$values['utc_end_datetime']  = clone $enddatetime;
			$values['user_end_datetime'] = clone $enddatetime;
			// create another DateTime to act as a base for our calculations for multiday events
			$basedatetime = JCalProHelperDate::getDateTimeFromParts(0, 0, 0, $item->month, $item->day, $item->year, $item->timezone);
			$values['multiday_base'] = clone $basedatetime;
			// go ahead and do the first increment
			$basedatetime->addDay();
			// generate a datetime for each day
			while ($basedatetime < $enddatetime) {
				// push a copy of the base DateTime onto the multidays stack
				$values['multidays'][] = clone $basedatetime;
				// increment the base by the duration
				$basedatetime->addDay();
			}
			unset($basedatetime);
		}
		// since some start and end strings are generated in the same manner
		// use a loop to address each
		foreach ($starts as $start) {
			// adjust specific prefixes
			$values['utc_' . $start . 'datetime']->toUtc();
			// we need to create new fields for the times displayed to the end user
			// for example, users on the west coast should see event times differently than those on the east coast
			if ($translate) {
				$values['user_' . $start . 'datetime']->toUser();
			}
			else {
				$values['user_' . $start . 'datetime']->toTimezone($item->timezone);
			}
			// set each of these based on prefix
			foreach ($prefixes as $prefix) {
				// LEGACY STRINGS
				$values[$prefix . $start . 'datedisplay']  = $values[$prefix . $start . 'datetime']->format(JText::_('COM_JCALPRO_DATE_FORMAT_FULL_DATE'));
				$values[$prefix . $start . 'minidisplay']  = $values[$prefix . $start . 'datetime']->format(JText::_('COM_JCALPRO_DATE_FORMAT_MINI_DATE'));
				$values[$prefix . $start . 'timedisplay']  = $values[$prefix . $start . 'datetime']->format(JCalProHelperDate::getUserTimeFormat());
				$values[$prefix . $start . 'microdisplay'] = $values[$prefix . $start . 'datetime']->format(JText::_('COM_JCALPRO_DATE_FORMAT_MICRO_DATE'));
				// these parts should not be applied directly
				if (!empty($prefix)) {
					// add the date parts
					foreach (array('year', 'month', 'day', 'hour', 'minute') as $datepart) {
						$values[$prefix . $start . $datepart] = (int) $values[$prefix . $start . 'datetime']->$datepart();
					}
				}
			}
		}
		
		$showspan = array_key_exists('end_minidisplay', $values) && ($values['minidisplay'] != $values['end_minidisplay']);
		
		// start adjusting legacy time strings
		foreach ($prefixes as $prefix) {
			$values[$prefix . 'start_timedisplay'] = $values[$prefix . 'timedisplay'] . '';
			if (!$enddatetime) {
				continue;
			}
			if (!$showspan) {
				$values[$prefix . 'timedisplay'] = $values[$prefix . 'timedisplay'] . ' - ' . $values[$prefix . 'end_datetime']->format(JCalProHelperDate::getUserTimeFormat());
			}
		}
		
		// start compiling the user date display object
		// this should contain the preferred date strings
		$user_date_display = array();
		
		// loop over the available layouts and set the formatted dates and times
		foreach ($this->format_layouts as $flayout) {
			// each layout has a format string ($format_event, $format_month, etc)
			$fvar  = "format_$flayout";
			// set the format string for this layout
			$$fvar = JCalPro::config($flayout . '_format_events', $format_default);
			// don't allow an empty format
			// TODO is this necessary?
			if (empty($$fvar)) {
				$$fvar = $format_default;
			}
			// for each layout, there should be 3 entries:
			// * a general entry that displays a combination of start and end
			// * a start entry that only contains the start time
			// * an end entry that only contains the end time
			// all events have a start date
			try {
				$startdate = $values['user_datetime']->format($$fvar);
			}
			catch (Exception $e) {
				$startdate = $values['user_datetime']->format($format_default);
			}
			// events that have a definite start and end will display a span of times in the general entry
			if (JCalPro::JCL_EVENT_DURATION_TIME == $item->duration_type || JCalPro::JCL_EVENT_DURATION_DATE == $item->duration_type) {
				try {
					$enddate = $values['user_end_datetime']->format($$fvar);
				}
				catch (Exception $e) {
					$enddate = $values['user_end_datetime']->format($format_default);
				}
				$user_date_display[$flayout . '_start'] = $startdate . ' ' . $values['user_start_timedisplay'];
				$user_date_display[$flayout . '_end']   = $enddate . ' ' . $values['user_end_timedisplay'];
				// events contained within a single date should show the date once with the span of time
				if ($showspan) {
					$user_date_display[$flayout] = $user_date_display[$flayout . '_start'] . ' - ' . $user_date_display[$flayout . '_end'];
				}
				// events that end in a different day need to be compiled
				else {
					$user_date_display[$flayout] = $user_date_display[$flayout . '_start'] . ' - ' . $values['user_end_timedisplay'];
				}
			}
			// events that are all day will have no start times
			else if (JCalPro::JCL_EVENT_DURATION_ALL == $item->duration_type) {
				$user_date_display[$flayout] = $user_date_display[$flayout . '_start'] = $startdate;
				$user_date_display[$flayout . '_end'] = JText::_('COM_JCALPRO_ALL_DAY');
			}
			// events that have no end will have no end entry
			else if (JCalPro::JCL_EVENT_DURATION_NONE == $item->duration_type) {
				$user_date_display[$flayout] = $user_date_display[$flayout . '_start'] = $startdate . ' ' . $values['user_timedisplay'];
				$user_date_display[$flayout . '_end'] = JText::_('COM_JCALPRO_NO_END');
			}
			
		}
		
		$values['user_date_display'] = (object) $user_date_display;
	}
	
	/**
	 * method to prepare an event for public display
	 * 
	 * @param unknown_type $item
	 */
	public function prepareEvent(&$item) {
		$app = JFactory::getApplication();
		$profiler = JProfiler::getInstance('Application');
		$profiler->mark('onJCalProEventsModelPrepareEventStart - ' . $item->id);
		// in order to make performance a little better, we want to try & only prepare each event once
		// so what we'll do is create a static array that's keyed off the id + the preparation state
		// then store an array of values to be added to the event there
		static $prepared;
		if (!is_array($prepared)) {
			$prepared = array();
		}
		$translate = (bool) (int) JCalPro::config('translate_usertime', 1);
		$module    = (bool) (int) $app->getUserState('com_jcalpro.events.jcalpro.module');
		// fix params, if necessary
		if (is_string($item->params)) {
			$reg = new JRegistry;
			$reg->loadString($item->params);
			$item->params = $reg->toArray();
		}
		// build our preparation key
		$key = $item->id . '-' . md5(serialize($this->getState())) . '-' . (int) $module;
		if (!array_key_exists($key, $prepared)) {
			// start our values
			$values = array();
			// URLs
			$this->_prepareEventUrls($item, $values);
			// categories
			$this->_prepareEventCategories($item, $values);
			// set the duration string
			$this->_prepareDurationString($item, $values);
			// set tags
			$this->_prepareEventTags($item, $values);
			// set dates
			$this->_prepareFormattedDateStrings($item, $values);
			// recur_type strings
			$this->_prepareRepeatString($item, $values);
			
			// prepare registration
			if ($this->getState('prepare.registration')) {
				$this->_prepareEventRegistration($item, $values);
			}
			
			// attach the location data, but only if we're not being called via the location model
			// also, in some cases we may not want the location data (in the minical module, for example)
			if ($this->getState('prepare.location') && !property_exists($item, 'location_data') && 'location' != $this->getState('filter.layout') && $item->location) {
				$this->_prepareEventLocations($item, $values);
			}
			
			
			// now set our key
			$prepared[$key] = $values;
		}
		
		// whew, the item is prepared - now assign values :)
		foreach ($prepared[$key] as $k => $v) {
			$item->$k = $v;
		}
		
		// check layout and task
		$layout = $app->input->get('layout');
		$task   = $app->input->get('task');
		if ($module || !('edit' == $layout || preg_match('/(\.(save(2new)?|apply|cancel))$/', $task))) {
			try {
				// fire a content plugin event on the event's description (for email cloaking, etc)
				// NOTE: no params at this time
				$eparams = array();
				// we have to set the "description" as the "text" before this plugin
				$item->text = $item->description;
				JDispatcher::getInstance()->trigger('onContentPrepare', array('com_jcalpro.event.' . $item->id, &$item, &$eparams, 0));
				$item->description = $item->text;
				unset($item->text);
				// try to "fix" the email cloaking
				$item->description = JCalProHelperFilter::recloak_emails($item->description);
			}
			catch (Exception $e) {
				JCalProHelperLog::error($e->getMessage());
			}
			
			// only check readmore in non-editor situations
			$readmorepattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';
			$item->show_readmore = preg_match($readmorepattern, $item->description);
			if ($item->show_readmore) {
				// load the language for COM_CONTENT first
				JCalPro::language('com_content');
				// now add the readmore link (with proper class)
				$item->description_readmore = '<a href="' . JCalProHelperFilter::escape($item->href) . '" class="eventreadmore">' . JText::_('COM_CONTENT_READ_MORE') . '</a>';
				list($item->description_intro, $end) = preg_split($readmorepattern, $item->description, 2);
				$item->description = $item->description_intro . $end;
			}
			else {
				$item->description_intro    = $item->description;
				$item->description_readmore = '';
			}
		}
		
		// allow plugins to alter the query
		JDispatcher::getInstance()->trigger('onJCalEventPrepare', array(&$item));
	}
	
	public function setCategoryFiltersInvert($invert = false) {
		$this->_categoryFiltersInvert = (bool) $invert;
		return $this->_categoryFiltersInvert;
	}
	
	public function getCategoryFiltersInvert() {
		return $this->_categoryFiltersInvert;
	}
	
	public function setCategoryFilters($filters = null) {
		if (is_null($filters)) {
			$filters = JCalPro::config('filter_category');
			if (1 == count($filters) && 0 == $filters[0]) $filters = array();
		}
		$this->_categoryFilters = $filters;
		return $this->_categoryFilters;
	}
	
	public function getCategoryFilters() {
		return $this->_categoryFilters;
	}
	
	/**
	 * Method to retrieve the categories that belong to JCal Pro
	 * 
	 * we can't piggyback off JCategories because we have belongs-to-many relationships
	 * however, what we can do is load these from the categories model
	 * 
	 * @param  bool  $refresh
	 * @return array
	 */
	public function getCategories($refresh = false) {
		static $cache;
		// initialize our static list
		if (is_null($cache)) {
			$cache = array();
		}
		// check if we're filtering
		$catfilters = $this->getCategoryFilters();
		// #466 - ignore Root
		if (!is_array($catfilters)) {
			$catfilters = array();
		}
		else if (!empty($catfilters)) {
			asort($catfilters);
			if (0 == $catfilters[0]) {
				array_shift($catfilters);
			}
		}
		$invertcatfilter = $this->getCategoryFiltersInvert();
		// use the md5 of our filters as the key
		$key = md5(serialize($catfilters) . $invertcatfilter);
		if (!array_key_exists($key, $cache) || $refresh) {
			$profiler = JProfiler::getInstance('Application');
			$profiler->mark('onJCalProEventsModelGetCategoriesStart');
			$app = JFactory::getApplication();
			// ensure we have the model path from the frontend
			//JCalProBaseModel::addIncludePath(JPATH_ROOT . '/components/com_jcalpro/models');
			// apparently there is a bug (?) in JModel that causes classes with the same identifier
			// but different prefixes to not be loaded correctly - manually include the model class file
			// see http://groups.google.com/group/joomlabugsquad/browse_thread/thread/76765911c0f6f6d3
			require_once JPATH_ROOT . '/components/com_jcalpro/models/categories.php';
			// load the frontend category model & send our items
			$model = JCalPro::getModelInstance('Categories', 'JCalProModel', array('ignore_request' => true));
			$model->setState('filter.published', '1');
			$model->setState('filter.search', '');
			$model->setState('list.start', 0);
			$model->setState('list.limit', 0);
			
			// BUG: setting the list.limit in the model's state doesn't work correctly
			// instead there must be a definite global list limit defined
			// so let's also force it into the session, remembering to put it back when finished
			$oldLimit = $this->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
			$app->input->set('limit', 0);
			$app->setUserState('global.list.limit', 0);
			// now get the items with the set limit
			$categories = $model->getItems();
			// reset the limit
			$app->input->set('limit', $oldLimit);
			$app->setUserState('global.list.limit', $oldLimit);
			
			$removed = array();
			// check if we're filtering
			if (!empty($catfilters)) {
				$filtered = array();
				foreach ($categories as $category) {
					if (is_array($catfilters) && (($invertcatfilter && in_array($category->id, $catfilters)) || (!$invertcatfilter && !in_array($category->id, $catfilters)))) {
						$removed[] = $category;
						continue;
					}
					$filtered[] = $category;
				}
				$categories = $filtered;
			}
			if (!empty($removed)) {
				JCalPro::debugger('Filtered Categories', $removed);
			}
			$cache[$key] = $categories;
			$profiler->mark('onJCalProEventsModelGetCategoriesEnd');
		}
		return $cache[$key];
	}
	
	/**
	 * Method to load an array that shows the xref between events as categories
	 * 
	 * the array returned here is of the form:
	 * [eid:[cid,cid],eid:[cid,cid]]
	 * (yes, this is not true json - just illustrative)
	 * 
	 */
	public function getCategoryXref($items) {
		$profiler = JProfiler::getInstance('Application');
		$profiler->mark('onJCalProEventsModelCategoryXrefStart');
		
		// save all our xrefs in this static array
		static $xref;
		// initialize if necessary
		if (is_null($xref)) {
			$xref = array();
		}
		// we don't know what else to do in this case :(
		// it shouldn't happen, but apparently sometimes it does
		if (empty($items) || !is_array($items)) {
			return array();
		}
		// get our ids from the list
		$ids = array();
		foreach ($items as $item) {
			$ids[] = (int) $item->id;
		}
		
		// what we want to do is limit the number of database calls we have to do
		// so in this case, what we're going to do is create a key based off each id in our list
		// if the key already exists, then we know that this item's xref has already been read
		// if not, we need to add it to a list to lookup later
		$lookup = array();
		$return = array();
		foreach ($ids as $id) {
			// we use a string-based key here to prevent numeric-key lookups
			$key = "xref_$id";
			if (array_key_exists($key, $xref)) {
				$return[$id] = array_merge(array(), $xref[$key]);
			}
			else {
				$lookup[] = $id;
			}
		}
		
		// now we know what events we've already done lookups for, and which ones we need to ask for
		// if we're not doing any more lookups, go ahead and return this now
		if (empty($lookup)) {
			$profiler->mark('onJCalProEventsModelCategoryXrefEnd - (' . count($return) . ' events cached - ' . implode(',', array_keys($return)) . ')');
			return $return;
		}
		
		$lookup = array_unique($lookup);
		$dblist = implode(',', $lookup);
		// go ahead and hit up the database for the rest
		$db = JFactory::getDbo();
		$db->setQuery((string) $db->getQuery(true)
			->select('Xref.*')
			->from('#__jcalpro_event_categories AS Xref')
			->where('Xref.event_id IN (' . $dblist . ')')
			->order('Xref.event_id ASC, Xref.canonical DESC, Xref.category_id ASC')
		);
		$raw = $db->loadObjectList();
		JCalPro::debugger("Xref DB Lookup ($dblist)", $raw);
		// add these to our static cache
		if (!empty($raw)) {
			foreach ($raw as $ref) {
				$key = "xref_{$ref->event_id}";
				if (!array_key_exists($key, $xref)) {
					$xref[$key] = array();
				}
				$xref[$key][] = $ref->category_id;
			}
		}
		// now add them to the return array
		foreach ($lookup as $id) {
			$key = "xref_$id";
			if (array_key_exists($key, $xref)) {
				$return[$id] = array_merge(array(), $xref[$key]);
			}
		}
		// finally, set up debugging code & return the values
		JCalPro::debugger("Xref", $xref);
		$profiler->mark('onJCalProEventsModelCategoryXrefEnd - (' . count($return) . ' events processed - ' . implode(',', array_keys($return)) . ')');
		return $return;
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 *
	 * @return	string		A store id.
	 */
	protected function getStoreId($id = '')
	{
		// NOTE: wrapping all of these in print_r() in case they cannot be converted to strings without notices
		// Compile the store id.
		$id	.= ':'.print_r($this->getState('filter.start_date'),1);
		$id	.= ':'.print_r($this->getState('filter.end_date'),1);
		$id	.= ':'.print_r($this->getState('filter.extension'),1);
		$id	.= ':'.print_r($this->getState('filter.access'),1);
		$id	.= ':'.print_r($this->getState('filter.parentId'),1);
		$id	.= ':'.print_r($this->getState('filter.location'),1);
		$id	.= ':'.print_r($this->getState('filter.catid'),1);
		$id	.= ':'.print_r($this->getState('filter.search'),1);
		$id	.= ':'.print_r($this->getState('filter.registration'),1);
		$id	.= ':'.print_r($this->getState('filter.approved'),1);
		$id	.= ':'.print_r($this->getState('filter.featured'),1);
		$id	.= ':'.print_r($this->getState('filter.date_range'),1);
		$id	.= ':'.print_r($this->getState('filter.ignore_archive'),1);
		$id	.= ':'.print_r($this->getState('insert.link'),1);
		$id	.= ':'.print_r($this->getState('insert.title'),1);
		$id	.= ':'.print_r($this->getState('insert.start_date'),1);
		$id	.= ':'.print_r($this->getState('insert.end_date'),1);
		$id	.= ':'.print_r($this->getState('insert.description'),1);
		$id	.= ':'.print_r($this->getState('insert.itemid'),1);
		$id	.= ':'.print_r($this->getState('prepare.categories'),1);
		$id	.= ':'.print_r($this->getState('prepare.categories.refresh'),1);
		$id	.= ':'.print_r($this->getState('prepare.location'),1);
		$id	.= ':'.print_r($this->getState('prepare.registration'),1);
		$id	.= ':'.print_r($this->getState('list.featured'),1);
		$id .= ':'.print_r($this->getCategoryFilters(),1);
		$id .= ':'.print_r($this->getCategoryFiltersInvert(),1);

		return parent::getStoreId($id);
	}
	
	public function getPublishedCategoryIds() {
		static $ids;
		if (!is_array($ids)) {
			$ids = array();
			$db = JFactory::getDbo();
			$db->setQuery($db->getQuery(true)
				->select('id')
				->from('#__categories')
				->where($db->quoteName('published') . ' = 1')
				->where($db->quoteName('extension') . ' = ' . $db->quote(JCalPro::COM))
				->where('access IN (' . implode(',', JFactory::getUser()->getAuthorisedViewLevels()) . ')')
			);
			try {
				$un = $db->loadColumn();
				if (!empty($un) && is_array($un)) {
					$ids = array_merge($ids, $un);
				}
			}
			catch (Exception $e) {
				
			}
		}
		return $ids;
	}

	protected function getListQuery() {
		
		$db = $this->getDbo();
	
		// main query
		$query = $db->getQuery(true)
			// Select the required fields from the table.
			->select($this->getState('list.select', 'Event.*'))
			// add context
			->select($db->quote(JCalPro::COM) . ' AS context')
			->from('#__jcalpro_events AS Event')
		;
		// add author to query
		$this->appendAuthorToQuery($query, 'Event');
		
		// Filter by language
		$site     = JFactory::getApplication()->isSite();
		$language = $this->getState('filter.language');
		// if there's a language here, display only that language in admin
		// and that language + those marked ALL in site
		if ($language) {
			if ($site) {
				$query->where('Event.language IN (' . $db->quote($language) . ',' . $db->quote('*') . ')');
			}
			else {
				$query->where('Event.language = ' . $db->quote($language));
			}
		}
		
		// filter by catid
		$catid = (int) $this->getState('filter.catid');
		if ($catid) {
			$query->leftJoin('#__jcalpro_event_categories AS Catid ON Catid.event_id = Event.id AND Catid.category_id = ' . $catid);
			$query->where('Catid.category_id = ' . $catid);
		}
		
		// filter by categories (see menu item)
		//$catfilters  = $this->getState('filter.category');
		$catfilters  = $this->getCategoryFilters();
		// "fix" root
		if (is_array($catfilters) && !empty($catfilters)) {
			asort($catfilters);
			$catfilters = array_values(array_unique($catfilters));
			if (0 == $catfilters[0]) array_shift($catfilters);
		}
		else {
			$catfilters = array();
		}
		
		// BUGFIX: for pagination to work properly, we need to filter out the unpublished categories
		$unpublishedCategories = $this->getPublishedCategoryIds();
		if (!empty($catfilters)) {
			// BUGFIX for BUGFIX - can't merge these two - instead get the shared values
			$catfilters = array_unique(array_values(array_intersect($catfilters, $unpublishedCategories)));
		}
		else {
			$catfilters = $unpublishedCategories;
		}
		
		if (!empty($catfilters)) {
			// "fix" root
			asort($catfilters);
			$catfilters = array_values(array_unique($catfilters));
			if (0 == $catfilters[0]) array_shift($catfilters);
			
			// UPDATE: we cannot just join in every category as before
			// too many categories will push MySQL to its join limit and cause errors
			
			// now, if we STILL have categories, loop & filter
			if (!empty($catfilters)) {
				$wheres = array();
				/*
				// this is the old, messy, broken way of handling this...
				foreach ($catfilters as $cf) {
					$query->leftJoin('#__jcalpro_event_categories AS Cat' . $cf . ' ON Cat' . $cf . '.event_id = Event.id AND Cat' . $cf . '.category_id = ' . $cf);
					$wheres[] = 'Cat' . $cf . '.category_id = ' . $cf;
				}
				*/
				// instead of joining each category seperately, join all as a group
				// and select those that match the combined ids (this is gonna be ugly!!!!)
				$query->leftJoin('#__jcalpro_event_categories AS CatFilter ON CatFilter.event_id = Event.id');
				// if we GROUP_CONCAT CatFilter's ids, we can get a comma delimited list like "3,24,57,58,66"
				// so let's select this string and add commas to the end, then do a WHERE this string matches "," . $catfilters[*] . "," for each filter
				if ($this->getCategoryFiltersInvert()) {
					$not    = ' NOT';
					$glue   = ' AND ';
					$having = ' AND ';
				}
				else {
					$not    = '';
					$glue   = ' OR ';
					$having = ' OR ';
				}
				foreach ($catfilters as $cf) {
					$wheregroup = array();
					$wheregroup[] = "CONCAT(',', GROUP_CONCAT(CatFilter.category_id), ',')$not LIKE " . $db->quote(",{$cf},%");
					$wheregroup[] = "CONCAT(',', GROUP_CONCAT(CatFilter.category_id), ',')$not LIKE " . $db->quote("%,{$cf},");
					$wheregroup[] = "CONCAT(',', GROUP_CONCAT(CatFilter.category_id), ',')$not LIKE " . $db->quote("%,{$cf},%");
					$wheregroup[] = "CONCAT(',', GROUP_CONCAT(CatFilter.category_id), ',')$not LIKE " . $db->quote(",{$cf},");
					$wheres[] = '(' . implode($glue, $wheregroup) . ')';
				}
				$query->having('(' . implode($having, $wheres) . ')');
			}
		}
		
		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (0 === stripos($search, 'id:')) {
				$searchid = substr($search, 3);
				$eq = '=';
				if (0 === strpos($searchid, '!')) {
					$eq = '<>';
					$searchid = trim($searchid, '!');
				}
				$query->where('Event.id ' . $eq . ' ' . (int) $searchid);
			}
			else if (stripos($search, 'ids:') === 0) {
				$searchids = explode(',', substr($search, 4));
				$include_ids = array();
				$exclude_ids = array();
				if (!empty($searchids)) {
					foreach ($searchids as $searchid) {
						if (0 === strpos($searchid, '!')) {
							$exclude_ids[] = (int) trim($searchid, '!');
						}
						else {
							$include_ids[] = (int) $searchid;
						}
					}
				}
				if (!empty($include_ids)) {
					$query->where('Event.id IN (' . implode(',', $include_ids) . ')');
				}
				if (!empty($exclude_ids)) {
					$query->where('Event.id NOT IN (' . implode(',', $exclude_ids) . ')');
				}
			}
			else {
				
				$query->leftJoin('#__jcalpro_locations AS Location ON Event.location = Location.id');
				
				$phrase = $this->getState('filter.search.phrase');
				switch ($phrase) {
					case 'exact':
						$text = $db->Quote('%'.$db->escape($search, true).'%', false);
						$searchwhere = array();
						$searchwhere[] = 'Event.title LIKE ' . $text;
						$searchwhere[] = 'Event.description LIKE ' . $text;
						
						$searchwhere[] = 'IF(Event.location = 0, ' . $db->quote('') . ', Location.title) LIKE ' . $text;
						$searchwhere[] = 'IF(Event.location = 0, ' . $db->quote('') . ', Location.address) LIKE ' . $text;
						$searchwhere[] = 'IF(Event.location = 0, ' . $db->quote('') . ', Location.city) LIKE ' . $text;
						
						if (!empty($searchwhere)) {
							$query->where('(' . implode(' OR ', $searchwhere) . ')');
						}
						break;
					case 'any':
					case 'all':
					default:
						$searchwhere = array();
						$words = explode(' ', $search);
						foreach ($words as $word) {
							// skip empty words
							if (empty($word)) {
								continue;
							}
							$text = $db->Quote('%'.$db->escape($word, true).'%', false);
							$wordwhere = array();
							$wordwhere[] = 'Event.title LIKE ' . $text;
							$wordwhere[] = 'Event.description LIKE ' . $text;
							
							$wordwhere[] = 'IF(Event.location = 0, ' . $db->quote('') . ', Location.title) LIKE ' . $text;
							$wordwhere[] = 'IF(Event.location = 0, ' . $db->quote('') . ', Location.address) LIKE ' . $text;
							$wordwhere[] = 'IF(Event.location = 0, ' . $db->quote('') . ', Location.city) LIKE ' . $text;
							
							$searchwhere[] = implode(' OR ', $wordwhere);
						}
						if (!empty($searchwhere)) {
							$query->where('((' . implode(($phrase == 'all' ? ') AND (' : ') OR ('), $searchwhere) . '))');
						}
						break;
				}
			}
		}
		
		// Filter by registration.
		$search = $this->getState('filter.registration');
		if (is_numeric($search)) {
			$query->where('Event.registration = '.(int) $search);
		}

		// Filter by approved state
		$published = $this->getState('filter.approved');
		if (is_numeric($published)) {
			$query->where('Event.approved = ' . (int) $published);
		}

		// Filter by featured state
		$featured = $this->getState('filter.featured');
		if (is_numeric($featured)) {
			$query->where('Event.featured = ' . (int) $featured);
		}
		
		
		// Filter by location
		$location = $this->getState('filter.location');
		if (is_numeric($location)) {
			// NOTE: no need to check LOCATION_FILTER_ALL, just don't filter by location
			if (JCalPro::LOCATION_FILTER_WITH == $location) {
				$query->where('Event.location > 0');
			}
			else if (JCalPro::LOCATION_FILTER_WITHOUT == $location) {
				$query->where('Event.location = 0');
			}
			else if (JCalPro::LOCATION_FILTER_ALL != $location) {
				$query->where('Event.location = ' . (int) $location);
			}
		}
		else if (is_array($location) && !empty($location)) {
			JArrayHelper::toInteger($location);
			$location = array_unique($location);
			$query->where('Event.location IN (' . implode(',', $location) . ')');
		}
		
		
		// date range
		$value = $this->getState('filter.date_range');
		if (is_numeric($value)) {
			$value = intval($value);
			// get a copy of "today", which is based on user time
			$date = JCalProHelperDate::getTodayTime()->toDayStart();
			// switch the values
			switch ($value) {
				
				case JCalPro::RANGE_ALL:
				default:
					break;
				
				// past events
				case JCalPro::RANGE_PAST:
					$query->where('Event.start_date < ' . $db->Quote($date->toNowTime()->toUtc()->toSql()));
					break;
				
				// past events, based on end date
				// if the event has not ended, do NOT include it here
				case JCalPro::RANGE_PAST_END:
					$now         = $date->toUser()->toNow()->toUtc()->toSql();
					$today_start = $date->toUser()->toDayStart()->toUtc()->toSql();
					$today_end   = $date->toUser()->toDayEnd()->toUtc()->toSql();
					
					// start query
					$query->where('(('
					// ignore "all day" events that happen today
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE)
					. ' AND Event.start_date < ' . $db->Quote($today_start)
					. ') OR ('
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY)
					. ' AND Event.start_date < ' . $db->Quote($today_start)
					. ') OR ('
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY_2)
					. ' AND Event.start_date < ' . $db->Quote($today_start)
					. ') OR ('
					// ignore "no end" events that started today
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_EVENT_NO_END_DATE)
					. ' AND Event.start_date < ' . $db->Quote($today_start)
					. ') OR ('
					// events that have ended
					. 'Event.end_date < ' . $db->Quote($now)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY_2)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_EVENT_NO_END_DATE)
					// end
					. '))');
					break;
					
				// upcoming events
				case JCalPro::RANGE_UPCOMING:
					$query->where('Event.start_date >= ' . $db->Quote($date->toNowTime()->toUtc()->toSql()));
					break;
					
				// upcoming events, based on end date
				// if the event has not ended, include it here
				case JCalPro::RANGE_UPCOMING_END:
					$now         = $date->toUser()->toNow()->toUtc()->toSql();
					$today_start = $date->toUser()->toDayStart()->toUtc()->toSql();
					$today_end   = $date->toUser()->toDayEnd()->toUtc()->toSql();
						
					// start query
					$query->where('(('
					// handle "all day" that happen today
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE)
					. ' AND Event.start_date >= ' . $db->Quote($today_start)
					. ') OR ('
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY)
					. ' AND Event.start_date >= ' . $db->Quote($today_start)
					. ') OR ('
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY_2)
					. ' AND Event.start_date >= ' . $db->Quote($today_start)
					. ') OR ('
					// "no end"
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_EVENT_NO_END_DATE)
					. ' AND Event.start_date >= ' . $db->Quote($today_start)
					. ') OR ('
					// events with an end that have already started
					. 'Event.start_date < ' . $db->Quote($now)
					. ' AND Event.end_date > ' . $db->Quote($now)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY_2)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_EVENT_NO_END_DATE)
					. ') OR ('
					// events with an end that have not started
					. 'Event.start_date >= ' . $db->Quote($now)
					// end
					. '))');
					break;
					
				// this week
				case JCalPro::RANGE_THIS_WEEK:
					$query->where('(Event.start_date >= ' . $db->Quote($date->toWeekStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->toWeekEnd()->toUtc()->toSql()) . ')');
					break;
					
				// last week
				case JCalPro::RANGE_LAST_WEEK:
					$query->where('(Event.start_date >= ' . $db->Quote($date->subWeek()->toWeekStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->toWeekEnd()->toUtc()->toSql()) . ')');
					break;
					
				// next week
				case JCalPro::RANGE_NEXT_WEEK:
					$query->where('(Event.start_date >= ' . $db->Quote($date->addWeek()->toWeekStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->toWeekEnd()->toUtc()->toSql()) . ')');
					break;
					
				// this month
				case JCalPro::RANGE_THIS_MONTH:
					$query->where('(Event.start_date >= ' . $db->Quote($date->toMonthStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->toMonthEnd()->toUtc()->toSql()) . ')');
					break;
					
				// last month
				case JCalPro::RANGE_LAST_MONTH:
					$query->where('(Event.start_date >= ' . $db->Quote($date->subMonth()->toMonthStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->toMonthEnd()->toUtc()->toSql()) . ')');
					break;
					
				// next month
				case JCalPro::RANGE_NEXT_MONTH:
					$query->where('(Event.start_date >= ' . $db->Quote($date->addMonth()->toMonthStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->toMonthEnd()->toUtc()->toSql()) . ')');
					break;
					
				// today
				case JCalPro::RANGE_TODAY:
					$query->where('(Event.start_date >= ' . $db->Quote($date->toDayStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->addDay()->subSec()->toSql()) . ')');
					break;
					
				// tomorrow
				case JCalPro::RANGE_TOMORROW:
					$query->where('(Event.start_date >= ' . $db->Quote($date->addDay()->toDayStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->addDay()->subSec()->toSql()) . ')');
					break;
					
				// yesterday
				case JCalPro::RANGE_YESTERDAY:
					$query->where('(Event.start_date >= ' . $db->Quote($date->subDay()->toDayStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->addDay()->subSec()->toSql()) . ')');
					break;
				
				// next 30
				case JCalPro::RANGE_NEXT_30:
					$query->where('(Event.start_date >= ' . $db->Quote($date->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->addDay(30)->toSql()) . ')');
					break;
				
				// last 30
				case JCalPro::RANGE_LAST_30:
					$query->where('(Event.start_date <= ' . $db->Quote($date->toUtc()->toSql()) . ' AND Event.start_date > ' . $db->Quote($date->subDay(30)->toSql()) . ')');
					break;
				
				// next 2 weeks
				case JCalPro::RANGE_NEXT_2_WEEKS:
					$query->where('(Event.start_date >= ' . $db->Quote($date->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->addWeek(2)->toUtc()->toSql()) . ')');
					break;
				
				// next 3 weeks
				case JCalPro::RANGE_NEXT_3_WEEKS:
					$query->where('(Event.start_date >= ' . $db->Quote($date->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->addWeek(3)->toUtc()->toSql()) . ')');
					break;
					
				// next 2 months
				case JCalPro::RANGE_NEXT_2_MONTHS:
					$query->where('(Event.start_date >= ' . $db->Quote($date->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->addMonth(2)->toUtc()->toSql()) . ')');
					break;
					
				// next 3 months
				case JCalPro::RANGE_NEXT_3_MONTHS:
					$query->where('(Event.start_date >= ' . $db->Quote($date->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->addMonth(3)->toUtc()->toSql()) . ')');
					break;
					
				// this year
				case JCalPro::RANGE_THIS_YEAR:
					$query->where('(Event.start_date >= ' . $db->Quote($date->toYearStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->addYear()->toUtc()->toSql()) . ')');
					break;
					
				// next year
				case JCalPro::RANGE_NEXT_YEAR:
					$query->where('(Event.start_date >= ' . $db->Quote($date->addYear()->toYearStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->addYear()->toUtc()->toSql()) . ')');
					break;
					
				// last year
				case JCalPro::RANGE_LAST_YEAR:
					$query->where('(Event.start_date >= ' . $db->Quote($date->subYear()->toYearStart()->toUtc()->toSql()) . ' AND Event.start_date < ' . $db->Quote($date->toUser()->addYear()->toUtc()->toSql()) . ')');
					break;
				
				// ongoing
				case JCalPro::RANGE_ONGOING:
					$now         = $date->toUser()->toNow()->toUtc()->toSql();
					$today_start = $date->toUser()->toDayStart()->toUtc()->toSql();
					$today_end   = $date->toUser()->toDayEnd()->toUtc()->toSql();
					
					// start query
					$query->where('(('
					// handle "all day" that happen today
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE)
					. ' AND Event.start_date >= ' . $db->Quote($today_start)
					. ' AND Event.start_date < ' . $db->Quote($today_end)
					. ') OR ('
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY)
					. ' AND Event.start_date >= ' . $db->Quote($today_start)
					. ' AND Event.start_date < ' . $db->Quote($today_end)
					. ') OR ('
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY_2)
					. ' AND Event.start_date >= ' . $db->Quote($today_start)
					. ' AND Event.start_date < ' . $db->Quote($today_end)
					. ') OR ('
					// "no end"
					. 'Event.end_date = ' . $db->Quote(JCalPro::JCL_EVENT_NO_END_DATE)
					. ' AND Event.start_date >= ' . $db->Quote($today_start)
					. ' AND Event.start_date < ' . $db->Quote($today_end)
					. ') OR ('
					// events with an end
					. 'Event.start_date < ' . $db->Quote($now)
					. ' AND Event.end_date > ' . $db->Quote($now)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY_2)
					. ' AND Event.end_date <> ' . $db->Quote(JCalPro::JCL_EVENT_NO_END_DATE)
					// end
					. '))');
					break;
			}
		}
		
		// Add the list ordering clause.
		$listOrdering = $this->getState('list.ordering', 'Event.start_date');
		$listDirn     = $db->escape($this->getState('list.direction', 'ASC'));
		if ($this->getState('list.featured', 0)) {
			$query->order('Event.featured DESC');
		}
		$query->order($db->escape($listOrdering) . ' ' . $listDirn);
		
		// Group by filter
		$query->group('Event.id');
		
		// allow plugins to alter the query
		JDispatcher::getInstance()->trigger('onJCalEventQuery', array(&$this, &$query));
		
		return $query;
	}
	
	
	/**
	 * retrieves a ginormous object with a ton of different "relevant" dates & times
	 * I won't lie, it's an odd assortment with random names
	 * most are named the same as when used in v2 code, placed here
	 * some of these I renamed for clarity's sake,
	 * some I created from different layout variables named the same depending on use
	 * some were just the same things calculated over & over with no variable at all 
	 * 
	 * Later in development an attempt was made to clean this up quite a bit,
	 * as we want to use DateTime for our conversions wherever possible
	 * 
	 * know your meme
	 * 
	 */
	public function getAllTheDates() {
		static $all = null;
		
		if (is_null($all)) {
			
			$profiler = JProfiler::getInstance('Application');
			$profiler->mark('onJCalProEventsModelGetDatesStart');
			
			// this is our "all" object
			$all = new stdClass();
			// get the request date as a DateTime object
			$all->date  = JCalProHelperDate::getDate();
			// get today as a DateTime object
			$all->today = JCalProHelperDate::getToday();
			// since we're basing all our times on frontend off user time, go ahead and force both to the proper timezone
			// dunno if forcing to UTC first is actually necessary (doubtful) but it can't hurt :)
			$all->date->toUtc()->toUser();
			$all->today->toUtc()->toUser();
			// shorthand
			$all->today_year  = $all->today->year();
			$all->today_month = $all->today->month();
			$all->today_day   = $all->today->day();
			// fix for archive mode
			// archive = show past events
			if (!JCalPro::config('archive') && $all->date < $all->today) {
				$all->date = clone $all->today;
				$all->date_year  = $all->today_year;
				$all->date_month = $all->today_month;
				$all->date_day   = $all->today_day;
			}
			else {
				$all->date_year  = $all->date->format('Y');
				$all->date_month = $all->date->month();
				$all->date_day   = $all->date->day();
			}
			
			// this month
			$month_start = clone $all->date;
			$all->month_start = $month_start->toMonthStart();
			
			// previous month & year
			$pm = ("1" == $all->date_month ? "12" : $all->date_month - 1);
			$py = $all->date_year - ("12" == $pm ? 1 : 0);
			$all->prev_month = JCalProHelperDate::getDayAsObject(array('day'=>1, 'month'=>$pm, 'year'=>$py));
			$all->prev_year = $all->date_year - 1;
			
			// next month & year
			$nm = ("12" == $all->date_month ? "1" : $all->date_month + 1);
			$ny = $all->date_year + ("1" == $nm ? 1 : 0);
			$all->next_month = JCalProHelperDate::getDayAsObject(array('day'=>1, 'month'=>$nm, 'year'=>$ny));
			$all->next_year = $all->date_year + 1;
			
			// days in month
			$all->days_in_month = JCalProHelperDate::getDaysInMonth();
			
			// week number
			$dayofweek = $all->date->weekday();
			// adjust the dayofweek variable based on the config
			if ((int) JCalPro::config('day_start', 0)) { // if monday is the first day
				$dayofweek = $dayofweek - 1; // weekday as a decimal number [0,6], with 0 representing Monday
				$dayofweek = (-1 == $dayofweek) ? 6 : $dayofweek;
			}
			// set dayofweek
			$all->dayofweek = $dayofweek;
			// set week number
			$all->week_number = (int) JCalProHelperDate::getWeekNumber($all->date_day, $all->date_month, $all->date_year);
			
			// now that we have the day of week, clone the date DateTime and remove that many days
			$all->week_start = clone $all->date;
			// however, if the date IS the first day of the week, leave it!
			if ($dayofweek) {
				$all->week_start->subDay($dayofweek);
			}
			// clone the start of the week and add 6 days to it to get the end of the week
			$all->week_end = clone $all->week_start;
			$all->week_end->addDay(6);
			
			// the previous week should be 7 days before the week_start
			$all->prev_week = clone $all->week_start;
			$all->prev_week->subWeek();
			// the next week should be 7 days after the week_start
			$all->next_week = clone $all->week_start;
			$all->next_week->addWeek();
			
			
			// previous day
			$all->prev_day = clone $all->date;
			$all->prev_day->subDay();
			// next day
			$all->next_day = clone $all->date;
			$all->next_day->addDay();
			
			// weekdays
			$all->weekdays = JCalProHelperDate::getWeekdays();
			
			$profiler->mark('onJCalProEventsModelGetDatesEnd');
		}
		return $all;
	}
	
}
