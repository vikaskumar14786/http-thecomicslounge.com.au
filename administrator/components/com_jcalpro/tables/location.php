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

jimport('joomla.database.table');
JLoader::register('JCalProAssetTable', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/tables/asset.php');

class JCalProTableLocation extends JCalProAssetTable
{
	public $id;
	public $title;
	public $alias;
	public $address;
	public $city;
	public $state;
	public $country;
	public $postal_code;
	public $latitude;
	public $longitude;
	public $latlng;
	public $created;
	public $created_by;
	public $modified;
	public $modified_by;
	public $published;
	public $checked_out;
	public $checked_out_time;

	function __construct(&$db) {
		parent::__construct('#__jcalpro_locations', 'id', $db);
	}

	protected function _getAssetName() {
		$k = $this->_tbl_key;
		return 'com_jcalpro.location.'.(int) $this->$k;
	}
	
	protected function _compat_getAssetParentId($table = null, $id = null) {
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_jcalpro.locations');
		return $asset->id;
	}

	protected function _getAssetTitle() {
		return $this->title;
	}
	
	
	/**
	 * Method to ensure our date data is ok before storing
	 * 
	 */
	public function check() {
		// ensure we have a title
		if ('' == trim($this->title)) {
			$this->setError(JText::_('COM_JCALPRO_LOCATION_EMPTY_TITLE'));
			return false;
		}
		// set alias
		if ('' == trim($this->alias)) {
			$this->alias = $this->title;
		}
		$this->alias = JApplication::stringURLSafe($this->alias);
		
		return true;
	}
	
	/**
	 * Overload the store method for the JCalPro Fields table.
	 * 
	 * @param       boolean Toggle whether null values should be updated.
	 * @return      boolean True on success, false on failure.
	 */
	public function store($updateNulls = false) {
		$date   = JFactory::getDate();
		$user   = JFactory::getUser();
		if ($this->id) {
			// Existing item
			$this->modified    = $date->toSql();
			$this->modified_by = $user->get('id');
		}
		else {
			// New field
			$this->created = $date->toSql();
			$this->created_by = $user->get('id');
		}
		// store the values
		$store = parent::store($updateNulls);
		// we have to do this manually, because JTable doesn't support GIS directly :(
		if ($store) {
			try {
				$query = 'UPDATE ' . $this->_tbl . ' SET latlng = GeomFromText(' . $this->_db->quote('POINT(' . (float) $this->latitude . ' ' . (float) $this->longitude . ')') . ') WHERE id = ' . $this->id;
				$this->_db->setQuery($query);
				$this->_db->query();
			}
			catch (Exception $e) {
				JFactory::getApplication()->enqueueMessage(JText::_('COM_JCALPRO_LOCATIONS_GIS_NOT_SUPPORTED'), 'warning');
			}
		}
		return $store;
	}
}
