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

JLoader::register('JCalPro', JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('path');
JCalPro::registerHelper('url');

JLoader::register('JCalProBaseController', JCalProHelperPath::library() . '/controllers/basecontroller.php');

/**
 * JCalPro Component Controller
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProController extends JCalProBaseController
{
	function __construct($config = array()) {
		$input  = JFactory::getApplication()->input;
		$view   = $input->get('view', '', 'cmd');
		$task   = $input->get('task', '', 'cmd');
		$layout = $input->get('layout', '', 'cmd');
		$tmpl   = $input->get('tmpl', '', 'cmd');
		// administrator proxying:
		if (('modal' === $layout && in_array($view, array('events', 'event', 'locations', 'location')))
		|| ('location' === $view && 'edit' === $layout)
		|| ('media' === $view)
		|| ('button' === $layout && 'events' === $view)
		) {
			// load the base language
			JCalPro::language('', JPATH_ADMINISTRATOR);
			// set the path
			$config['base_path'] = JPATH_ADMINISTRATOR . '/components/com_jcalpro';
		}
		
		// this is ugly :P
		if ('component' === $tmpl && 'locations' === $view) {
			$input->set('layout', 'modal');
			// load the base language
			JCalPro::language('', JPATH_ADMINISTRATOR);
			// set the path
			$config['base_path'] = JPATH_ADMINISTRATOR . '/components/com_jcalpro';
		}
		
		// debug the user
		JCalPro::debugger('User', JFactory::getUser());
		// construct the parent
		parent::__construct($config);
	}
	
	/**
	 * Method to display a view.
	 *
	 * @param	boolean			If true, the view output will be cached
	 * @param	array			An array of safe url parameters and their variable types, for valid values see JFilterInput::clean().
	 *
	 * @return	JController		This object to support chaining.
	 */
	public function display($cachable = false, $urlparams = false) {
		$profiler = JProfiler::getInstance('Application');
		$profiler->mark('onJCalProTaskDisplayStart');
		$app = JFactory::getApplication();
		// check to see if we're requesting a specific catid
		// if so, then we need to set that catid in the session and redirect if the catid is via _GET
		$catid = $app->input->get->get('filter_catid', '_JCALPRO_');
		if ('_JCALPRO_' !== $catid) {
			$app->setUserState('com_jcalpro.events.filter.catid', $catid);
			$url = JCalProHelperUrl::page(array(), array('filter_catid'));
			$app->redirect($url);
			jexit();
		}
		// default view
		$default_view = 'events';
		// default layout
		$default_layout = 'month';
		// we have an interesting conundrum here
		// what we WANT to do is set a default view in this controller
		// unfortunately, the admin has the option to disable views
		// so, what happens when an admin disables ALL the views?
		// what we're going to do then is check the config
		// if any of the event views are enabled, we'll handle that in the events view
		// but if they are all disabled, we have to redirect somewhere besides this view
		// so go ahead and check all the different views
		$event_views = array('month', 'flat', 'week', 'day');
		$views_disabled = 0;
		foreach ($event_views as $event_view) {
			if (0 == (int) JCalPro::config("{$event_view}_view", 1)) {
				$views_disabled++;
				// since we now know this layout is disabled, do a check on view & layout
				// if both match explicitly, we need to error
				if ('events' == $app->input->get('view', '', 'cmd') && $app->input->get('layout', '', 'cmd') == $event_view) {
					JError::raiseError(403, JText::_('COM_JCALPRO_LAYOUT_DISABLED'));
				}
			}
			// no need to keep checking! :)
			else {
				// set our default layout
				$default_layout = $event_view;
				// quit the check
				break;
			}
		}
		// check to see if all the views are disabled
		// if they are, we need to do something about it!
		if (count($event_views) == $views_disabled) {
			// we're only explicitly setting layout here if we're in events view
			// so blank out the default layout
			$default_layout = '';
			// since the event views have been exhausted,
			// move on to the categories view
			// but we have to make sure it's not disabled too
			if (1 == (int) JCalPro::config("categories_view", 1)) {
				// whew, we're allowed to use this one :)
				$default_view = 'categories';
			}
			// yowch, our last view is search
			// this can be disabled as well :(
			else if (1 == (int) JCalPro::config("search_view", 1)) {
				// whew, we're allowed to use this one :)
				$default_view = 'search';
			}
			// oh no! ALL the views are disabled - throw an error
			else {
				JError::raiseError(403, JText::_('COM_JCALPRO_ALL_VIEWS_DISABLED'));
			}
		}
		// set default view
		$view = $app->input->get('view', $default_view, 'cmd');
		$app->input->set('view', $view);
		// set default layout
		$layout = $app->input->get('layout', $default_layout, 'cmd');
		$app->input->set('layout', $layout);
	
		// guests should not be allowed to see the admin panel
		// redirect them to the login screen and tell them why they are there instead
		if ('admin' == $layout && 'events' == $view && empty(JFactory::getUser()->id)) {
			$this->setRedirect(JRoute::_('index.php?option=com_users&view=login&return=' . base64_encode(JCalProHelperUrl::page()), false));
			return;
		}
		
		// we have a weird disconnect between the "add event" menu item and the add event task
		// if you have a menu item that points to the "add event" it ends up bypassing the "add" task of the event controller
		// so if the layout is "edit" go ahead & do an extra permissions check here
		if ('edit' == $layout && 'event' == $view && !JCalPro::canAddEvents()) {
			$this->setRedirect(JCalProHelperUrl::events('', 'month', false), JText::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
			return;
		}
		
		// check to see if the date is empty - if not, we need to look at the config for archive mode
		// and check the requested date to ensure we're not requesting a date in the past
		// if so, we want to not only 301 redirect but also warn the user that this date is no longer available
		// NOTE: we only do this in events mode and only if in archive mode!
		// NOTE: archive = show past events
		$date = $app->input->get('date', '', 'string');
		if ('events' == $view && !empty($date) && !JCalPro::config('archive')) {
			// register our date helper
			JLoader::register('JCalProHelperDate', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/date.php');
			// we're just gonna reload the date from our helper
			$date  = JCalProHelperDate::getDate()->toDayStart();
			$today = JCalProHelperDate::getToday()->toDayStart();
			// since we have these as DateTime objects, we can just do a raw comparison
			if ($date < $today) {
				// we need to ensure we're not using raw mode - if we are, we need to append it
				// and if we aren't, warn the user
				$format = $app->input->post->get('format', '', 'word');
				$append = array();
				if ('raw' == $format) {
					$append['format'] = 'raw';
				}
				else {
					$app->enqueuemessage(JText::_('COM_JCALPRO_DATE_BEFORE_TODAY'), 'warning');
				}
				// the date is lesser than today, throw a message and redirect
				$url = JCalProHelperUrl::page($append, array('date'));
				$app->redirect($url);
				jexit();
			}
		}
		
		$profiler->mark('onJCalProTaskDisplayEnd');
		// display
		return parent::display($cachable, $urlparams);
	}
	
	/**
	 * returns a single rendered module
	 */
	public function module() {
		// BUGFIX: there are some seriously bad plugins that screw with the output...
		ob_start();
		// input handler
		$input = JFactory::getApplication()->input;
		// check what module we're loading
		// right now, we ONLY support mod_jcalpro_(calendar|locations) explicitly :)
		$type = $input->get('module', 'mod_jcalpro_calendar', 'cmd');
		$id = $input->get('id', 0, 'int');
		$params = $input->get('params', array(), 'array');
		JCalPro::registerHelper('module');
		$module = JCalProHelperModule::render($type, $id, $params);
		// clear the buffer
		ob_end_clean();
		// send error, if no module found
		if (!$module) {
			JError::raiseError(404, JText::_('COM_JCALPRO_MODULE_NOT_INSTALLED'));
			jexit();
		}
		echo $module;
		jexit();
	}
	
	/**
	 * fires a single plugin event
	 */
	public function plugin() {
		// BUGFIX: there are some seriously bad plugins that screw with the output...
		ob_start();
		// variables
		$input  = JFactory::getApplication()->input;
		$plugin = basename($input->get('plugin', '', 'cmd'));
		$type   = basename($input->get('type', '', 'cmd'));
		$action = $input->get('action', '', 'cmd');
		$args   = $input->get('args', array(), 'array');
		// quick sanity check to ensure the plugin exists
		$file   = JPATH_ROOT . "/plugins/$type/$plugin/$plugin.php";
		if (!JFile::exists($file)) {
			ob_end_clean();
			JError::raiseError(404, JText::_('COM_JCALPRO_PLUGIN_NOT_FOUND'));
		}
		// now check that the plugin is enabled
		jimport('joomla.plugin.helper');
		if (!JPluginHelper::isEnabled($type, $plugin)) {
			ob_end_clean();
			JError::raiseError(404, JText::_('COM_JCALPRO_PLUGIN_NOT_ENABLED'));
		}
		// get the plugin & try to fire the action
		$pdata      = JPluginHelper::getPlugin($type, $plugin);
		$class      = 'plg' . strtoupper($type) . strtoupper($plugin);
		$instance   = false;
		$dispatcher = JDispatcher::getInstance();
		if (!class_exists($class)) {
			require_once $file;
		}
		if (class_exists($class)) {
			$instance = new $class($dispatcher, JArrayHelper::fromObject($pdata));
		}
		if (!is_object($instance) || !method_exists($instance, $action) || !is_callable(array($instance, $action))) {
			JError::raiseError(404, JText::_('COM_JCALPRO_PLUGIN_ACTION_NOT_CALLABLE'));
		}
		// empty buffer
		ob_end_clean();
		// now fire the plugin event & exit
		call_user_func_array(array($instance, $action), $args);
		jexit();
	}
}
