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

jimport('joomla.html.pane');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');
JLoader::register('JCalProView', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/views/baseview.php');
JCalPro::registerHelper('url');

class JCalProViewDashboard extends JCalProView
{
	var $buttons;

	function display($tpl = null, $safeparams = false) {
		// buttons for dashboard
		$this->buttons = $this->getButtons();
		// rss feed
		$this->feed = JCalPro::config('show_ad_feeds', 1) ? $this->getFeed() : false;
		// component ID
		$this->component_id = JCalPro::getPackageId();
		// set up the model environment
		JCalProBaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/models');
		// grab the events model so we can load some events
		$model = JCalPro::getModelInstance('Events', 'JCalProModel');
		// force state to be loaded
		$model->getState('list.ordering');
		// common states
		$model->setState('list.ordering', 'Event.start_date');
		$model->setState('list.start', 0);
		$model->setState('list.limit', 10);
		$model->setState('filter.published', 1);
		// set the model for latest events
		$model->setState('filter.date_range', '1');
		$model->setState('list.direction', 'ASC');
		$this->latest_events = $model->getItems();
		// reset the state for upcoming events
		$model->setState('filter.date_range', '2');
		$model->setState('list.direction', 'DESC');
		$this->upcoming_events = $model->getItems();
		// the rest
		$this->addToolBar();
		$this->addMenuBar();
		parent::display($tpl, $safeparams);
	}

	function addToolBar() {
		JToolBarHelper::title(JText::_(JCalPro::COM . '_DASHBOARD_TITLE'), 'jcalpro');
		parent::addToolBar();
	}
	
	/**
	 * Method to load the feed from anything-digital
	 * 
	 * this should technically be in a model, but hey - we don't have one for the dashboard!
	 */
	function getFeed() {
		$cacheTime = 15 * 60;
		//  get RSS parsed object
		$options = array('rssUrl' => 'http://anything-digital.com/News/?format=feed');
		$cacheDir = JPATH_BASE.'/cache';
		if (is_writable($cacheDir)) {
			$options['cache_time'] = $cacheTime;
		}
		
		// getXMLParser is deprecated
		if (method_exists('JFactory', 'getXMLParser')) {
			$rssDoc = JFactory::getXMLParser('RSS', $options);
		}
		else {
			// JFactory::getFeedParser()
			$rssDoc = JFactory::getFeedParser($options['rssUrl'], array_key_exists('cache_time', $options) ? $options['cache_time'] : $cacheTime);
		}
		
		return $rssDoc;
	}

	private function getButtons() {
		if (empty($this->buttons)) {
			$buttons = array();
			// event manager
			$buttons[] = array(
				'link'  => JCalProHelperUrl::view('events')
			,	'image' => JHtml::_('jcalpro.image', 'icon-48-events.png')
			,	'text'  => JText::_(JCalPro::COM . '_EVENTS_MANAGER_BUTTON')
			);
			// categories
			$buttons[] = array(
				'link'  => JCalProHelperUrl::_(array('option' => 'com_categories', 'extension' => JCalPro::COM))
			,	'image' => JHtml::_('jcalpro.image', 'icon-48-category.png')
			,	'text'  => JText::_('COM_CATEGORIES')
			);
			// locations manager
			$buttons[] = array(
				'link'  => JCalProHelperUrl::view('locations')
			,	'image' => JHtml::_('jcalpro.image', 'icon-48-locations.png')
			,	'text'  => JText::_(JCalPro::COM . '_LOCATIONS_MANAGER_BUTTON')
			);
			// registrations manager
			$buttons[] = array(
				'link'  => JCalProHelperUrl::view('registrations')
			,	'image' => JHtml::_('jcalpro.image', 'icon-48-registration.png')
			,	'text'  => JText::_(JCalPro::COM . '_REGISTRATIONS_MANAGER_BUTTON')
			);
			// form manager
			$buttons[] = array(
				'link'  => JCalProHelperUrl::view('forms')
			,	'image' => JHtml::_('jcalpro.image', 'icon-48-forms.png')
			,	'text'  => JText::_(JCalPro::COM . '_FORMS_MANAGER_BUTTON')
			);
			// field manager
			$buttons[] = array(
				'link'  => JCalProHelperUrl::view('fields')
			,	'image' => JHtml::_('jcalpro.image', 'icon-48-fields.png')
			,	'text'  => JText::_(JCalPro::COM . '_FIELDS_MANAGER_BUTTON')
			);
			// email manager
			$buttons[] = array(
				'link'  => JCalProHelperUrl::view('emails')
			,	'image' => JHtml::_('jcalpro.image', 'icon-48-emails.png')
			,	'text'  => JText::_(JCalPro::COM . '_EMAILS_MANAGER_BUTTON')
			);
			// about
			$buttons[] = array(
				'link'  => JCalProHelperUrl::view('about')
			,	'image' => JHtml::_('jcalpro.image', 'icon-48-about.png')
			,	'text'  => JText::_(JCalPro::COM . '_ABOUT')
			);
			// help
			$buttons[] = array(
				'link'  => JCalProHelperUrl::help()
			,	'image' => JHtml::_('jcalpro.image', 'icon-48-help.png')
			,	'text'  => JText::_(JCalPro::COM . '_HELP')
			);
			$this->buttons = $buttons;
		}
		return $this->buttons;
	}
}
