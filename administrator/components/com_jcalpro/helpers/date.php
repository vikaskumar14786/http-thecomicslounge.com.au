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

jimport('joomla.utilities.date');
jimport('jcaldate.date');
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');

abstract class JCalProHelperDate
{
	// end date constants
	const JCL_ALL_DAY_EVENT_END_DATE_LEGACY   = '0000-00-00 00:00:01';
	const JCL_ALL_DAY_EVENT_END_DATE_LEGACY_2 = '9999-12-01 00:00:00';
	const JCL_ALL_DAY_EVENT_END_DATE          = '2038-01-18 00:00:00';
	const JCL_EVENT_NO_END_DATE               = '0000-00-00 00:00:00';
	
	// min dates
	const JCL_DATE_MIN = '1971-01-01 00:00:00';
	const JCL_DATE_MAX = '2038-01-17 00:00:00';
	
	// time formats
	const JCL_FORMAT_12 = 'g:i a';
	const JCL_FORMAT_24 = 'G:i';
	
	/**
	 * returns a static timestamp for this request
	 * 
	 * (replaces extcal_get_local_time)
	 * 
	 * @return int
	 */
	public static function time() {
		static $now = null;
		
		if (is_null($now)) {
			$now = time();
		}
		
		return $now;
	}
	
	/**
	 * returns a JCalDate object with the request date as the data
	 * if 'date' can be read from the request, this method returns that parsed
	 * if not, it returns today
	 * 
	 * (replaces v2 _GLOBAL $date)
	 * 
	 * @param bool
	 * 
	 * @return JCalDate
	 */
	public static function getDate($refresh = false) {
		static $date = null;
		
		if (is_null($date) || $refresh) {
			$reqDate = JFactory::getApplication()->input->get('date', '', 'string');
			if (!empty($reqDate) && preg_match('/^[1-9][0-9]{3}\-[0-9]{2}\-[0-9]{2}$/', $reqDate)) {
				$date = JCalDate::createFromMySQLFormat("$reqDate 00:00:00", JCalTimeZone::user());
			}
			if (empty($date)) {
				$date = self::getToday();
			}
		}
		return clone $date;
	}
	
	
	/**
	 * get "today" as a JCalDate object in USER TIME, not UTC
	 *
	 * this means - it's either the timezone configured by the user OR if none configured, Joomla! timezone
	 *
	 * (replaces v2 _GLOBAL $today)
	 *
	 * @return JCalDate
	 */
	public static function getToday() {
		static $today;
		if (!isset($today)) {
			// in 2.x we used jcServerDateToFormat here
			// simplified as of 3.2.4 because the old code was kind of convoluted and involved JDate
			$today = JCalDate::_()->toUser()->toDayStart();
		}
		return clone $today;
	}
	
	/**
	 * static method to get a given day as a DateTime object
	 * if no day is specified it returns the value of getDate
	 * 
	 * @param unknown_type $day
	 */
	public static function getDayAsObject($day = null) {
		if (is_null($day)
				|| !is_array($day)
				|| (is_array($day) && (!array_key_exists('day', $day) || !array_key_exists('month', $day) || !array_key_exists('year', $day)))
		) {
			return self::getDate();
		}
		// we need a DateTime object that represents the day
		$datetime = self::getDateTimeFromParts(0, 0, 0, $day['month'], $day['day'], $day['year'], JCalTimeZone::user());
		return $datetime;
	}
	
	/**
	 * static method to get today, with time as a DateTime object
	 */
	public static function getTodayTime() {
		static $today;
		if (!isset($today)) {
			/*
			// Localizing time : we get timestamp
			$zone_stamp = self::time();  // time stamp of 'now'
			
			// in 2.x we used jcServerDateToFormat here
			$tz = JCalTimeZone::user();
			$date = new JDate(self::time(), $tz->getName());
			$today = JCalDate::createFromMySQLFormat($date->format('Y-m-d H:i:00', true), $tz);
			*/
			// simplified as of 3.2.4 because the old code was kind of convoluted and involved JDate
			$today = JCalDate::_()->toUser();
		}
		return clone $today;
	}
	
	/**
	 * static method to return the format string for user display
	 * 
	 */
	public static function getUserTimeFormat() {
		static $format;
		if (is_null($format)) {
			$format = (string) ((0 === (int) JCalPro::config('time_format_24hours')) ? self::JCL_FORMAT_12 : self::JCL_FORMAT_24);
		}
		return $format;
	}
	
	/**
	 * static method to get the Nth day of a month
	 * Nth should be an integer in the range of 1-5
	 * where 1 is first and 5 is last
	 * 
	 * @param  int    $nth
	 * @param  int    $weekday
	 * @param  int    $month
	 * @param  int    $year
	 * @return mixed  Nth day of month in DateTime or false
	 */
	public static function getNthDayOfMonth($nth, $weekday, $month, $year) {
		// $nth should be an integer from 1-5, so check that it is
		if (!in_array($nth, range(1, 5))) {
			return false;
		}
		// $day should be an integer from 0-6
		if (!in_array($weekday, range(0, 6))) {
			return false;
		}
		// set a reverse flag to see if we're looking for the last day
		$reverse = (5 == $nth);
		// go ahead and create a new UTC DateTime object for the given month
		$datetime = JCalProHelperDate::getDateTimeFromParts(0, 0, 0, $month, 1, $year, 'UTC');
		// if we're looking for the last day, move our DateTime to the end
		if ($reverse) {
			$datetime->toMonthEnd()->toDayStart();
		}
		// we're going to start from the beginning of the month and step forwards 1 day
		// until we find the correct day, unless we're looking for "last" then we loop backwards
		// then, once we've found the correct day, we add/subtract weeks until we find the nth
		while (true) {
			// check the given day against $day
			// if it's the right day, we break
			// unless we're looking for the first or last day, then we return
			if ($datetime->weekday() == $weekday) {
				if ($reverse || 1 == $nth) {
					return $datetime;
				}
				break;
			}
			// since we haven't found the right day yet, add/subtract the day interval and try again
			if ($reverse) {
				$datetime->subDay();
			}
			else {
				$datetime->addDay();
			}
		}
		// loop again and add weeks until we find the right day
		while (--$nth) {
			// since we haven't found the right day yet, add the weekinterval and try again
			$datetime->addWeek();
		}
		// done changing date - return
		return $datetime;
	}
	
	/**
	 * static method to convert a DateTime to an array
	 * 
	 * @param DateTime $dt
	 */
	public static function getArrayFromDateTime($dt) {
		// initialize
		$array = array(
			'year'     => 0
		,	'month'    => 0
		,	'day'      => 0
		,	'hour'     => 0
		,	'minute'   => 0
		,	'second'   => 0
		, 'timezone' => ''
		);
		
		if ($dt) {
			$array['year']     = (int) $dt->format('Y');
			$array['month']    = (int) $dt->format('n');
			$array['day']      = (int) $dt->format('j');
			$array['hour']     = (int) $dt->format('G');
			$array['minute']   = (int) $dt->format('i');
			$array['second']   = (int) $dt->format('s');
			$array['timezone'] = $dt->format('e');
		}
		
		return $array;
	}
	
	
	public static function getWeekdays($names_only = false) {
		static $weekdays = null;
		static $names = null;
		
		if (is_null($weekdays)) {
			$weekdays = array();
			$names = array();
			for ($i=0; $i<=6; $i++) {
				$array_index = JCalPro::config('day_start', false) ? ($i + 1) % 7 : $i;
				if ( $array_index ) {
					$css_class = "weekdaytopclr"; // weekdays
				} else {
					$css_class = "sundaytopclr"; // sunday
				}
				$names[$i] = $weekdays[$i]['name'] = JText::_(JCalDate::$days[$array_index]);
				$weekdays[$i]['shortname'] = JText::_(JCalDate::$days[$array_index]);
				$weekdays[$i]['rawname'] = JCalDate::$days[$array_index];
				$weekdays[$i]['class'] = $css_class;
			}
		}
		
		return $names_only ? $names : $weekdays;
	}
	
	/**
	 * converts parts to a JCalDate object
	 * 
	 * @param int $hour
	 * @param int $minute
	 * @param int $second
	 * @param int $month
	 * @param int $day
	 * @param int $year
	 * @param mixed $timezone
	 * @return JCalDate object
	 */
	public static function getDateTimeFromParts($hour, $minute, $second, $month, $day, $year, $timezone = null) {
		// start our string variable
		$string = '';
		// the year should be 4 digits long
		$string .= str_pad(intval($year), 4, '0', STR_PAD_LEFT);
		// add the separator
		$string .= '-';
		// add the month
		$string .= str_pad(intval($month), 2, '0', STR_PAD_LEFT);
		// add the separator
		$string .= '-';
		// add the day
		$string .= str_pad(intval($day), 2, '0', STR_PAD_LEFT);
		// add the space
		$string .= ' ';
		// add the hour
		$string .= str_pad(intval($hour), 2, '0', STR_PAD_LEFT);
		// add the separator
		$string .= ':';
		// add the minute
		$string .= str_pad(intval($minute), 2, '0', STR_PAD_LEFT);
		// add the separator
		$string .= ':';
		// add the second
		$string .= str_pad(intval($second), 2, '0', STR_PAD_LEFT);
		// make sure we have some kind of timezone
		if (is_null($timezone)) {
			$timezone = JCalTimeZone::joomla();
		}
		if (!($timezone instanceof JCalTimeZone)) {
			// convert the timezone to a DateTimeZone object
			$timezone = new JCalTimeZone($timezone);
		}
		try {
			// use our internal JCalDate class :)
			$date = JCalDate::createFromMySQLFormat($string, $timezone);
		}
		catch (Exception $e) {
			JError::raiseError(500, JText::sprintf('COM_JCALPRO_CANNOT_PARSE_DATE_STRING', $string));
		}
		// return the DateTime object
		return $date;
	}
	
	/**
	 * Method to create a DateInterval object (or date interval string for PHP 5.2)
	 * 
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $second
	 * @param integer $month
	 * @param integer $day
	 * @param integer $year
	 * @param bool    $asString
	 * @return mixed DateInterval or string
	 */
	public static function getDateIntervalFromParts($hour, $minute, $second, $month, $day, $year, $asString = false) {
		// build from these parts
		$parts = array();
		// handle parts
		foreach (array('year', 'month', 'day', 'hour', 'minute', 'second') as $part) {
			$$part = intval($$part);
			if ($$part) {
				$parts[] = $$part . " $part" . (1 < $$part ? 's' : '');
			}
		}
		// TODO: throw an exception here?
		if (empty($parts)) return false;
		// TODO: use JCalInterval instead
		// NOTE: DateInterval does not exist in PHP 5.2
		$string = implode(' ', $parts);
		if (!class_exists('DateInterval') || $asString) {
			return $string;
		}
		// return our interval
		return DateInterval::createFromDateString($string);
	}
	
	/**
	 * Method to determine the number of days in the request date's month
	 * 
	 */
	public static function getDaysInMonth($dt = null) {
		static $staticdaysinmonth;
		$usestatic = is_null($dt) || !is_object($dt) || !($dt instanceof JCalDate);
		// we don't want to send the static variable if we're requesting from a DateTime
		if (is_null($staticdaysinmonth) || !$usestatic) {
			if ($usestatic) {
				// start by loading the requested day from the helper
				// take note this will contain today's date data if the helper couldn't parse the request date
				$datetime = self::getDate();
				$month = $datetime->month();
			}
			else {
				$datetime = clone($dt);
				$month = $datetime->format('n');
			}
			// force this DateTime object to use a UTC timezone
			$daysinmonth = $datetime->toUtc()->daysInMonth();
			// free up the DateTime object's memory
			unset($datetime);
			// either set static or return
			if ($usestatic) $staticdaysinmonth = $daysinmonth; else return $daysinmonth;
		}
		// return our days
		return $staticdaysinmonth;
	}
	
	
	public static function getFirstDay() {
		static $firstday = null;
		
		if (is_null($firstday)) {
			$date = self::getDate();
			$datetime = self::getDateTimeFromParts(0, 0, 0, $date->month(), 1, $date->year(), $date->getTimezone());
			// get first day
			$firstday = $datetime->weekday();
			if (JCalPro::config('day_start', false)) $firstday-=1;
			$firstday = ($firstday < 0) ? $firstday + 7: $firstday % 7;
		}
		
		return $firstday;
	}

	/**
	 * Explode a date as entered by a user into its components
	 * (day, month, year), using the date format that
	 * was used for entry
	 * Can only operate if separator is - / : _ +
	 * @param string $date   the user input
	 * @param string $format the date format (strftime kind) used for input
	 * @return object the extracted day, month and year
	 * 
	 * this replaces jclExtractDetailsFromDate
	 */
	public static function dateFromString($date, $format) {
		// make sure $date is ACTUALLY a string before handing it over
		$date = (string) $date;
	
		$separators = '[-\/\:_+]';
		$pattern = '#[0-9]{1,4}' .$separators. '[0-9]{1,4}' .$separators. '[0-9]{1,4}$#iU';
		// check entry
		if (!$result = preg_match($pattern, $date)) {
			return false;
		};
	
		// normalize format string to a - separator
		$format = preg_replace( '#' . $separators . '+#iU', '-', $format);
		$date = preg_replace( '#' . $separators . '+#iU', '-', $date);
	
		// now we can split both
		$formatBits = explode( '-', $format);
		$dateBits = explode( '-', $date);
	
		// check data size
		if (empty( $formatBits) || empty( $dateBits) || (count ($formatBits) != count($dateBits))) {
			return false;
		}
	
		// prepare result
		$details = new StdClass();
	
		// iterate over bits and basic validation
		for( $i = 0; $i < count( $formatBits); $i++) {
			switch ($formatBits[$i]) {
				case '%m' :
					$details->month = (int)$dateBits[$i];
					if ($details->month < 1 || $details->month > 12) {
						return false;
					}
					break;
				case '%d' :
					$details->day = (int)$dateBits[$i];
					if ($details->day < 1 || $details->day > 31) {
						return false;
					}
					break;
				case '%Y' :
					$details->year = (int)$dateBits[$i];
					if ($details->year < 1971 || $details->year > 2037) {
						return false;
					}
			
			// standard stuff
			
					break;
				default :
					return false;
					break;
			}
		}
	
		return $details;
	}
	
	/**
	 * http://bugs.php.net/bug.php?id=51819
	 * 
	 */
	private function _workaroundPHPBug51819( $zoneId) {
		
		$bad= array( 'Australia/ACT', 'Australia/LHI', 'Australia/NSW', 'Europe/Isle_of_Man');
		$good = array( 'Australia/Act', 'Australia/Lhi', 'Australia/Nsw', 'Europe/Isle_Of_Man');
		$id = str_replace( $bad, $good, $zoneId);
	
		return $id;
	}
	
	/**
	 * translates an interval string into user language
	 * 
	 * @param unknown_type $interval
	 * @return string
	 */
	public static function translateInterval($interval) {
		$periods = array(
			'y' => 'year'
		,	'm' => 'month'
		,	'w' => 'week'
		,	'd' => 'day'
		,	'h' => 'hour'
		,	'i' => 'minute'
		,	's' => 'second'
		);
		// convert DateInterval objects to strings
		jimport('jcaldate.interval');
		if (is_a($interval, 'DateInterval') || is_a($interval, 'JCalInterval')) {
			$parts = array();
			foreach ($periods as $var => $period) {
				if (!property_exists($interval, $var)) {
					continue;
				}
				$val = (int) $interval->$var;
				if (0 < $val) {
					$parts[] = $val . ' ' . $period . (1 < $val ? 's' : '');
				}
			}
			$interval = empty($parts) ? '' : implode(', ', $parts);
		}
		// force to string
		$interval = (string) $interval;
		// loop the periods and translate
		foreach ($periods as $period) {
			if (false !== JString::strpos(JString::strtolower($interval), $period)) {
				$singular = JText::_('COM_JCALPRO_' . strtoupper($period));
				$plural   = JText::_('COM_JCALPRO_' . strtoupper($period) . 'S');
				if (!empty($plural)) {
					$interval = JString::str_ireplace($period . 's', $plural, $interval);
				}
				if (!empty($singular)) {
					$interval = JString::str_ireplace($period, $singular, $interval);
				}
			}
		}
		return $interval;
	}
	
	/**
	 * convert a date to the provided format
	 * 
	 * @param unknown_type $ts     Unix timestamp
	 * @param string       $format date format
	 * @param bool         $local  utc (true) or server (false)
	 * 
	 * @deprecated
	 */
	public static function format($ts, $format = null, $local = false) {
		JCalPro::registerHelper('log');
		JCalProHelperLog::debug('Deprecated Method: ' . __METHOD__);
		$jcDate = new JDate($ts);
		if (is_null($format)) {
			$format = JCalDate::JCL_FORMAT_MYSQL;
		}
		return false === strpos('%', $format) ? $jcDate->format($format, $local) : $jcDate->toFormat($format, $local);
	}
	

	public static function getWeekNumber($day, $month, $year) {
		// create a new JCalDate from the pieces & return the value
		return self::getDateTimeFromParts(0, 0, 0, $month, $day, $year)->week((int) JCalPro::config('day_start'));
	}
}
