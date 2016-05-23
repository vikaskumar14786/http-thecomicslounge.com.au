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

abstract class JCalPro
{
	const COM = 'com_jcalpro';
	
	const RECUR_TYPE_NONE    = 0;
	const RECUR_TYPE_DAILY   = 1;
	const RECUR_TYPE_WEEKLY  = 2;
	const RECUR_TYPE_MONTHLY = 3;
	const RECUR_TYPE_YEARLY  = 4;
	
	const RECUR_END_TYPE_LIMIT = 1;
	const RECUR_END_TYPE_UNTIL = 2;
	
	const JCL_RECUR_NO_LIMIT = 0;
	
	const JCL_SHOW_RECURRING_EVENTS_NONE          = 0;
	const JCL_SHOW_RECURRING_EVENTS_ALL           = 1;
	const JCL_SHOW_RECURRING_EVENTS_FIRST_ONLY    = 2;
	const JCL_SHOW_RECURRING_EVENTS_NEXT_ONLY     = 2;
	const JCL_SHOW_RECURRING_EVENTS_DEFER_TO_JCAL = 3;
	
	const JCL_EVENT_DURATION_NONE = 0;
	const JCL_EVENT_DURATION_DATE = 1;
	const JCL_EVENT_DURATION_ALL  = 2;
	const JCL_EVENT_DURATION_TIME = 3;

	const JCL_EVENT_NO_END_DATE = '0000-00-00 00:00:00';

	const JCL_ALL_DAY_EVENT_END_DATE_LEGACY   = '0000-00-00 00:00:01';
	const JCL_ALL_DAY_EVENT_END_DATE_LEGACY_2 = '9999-12-01 00:00:00';
	const JCL_ALL_DAY_EVENT_END_DATE          = '2038-01-18 00:00:00';
	
	const RANGE_ALL           = 0;
	const RANGE_PAST          = 1;
	const RANGE_UPCOMING      = 2;
	const RANGE_THIS_WEEK     = 3;
	const RANGE_LAST_WEEK     = 4;
	const RANGE_NEXT_WEEK     = 5;
	const RANGE_THIS_MONTH    = 6;
	const RANGE_LAST_MONTH    = 7;
	const RANGE_NEXT_MONTH    = 8;
	const RANGE_TODAY         = 9;
	const RANGE_TOMORROW      = 10;
	const RANGE_YESTERDAY     = 11;
	const RANGE_NEXT_30       = 12;
	const RANGE_LAST_30       = 13;
	const RANGE_ONGOING       = 14;
	const RANGE_NEXT_2_WEEKS  = 15;
	const RANGE_NEXT_3_WEEKS  = 16;
	const RANGE_NEXT_2_MONTHS = 17;
	const RANGE_NEXT_3_MONTHS = 18;
	const RANGE_THIS_YEAR     = 19;
	const RANGE_LAST_YEAR     = 20;
	const RANGE_NEXT_YEAR     = 21;
	const RANGE_PAST_END      = 22;
	const RANGE_UPCOMING_END  = 23;
	
	const LOCATION_FILTER_WITHOUT = 0;  // "Without Location"
	const LOCATION_FILTER_ALL     = -1; // "No Filter"
	const LOCATION_FILTER_WITH    = -2; // "With Location"
	
	static private $_debug;
	
	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param	int		The category ID.
	 * @return	JObject
	 * 
	 * @deprecated
	 */
	public static function getActions($categoryId = 0)
	{
		JCalPro::registerHelper('access');
		return JCalProHelperAccess::getActions($categoryId);
	}
	
	/**
	 * determines if the given (or current) user can add new events
	 * 
	 * @param mixed  $catid
	 * @param mixed  $uid
	 * 
	 * @deprecated
	 */
	public static function canAddEvents($catid = null, $uid = null) {
		JCalPro::registerHelper('access');
		return JCalProHelperAccess::canAddEvents($catid, $uid);
	}
	
	/**
	 * determines if the given (or current) user can moderate events
	 * 
	 * @param mixed  $catid
	 * @param mixed  $uid
	 * 
	 * @deprecated
	 */
	public static function canModerateEvents($catid = null, $uid = null) {
		JCalPro::registerHelper('access');
		return JCalProHelperAccess::canModerateEvents($catid, $uid);
	}
	
	/**
	 * determines if the given (or current) user can feature events
	 * 
	 * @param mixed  $catid
	 * @param mixed  $uid
	 * 
	 * @deprecated
	 */
	public static function canFeatureEvents($catid = null, $uid = null) {
		JCalPro::registerHelper('access');
		return JCalProHelperAccess::canFeatureEvents($catid, $uid);
	}
	
	/**
	 * determines if the given (or current) user can change events states
	 * 
	 * @param mixed  $catid
	 * @param mixed  $uid
	 * 
	 * @deprecated
	 */
	public static function canPublishEvents($catid = null, $uid = null) {
		JCalPro::registerHelper('access');
		return JCalProHelperAccess::canPublishEvents($catid, $uid);
	}
	
	/**
	 * determines if the given (or current) user can delete events
	 * 
	 * @param mixed  $catid
	 * @param mixed  $uid
	 * 
	 * @deprecated
	 */
	public static function canDeleteEvents($catid = null, $uid = null) {
		JCalPro::registerHelper('access');
		return JCalProHelperAccess::canDeleteEvents($catid, $uid);
	}
	
	/**
	 * determines if the given (or current) user can perform the given action(s)
	 * 
	 * @param string $action
	 * @param mixed  $catid
	 * @param mixed  $uid
	 * 
	 * @deprecated
	 */
	public static function canDo($action, $catid = null, $uid = null) {
		JCalPro::registerHelper('access');
		return JCalProHelperAccess::canDo($action, $catid, $uid);
	}
	
	/**
	 * gets a SINGULAR instance of a model
	 * 
	 * @param  string $type
	 * @param  string $prefix
	 * @param  array  $config
	 * @return mixed
	 */
	public static function getModelInstance($type, $prefix = 'JCalProModel', $config = array()) {
		// we only want one instance per key
		static $models;
		// instantiate our static array
		if (!is_array($models)) {
			JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR . '/components/com_jcalpro/libraries/models/basemodel.php');
			$models = array();
		}
		// get our key
		$key = md5($type . $prefix . serialize($config));
		if (!array_key_exists($key, $models)) {
			$models[$key] = JCalProBaseModel::getInstance($type, $prefix, $config);
		}
		// send back our model
		return $models[$key];
	}
	
	/**
	 * static method to get either the component parameters,
	 * or when a key is supplied the value of that key
	 * if val is supplied (with a key) def() is used instead of get()
	 * 
	 * @param  string $key
	 * @param  mixed  $val
	 * @return mixed
	 */
	public static function config($key = null, $val = null) {
		static $params;
		if (!isset($params)) {
			$app = JFactory::getApplication();
			// get the params, either from the helper or the application
			if ($app->isAdmin() || 'com_jcalpro' != $app->input->get('option', '', 'cmd')) {
				$params = JComponentHelper::getParams(JCalPro::COM);
			}
			else {
				$params = $app->getParams();
			}
		}
		// if we don't have a key, return the entire params object
		if (is_null($key) || empty($key)) {
			return $params;
		}
		// return the param value, with optional def
		if (is_null($val)) {
			return $params->get($key);
		}
		return $params->def($key, $val);
	}
	
	/**
	 * loads language files, english first then configured language
	 * 
	 * @param string $name
	 * @param mixed  $client
	 */
	public static function language($name, $client = null) {
		// force client
		if (is_null($client) || !is_string($client)) {
			$client = JPATH_ROOT;
		}
		// we really only want to load once each asset
		static $langs;
		// initialize our static list
		if (!is_array($langs)) {
			$langs = array();
		}
		// create our key
		$key = md5($name . $client);
		// set the list item if it's not been set
		if (!array_key_exists($key, $langs)) {
			// what language should we try?
			$user  = JFactory::getUser();
			$ulang = $user->getParam('language', $user->getParam('admin_language'));
			$lang  = JFactory::getLanguage();
			$langs[$key] = $lang->load($name, $client, $ulang, true) || $lang->load($name, $client, 'en-GB');
		}
		// return the value :)
		return $langs[$key];
	}
	
	/*
	public static function allowAccessBypass() {
		static $allowed;
		if (is_null($allowed)) {
			$allowed = array();
			$token = JFactory::getApplication()->input->get('token', '', 'cmd');
			if (!empty($token)) {
				$db = JFactory::getDbo();
				$db->setQuery((string) $db->getQuery(true)
					->select('id')
					->from('#__users')
					->where('MD5(CONCAT_WS("::", ))' . $db->Quote($token))
				);
			}
		}
		return $allowed;
	}
	*/
	
	public static function loadJsFramework() {
		static $canLoad;
		if (is_null($canLoad)) {
			$document = JFactory::getDocument();
			$canLoad  = method_exists($document, 'addScript');
			if ($canLoad) {
				if (JCalPro::version()->isCompatible('3.0.0')) {
					JHtml::_('bootstrap.framework');
				}
				else {
					JHtml::_('behavior.framework', true);
				}
				// load the jcal framework helper
				JCalPro::registerHelper('url');
				$document->addScript(JCalProHelperUrl::media() . '/js/jcalpro.js');
				// add modal action, if enabled
				if (JCalPro::config('modal_events', 0)) {
					JHtml::_('behavior.modal');
					$document->addScript(JCalProHelperUrl::media() . '/js/modal.js');
				}
			}
		}
		return $canLoad;
	}
	
	
	/**
	 * adds Google maps scripts to the document
	 * 
	 * @param bool  $component
	 * @param array $libraries
	 */
	public static function mapScript($component = true, $libraries = array()) {
		static $mapsLoaded;
		if (is_null($mapsLoaded)) {
			$document = JFactory::getDocument();
			if (JCalPro::loadJsFramework()) {
				JText::script('COM_JCALPRO_ERROR_NO_MAP_ELEMENT');
				JText::script('COM_JCALPRO_GEOCODER_STATUS_INVALID_REQUEST');
				JText::script('COM_JCALPRO_GEOCODER_STATUS_OVER_QUERY_LIMIT');
				JText::script('COM_JCALPRO_GEOCODER_STATUS_REQUEST_DENIED');
				JText::script('COM_JCALPRO_GEOCODER_STATUS_ZERO_RESULTS');
				JText::script('COM_JCALPRO_INITIALIZING_MAP');
				JText::script('COM_JCALPRO_LOCATION_GET_DIRECTIONS_CANNOT_GEOLOCATE');
				JText::script('COM_JCALPRO_LOCATION_GET_DIRECTIONS_FAILED');
				JText::script('COM_JCALPRO_LOCATION_GET_DIRECTIONS_FORM_NOT_FOUND');
				
				$script = '//maps.googleapis.com/maps/api/js?sensor=false';
				if (!empty($libraries)) {
					JCalPro::registerHelper('filter');
					$script .= '&libraries=' . JCalProHelperFilter::escape(implode(',', $libraries));
				}
				$document->addScript($script);
				if ($component) {
					JCalPro::registerHelper('url');
					$document->addScript(JCalProHelperUrl::media() . '/js/jcalpro.js');
					$document->addScript(JCalProHelperUrl::media() . '/js/map.js');
					$document->addStyleSheet(JCalProHelperUrl::media() . '/css/map.css');
					$document->addScriptDeclaration('window.jcl_map_default_zoom_level = ' . max(0, min(18, (int) JCalPro::config('default_zoom', 8))) . ';');
				}
			}
		}
		$mapsLoaded = true;
	}
	/*@/jcal_standard_code@*/
	
	/**
	 * static method to register a helper
	 * 
	 * @param string $helper
	 */
	public static function registerHelper($helper) {
		$pathHelper = 'JCalProHelperPath';
		if (!class_exists($pathHelper)) {
			JLoader::register($pathHelper, JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/path.php');
		}
		if (!class_exists($pathHelper)) {
			JError::raiseError(500, JText::sprintf('COM_JCALPRO_CANNOT_LOAD_HELPER', $pathHelper));
		}
		JLoader::register('JCalProHelper' . ucwords($helper), JCalProHelperPath::helper($helper));
	}
	
	/**
	 * Gets the extension_id for the package
	 * 
	 * @staticvar type $package_id
	 * @return type
	 */
	public static function getPackageId() {
		static $package_id;
		if (is_null($package_id)) {
			$db = JFactory::getDbo();
			$package_id = $db->setQuery($db->getQuery(true)
				->select('extension_id AS id')
				->from('#__extensions')
				->where($db->quoteName('element') . ' = ' . $db->quote('pkg_jcalpro'))
			)->loadResult();
		}
		return $package_id;
	}
	
	/**
	 * static method to keep track of debug info
	 * 
	 * @param  string  $name
	 * @param  mixed   $data
	 * @param  mixed   $context
	 * @return array
	 */
	public static function debugger($name = null, $data = null, $context = null) {
		if (!is_array(self::$_debug)) {
			self::$_debug = array();
		}
		if (!is_string($context)) {
			$context = 'component';
		}
		if (!array_key_exists($context, self::$_debug)) {
			self::$_debug[$context] = array();
		}
		if (!is_null($name)) {
			self::$_debug[$context][$name] = $data;
		}
		return self::$_debug[$context];
	}
	
	public static function debugged() {
		self::$_debug = null;
	}
	
	/**
	 * static method to aide in debugging
	 * 
	 * @param  mixed  $data data to debug
	 * @param  string $fin one of 'echo', 'return', 'die', defaults to 'echo'
	 * @return mixed
	 */
	public static function debug($data, $fin = 'echo') {
		if (!defined('JDEBUG') || !JDEBUG) {
			return '';
		}
		$e       = new Exception;
		$output  = "<pre>\n" . htmlspecialchars(print_r($data, 1)) . "\n\n" . htmlspecialchars($e->getTraceAsString()) . "\n</pre>\n";
		switch ($fin) {
			case 'return':
				return $output;
			case 'die'   :
				echo $output;
				die();
			case 'echo'  :
			default      :
				echo $output;
				return;
		}
	}
	
	/**
	 * gets an instance of JVersion
	 * 
	 */
	public static function version() {
		static $version;
		if (is_null($version)) {
			jimport('cms.version.version');
			$version = new JVersion;
		}
		return $version;
	}
}
