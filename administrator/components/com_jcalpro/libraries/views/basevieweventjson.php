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

jimport('joomla.application.component.view');
jimport('jcaldate.date');
jimport('jcaldate.timezone');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');
JLoader::register('JCalProJsonView', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/views/baseviewjson.php');
JLoader::register('JCalProHelperDate', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/date.php');

class JCalProEventJsonView extends JCalProJsonView
{
	function display($tpl = null, $safeparams = false) {
		$input = JFactory::getApplication()->input;
		$data  = array();
		switch ($input->get('layout', '', 'word')) {
			case 'checkdate':
				$year     = $input->get('year', 0, 'uint');
				$month    = $input->get('month', 0, 'uint');
				$day      = $input->get('day', 0, 'uint');
				$hour     = $input->get('hour', 0, 'uint');
				$minute   = $input->get('minute', 0, 'uint');
				$timezone = $input->get('timezone', JCalTimeZone::joomla(), 'string');
				try {
					$date = JCalProHelperDate::getDateTimeFromParts($hour, $minute, 0, $month, $day, $year, $timezone);
				}
				catch (Exception $e) {
					$this->_end(array('valid' => false, 'error' => $e->getMessage()));
					return false;
				}
				$parts = $date->toArray();
				if ($parts['day'] == $day && $parts['month'] == $month && $parts['year'] == $year) {
					$data['valid'] = true;
					$data['weekday'] = $date->weekday();
				}
				else {
					$data['valid'] = false;
					$data['error'] = JText::_('COM_JCALPRO_INVALID_DATE');
				}
				$data = array_merge($data, $parts);
				
				break;
			case 'catcounts':
				$rcats = $input->get('catids', array(), 'array');
				if (!empty($rcats)) {
					JArrayHelper::toInteger($rcats);
					$categories = array();
					JCalProBaseModel::addIncludePath(JPATH_ROOT . '/components/com_jcalpro/models');
					$catModel = JCalPro::getModelInstance('Categories', 'JCalProModel');
					$catModel->setState('filter.published', '');
					$cats = $catModel->getItems();
					foreach ($cats as $cat) {
						if (!in_array($cat->id, $rcats)) {
							continue;
						}
						$catObj = new stdClass();
						$catObj->id = $cat->id;
						$catObj->total_events = (int) (empty($cat->total_events) ? 0 : $cat->total_events);
						$catObj->upcoming_events = (int) (empty($cat->upcoming_events) ? 0 : $cat->upcoming_events);
						$catObj->color = $cat->params->get('jcalpro_color');
						$categories[] = $catObj;
					}
					$data['categories'] = $categories;
				}
			default:
				break;
		}
		$this->_data = $data;
		parent::display($tpl, $safeparams);
	}
}
