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

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('text');

/**
 * User Text class for JCalPro.
 *
 */
class JFormFieldJCalUserText extends JFormFieldText
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	protected $type = 'Jcalusertext';
	
	protected function getInput() {
		if (empty($this->value)) {
			// add the refresh script
			// TODO
			//JFactory::getDocument()->addScript();
			// if this is hooked to a field, we need to know which one & get the value
			$field = $this->element['field'] ? (string) $this->element['field'] : '';
			$user = false;
			if (!empty($field)) {
				$user_id = $this->form->getValue($field);
				if ($user_id) {
					$user = JFactory::getUser($user_id);
				}
			}
			if (!$user) {
				$user = JFactory::getUser();
			}
			$column = $this->element['column'] ? (string) $this->element['column'] : '';
			if (!empty($column)) {
				$fields = (false !== strpos($column, '|') ? explode('|', $column) : array($column));
				foreach ($fields as $field) {
					if (!property_exists($user, $field)) continue;
					$data = $user->{$field};
					if (!empty($data)) {
						$this->value = $data;
						break;
					}
				}
			}
		}
		return parent::getInput();
	}
}