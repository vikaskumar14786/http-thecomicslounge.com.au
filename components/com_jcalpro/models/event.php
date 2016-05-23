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

JLoader::register('JCalProEventModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodelevent.php');

/**
 * Frontend event model
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelEvent extends JCalProEventModel
{
	public function getOrphanParent($id = 0) {
		static $orphans;
		if (is_null($orphans)) {
			$orphans = array();
		}
		$key = "orphan_$id";
		if (array_key_exists($key, $orphans)) {
			return $orphans[$key];
		}
		
		$id = (int) $id;
		$db = JFactory::getDbo();
		$db->setQuery("SELECT parent_id FROM #__jcalpro_event_xref WHERE child_id = $id");
		
		try {
			$orphans[$key] = (int) $db->loadResult();
		}
		catch (Exception $e) {
			$orphans[$key] = 0;
		}
		
		return $orphans[$key];
	}
}
