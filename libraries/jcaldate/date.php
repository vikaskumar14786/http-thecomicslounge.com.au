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

defined('_JEXEC') or die;

jimport('jcaldate.exceptions.date');
jimport('jcaldate.timezone');

/**
 * JCalPro DateTime class
 * 
 * includes extra methods that allow us to output date parts easier
 * it also starts its life in UTC time, NOT server time
 * 
 */
class JCalDate extends DateTime
{
	// formats
	const JCL_FORMAT_MYSQL   = 'Y-m-d H:i:s';
	const JCL_FORMAT_REQUEST = 'Y-m-d';
	
	// zero dates
	const ZERO_DATE_MYSQL   = '0000-00-00 00:00:00';
	const ZERO_DATE_REQUEST = '0000-00-00';
	
	// borrowed from JDate, for proper translation
	const DAY_ABBR = "\x021\x03";
	const DAY_NAME = "\x022\x03";
	const MONTH_ABBR = "\x023\x03";
	const MONTH_NAME = "\x024\x03";
	
	private static $_exceptions = array(
		-1  => 'LIB_JCALDATE_ERROR_UNSPECIFIED'
	,	98  => 'LIB_JCALDATE_ERROR_LEGACY_DIFF'
	,	99  => 'LIB_JCALDATE_ERROR_LEGACY_STRTOTIME'
	,	100 => 'LIB_JCALDATE_ERROR_ZERO_DATE_MYSQL'
	,	101 => 'LIB_JCALDATE_ERROR_ZERO_DATE'
	,	200 => 'LIB_JCALDATE_ERROR_INTERVAL_TYPE_MISMATCH'
	);
	
	public static $months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
	public static $days   = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	
	/**
	 * constructor
	 * 
	 * @see DateTime
	 * 
	 * @param unknown_type $time
	 * @param unknown_type $timezone
	 * 
	 * @throws JCalDateException
	 */
	public function __construct($time = 'now', $timezone = null) {
		
		// if the time is numeric, there is a good chance it's a Unix timestamp
		if (is_int($time)) {
			$time = "@$time";
		}
		
		// force null timezones to use UTC
		if (is_null($timezone)) {
			$timezone = JCalTimeZone::utc();
		}
		// force strings to be JCalTimeZone objects
		else if (is_string($timezone)) {
			$timezone = new JCalTimeZone($timezone);
		}
		
		try {
			// WE MUST CALL THE PARENT CONSTRUCTOR HERE!!!
			// https://bugs.php.net/bug.php?id=48476
			parent::__construct($time, $timezone);
		}
		catch (Exception $e) {
			throw new JCalDateException($e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * static method, same as calling "new JCalDate"
	 * 
	 * @return JCalDate
	 * 
	 * @throws JCalDateException
	 */
	public static function _($time = 'now', $timezone = null) {
		return new JCalDate($time, $timezone);
	}
	
	/**
	 * method to return only the day
	 * 
	 * @param  bool   true for integer, false for padded string
	 * 
	 * @return mixed  day represented by this DateTime
	 */
	public function day($int = true) {
		$day = (int) parent::format('j');
		return $int ? $day : str_pad($day, 2, '0', STR_PAD_LEFT);
	}
	
	/**
	 * method to return only the day, padded
	 * 
	 * @return string  day represented by this DateTime
	 */
	public function dayPadded() {
		return $this->day(false);
	}
	
	/**
	 * method to return only the day number (1-366)
	 * 
	 * @return int day represented by this DateTime
	 */
	public function dayNum() {
		return 1 + (int) parent::format('z');
	}
	
	/**
	 * method to return only the month
	 * 
	 * @param  bool true for integer, false for padded string
	 * 
	 * @return int month represented by this DateTime
	 */
	public function month($int = true) {
		$month = (int) parent::format('n');
		return $int ? $month : str_pad($month, 2, '0', STR_PAD_LEFT);
	}
	
	/**
	 * method to return only the month, padded
	 * 
	 * @return string  month represented by this DateTime
	 */
	public function monthPadded() {
		return $this->month(false);
	}
	
	/**
	 * method to return only the year
	 * 
	 * @param  bool true for integer, false for padded string
	 * 
	 * @return int year represented by this DateTime
	 */
	public function year($int = true) {
		$year = (int) parent::format('Y');
		return $int ? $year : str_pad($year, 4, '0', STR_PAD_LEFT);
	}
	
	/**
	 * method to return only the year, padded
	 * 
	 * @return string  year represented by this DateTime
	 */
	public function yearPadded() {
		return $this->year(false);
	}
	
	/**
	 * method to return only the hour (0-23)
	 * 
	 * @param  bool true for integer, false for padded string
	 * 
	 * @return int hour represented by this DateTime
	 */
	public function hour($int = true) {
		$hour = (int) parent::format('G');
		return $int ? $hour : str_pad($hour, 2, '0', STR_PAD_LEFT);
	}
	
	/**
	 * method to return only the hour, padded
	 * 
	 * @return string  hour represented by this DateTime
	 */
	public function hourPadded() {
		return $this->hour(false);
	}
	
	/**
	 * method to return only the minutes
	 * 
	 * @param  bool true for integer, false for padded string
	 * 
	 * @return int minute represented by this DateTime
	 */
	public function minute($int = true) {
		$minute = (int) parent::format('i');
		return $int ? $minute : str_pad($minute, 2, '0', STR_PAD_LEFT);
	}
	
	/**
	 * method to return only the minutes, padded
	 * 
	 * @return string  minute represented by this DateTime
	 */
	public function minutePadded() {
		return $this->minute(false);
	}
	
	/**
	 * method to return only the seconds
	 * 
	 * @param  bool true for integer, false for padded string
	 * 
	 * @return int second represented by this DateTime
	 */
	public function second($int = true) {
		$second = (int) parent::format('s');
		return $int ? $second : str_pad($second, 2, '0', STR_PAD_LEFT);
	}
	
	/**
	 * method to return only the seconds, padded
	 * 
	 * @return string  second represented by this DateTime
	 */
	public function secondPadded() {
		return $this->second(false);
	}
	
	/**
	 * method to return the day of week (0 for Sunday, 6 for Saturday)
	 * 
	 * @return int day of week represented by this DateTime
	 */
	public function weekday() {
		return (int) parent::format('w');
	}
	
	/**
	 * method to return the ISO week number (1-53)
	 * 
	 * @return int ISO week number represented by this DateTime
	 */
	public function week($monday = true) {
		// monday based? easy, use ISO:
		if ($monday) return (int) parent::format('W');
		// sunday based
		$first = clone $this;
		$first->toYearStart();
		$offset = $first->weekday();
		return (int) ceil(($offset + $this->dayNum()) / $this->daysInWeek());
	}
	
	/**
	 * method to return the name of the month
	 * 
	 * @return string  name of month represented by this DateTime
	 */
	public function monthName() {
		// note that the day name by itself will be translated
		// so no need to use our wrapper "format" method
		return JText::_(parent::format('F'));
	}
	
	/**
	 * method to return the abbreviated name of the month
	 * 
	 * @return string  name of month represented by this DateTime
	 */
	public function monthShortName() {
		return $this->format('M');
	}
	
	/**
	 * method to return the name of the day
	 * 
	 * @return string  name of day represented by this DateTime
	 */
	public function dayName() {
		// note that the day name by itself will be translated
		// so no need to use our wrapper "format" method
		return JText::_(parent::format('l'));
	}
	
	/**
	 * method to return the abbreviated name of the day
	 * 
	 * @return string  name of day represented by this DateTime
	 */
	public function dayShortName() {
		return $this->format('D');
	}
	
	/**
	 * method to return the Unix timestamp
	 * we have to actually watch out for DST here
	 * if the DateTime is in DST, the correct offset is not applied correctly
	 * TODO: determine if this is a PHP version-specific bug or not
	 * 
	 * @return number
	 */
	public function timestamp() {
		$offset = (int) parent::format('Z');
		if ($offset) {
			if (0 > $offset) $offset *= -1;
			$offset %= 3600;
			if (0 == $offset) $offset = 3600;
		}
		return (int) parent::format('U') + (((int) $this->dst()) * $offset);
	}
	
	/**
	 * method to return the timezone in use
	 * 
	 * @return string
	 */
	public function timezone() {
		return $this->format('e');
	}
	
	/**
	 * method to determine if the this DateTime is in DST or not
	 * 
	 * @return bool
	 */
	public function dst() {
		return (1 == parent::format('I'));
	}
	
	/**
	 * method to return the date as a MySQL-compatible string
	 * 
	 * @return string date in MySQL format
	 * 
	 * @deprecated
	 */
	public function toMySQL() {
		jimport('joomla.log.log');
		JLog::add(JText::_('LIB_JCALDATE_WARNING_TOMYSQL_DEPRECATED'), JLog::WARNING, 'deprecated');
		return $this->toSql();
	}
	
	/**
	 * method to return the date as a MySQL-compatible string
	 * 
	 * @return string date in MySQL format
	 */
	public function toSql() {
		return $this->format(self::JCL_FORMAT_MYSQL);
	}
	
	/**
	 * method to return the date in the format expected by JCalPro requests
	 * 
	 * @return string date in JCalPro request format
	 */
	public function toRequest() {
		return $this->format(self::JCL_FORMAT_REQUEST);
	}
	
	/**
	 * method to return the date in the RFC822 format
	 * 
	 * @return string date in RFC822 format
	 */
	public function toRFC822() {
		return $this->format(DateTime::RFC822);
	}
	
	/**
	 * method to output the parts of the time as an array
	 * 
	 * @return array
	 */
	public function toArray() {
		return array(
			'year'   => $this->year()
		,	'month'  => $this->month()
		,	'day'    => $this->day()
		,	'hour'   => $this->hour()
		,	'minute' => $this->minute()
		,	'second' => $this->second()
		);
	}
	
	/**
	 * method to get the number of days in the week (lol)
	 * 
	 * http://thedailywtf.com/Comments/Nondeterministic-Months.aspx
	 * 
	 * @return int
	 */
	public function daysInWeek() {
		return 7;
	}
	
	/**
	 * method to get the number of days in the month
	 * 
	 * @return int
	 */
	public function daysInMonth() {
		return (int) parent::format('t');
	}
	
	/**
	 * method to get the number of days in the year
	 * 
	 * @return number
	 */
	public function daysInYear() {
		return 365 + ((int) parent::format('L') ? 1 : 0);
	}
	
	/**
	 * method to get the number of months in the year (lol)
	 * 
	 * http://thedailywtf.com/Comments/Nondeterministic-Months.aspx
	 * 
	 * @return int
	 */
	public function monthsInYear() {
		return 12;
	}
	
	/**
	 * method for PHP 5.2 backcompat
	 * 
	 * @param mixed $interval
	 */
	public function add($interval) {
		if (!is_string($interval)) {
			parent::add($interval);
		}
		else {
			$this->modify("+{$interval}");
		}
	}
	
	/**
	 * method for PHP 5.2 backcompat
	 * 
	 * @param mixed $interval
	 */
	public function sub($interval) {
		if (!is_string($interval)) {
			parent::sub($interval);
		}
		else {
			$this->modify("-{$interval}");
		}
	}
	
	/**
	 * sets this instance to UTC time
	 * 
	 * @return this (for chaining)
	 */
	public function toUtc() {
		$this->setTimezone(JCalTimeZone::utc());
		return $this;
	}
	
	/**
	 * sets this instance to the Joomla! time
	 * 
	 * @return this (for chaining)
	 */
	public function toJoomla() {
		$this->setTimezone(JCalTimeZone::joomla());
		return $this;
	}
	
	/**
	 * sets this instance to the Joomla! user time
	 * 
	 * @return this (for chaining)
	 */
	public function toUser() {
		$this->setTimezone(JCalTimeZone::user());
		return $this;
	}
	
	/**
	 * sets this instance to the provided timezone
	 * NOTE: this differs from setTimezone in that it accepts a string
	 * 
	 * @see DateTime::setTimezone
	 * 
	 * @return this (for chaining)
	 */
	public function toTimezone($timezone) {
		if ($timezone instanceof JCalTimeZone || $timezone instanceof DateTimeZone) {
			$this->setTimezone($timezone);
		}
		else {
			$this->setTimezone(new JCalTimeZone($timezone));
		}
		return $this;
	}
	
	/**
	 * checks type variable used for date modification
	 * 
	 * @param unknown_type $type
	 * 
	 * @return boolean
	 * 
	 * @throws JCalDateException
	 */
	private static function checkModType($type) {
		switch ($type) {
			case 'second':
			case 'minute':
			case 'hour':
			case 'day':
			case 'week':
			case 'month':
			case 'year':
				return true;
			default:
				self::_throwException(200);
		}
	}
	
	/**
	 * adds X number of Y to this DateTime
	 * 
	 * @param int    $interval  interval to add
	 * @param string $type      type to add - one of second, minute, hour, day, week, month, year
	 * 
	 * @return this (for chaining)
	 * 
	 * @throws JCalDateException
	 */
	public function addX($interval, $type) {
		self::checkModType($type);
		$interval = (int) $interval;
		$string = "+{$interval} {$type}s";
		if (0 === $interval) {
			return $this;
		}
		else if (1 === $interval) {
			$string = "+1 {$type}";
		}
		else if (-1 === $interval) {
			$string = "-1 {$type}";
		}
		else if (-1 > $interval) {
			$string = "{$interval} {$type}s";
		}
		$this->modify($string);
		return $this;
	}
	
	/**
	 * subtracts X number of Y to this DateTime
	 * 
	 * @param int    $interval  interval to subtract
	 * @param string $type      type to subtract - one of second, minute, hour, day, week, month, year
	 * 
	 * @return this (for chaining)
	 * 
	 * @throws JCalDateException
	 */
	public function subX($interval, $type) {
		return $this->addX(-1 * $interval, $type);
	}
	
	/**
	 * adds X seconds to this DateTime
	 * 
	 * @param int $sec
	 * 
	 * @return this (for chaining)
	 */
	public function addSec($sec = 1) {
		return $this->addX($sec, 'second');
	}
	
	/**
	 * subtracts X seconds from this DateTime
	 * 
	 * @param int $sec
	 * 
	 * @return this (for chaining)
	 */
	public function subSec($sec = 1) {
		return $this->addSec(-1 * $sec);
	}
	
	/**
	 * adds X minutes to this DateTime
	 * 
	 * @param int $min
	 * 
	 * @return this (for chaining)
	 */
	public function addMin($min = 1) {
		return $this->addX($min, 'minute');
	}
	
	/**
	 * subtracts X minutes from this DateTime
	 * 
	 * @param int $min
	 * 
	 * @return this (for chaining)
	 */
	public function subMin($min = 1) {
		return $this->addMin(-1 * $min);
	}
	
	/**
	 * adds X hours to this DateTime
	 * 
	 * @param int $hour
	 * 
	 * @return this (for chaining)
	 */
	public function addHour($hour = 1) {
		return $this->addX($hour, 'hour');
	}
	
	/**
	 * subtracts X hours from this DateTime
	 * 
	 * @param int $hour
	 * 
	 * @return this (for chaining)
	 */
	public function subHour($hour = 1) {
		return $this->addHour(-1 * $hour);
	}
	
	/**
	 * adds X days to this DateTime
	 * 
	 * @param int $day
	 * 
	 * @return this (for chaining)
	 */
	public function addDay($day = 1) {
		return $this->addX($day, 'day');
	}
	
	/**
	 * subtracts X days from this DateTime
	 * 
	 * @param int $day
	 * 
	 * @return this (for chaining)
	 */
	public function subDay($day = 1) {
		return $this->addDay(-1 * $day);
	}
	
	/**
	 * adds X weeks to this DateTime
	 * 
	 * @param int $week
	 * 
	 * @return this (for chaining)
	 */
	public function addWeek($week = 1) {
		return $this->addX($week, 'week');
	}
	
	/**
	 * subtracts X weeks from this DateTime
	 * 
	 * @param int $week
	 * 
	 * @return this (for chaining)
	 */
	public function subWeek($week = 1) {
		return $this->addWeek(-1 * $week);
	}
	
	/**
	 * adds X months to this DateTime
	 * 
	 * @param int $month
	 * 
	 * @return this (for chaining)
	 */
	public function addMonth($month = 1) {
		return $this->addX($month, 'month');
	}
	
	/**
	 * subtracts X months from this DateTime
	 * 
	 * @param int $month
	 * 
	 * @return this (for chaining)
	 */
	public function subMonth($month = 1) {
		return $this->addMonth(-1 * $month);
	}
	
	/**
	 * adds X years to this DateTime
	 * 
	 * @param int $year
	 * 
	 * @return this (for chaining)
	 */
	public function addYear($year = 1) {
		return $this->addX($year, 'year');
	}
	
	/**
	 * subtracts X years from this DateTime
	 * 
	 * @param int $year
	 * 
	 * @return this (for chaining)
	 */
	public function subYear($year = 1) {
		return $this->addYear(-1 * $year);
	}
	
	/**
	 * moves this DateTime to the given type
	 * 
	 * @param int    $interval  interval to add
	 * @param string $type      type to add - one of second, minute, hour, day, week, month, year
	 * 
	 * @return this (for chaining)
	 * 
	 * @throws JCalDateException
	 */
	public function toX($interval, $type) {
		self::checkModType($type);
		$i = $this->{$type}() - (int) $interval;
		return $this->subX($i, $type);
	}
	
	/**
	 * moves this DateTime to the given year
	 * 
	 * @param int $year
	 * 
	 * @return this (for chaining)
	 */
	public function toYear($year) {
		return $this->toX($year, 'year');
	}
	
	/**
	 * moves this DateTime to the given month
	 * 
	 * @param int $month
	 * 
	 * @return this (for chaining)
	 */
	public function toMonth($month) {
		return $this->toX($month, 'month');
	}
	
	/**
	 * moves this DateTime to the given day
	 * 
	 * @param int $day
	 * 
	 * @return this (for chaining)
	 */
	public function toDay($day) {
		return $this->toX($day, 'day');
	}
	
	/**
	 * moves this DateTime to the given hour
	 * 
	 * @param int $hour
	 * 
	 * @return this (for chaining)
	 */
	public function toHour($hour) {
		return $this->toX($hour, 'hour');
	}
	
	/**
	 * moves this DateTime to the given minute
	 * 
	 * @param int $minute
	 * 
	 * @return this (for chaining)
	 */
	public function toMin($minute) {
		return $this->toX($minute, 'minute');
	}
	
	/**
	 * moves this DateTime to the given second
	 * 
	 * @param int $second
	 * 
	 * @return this (for chaining)
	 */
	public function toSec($second) {
		return $this->toX($second, 'second');
	}
	
	/**
	 * moves DateTime to the current time (but not the current date)
	 * 
	 * @return this (for chaining)
	 */
	public function toNowTime() {
		$now = new JCalDate('now', $this->timezone());
		return $this->toSec($now->second(true))->toMin($now->minute(true))->toHour($now->hour(true));
	}
	
	/**
	 * moves DateTime to the current date (but not the current time)
	 * 
	 * @return this (for chaining)
	 */
	public function toNowDate() {
		$now = new JCalDate('now', $this->timezone());
		return $this->toYear($now->year(true))->toMonth($now->month(true))->toDay($now->day(true));
	}
	
	/**
	 * moves DateTime to the current time and date
	 * 
	 * @return this (for chaining)
	 */
	public function toNow() {
		return $this->toNowDate()->toNowTime();
	}
	
	/**
	 * moves DateTime to the beginning of the hour (XX:00:00)
	 * 
	 * @return this (for chaining)
	 */
	public function toHourStart() {
		return $this->toSec(0)->toMin(0);
	}
	
	/**
	 * moves DateTime to the end of the hour (XX:59:59)
	 * 
	 * @return this (for chaining)
	 */
	public function toHourEnd() {
		return $this->toMin(59)->toSec(59);
	}
	
	/**
	 * moves DateTime to the end of the day (23:59:59)
	 * 
	 * @return this (for chaining)
	 */
	public function toDayEnd() {
		return $this->toHour(23)->toHourEnd();
	}
	
	/**
	 * moves DateTime to the beginning of the day (00:00:00)
	 * 
	 * @return this (for chaining)
	 */
	public function toDayStart() {
		return $this->toHour(0)->toHourStart();
	}
	
	/**
	 * moves DateTime to the beginning of the week (00:00:00 on Sunday)
	 * if Monday is desired, just add a day ;)
	 * 
	 * @return this (for chaining)
	 */
	public function toWeekStart() {
		$num = $this->weekday();
		return $this->toDayStart()->subDay($num);
	}
	
	/**
	 * moves DateTime to the end of the week (23:59:59 on Saturday)
	 * if Sunday is desired, just add a day ;)
	 * 
	 * @return this (for chaining)
	 */
	public function toWeekEnd() {
		return $this->toWeekStart()->addDay(7)->subSec();
	}
	
	/**
	 * moves DateTime to the beginning of the month
	 * 
	 * @return this (for chaining)
	 */
	public function toMonthStart() {
		return $this->toDay(1)->toDayStart();
	}
	
	/**
	 * moves DateTime to the end of the month
	 * 
	 * @return this (for chaining)
	 */
	public function toMonthEnd() {
		$days = $this->toDayStart()->daysInMonth();
		$d = $this->day();
		return $this->addDay(($days - $d) + 1)->subSec();
	}
	
	/**
	 * moves DateTime to the beginning of the year
	 * 
	 * @return this (for chaining)
	 */
	public function toYearStart() {
		$m = $this->toDayStart()->toMonthStart()->month();
		return $this->subMonth($m - 1);
	}
	
	/**
	 * moves DateTime to the end of the year (Dec 31, 23:59:59)
	 * 
	 * @return this (for chaining)
	 */
	public function toYearEnd() {
		$m = $this->toDayStart()->toMonthStart()->month();
		return $this->addMonth(13 - $m)->subSec();
	}

	/**
	 * for Aethan :)
	 * 
	 * @return this (for chaining)
	 */
	public function toTheDayMyLifeChanged() {
		return $this->toTimezone('America/New_York')->toYear(2006)->toMonth(2)->toDay(21)->toHour(21)->toMin(11)->toSec(0);
	}
	
	/**
	 * Overrides DateTime::format to translate the days and months
	 * 
	 * @see DateTime::format()
	 */
	public function format($format) {
		$translate = false;
		// we don't use a "translate" flag - always translate if possible
		if (preg_match('/[DlMF]/', $format)) {
			$original = "$format";
			// Do string replacements for date format options that can be translated.
			$format = preg_replace('/(^|[^\\\])l/', "\\1" . self::DAY_NAME, $format);
			$format = preg_replace('/(^|[^\\\])D/', "\\1" . self::DAY_ABBR, $format);
			$format = preg_replace('/(^|[^\\\])F/', "\\1" . self::MONTH_NAME, $format);
			$format = preg_replace('/(^|[^\\\])M/', "\\1" . self::MONTH_ABBR, $format);
			if ($original != $format) $translate = true;
		}
		$return = parent::format($format);
		// if we need to translate, do so
		if ($translate) {
			// Manually modify the month and day strings in the formatted time.
			if (false !== strpos($return, self::DAY_NAME)) {
				$return = str_replace(self::DAY_NAME, JText::_(self::$days[$this->weekday()]), $return);
			}
			if (false !== strpos($return, self::DAY_ABBR)) {
				$return = str_replace(self::DAY_ABBR, JText::_(substr(self::$days[$this->weekday()], 0, 3)), $return);
			}
			if (false !== strpos($return, self::MONTH_NAME)) {
				$return = str_replace(self::MONTH_NAME, JText::_(self::$months[$this->month() - 1]), $return);
			}
			if (false !== strpos($return, self::MONTH_ABBR)) {
				$return = str_replace(self::MONTH_ABBR, JText::_(self::$months[$this->month() - 1] . '_SHORT'), $return);
			}
		}
		// return formatted string
		return $return;
	}
		
	/**
	 * method to wrap the PHP 5.3 diff method for PHP 5.2 backcompat
	 * THE UNDERLYING CLASS BEING USED IS INCOMPLETE IN PHP 5.2 - USE AT YOUR OWN RISK!!!!!!!!
	 * 
	 * @param  $date
	 * @param  $absolute
	 * 
	 * @return JCalInterval
	 * 
	 * @throws JCalDateException
	 * @throws JCalIntervalException
	 */
	public function diff($date, $absolute = false) {
		// make sure we have our interval class
		require_once 'interval.php';
		// get the 5.3 interval & convert to a JCalInterval
		if (method_exists('DateTime', 'diff')) {
			$interval = parent::diff($date, $absolute);
		}
		// TODO: fix this for 5.2!!!
		else {
			// FORMAT PxYxMxDTxHxMxS
			$start  = clone $this;
			$string = 'P';
			$time   = false;
			$end    = is_a($date, 'JCalDate') ? clone $date : new JCalDate($date->format(JCalDate::JCL_FORMAT_MYSQL, $this->timezone()));
			foreach (array('year', 'month', 'day', 'hour', 'minute', 'second') as $int) {
				if ('hour' == $int) {
					$time = true;
				}
				$num = 0;
				while ($start->$int() < $end->$int()) {
					$num++;
					$start->{'add' . ucwords($int)}();
				}
				if ($num || ('second' == $int && 'P' == $string)) {
					if ($time) {
						$string .= 'T';
						$time = false;
					}
					$string .= strtoupper(substr($int, 0, 1)) . $num;
				}
			}
			$interval = new DateInterval($string);
		}
		return JCalInterval::createFromInterval($interval);
	}
	
	/**
	 * static method to wrap the PHP 5.3 createFromFormat method for PHP 5.2 backcompat
	 * 
	 * @param string $format
	 * @param mixed  $time
	 * @param mixed  $timezone
	 * 
	 * @return JCalDate object
	 * 
	 * @throws JCalDateException
	 */
	public static function createFromFormat($format, $time, $timezone = null) {
		// force null timezones to use UTC
		if (is_null($timezone)) {
			$timezone = JCalTimeZone::utc();
		}
		// force timezone to be a JCalTimeZone
		if (!($timezone instanceof JCalTimeZone)) {
			$timezone = new JCalTimeZone($timezone);
		}
		// extra sanity checks to ensure zero dates get handled correctly
		// TODO: try other formats as well?
		switch ($format) {
			case self::JCL_FORMAT_MYSQL:
				if (self::ZERO_DATE_MYSQL == $time) {
					self::_throwException(100);
				}
			case self::JCL_FORMAT_REQUEST:
				if (self::ZERO_DATE_REQUEST == $time) {
					self::_throwException(101);
				}
		}
		// if we're using PHP 5.3, use internal method and return a JCalDate
		if (method_exists('DateTime', 'createFromFormat')) {
			$date = DateTime::createFromFormat($format, $time, $timezone);
			if (!($date instanceof DateTime)) {
				self::_throwException(-1);
			}
			$jcld = new JCalDate($date->format(self::JCL_FORMAT_MYSQL), $date->getTimezone());
			return $jcld;
		}
		else {
			// since PHP 5.2 assumes that the time is in the server's timezone
			// unless the string has a timezone set, we have a problem
			// what we're going to do is set the default timezone based on this,
			// parse the string, then reset back to whatever it was before
			// TODO: pre-parse this string and check that the timezone is present?
			// OR create a DateTime and check the timezone provided against the default?
			$oldtz = date_default_timezone_get();
			date_default_timezone_set((string) $timezone);
			$stime = strtotime($time);
			// we CAN NOT have false here!
			if (false === $stime) {
				self::_throwException(99, array($stime));
			}
			$time = "@$stime";
			date_default_timezone_set($oldtz);
			$date = new DateTime($time, $timezone);
			$jcld = new JCalDate($date->format(DateTime::RFC850));
			$jcld->setTimezone($timezone);
			return $jcld;
		}
	}
	
	/**
	 * static method to create a new instance based on MySQL format
	 * 
	 * @param $time
	 * @param $timezone
	 * 
	 * @return JCalDate object
	 * 
	 * @throws JCalDateException
	 */
	public static function createFromMySQLFormat($time, $timezone = null) {
		// special cases for zero dates
		switch ($time) {
			case self::ZERO_DATE_MYSQL:
				self::_throwException(100);
			default:
				// return our formatted date
				return self::createFromFormat(self::JCL_FORMAT_MYSQL, $time, $timezone);
		}
	}
	
	/**
	 * private method to throw an exception
	 * 
	 * @param unknown_type $code
	 * 
	 * @throws JCalDateException
	 */
	private static function _throwException($code, $args = null) {
		JFactory::getLanguage()->load('lib_jcaldate.sys', JPATH_ROOT);
		// ensure this is a known code - if not, throw unspecified
		if (!array_key_exists($code, self::$_exceptions)) $code = -1;
		// in case we need to add data to the exception message
		$exception = self::$_exceptions[$code];
		if (is_array($args)) $exception = vsprintf(JText::_($exception), $args);
		// throw the exception
		throw new JCalDateException($exception, $code);
	}
	
	/**
	 * magic method to convert this DateTime to a string
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->toMySQL() . ' ' . $this->timezone();
	}
}
