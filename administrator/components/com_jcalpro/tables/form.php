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

class JCalProTableForm extends JCalProAssetTable
{
	public $id;
	public $type;
	public $title;
	public $created;
	public $created_by;
	public $modified;
	public $modified_by;
	public $published;
	public $checked_out;
	public $checked_out_time;
	public $default;
	
	private $_formfields = null;

	function __construct(&$db) {
		parent::__construct('#__jcalpro_forms', 'id', $db);
	}

	protected function _getAssetName() {
		$k = $this->_tbl_key;
		return 'com_jcalpro.form.'.(int) $this->$k;
	}

	protected function _getAssetTitle() {
		return $this->title;
	}
	
	protected function _compat_getAssetParentId($table = null, $id = null) {
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_jcalpro.forms');
		if (empty($asset->id))
		{
			JCalPro::registerHelper('access');
			JCalProHelperAccess::saveRules('forms', array('core.dummy' => array()), false);
			$asset->loadByName('com_jcalpro.forms');
		}
		return $asset->id;
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
		
		// check if there are any defaults already for this type of form
		// if not, set this one as default
		$this->_db->setQuery($this->_db->getQuery(true)
			->select('id')
			->from($this->_tbl)
			->where($this->_db->quoteName('default') . ' = 1')
			->where($this->_db->quoteName('type') . ' = ' . (int) $this->type)
		);
		if (!$this->_db->loadResult()) {
			$this->default = 1;
		}
		
		// force formfields
		if (property_exists($this, 'formfields')) {
			$this->_formfields = $this->formfields;
			unset($this->formfields);
		}
		
		// go ahead and store now, as we'll need it later
		$store = parent::store($updateNulls);
		// handle xref but only if we have an id already ;)
		// if store was successful, there should now be an assigned id
		if ($store) {
			if (empty($this->_formfields)) {
				// go ahead and fetch these from the request
				// we currently have no need to get this data from anywhere else
				// but eventually we may need to
				// unfortunately, we have to extract this data from JForm, so we have to fetch via JForm array
				$jform = JFactory::getApplication()->input->get('jform', array(), null);
				// if for some dumb reason jform isn't an array we should account for this
				// as well, this variable may not be set
				if (is_array($jform) && array_key_exists('formfields', $jform)) {
					// we may receive an array from the request
					// this is doubtful, but let's go ahead and account for it anyways
					$this->_formfields = $jform['formfields'];
				}
				else {
					return $store;
				}
			}
			// we want the formfields value to be a string
			if (is_array($this->_formfields)) {
				$this->_formfields = implode('|', $this->_formfields);
			}
			// account for non-string variables by making it empty,
			// but only if the passed variable cannot be converted to a string
			else if (!is_string($this->_formfields)) {
				try {
					$this->_formfields = (string) $this->_formfields;
				}
				catch (Exception $e) {
					// ouch, failed converting to string - just make it blank
					$this->_formfields = '';
				}
			}
			// now we need to convert our string back into an array and inject the records
			$formfields = explode('|', $this->_formfields);
			if (!empty($formfields)) {
				// go ahead and purge the existing records
				$this->_db->setQuery($this->_db->getQuery(true)
					->delete('#__jcalpro_form_fields')
					->where('form_id=' . intval($this->id))
				)->query();
				// go ahead & force our fields to be integers, unique, and only values
				JArrayHelper::toInteger($formfields);
				$formfields = array_unique($formfields);
				$formfields = array_values($formfields);
				$insert     = $this->_db->getQuery(true)
					->insert('#__jcalpro_form_fields')
					->columns(array('form_id', 'field_id', 'ordering'))
				;
				$query      = false;
				// walk the array and convert to INSERT snippets
				// we're using for() instead of foreach() here so we have proper ordering :)
				for ($i = 0; $i < count($formfields); $i++) {
					$query = true;
					$insert->values(intval($this->id) . ", " . $formfields[$i] . ", $i");
				}
				if ($query) {
					// inject the new records
					$this->_db->setQuery($insert)->query();
				}
			}
		}
		// return the store value
		return $store;
	}
	
	public function bind($array, $ignore = '') {
		// make sure fields get set
		if (array_key_exists('formfields', $array)) {
			$this->_formfields = $array['formfields'];
			unset($array['formfields']);
		}
		return parent::bind($array, $ignore);
	}
	
}
