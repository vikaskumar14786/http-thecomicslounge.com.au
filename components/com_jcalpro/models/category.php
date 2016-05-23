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
 * This model supports listing events in a single category
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelCategory extends JCalProListModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $context  = 'com_jcalpro.category';

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
		parent::__construct($config);
		$app     = JFactory::getApplication();
		// dirty fix for category view
		$isCategoryView = ('category' == $app->input->get('view', '', 'cmd'));
		if ($isCategoryView) {
			$context = 'com_jcalpro.events.filter.catid';
			$catid   = $app->getUserStateFromRequest($context, 'filter_catid', '');
			$app->setUserState($context, $app->input->get('id', 0, 'uint'));
		}
		// get the model and populate it's state by reading something from it
		$model = JCalPro::getModelInstance('Events', 'JCalProModel');
		$state = $model->getState('filter.catid');
		$this->_eventModel = $model;
		// end dirty category view fix
		if ($isCategoryView) {
			$app->setUserState($context, $catid);
		}
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null) {
		
		parent::populateState($ordering, $direction);
		
		$app = JFactory::getApplication();
		
		// category id
		$catid = $app->input->get('id', 0, 'uint');
		$this->setState('filter.category', $catid);
		// we have to fix the list limits as the base events model screws them up
		$global_limit = JCalPro::version()->isCompatible('3.0') ? $app->get('list_limit') : $app->getCfg('list_limit');
		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $global_limit);
		$this->setState('list.limit', $limit);
		$start = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $start);
		
		$params = JCalPro::config();
		$this->setState('params', $params);
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
		$id	.= ':'.$this->getState('filter.category');
		return parent::getStoreId($id);
	}

	protected function getListQuery() {
		
		// get our category id from the state
		$catid = (int) $this->getState('filter.category');
		
		// NOTE: archive = show past events
		$range = (int) JCalPro::config('category_date_range', (int) JCalPro::config('archive') ? JCalPro::RANGE_ALL : JCalPro::RANGE_UPCOMING);
		
		// set the limits
		$this->_eventModel->setState('list.limit', $this->getState('list.limit'));
		$this->_eventModel->setState('list.start', $this->getState('list.start'));
		$this->_eventModel->setState('filter.layout', 'category');
		$this->_eventModel->setState('filter.date_range', $range);
		$this->_eventModel->setState('filter.catid', $catid);
		$this->_eventModel->setState('filter.category', array());
		$this->_eventModel->setState('parent.category', true);
		// we're going to get the query from the events model and alter it to join the xref table
		// then force only the correct categories
		$query = $this->_eventModel->getListQuery();
		
		// alter the query
		$query
			// join over the xref table
			->leftJoin('#__jcalpro_event_categories AS Xref ON Xref.event_id = Event.id AND Xref.category_id = ' . $catid)
			// join over the requested category
			->leftJoin('#__categories AS Category ON Category.id = ' . $catid)
			// select id from category
			->select('Category.id AS category_id')
			// force WHERE clause to select only this category
			->where('Xref.category_id = ' . $catid)
			->where('Category.id = ' . $catid)
		;
		
		return $query;
	}
	
	public function getCategory() {
		// get the catid from the request
		$catid = $this->getState('filter.category');
		if (empty($catid)) {
			JError::raiseError(404, JText::_('COM_JCALPRO_CATEGORY_NOT_FOUND'));
			return false;
		}
		// load the category model
		$catsModel = JCalPro::getModelInstance('Categories', 'JCalProModel', array('ignore_request' => true));
		$catsModel->setState('filter.search', 'id:' . $catid);
		$catsModel->setState('list.start', 0);
		$catsModel->setState('list.limit', 0);
		$cats = $catsModel->getItems();
		foreach ($cats as $cat) {
			if ($cat->id == $catid) break;
			$cat = false;
		}
		// if there are no items, set the error and return
		if (empty($cat) || empty($cat->id)) {
			JError::raiseError(404, JText::_('COM_JCALPRO_CATEGORY_NOT_FOUND'));
			return false;
		}
		
		return $cat;
	}
	
	/**
	 * Method to retrieve items from the database
	 * 
	 * This is overloaded in the base model so we can alter the item data
	 */
	public function getItems() {
		$items = parent::getItems();
		
		if (!empty($items)) {
			foreach ($items as &$item) {
				$this->_eventModel->prepareEvent($item);
			}
		}
		
		return $items;
	}
	
}
