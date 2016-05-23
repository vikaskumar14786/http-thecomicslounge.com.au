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
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');

/**
 * This is a base class for backwards compat
 */
class JCalProBaseAssetTable extends JTable
{
	public $asset_id;
	
	/**
	 * Our compat method
	 *
	 * @param unknown_type $table
	 * @param unknown_type $id
	 */
	protected function _compat_getAssetParentId($table = null, $id = null) {
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_jcalpro');
		return $asset->id;
	}
}

/**
 * Declare the shim class that defines _getAssetParentId in different ways based on version
 *
 */
if (JCalPro::version()->isCompatible('3.2.0'))
{
	class JCalProAssetTable extends JCalProBaseAssetTable
	{
		protected function _getAssetParentId(JTable $table = null, $id = null)
		{
			return $this->_compat_getAssetParentId($table, $id);
		}
	}
}
else
{
	class JCalProAssetTable extends JCalProBaseAssetTable
	{
		protected function _getAssetParentId($table = null, $id = null)
		{
			return $this->_compat_getAssetParentId($table, $id);
		}
	}
}
