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

jimport('joomla.database.table');
jimport('jcaldate.date');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');

JCalPro::registerHelper('date');
JCalPro::registerHelper('tags');
JCalPro::registerHelper('url');

/**
 * This is a base class for backwards compat
 */
class JCalProBaseEventTable extends JTable
{
	protected $_cat_ids;
	
	/**
	 * Our compat method
	 * 
	 * @param unknown_type $table
	 * @param unknown_type $id
	 */
	protected function _compat_getAssetParentId($table = null, $id = null) {
		// an event should have its canonical category as its parent asset
		$catid = (int) (property_exists($this, 'canonical') ? $this->canonical : (!empty($this->_cat_ids) ? $this->_cat_ids[0] : 0));
		$asset = JTable::getInstance('Asset');
		$asset->loadByName(JCalPro::COM . ($catid ? '.category.' . $catid : ''));
		return $asset->id;
	}
}

/**
 * Declare the shim class that defines _getAssetParentId in different ways based on version
 * 
 */
if (JCalPro::version()->isCompatible('3.2.0'))
{
	class JCalProEventTable extends JCalProBaseEventTable
	{
		protected function _getAssetParentId(JTable $table = null, $id = null)
		{
			return $this->_compat_getAssetParentId($table, $id);
		}
	}
}
else
{
	class JCalProEventTable extends JCalProBaseEventTable
	{
		protected function _getAssetParentId($table = null, $id = null)
		{
			return $this->_compat_getAssetParentId($table, $id);
		}
	}
}

/**
 * The real event table class
 * 
 */
class JCalProTableEvent extends JCalProEventTable
{
	public $id; // Primary Key
	public $title; // Event Title
	public $alias; // Event Title Alias
	public $description; // Description of event (unfiltered)
	public $language; // Event language code
	public $common_event_id; // Identification string
	public $location; // Primary key of location
	public $rec_id; // Primary key of parent recurrence
	public $detached_from_rec; // Boolean flag to denote if this event is detached from the other recurrences
	public $day; // Day as configured by the user
	public $month; // Month as configured by the user
	public $year; // Year as configured by the user
	public $hour; // Hour as configured by the user
	public $minute; // Minute as configured by the user
	public $timezone; // Timezone as configured by the user
	public $start_date; // Start of event in UTC DateTime
	public $end_date; // End of event in UTC DateTime
	public $registration; // Flag to denote if registration is allowed
	public $registration_capacity; // Maximum amount of registrations to allow - 0 means "no limit"
	public $registration_start_day; // Day registration starts as configured by the user
	public $registration_start_month; // Month registration starts as configured by the user
	public $registration_start_year; // Year registration starts as configured by the user
	public $registration_start_hour; // Hour registration starts as configured by the user
	public $registration_start_minute; // Minute registration starts as configured by the user
	public $registration_start_date; // Start of event registration in UTC DateTime
	public $registration_until_event; // Boolean flag to denote if this event can have registrations up to the start time
	public $registration_end_day; // Day registration ends as configured by the user
	public $registration_end_month; // Month registration ends as configured by the user
	public $registration_end_year; // Year registration ends as configured by the user
	public $registration_end_hour; // Hour registration ends as configured by the user
	public $registration_end_minute; // Minute registration ends as configured by the user
	public $registration_end_date; // End of event registration in UTC DateTime
	public $recur_type; // Recur type for event (merged with rec_type_select in v2)
	public $recur_end_type; // Recur end type - 1 for X occurrences (recur_end_count), 2 for given end date (recur_end_until)
	public $recur_end_count; // Recur end count, only used when recur_end_type is 1
	public $recur_end_until; // Recur end date, raw string from user, only used when recur_end_type is 2
	public $recur_end_datetime; // Calculated end date for recurrences in UTC
	public $recur_val;
	public $rec_daily_period;
	public $rec_weekly_period;
	public $rec_weekly_on_monday;
	public $rec_weekly_on_tuesday;
	public $rec_weekly_on_wednesday;
	public $rec_weekly_on_thursday;
	public $rec_weekly_on_friday;
	public $rec_weekly_on_saturday;
	public $rec_weekly_on_sunday;
	public $rec_monthly_period;
	public $rec_monthly_type;
	public $rec_monthly_day_number;
	public $rec_monthly_day_list;
	public $rec_monthly_day_order;
	public $rec_monthly_day_type;
	public $rec_yearly_period;
	public $rec_yearly_on_month;
	public $rec_yearly_on_month_list;
	public $rec_yearly_type;
	public $rec_yearly_day_number;
	public $rec_yearly_day_order;
	public $rec_yearly_day_type;
	public $approved;
	public $private;
	public $published; // publication status of event - 0 is Unpublished, 1 is Published, -2 is Trashed
	public $featured; // featured status of event
	public $created; // when event was created, in UTC
	public $created_by; // User id of form creator
	public $modified; // when event was last modified, deprecates last_updated
	public $modified_by; // User id of last modifier
	public $checked_out; // Locking column to prevent simultaneous updates
	public $checked_out_time; // Date and Time event was checked out
	public $duration_type; // Type used when calculating duration
	public $end_minutes; // end minutes used when calculating duration
	public $end_hours; // end hours used when calculating duration
	public $end_days; // end days used when calculating duration
	public $end_month; // end month used when calculating duration
	public $end_year; // end year used when calculating duration
	public $params; // Extra parameters for this event
	public $metadata; // JSON encoded metadata.
	
	private $_basedatetime; // base DateTime object of this event
	private $_parsed_end_parts; // for end parts
	private $_parsed_rec_weekly; // array with numeric keys representing Sunday-Saturday and values of 1 or 0
	
	private static $_time_vars = array('day', 'month', 'year', 'hour', 'minute');
	private static $_date_array_parts = array(
		'start_date_array'              => ''
	,	'end_date_array'                => 'end_'
	,	'registration_start_date_array' => 'registration_start_'
	,	'registration_end_date_array'   => 'registration_end_'
	);
	
	/**
	 * Helper object for storing and deleting tag information.
	 *
	 * @var    JHelperTags
	 * @since  3.2.4
	 */
	protected $tagsHelper = null;

	function __construct(&$db) {
		parent::__construct('#__jcalpro_events', 'id', $db);
		if (JCalProHelperTags::useTags()) {
			$this->tagsHelper = JCalProHelperTags::getHelper();
		}
	}

	protected function _getAssetName($key = null) {
		$k = $this->_tbl_key;
		return 'com_jcalpro.event.' . ((int) (is_numeric($key) ? $key : $this->$k));
	}

	protected function _getAssetTitle() {
		return $this->title;
	}
	
	public function loadByCommonId($common_id, $reset = true) {
		$this->_db->setQuery((string) $this->_db->getQuery(true)
			->select('id')
			->from($this->getTableName())
			->where('common_event_id = ' . $this->_db->Quote($common_id))
			->where('rec_id = 0')
		);
		$id = $this->_db->loadResult();
		if (empty($id)) {
			$id = null;
		}
		return $this->load($id, $reset);
	}

	public function load($keys = null, $reset = true) {
		$load = parent::load($keys, $reset);
		// date parts
		foreach (self::$_date_array_parts as $key => $prefix) {
			$this->{$key} = array();
			foreach (self::$_time_vars as $var) {
				$this->{$key}[$var] = intval($this->{$prefix . $var});
			}
		}
		// JSON encoded objects
		foreach (array('params', 'metadata') as $json) {
			if (is_string($this->$json)) {
				$registry = new JRegistry;
				$registry->loadString($this->$json);
				$this->$json = $registry;
			}
		}
		// load up the categories from the xref table if this is an existing record
		// go ahead and set this now, jik
		$this->canonical = '';
		$this->cat = '';
		// only bother loading categories if we have an id
		if ($this->id) {
			// instead of loading an array and imploding it, let MySQL handle it :)
			$this->_db->setQuery('SELECT GROUP_CONCAT(CAST(category_id AS CHAR) ORDER BY canonical DESC, category_id ASC SEPARATOR "|") AS categories FROM #__jcalpro_event_categories WHERE event_id = ' . intval($this->id));
			$catids = $this->_db->loadResult();
			if (!empty($catids)) {
				$this->cat = $catids;
			}
			// in order to set the canonical categories, we need an array, not a string... :/
			$this->cat = explode('|', $this->cat);
			$this->canonical = array_shift($this->cat);
			$this->cat = implode('|', $this->cat);
		}
		return $load;
	}
	
	
	/**
	 * Method to ensure our date data is ok before storing
	 * 
	 */
	public function check() {
		// set base DateTime in UTC
		$this->_basedatetime = JCalProHelperDate::getDateTimeFromParts($this->hour, $this->minute, 0, $this->month, $this->day, $this->year, $this->timezone)->toUtc();
		
		// required description?
		if (JCalPro::config('require_description') && '' == trim(strip_tags($this->description))) {
			$this->setError(JText::_('COM_JCALPRO_EVENT_MUST_HAVE_DESCRIPTION'));
			return false;
		}
		
		// handle registration dates
		if ($this->registration) {
			// set registration start DateTime in UTC
			$regdate = JCalProHelperDate::getDateTimeFromParts($this->registration_start_hour, $this->registration_start_minute, 0, $this->registration_start_month, $this->registration_start_day, $this->registration_start_year, $this->timezone)->toUtc();
			$this->registration_start_date = $regdate->toSql();
			// ensure that the registration start date is before the start date
			if ($regdate > $this->_basedatetime) {
				$this->setError(JText::_('COM_JCALPRO_EVENT_REGISTRATION_AFTER_START_DATE'));
				return false;
			}
			// set registration end DateTime in UTC, but only if we have a date
			if ($this->registration_until_event) {
				$this->registration_end_date = '0000-00-00 00:00:00';
			}
			else {
				$endregdate = JCalProHelperDate::getDateTimeFromParts($this->registration_end_hour, $this->registration_end_minute, 0, $this->registration_end_month, $this->registration_end_day, $this->registration_end_year, $this->timezone)->toUtc();
				$this->registration_end_date = $endregdate->toSql();
				// ensure that the registration end date is before the start date
				if ($endregdate > $this->_basedatetime) {
					$this->setError(JText::_('COM_JCALPRO_EVENT_REGISTRATION_AFTER_START_DATE'));
					return false;
				}
			}
		}
		
		// create a UID for this event, if it doesn't have one
		// NOTE: the old method of creating on in v2 sucked, so we're sorta basing it off the iCalcreator class's
		// except we're not using date() and using core methods to get a random string :)
		if ('' == trim($this->common_event_id)) {
			$uid  = $this->_basedatetime->format('Ymd\THisT');
			// removed JUtility call for forwards compat with Joomla! 3.x
			$uid .= '-' . strtolower(substr(strrev(md5(JFactory::getConfig()->get('secret') . md5($uid . rand(0, $this->year)))), 0, 6)) . '@';
			$uid .= str_replace('/administrator', '', JURI::base());
			$this->common_event_id = $uid;
		}
		
		// ensure we have a canonical category
		if (empty($this->_cat_ids)) {
			$this->setError(JText::_('COM_JCALPRO_EVENT_CANONICAL_CATEGORY_NOT_SET'));
			return false;
		}
		
		// ensure we have a title
		if ('' == trim($this->title)) {
			$this->setError(JText::_('COM_JCALPRO_EVENT_EMPTY_TITLE'));
			return false;
		}
		// set alias
		if ('' == trim($this->alias)) {
			$this->alias = $this->title;
		}
		$this->alias = JApplication::stringURLSafe($this->alias);
		
		// check the recurrence if the event is recurring
		if ($this->recur_type && 0 == $this->rec_id) {
			
			// check the end type data - if it's a date, make sure we can parse it
			if (JCalPro::RECUR_END_TYPE_UNTIL == $this->recur_end_type) {
				$endparts = JCalProHelperDate::dateFromString($this->recur_end_until, '%Y-%m-%d');
				// if it's still false, send error
				if (false == $endparts) {
					$this->setError(JText::_('COM_JCALPRO_RECUR_END_UNTIL_CANNOT_PARSE'));
					return false;
				}
				// save this for use later :)
				$this->_parsed_end_parts = $endparts;
			}
			else {
				$endcount = (int) $this->recur_end_count;
				if (0 > $endcount) {
					$this->setError(JText::_('COM_JCALPRO_RECUR_END_COUNT_NEGATIVE'));
					return false;
				}
				else if (0 == $endcount) {
					$this->setError(JText::_('COM_JCALPRO_RECUR_END_COUNT_ZERO'));
					return false;
				}
			}
			
			// check the recurrence types to ensure the data is valid
			switch ((int) $this->recur_type) {
				
				case JCalPro::RECUR_TYPE_DAILY :
					// day count cannot be less than 0
					$period = (int) $this->rec_daily_period;
					if (0 > $period) {
						$this->setError(JText::_('COM_JCALPRO_REC_DAILY_PERIOD_NEGATIVE'));
						return false;
					}
					else if (0 == $period) {
						$this->setError(JText::_('COM_JCALPRO_REC_DAILY_PERIOD_ZERO'));
						return false;
					}
					break;
					
				case JCalPro::RECUR_TYPE_WEEKLY :
					// week count cannot be less than 0
					$period = (int) $this->rec_weekly_period;
					if (0 > $period) {
						$this->setError(JText::_('COM_JCALPRO_REC_WEEKLY_PERIOD_NEGATIVE'));
						return false;
					}
					else if (0 == $period) {
						$this->setError(JText::_('COM_JCALPRO_REC_WEEKLY_PERIOD_ZERO'));
						return false;
					}
					// set parsed week variable
					$this->_parsed_rec_weekly = array(
						(int) $this->rec_weekly_on_sunday
					,	(int) $this->rec_weekly_on_monday
					,	(int) $this->rec_weekly_on_tuesday
					,	(int) $this->rec_weekly_on_wednesday
					,	(int) $this->rec_weekly_on_thursday
					,	(int) $this->rec_weekly_on_friday
					,	(int) $this->rec_weekly_on_saturday
					);
					// clone the DateTime and put it back to the configured timezone
					$wdatetime = clone $this->_basedatetime;
					$wdatetime->toTimezone($this->timezone);
					$weekday = (int) $wdatetime->weekday();
					// check that at least one of the week boxes has been checked
					if (0 == array_sum($this->_parsed_rec_weekly)) {
						// set default
						$rec_weekly_var = 'rec_weekly_on_' . strtolower(JCalDate::$days[$weekday]);
						$this->{$rec_weekly_var} = 1;
						$this->_parsed_rec_weekly[$weekday] = 1;
					}
					// check that this event falls on one of our recurring days
					if (0 == $this->_parsed_rec_weekly[$weekday]) {
						$this->setError(JText::_('COM_JCALPRO_REC_INVALID_START_DAY') . ' ' . JCalPro::RECUR_TYPE_WEEKLY);
						return false;
					}
					
					break;
					
				case JCalPro::RECUR_TYPE_MONTHLY :
					// month count cannot be less than 0
					$period = (int) $this->rec_monthly_period;
					if (0 > $period) {
						$this->setError(JText::_('COM_JCALPRO_REC_MONTHLY_PERIOD_NEGATIVE'));
						return false;
					}
					else if (0 == $period) {
						$this->setError(JText::_('COM_JCALPRO_REC_MONTHLY_PERIOD_ZERO'));
						return false;
					}
					// check rec_monthly_type and ensure the repeat falls on the same day
					$type = $this->rec_monthly_type;
					// 0 is easy - just match this->day :D
					if (0 == $type && $this->day != $this->rec_monthly_day_number) {
						$this->setError(JText::_('COM_JCALPRO_REC_INVALID_START_DAY') . ' ' . JCalPro::RECUR_TYPE_MONTHLY . '.1');
						return false;
					}
					// check that we have a day at all
					if (1 == $type) {
						if (0 == $this->rec_monthly_day_type) {
							$this->setError(JText::_('COM_JCALPRO_REC_INVALID_START_DAY') . ' ' . JCalPro::RECUR_TYPE_MONTHLY . '.2');
							return false;
						}
						// just make sure the days match :)
						$mdatetime = clone($this->_basedatetime);
						$mdatetime->toTimezone($this->timezone);
						if ($this->rec_monthly_day_type != 1 + (int) $mdatetime->weekday()) {
							$this->setError(JText::_('COM_JCALPRO_REC_INVALID_START_DAY') . ' ' . JCalPro::RECUR_TYPE_MONTHLY . '.3');
							return false;
						}
					}
					
					break;
					
				case JCalPro::RECUR_TYPE_YEARLY :
					// year count cannot be less than 0
					$period = (int) $this->rec_yearly_period;
					if (0 > $period) {
						$this->setError(JText::_('COM_JCALPRO_REC_YEARLY_PERIOD_NEGATIVE'));
						return false;
					}
					else if (0 == $period) {
						$this->setError(JText::_('COM_JCALPRO_REC_YEARLY_PERIOD_ZERO'));
						return false;
					}
					// when repeating by year, we have to ensure that the month set to repeat in is the same
					// TODO: then why the hell is this an option? DOES NOT COMPUTE :P
					if ($this->month != $this->rec_yearly_on_month) {
						$this->setError(JText::_('COM_JCALPRO_REC_INVALID_START_DAY') . ' ' . JCalPro::RECUR_TYPE_YEARLY . '.1');
					}
					// check rec_yearly_type and ensure the repeat falls on the same day
					$type = $this->rec_yearly_type;
					// 0 is easy - just match this->day :D
					if (0 == $type && $this->day != $this->rec_yearly_day_number) {
						$this->setError(JText::_('COM_JCALPRO_REC_INVALID_START_DAY') . ' ' . JCalPro::RECUR_TYPE_YEARLY . '.2');
						return false;
					}
					// check that we have a day at all
					if (1 == $type) {
						if (0 == $this->rec_yearly_day_type) {
							$this->setError(JText::_('COM_JCALPRO_REC_INVALID_START_DAY') . ' ' . JCalPro::RECUR_TYPE_YEARLY . '.3');
							return false;
						}
						// just make sure the days match :)
						$ydatetime = clone($this->_basedatetime);
						$ydatetime->toTimezone($this->timezone);
						if ($this->rec_yearly_day_type != 1 + (int) $ydatetime->weekday()) {
							$this->setError(JText::_('COM_JCALPRO_REC_INVALID_START_DAY') . ' ' . JCalPro::RECUR_TYPE_YEARLY . '.4');
							return false;
						}
					}
					
					break;
			}
		}
		return true;
	}
	
	
	/**
	 * Method to store the event (and regenerate its children, if it has any)
	 * 
	 * @param bool $updateNulls
	 */
	public function store($updateNulls = false) {
		/**
		 * IMPORTANT NOTICE!!!
		 * 
		 * If an event repeats, this method calls $this->_cloneAndStoreRecurrence()
		 * _cloneAndStoreRecurrence then clones this table and re-runs store() on itself
		 * 
		 * store() handles some calculations etc. that may already be done on clones
		 * 
		 * Therefore, be VERY attentive to what NEEDS to be calculated BEFORE doing so.
		 * In addition, attempt to clean up as much as possible - the less memory used, the better!
		 * 
		 */
		
		// first things first - check if this is a clone or not!
		// recurrence clones will have a rec_id and no real id
		$isClone = (empty($this->id) && 0 !== (int) $this->rec_id);
		
		$app 	= JFactory::getApplication();
		$user = JFactory::getUser();
		
		// sometimes this is necessary, as _cat_ids will not be set (and we can't have canonical or cat set here)
		if (isset($this->canonical) && isset($this->cat) && empty($this->_cat_ids)) {
			// start our category array
			$this->_cat_ids = array();
			// add canonical
			if (isset($this->canonical)) {
				$this->_cat_ids[] = $this->canonical;
				unset($this->canonical);
			}
			// save the assigned categories to our private variable
			if (isset($this->cat)) {
				$this->_cat_ids = array_merge($this->_cat_ids, is_array($this->cat) ? $this->cat : explode('|', $this->cat));
				unset($this->cat);
			}
			$this->_cat_ids = array_unique($this->_cat_ids);
		}
		if (property_exists($this, 'canonical')) {
			unset($this->canonical);
		}
		if (property_exists($this, 'cat')) {
			unset($this->cat);
		}
		
		// user permissions will already be set from parents so only do so on non-clones
		if (!$isClone) {
			// for now just store created/modified dates via getDate
			// TODO: use date helper to force this date to UTC?
			$jdate = JFactory::getDate();
			// we need to check the user's permissions for setting the state and approval
			// the "published" and "approved" flag handling is dependent upon the "private" flag
			// if private = 0, we use core.moderate for approved and core.edit.state for published
			// if private = 1, we set approved as 1 and take published from the user
			// ALSO NOTE: if we're setting private to 0 and the event exists, we need to check
			// to ensure the event is not already private - this prevents users from bypassing moderation
			$canonical        = $this->_cat_ids[0];
			$canModerate      = JCalPro::canModerateEvents($canonical);
			$canEditState     = JCalPro::canDo('core.edit.state', $canonical);
			$canCreatePublic  = JCalPro::canDo('core.create', $canonical);
			$canCreatePrivate = JCalPro::canDo('core.create.private', $canonical);
			$canAutoPublish   = JCalPro::canDo('core.edit.autopublish', $canonical);
			$canAutoApprove   = JCalPro::canDo('core.moderate.autoapprove', $canonical);
			// go ahead and do quick checks on new events
			if (!$this->id) {
				
				// before doing anything with private events, we have to re-check the permissions
				// this is because if the user only has permission to create one or the other,
				// the select option is disabled and no value is sent
				// we're not going to just check if it's set though, as we want to enforce
				// if we can create either, do nothing...
				if ($canCreatePublic && $canCreatePrivate) {
					// nothing!
				}
				// if we can create public events but not private (or vice versa), force value
				else if (($canCreatePublic && !$canCreatePrivate) || (!$canCreatePublic && $canCreatePrivate)) {
					// use the private create value to set the value of the "private" var
					$this->private = (int) $canCreatePrivate;
				}
				// something is seriously wrong - we should probably fail here...
				else {
					$this->private = 0;
				}
				
				// private events get automatically approved and don't change published
				if ($this->private) {
					$this->approved = 1;
				}
				// public events need to be moderated (and not published automatically)
				else {
					if (!$canModerate) {
						$this->approved  = (bool) (int) $canAutoApprove;
					}
					if (!$canEditState) {
						$this->published = (bool) (int) $canAutoPublish;
					}
				}
				// set created information
				if (!intval($this->created)) {
					$this->created    = $jdate->toSql();
				}
				if (empty($this->created_by)) {
					$this->created_by = $user->get('id');
				}
			}
			// existing event
			else {
				// set modified information
				$this->modified    = $jdate->toSql();
				$this->modified_by = $user->get('id');
				// if this item has a rec_id, it is being detached - make it so
				if ($this->rec_id) {
					$this->detached_from_rec = 1;
				}
				// ensure private events are not being made public, unless done so by someone with core.edit.state
				if (0 == $this->private) {
					if (!$canEditState) {
						// check the old value and see if it's already private - if so, force the privacy value
						$this->_db->setQuery((string) $this->_db->getQuery(true)
							->select($this->_db->quoteName('private'))
							->from($this->_tbl)
							->where($this->_db->quoteName('id') . ' = ' . (int) $this->id)
						);
						$oldvalue = (int) $this->_db->loadResult();
						if ($oldvalue) {
							$this->private = 1;
						}
					}
					// now that we're sure our privacy setting is correct, handle approval
					if (!$canModerate) {
						// let's just do this quick & dirty...
						$this->_db->setQuery((string) $this->_db->getQuery(true)
							->select($this->_db->quoteName('approved'))
							->from($this->_tbl)
							->where($this->_db->quoteName('id') . ' = ' . (int) $this->id)
						);
						$this->approved = (int) $this->_db->loadResult();
					}
					// now handle published
					if (!$canEditState) {
						// let's just do this quick & dirty...
						$this->_db->setQuery((string) $this->_db->getQuery(true)
							->select($this->_db->quoteName('approved'))
							->from($this->_tbl)
							->where($this->_db->quoteName('id') . ' = ' . (int) $this->id)
						);
						$this->approved = (int) $this->_db->loadResult();
					}
				}
				// this is a private event - force approval and allow published
				else {
					$this->approved = 1;
				}
			}
		}
		// grab today from the date helper
		$today = JCalProHelperDate::getToday();
		// create a DateTime object for the start date
		$datetime = clone $this->_basedatetime;
		// get our sql-formatted string
		$this->start_date = $datetime->toSql();
		// calculate the end_date sql datetime
		// Here is where we deal with what kind of duration to use. If a duration is specified, we calculate the end_date to enter into the database.
		// If not, we enter a special end_date instead.
		switch ($this->duration_type) {
			// this is an event that lasts all day
			case JCalPro::JCL_EVENT_DURATION_ALL:
				$this->end_date = JCalProHelperDate::JCL_ALL_DAY_EVENT_END_DATE;
				break;
			// This is a normal event, with a SPECIFIED duration
			case JCalPro::JCL_EVENT_DURATION_DATE:
				// we want to use a different DateTime for the end, in case we need to use the original later
				$enddatetime = clone $datetime;
				// add the interval to our DateTime object
				$enddatetime->addDay($this->end_days)->addHour($this->end_hours)->addMin($this->end_minutes);
				// ensure the end is after the beginning
				if ($datetime >= $enddatetime) {
					$this->setError(JText::_('COM_JCALPRO_ERROR_END_DATE_BEFORE_START_DATE'));
					return false;
				}
				// set string value
				$this->end_date = $enddatetime->toSql();
				break;
			// this is an event with a specified end time instead of duration
			case JCalPro::JCL_EVENT_DURATION_TIME:
				$enddatetime = JCalProHelperDate::getDateTimeFromParts($this->end_hour, $this->end_minute, 0, $this->end_month, $this->end_day, $this->end_year, $this->timezone)->toUtc();
				// ensure the end is after the beginning
				if ($datetime >= $enddatetime) {
					$this->setError(JText::_('COM_JCALPRO_ERROR_END_DATE_BEFORE_START_DATE'));
					return false;
				}
				// set string value
				$this->end_date = $enddatetime->toSql();
				break;
			// This is an event where "No end date" was checked instead
			case JCalPro::JCL_EVENT_DURATION_NONE:
			default:
				$this->end_date = JCalProHelperDate::JCL_EVENT_NO_END_DATE;
				break;
		}
		// before storing, process the tags
		if (JCalProHelperTags::useTags()) {
			$this->tagsHelper->typeAlias = JCalPro::COM . '.event';
			$this->tagsHelper->preStoreProcess($this);
		}
		// we have to store first in case we don't have an id yet
		$store = parent::store($updateNulls);
		// if store was successful, there should now be an assigned id
		// finally, handle tags
		$tags = true;
		if ($store && JCalProHelperTags::useTags()) {
			$tags = $this->tagsHelper->postStoreProcess($this);
		}
		// we need to keep track of the ids of the children for re-saving later
		$children = array();
		// do the store shuffle
		if ($store) {
			
			/**
			 * recurrence handling
			 * 
			 */
			
			// NOTE: this is outside this check in case a recurring event is changed to a non-recurring one
			// just fucking obliterate any children that aren't detached
			// we'll revisit the idea of finding & updating children at a later time
			// TODO: add a new xref table for events & their children
			// then check in event controller for ids against that if not found in this one
			// BUGFIX: we have to clean out the assets table too!
			// thanks Elin for pointing us in the right direction here!
			// NOTE: only needed on parent events - children should not have children!
			if (!$isClone) {
				$this->_db->setQuery($this->_db->getQuery(true)
					->select('id')
					->from($this->_tbl)
					->where('rec_id = ' . (int) $this->id)
					->where('detached_from_rec = 0')
				);
				// see if we can't find the old children
				try {
					$oldchildren = $this->_db->loadColumn();
				}
				catch (Exception $e) {
					$oldchildren = array();
				}
				// do we have old children? handle them like the bastards they are
				if (!empty($oldchildren)) {
					$wheres = array();
					foreach ($oldchildren as $oc) {
						$wheres[] = $this->_db->quoteName('name') . '=' . $this->_db->quote('com_jcalpro.event.' . (int) $oc);
					}
					$this->_db->setQuery($this->_db->getQuery(true)
						->delete('#__assets')
						->where($wheres, 'OR')
					);
					try {
						$this->_db->query();
					}
					catch (Exception $e) {
						// TODO maybe alert user that something failed horribly? :P
					}
				}
				// delete the old children
				$this->_db->setQuery($this->_db->getQuery(true)
					->delete($this->_tbl)
					->where('rec_id = ' . (int) $this->id)
					->where('detached_from_rec = 0')
				);
				try {
					$this->_db->query();
				}
				catch (Exception $e) {
					// TODO maybe alert user that something failed horribly? :P
				}
			}
			
			// make sure no rec_id has been set!!!
			// TODO: check v2 to see if detached events get repeat options
			if (0 == $this->rec_id && $this->recur_type) {
				// what we need to do now is determine each recurrence and store it separately
				
				// go ahead and clone the original DateTime from above to use as our base
				$recurbasedatetime = clone $datetime;
				// put this copy back into our configured timezone (as the clone process will need it)
				$recurbasedatetime->toTimezone($this->timezone);
				
				// if we are basing our recurrence on recur_end_until, go ahead and build a DateTime for the given date
				// otherwise, we have to calculate our own end DateTime based on the various other values given
				// this little chunk is setting up our variables for the impending storm
				// take note this little check of recur_end_type will happen quite a few more times! ;)
				if (JCalPro::RECUR_END_TYPE_UNTIL == $this->recur_end_type) {
					// this shouldn't be empty... hmm
					if (empty($this->_parsed_end_parts)) {
						$endparts = JCalProHelperDate::dateFromString($this->recur_end_until, '%Y-%m-%d');
					}
					else {
						$endparts = $this->_parsed_end_parts;
					}
					// we have our parts, create a new DateTime for this end
					$enddatetime = JCalProHelperDate::getDateTimeFromParts(0, 0, 0, $endparts->month, $endparts->day, $endparts->year, $this->timezone)->toDayEnd();
				}
				else {
					$repeatedcount = 0;
					$endtimes = (int) $this->recur_end_count;
				}
				
				// ok, go through the recur options
				switch ((int) $this->recur_type) {
					/**
					 * DAILY REPEAT
					 * 
					 */
					case JCalPro::RECUR_TYPE_DAILY :
						// Daily repeat is pretty easy - the value of rec_daily_period becomes an interval that we add to the base
						// until either we hit the number of occurrences or we hit the end date
						
						// if we don't yet know the end DateTime, we need to calculate it
						if (2 != $this->recur_end_type) {
							// use the base as our start
							$enddatetime = clone $recurbasedatetime;
							// loop as many times as we need to add the interval
							for ($i=0; $i<$endtimes; $i++) {
								$enddatetime->addDay($this->rec_daily_period);
							}
						}
						// go ahead and increment the base once
						$recurbasedatetime->addDay($this->rec_daily_period);
						// now we should have the start & end DateTime and our interval - start looping & saving children!
						while ($recurbasedatetime < $enddatetime) {
							// clone & store recurrence
							$cyear  = $recurbasedatetime->year();
							$cmonth = $recurbasedatetime->month();
							$cday   = $recurbasedatetime->day();
							$children[] = $this->_cloneAndStoreRecurrence($cday, $cmonth, $cyear);
							// increment the base DateTime
							$recurbasedatetime->addDay($this->rec_daily_period);
						}
						// since we're saving the datetime we need to backtrack the period
						$recurbasedatetime->subDay($this->rec_daily_period);
						
						break;
						
					/**
					 * WEEKLY REPEAT
					 * 
					 */
					case JCalPro::RECUR_TYPE_WEEKLY :
						// the value of rec_weekly_period tells us how many weeks we have to skip to add recurrences
						// so what we need to do is find the next instance of the next set day
						// then add intervals for each day
						
						// find the day this event falls on
						// 0 for Sunday, 6 for Saturday
						$eventdaynum = $recurbasedatetime->weekday();
						// BUG: Sunday repeats were not being detected due to a logic bug
						// in order to fix, what we'll do is use a copy of the parsed array,
						// and add an extra bit to the beginning :)
						$weekdays = array_merge(array(), $this->_parsed_rec_weekly);
						array_unshift($weekdays, 0);
						$weekdays = array_values($weekdays);
						// reset the pointer of our parsed array to the beginning
						reset($weekdays);
						// walk the array and set the pointer based on the day number
						foreach ($weekdays as $k => $v) {
							// if this day is the key, we've hit where we need to be - but we need to be back one :)
							if (1 + (int) $recurbasedatetime->weekday() == $k) {
								$x = prev($weekdays);
								break;
							}
						}
						// add an initial day to the base time to start out
						$recurbasedatetime->addDay();
						
						// this loop is a little weird
						// we start by checking the datetimes against each other (if we're going by end date)
						// or the clone amounts (if we're going by count)
						// then we move the pointer up and check the value
						// if it's 1, there's an event so we clone & keep count
						//   if the clone count meets the limit, we break afterwards
						// if it's false, reset the pointer
						//   if the weeks are more than 1, we add an extra week to the date
						// we check the datetime (if we're going by end date) and break
						while (true) {
							
							// check the dates
							if (2 == $this->recur_end_type && $recurbasedatetime > $enddatetime) break;
							// check the counts
							if (1 == $this->recur_end_type && $repeatedcount == $endtimes - 1) break;
							
							// get the value of the next item in the week array and move the pointer forward
							$value = next($weekdays);
							// check the value
							// if it's 1, clone the event
							if (1 === $value) {
								// clone & store recurrence
								$cyear  = $recurbasedatetime->year();
								$cmonth = $recurbasedatetime->month();
								$cday   = $recurbasedatetime->day();
								$children[] = $this->_cloneAndStoreRecurrence($cday, $cmonth, $cyear);
								// increment the clone counter and check again
								if (1 == $this->recur_end_type && ++$repeatedcount == $endtimes - 1) break;
							}
							// if it's strictly false, we've gone past the end of the array
							// this means we've hit the end of the week
							else if (false === $value) {
								// reset the array pointer
								reset($weekdays);
								// move the DateTime forward
								$recurbasedatetime->addWeek($this->rec_weekly_period - 1);
								// don't add a day ;)
								continue;
							}
							// we're not really doing anything on days of 0 besides adding a day
							// but we'll also do that on values of 1 if we're not breaking out of the loop
							$recurbasedatetime->addDay();
							
						}
						
						// decrement for storage
						$recurbasedatetime->subWeek($this->rec_weekly_period - 1);
						
						break;
						
					/**
					 * MONTHLY REPEAT
					 * 
					 */
					case JCalPro::RECUR_TYPE_MONTHLY :
						// months and years are the same, except the variables used
						// set some variables for month, then move on to years
						
						$interval = 'month';
						$type = $this->rec_monthly_type;
						$order = $this->rec_monthly_day_order;
						$daytype = $this->rec_monthly_day_type;
						$period = $this->rec_monthly_period;
						$repeatingInMonths = true;
					
						
					/**
					 * YEARLY REPEAT
					 * 
					 */
					case JCalPro::RECUR_TYPE_YEARLY :
						
						$repeatingInMonths = isset($repeatingInMonths);
						
						if (!$repeatingInMonths) {
							$interval = 'year';
							$type = $this->rec_yearly_type;
							$order = $this->rec_yearly_day_order;
							$daytype = $this->rec_yearly_day_type;
							$period = $this->rec_yearly_period;
						}
						
						// add the first interval
						$recurbasedatetime->addX($period, $interval);
						// start a loop and keep adding months to $recurbasedatetime until we hit the limits
						while (true) {
							
							// check the dates
							if (JCalPro::RECUR_END_TYPE_UNTIL == $this->recur_end_type && $recurbasedatetime > $enddatetime) {
								break;
							}
							// check the counts
							if (JCalPro::RECUR_END_TYPE_LIMIT == $this->recur_end_type && $repeatedcount == $endtimes - 1) {
								break;
							}
							
							$cyear   = (int) $recurbasedatetime->year();
							$cmonth  = (int) $recurbasedatetime->month();
							
							// if we're doing it on day X of each month, we SHOULD be there now :)
							if (0 == $type) {
								$children[] = $this->_cloneAndStoreRecurrence($this->day, $cmonth, $cyear);
							}
							// otherwise we need to find the nth day, so we use the date helper to grab that
							else {
								$nth = JCalProHelperDate::getNthDayOfMonth($order, $daytype - 1, $cmonth, $cyear);
								if ($nth) {
									$children[] = $this->_cloneAndStoreRecurrence((int) $nth->day(), (int) $nth->month(), (int) $nth->year());
								}
							}
							
							// increment counters
							$recurbasedatetime->addX($period, $interval);
							if (1 == $this->recur_end_type) {
								$repeatedcount++;
							}
						}
						
						// decrement the base for storage
						$recurbasedatetime->subX($period, $interval);
						
						break;
					
					/**
					 * BAD JUJU AFOOT!
					 * 
					 */
					default:
						// wtf, this shouldn't happen
						// should we bother throwing an exception here? nah, just let it ride :P
				}
				// save the end recur time as MySQL
				$this->recur_end_datetime = $recurbasedatetime->toUtc()->toSql();
				// we have to store again - yuk
				// 
				$store = parent::store($updateNulls);
				// update all the children we just created
				$this->_db->setQuery((string) $this->_db->getQuery(true)
					->update($this->_tbl)
					->set($this->_db->quoteName('recur_end_datetime') . ' = ' . $this->_db->Quote($this->recur_end_datetime))
					->where($this->_db->quoteName('rec_id') . ' = ' . $this->_db->Quote($this->id))
					->where($this->_db->quoteName('detached_from_rec') . ' = 0')
				);
				$this->_db->query();
				// update the event children xref table
				$xrefQuery  = 'INSERT INTO #__jcalpro_event_xref (parent_id, child_id) VALUES ';
				$xrefValues = array();
				foreach ($children as $child) {
					$xrefValues[] = '(' . (int) $this->id . ', ' . (int) $child . ')';
				}
				if (!empty($xrefValues)) {
					$this->_db->setQuery($xrefQuery . implode(',', $xrefValues));
					$this->_db->query();
				}
			}
			
			/**
			 * xref handling
			 * 
			 */
			
			// check if _cat_ids is empty - if it is, we have to pull this from the request
			if (empty($this->_cat_ids)) {
				// we have to extract this data from JForm, so we have to fetch via JForm array
				$jform = $app->input->get('jform', array(), null);
				// if for some dumb reason jform isn't an array we should account for this
				// as well, this variable may not be set
				if (is_array($jform)) {
					// start array
					$this->_cat_ids = array();
					// add canonical
					if (array_key_exists('canonical', $jform)) {
						// we're not going to worry about what kind of data we get here, as we'll account for it later
						$this->_cat_ids = array($jform['canonical']);
					}
					// add secondary
					if (array_key_exists('cat', $jform)) {
						// we're not going to worry about what kind of data we get here, as we'll account for it later
						$this->_cat_ids = array_merge($this->_cat_ids, is_array($jform['cat']) ? $jform['cat'] : explode('|', $jform['cat']));
					}
				}
				// clean up
				unset($jform);
			}
			// we SHOULD have our data here now! :)
			if (!empty($this->_cat_ids)) {
				// we may receive an array from the request
				// this is doubtful, but let's go ahead and account for it anyways
				$cats = $this->_cat_ids;
				if (is_array($cats)) {
					$cats = implode('|', $cats);
				}
				// account for non-string variables by making it empty,
				// but only if the passed variable cannot be converted to a string
				else if (!is_string($cats)) {
					try {
						$cats = (string) $cats;
					}
					catch (Exception $e) {
						// ouch, failed converting to string - just make it blank
						$cats = '';
					}
				}
				// ok, we should have a string now
				// purge the xref table and repopulate
				// NOTE: clones shouldn't have categories, save a query
				if (!$isClone) {
					$this->_db->setQuery('DELETE FROM #__jcalpro_event_categories WHERE event_id = ' . (int) $this->id);
					$this->_db->query();
				}
				// now we need to convert our string back into an array and inject the records
				$cats = explode('|', $cats);
				// if we have categories, insert into xref table
				if (!empty($cats)) {
					// go ahead & force our fields to be integers, unique, and only values
					JArrayHelper::toInteger($cats);
					$cats = array_unique($cats);
					$cats = array_values($cats);
					// create the query to repopulate the xref table with the categories
					$query = 'INSERT INTO #__jcalpro_event_categories (event_id, category_id, canonical) VALUES (%s)';
					// build the internals of the insert values
					$values = array();
					foreach ($cats as $cat) {
						$values[] = (int) $this->id . ', ' . (int) $cat . ', ' . (empty($values) ? '1' : '0');
					}
					if (!empty($values)) {
						// insert xref records
						$this->_db->setQuery(sprintf($query, implode('), (', $values)));
						$this->_db->query();
					}
				}
			}
			
		}
		// store
		return $store && $tags;
	}
	
	/**
	 * overload bind
	 */
	public function bind($array, $ignore = '') {
		// json data
		foreach (array('params', 'metadata') as $json) {
			if (isset($array[$json])) {
				$registry = new JRegistry;
				if (is_array($array[$json])) {
					$registry->loadArray($array[$json]);
				}
				else if (is_string($array[$json])) {
					$registry->loadString($array[$json]);
				}
				else if (is_object($array[$json])) {
					
				}
				$array[$json] = (string) $registry;
			}
		}
		// force the date arrays to the correct values
		foreach (self::$_date_array_parts as $key => $prefix) {
			if (array_key_exists($key, $array) && is_array($array[$key])) {
				// make sure we have a start_date and the user-supplied parts
				// set the various start date parts
				foreach (self::$_time_vars as $var) {
					// skip this one if it's not set
					if (!array_key_exists($var, $array[$key])) {
						continue;
					}
					// add the variable to the array
					$array[$prefix . $var] = intval($array[$key][$var]);
				}
				// unset the start date array
				unset($array[$key]);
			}
		}
		
		$this->_cat_ids = array();
		// check our canonical category
		if (array_key_exists('canonical', $array)) {
			$this->_cat_ids[] = $array['canonical'];
			unset($array['canonical']);
		}
		
		// make sure cat gets set, if available
		if (array_key_exists('cat', $array)) {
			$this->_cat_ids = array_merge($this->_cat_ids, is_array($array['cat']) ? $array['cat'] : explode('|', $array['cat']));
			unset($array['cat']);
		}
		// checkboxes that are unchecked don't get sent in the request
		// this means weekly repeat gets screwed up - you can't UNcheck a day :)
		foreach (JCalDate::$days as $day) {
			$key = "rec_weekly_on_" . strtolower($day);
			if (!array_key_exists($key, $array)) {
				$array[$key] = 0;
			}
			unset($key);
		}
		
		// TODO: why is this here?
		if (array_key_exists('catid', $array)) {
			unset($array['catid']);
		}
		
		// ok, handle normal bind
		return parent::bind($array, $ignore);
	}
	
	/**
	 * method to clone & store a recurrence of this event
	 * 
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 */
	private function _cloneAndStoreRecurrence($day, $month, $year) {
		if (0 >= $this->id) {
			return false;
		}
		// we need a clone of this event
		$clone = clone $this;
		// set the rec_id to be this id
		$clone->rec_id = $this->id;
		// reset the clone's id so we can save it as a new item
		$clone->id = 0;
		// reset the month/day/year variables in the clone to match the current base time
		$clone->year  = $year;
		$clone->month = $month;
		$clone->day   = $day;
		// set the clone as not checked out
		$clone->checked_out = 0;
		$clone->checked_out_time = 0;
		// special handling of events with specified end time
		// the parent event's configured end time will carry over otherwise
		// and store will fail because the event ends before it starts
		if (JCalPro::JCL_EVENT_DURATION_TIME === $clone->duration_type) {
			// figure out the duration between the beginning and end
			$start = clone $this->_basedatetime;
			$end   = JCalDate::_($this->end_date);
			$diff  = $start->diff($end);
			unset($start);
			unset($end);
			// now take this diff and add it to the clone values
			$clone->end_year  = $year  + (int) $diff->y;
			$clone->end_month = $month + (int) $diff->m;
			$clone->end_day   = $day   + (int) $diff->d;
		}
		// we have to check before we store, because if we don't the base DateTime doesn't get set properly
		// we don't have to act on the return value
		$clone->check();
		// save the clone
		$clone->store();
		// get the clone id
		$clone_id = (int) $clone->id;
		// remove the clone
		unset($clone);
		// send back the id so we can update info later
		return $clone_id;
	}

	/**
	 * Method to set the approved state for a row or list of rows in the database
	 * table.  The method respects checked out rows by other users and will attempt
	 * to checkin rows that it can after adjustments are made.
	 *
	 * @param   mixed    $pk      An optional array of primary key values to update.  If not
	 *                            set the instance property value is used.
	 * @param   integer  $state   The approved state. eg. [0 = unapproved, 1 = approved]
	 * @param   integer  $userId  The user id of the user performing the operation.
	 *
	 * @return  boolean  True on success.
	 */
	public function approve($pks = null, $state = 1, $userId = 0) {
		return $this->toggle('approved', $pks, $state, $userId);
	}
	
	public function feature($pks = null, $state = 1, $userId = 0) {
		return $this->toggle('featured', $pks, $state, $userId);
	}
	
	public function toggle($column, $pks = null, $state = 1, $userId = 0) {
		// Initialise variables.
		$k = $this->_tbl_key;

		// Sanitize input.
		JArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state  = (int) $state;

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks)) {
			if ($this->$k) {
				$pks = array($this->$k);
			}
			// Nothing to set approved state on, return false.
			else {
				$e = new JException(JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
				$this->setError($e);

				return false;
			}
		}

		// Update the publishing state for rows with the given primary keys.
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set($this->_db->quoteName($column) . ' = '.(int) $state);

		// Determine if there is checkin support for the table.
		if (property_exists($this, 'checked_out') || property_exists($this, 'checked_out_time')) {
			$query->where('(checked_out = 0 OR checked_out = '.(int) $userId.')');
			$checkin = true;
		}
		else {
			$checkin = false;
		}

		// Build the WHERE clause for the primary keys.
		$query->where($k.' = '.implode(' OR '.$k.' = ', $pks).' OR ((rec_id = '.implode(' OR rec_id = ', $pks).') AND detached_from_rec = 0)');

		$this->_db->setQuery($query);

		// Check for a database error.
		if (!$this->_db->query()) {
			$e = new JException(JText::sprintf('COM_JCALPRO_ERROR_' . strtoupper($column) . '_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);

			return false;
		}

		// If checkin is supported and all rows were adjusted, check them in.
		if ($checkin && (count($pks) == $this->_db->getAffectedRows())) {
			// Checkin the rows.
			foreach($pks as $pk)
			{
				$this->checkin($pk);
			}
		}

		// If the JTable instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks)) {
			$this->$column = $state;
		}

		$this->setError('');
		return true;
	}
	
	/**
	 * Override delete in order to delete tags
	 * 
	 * (non-PHPdoc)
	 * @see JTable::delete()
	 */
	public function delete($pk = null) {
		$result = parent::delete($pk);
		$tags = true;
		if (JCalProHelperTags::useTags()) {
			$this->tagsHelper->typeAlias = JCalPro::COM . '.event';
			$tags = $this->tagsHelper->deleteTagData($this, $pk);
		}
		return $result && $tags;
	}
}
