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

class JCalProTableField extends JCalProAssetTable
{
	public $id;
	public $type;
	public $title;
	public $description;
	public $default;
	public $params;
	public $created;
	public $created_by;
	public $modified;
	public $modified_by;
	public $published;
	public $checked_out;
	public $checked_out_time;

	function __construct(&$db) {
		parent::__construct('#__jcalpro_fields', 'id', $db);
	}

	protected function _getAssetName() {
		$k = $this->_tbl_key;
		return 'com_jcalpro.field.'.(int) $this->$k;
	}

	protected function _getAssetTitle() {
		return $this->title;
	}
	
	protected function _compat_getAssetParentId($table = null, $id = null) {
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_jcalpro.fields');
		if (empty($asset->id))
		{
			JCalPro::registerHelper('access');
			JCalProHelperAccess::saveRules('fields', array('core.dummy' => array()), false);
			$asset->loadByName('com_jcalpro.fields');
		}
		return $asset->id;
	}
	
	/**
	 * Overload the bind method to store params
	 * 
	 * @param $array
	 * @param $ignore
	 */
	function bind($array, $ignore = '') {
		if (array_key_exists('params', $array) && is_array($array['params'])) {
			// we have to do some extra crap here, to avoid a lot of extra coding in other parts of the application
			// due to the way the UI is coded (can't be helped without massive amounts of js)
			// we get a pretty funky array for attrs and opts
			// to "fix" this, we convert those here into simple key => value pairs
			// instead of having 2 arrays, one for keys & one for values
			// now, we could very well use php functions to accomplish this, but we want to ensure
			// that the data is preserved correctly for later use
			foreach (array('attrs', 'opts') as $param) {
				// make sure that this key exists & isn't empty
				if (!array_key_exists($param, $array['params']) || empty($array['params'][$param])) continue;
				// shortvar for this array
				$parray = &$array['params'][$param];
				// make sure the array is actually an array
				if (!is_array($parray)) continue;
				// this array stores the actual values
				$realValues = array();
				// make sure we have the necessary keys
				if (array_key_exists('key', $parray) && !empty($parray['key']) && array_key_exists('value', $parray)) {
					foreach ($parray['key'] as $i => $key) {
						// make sure we have a corresponding value
						if (!array_key_exists($i, $parray['value'])) continue;
						$value = $parray['value'][$i];
						// if key is empty, bail
						if ('' === $key) continue;
						// woohoo! nailed it! add it to our array
						$realValues[$key] = $value;
					}
				}
				// reset the array
				$parray = $realValues;
			}
			// create registry & convert to string
			$registry = new JRegistry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}
		// Attempt to bind the data.
		return parent::bind($array, $ignore);
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
		
		// Attempt to store the user data.
		return parent::store($updateNulls);
	}
}
