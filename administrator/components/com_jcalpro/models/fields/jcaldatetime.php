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

jimport('joomla.form.formfield');
jimport('joomla.form.helper');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JLoader::register('JCalProHelperDate', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/date.php');

/**
 * this form field displays several different elements:
 * + day select
 * + month select
 * + year select
 * + hour select
 * + minute select
 */
class JFormFieldJCalDateTime extends JFormField
{
	public $type = 'Jcaldatetime';

	protected function getInput() {
		// we need to go ahead and get the date for today
		$today = JCalProHelperDate::getTodayTime();
		// get class for this element
		$class = ' class="jcaldatetime-select input-small ' . ($this->element['class'] ? (string) $this->element['class'] : '') . ' %s"';
		// excludes, if any
		$excludes = $this->element['excludes'] ? explode(',', (string) $this->element['excludes']) : array();
		// handle value, if empty
		if (empty($this->value)) {
			// load the date from the request as a DateTime in user time then set the hour based on today
			$this->value = JCalProHelperDate::getDate()->toUser()->toDayStart()->toHour($today->hour())->toArray();
		}
		// check if value is an object - if it is, convert it
		if (is_object($this->value)) {
			$this->value = JArrayHelper::fromObject($this->value);
		}
		// initialize our parts
		$day    = '';
		$month  = '';
		$year   = '';
		$hour   = '';
		$minute = '';
		// build the day select
		if (!in_array('day', $excludes)) {
			$day = $this->_getRangedList($this->name . '[day]', $this->value['day'], 1, 31, false, 'jcaldatetime-select-day');
		}
		// build the month select
		if (!in_array('month', $excludes)) {
			$month = JHtml::_('select.genericlist', array(
				JHtml::_('select.option',  1, JText::_('JANUARY'),   'month_id', 'month_name')
			,	JHtml::_('select.option',  2, JText::_('FEBRUARY'),  'month_id', 'month_name')
			,	JHtml::_('select.option',  3, JText::_('MARCH'),     'month_id', 'month_name')
			,	JHtml::_('select.option',  4, JText::_('APRIL'),     'month_id', 'month_name')
			,	JHtml::_('select.option',  5, JText::_('MAY'),       'month_id', 'month_name')
			,	JHtml::_('select.option',  6, JText::_('JUNE'),      'month_id', 'month_name')
			,	JHtml::_('select.option',  7, JText::_('JULY'),      'month_id', 'month_name')
			,	JHtml::_('select.option',  8, JText::_('AUGUST'),    'month_id', 'month_name')
			,	JHtml::_('select.option',  9, JText::_('SEPTEMBER'), 'month_id', 'month_name')
			,	JHtml::_('select.option', 10, JText::_('OCTOBER'),   'month_id', 'month_name')
			,	JHtml::_('select.option', 11, JText::_('NOVEMBER'),  'month_id', 'month_name')
			,	JHtml::_('select.option', 12, JText::_('DECEMBER'),  'month_id', 'month_name')
			), $this->name . '[month]', sprintf($class, 'jcaldatetime-select-month'), 'month_id', 'month_name', $this->value['month']);
		}
		// build the year select
		if (!in_array('year', $excludes)) {
			$year = $this->_getRangedList($this->name . '[year]', $this->value['year'], $today->year() - 5, $today->year() + 20, false, 'jcaldatetime-select-year');
		}
		// build the hour select
		if (!in_array('hour', $excludes)) {
			$hourlist = array();
			for ($i = 0; $i <= 23; $i++) {
				// find display value depending on setting :
				if (JCalPro::config('time_format_24hours', 0)) {
					// 24 hours
					$displayItem = sprintf("%02d", $i);
					$classSfx    = '24';
				} else {
					// amp/pm
					if ($i == 0) {
						$displayItem = '00 '. JText::_('COM_JCALPRO_MIDNIGHT');
					} else if ($i == 12) {
						$displayItem = '12 '. JText::_('COM_JCALPRO_NOON');
					} else if ($i > 0 && $i < 12) {
						$displayItem = sprintf("%02d",$i) . ' ' . JText::_('COM_JCALPRO_AM');
					} else {
						$displayItem = sprintf("%02d",$i - 12) . ' ' . JText::_('COM_JCALPRO_PM');
					}
					$classSfx = '12';
				}
				$hourlist[] = JHtml::_('select.option', $i, $displayItem, 'hour_id', 'hour_name');
			}
			$hour = JHtml::_('select.genericlist', $hourlist, $this->name . '[hour]', sprintf($class, 'jcaldatetime-select-hour-' . $classSfx) . ' size="1"', 'hour_id', 'hour_name', $this->value['hour']);
		}
		// build the minutes select
		if (!in_array('minute', $excludes)) {
			$minute = $this->_getRangedList($this->name . '[minute]', $this->value['minute'], 0, 59, 'left', 'jcaldatetime-select-minute');
		}
		// put it all together & return
		$hourminute = $hour . $minute;
		return $day . $month . $year . (empty($hourminute) ? '' : '<span class="jcaldatetime_at">' . JText::_('COM_JCALPRO_DATETIME_AT')) . '</span>' . $hourminute;
	}
	
	private function _getRangedList($name, $value, $start = 0, $end = 0, $padding = false, $extraClass = '') {
		
		$list   = array();
		$start  = intval($start ? $start : 0);
		$end    = intval($end   ? $end   : 0);
		$class  = ' class="jcaldatetime-select input-small ' . ($this->element['class'] ? (string) $this->element['class'] : '') . ' %s"';
		$pad    = strlen("$end");
		
		for ($i = $start; $i <= $end; $i++) {
			$text = "$i";
			if ($pad && $padding) {
				if ('left' == strtolower(trim($padding))) {
					$text = str_pad($text, $pad, '0', STR_PAD_LEFT);
				}
				else if ('both' == strtolower(trim($padding))) {
					$text = str_pad($text, $pad, '0', STR_PAD_BOTH);
				}
				else {
					$text = str_pad($text, $pad, '0');
				}
			}
			$list[] = JHtml::_('select.option', $i, $text, 'range_id', 'range_name');
		}
		return JHtml::_('select.genericlist', $list, $name, sprintf($class, $extraClass), 'range_id', 'range_name', $value);
		
	}
}
