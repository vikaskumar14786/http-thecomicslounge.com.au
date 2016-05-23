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

JCalPro::registerHelper('tags');
JCalPro::registerHelper('theme');

JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');
JLoader::register('JCalProListModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodellist.php');

JCalProBaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models');

/**
 * This model supports retrieving lists of categories for the frontend.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelCategories extends JCalProListModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $context  = 'com_jcalpro.categories';

	/**
	 * The category context (allows other extensions to derived from this model).
	 *
	 * @var		string
	 */
	protected $_extension = 'com_jcalpro';

	private $_parent = null;

	private $_items = null;
	
	private $_categoryModel = null;
	
	function __construct($config = array()) {
		$this->_categoryModel = JCalPro::getModelInstance('Categories', 'CategoriesModel', array('ignore_request' => true)); $l = __LINE__;
		if ($this->_categoryModel) {
			// get only our categories
			$this->_categoryModel->setState('filter.extension', $this->_extension);
			// ensure we're only getting the published ones
			// BUGFIX: administrator panel should have all?
			if (JFactory::getApplication()->isAdmin()) {
				$this->_categoryModel->setState('filter.published', '');
			}
			else {
				$this->_categoryModel->setState('filter.published', 1);
			}
			// make sure we're ordering properly
			$this->_categoryModel->setState('list.ordering', 'a.lft');
			$this->_categoryModel->setState('list.direction', 'ASC');
			$this->_categoryModel->setState('list.limit', 0);
			$this->_categoryModel->setState('list.limitstart', 0);
		}
		else {
			$error = JText::sprintf('COM_JCALPRO_MODEL_X_NOT_FOUND', 'Category', __FILE__, $l);
			JCalProHelperLog::toss($error);
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
		// we have to fix the list limits as the base events model screws them up
		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$this->setState('list.limit', $limit);
		
		$value = $app->getUserStateFromRequest($this->context.'.limitstart', 'limitstart', 0);
		$limitstart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
		$this->setState('list.start', $limitstart);
		
		$params = JCalPro::config();
		$this->setState('params', $params);
		
		$value = $app->getUserStateFromRequest($this->context.'.filter.search', 'filter_search', '');
		$this->setState('filter.search', $value);
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
		$id	.= ':'.$this->getState('filter.search');

		return parent::getStoreId($id);
	}

	protected function getListQuery() {
		$db    = JFactory::getDbo();
		
		// set the id/search
		$this->_categoryModel->setState('filter.search', $this->getState('filter.search'));
		// get the query from the category model
		$query = $this->_categoryModel->getListQuery();
		
		/*
		// check if the state allows us to show non-accessible categories
		// TODO
		if (false) {
			// get the "where" JDatabaseQueryElement element
			$whereObj = $query->get('where');
			// ensure we're acting upon a JDatabaseQueryElement
			if (class_exists('JDatabaseQueryElement') && $whereObj instanceof JDatabaseQueryElement) {
				// get the elements
				$wheres = $whereObj->getElements();
				if (!empty($wheres)) {
					// clear the current "where" and reappend all the non-access wheres
					$query->clear('where');
					foreach ($wheres as $where) {
						if (preg_match('/^a\.access\ IN/', $where)) continue;
						$query->where($where);
					}
				}
			}
		}
		*/
		
		// add our extras
		$query
			// add in the parameters
			->select('a.params')
			// add in the description
			->select('a.description')
			->group('a.id')
		;
		
		// return the query
		return $query;
	}
	
	/**
	 * Method to retrieve items from the database
	 * 
	 * This is overloaded in the base model so we can alter the item data
	 */
	public function getItems() {
		// we want to store counts based on the sql query so we don't hammer the server
		static $counts;
		if (!is_array($counts)) {
			$counts = array();
		}
		$today = JCalProHelperDate::getToday()->toUtc();
		$user  = JFactory::getUser();
		$db    = JFactory::getDbo();
		// build a generic query that will be used for upcoming & total counts
		$total_query = $db->getQuery(true)
			->select('COUNT(Event.id)')
			->from('#__jcalpro_events AS Event')
			->leftJoin('#__jcalpro_event_categories AS Xref ON Xref.event_id = Event.id AND Xref.category_id = %1$d')
			->where('Xref.category_id = %1$d')
			->where('Event.published = 1')
			->where('Event.approved = 1')
			->where('(Event.private = 0 OR (Event.private = 1 AND Event.created_by = ' . (int) $user->id . '))')
			->group('Xref.category_id')
		;
		$upcoming_query = clone $total_query;
		// now adjust the query for "upcoming" count
		$upcoming_query->where('Event.start_date >= ' . $db->Quote($today->toSql()));
		// TODO: pull from theme
		$default_color = '545454';
		$default_theme = JCalProHelperTheme::current();
		$default_eform = '';
		$default_rform = '';
		static $forms;
		if (is_null($forms)) {
			// reset default forms
			$db->setQuery((string) $db->getQuery(true)
				->select('id, type')
				->from('#__jcalpro_forms')
				->where($db->quoteName('default') . '=1')
			);
			$forms = $db->loadObjectList();
		}
		if (!empty($forms)) {
			foreach ($forms as $form) {
				switch ($form->type) {
					case 0: $default_eform = $form->id; break;
					case 1: $default_rform = $form->id; break;
				}
			}
		}
		// use our list model parent and NOT the category model's!!
		$items = parent::getItems();
		// decode the parameters
		if (!empty($items)) {
			// force the items to have numeric keys
			$items = array_values($items);
			// loop items
			for ($i=0; $i<count($items); $i++) {
				$item = &$items[$i];
				$upcoming_query_string = sprintf((string) $upcoming_query, $item->id);
				$upcoming_key = md5($upcoming_query_string);
				if (!array_key_exists($upcoming_key, $counts)) {
					$db->setQuery($upcoming_query_string);
					$counts[$upcoming_key] = $db->loadResult();
				}
				$total_query_string = sprintf((string) $total_query, $item->id);
				$total_key = md5($total_query_string);
				if (!array_key_exists($total_key, $counts)) {
					$db->setQuery($total_query_string);
					$counts[$total_key] = $db->loadResult();
				}
				// add item counts
				$item->upcoming_events = $counts[$upcoming_key];
				$item->total_events = $counts[$total_key];
				// check tags
				if (JCalProHelperTags::useTags()) {
					$item->tags = JCalProHelperTags::getHelper('category');
					$item->tags->getItemTags('com_jcalpro.category', $item->id);
				}
				// params
				$registry = new JRegistry;
				$registry->loadString($item->params);
				$item->params = $registry;
				// check the values for this category
				$color = $item->params->def('jcalpro_color', '');
				$theme = $item->params->def('jcalpro_theme', '');
				$eform = $item->params->def('jcalpro_eventform', '');
				$rform = $item->params->def('jcalpro_registrationform', '');
				$reg   = $item->params->def('jcalpro_registration', '');
				$desc  = $item->params->def('jcalpro_category_description', JCalPro::config('category_description', ''));
				// no color? we need to either get the default for the theme (if the parent is 1) or from the parent
				if ('' === $color || ('' === $theme || -1 == $theme) || '' === $eform || '' === $rform || '' === $reg || '' === $desc) {
					if (1 != $item->parent_id) {
						// loop the categories again, find the parent and assign the color from that
						for ($j=0; $j<count($items); $j++) {
							if ($items[$j]->id != $item->parent_id) continue;
							$color = '' === $color ? $items[$j]->params->get('jcalpro_color') : $color;
							$theme = ('' === $theme || -1 == $theme) ? $items[$j]->params->get('jcalpro_theme') : $theme;
							$eform = '' === $eform ? $items[$j]->params->get('jcalpro_eventform') : $eform;
							$rform = '' === $rform ? $items[$j]->params->get('jcalpro_registrationform') : $rform;
							$reg   = '' === $reg ? $items[$j]->params->get('jcalpro_registration') : $reg;
							$desc  = '' === $desc ? $items[$j]->params->get('jcalpro_category_description') : $desc;
							break;
						}
					}
					$item->params->set('jcalpro_color', empty($color) ? $default_color : $color);
					$item->params->set('jcalpro_theme', empty($theme) || -1 == $theme ? $default_theme : $theme);
					$item->params->set('jcalpro_eventform', empty($eform) ? $default_eform : $eform);
					$item->params->set('jcalpro_registrationform', empty($rform) ? $default_rform : $rform);
					$item->params->set('jcalpro_registration', $reg);
					$item->params->set('jcalpro_category_description', $desc);
				} 
				// ensure the color has a hash
				$item->params->set('jcalpro_color', '#' . trim($item->params->get('jcalpro_color'), '#'));
			}
		} 
		return $items;
	}
	
}
