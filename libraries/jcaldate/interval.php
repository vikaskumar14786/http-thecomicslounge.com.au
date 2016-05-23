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

jimport('jcaldate.exceptions.interval');

/**
 * Backwards compatability class for PHP 5.2
 * 
 * this is NOT 100% compatible, guaranteed ;)
 * USE AT YOUR OWN RISK!!!!!!!!!!!!!!!!!!!!!!
 * 
 */
if (!class_exists('DateInterval')) {
	class DateInterval
	{
		public $y; // year
		public $m; // month
		public $d; // day
		public $h; // hour
		public $i; // minute
		public $s; // second
		public $invert;
		public $days;
		
		// this extra public member is to determine if we're using our own backcompat class or the core PHP 5.3 class
		public static $jcal = true;
		
		private static $_badformat = 'Unknown or bad format(%s)';
		
		/**
		 * takes a given string based on the ISO 8601 duration specification
		 * see http://en.wikipedia.org/wiki/Iso8601#Durations
		 * 
		 * @param string $interval_spec
		 */
		public function __construct($interval_spec) {
			// this always starts as 0
			$this->invert = 0;
			// make sure we have a string, it's not empty, and in the correct format
			if (!is_string($interval_spec) || empty($interval_spec) || 0 !== strpos($interval_spec, 'P')) {
				throw new Exception(sprintf($this->_badformat, $interval_spec));
			}
			// remove the opening 'P'
			$int = ltrim($interval_spec, 'P');
			// check again for empty
			if (empty($int)) {
				throw new Exception(sprintf($this->_badformat, $interval_spec));
			}
			// we need a time 'T' flag to determine if 'M' means Month or Minute
			$time = false;
			// loop the string until it's empty, parsing along the way
			while (!empty($int)) {
				// as soon as we hit the time flag, we want to set our marker
				if (0 === strpos($int, 'T')) {
					$time = true;
					$int = ltrim($int, 'T');
				}
				// pull the number off the end
				$regex = '/^(?P<digit>\d+)(?P<type>[YMWDHS])/';
				// TODO: check if DateInterval proper supports the decimal part of the spec (we don't use it)
				preg_match($regex, $int, $matches);
				// check our matches :)
				if (empty($matches) || !array_key_exists('digit', $matches) || !array_key_exists('type', $matches)) {
					throw new Exception(sprintf($this->_badformat, $interval_spec));
				}
				// convert our value to an integer
				$num = intval($matches['digit']);
				// set the correct variable in our class
				switch ($matches['type']) {
					case 'Y': $this->y = $num; break;
					case 'D': $this->d = $num; break;
					case 'H': $this->h = $num; break;
					case 'S': $this->s = $num; break;
					case 'M': if ($time) $this->i = $num; else $this->s = $num; break;
					case 'W': throw new Exception('Legacy compatibility class does not support W in interval period (' . $interval_spec . ')');
					default: throw new Exception(sprintf($this->_badformat, $interval_spec));
				}
				// remove this period
				$int = preg_replace($regex, '', $int);
			}
			
		}
		
		/**
		 * static method to return a new instance based on the given time string
		 * this string is generally in the format "X days Y hours" etc
		 * this is not nearly as feature-complete as the PHP 5.3 class!
		 * 
		 * @param unknown_type $time
		 */
		public static function createFromDateString($time) {
			// for inverting
			$invert = 0;
			// get the "now" timestamp
			$now = time();
			// get the "time" timestamp
			$stamp = strtotime($time, $now);
			// uh-oh
			if (false === $stamp) {
				throw new Exception(sprintf($this->_badformat, $time));
			}
			// set the invert flag based on the stamp differences
			if ($stamp < $now) $invert = 1;
			// get the difference between the 2 stamps
			$diff = $stamp - $now;
			// make sure we're always positive
			if (0 < $diff) $diff *= -1;
			// for our purposes, we're just going to create a format in seconds & let DateTime figure it out
			$formatstring = 'PT' . $diff . 'S';
			return new DateInterval($formatstring);
		}
		
		/**
		 * formats the interval
		 * 
		 * @param unknown_type $format
		 */
		public function format($format) {
			// remove real percents first
			$realpercent = '_____REAL_PERCENT_____';
			$format = str_replace('%%', $realpercent, $format);
			// handle modifiers
			$format = str_replace('%r', ($this->invert ? '-' : ''), $format);
			$format = str_replace('%R', ($this->invert ? '-' : '+'), $format);
			// handle parts
			foreach (array('s','i','h','m','d','y') as $part) {
				$format = str_replace('%' . $part, $this->{$part}, $format);
				$format = str_replace('%' . strtoupper($part), str_pad($this->{$part}, 2, '0', STR_PAD_LEFT), $format);
			}
			// TODO: %a (total days)
			// replace the percents
			$format = str_replace($realpercent, '%', $format);
			
			return $format;
		}
	}
}

/**
 * JCalPro DateInterval class
 * 
 */
class JCalInterval extends DateInterval
{
	//public $spec; 
	
	public function __construct($interval_spec) {
		//$this->spec = $interval_spec;
		parent::__construct($interval_spec);
	}
	
	/**
	 * override the static method so this returns a JCalInterval 
	 * 
	 * @return JCalInterval
	 * 
	 * @throws JCalIntervalException
	 */
	public static function createFromDateString($time) {
		$interval = DateInterval::createFromDateString($time);
		return new JCalInterval($interval->format('PT%sS'));
	}
	
	/**
	 * returns a JCalInterval from another interval (either JCalInterval or DateInterval)
	 * 
	 * @param unknown_type $interval
	 * 
	 * @return JCalInterval
	 * 
	 * @throws JCalIntervalException
	 */
	public static function createFromInterval($interval) {
		// no need to do any conversion
		if ($interval instanceof JCalInterval) return $interval;
		// interval MUST be an object!!!
		if (!is_object($interval)) throw new JCalIntervalException('Interval is not an object');
		// create an interval spec from this DateInterval
		$interval_spec = self::_intervalToString($interval);
		// create a new JCalInterval based on the interval spec
		return new JCalInterval($interval_spec);
	}
	
	/**
	 * private method to convert an interval to a string
	 * 
	 * @param mixed $interval
	 * 
	 * @return string
	 * 
	 * @throws JCalIntervalException
	 */
	private static function _intervalToString($interval) {
		$string = 'P';
		if ($interval->y) $string .= $interval->y . 'Y';
		if ($interval->m) $string .= $interval->m . 'M';
		if ($interval->d) $string .= $interval->d . 'D';
		if ($interval->h + $interval->i + $interval->s) $string .= 'T';
		if ($interval->h) $string .= $interval->h . 'H';
		if ($interval->i) $string .= $interval->i . 'M';
		if ($interval->s) $string .= $interval->s . 'S';
		if ('P' == $string) throw new JCalIntervalException('Could not convert interval to string');
		return $string;
	}
	
	/**
	 * toString method
	 * 
	 */
	public function __toString() {
		return self::_intervalToString($this);
	}
}
