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
 * This model supports retrieving lists of fields.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelFields extends JCalProListModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $context  = 'com_jcalpro.fields';

	/**
	 * The category context (allows other extensions to derived from this model).
	 *
	 * @var		string
	 */
	protected $_extension = 'com_jcalpro';

	private $_parent = null;

	private $_items = null;
	
	/**
	 * Constructor.
	 *
	 * @param       array   An optional associative array of configuration settings.
	 * @see         JController
	 */
	function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'Field.id'
			,	'Field.title'
			,	'Field.type'
			,	'Field.formtype'
			,	'Field.created_by'
			,	'Field.published'
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
		parent::populateState($ordering, $direction);
		
		// force only published fields on frontend
		if (!JFactory::getApplication()->isAdmin()) {
			$this->setState('filter.published',	1);
		}
		
		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);
		
		$type = $this->getUserStateFromRequest($this->context.'.filter.formtype', 'filter_formtype', '', 'string');
		$this->setState('filter.formtype', $type);
		
		$this->setState('filter.access', true);
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
		$id	.= ':'.$this->getState('filter.formtype');

		return parent::getStoreId($id);
	}

	protected function getListQuery() {
		// Create a new query object.
		$db = $this->getDbo();
	
		// main query
		$query = $db->getQuery(true)
			// Select the required fields from the table.
			->select($this->getState('list.select', 'Field.*'))
			->from('#__jcalpro_fields AS Field')
		;
		// add author to query
		$this->appendAuthorToQuery($query, 'Field');

		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('Field.published = ' . (int) $published);
		}
		else if ($published == '') {
			$query->where('(Field.published = 0 OR Field.published = 1)');
		}

		// Filter by search.
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('Field.id = '.(int) substr($search, 3));
			}
			else {
				$search = $db->Quote('%'.$db->getEscaped($search, true).'%');
				$query->where('Field.title LIKE '.$search);
			}
		}
		
		$type = $this->getState('filter.formtype');
		if (is_numeric($type)) {
			$query->where('Field.formtype = ' . (int) $type);
		}
		
		// Add the list ordering clause.
		$listOrdering = $this->getState('list.ordering', 'Field.title');
		$listDirn     = $db->escape($this->getState('list.direction', 'ASC'));
		$query->order($db->escape($listOrdering) . ' ' . $listDirn);

		// Group by filter
		$query->group('Field.id');
		return $query;
	}
	
}
