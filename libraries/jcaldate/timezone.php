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

jimport('jcaldate.exceptions.timezone');

/**
 * JCalPro DateTimeZone class
 * 
 */
class JCalTimeZone extends DateTimeZone
{
	/**
	 * An array of offsets and time zone strings representing the available
	 * options from Joomla! CMS 1.5 and below.
	 * 
	 * This list was originally borrowed from the 11.1 JDate class
	 * However, we are not extending JDate so we moved it here.
	 * 
	 * Thus far the only time this has been used is when dealing with a 
	 * manually-adjusted configuration, so this should never come up in practice
	 * 
	 * Also of importance - some of these zones have been altered to better ones,
	 * like "UTC" in place of "Europe/London"
	 * 
	 * Finally, if anyone actually ever needs UTC-12, you are out of luck!
	 * 
	 */
	protected static $offsets = array(
		'-12'   => 'Etc/GMT-12'
	,	'-11'   => 'Pacific/Midway'
	,	'-10'   => 'Pacific/Honolulu'
	,	'-9.5'  => 'Pacific/Marquesas'
	,	'-9'    => 'America/Anchorage' // US/Alaska
	,	'-8'    => 'America/Los_Angeles' // US/Pacific
	,	'-7'    => 'America/Denver' // US/Mountain
	,	'-6'    => 'America/Chicago' // US/Central
	,	'-5'    => 'America/New_York' // US/Eastern
	,	'-4.5'  => 'America/Caracas'
	,	'-4'    => 'America/Barbados'
	, '-3.5'  => 'America/St_Johns' // Canada/Newfoundland
	,	'-3'    => 'America/Argentina/Buenos_Aires' // America/Buenos_Aires
	,	'-2'    => 'Atlantic/South_Georgia'
	,	'-1'    => 'Atlantic/Azores'
	,	'0'     => 'UTC'
	,	'1'     => 'Europe/Amsterdam'
	,	'2'     => 'Europe/Istanbul'
	,	'3'     => 'Asia/Riyadh'
	,	'3.5'   => 'Asia/Tehran'
	,	'4'     => 'Asia/Muscat'
	,	'4.5'   => 'Asia/Kabul'
	,	'5'     => 'Asia/Karachi'
	,	'5.5'   => 'Asia/Calcutta'
	,	'5.75'  => 'Asia/Katmandu'
	,	'6'     => 'Asia/Dhaka'
	,	'6.5'   => 'Indian/Cocos'
	,	'7'     => 'Asia/Bangkok'
	,	'8'     => 'Australia/Perth'
	,	'8.75'  => 'Australia/West'
	,	'9'     => 'Asia/Tokyo'
	,	'9.5'   => 'Australia/Adelaide'
	,	'10'    => 'Australia/Brisbane'
	,	'10.5'  => 'Australia/Lord_Howe'
	,	'11'    => 'Pacific/Kosrae'
	,	'11.5'  => 'Pacific/Norfolk'
	,	'12'    => 'Pacific/Auckland'
	,	'12.75' => 'Pacific/Chatham'
	,	'13'    => 'Pacific/Tongatapu'
	,	'14'    => 'Pacific/Kiritimati'
	);
	
	
	/**
	 * An array of offsets and time zone strings representing the available
	 * options from Outlook (or other stupid non-standard apps)
	 */
	protected static $zonemap = array(
		'AUS Central Standard Time' => 'Australia/Darwin'
	,	'AUS Eastern Standard Time' => 'Australia/Sydney'
	,	'Afghanistan Standard Time' => 'Asia/Kabul'
	,	'Alaskan Standard Time' => 'America/Anchorage'
	,	'Arab Standard Time' => 'Asia/Riyadh'
	,	'Arabian Standard Time' => 'Asia/Dubai'
	,	'Arabic Standard Time' => 'Asia/Baghdad'
	,	'Argentina Standard Time' => 'America/Buenos_Aires'
	,	'Atlantic Standard Time' => 'America/Halifax'
	,	'Azerbaijan Standard Time' => 'Asia/Baku'
	,	'Azores Standard Time' => 'Atlantic/Azores'
	,	'Bahia Standard Time' => 'America/Bahia'
	,	'Bangladesh Standard Time' => 'Asia/Dhaka'
	,	'Canada Central Standard Time' => 'America/Regina'
	,	'Cape Verde Standard Time' => 'Atlantic/Cape_Verde'
	,	'Caucasus Standard Time' => 'Asia/Yerevan'
	,	'Cen. Australia Standard Time' => 'Australia/Adelaide'
	,	'Central America Standard Time' => 'America/Guatemala'
	,	'Central Asia Standard Time' => 'Asia/Almaty'
	,	'Central Brazilian Standard Time' => 'America/Cuiaba'
	,	'Central Europe Standard Time' => 'Europe/Budapest'
	,	'Central European Standard Time' => 'Europe/Warsaw'
	,	'Central Pacific Standard Time' => 'Pacific/Guadalcanal'
	,	'Central Standard Time' => 'America/Chicago'
	,	'Central Standard Time (Mexico)' => 'America/Mexico_City'
	,	'China Standard Time' => 'Asia/Shanghai'
	,	'Dateline Standard Time' => 'Etc/GMT+12'
	,	'E. Africa Standard Time' => 'Africa/Nairobi'
	,	'E. Australia Standard Time' => 'Australia/Brisbane'
	,	'E. Europe Standard Time' => 'Asia/Nicosia'
	,	'E. South America Standard Time' => 'America/Sao_Paulo'
	,	'Eastern Standard Time' => 'America/New_York'
	,	'Egypt Standard Time' => 'Africa/Cairo'
	,	'Ekaterinburg Standard Time' => 'Asia/Yekaterinburg'
	
	// BUGFIX (sorta) - when this was introduced we accidentally started masking
	// UTC as "Etc/GMT" so FIX THIS HERE!!! :)
	,	'Etc/GMT' => 'UTC'
	
	,	'FLE Standard Time' => 'Europe/Kiev'
	,	'Fiji Standard Time' => 'Pacific/Fiji'
	,	'GMT Standard Time' => 'Europe/London'
	,	'GTB Standard Time' => 'Europe/Bucharest'
	,	'Georgian Standard Time' => 'Asia/Tbilisi'
	,	'Greenland Standard Time' => 'America/Godthab'
	,	'Hawaiian Standard Time' => 'Pacific/Honolulu'
	,	'India Standard Time' => 'Asia/Calcutta'
	,	'Iran Standard Time' => 'Asia/Tehran'
	,	'Israel Standard Time' => 'Asia/Jerusalem'
	,	'Jordan Standard Time' => 'Asia/Amman'
	,	'Kaliningrad Standard Time' => 'Europe/Kaliningrad'
	,	'Korea Standard Time' => 'Asia/Seoul'
	,	'Magadan Standard Time' => 'Asia/Magadan'
	,	'Mauritius Standard Time' => 'Indian/Mauritius'
	,	'Middle East Standard Time' => 'Asia/Beirut'
	,	'Montevideo Standard Time' => 'America/Montevideo'
	,	'Morocco Standard Time' => 'Africa/Casablanca'
	,	'Mountain Standard Time' => 'America/Denver'
	,	'Mountain Standard Time (Mexico)' => 'America/Chihuahua'
	,	'Myanmar Standard Time' => 'Asia/Rangoon'
	,	'N. Central Asia Standard Time' => 'Asia/Novosibirsk'
	,	'Namibia Standard Time' => 'Africa/Windhoek'
	,	'Nepal Standard Time' => 'Asia/Katmandu'
	,	'New Zealand Standard Time' => 'Pacific/Auckland'
	,	'Newfoundland Standard Time' => 'America/St_Johns'
	,	'North Asia East Standard Time' => 'Asia/Irkutsk'
	,	'North Asia Standard Time' => 'Asia/Krasnoyarsk'
	,	'Pacific SA Standard Time' => 'America/Santiago'
	,	'Pacific Standard Time' => 'America/Los_Angeles'
	,	'Pacific Standard Time (Mexico)' => 'America/Santa_Isabel'
	,	'Pakistan Standard Time' => 'Asia/Karachi'
	,	'Paraguay Standard Time' => 'America/Asuncion'
	,	'Romance Standard Time' => 'Europe/Paris'
	,	'Russian Standard Time' => 'Europe/Moscow'
	,	'SA Eastern Standard Time' => 'America/Cayenne'
	,	'SA Pacific Standard Time' => 'America/Bogota'
	,	'SA Western Standard Time' => 'America/La_Paz'
	,	'SE Asia Standard Time' => 'Asia/Bangkok'
	,	'Samoa Standard Time' => 'Pacific/Apia'
	,	'Singapore Standard Time' => 'Asia/Singapore'
	,	'South Africa Standard Time' => 'Africa/Johannesburg'
	,	'Sri Lanka Standard Time' => 'Asia/Colombo'
	,	'Syria Standard Time' => 'Asia/Damascus'
	,	'Taipei Standard Time' => 'Asia/Taipei'
	,	'Tasmania Standard Time' => 'Australia/Hobart'
	,	'Tokyo Standard Time' => 'Asia/Tokyo'
	,	'Tonga Standard Time' => 'Pacific/Tongatapu'
	,	'Turkey Standard Time' => 'Europe/Istanbul'
	,	'US Eastern Standard Time' => 'America/Indianapolis'
	,	'US Mountain Standard Time' => 'America/Phoenix'
	,	'UTC+12' => 'Etc/GMT-12'
	,	'UTC-02' => 'Etc/GMT+2'
	,	'UTC-11' => 'Etc/GMT+11'
	,	'Ulaanbaatar Standard Time' => 'Asia/Ulaanbaatar'
	,	'Venezuela Standard Time' => 'America/Caracas'
	,	'Vladivostok Standard Time' => 'Asia/Vladivostok'
	,	'W. Australia Standard Time' => 'Australia/Perth'
	,	'W. Central Africa Standard Time' => 'Africa/Lagos'
	,	'W. Europe Standard Time' => 'Europe/Berlin'
	,	'West Asia Standard Time' => 'Asia/Tashkent'
	,	'West Pacific Standard Time' => 'Pacific/Port_Moresby'
	,	'Yakutsk Standard Time' => 'Asia/Yakutsk'
	,	'Z' => 'UTC'
	);
	
	/**
	 * some ics files come with non-standard timezones
	 * 
	 * if it has a UTC declaration in there somewhere, use it
	 * 
	 * @var string
	 */
	const UTC_REGEXP = '/^.*?UTC([\+\-])([0-9]{2})[\.\:]?([0-9]{2}).*?$/Dim';
	/**
	 * @var string
	 */
	const UTC_REGEXP_REPLACEMENT = '$1$2.$3';
	
	/**
	 * constructor
	 * 
	 * @see DateTimeZone
	 * 
	 * @param unknown_type $timezone
	 * 
	 * @throws JCalTimeZoneException
	 */
	public function __construct($timezone) {
		// try to prevent Exceptions if possible
		if ($timezone instanceof DateTimeZone) {
			$timezone = $timezone->getName();
		}
		// old Joomla 1.5 offset
		else if (is_numeric($timezone) && array_key_exists("$timezone", self::$offsets)) {
			$timezone = self::$offsets[$timezone];
		}
		// something wonky (outlook, DateTime::ATOM, etc)
		else if (array_key_exists("$timezone", self::$zonemap)) {
			$timezone = self::$zonemap[$timezone];
		}
		// more protection against non-standard timezones
		else if (preg_match(self::UTC_REGEXP, "$timezone")) {
			$offset = preg_replace('/\.0$/', '', number_format((float) preg_replace(self::UTC_REGEXP, self::UTC_REGEXP_REPLACEMENT, "$timezone"), 1));
			if (array_key_exists("$offset", self::$offsets)) {
				$timezone = self::$offsets[$offset];
			}
		}
		// no timezone - default to UTC
		else if (empty($timezone)) {
			$timezone = 'UTC';
		}
		
		try {
			parent::__construct((string) $timezone);
		}
		catch (Exception $e) {
			throw new JCalTimeZoneException($e->getMessage());
		}
	}

	/**
	 * static method to create a new instance in UTC
	 * 
	 * @return JCalTimeZone object
	 */
	static public function utc() {
		static $utc;
		if (is_null($utc)) {
			$utc = new JCalTimeZone('UTC');
		}
		return $utc;
	}
	
	/**
	 * static method to create a new instance in the Joomla! timezone
	 * 
	 * @return JCalTimeZone object
	 */
	static public function joomla() {
		static $joomla;
		if (is_null($joomla)) {
			$joomla = new JCalTimeZone(JFactory::getConfig()->get('offset'));
		}
		return $joomla;
	}
	
	/**
	 * static method to create a new instance in the user's timezone
	 * 
	 * @return JCalTimeZone object
	 */
	static public function user() {
		static $user;
		if (is_null($user)) {
			$user = new JCalTimeZone(JFactory::getUser()->getParam('timezone', (string) self::joomla()));
		}
		return $user;
	}
	
	/**
	 * converts to the full timezone
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->getName();
	}
}
