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
JLoader::register('JCalProHelperTheme', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/theme.php');

class JFormFieldJCalTheme extends JFormField
{
	public $type = 'Jcaltheme';

	protected function getInput() {
		// get class for this element
		$class = $this->element['class'] ? ' class="'.(string) $this->element['class'].'"' : '';
		// get our list
		$list = JCalProHelperTheme::getList();
		// when this element is using in the component config, we just want a list
		// however, we need to make a special case for categories (since they can have their own)
		// check the element to see if "inherit" is set and if so, we'll add that option below
		if ($this->element['inherit']) {
			array_unshift($list, JHtml::_('select.option', '-1', JText::_('COM_JCALPRO_THEMES_INHERIT'), '_id', '_name'));
		}
    // send back our select list
    return JHtml::_('select.genericlist', $list, $this->name, $class . ' size="1"', '_id', '_name', $this->value);
	}
}
