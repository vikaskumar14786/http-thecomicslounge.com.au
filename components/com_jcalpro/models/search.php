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

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JLoader::register('JCalProListModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodellist.php');

/**
 * This model supports searching for events
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelSearch extends JCalProListModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $context  = 'com_jcalpro.search';

	/**
	 * The category context (allows other extensions to derived from this model).
	 *
	 * @var		string
	 */
	protected $_extension = 'com_jcalpro';

	private $_parent = null;

	private $_items = null;
	
	private $_eventModel = null;
	
	function __construct($config = array()) {
		// get the model and populate it's state
		$model = JCalPro::getModelInstance('Events', 'JCalProModel', array('ignore_request' => true));
		$model->populateState();
		$this->_eventModel = $model;
		
		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null) {
		
		parent::populateState($ordering, $direction);
		
		$app = JFactory::getApplication();
		
		// search
		$search = $app->input->get('searchword', '', 'string');
		$this->setState('filter.search', $search);
		
		// we have to fix the list limits as the base events model screws them up
		$value = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$limit = $value;
		$this->setState('list.limit', $limit);
		
		$value = $app->getUserStateFromRequest($this->context.'.limitstart', 'limitstart', 0);
		$limitstart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
		$this->setState('list.start', $limitstart);
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
	protected function getStoreId($id = '') {
		// Compile the store id.
		$id	.= ':'.$this->getState('filter.search');
		return parent::getStoreId($id);
	}

	protected function getListQuery() {
		
		// get the search from the search view's state and pass it to the model
		$search = $this->getState('filter.search');
		
		// set the limits
		$this->_eventModel->setState('list.limit', $this->getState('list.limit'));
		$this->_eventModel->setState('list.start', $this->getState('list.start'));
		// set the start & end dates - event model will override these if necessary
		JCalPro::registerHelper('date');
		$this->_eventModel->setState('filter.start_date', JCalProHelperDate::JCL_DATE_MIN);
		$this->_eventModel->setState('filter.end_date', JCalProHelperDate::JCL_DATE_MAX);
		$this->_eventModel->setState('filter.layout', 'search');
		if (!empty($search)) {
			// set the search as the filter for the events model
			$this->_eventModel->setState('filter.search', $search);
		}
		
		$query = $this->_eventModel->getListQuery();
		
		// JModel, Y U NO SET STATE?
		if (empty($search)) {
			$query->where('1=2');
		}
		
		return $query;
	}
	
	/**
	 * Method to retrieve items from the database
	 * 
	 * This is overloaded in the base model so we can alter the item data
	 */
	public function getItems() {
		$items = parent::getItems();
		
		$final = array();
		
		if (!empty($items)) {
			foreach ($items as $key => $item) {
				$this->_eventModel->prepareEvent($items[$key]);
				if (empty($items[$key]->categories->canonical)) {
					continue;
				}
				$final[] = $items[$key];
			}
		}
		
		return $final;
	}
	
	public function getPagination() {
		return $this->_eventModel->getPagination();
	}
	
}
