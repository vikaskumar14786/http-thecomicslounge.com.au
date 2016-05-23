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

JLoader::register('JCalProListView', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/views/baseviewlist.php');

class JCalProViewEvents extends JCalProListView
{
	/**
	 * Default sorting column
	 * 
	 * @var string
	 */
	protected $_sortColumn = 'Event.start_date';
	
	function display($tpl = null, $safeparams = false) {
		$db  = JFactory::getDbo();
		$app = JFactory::getApplication();
		// before we load the data, we have to set the start & end (because we want all events here)
		$model = $this->getModel();
		$model->setState('filter.start_date', 1);
		$model->setState('filter.end_date', 1);
		// assign data
		$items		  = $this->get('Items');
		$dates      = $this->get('AllTheDates'); // challenge accepted
		$categories = $this->get('Categories');
		$state      = $this->get('State');
		
		// before we go anywhere, we need to reset the state now that we've asked the model for our events
		// if we're in "modal" layout - otherwise things stay filtered later and it gets icky
		// TODO: work in a dual-state for this, so we can preserve this state if needed
		if ('modal' == $app->input->get('layout', '', 'cmd')) {
			$app->setUserState('com_jcalpro.events.filter.registration', '');
		}

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseWarning(500, implode("\n", $errors));
			return false;
		}
		
		// only add this message if we haven't been redirected
		if (empty($categories)) {
			$queue = $app->getMessageQueue();
			if (empty($queue)) {
				$app->enqueueMessage(JText::_('COM_JCALPRO_CANNOT_CREATE_EVENTS_WITHOUT_CATEGORIES'), 'warning');
			}
		}
		
		// build a category list for imports
		$importcatopts    = JHtml::_('jcalpro.calendarlistoptions', array(), false, false, true);
		$this->importcats = JHtml::_('select.genericlist', $importcatopts, 'import_catid', 'id="catid" class="listbox"', 'value', 'text', $app->getUserStateFromRequest('com_jcalpro.events.jcal.catid', 'catid', ''));
		
		
		// location
		$db->setQuery((string) $db->getQuery(true)
			->select('id AS value')
			->select('title AS text')
			->from('#__jcalpro_locations')
			->where('published = 1')
			->order('title')
		);
		$opts = $db->loadObjectList();
		array_unshift($opts, JHtml::_('select.option', '0', JText::_('COM_JCALPRO_FILTER_LOCATION_NONE')));
		array_unshift($opts, JHtml::_('select.option', '', JText::_('COM_JCALPRO_FILTER_LOCATION')));
		$this->addFilter(JText::_('COM_JCALPRO_FILTER_LOCATION'), 'filter_location', $opts, $state->get('filter.location'));
		
		
		// language filter
		$opts = array(JHtml::_('select.option', '', JText::_('JOPTION_SELECT_LANGUAGE')), JHtml::_('select.option', '*', JText::_('JALL')));
		$opts = array_merge($opts, JLanguageHelper::createLanguageList('', constant('JPATH_SITE'), true, true));
		$this->addFilter(JText::_('JOPTION_SELECT_LANGUAGE'), 'filter_language', $opts, $state->get('filter.language'));
		
		// date range
		$opts = array(
			JHtml::_('select.option',   '', JText::_('COM_JCALPRO_FILTER_DATE_RANGE'))
		,	JHtml::_('select.option', '14', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_ONGOING'))
		,	JHtml::_('select.option',  '1', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_PAST_EVENTS'))
		,	JHtml::_('select.option', '22', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_PAST_END_EVENTS'))
		,	JHtml::_('select.option',  '2', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_UPCOMING_EVENTS'))
		,	JHtml::_('select.option', '23', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_UPCOMING_END_EVENTS'))
		,	JHtml::_('select.option',  '3', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_THIS_WEEK'))
		,	JHtml::_('select.option',  '4', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_LAST_WEEK'))
		,	JHtml::_('select.option',  '5', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_NEXT_WEEK'))
		,	JHtml::_('select.option', '15', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_NEXT_2_WEEKS'))
		,	JHtml::_('select.option', '16', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_NEXT_3_WEEKS'))
		,	JHtml::_('select.option',  '6', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_THIS_MONTH'))
		,	JHtml::_('select.option',  '7', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_LAST_MONTH'))
		,	JHtml::_('select.option',  '8', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_NEXT_MONTH'))
		,	JHtml::_('select.option', '17', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_NEXT_2_MONTHS'))
		,	JHtml::_('select.option', '18', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_NEXT_3_MONTHS'))
		,	JHtml::_('select.option', '19', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_THIS_YEAR'))
		,	JHtml::_('select.option', '20', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_LAST_YEAR'))
		,	JHtml::_('select.option', '21', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_NEXT_YEAR'))
		,	JHtml::_('select.option',  '9', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_TODAY'))
		,	JHtml::_('select.option', '10', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_TOMORROW'))
		,	JHtml::_('select.option', '11', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_YESTERDAY'))
		,	JHtml::_('select.option', '12', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_NEXT_30'))
		,	JHtml::_('select.option', '13', JText::_('COM_JCALPRO_FILTER_DATE_RANGE_LAST_30'))
		);
		$this->addFilter(JText::_('COM_JCALPRO_FILTER_DATE_RANGE'), 'filter_date_range', $opts, $state->get('filter.date_range'));
		
		// by month
		$opts = array(
			JHtml::_('select.option', '', JText::_('COM_JCALPRO_FILTER_MONTH'))
		,	JHtml::_('select.option', '1', JText::_('JANUARY'))
		,	JHtml::_('select.option', '2', JText::_('FEBRUARY'))
		,	JHtml::_('select.option', '3', JText::_('MARCH'))
		,	JHtml::_('select.option', '4', JText::_('APRIL'))
		,	JHtml::_('select.option', '5', JText::_('MAY'))
		,	JHtml::_('select.option', '6', JText::_('JUNE'))
		,	JHtml::_('select.option', '7', JText::_('JULY'))
		,	JHtml::_('select.option', '8', JText::_('AUGUST'))
		,	JHtml::_('select.option', '9', JText::_('SEPTEMBER'))
		,	JHtml::_('select.option', '10', JText::_('OCTOBER'))
		,	JHtml::_('select.option', '11', JText::_('NOVEMBER'))
		,	JHtml::_('select.option', '12', JText::_('DECEMBER'))
		);
		$this->addFilter(JText::_('COM_JCALPRO_FILTER_MONTH'), 'filter_month', $opts, $state->get('filter.month'));
		
		// by year
		$today = JCalProHelperDate::getToday();
		$opts  = array(
			JHtml::_('select.option', '', JText::_('COM_JCALPRO_FILTER_YEAR'))
		);
		$year  = JCalProHelperDate::getToday()->year() - 5;
		$max   = $year + 25;
		while ($year < $max) {
			$opts[] = JHtml::_('select.option', $year, $year);
			$year++;
		}
		$this->addFilter(JText::_('COM_JCALPRO_FILTER_YEAR'), 'filter_year', $opts, $state->get('filter.year'));
		
		// approved
		$opts = array(
			JHtml::_('select.option', '', JText::_('COM_JCALPRO_FILTER_APPROVED'))
		,	JHtml::_('select.option', '1', JText::_('COM_JCALPRO_FILTER_APPROVED_APPROVED'))
		,	JHtml::_('select.option', '0', JText::_('COM_JCALPRO_FILTER_APPROVED_UNAPPROVED'))
		);
		$this->addFilter(JText::_('COM_JCALPRO_FILTER_APPROVED'), 'filter_approved', $opts, $state->get('filter.approved'));
		
		// featured
		$opts = array(
			JHtml::_('select.option', '', JText::_('COM_JCALPRO_FILTER_FEATURED'))
		,	JHtml::_('select.option', '1', JText::_('COM_JCALPRO_FILTER_FEATURED_FEATURED'))
		,	JHtml::_('select.option', '0', JText::_('COM_JCALPRO_FILTER_FEATURED_UNFEATURED'))
		);
		$this->addFilter(JText::_('COM_JCALPRO_FILTER_FEATURED'), 'filter_featured', $opts, $state->get('filter.featured'));
		
		// recur
		$opts = array(
			JHtml::_('select.option', '', JText::_('COM_JCALPRO_FILTER_RECUR'))
		,	JHtml::_('select.option', '0', JText::_('COM_JCALPRO_FILTER_RECUR_CHILDREN'))
		,	JHtml::_('select.option', '1', JText::_('COM_JCALPRO_FILTER_RECUR_NOCHILDREN'))
		);
		$this->addFilter(JText::_('COM_JCALPRO_FILTER_RECUR'), 'filter_recur', $opts, $state->get('filter.recur'));
		
		// registration
		$opts = array(
			JHtml::_('select.option', '', JText::_('COM_JCALPRO_FILTER_REGISTRATION'))
		,	JHtml::_('select.option', '0', JText::_('COM_JCALPRO_FILTER_REGISTRATION_NOT_ALLOWED'))
		,	JHtml::_('select.option', '1', JText::_('COM_JCALPRO_FILTER_REGISTRATION_ALLOWED'))
		);
		$this->addFilter(JText::_('COM_JCALPRO_FILTER_REGISTRATION'), 'filter_registration', $opts, $state->get('filter.registration'));
		
		// catid
		$opts = array(JHtml::_('select.option', '', JText::_('JOPTION_SELECT_CATEGORY')));
		$opts = array_merge($opts, JHtml::_('category.options', 'com_jcalpro'));
		$this->addFilter(JText::_('JOPTION_SELECT_CATEGORY'), 'filter_catid', $opts, $state->get('filter.catid'));
		
		// time zone
		$opts = array(JHtml::_('select.option', '', JText::_('COM_JCALPRO_FILTER_TIMEZONE')));
		$db->setQuery((string) $db->getQuery(true)->select('DISTINCT(timezone)')->from('#__jcalpro_events')->order('timezone ASC'));
		if ($tzs = $db->loadColumn()) {
			foreach ($tzs as $tz) $opts[] = JHtml::_('select.option', $tz, $tz);
		}
		if (1 == count($opts)) {
			$this->addFilter(JText::_('COM_JCALPRO_FILTER_TIMEZONE'), 'filter_timezone', $opts, $state->get('filter.timezone'));
		}
		
		// editor button inputs
		if ('button' == $app->input->get('layout', '', 'cmd')) {
			$opts = array(
				JHtml::_('select.option', '1', JText::_('JYES'))
			,	JHtml::_('select.option', '0', JText::_('JNO'))
			);
			$this->insert_link = JHtml::_('select.genericlist', $opts, 'insert_link', 'class="inputbox"', 'value', 'text', (int) $state->get('insert.link'));
			$this->insert_title = JHtml::_('select.genericlist', $opts, 'insert_title', 'class="inputbox"', 'value', 'text', (int) $state->get('insert.title'));
			$this->insert_start_date = JHtml::_('select.genericlist', $opts, 'insert_start_date', 'class="inputbox"', 'value', 'text', (int) $state->get('insert.start_date'));
			$this->insert_end_date = JHtml::_('select.genericlist', $opts, 'insert_end_date', 'class="inputbox"', 'value', 'text', (int) $state->get('insert.end_date'));
			$this->insert_description = JHtml::_('select.genericlist', $opts, 'insert_description', 'class="inputbox"', 'value', 'text', (int) $state->get('insert.description'));
			$this->insert_itemid = '<input name="insert_itemid" id="insert_itemid" size="5" value="' . (int) $state->get('insert.description') . '" />';
		}
		
		// assign data
		$this->items		  = $items;
		$this->dates      = $dates;
		$this->categories = $categories;
		
		// display
		parent::display($tpl, $safeparams);
	}

	public function addToolBar() {
		if (!JFactory::getApplication()->isAdmin()) {
			return;
		}
		JCalPro::registerHelper('access');
		// add our approval buttons
		if ($this->state->get('filter.published') != -2 && JCalProHelperAccess::canModerateEvents($this->state->get('filter.catid', null))) {
			JToolBarHelper::publishList('events.approve','COM_JCALPRO_APPROVE_APPROVE');
			JToolBarHelper::unpublishList('events.unapprove','COM_JCALPRO_APPROVE_UNAPPROVE');
			JToolBarHelper::divider();
		}
		// add the rest
		parent::addToolBar();
	}
	
	protected function _prepareDocument() {
		parent::_prepareDocument();
		
		// get a list of our items that are children, so we can warn if we try to alter the state
		$children = array();
		if (!empty($this->items)) {
			foreach ($this->items as &$item) {
				if ($item->rec_id && 0 == $item->detached_from_rec) {
					if (!array_key_exists($item->rec_id, $children)) {
						$children[$item->rec_id] = array();
					}
					$children[$item->rec_id][] = $item->id;
				}
			}
		}
		$script = array('window.jclChildrenEvents = {data:[],list:[]};');
		if (!empty($children)) {
			$kids = array();
			foreach ($children as $id => $rec) {
				$script[] = 'window.jclChildrenEvents.data.push({id:' . $id . ',children:[' . implode(',', $rec) . ']});';
				$kids = array_merge($kids, $rec);
			}
			$script[] = 'window.jclChildrenEvents.list = [' . implode(',', $kids) . '];';
		}
		$this->document->addScriptDeclaration(implode("\n", $script));
		$this->document->addScript(JCalProHelperUrl::media() . '/js/events.js');
	}
	
	/**
	 * Returns an array of fields the table can be sorted by
	 * 
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields() {
		return array(
			'Event.title'      => JText::_('COM_JCALPRO_TITLE')
		,	'Event.timezone'   => JText::_('COM_JCALPRO_TIMEZONE')
		,	'Event.start_date' => JText::_('COM_JCALPRO_START_DATE')
		,	'Event.end_date'   => JText::_('COM_JCALPRO_END_DATE')
		,	'Event.recur_type' => JText::_('COM_JCALPRO_KIND')
		,	'Event.created_by' => JText::_('COM_JCALPRO_CREATED_BY')
		,	'Event.approved'   => JText::_('COM_JCALPRO_APPROVED')
		,	'Event.published'  => JText::_('COM_JCALPRO_PUBLISHED')
		);
	}
}