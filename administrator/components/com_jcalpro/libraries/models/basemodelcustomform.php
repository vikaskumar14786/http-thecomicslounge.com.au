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

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/helpers/jcalpro.php');
JLoader::register('JCalProHelperForm', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/form.php');
JLoader::register('JCalProAdminModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodeladmin.php');

/**
 * This model acts as a base for the custom form models
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProCustomFormModel extends JCalProAdminModel
{
	/**
	 * The category context (allows other extensions to derived from this model).
	 *
	 * @var		string
	 */
	protected $_extension = 'com_jcalpro';
	
	public $option = 'com_jcalpro';
	
	public function getCategories() {
		static $categories;
		if (!isset($categories)) {
			$categories = JCalPro::getModelInstance('Events', 'JCalProModel')->getCategories();
		}
		return $categories;
	}
	
	protected function addFieldsToForm(&$fields, &$form, $label) {
		// our custom fields should not have the following as extras
		$banned = array('name', 'type', 'default', 'label', 'description', 'class', 'classname');
		// we'll need to know if this form is for a new item or not
		// because the permissions check that we do during the loop
		// will be different based on this information
		$isNew = (0 == (int) $form->getValue('id'));
		$user  = JFactory::getUser();
		// now that we have the fields, we need to add them to the form
		// we do this by creating a new JXMLElement and passing it to JForm::load
		// go ahead and start creating our element
		// UPDATE: JXMLElement is deprecated
		/*if (JCalPro::version()->isCompatible('3.0')) {
			$xml = new SimpleXMLElement('<form></form>');
		}
		else {*/
			$xml = new JXMLElement('<form></form>');
		/*}*/
		// since these are custom fields, we're storing them in the params column of the events table
		// so we have to add a new "fields" element named "params" to hold them
		$xmlFields = $xml->addChild('fields');
		$xmlFields->addAttribute('name', 'params');
		// next up, we create a fieldset (and we'll name it based on the form)
		$xmlFieldset = $xmlFields->addChild('fieldset');
		$xmlFieldset->addAttribute('name', 'customfields');
		$xmlFieldset->addAttribute('label', $label);
		// we need to count how many fields are allowed
		// if all of these end up being removed due to permissions we get a blank fieldset
		$allowedFields = 0;
		// finally, we loop through each of our fields and create elements for them
		foreach ($fields as $field) {
			// is this field blocked?
			if (!$user->authorise('field.data.' . ($isNew ? 'create' : 'edit'), 'com_jcalpro.field.' . $field->id)) {
				continue;
			}
			$allowedFields++;
			// start our xml field
			$xmlField = $xmlFieldset->addChild('field');
			$xmlField->addAttribute('name', $field->name);
			$xmlField->addAttribute('type', $field->type);
			$xmlField->addAttribute('default', $field->default);
			$xmlField->addAttribute('label', $field->title);
			$xmlField->addAttribute('description', $field->description);
			// handle class
			if (array_key_exists('classname', $field->params) && !empty($field->params['classname'])) {
				$xmlField->addAttribute('class', $field->params['classname']);
			}
			// handle extra attributes
			if (array_key_exists('attrs', $field->params) && !empty($field->params['attrs']) && is_array($field->params['attrs'])) {
				// loop keys
				foreach ($field->params['attrs'] as $key => $value) {
					if (in_array($key, $banned)) {
						continue;
					}
					// set attribute
					$xmlField->addAttribute($key, $value);
				}
			}
			// handle options, if any
			if (array_key_exists('opts', $field->params) && !empty($field->params['opts']) && is_array($field->params['opts'])) {
				// loop keys
				foreach ($field->params['opts'] as $key => $value) {
					// set attribute
					$xmlOption = $xmlField->addChild('option', $key);
					$xmlOption->addAttribute('value', $value);
				}
			}
		}
		// if we have allowed fields, add them
		if (0 < $allowedFields) {
			// ok, we should have enough now to add to the form
			$form->load($xml, false);
			// we have to repopulate the form data now so our custom form fields get populated with data
			$formData = $this->loadFormData();
			$form->bind($formData);
		}
	}
}
