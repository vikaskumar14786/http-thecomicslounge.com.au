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

JLoader::register('JCalPro', JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/jcalpro.php');
JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');

class JFormFieldJCalForm extends JFormField
{
	public $type = 'Jcalform';

	protected function getInput() {
		// get class for this element
		$class = $this->element['class'] ? ' class="'.(string) $this->element['class'].'"' : '';
		// get our form model
		JCalProBaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/models');
		$model = JCalPro::getModelInstance('Forms', 'JCalProModel');
		// fetch the list of available, published forms from the model
		$model->setState('filter.published', '1');
		$model->setState('filter.formtype', (string) $this->element['formtype']);
		$forms = $model->getItems();
		// list of available form types - right now we only have 2 :P
		$list = array();
		// inherit
		$list[] = JHtml::_('select.option', '', JText::_('COM_JCALPRO_FORM_OPTION_INHERIT'), '_id', '_name');
		// no form
		$list[] = JHtml::_('select.option', '-1', JText::_('COM_JCALPRO_FORM_OPTION_NONE'), '_id', '_name');
		// loop available forms & add to the list
		if (!empty($forms)) {
			foreach ($forms as $form) {
				$list[] = JHtml::_('select.option', $form->id, $form->title, '_id', '_name');
			}
		}
    // send back our select list
    return JHtml::_('select.genericlist', $list, $this->name, $class . ' size="1"', '_id', '_name', $this->value);
	}
}
