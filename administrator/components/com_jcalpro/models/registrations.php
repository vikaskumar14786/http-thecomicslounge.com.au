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

JLoader::register('JCalProListModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodellist.php');

/**
 * This model supports retrieving lists of registrations.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelRegistrations extends JCalProListModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $context  = 'com_jcalpro.registrations';

	/**
	 * The category context (allows other extensions to derived from this model).
	 *
	 * @var		string
	 */
	protected $_extension = 'com_jcalpro';

	private $_parent = null;

	private $_items = null;
	
	private $_content = null;
	
	/**
	 * Constructor.
	 *
	 * @param       array   An optional associative array of configuration settings.
	 * @see         JController
	 */
	function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'Registration.id'
			,	'Registration.user_name'
			,	'Registration.event_id'
			,	'Event.start_date'
			,	'Event.title'
			,	'Registration.created_by'
			,	'Registration.published'
			);
		}
		
		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// force only published fields on frontend
		if (!JFactory::getApplication()->isAdmin()) {
			$this->setState('filter.published',	1);
		}
		$this->setState('filter.access', true);
		
		$app = JFactory::getApplication();
		
		$value = $app->getUserStateFromRequest($this->context.'.filter.event', 'filter_event', '');
		$this->setState('filter.event', $value);
		
		parent::populateState($ordering, $direction);
	}
	
	/**
	 * Method to retrieve items from the database
	 * 
	 */
	public function getItems() {
		$items = parent::getItems();
		if (!empty($items)) {
			jimport('jcaldate.date');
			foreach ($items as &$item) {
				try {
					$item->start_date = JCalDate::createFromFormat(JCalDate::JCL_FORMAT_MYSQL, $item->start_date)->toUser()->toSql();
				}
				catch (Exception $e) {
					JFactory::getApplication()->enqueueMessage(JText::_($e->getMessage()));
					continue;
				}
			}
		}
		return $items;
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 *
	 * @return	string		A store id.
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id	.= ':'.$this->getState('filter.extension');
		$id	.= ':'.$this->getState('filter.published');
		$id	.= ':'.$this->getState('filter.access');
		$id	.= ':'.$this->getState('filter.parentId');

		return parent::getStoreId($id);
	}

	protected function getListQuery() {
		// Create a new query object.
		$db = $this->getDbo();
	
		// main query
		$query = $db->getQuery(true)
			// Select the required fields from the table.
			->select($this->getState('list.select', 'Registration.*'))
			->from('#__jcalpro_registration AS Registration')
			// join over the events
			->select('Event.title AS event')
			->select('Event.start_date AS start_date')
			->leftJoin('#__jcalpro_events AS Event ON Registration.event_id = Event.id')
		;
		// add author to query
		$this->appendAuthorToQuery($query, 'Registration');

		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('Registration.published = ' . (int) $published);
		}
		else if ($published == '') {
			$query->where('(Registration.published = 0 OR Registration.published = 1)');
		}
		
		// Filter by event id
		$event = $this->getState('filter.event');
		if (is_numeric($event) && $event) {
			$query->where('Registration.event_id = ' . (int) $event);
		}

		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('Registration.id = '.(int) substr($search, 3));
			}
			else {
				$search = $db->Quote('%'.$db->getEscaped($search, true).'%');
				$query->where('Registration.user_name LIKE '.$search);
			}
		}
		
		// Add the list ordering clause.
		$listOrdering = $this->getState('list.ordering', 'Registration.id');
		$listDirn     = $db->escape($this->getState('list.direction', 'ASC'));
		$query->order($db->escape($listOrdering) . ' ' . $listDirn);

		// Group by filter
		$query->group('Registration.id');
		return $query;
	}
	
	/**
	 * Override our base to have the proper strings
	 * 
	 */
	public function getPublishedStatus() {
		$list = array(
			JHtml::_('select.option',  '',  JText::_('COM_JCALPRO_SELECT_PUBLISHED'))
		,	JHtml::_('select.option',  '1', JText::_('COM_JCALPRO_REGISTRATIONS_CONFIRMED'))
		,	JHtml::_('select.option',  '0', JText::_('COM_JCALPRO_REGISTRATIONS_UNCONFIRMED'))
		,	JHtml::_('select.option', '-2', JText::_('COM_JCALPRO_SELECT_PUBLISHED_OPTION_TRASHED'))
		);
		return $list;
	}
	
	public function getEventFilter() {
		// import the event field element
		require_once dirname(__FILE__) . '/fields/modal/jcalevent.php';
		// create a new instance of the field class
		$field = new JFormFieldModal_JCalEvent();
		// create a new xml element to pass to the setup
		$xml = JFactory::getXML('<field name="filter_event" type="jcalevent" />', false);
		// setup the form field
		$field->setup($xml, $this->getState('filter.event'));
		return $field->getExposedInput();
	}
	
	public function getContent() {
		if (empty($this->_content)) {
			// we don't want a paginated list
			$this->setState('list.start', 0);
			$this->setState('list.limit', 0);
			// get our items
			$items = $this->getItems();
			// standard headings
			$headings = array(
				JText::_('COM_JCALPRO_REGISTRATIONS_ID')
			,	JText::_('COM_JCALPRO_REGISTRATIONS_EVENT')
			,	JText::_('COM_JCALPRO_REGISTRATIONS_EVENT_ID')
			,	JText::_('COM_JCALPRO_REGISTRATIONS_CREATED')
			,	JText::_('COM_JCALPRO_REGISTRATIONS_USER_ID')
			,	JText::_('COM_JCALPRO_REGISTRATIONS_USER_NAME')
			,	JText::_('COM_JCALPRO_REGISTRATIONS_USER_EMAIL')
			,	JText::_('COM_JCALPRO_REGISTRATIONS_CREATED_BY')
			,	JText::_('COM_JCALPRO_REGISTRATIONS_MODIFIED')
			,	JText::_('COM_JCALPRO_REGISTRATIONS_MODIFIED_BY')
			,	JText::_('COM_JCALPRO_REGISTRATIONS_CONFIRMED')
			);
			// before we can really start building the CSV data, we need all the columns
			$extra  = false;
			$fields = array();
			// first pass - convert the params and find the events
			if (!empty($items)) {
				foreach ($items as &$item) {
					// convert this item's params
					$registry = new JRegistry();
					$registry->loadString($item->params);
					$item->params = $registry->toArray();
					// no params? skip
					if (empty($item->params)) continue;
					// add the fields to our list
					foreach (array_keys($item->params) as $key) if (!in_array($key, $fields)) $fields[] = $key;
				}
				// BUGFIX unset the last referenced item!!!
				unset($item);
			}
			
			// now we have our field keys - we need to get the labels for each field
			$db = JFactory::getDbo();
			// before we build the query, go ahead and quote the field array
			if (!empty($fields)) {
				foreach ($fields as &$field) $field = $db->Quote($field);
				// build the query
				$db->setQuery((string) $db->getQuery(true)
					->select($db->quoteName('name'))
					->select($db->quoteName('title'))
					->from('#__jcalpro_fields')
					->where($db->quoteName('formtype') . ' IN (-1, 1)')
					->where($db->quoteName('name') . ' IN (' . implode(',', $fields) . ')')
				);
				$extra = $db->loadObjectList();
				if (!empty($extra)) foreach ($extra as $e) $headings[] = $e->title;
			}
			
			// create the first row for the final headings
			$this->_content = $this->_toCsvRow($headings);
			// loop the registrations and add each row
			if (!empty($items)) {
				foreach ($items as $item) {
					$row = array(
						$item->id
					,	$item->event
					,	$item->event_id
					,	$item->created
					,	$item->user_id
					,	$item->user_name
					,	$item->user_email
					,	$item->created_by
					,	$item->modified
					,	$item->modified_by
					,	$item->published
					);
					if (!empty($extra)) {
						foreach ($extra as $field) {
							$value = '';
							if (array_key_exists($field->name, $item->params)) {
								$value = $item->params[$field->name];
							}
							$row[] = $value;
						}
					}
					$this->_content .= "\n" . $this->_toCsvRow($row);
				}
			}
		}
		return $this->_content;
	}
	
	private function _toCsvRow($columns) {
		$row = array();
		foreach ($columns as $column) {
			$row[] = '"' . str_replace('"', '""', $column) . '"';
		}
		return implode(',', $row);
	}
	
}
