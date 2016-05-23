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

jimport('ical.loader');
jimport('jcaldate.timezone');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');

abstract class JCalProHelperIcal
{

	/**
	 * static method to display an array of events in .ics format
	 *
	 * @param array $events
	 */
	public static function toIcal($events) {

		// instantiate new calendar
		$ical = new vcalendar();

		// better for outlook 2003 : set method = publish
		$ical->setMethod('PUBLISH');

		// set main properties
		$ical->setConfig('unique_id', str_replace('/administrator', '', trim( JURI::base(), '/')));

		// set some X-properties
		$ical->setProperty("X-WR-CALNAME", JText::_('COM_JCALPRO_CALNAME'));
		$ical->setProperty("X-WR-CALDESC", JText::_('COM_JCALPRO_CALDESC'));
		$ical->setProperty("X-WR-TIMEZONE", (string) JCalTimeZone::joomla());
		
		// keep track of parent events because we need to ensure those are added instead of attached children, and only once
		$added      = array();
		// keep track of exdates for events that haven't been added yet
		$exdates    = array();
		// keep track of timezones
		$timezones  = array();
		// some events may somehow not have ids (plugins or something)
		$dynamic    = -1;
		// frequency values
		$freq_types = array(false, 'COUNT', 'UNTIL');
		$freqs      = array(false, 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY');

		if (!empty($events)) {
			foreach ($events as $event) {
				// check event uid
				$event_uid = false;
				if (isset($event->common_event_id) && !empty($event->common_event_id)) {
					$event_uid = $event->common_event_id;
				}
				$key       = $event->id ? $event->id : $dynamic--;
				$recurs    = isset($event->recur_type) && $event->recur_type;
				$child     = isset($event->rec_id) && $event->rec_id;
				$detached  = isset($event->detached_from_rec) && $event->detached_from_rec;
				$parent    = $child ? $event->rec_id : 0;
				$freq      = array_key_exists((int) $event->recur_type, $freqs) ? $freqs[(int) $event->recur_type] : false;
				$freq_type = array_key_exists((int) $event->recur_end_type, $freq_types) ? $freq_types[(int) $event->recur_end_type] : false;
				// if this event recurs we may have problems
				// sometimes (in cases where the parent is in the far past)
				// we may only have the children and not the parent
				// so we need to get the parent event somehow
				// it may already be in this list
				// it may not be
				// the good thing is we SHOULD be able to assume it's one of ours
				// because of the rec_id check to determine repeat
				// (why would rec_id be set otherwise?)
				
				// if it's a child, reset the key if the parent has been added
				// but only if it hasn't been detached
				if ($child && !$detached) {
					$key = $event->rec_id;
				}
				// this key is added already
				if (false !== array_search($key, $added)) {
					continue;
				}
				// child events with no parent in the list - either a part of a series or an orphan
				else if ($child) {
					// this has not been added but is an attached child event
					// we need to load the parent and use it instead
					if (!$detached) {
						$event = JCalPro::getModelInstance('Event')->getItem($parent);
					}
					// ok this one is detached - we need to exclude it from the parent in addition to adding it
					else {
						// we have the parent - update it
						if (false !== array_search($event->rec_id, $added)) {
							// we need to find the event in our ical and add an EXDATE
							$i = 0;
							while ($parent_item = $ical->getComponent('vevent')) {
								if ($parent_item->getProperty('uid') == $event_uid) {
									$parent_item->setProperty('exdate', array($event->datetime->toSql()), array('TZID' => (string) $event->datetime->timezone()));
									$ical->setComponent($parent_item, 'vevent', $i);
									break;
								}
								$i++;
							}
						}
						// we haven't added the parent event yet so we can't assign an EXRULE :(
						else {
							// store these orphan EXDATE needs in an array and check for any when the event is a parent
							$exdate = "e" . $event->rec_id;
							if (!array_key_exists($exdate, $exdates)) {
								$exdates[$exdate] = array();
							}
							$exdates[$exdate][] = $event->datetime->toSql();
						}
					}
				}
				
				$added[] = $key;
				// prepare vevent object with elements from current event
				$item = new vevent();
				// add title
				$item->setProperty('summary', $event->title);
				// add description
				$item->setProperty('description', $event->description);
				// set UID
				if ($event_uid) {
					$item->setProperty('uid', $event_uid);
				}
				
				$tzstring = (string) $event->timezone;
				// timezones need to be explicit, apparently - thanks outlook
				if (!in_array($tzstring, $timezones)) {
					// add to the array so we don't add again
					$timezones[] = $tzstring;
				}
				
				$item_start = array(
					'year'  => $event->year
				,	'month' => $event->month
				,	'day'   => $event->day
				,	'hour'  => 0
				,	'min'   => 0
				,	'sec'   => 0
				,	'tz'    => $tzstring
				);
				
				$item_params = array();
				$item_params['TZID'] = $tzstring;
				
				// we'll need the duration type as an integer
				$duration_type = (int) $event->duration_type;
				// we need to set different start/end times based on duration_type
				switch ($duration_type) {
					// all day event
					case JCalPro::JCL_EVENT_DURATION_ALL:
						$item_start = $event->datetime->format('Ymd');
						$end_datetime = clone $event->datetime;
						$end_datetime->addDay();
						$item_end = $end_datetime->format('Ymd');
						$item_params['VALUE'] = 'DATE';
						$item->setProperty('dtend', $item_end, $item_params);
						//unset($item_start['hour']);
						//unset($item_start['min']);
						//unset($item_start['sec']);
						//$item_params['hour'] = array('VALUE' => 'DATE');
						break;
						// no end date
					case JCalPro::JCL_EVENT_DURATION_NONE:
						$item_start['hour'] = $event->hour;
						$item_start['min'] = $event->minute;
						break;
						// start and end date
					case JCalPro::JCL_EVENT_DURATION_DATE:
						$item_start['hour'] = $event->hour;
						$item_start['min'] = $event->minute;
						if (isset($event->end_datetime) && !empty($event->end_datetime)) {
							$end = array(
								'year'  => $event->end_datetime->year(true)
							,	'month' => $event->end_datetime->month(true)
							,	'day'   => $event->end_datetime->day(true)
							,	'hour'  => $event->end_datetime->hour(true)
							,	'min'   => $event->end_datetime->minute(true)
							,	'sec'   => $event->end_datetime->second(true)
							,	'tz'    => $tzstring
							);
							$item->setProperty('dtend', $end, $item_params);
						}
						break;
				}
				$item->setProperty('dtstart', $item_start, $item_params);
				
				// item url
				$item->setProperty('url', $event->fullhref);
				
				// item repeat
				if ($recurs && !$detached) {
					// our rrule values
					$rrule_value = array();
					// we have a frequency - start building RRULE
					if ($freq && $freq_type) {
						$rrule_value['FREQ'] = $freq;
						switch ($freq_type) {
							case 'COUNT':
								$rrule_value[$freq_type] = (int) $event->recur_end_count;
								break;
							case 'UNTIL':
								$rrule_value[$freq_type] = $event->recur_end_datetime;
								break;
						}
						$rrule_value['INTERVAL'] = (int) $event->{'rec_' . strtolower($freq) . '_period'};
						// yikes, no value
						if (!array_key_exists($freq_type, $rrule_value)) {
							continue;
						}
						// set the rule
						$item->setProperty('rrule', $rrule_value);
					}
					// check if this event had a detached child come through earlier ;)
					$needs_exdate = false;
					if (array_key_exists('e' . $event->id, $exdates)) {
						$item->setProperty('exdate', $exdates['e' . $event->id]);
					}
				}
				// add item to ical
				$ical->addComponent($item);
			}
		}
		
		// add timezones
		if (!empty($timezones)) {
			foreach ($timezones as $tz) {
				iCalUtilityFunctions::createTimezone($ical, $tz, array('X-LIC-LOCATION' => $tz));
			}
		}
		
		$ical->returnCalendar();
		jexit();
	}


	public static function fromIcal($file, $catid = false, $guess = false) {
		// get our DateTime classes
		jimport('jcaldate.date');
		jimport('jcaldate.timezone');
		// this will hold our events parsed from the ical
		$events = array();
		// instantiate new calendar
		$ical = new vcalendar();
		// set the calendar config so we can read the file
		// TODO: try non-ics files & see what happens ;)
		$ical->setConfig(array('directory' => dirname($file), 'filename' => basename($file)));
		// parse the data
		if (!$ical->parse()) {
			JFactory::getApplication()->enqueuemessage(JText::_('COM_JCALPRO_IMPORT_CANNOT_PARSE'), 'error');
			return false;
		}

		JCalProHelperLog::debug("Importing the following ical:\n" . print_r($file, 1));
		JCalProHelperLog::debug("Importing ical:\n" . print_r($ical, 1));

		// iCal will have a default TimeZone (or we'll default to... utc?)
		$timezone = $ical->getProperty('X-WR-TIMEZONE');
		// sometimes we'll end up with a vtimezone object instead of a simple property
		if (empty($timezone)) {
			$tz = $ical->getComponent('vtimezone');
			if (is_object($tz) && method_exists($tz, 'getProperty')) {
				$timezone = $tz->getProperty('TZID');
			}
		}
		if (empty($timezone)) {
			$timezone = 'UTC';
		}
		else if (is_array($timezone)) {
			$timezone = array_pop($timezone);
		}
		JCalProHelperLog::debug("Using timezone for this ical:\n" . print_r($timezone, 1));
		$timezone = new JCalTimeZone($timezone);

		// don't worry about the catid for now - just load whatever categories there are & we'll find the right one later
		JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');
		JCalProBaseModel::addIncludePath(JPATH_ROOT . '/components/com_jcalpro/models');
		$catModel   = JCalPro::getModelInstance('Categories', 'JCalProModel');
		$categories = $catModel->getItems();
		// loop through the events in the ics and create JTable instances for each
		while ($event = $ical->getComponent('vevent')) {
			$defcats    = array();
			// find our category
			foreach ($categories as $cat) {
				if ($cat->id == $catid) {
					$defcats[] = $cat->id;
					break;
				}
			}
			// handle categories if we're told to guess
			if ($guess) {
				$ecats = $event->getProperty('CATEGORIES');
				if (empty($ecats)) {
					$ecats = false;
				}
				else {
					$guesscats = array();
					// ecats is either going to be a string or an array
					// if it's not an array, it should be
					if (!is_array($ecats)) {
						$ecats = array($ecats);
					}
					// let's loop our ecats and our categories and compare the titles
					foreach ($ecats as $ecat) {
						foreach ($categories as $cat) {
							if (strtolower($cat->title) == strtolower($ecat)) $guesscats[] = $cat->id;
						}
					}
					// if we have any guessed categories, replace defcats
					if (!empty($guesscats)) $defcats = $guesscats;
				}
			}
				
			// here's our event data array
			$data = array(
				'approved'          => 1
			,	'published'         => 1
			,	'rec_id'            => 0
			,	'detached_from_rec' => 0
			);
			// add our categories
			$data['canonical'] = array_shift($defcats);
			$data['cat'] = $defcats;
			// event title
			$data['title'] = $event->getProperty('summary');
			// event description
			$data['description'] = $event->getProperty('description');
			// reformat the description to fix import bug
			$data['description'] = JString::str_ireplace('\n', "\n", $data['description']);
			$data['description'] = JString::str_ireplace('\r', "\r", $data['description']);
			$data['description'] = JString::str_ireplace("\r", "", $data['description']);
			$data['description'] = nl2br($data['description']);
			
			// handle parameters
			// TODO
				
			// common event id
				
			// this is broken. especially coming from somewhere where the uid is screwed up (like JCal Pro 2 lol)
				
			// if there is one, use it - otherwise leave empty and we'll generate a new one
			// TODO: lookup by common event id & if we have this event, we need to set the id
			$cei = $event->getProperty('uid');
			if (!empty($cei) && !is_numeric($cei)) {
				$data['common_event_id'] = $cei;
			}
				
			// get the time variables and create a new JCalDate from the values
			// we can't just pass the data directly because we need a decent timezone
			$dtstartdata = $event->getProperty('dtstart', false, true);
			//$dtstart = $event->getProperty('dtstart');
			$dtstart = $dtstartdata['value'];
				
			JCalProHelperLog::debug("Event has the following start time:\n" . print_r($dtstart, 1));
				
			// we have to watch out - some ics files won't have all this time data
			// so we have to make sure we initialize at least some of the data
			if (!array_key_exists('hour', $dtstart)) $dtstart['hour'] = '00';
			if (!array_key_exists('min', $dtstart))  $dtstart['min'] = '00';
			if (!array_key_exists('sec', $dtstart))  $dtstart['sec'] = '00';
				
			// if there is a timezone identified in the params, use it
			if (is_array($dtstartdata) && array_key_exists('params', $dtstartdata) && is_array($dtstartdata['params']) && array_key_exists('TZID', $dtstartdata['params'])) {
				$dtstart['tz'] = $dtstartdata['params']['TZID'];
			}
			else {
				// assuming UTC here for now (yuk)
				if (!array_key_exists('tz', $dtstart)) $dtstart['tz'] = $timezone;
				else if ('Z' == $dtstart['tz']) $dtstart['tz'] = 'UTC';
			}
				
			JCalProHelperLog::debug("Event start time, adjusted:\n" . print_r($dtstart, 1));
				
			// it would be nice to be able to use our helper class here
			// instead we're going to just create a string manually and pass to the main class
			// use this format: "2010-07-05T06:00:00Z" = DateTime::ATOM
			// UPDATE: use mysql format instead
			//$dtstring = $dtstart['year'].'-'.$dtstart['month'].'-'.$dtstart['day'].'T'.$dtstart['hour'].':'.$dtstart['min'].':'.$dtstart['sec'].$dtstart['tz'];
			$dtstring = $dtstart['year'].'-'.$dtstart['month'].'-'.$dtstart['day'].' '.$dtstart['hour'].':'.$dtstart['min'].':'.$dtstart['sec'];
			// UPDATE: instead of passing the timezone in the constructor
			// push the event to that timezone instead
			// this is because the event times are always in UTC (?)
			//$time = JCalDate::createFromFormat(DateTime::ATOM, $dtstring/*, $timezone*/)->toTimezone($timezone);
			//$time = JCalDate::createFromFormat(JCalDate::JCL_FORMAT_MYSQL, $dtstring, $dtstart['tz']);
			$time = JCalDate::createFromFormat(JCalDate::JCL_FORMAT_MYSQL, $dtstring, $dtstart['tz'])->toTimezone($timezone);
			// add time to data
			$data['year']       = $time->year();
			$data['month']      = $time->month();
			$data['day']        = $time->day();
			$data['hour']       = $time->hour();
			$data['minute']     = $time->minute();
			$data['timezone']   = $time->timezone();
			// here's the rub - we need to find out what to use not as the end date,
			// but the interval and interval type (like what we use internally)
			// it's tempting to use DateInterval, but it's not available in 5.2
			// and our backcompat lib is incomplete at this time
			// regardless, we can't really use an interval anyways,
			// as the values we need can't necessarily be derived from this
			// so what we need to do is check the different end types
			$dtenddata = $event->getProperty('dtend', false, true);
			$dtend = $dtenddata['value'];
			// if it's an event with "no end date" - there will be no dtend
			if (empty($dtend)) {
				// easy, no end :)
				$data['duration_type'] = 0;
				$data['end_days']      = 0;
				$data['end_hours']     = 0;
				$data['end_minutes']   = 0;
			}
			// this event has a definite ending, calculate the difference
			else {
				// we have to watch out - some ics files won't have all this time data
				// so we have to make sure we initialize at least some of the data
				if (!array_key_exists('hour', $dtend)) $dtend['hour'] = '00';
				if (!array_key_exists('min', $dtend))  $dtend['min'] = '00';
				if (!array_key_exists('sec', $dtend))  $dtend['sec'] = '00';

				// if there is a timezone identified in the params, use it
				if (is_array($dtenddata) && array_key_exists('params', $dtenddata) && is_array($dtenddata['params']) && array_key_exists('TZID', $dtenddata['params'])) {
					$dtend['tz'] = $dtenddata['params']['TZID'];
				}
				else {
					// assuming UTC here for now (yuk)
					if (!array_key_exists('tz', $dtend)) $dtend['tz'] = $timezone;
					else if ('Z' == $dtend['tz']) $dtend['tz'] = 'UTC';
				}
				// first, get a JCalDate the represents the ending
				//$dtstring = $dtend['year'].'-'.$dtend['month'].'-'.$dtend['day'].'T'.$dtend['hour'].':'.$dtend['min'].':'.$dtend['sec'].$dtend['tz'];
				$dtstring = $dtend['year'].'-'.$dtend['month'].'-'.$dtend['day'].' '.$dtend['hour'].':'.$dtend['min'].':'.$dtend['sec'];
				//$endtime = JCalDate::createFromFormat(DateTime::ATOM, $dtstring/*, $timezone*/)->toTimezone($timezone);
				//$endtime = JCalDate::createFromFormat(JCalDate::JCL_FORMAT_MYSQL, $dtstring, $dtend['tz']);
				$endtime = JCalDate::createFromFormat(JCalDate::JCL_FORMAT_MYSQL, $dtstring, $dtend['tz'])->toTimezone($timezone);
				// now comes the fun part ;)
				// clone the time and set it to the end of the day then check
				// also check to ensure that the seconds aren't taken into account
				$timecheck = clone $time;
				$timecheck->toDayEnd();
				// check end of day (with seconds then without)
				if ($timecheck == $endtime || $timecheck->toSec(0) == $endtime) {
					// it's an all day event!
					$data['duration_type'] = 2;
					$data['end_days']      = 0;
					$data['end_hours']     = 0;
					$data['end_minutes']   = 0;
				}
				else {
					// it's a timed event!
					$data['duration_type'] = 1;
					// we have a bunch of loops for the different parts
					// just loop & subtract until we find the values
					// seconds first (in case they matter) but don't bother keeping track
					while ($endtime->second() != $time->second()) {
						$endtime->subSec();
						// we're not keeping track of seconds
						continue;
					}
					// minutes
					$minutes = 0;
					while ($endtime->minute() != $time->minute()) {
						$endtime->subMin();
						$minutes++;
					}
					// hours
					$hours = 0;
					while ($endtime->hour() != $time->hour()) {
						$endtime->subHour();
						$hours++;
					}
					// days
					$days = 0;
					while ($endtime->day() != $time->day()) {
						$endtime->subDay();
						$days++;
					}
					$data['end_days']    = $days;
					$data['end_hours']   = $hours;
					$data['end_minutes'] = $minutes;
				}
			}
			// some iCal files are nice enough to give us repeat info
			// http://www.essentialpim.com/download/calendars/US%20Holidays.ics
			// looking for the RRULE property...
			$rrule = $event->getProperty('rrule');
			// we have repeats!
			if (!empty($rrule) && is_array($rrule) && array_key_exists('FREQ', $rrule)) {
				// let's go ahead and set the recur_type based on the FREQ
				$recur_type = 0;
				// this is a fun switch/case trick here
				// we should probably do this diffrently as we'll have to re-check the recur type later
				// but in the case of 0 recur type, we won't
				// so we'll save ourselves the overhead of calculating a bunch of stuff now
				// and do it later as needed
				switch ($rrule['FREQ']) {
					case 'YEARLY' : $recur_type++;
					case 'MONTHLY': $recur_type++;
					case 'WEEKLY' : $recur_type++;
					case 'DAILY'  : $recur_type++;
					break;
					// we don't handle SECONDLY, MINUTELY or HOURLY - treat them as "no recur"
					default:
						$recur_type = 0;
				}
				// if we have a recur type we handle, do so - otherwise don't bother
				if ($recur_type) {
					$data['recur_type'] = $recur_type;
					// according to rfc2445, we will have either UNTIL or COUNT (or neither)
					// if we have neither, we don't want to add indefinitely, so we'll use
					// an abitrary day in the future as our until - JCalProHelperDate::JCL_ALL_DAY_EVENT_END_DATE
					if (array_key_exists('COUNT', $rrule)) {
						// we have a COUNT, so set the recur end type and the count from this
						$data['recur_end_type']  = JCalPro::RECUR_END_TYPE_LIMIT;
						$data['recur_end_count'] = intval($rrule['COUNT']);
					}
					else {
						// we don't have a count - we have an "until"
						$data['recur_end_type'] = JCalPro::RECUR_END_TYPE_UNTIL;
						// so if we DO have an UNTIL, we need to format it properly (it's in UTC, take heed)
						// otherwise, we need to use the default end
						$untilstring = '2038-01-18T00:00:00Z';
						if (array_key_exists('UNTIL', $rrule)) {
							// sometimes this is a proper string, other times it's an array (?)
							// if it's an array, force it to be a string in our defined format
							if (is_array($rrule['UNTIL'])) {
								$untilstring = $rrule['UNTIL']['year'] . '-' . $rrule['UNTIL']['month'] . '-' . $rrule['UNTIL']['day'] . 'T00:00:00Z';
							}
							else {
								$untilstring = $rrule['UNTIL'];
							}
						}
						try {
							$enduntil = JCalDate::createFromFormat(DateTime::ATOM, $untilstring, 'UTC');
						}
						catch (Exception $e) {
							$data['recur_end_type'] = 0;
							continue;
						}
						$data['recur_end_until'] = $enduntil->toRequest();
					}
					// now all we have to do is figure out our rec_*_options
					// we will always have an INTERVAL of some sort
					// if it's not set, it defaults to 1 (as per rfc)
					$data['rec_' . strtolower($rrule['FREQ']) . '_period'] = (array_key_exists('INTERVAL', $rrule) ? min(1, intval($rrule['INTERVAL'])) : 1);
					// we need the BYDAY rule bbefore the switch, as it's used differently depending on FREQ
					// we want BYDAY to be an array, but it may not be - it may be a single entry (?)
					$rbyday = false;
					if (array_key_exists('BYDAY', $rrule)) {
						$rbyday = $rrule['BYDAY'];
						if (!is_array($rrule['BYDAY'])) {
							$rbyday = array($rrule['BYDAY']);
						}
					}
					// we need BYSETPOS for monthly/yearly repeats
					$rbysetpos = false;
					if (array_key_exists('BYSETPOS', $rrule)) {
						$rbysetpos = intval(trim($rrule['BYSETPOS']));
					}
					// here again we switch over the recur type and assign different things based on the data we have
					// we're not worried about a few keys - BYSECOND, BYMINUTE, BYHOUR, as we're not using them
					switch ($rrule['FREQ']) {
						//
						case 'YEARLY' :
							$interval                      = intval(array_key_exists('INTERVAL', $rrule) ? $rrule['INTERVAL'] : 1);
							$data['rec_yearly_type']       = 0;
							$data['rec_yearly_period']     = $interval;
							$data['rec_yearly_day_number'] = $data['day'];
							$data['rec_yearly_on_month']   = $data['month'];
							break;
						case 'MONTHLY':
							// if we're setting "on the Nth day" handle that
							if (false !== $rbysetpos) {
								$data['rec_monthly_type']      = 1;
								$data['rec_monthly_day_type']  = empty($rbyday) ? 0 : $rbyday[0];
								$data['rec_monthly_day_order'] = $rbysetpos;
							}
							// we must be setting "on the X"
							else {
								$data['rec_monthly_type'] = 0;
								// get BYMONTHDAY
								if (array_key_exists('BYMONTHDAY', $rrule)) {
									$rbymonthday = $rrule['BYMONTHDAY'];
									if (!is_array($rrule['BYMONTHDAY'])) {
										$rbymonthday = array($rrule['BYMONTHDAY']);
									}
									JArrayHelper::toInteger($rbymonthday);
									if (!empty($rbymonthday)) {
										$data['rec_monthly_day_number'] = array_pop($rbymonthday);
									}
								}
							}
							break;
						case 'WEEKLY' :
							// loop & set the proper recur
							if (!empty($rbyday)) {
								foreach ($rbyday as $byday) {
									switch ($byday) {
										case 'SU': $data['rec_weekly_on_sunday']    = 1; break;
										case 'MO': $data['rec_weekly_on_monday']    = 1; break;
										case 'TU': $data['rec_weekly_on_tuesday']   = 1; break;
										case 'WE': $data['rec_weekly_on_wednesday'] = 1; break;
										case 'TH': $data['rec_weekly_on_thursday']  = 1; break;
										case 'FR': $data['rec_weekly_on_friday']    = 1; break;
										case 'SA': $data['rec_weekly_on_saturday']  = 1; break;
									}
								}
							}
							break;
							// we can skip DAILY as all we needed was the period
						case 'DAILY'  : break;
					}
				}
			}
			// add our data to the stack
			$events[] = $data;
		}
		// all done, send back our events
		return $events;
	}
	
	static public function downloadIcsFile($url) {
		$app = JFactory::getApplication();
		// url cannot be empty!
		if (empty($url)) {
			$app->enqueueMessage(JText::_('COM_JCALPRO_IMPORT_REMOTE_EMPTY_FILENAME'), 'error');
			return false;
		}
		// change webcal:// urls to http://
		$url = preg_replace('/^(webcal\:\/{2})/', 'http://', $url);
		// make sure url is, you know, a url of some sort
		if (!preg_match('#^https?\:/{2}#', $url)) {
			$app->enqueueMessage(JText::_('COM_JCALPRO_IMPORT_REMOTE_BAD_URL'), 'error');
			return false;
		}
		// load up the net class
		require_once JPATH_ADMINISTRATOR . '/components/com_jcalpro/libraries/net.php';
		$net = new JCalNet;
		
		// fetch the url and save it to a temporary file
		$data = $net->request($url);
		$base = JFactory::getConfig()->get('tmp_path');
		$file = $base . '/' . md5($url) . '.tmp.ics';
		
		// try to write the file using JFile
		if (!JFile::write($file, $data)) {
			$app->enqueueMessage(JText::_('COM_JCALPRO_IMPORT_REMOTE_TMP_UNWRITABLE'), 'error');
			return false;
		}
		
		// return the file
		return $file;
	}
}
