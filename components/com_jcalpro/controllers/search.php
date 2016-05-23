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

JLoader::register('JCalProBaseController', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/controllers/basecontroller.php');
JLoader::register('JCalProHelperUrl', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/url.php');

/**
 * JCalPro Search Controller
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProControllerSearch extends JCalProBaseController
{
	public function search() {
		$app = JFactory::getApplication();
		// start building our extra variable array
		$extra = array();
		// fetch the searchword
		$searchword = trim($app->input->get('searchword', '', 'string'));
		// add the searchword to our extra variable array if it exists
		if (!empty($searchword)) {
			$extra['searchword'] = rawurldecode($searchword);
		}
		// fetch the Itemid & add that too
		$extra['Itemid'] = JCalProHelperUrl::findItemid();
		// redirect
		$app->redirect(JCalProHelperUrl::view('search', true, $extra));
		jexit();
	}
}