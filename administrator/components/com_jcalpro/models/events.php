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

JLoader::register('JCalProListEventsModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodelevents.php');

/**
 * This models supports retrieving lists of events.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelEvents extends JCalProListEventsModel
{
	private $_parent = null;

	private $_items = null;

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
		$id	.= ':'.$this->getState('filter.date_range');
		$id	.= ':'.$this->getState('filter.approved');
		$id	.= ':'.$this->getState('filter.featured');
		$id	.= ':'.$this->getState('filter.catid');
		$id	.= ':'.$this->getState('filter.month');
		$id	.= ':'.$this->getState('filter.year');
		$id	.= ':'.$this->getState('filter.timezone');
		$id	.= ':'.$this->getState('filter.registration');
		$id	.= ':'.$this->getState('filter.search');
		$id	.= ':'.$this->getState('prepare.categories');
		$id	.= ':'.$this->getState('prepare.location');
		$id	.= ':'.$this->getState('prepare.registration');

		return parent::getStoreId($id);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);
		
		$filters = array('search', 'language', 'date_range', 'approved', 'featured', 'recur', 'catid', 'month', 'year', 'timezone', 'registration', 'location');
		foreach ($filters as $filter) {
			$value = $this->getUserStateFromRequest($this->context.'.filter.'.$filter, 'filter_'.$filter, '', 'string');
			$this->setState('filter.'.$filter, $value);
		}
		
		$this->setState('prepare.categories', true);
		$this->setState('prepare.location', true);
		$this->setState('prepare.registration', true);
	}
	
	/**
	 * overrides the base events model so we can add extra filters
	 * 
	 */
	protected function getListQuery() {
		$db = $this->getDbo();
		$query = parent::getListQuery();

		// Filter by published state
		$published = $this->getState('filter.published');
		if (is_numeric($published)) {
			$query->where('Event.published = ' . (int) $published);
		}
		else if ($published === '') {
			$query->where('(Event.published = 0 OR Event.published = 1)');
		}
		
		// timezone
		$value = $this->getState('filter.timezone');
		if (!empty($value)) {
			$query->where('Event.timezone = ' . $db->Quote($value));
		}
		
		// show recur children?
		$value = intval($this->getState('filter.recur'));
		if ($value) {
			$query->where('Event.rec_id = 0 AND Event.detached_from_rec = 0');
		}
		
		// year
		$value = intval($this->getState('filter.year'));
		if ($value) {
			$query->where('Event.year = ' . $db->Quote($value));
		}
		
		// month
		$value = intval($this->getState('filter.month'));
		if ($value) {
			$query->where('Event.month = ' . $db->Quote($value));
		}

		// Add the list ordering clause.
		/*
		$orderCol = trim($this->getState('list.ordering'));
		$orderDirn = trim($this->getState('list.direction'));
		if (strlen($orderCol)) {
			$query->order($db->getEscaped($orderCol.' '.$orderDirn));
		}
		*/
		
		return $query;
	}
}
