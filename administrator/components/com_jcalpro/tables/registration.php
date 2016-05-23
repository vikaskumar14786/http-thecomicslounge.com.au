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

class JCalProTableRegistration extends JCalProAssetTable
{
	public $id;
	public $event_id;
	public $created;
	public $created_by;
	public $modified;
	public $modified_by;
	public $published;
	public $user_name;
	public $user_email;
	public $user_id;
	public $confirmation;
	public $checked_out;
	public $checked_out_time;
	public $params;

	function __construct(&$db) {
		parent::__construct('#__jcalpro_registration', 'id', $db);
	}

	protected function _getAssetName() {
		$k = $this->_tbl_key;
		return 'com_jcalpro.registration.'.(int) $this->$k;
	}
	
	protected function _compat_getAssetParentId($table = null, $id = null) {
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_jcalpro.registrations');
		return $asset->id;
	}

	protected function _getAssetTitle() {
		return $this->user_name . ' ' . $this->user_email . ' ' . $this->event_id;
	}
	
	public function load($keys = null, $reset = true) {
		$load = parent::load($keys, $reset);
		// parameters
		if (is_string($this->params)) {
			$registry = new JRegistry;
			$registry->loadString($this->params);
			$this->params = $registry;
		}
		return $load;
	}
	
	/**
	 * overload bind
	 */
	public function bind($array, $ignore = '') {
		// parameters
		if (isset($array['params'])) {
			$registry = new JRegistry;
			if (is_array($array['params'])) {
				$registry->loadArray($array['params']);
			}
			else if (is_string($array['params'])) {
				$registry->loadString($array['params']);
			}
			else if (is_object($array['params'])) {
				
			}
			$array['params'] = (string) $registry;
		}
		
		return parent::bind($array, $ignore);
	}
	
	/**
	 * Overload the store method for the JCalPro Registration table.
	 * 
	 * @param       boolean Toggle whether null values should be updated.
	 * @return      boolean True on success, false on failure.
	 */
	public function store($updateNulls = false) {
		$date   = JFactory::getDate();
		$user   = JFactory::getUser();
		// handle new registrations
		if (!$this->id) {
			// set the creation columns
			$this->created = $date->toSql();
			$this->created_by = $user->get('id');
			// set the published/confirmed status based on core.manage if it's not set
			// but only do this via the frontend
			if (!JFactory::getApplication()->isAdmin()) {
				$this->published = (int) $user->authorise('core.manage', 'com_jcalpro');
			}
			// only set the token if we're not confirming this registration
			if (1 != $this->published) {
				// create a new confirmation token - we're not worried about being super secure here :P
				$this->confirmation = sha1($date->toSql() . $this->created_by . rand(0, rand(128, 256)));
			}
		}
		// existing registrations
		else {
			// wipe out the token
			$this->confirmation = '';
			// set the modified columns
			$this->modified = $date->toSql();
			$this->modified_by = $user->get('id');
		}
		
		// we need to fill in the user info
		// if we have a user_id, use that
		if ($this->user_id) {
			$user = JFactory::getUser($this->user_id);
		}
		
		if (empty($this->user_name)) {
			$this->user_name = $user->get('name');
			if (empty($this->user_name)) {
				$this->user_name = $user->get('username');
			}
		}
		
		return parent::store($updateNulls);
	}
}
