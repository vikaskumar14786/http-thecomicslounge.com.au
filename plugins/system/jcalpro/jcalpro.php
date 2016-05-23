<?php
/**
 * @package		JCalPro
 * @subpackage	plg_system_jcalpro

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

jimport('joomla.filesystem.file');
jimport('joomla.plugin.plugin');
// we HAVE to force-load the helper here to prevent fatal errors!
$helper = JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/jcalpro.php';
if (JFile::exists($helper)) require_once $helper;


// it is impossible to use old plugin events to handle configuration changes anymore
// in 3.x there must be an observer attached to the configuration table
// in order to trigger these events
jimport('joomla.observer.interface');
if (function_exists('interface_exists') && is_callable('interface_exists') && interface_exists('JObserverInterface'))
{
	class PlgSystemJCalProObserver implements JObserverInterface
	{
		protected $app;
		protected $observableObject;
		
		public function __construct(JObservableInterface $observableObject)
		{
			$observableObject->attachObserver($this);
			$this->observableObject = $observableObject;
			$this->app = JFactory::getApplication();
		}
		
		public static function createObserver(JObservableInterface $observableObject, $params = array())
		{
			$observer = new self($observableObject);
			return $observer;
		}
		
		public function onAfterStore(&$result)
		{
			if ($result)
			{
				$dispatcher = JDispatcher::getInstance();
				$dispatcher->trigger('onConfigurationAfterSave', array('com_config.component', $this->observableObject));
			}
		}
	}
}

class plgSystemJCalPro extends JPlugin
{
	public static $com = 'com_jcalpro';
	
	private static $_run;
	
	private static $_debug;
	
	private $updatebaseurl = 'http://anything-digital.com/update/jcalpro/standard/';
	
	private $updatestableurl = 'http://anything-digital.com/update/jcalpro/standard/list_stable.xml';
	private $updatebleedingurl = 'http://anything-digital.com/update/jcalpro/standard/list.xml';
	
	/**
	 * Constructor
	 * 
	 * @param unknown_type $subject
	 * @param unknown_type $config
	 */
	public function __construct(&$subject, $config) {
		// if something happens & the helper class can't be found, we don't want a fatal error here
		if (class_exists('JCalPro')) {
			JCalPro::language(JCalPro::COM, JPATH_ADMINISTRATOR);
			self::$_run = true;
		}
		else {
			$this->loadLanguage();
			JFactory::getApplication()->enqueueMessage(JText::_('PLG_SYSTEM_JCALPRO_COMPONENT_NOT_INSTALLED'));
			self::$_run = false;
		}
		parent::__construct($subject, $config);
	}
	
	public function loadLanguage($extension = 'plg_system_jcalpro.sys', $basePath = JPATH_ADMINISTRATOR) {
		parent::loadLanguage($extension, $basePath);
	}
	
	public function onAfterInitialise() {
		if (!self::$_run) return;
		// attaches the observers to the mapper, if applicable
		$this->attachObservers();
		// fix post-install issues with updateservers
		$this->fixUpdateServers();
		// for debugging only :)
		if (defined('JDEBUG') && JDEBUG && $this->params->get('debug', true)) {
			try {
				jimport('jcaldate.date');
				$time = new JCalDate();
				$this->loadLanguage();
				self::$_debug  = JText::sprintf('PLG_SYSTEM_JCALPRO_DEBUG_TIMES', $time->toSql(), $time->toJoomla()->toSql(), $time->timezone(), $time->toUser()->toSql(), $time->timezone());
			}
			catch (Exception $e) {
				return;
			}
		}
	}
	
	protected function fixUpdateServers()
	{
		$session = JFactory::getSession();
		if ($session->get('jcalpro.update.updateservers'))
		{
			$component = JComponentHelper::getComponent(JCalPro::COM);
			$table = JTable::getInstance('Extension');
			$table->load($component->id);
			$this->saveConfigForm($table) && $table->check() && $table->store();
			$session->clear('jcalpro.update.updateservers');
		}
	}
	
	/**
	 * onAfterDispatch
	 * 
	 * handles flair after dispatch
	 */
	public function onAfterDispatch() {
		if (!self::$_run) return;
		$app    = JFactory::getApplication();
		$option = $app->input->get('option', '', 'cmd');
		$view   = $app->input->get('view', '', 'cmd');
		if (!empty(self::$_debug) && 'component' != $app->input->get('tmpl')) {
			$app->enqueueMessage(self::$_debug);
		}
		// handle some extras in admin
		if ($app->isAdmin()) {
			JCalPro::registerHelper('url');
			// we want to add some extras to com_categories
			if ('com_categories' == $option && JCalPro::COM == $app->input->get('extension', '', 'cmd') && class_exists('JCalPro')) {
				// UPDATE: don't do this in edit layout in 3.0+
				if (!(JCalPro::version()->isCompatible('3.0') && 'edit' == $app->input->get('layout'))) {
					// add submenu to categories
					JLoader::register('JCalProView', JPATH_ADMINISTRATOR . '/components/' . JCalPro::COM . '/libraries/views/baseview.php');
					$comView = new JCalProView();
					$comView->addMenuBar();
					// add script to inject extra columns into the categories list table
					JText::script('COM_JCALPRO_TOTAL_EVENTS');
					JText::script('COM_JCALPRO_UPCOMING_EVENTS');
					JFactory::getDocument()->addScript(JCalProHelperUrl::media() . '/js/jcalpro.js');
					JFactory::getDocument()->addScript(JCalProHelperUrl::media() . '/js/categories.js');
				}
			}
			
			// add styles to config
			if ('com_config' == $option && 'component' == $view && JCalPro::COM == $app->input->get('component', '', 'cmd')) {
				JFactory::getDocument()->addStyleSheet(JCalProHelperUrl::media() . '/css/config.css');
			}
		}
	}
	
	/**
	 * onAfterRender
	 * 
	 * used mainly for debugging purposes. if debug is causing issues, push this plugin to the end of the plugin stack in admin
	 * 
	 * TODO integrate time debugging into this instead
	 * 
	 */
	public function onAfterRender() {
		if (!self::$_run) return;
		$app = JFactory::getApplication();
		$tag = '</body>';
		// check conditions for debugging main component
		// TODO: do we have to check if we're the homepage?
		if (JCalPro::COM !== $app->input->get('option')) {
			return;
		}
		// check for debug mode
		if (!(defined('JDEBUG') && JDEBUG && $this->params->get('debug', true))) {
			return;
		}
		// get the debug data
		$debug = JCalPro::debugger();
		// bail if there's no data
		if (empty($debug)) {
			return;
		}
		// get the body
		$buffer = JResponse::getBody();
		// get the last position of this tag
		$tagpos = strrpos($buffer, $tag);
		// bail if there's no tag
		if (false === $tagpos) {
			return;
		}
		// import the filter
		JCalPro::registerHelper('filter');
		// start building the sliders
		$slider = array(JHtml::_('sliders.start', 'jcldebug'));
		$i = 0;
		foreach ($debug as $key => $value) {
			$slider[] = JHtml::_('sliders.panel', JCalProHelperFilter::escape($key), 'debug-' . $i++);
			$slider[] = JCalPro::debug($value, 'return');
		}
		$slider[] = JHtml::_('sliders.end');
		// BUGFIX: don't use JString::str_ireplace to replace tag, causes too much memory use
		// set body
		JResponse::setBody(substr_replace($buffer, implode("", $slider), $tagpos, 0));
		JCalPro::debugged();
	}
	
	/**
	 * onGetIcons
	 * 
	 * @param string $context
	 * @return array
	 */
	public function onGetIcons($context) {
		if (!self::$_run) return;
		if ('mod_quickicon' == $context && class_exists('JCalPro') && $this->params->get('quickicon', true)) {
			// get our helper
			JCalPro::registerHelper('url');
			// link to the main page
			return array(array(
				'link' => JCalProHelperUrl::_()
			,	'text' => JText::_(JCalPro::COM)
			,	'access' => JCalPro::version()->isCompatible('3.0') ? JFactory::getUser()->authorise('core.manage', JCalPro::COM) : array('core.manage', JCalPro::COM)
			));
		}
		return array();
	}
	
	/**
	 * Joomla! 2.5 configuration save
	 * 
	 * @param type $context
	 * @param type $table
	 * @return boolean
	 */
	public function onConfigurationAfterSave($context, $table)
	{
		if (!self::$_run)
		{
			return true;
		}
		$this->saveConfigForm($table);
	}
	
	protected function saveConfigForm($table)
	{
		$params = is_object($table->params) ? $table->params : json_decode($table->params);
		$value  = (is_object($params) && property_exists($params, 'update_server')) ? $params->update_server : $this->updatestableurl;
		switch ($value)
		{
			case $this->updatebleedingurl:
				$value = $this->updatebleedingurl;
				break;
			case $this->updatestableurl:
			default:
				$value = $this->updatestableurl;
				break;
		}
		$db = JFactory::getDbo();
		try
		{
			$db->setQuery($db->getQuery(true)
				->update('#__update_sites')
				->set($db->quoteName('location') . ' = ' . $db->quote($value))
				->set($db->quoteName('enabled') . ' = 1')
				->set($db->quoteName('last_check_timestamp') . ' = 0')
				->where($db->quoteName('name') . ' = ' . $db->quote('jcalpro'))
			)->query();
		}
		catch (Exception $e)
		{
			$this->app->enqueueMessage($e->getMessage());
			return false;
		}
		return true;
	}
	
	/**
	 * Handle update headers
	 * 
	 * @param type $url
	 * @param type $headers
	 */
	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{
		// only operate on our urls
		if (0 !== strpos($url, $this->updatebaseurl))
		{
			return true;
		}
		// fetch credentials from extension parameters
		JLoader::import('joomla.application.component.helper');
		$component   = JComponentHelper::getComponent('com_jcalpro');
		$credentials = array(
			'id'     => trim($component->params->get('update_credentials_access', ''))
		,	'secret' => trim($component->params->get('update_credentials_secret', ''))
		);
		
		// allow the url to override the provided credentials
		if (false !== strpos($url, '?') && false !== strpos($url, 'access_key=') && false !== strpos($url, 'secret_key='))
		{
			list($base, $query) = explode('?', $url, 2);
			$params = array();
			parse_str($query, $params);
			if (array_key_exists('access_key', $params) && !empty($params['access_key']))
			{
				$credentials['id'] = $params['access_key'];
				unset($params['access_key']);
			}
			if (array_key_exists('secret_key', $params) && !empty($params['secret_key']))
			{
				$credentials['secret'] = $params['secret_key'];
				unset($params['secret_key']);
			}
			$url = $base . '?' . http_build_query($params);
		}
		
		// set the headers necessary to authenticate
		$headers['X-download-auth-ts']    = time();
		$headers['X-download-auth-id']    = $credentials['id'];
		$headers['X-download-auth-token'] = sha1($headers['X-download-auth-ts'] . mt_rand() . $credentials['secret'] . $url);
		$headers['X-download-auth-sig']   = sha1($credentials['id'] . $headers['X-download-auth-token'] . $credentials['secret'] . $headers['X-download-auth-ts'] . 'jcalpro');
		
		return true;
	}
	
	public function attachObservers()
	{
		if (!class_exists('PlgSystemJCalProObserver'))
		{
			return;
		}
		jimport('joomla.observer.mapper');
		JObserverMapper::addObserverClassToClass('PlgSystemJCalProObserver', 'JTableExtension');
	}
	
	public function getDebug() {
		return self::$_debug;
	}
}
