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

class JFormFieldJCalFormType extends JFormField
{
	public $type = 'Jcalformtype';

	protected function getInput() {
		// get class for this element
		$class    = 'class="' . ($this->element['class'] ? (string) $this->element['class'] : '') . ' jcalformtype"';
		$allvalue = $this->element['allvalue'] ? (string) $this->element['allvalue'] : '';
		$alltext  = $this->element['alltext'] ? JText::_((string) $this->element['alltext']) : JText::_('COM_JCALPRO_FORM_TYPE_OPTION_NONE');
		
		JFactory::getDocument()->addScript(rtrim(JUri::root(), '/') . '/media/jcalpro/js/formtype.js');
		
		// list of available form types - right now we only have 2 :P
		$list = array();
		// no type (BAD)
		$list[] = JHtml::_('select.option', $allvalue, $alltext, '_id', '_name');
		// event form type
		$list[] = JHtml::_('select.option', 0, JText::_('COM_JCALPRO_FORM_TYPE_OPTION_EVENT'), '_id', '_name');
		// registration form type
		$list[] = JHtml::_('select.option', 1, JText::_('COM_JCALPRO_FORM_TYPE_OPTION_REGISTRATION'), '_id', '_name');
    // send back our select list
    return JHtml::_('select.genericlist', $list, $this->name, $class . ' size="1"', '_id', '_name', $this->value);
	}
}
