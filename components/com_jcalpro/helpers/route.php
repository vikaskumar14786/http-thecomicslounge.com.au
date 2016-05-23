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

JLoader::register('JCalPro', JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('url');

abstract class JCalProHelperRoute
{
	public static function getEventRoute($id, $catid = 0, $language = 0) {
		$extra = array();
		if ($catid) {
			$extra['catid'] = $catid;
		}
		if ($language && '*' != $language) {
			$extra['lang'] = $language;
		}
		return JCalProHelperUrl::event($id, true, $extra);
	}
	
	public static function getCategoryRoute($id, $language = 0) {
		$extra = array();
		if ($language && '*' != $language) {
			$extra['lang'] = $language;
		}
		return JCalProHelperUrl::category($id, true, $extra);
	}
}
