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
JFormHelper::loadFieldClass('list');

JHtml::addIncludePath(JPATH_ROOT . '/components/com_jcalpro/helpers/html');
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');

class JFormFieldJCalMultiCat extends JFormFieldList
{
	public $type = 'Jcalmulticat';
	
	protected function getOptions() {
		// get the canonical category
		$canonical = (int) $this->form->getValue('canonical');
		// get our category options
		$options = JHtml::_('jcalpro.calendarlistoptions', array(), false, true);
		// disable canonical
		foreach ($options as &$option) {
			if ($option->value != $canonical) continue;
			$option->disable = true;
			// check for the canonical in the value
			$value = $this->value;
			if (is_string($value)) $value = explode('|', $value);
			if (!empty($value)) {
				foreach ($value as $k => $v) if ($v == $canonical) unset($value[$k]);
				$value = array_values($value);
			}
			$this->value = $value;
			// we found the canonical category - quit looking
			break;
		}
		return $options;
	}
}