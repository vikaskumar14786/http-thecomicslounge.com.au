<?php
/**
 * Generic tools that all models use
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2013 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Generic tools that all models use
 * This code used to be in models/parent.php
 *
 * @package     Joomla
 * @subpackage  Fabrik.helpers
 * @since       3.0
 */

class FabrikWorker
{
	/**
	 * Fabrik database objects
	 *
	 * @var  array
	 */
	public static $database = null;

	/**
	 * Fabrik db connections
	 *
	 * @var  array
	 */
	public static $connection = null;

	/**
	 * Plugin manager
	 *
	 * @var  object
	 */
	public static $pluginManager = null;

	/**
	 * Strtotime final date format
	 *
	 * @var  string
	 */
	static protected $finalformat = null;

	/**
	 * Image file extensions
	 *
	 * @var  string
	 */
	protected $image_extensions_eregi = 'bmp|gif|jpg|jpeg|png';

	/**
	 * Audio file extensions
	 *
	 * @var  string
	 */
	protected $audio_extensions_eregi = 'mp3';

	/**
	 * Audio mime types
	 *
	 * @var array
	 */
	static protected $audio_mime_types = array('mp3' => 'audio/x-mpeg', 'm4a' => 'audio/x-m4a');

	/**
	 * Video mime types
	 *
	 * @var  array
	 */
	static protected $video_mime_types = array('mp4' => 'video/mp4', 'm4v' => 'video/x-m4v', 'mov' => 'video/quicktime');

	/**
	 * Document mime types
	 *
	 * @var  array
	 */
	static protected $doc_mime_types = array('pdf' => 'application/pdf', 'epub' => 'document/x-epub');
	
	/**
	 * Valid view types, for sanity checking inputs, used by isViewType()
	 */
	static protected $viewTypes = array(
		'article',
		'cron',
		'csv',
		'details',
		'element',
		'form',
		'list',
		'package',
		'visualization'
	);

	/**
	 * Returns true if $view is a valid view type
	 *
	 * @param   string  $view  View type
	 *
	 * @return	bool
	 */
	
	public static function isViewType($view)
	{
		return in_array($view, self::$viewTypes);
	}
	
	/**
	 * Returns true if $file has an image extension type
	 *
	 * @param   string  $file  Filename
	 *
	 * @deprecated - doesn't seem to be used
	 *
	 * @return	bool
	 */

	public static function isImageExtension($file)
	{
		$path_parts = pathinfo($file);

		return preg_match('/' . self::$image_extensions_eregi . '/i', $path_parts['extension']);
	}

	/**
	 * Returns true if $file has an image extension type
	 *
	 * @param   string  $file  Filename
	 *
	 * @deprecated - doesn't seem to be used
	 *
	 * @return	bool
	 */

	public static function isAudioExtension($file)
	{
		$path_parts = pathinfo($file);

		return preg_match('/' . self::$audio_extensions_eregi . '/i', $path_parts['extension']);
	}

	/**
	 * Get Audoio Mime type
	 *
	 * @param   string  $file  Filename
	 *
	 * @deprecated - doesn't seem to be used
	 *
	 * @return  bool
	 */

	public static function getAudioMimeType($file)
	{
		$path_parts = pathinfo($file);

		if (array_key_exists($path_parts['extension'], self::$audio_mime_types))
		{
			return self::$audio_mime_types[$path_parts['extension']];
		}

		return false;
	}

	/**
	 * Get Video Mime type
	 *
	 * @param   string  $file  Filename
	 *
	 * @deprecated - doesn't seem to be used
	 *
	 * @return  bool
	 */

	public static function getVideoMimeType($file)
	{
		$path_parts = pathinfo($file);

		if (array_key_exists($path_parts['extension'], self::$video_mime_types))
		{
			return self::$video_mime_types[$path_parts['extension']];
		}

		return false;
	}

	/**
	 * Get Podcast Mime type
	 *
	 * @param   string  $file  Filename
	 *
	 * @deprecated - doesn't seem to be used
	 *
	 * @return  bool
	 */

	public static function getPodcastMimeType($file)
	{
		$path_parts = pathinfo($file);

		if (array_key_exists($path_parts['extension'], self::$video_mime_types))
		{
			return self::$video_mime_types[$path_parts['extension']];
		}
		elseif (array_key_exists($path_parts['extension'], self::$audio_mime_types))
		{
			return self::$audio_mime_types[$path_parts['extension']];
		}
		elseif (array_key_exists($path_parts['extension'], self::$doc_mime_types))
		{
			return self::$doc_mime_types[$path_parts['extension']];
		}

		return false;
	}

	/**
	 * Format a string to datetime
	 *
	 * http://fr.php.net/strftime
	 * (use as strptime)
	 *
	 * @param   string  $date    String date to format
	 * @param   string  $format  Date format strftime format
	 *
	 * @return	array	date info
	 */

	public static function strToDateTime($date, $format)
	{
		$weekdays = array('Sun' => '0', 'Mon' => '1', 'Tue' => '2', 'Wed' => '3', 'Thu' => '4', 'Fri' => '5', 'Sat' => '6');
		$months = array('Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04', 'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08',
			'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12');

		if (!($date = self::str2Time($date, $format)))
		{
			return;
		}

		$months = array(FText::_('January'), FText::_('February'), FText::_('March'), FText::_('April'), FText::_('May'), FText::_('June'),
			FText::_('July'), FText::_('August'), FText::_('September'), FText::_('October'), FText::_('November'), FText::_('December'));
		$shortMonths = array(FText::_('Jan'), FText::_('Feb'), FText::_('Mar'), FText::_('Apr'), FText::_('May'), FText::_('Jun'), FText::_('Jul'),
			FText::_('Aug'), FText::_('Sept'), FText::_('Oct'), FText::_('Nov'), FText::_('Dec'));

		/*$$ rob set day default to 1, so that if you have a date format string of %m-%Y the day is set to the first day of the month
		 * and not the last day of the previous month (which is what a 0 here would represent)
		 */
		$dateTime = array('sec' => 0, 'min' => 0, 'hour' => 0, 'day' => 1, 'mon' => 0, 'year' => 0, 'timestamp' => 0);

		foreach ($date as $key => $val)
		{
			switch ($key)
			{
				case 'd':
				case 'e':
				case 'j':
					$dateTime['day'] = intval($val);
					break;
				case 'D':
					$dateTime['day'] = intval($weekdays[$val]);
					break;
				case 'm':
				case 'n':
					$dateTime['mon'] = intval($val);
					break;
				case 'b':
					$dateTime['mon'] = $shortMonths[$val] + 1;
					break;
				case 'Y':
					$dateTime['year'] = intval($val);
					break;
				case 'y':
					$dateTime['year'] = intval($val) + 2000;
					break;
				case 'G':
				case 'g':
				case 'H':
				case 'h':
					$dateTime['hour'] = intval($val);
					break;
				case 'M':
					$dateTime['min'] = intval($val);
					break;
				case 'i':
					$dateTime['min'] = intval($val);
					break;
				case 's':
				case 'S':
					$dateTime['sec'] = intval($val);
					break;
			}
		}

		$dateTime['timestamp'] = mktime($dateTime['hour'], $dateTime['min'], $dateTime['sec'], $dateTime['mon'], $dateTime['day'], $dateTime['year']);

		return $dateTime;
	}

	/**
	 * Check for, and convert, any 'special' formats for strtotime, like 'yesterday', etc.
	 *
	 * @param   string  $date  Date to check
	 * @param   bool    $gmt   Set date to universal time?
	 *
	 * @return	string	date
	 */

	public static function specialStrToMySQL($date, $gmt = true)
	{
		/**
		 * $$$ hugh - if date is empty, just return today's date
		 */
		if (empty($date))
		{
			$d = JFactory::getDate();
			$date = $d->toSql(!$gmt);

			return $date;
		}

		/**
		 * lets check if we have some special text as per :
		 * http://php.net/strtotime - this means we can use "+2 week" as a url filter
		 * do this before we urldecode the date otherwise the + is replaced with ' ';
		 */

		$matches = array();
		$matches2 = array();
		$matches3 = array();

		// E.g. now
		preg_match("/[now|ago|midnight|yesterday|today]/i", $date, $matches);

		// E.g. +2 Week
		preg_match("/[+|-][0-9]* (week\b|year\b|day\b|month\b)/i", $date, $matches2);

		// E.g. next Wednesday
		preg_match("/[next|last]* (\monday\b|tuesday\b|wednesday\b|thursday\b|friday\b|saturday\b|sunday\b)/i", $date, $matches3);
		$matches = array_merge($matches, $matches2, $matches3);

		if (!empty($matches))
		{
			$d = JFactory::getDate($date);
			$date = $d->toSql(!$gmt);
		}

		return $date;
	}

	/**
	 * String to time
	 *
	 * @param   string  $date    Date representation
	 * @param   string  $format  Date format
	 *
	 * @return	array	date bits keyed on date representations e.g.  m/d/Y
	 */

	public static function str2Time($date, $format)
	{
		/**
		 * lets check if we have some special text as per :
		 * http://php.net/strtotime - this means we can use "+2 week" as a url filter
		 * do this before we urldecode the date otherwise the + is replaced with ' ';
		 */
		$matches = array();
		$matches2 = array();
		$matches3 = array();

		// E.g. now
		preg_match("/[now|ago|midnight|yesterday|today]/i", $date, $matches);

		// E.g. +2 Week
		preg_match("/[+|-][0-9]* (week\b|year\b|day\b|month\b)/i", $date, $matches2);

		// E.g. next Wednesday
		preg_match("/[next|last]* (\monday\b|tuesday\b|wednesday\b|thursday\b|friday\b|saturday\b|sunday\b)/i", $date, $matches3);
		$matches = array_merge($matches, $matches2, $matches3);

		if (!empty($matches))
		{
			$d = JFactory::getDate($date);
			$date = $d->format($format);
		}

		/* $$$ - hugh : urldecode (useful when ajax calls, may need better fix)
		 * as per http://fabrikar.com/forums/showthread.php?p=43314#post43314
		 */
		$date = urldecode($date);

		// Strip any textual date representations from the string
		$days = array('%A', '%a');

		foreach ($days as $day)
		{
			if (strstr($format, $day))
			{
				$format = str_replace($day, '', $format);
				$date = self::stripDay($date, $day == '%a' ? true : false);
			}
		}

		$months = array('%B', '%b', '%h');

		foreach ($months as $month)
		{
			if (strstr($format, $month))
			{
				$format = str_replace($month, '%m', $format);
				$date = self::monthToInt($date, $month == '%B' ? false : true);
			}
		}
		// @TODO: some of these aren't right for strftime
		self::$finalformat = $format;
		$search = array('%d', '%e', '%D', '%j', '%m', '%b', '%Y', '%y', '%g', '%H', '%h', '%i', '%s', '%S', '%M');

		$replace = array('(\d{2})', '(\d{1,2})', '(\w{3})', '(\d{1,2})', '(\d{2})', '(\w{3})', '(\d{4})', '(\d{2})', '(\d{1,2})', '(\d{2})',
			'(\d{2})', '(\d{2})', '(\d{2})', '(\d{2})', '(\d{2})');

		$pattern = str_replace($search, $replace, $format);

		if (!preg_match("#$pattern#", $date, $matches))
		{
			// Lets allow for partial date formats - e.g. just the date and ignore the time
			$format = explode('%', $format);

			if (empty($format))
			{
				// No format left to test so return false
				return false;
			}

			array_pop($format);
			$format = trim(implode('%', $format));
			self::$finalformat = $format;

			return self::str2Time($date, $format);
		}

		$dp = $matches;

		if (!preg_match_all('#%(\w)#', $format, $matches))
		{
			return false;
		}

		$id = $matches['1'];

		if (count($dp) != count($id) + 1)
		{
			return false;
		}

		$ret = array();

		for ($i = 0, $j = count($id); $i < $j; $i++)
		{
			$ret[$id[$i]] = $dp[$i + 1];
		}

		return $ret;
	}

	/**
	 * Get final date format
	 *
	 * @deprecated - not used?
	 *
	 * @return  string
	 */

	public static function getFinalDateFormat()
	{
		return self::$finalformat;
	}

	/**
	 * Removed day of week name from string
	 *
	 * @param   string  $date  The string date
	 * @param   bool    $abrv  Abbreviated day?
	 *
	 * @return	string	date
	 */

	public static function stripDay($date, $abrv = false)
	{
		if ($abrv)
		{
			$date = str_replace(FText::_('SUN'), '', $date);
			$date = str_replace(FText::_('MON'), '', $date);
			$date = str_replace(FText::_('TUE'), '', $date);
			$date = str_replace(FText::_('WED'), '', $date);
			$date = str_replace(FText::_('THU'), '', $date);
			$date = str_replace(FText::_('FRI'), '', $date);
			$date = str_replace(FText::_('SAT'), '', $date);
		}
		else
		{
			$date = str_replace(FText::_('SUNDAY'), '', $date);
			$date = str_replace(FText::_('MONDAY'), '', $date);
			$date = str_replace(FText::_('TUESDAY'), '', $date);
			$date = str_replace(FText::_('WEDNESDAY'), '', $date);
			$date = str_replace(FText::_('THURSDAY'), '', $date);
			$date = str_replace(FText::_('FRIDAY'), '', $date);
			$date = str_replace(FText::_('SATURDAY'), '', $date);
		}

		return $date;
	}

	/**
	 * Convert a month (could be in any language) into the month number (1 = jan)
	 *
	 * @param   string  $date  Data to convert
	 * @param   bool    $abrv  Is the month is a short or full name version
	 *
	 * @return  string
	 */

	public static function monthToInt($date, $abrv = false)
	{
		if ($abrv)
		{
			$date = str_replace(FText::_('JANUARY_SHORT'), '01', $date);
			$date = str_replace(FText::_('FEBRUARY_SHORT'), '02', $date);
			$date = str_replace(FText::_('MARCH_SHORT'), '03', $date);
			$date = str_replace(FText::_('APRIL_SHORT'), '04', $date);
			$date = str_replace(FText::_('MAY_SHORT'), '05', $date);
			$date = str_replace(FText::_('JUNE_SHORT'), '06', $date);
			$date = str_replace(FText::_('JULY_SHORT'), '07', $date);
			$date = str_replace(FText::_('AUGUST_SHORT'), '08', $date);
			$date = str_replace(FText::_('SEPTEMBER_SHORT'), '09', $date);
			$date = str_replace(FText::_('OCTOBER_SHORT'), 10, $date);
			$date = str_replace(FText::_('NOVEMBER_SHORT'), 11, $date);
			$date = str_replace(FText::_('DECEMBER_SHORT'), 12, $date);
		}
		else
		{
			$date = str_replace(FText::_('JANUARY'), '01', $date);
			$date = str_replace(FText::_('FEBRUARY'), '02', $date);
			$date = str_replace(FText::_('MARCH'), '03', $date);
			$date = str_replace(FText::_('APRIL'), '04', $date);
			$date = str_replace(FText::_('MAY'), '05', $date);
			$date = str_replace(FText::_('JUNE'), '06', $date);
			$date = str_replace(FText::_('JULY'), '07', $date);
			$date = str_replace(FText::_('AUGUST'), '08', $date);
			$date = str_replace(FText::_('SEPTEMBER'), '09', $date);
			$date = str_replace(FText::_('OCTOBER'), 10, $date);
			$date = str_replace(FText::_('NOVEMBER'), 11, $date);
			$date = str_replace(FText::_('DECEMBER'), 12, $date);
		}

		return $date;
	}

	/**
	 * Check a string is not reserved by Fabrik
	 *
	 * @param   string  $str     To check
	 * @param   bool    $strict  Include things like rowid, listid in the reserved words, defaults to true
	 *
	 * @return bool
	 */

	public static function isReserved($str, $strict = true)
	{
		$_reservedWords = array("task", "view", "layout", "option", "formid", "submit", "ul_max_file_size"
				, "ul_file_types", "ul_directory", 'adddropdownvalue', 'adddropdownlabel', 'ul_end_dir');
		/*
		 * $$$ hugh - a little arbitrary, but need to be able to exclude these so people can create lists from things like
		 * log files, which include field names like rowid and itemid.  So when saving an element, we now set strict mode
		 * to false if it's not a new element.
		 */
		$_strictWords = array("listid", 'rowid', 'itemid');

		if ($strict)
		{
			$_reservedWords = array_merge($_reservedWords, $_strictWords);
		}

		if (in_array(JString::strtolower($str), $_reservedWords))
		{
			return true;
		}

		return false;
	}

	/**
	 * Get the crypt object
	 *
	 * @since  3.1
	 *
	 * @return  JCrypt
	 */

	public static function getCrypt()
	{
		jimport('joomla.crypt.crypt');
		jimport('joomla.crypt.key');
		$config = JFactory::getConfig();
		$secret = $config->get('secret', '');

		if (trim($secret) == '')
		{
			throw new RuntimeException('You must supply a secret code in your Joomla configuration.php file');
		}

		$key = new JCryptKey('simple', $secret, $secret);
		$crypt = new JCrypt(new JCryptCipherSimple, $key);

		return $crypt;
	}

	/**
	 * Iterates through string to replace every
	 * {placeholder} with posted data
	 *
	 * @param   mixed   $msg               Text|Array to parse
	 * @param   array   $searchData        Data to search for placeholders (default $_REQUEST)
	 * @param   bool    $keepPlaceholders  If no data found for the place holder do we keep the {...} string in the message
	 * @param   bool    $addslashes        Add slashed to the text?
	 * @param   object  $theirUser         User to use in replaceWithUserData (defaults to logged in user)
	 *
	 * @return  string  parsed message
	 */

	public function parseMessageForPlaceHolder($msg, $searchData = null, $keepPlaceholders = true, $addslashes = false, $theirUser = null)
	{
		$returnType = is_array($msg) ? 'array' : 'string';
		$msgs = (array) $msg;

		foreach ($msgs as &$msg)
		{
			$this->parseAddSlases = $addslashes;

			if (!($msg == '' || is_array($msg) || JString::strpos($msg, '{') === false))
			{
				$msg = str_replace(array('%7B', '%7D'), array('{', '}'), $msg);

				if (is_object($searchData))
				{
					$searchData = JArrayHelper::fromObject($searchData);
				}
				// Merge in request and specified search data
				$f = JFilterInput::getInstance();
				$post = $f->clean($_REQUEST, 'array');
				$this->_searchData = is_null($searchData) ? $post : array_merge($post, $searchData);

				// Enable users to use placeholder to insert session token
				$this->_searchData['JSession::getFormToken'] = JSession::getFormToken();

				// Replace with the user's data
				$msg = self::replaceWithUserData($msg);

				if (!is_null($theirUser))
				{
					// Replace with a specified user's data
					$msg = self::replaceWithUserData($msg, $theirUser, 'your');
				}

				$msg = self::replaceWithGlobals($msg);
				$msg = preg_replace("/{}/", "", $msg);

				// Replace {element name} with form data
				$msg = preg_replace_callback("/{[^}\s]+}/i", array($this, 'replaceWithFormData'), $msg);

				if (!$keepPlaceholders)
				{
					$msg = preg_replace("/{[^}\s]+}/i", '', $msg);
				}
			}
		}

		return $returnType === 'array' ? $msgs : FArrayHelper::getValue($msgs, 0, '');
	}

	/**
	 * Replace {varname} with request data (called from J content plugin)
	 *
	 * @param   string  &$msg  String to parse
	 *
	 * @return  void
	 */

	public function replaceRequest(&$msg)
	{
		$f = JFilterInput::getInstance();
		$request = $f->clean($_REQUEST, 'array');

		foreach ($request as $key => $val)
		{
			if (is_string($val))
			{
				// $$$ hugh - escape the key so preg_replace won't puke if key contains /
				$key = str_replace('/', '\/', $key);
				$msg = preg_replace("/\{$key\}/", $val, $msg);
			}
		}
	}

	/**
	 * Called from parseMessageForPlaceHolder to iterate through string to replace
	 * {placeholder} with user ($my) data
	 * AND
	 * {$their->var->email} placeholders
	 *
	 * @param   string  $msg     Message to parse
	 * @param   object  $user    Joomla user object
	 * @param   string  $prefix  Search string to look for e.g. 'my' to look for {$my->id}
	 *
	 * @return	string	parsed message
	 */

	public static function replaceWithUserData($msg, $user = null, $prefix = 'my')
	{
		$app = JFactory::getApplication();

		if (is_null($user))
		{
			$user = JFactory::getUser();
		}

		if (is_object($user))
		{
			foreach ($user as $key => $val)
			{
				if (substr($key, 0, 1) != '_')
				{
					if (!is_object($val) && !is_array($val))
					{
						$msg = str_replace('{$' . $prefix . '->' . $key . '}', $val, $msg);
						$msg = str_replace('{$' . $prefix . '-&gt;' . $key . '}', $val, $msg);
					}
					elseif (is_array($val))
					{
						$msg = str_replace('{$' . $prefix . '->' . $key . '}', implode(',', $val), $msg);
						$msg = str_replace('{$' . $prefix . '-&gt;' . $key . '}', implode(',', $val), $msg);
					}
				}
			}
		}
		/*
		 *  $$$rob parse another users data into the string:
		 *  format: is {$their->var->email} where var is the $app->input var to search for
		 *  e.g url - index.php?owner=62 with placeholder {$their->owner->id}
		 *  var should be an integer corresponding to the user id to load
		 */
		$matches = array();
		preg_match('/{\$their-\>(.*?)}/', $msg, $matches);

		foreach ($matches as $match)
		{
			$bits = explode('->', str_replace(array('{', '}'), '', $match));
			$userid = $app->input->getInt(FArrayHelper::getValue($bits, 1));

			if ($userid !== 0)
			{
				$user = JFactory::getUser($userid);
				$val = $user->get(FArrayHelper::getValue($bits, 2));
				$msg = str_replace($match, $val, $msg);
			}
		}

		return $msg;
	}

	/**
	 * Called from parseMessageForPlaceHolder to iterate through string to replace
	 * {placeholder} with global data
	 *
	 * @param   string  $msg  Message to parse
	 *
	 * @return	string	parsed message
	 */

	public static function replaceWithGlobals($msg)
	{
		$app = JFactory::getApplication();
		$Itemid = self::itemId();
		$config = JFactory::getConfig();
		$msg = str_replace('{$mosConfig_absolute_path}', JPATH_SITE, $msg);
		$msg = str_replace('{$mosConfig_live_site}', COM_FABRIK_LIVESITE, $msg);
		$msg = str_replace('{$mosConfig_offset}', $config->get('offset'), $msg);
		$msg = str_replace('{$Itemid}', $Itemid, $msg);
		$msg = str_replace('{$mosConfig_sitename}', $config->get('sitename'), $msg);
		$msg = str_replace('{$mosConfig_mailfrom}', $config->get('mailfrom'), $msg);
		$msg = str_replace('{$mosConfig_secret}', $config->get('secret'), $msg);
		$msg = str_replace('{where_i_came_from}', $app->input->server->get('HTTP_REFERER', '', 'string'), $msg);

		foreach ($_SERVER as $key => $val)
		{
			if (!is_object($val) && !is_array($val))
			{
				$msg = str_replace('{$_SERVER->' . $key . '}', $val, $msg);
				$msg = str_replace('{$_SERVER-&gt;' . $key . '}', $val, $msg);
			}
		}

		$lang = JFactory::getLanguage()->getTag();
		$lang = str_replace('-', '_', $lang);
		$msg = str_replace('{lang}', $lang, $msg);
		$session = JFactory::getSession();
		$token = $session->get('session.token');
		$msg = str_replace('{session.token}', $token, $msg);

		return $msg;
	}

	/**
	 * Called from parseMessageForPlaceHolder to iterate through string to replace
	 * {placeholder} with posted data
	 *
	 * @param   string  $matches  Placeholder e.g. {placeholder}
	 *
	 * @return	string	posted data that corresponds with placeholder
	 */

	protected function replaceWithFormData($matches)
	{
		// Merge any join data key val pairs down into the main data array
		$joins = FArrayHelper::getValue($this->_searchData, 'join', array());

		foreach ($joins as $k => $data)
		{
			foreach ($data as $k => $v)
			{
				/*
				 * Only replace if we haven't explicitly set the key in _searchData.
				 * Otherwise, calc element in repeat group uses all repeating groups values rather than the
				 * current one that the plugin sets when it fire its Ajax request.
				 */
				if (!array_key_exists($k, $this->_searchData))
				{
					$this->_searchData[$k] = $v;
				}
			}
		}

		$match = $matches[0];
		$orig = $match;

		// Strip the {}
		$match = JString::substr($match, 1, JString::strlen($match) - 2);

		/* $$$ hugh - added dbprefix substitution
		 * Not 100% if we should do this on $match before copying to $orig, but for now doing it
		 * after, so we don't potentially disclose dbprefix if no substitution found.
		 */
		$config = JFactory::getConfig();
		$prefix = $config->get('dbprefix');
		$match = str_replace('#__', $prefix, $match);

		// $$$ rob test this format searchvalue||defaultsearchvalue
		$bits = explode('||', $match);

		if (count($bits) == 2)
		{
			$match = self::parseMessageForPlaceHolder('{' . $bits[0] . '}', $this->_searchData, false);
			$default = $bits[1];

			if ($match == '')
			{
				// 	$$$ rob seems like bits[1] in fabrik plugin is already matched so return that rather then reparsing
				// $match = self::parseMessageForPlaceHolder("{".$bits[1]."}", $this->_searchData);
				return $bits[1] !== '' ? $bits[1] : $orig;
			}
			else
			{
				return $match !== '' ? $match : $orig;
			}
		}

		$match = preg_replace("/ /", "_", $match);

		if (!strstr($match, '.'))
		{
			// For some reason array_key_exists wasn't working for nested arrays??
			$aKeys = array_keys($this->_searchData);

			// Remove the table prefix from the post key
			$aPrefixFields = array();

			for ($i = 0; $i < count($aKeys); $i++)
			{
				$aKeyParts = explode('___', $aKeys[$i]);

				if (count($aKeyParts) == 2)
				{
					$tablePrefix = array_shift($aKeyParts);
					$field = array_pop($aKeyParts);
					$aPrefixFields[$field] = $tablePrefix;
				}
			}

			if (array_key_exists($match, $aPrefixFields))
			{
				$match = $aPrefixFields[$match] . '___' . $match;
			}

			// Test to see if the made match is in the post key arrays
			$found = in_array($match, $aKeys, true);

			if ($found)
			{
				// Get the post data
				$match = $this->_searchData[$match];

				if (is_array($match))
				{
					$newmatch = '';

					// Deal with radio boxes etc. inside repeat groups
					foreach ($match as $m)
					{
						if (is_array($m))
						{
							$newmatch .= ',' . implode(',', $m);
						}
						else
						{
							$newmatch .= ',' . $m;
						}
					}

					$match = JString::ltrim($newmatch, ',');
				}
			}
			else
			{
				$match = '';
			}
		}
		else
		{
			// Could be looking for URL field type e.g. for $_POST[url][link] the match text will be url.link
			$aMatch = explode(".", $match);
			$aPost = $this->_searchData;

			foreach ($aMatch as $sPossibleArrayKey)
			{
				if (is_array($aPost))
				{
					if (!isset($aPost[$sPossibleArrayKey]))
					{
						return $orig;
					}
					else
					{
						$aPost = $aPost[$sPossibleArrayKey];
					}
				}
			}

			$match = $aPost;
		}

		if ($this->parseAddSlases)
		{
			$match = htmlspecialchars($match, ENT_QUOTES, 'UTF-8');
		}

		return $found ? $match : $orig;
	}

	/**
	 * Internal function to recursive scan directories
	 *
	 * @param   string  $imagePath      Image path
	 * @param   string  $folderPath     Path to scan
	 * @param   string  &$folders       Root path of this folder
	 * @param   array   &$images        Value array of all existing folders
	 * @param   array   $aFolderFilter  Value array of all existing images
	 * @param   bool    $makeOptions    Make options out for the results
	 *
	 * @return  void
	 */

	public static function readImages($imagePath, $folderPath, &$folders, &$images, $aFolderFilter, $makeOptions = true)
	{
		$imgFiles = self::fabrikReadDirectory($imagePath, '.', false, false, $aFolderFilter);

		foreach ($imgFiles as $file)
		{
			$ff_ = $folderPath . $file . '/';
			$ff = $folderPath . $file;
			$i_f = $imagePath . '/' . $file;

			if (is_dir($i_f) && $file != 'CVS' && $file != '.svn')
			{
				if (!in_array($file, $aFolderFilter))
				{
					$folders[] = JHTML::_('select.option', $ff_);
					self::readImages($i_f, $ff_, $folders, $images, $aFolderFilter);
				}
			}
			elseif (preg_match('/bmp|gif|jpg|png/i', $file) && is_file($i_f))
			{
				// Leading / we don't need
				$imageFile = JString::substr($ff, 1);
				$images[$folderPath][] = $makeOptions ? JHTML::_('select.option', $imageFile, $file) : $file;
			}
		}
	}

	/**
	 * Utility function to read the files in a directory
	 *
	 * @param   string  $path           The file system path
	 * @param   string  $filter         A filter for the names
	 * @param   bool    $recurse        Recurse search into sub-directories
	 * @param   bool    $fullpath       True if to prepend the full path to the file name
	 * @param   array   $aFolderFilter  Folder names not to recurse into
	 * @param   bool    $foldersOnly    Return a list of folders only (true)
	 *
	 * @return	array	of file/folder names
	 */

	public static function fabrikReadDirectory($path, $filter = '.', $recurse = false, $fullpath = false, $aFolderFilter = array(),
		$foldersOnly = false)
	{
		$arr = array();

		if (!@is_dir($path))
		{
			return $arr;
		}

		$handle = opendir($path);

		while ($file = readdir($handle))
		{
			$dir = JPath::clean($path . '/' . $file);
			$isDir = is_dir($dir);

			if ($file != "." && $file != "..")
			{
				if (preg_match("/$filter/", $file))
				{
					if (($isDir && $foldersOnly) || !$foldersOnly)
					{
						if ($fullpath)
						{
							$arr[] = trim(JPath::clean($path . '/' . $file));
						}
						else
						{
							$arr[] = trim($file);
						}
					}
				}

				$goDown = true;

				if ($recurse && $isDir)
				{
					foreach ($aFolderFilter as $sFolderFilter)
					{
						if (strstr($dir, $sFolderFilter))
						{
							$goDown = false;
						}
					}

					if ($goDown)
					{
						$arr2 = self::fabrikReadDirectory($dir, $filter, $recurse, $fullpath, $aFolderFilter, $foldersOnly);
						$arrDiff = array_diff($arr, $arr2);
						$arr = array_merge($arrDiff);
					}
				}
			}
		}

		closedir($handle);
		asort($arr);

		return $arr;
	}

	/**
	 * Joomfish translations don't seem to work when you do an ajax call
	 * it seems to load the geographical location language rather than the selected lang
	 * so for ajax calls that need to use jf translated text we need to get the current lang and
	 * send it to the js code which will then append the lang=XX to the ajax querystring
	 *
	 * @since 2.0.5
	 *
	 * @return	string	first two letters of lang code - e.g. nl from 'nl-NL'
	 */

	public static function getJoomfishLang()
	{
		$lang = JFactory::getLanguage();

		return array_shift(explode('-', $lang->getTag()));
	}

	/**
	 * Get the content filter used both in form and admin pages for content filter
	 * takes values from J content filtering options
	 *
	 * @return   array  (bool should the filter be used, object the filter to use)
	 */

	public static function getContentFilter()
	{
		$dofilter = false;
		$filter = false;

		// Filter settings
		jimport('joomla.application.component.helper');

		// Get Config and Filters in Joomla 2.5
		$config = JComponentHelper::getParams('com_config');
		$filters = $config->get('filters');

		// If no filter data found, get from com_content (Joomla 1.6/1.7 sites)
		if (empty($filters))
		{
			$contentParams = JComponentHelper::getParams('com_content');
			$filters = $contentParams->get('filters');
		}

		$user = JFactory::getUser();
		$userGroups = JAccess::getGroupsByUser($user->get('id'));

		$blackListTags = array();
		$blackListAttributes = array();

		$whiteListTags = array();
		$whiteListAttributes = array();

		$noHtml = false;
		$whiteList = false;
		$blackList = false;
		$unfiltered = false;

		// Cycle through each of the user groups the user is in.
		// Remember they are include in the Public group as well.
		foreach ($userGroups AS $groupId)
		{
			// May have added a group by not saved the filters.
			if (!isset($filters->$groupId))
			{
				continue;
			}

			// Each group the user is in could have different filtering properties.
			$filterData = $filters->$groupId;
			$filterType = JString::strtoupper($filterData->filter_type);

			if ($filterType == 'NH')
			{
				// Maximum HTML filtering.
				$noHtml = true;
			}
			elseif ($filterType == 'NONE')
			{
				// No HTML filtering.
				$unfiltered = true;
			}
			else
			{
				// Black or white list.
				// Preprocess the tags and attributes.
				$tags = explode(',', $filterData->filter_tags);
				$attributes = explode(',', $filterData->filter_attributes);
				$tempTags = array();
				$tempAttributes = array();

				foreach ($tags as $tag)
				{
					$tag = trim($tag);

					if ($tag)
					{
						$tempTags[] = $tag;
					}
				}

				foreach ($attributes as $attribute)
				{
					$attribute = trim($attribute);

					if ($attribute)
					{
						$tempAttributes[] = $attribute;
					}
				}

				// Collect the black or white list tags and attributes.
				// Each list is cumulative.
				if ($filterType == 'BL')
				{
					$blackList = true;
					$blackListTags = array_merge($blackListTags, $tempTags);
					$blackListAttributes = array_merge($blackListAttributes, $tempAttributes);
				}
				elseif ($filterType == 'WL')
				{
					$whiteList = true;
					$whiteListTags = array_merge($whiteListTags, $tempTags);
					$whiteListAttributes = array_merge($whiteListAttributes, $tempAttributes);
				}
			}
		}

		// Remove duplicates before processing (because the black list uses both sets of arrays).
		$blackListTags = array_unique($blackListTags);
		$blackListAttributes = array_unique($blackListAttributes);
		$whiteListTags = array_unique($whiteListTags);
		$whiteListAttributes = array_unique($whiteListAttributes);

		// Unfiltered assumes first priority.
		if ($unfiltered)
		{
			$dofilter = false;

			// Don't apply filtering.
		}
		else
		{
			$dofilter = true;

			// Black lists take second precedence.
			if ($blackList)
			{
				// Remove the white-listed attributes from the black-list.
				$tags = array_diff($blackListTags, $whiteListTags);
				$filter = JFilterInput::getInstance($tags, array_diff($blackListAttributes, $whiteListAttributes), 1, 1);
			}
			// White lists take third precedence.
			elseif ($whiteList)
			{
				// Turn off xss auto clean
				$filter = JFilterInput::getInstance($whiteListTags, $whiteListAttributes, 0, 0, 0);
			}
			// No HTML takes last place.
			else
			{
				$filter = JFilterInput::getInstance();
			}
		}

		return array($dofilter, $filter);
	}

	/**
	 * Clear PHP errors prior to running eval'd code
	 *
	 * @return  void
	 */

	public static function clearEval()
	{
		/**
		 * "Clear" PHP's errors.  NOTE that error_get_last() will still return non-null after this
		 * if there were any errors, but $error['message'] will be empty.  See comment in logEval()
		 * below for details.
		 */
		@trigger_error("");
	}

	/**
	 * Raise a J Error notice if the eval'd result is false and there is a error
	 *
	 * @param   mixed   $val  Evaluated result
	 * @param   string  $msg  Error message, should contain %s as we sprintf in the error_get_last()'s message property
	 *
	 * @return  void
	 */

	public static function logEval($val, $msg)
	{
		if ($val !== false)
		{
			return;
		}

		$error = error_get_last();
		/**
		 * $$$ hugh - added check for 'message' being empty, so we can do ..
		 * @trigger_error('');
		 * ... prior to eval'ing code if we want to "clear" anything pitched prior
		 * to the eval.  For instance, in the PHP validation plugin.  If we don't "clear"
		 * the errors before running the eval'd validation code, we end up reporting any
		 * warnings or notices pitched in our code prior to the validation running, which
		 * can be REALLY confusing.  After a trigger_error(), error_get_last() won't return null,
		 * but 'message' will be empty.
		 */
		if (is_null($error) || empty($error['message']))
		{
			// No error set (eval could have actually returned false as a correct value)
			return;
		}

		$enqMsgType = 'error';
		$indentHTML = '<br/>&nbsp;&nbsp;&nbsp;&nbsp;Debug:&nbsp;';
		$errString = FText::_('COM_FABRIK_EVAL_ERROR_USER_WARNING');

		// Give a technical error message to the developer
		if (version_compare(phpversion(), '5.2.0', '>=') && $error && is_array($error))
		{
			$errString .= $indentHTML . sprintf($msg, $error['message']);
		}
		else
		{
			$errString .= $indentHTML . sprintf($msg, "unknown error - php version < 5.2.0");
		}

		self::logError($errString, $enqMsgType);
	}

	/**
	 * Raise a J Error notice if in dev mode or log a J error otherwise
	 *
	 * @param   string  $errString  Message to display / log
	 * @param   string  $msgType    Joomla enqueueMessage message type e.g. 'error', 'warning' etc.
	 *
	 * @return  void
	 */

	public static function logError($errString, $msgType)
	{
		if (FabrikHelperHTML::isDebug())
		{
			$app = JFactory::getApplication();
			$app->enqueueMessage($errString, $msgType);
		}
		else
		{
			switch ($msgType)
			{
				case 'message':
					$priority = JLog::INFO;
					break;
				case 'warning':
					$priority = JLog::WARNING;
					break;
				case 'error':
				default:
					$priority = JLog::ERROR;
					break;
			}

			JLog::add($errString, $priority, 'com_fabrik');
		}
	}

	/**
	 * Log  to table jos_fabrik_logs
	 *
	 * @param   string  $type        E.g. 'fabrik.fileupload.download'
	 * @param   mixed   $msg         Array/object/string
	 * @param   bool    $jsonEncode  Should we json encode the message?
	 *
	 * @return  void
	 */

	public static function log($type, $msg, $jsonEncode = true)
	{
		if ($jsonEncode)
		{
			$msg = json_encode($msg);
		}

		$log = FabTable::getInstance('log', 'FabrikTable');
		$log->message_type = $type;
		$log->message = $msg;
		$log->store();
	}

	/**
	 * Get a database object
	 *
	 * Returns the global {@link JDatabase} object, only creating it
	 * if it doesn't already exist.
	 *
	 * @param   bool   $loadJoomlaDb  Force (if true) the loading of the main J database,
	 * needed in admin to connect to J db whilst still using fab db drivers "{package}" replacement text
	 *
	 * @param   mixed  $cnnId         If null then loads the fabrik default connection, if an int then loads the specified connection by its id
	 *
	 * @return  JDatabase object
	 */

	public static function getDbo($loadJoomlaDb = false, $cnnId = null)
	{
		$sig = (int) $loadJoomlaDb . '.' . $cnnId;

		if (!self::$database)
		{
			self::$database = array();
		}

		if (!array_key_exists($sig, self::$database))
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fabrik/tables');
			$conf = JFactory::getConfig();

			if (!$loadJoomlaDb)
			{
				$cnModel = JModelLegacy::getInstance('Connection', 'FabrikFEModel');
				$cn = $cnModel->getConnection($cnnId);
				$host = $cn->host;
				$user = $cn->user;
				$password = $cn->password;
				$database = $cn->database;
			}
			else
			{
				$host = $conf->get('host');
				$user = $conf->get('user');
				$password = $conf->get('password');
				$database = $conf->get('db');
			}

			$dbprefix = $conf->get('dbprefix');
			$driver = $conf->get('dbtype');

			// Test for swapping db table names
			$driver .= '_fab';
			$debug = $conf->get('debug');
			$options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database,
				'prefix' => $dbprefix);

			$version = new JVersion;
			self::$database[$sig] = $version->RELEASE > 2.5 ? JDatabaseDriver::getInstance($options) : JDatabase::getInstance($options);

			/*
			 *  $$$ hugh - testing doing bigSelects stuff here
			 *  Reason being, some folk on shared hosting plans with very restrictive MySQL
			 *  setups are hitting the 'big selects' problem on Fabrik internal queries, not
			 *  just on their List specific queries.  So we need to apply 'big selects' to our
			 *  default connection as well, essentially enabling it for ALL queries we do.
			 */
			$fbConfig = JComponentHelper::getParams('com_fabrik');
			
			if ($fbConfig->get('enable_big_selects', 0) == '1')
			{
				$fabrikDb = self::$database[$sig];
				
				/**
				 * Use of OPTION in SET deprecated from MySQL 5.1. onward
				 * http://www.fabrikar.com/forums/index.php?threads/enable-big-selects-error.39463/#post-198293
				 * NOTE - technically, using verison_compare on MySQL version could fail, if it's a "gamma"
				 * release, which PHP desn't grok!
				 */
				
				if (version_compare($fabrikDb->getVersion(), '5.1.0', '>='))
				{
					$fabrikDb->setQuery("SET SQL_BIG_SELECTS=1, GROUP_CONCAT_MAX_LEN=10240");
				}
				else
				{
					$fabrikDb->setQuery("SET OPTION SQL_BIG_SELECTS=1, GROUP_CONCAT_MAX_LEN=10240");
				}
				
				$fabrikDb->execute();
			}
		}

		return self::$database[$sig];
	}

	/**
	 * Helper function get get a connection
	 *
	 * @param   mixed  $item  A list table or connection id
	 *
	 * @since 3.0b
	 *
	 * @return object  connection
	 */

	public static function getConnection($item = null)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$jform = $input->get('jform', array(), 'array');

		if (is_object($item))
		{
			$item = is_null($item->connection_id) ? FArrayHelper::getValue($jform, 'connection_id', -1) : $item->connection_id;
		}

		$connId = (int) $item;

		if (!self::$connection)
		{
			self::$connection = array();
		}

		if (!array_key_exists($connId, self::$connection))
		{
			$connectionModel = JModelLegacy::getInstance('connection', 'FabrikFEModel');
			$connectionModel->setId($connId);

			if ($connId === -1)
			{
				// -1 for creating new table
				$connectionModel->loadDefaultConnection();
				$connectionModel->setId($connectionModel->getConnection()->id);
			}

			$connection = $connectionModel->getConnection();
			self::$connection[$connId] = $connectionModel;
		}

		return self::$connection[$connId];
	}

	/**
	 * Get the plugin manager
	 *
	 * @since	3.0b
	 *
	 * @return	object	plugin manager
	 */

	public static function getPluginManager()
	{
		if (!self::$pluginManager)
		{
			self::$pluginManager = JModelLegacy::getInstance('Pluginmanager', 'FabrikFEModel');
		}

		return self::$pluginManager;
	}

	/**
	 * Takes a string which may or may not be json and returns either string/array/object
	 * will also turn valGROUPSPLITTERval2 to array
	 *
	 * @param   string  $data     Json encoded string
	 * @param   bool    $toArray  Force data to be an array
	 *
	 * @return  mixed data
	 */

	public static function JSONtoData($data, $toArray = false)
	{
		if (is_string($data))
		{
			if (!strstr($data, '{'))
			{
				// Was messing up date rendering @ http://www.podion.eu/dev2/index.php/2011-12-19-10-33-59/actueel
				// return $toArray ? (array) $data : $data;
			}

			// Repeat elements are concatenated with the GROUPSPLITTER - convert to json string  before continuing.
			if (strstr($data, GROUPSPLITTER))
			{
				$data = json_encode(explode(GROUPSPLITTER, $data));
			}
			/* half hearted attempt to see if string is actually json or not.
			 * issue was that if you try to decode '000123' its turned into '123'
			 */
			if (strstr($data, '{') || strstr($data, '['))
			{
				$json = json_decode($data);

				// Only works in PHP5.3
				// $data = (json_last_error() == JSON_ERROR_NONE) ? $json : $data;
				if (is_null($json))
				{
					/*
					 * if coming back from a failed validation - the json string may have been htmlspecialchars_encoded in
					 * the form model getGroupView method
					 */
					$json = json_decode(stripslashes(htmlspecialchars_decode($data, ENT_QUOTES)));
				}

				$data = is_null($json) ? $data : $json;
			}
		}

		$data = $toArray ? (array) $data : $data;

		return $data;
	}

	/**
	 * Test if a string is a compatible date
	 *
	 * @param   string  $d  Date to test
	 *
	 * @return	bool
	 */

	public static function isDate($d)
	{
		$db = self::getDbo();
		$aNullDates = array('0000-00-000000-00-00', '0000-00-00 00:00:00', '0000-00-00', '', $db->getNullDate());

		// Catch for ','
		if (strlen($d) < 2)
		{
			return false;
		}

		if (in_array($d, $aNullDates))
		{
			return false;
		}

		try
		{
			$dt = new DateTime($d);
		}
		catch (Exception $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * See if data is JSON or not.
	 *
	 * @param   mixed  $data  Date to test
	 *
	 * @since	3.0.6
	 *
	 * @return bool
	 */

	public static function isJSON($data)
	{
		if (!is_string($data))
		{
			return false;
		}

		if (is_numeric($data))
		{
			return false;
		}

		return json_decode($data) !== null;
	}

	/**
	 * Is the email really an email (more strict than JMailHelper::isEmailAddress())
	 *
	 * @param   string  $email  Email address
	 *
	 * @since 3.0.4
	 *
	 * @return bool
	 */

	public static function isEmail($email)
	{
		$conf = JFactory::getConfig();
		$mail = JFactory::getMailer();
		$mailer = $conf->get('mailer');

		if ($mailer === 'mail')
		{
			// Sendmail and Joomla isEmailAddress don't use the same conditions
			return (JMailHelper::isEmailAddress($email) && PHPMailer::ValidateAddress($email));
		}

		return JMailHelper::isEmailAddress($email);
	}

	/**
	 * Get a JS go back action e.g 'onclick="history.back()"
	 *
	 * @return string
	 */

	public static function goBackAction()
	{
		jimport('joomla.environment.browser');
		$uri = JUri::getInstance();

		if ($uri->getScheme() === 'https')
		{
			$gobackaction = 'onclick="parent.location=\'' . FArrayHelper::getValue($_SERVER, 'HTTP_REFERER') . '\'"';
		}
		else
		{
			$gobackaction = 'onclick=\'history.back();\'';
		}

		return $gobackaction;
	}

	/**
	 * Attempt to find the active menu item id - Only for front end
	 *
	 *  - First checked $listId for menu items
	 *  - Then checks if itemId in $input
	 *  - Finally checked active menu item
	 *
	 * @param   int  $listId  List id to attempt to get the menu item id for the list.
	 *
	 * @return mixed NULL if nothing found, int if menu item found
	 */

	public static function itemId($listId = null)
	{
		$app = JFactory::getApplication();

		if (!$app->isAdmin())
		{
			// Attempt to get Itemid from possible list menu item.
			if (!is_null($listId))
			{
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select('m.id AS itemId')->from('#__extensions AS e')
				->leftJoin('#__menu AS m ON m.component_id = e.extension_id')
				->where('e.name = "fabrik" and e.type = "component" and m.link LIKE "%listid=' . $listId . '%"');
				$db->setQuery($query);

				if ($itemId = $db->loadResult())
				{
					return $itemId;
				}
			}

			$itemId = (int) $app->input->getInt('itemId');

			if ($itemId !== 0)
			{
				return $itemId;
			}

			$menus = $app->getMenu();
			$menu = $menus->getActive();

			if (is_object($menu))
			{
				return $menu->id;
			}
		}

		return null;
	}

	/**
	 * Attempt to get a variable first from the menu params (if they exists) if not from request
	 *
	 * @param   string  $name      Param name
	 * @param   mixed   $val       Default
	 * @param   bool    $mambot    If set to true menu params ignored
	 * @param   string  $priority  Defaults that menu priorities override request - set to 'request' to inverse this priority
	 *
	 * @return  string
	 */

	public static function getMenuOrRequestVar($name, $val = '', $mambot = false, $priority = 'menu')
	{
		$app = JFactory::getApplication();
		$input = $app->input;

		if ($priority === 'menu')
		{
			$val = $input->get($name, $val, 'string');

			if (!$app->isAdmin())
			{
				$menus = $app->getMenu();
				$menu = $menus->getActive();

				// If there is a menu item available AND the view is not rendered in a content plugin
				if (is_object($menu) && !$mambot)
				{
					$val = $menu->params->get($name, $val);
				}
			}
		}
		else
		{
			if (!$app->isAdmin())
			{
				$menus = $app->getMenu();
				$menu = $menus->getActive();

				// If there is a menu item available AND the view is not rendered in a content plugin
				if (is_object($menu) && !$mambot)
				{
					$val = $menu->params->get($name, $val);
				}
			}

			$val = $input->get($name, $val, 'string');
		}

		return $val;
	}

	/**
	 * Access control function for determining if the user can perform
	 * a designated function on a specific row
	 *
	 * @param   object  $params  Item params to test
	 * @param   object  $row     Data
	 * @param   string  $col     Access control setting to compare against
	 *
	 * @return	mixed	- if ACL setting defined here return bool, otherwise return -1 to continue with default acl setting
	 */

	public static function canUserDo($params, $row, $col)
	{
		if (!is_null($row))
		{
			$app = JFactory::getApplication();
			$input = $app->input;
			$user = JFactory::getUser();
			$usercol = $params->get($col, '');

			if ($usercol != '')
			{
				$usercol = FabrikString::safeColNameToArrayKey($usercol);

				if (!array_key_exists($usercol, $row))
				{
					return false;
				}
				else
				{
					if (array_key_exists($usercol . '_raw', $row))
					{
						$usercol .= '_raw';
					}

					$myid = $user->get('id');

					// -1 for menu items that link to their own records
					$usercol_val = is_array($row) ? $row[$usercol] : $row->$usercol;

					// User element stores as object
					if (is_object($usercol_val))
					{
						$usercol_val = JArrayHelper::fromObject($usercol_val);
					}

					// Could be coming back from a failed validation in which case val might be an array
					if (is_array($usercol_val))
					{
						$usercol_val = array_shift($usercol_val);
					}

					if (empty($usercol_val) && empty($myid))
					{
						return false;
					}

					if (intVal($usercol_val) === intVal($myid) || $input->get('rowid') == -1)
					{
						return true;
					}
				}
			}
		}

		return -1;
	}

	/**
	 * Can Fabrik render PDF - required the DOMPDF library to be installed in Joomla libraries folder
	 *
	 * @throws RuntimeException
	 *
	 * @return bool
	 */

	public static function canPdf()
	{
		$file = JPATH_LIBRARIES . '/dompdf/dompdf_config.inc.php';

		if (!JFile::exists($file))
		{
			throw new RuntimeException(FText::_('COM_FABRIK_NOTICE_DOMPDF_NOT_FOUND'));
		}

		return true;
	}

	/**
	 * Get a cache handler
	 * $$$ hugh - added $listModel arg, needed so we can see if they have set "Disable Caching" on the List
	 *
	 * @param   object  $listModel  List Model
	 *
	 * @since   3.0.7
	 *
	 * @return  JCache
	 */

	public static function getCache($listModel = null)
	{
		$app = JFactory::getApplication();
		$package = $app->getUserState('com_fabrik.package', 'fabrik');
		$time = ((float) 2 * 60 * 60);
		$base = JPATH_BASE . '/cache/';
		$opts = array('defaultgroup' => 'com_' . $package, 'cachebase' => $base, 'lifetime' => $time, 'language' => 'en-GB', 'storage' => 'file');
		$cache = JCache::getInstance('callback', $opts);
		$config = JFactory::getConfig();
		$doCache = $config->get('caching', 0) > 0 ? true : false;

		if ($doCache && $listModel !== null)
		{
			$doCache = $listModel->getParams()->get('list_disable_caching', '0') == '0';
		}

		$cache->setCaching($doCache);

		return $cache;
	}

	/**
	 * Get the default values for a given JForm
	 *
	 * @param   string  $form  Form name e.g. list, form etc.
	 *
	 * @since   3.0.7
	 *
	 * @return  array  key field name, value default value
	 */

	public static function formDefaults($form)
	{
		JForm::addFormPath(JPATH_COMPONENT . '/models/forms');
		JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');
		$form = JForm::getInstance('com_fabrik.' . $form, $form, array('control' => '', 'load_data' => true));
		$fs = $form->getFieldset();
		$json = array('params' => array());

		foreach ($fs as $name => $field)
		{
			if (substr($name, 0, 7) === 'params_')
			{
				$name = str_replace('params_', '', $name);
				$json['params'][$name] = $field->value;
			}
			else
			{
				$json[$name] = $field->value;
			}
		}

		return $json;
	}

	/**
	 * Are we in J3 or using a bootstrap tmpl
	 *
	 * @since   3.1
	 *
	 * @return  bool
	 */

	public static function j3()
	{
		$app = JFactory::getApplication();
		$version = new JVersion;

		// Only use template test for testing in 2.5 with my temp J bootstrap template.
		$tpl = $app->getTemplate();

		return ($tpl === 'bootstrap' || $tpl === 'fabrik4' || $version->RELEASE > 2.5);
	}
	
	/**
	 * Are we in a form process task
	 * 
	 * @since 3.2
	 * 
	 * @return bool
	 */
	
	public static function inFormProcess()
	{
		$app = JFactory::getApplication();
		return $app->input->get('task') == 'form.process' || ($app->isAdmin() && $app->input->get('task') == 'process');
	}
}
