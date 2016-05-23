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

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');

abstract class JCalProHelperForm
{
	public static function getForm($name, $data, $asset = false) {
		// only load once
		static $loaded;
		if (is_null($loaded)) {
			jimport('joomla.form.form');
			JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/models/forms');
			$loaded = true;
		}
		// get our form
		// TODO pass more options?
		$form = JForm::getInstance($name, $data);
		// check the form
		if (!($form instanceof JForm)) {
			throw new Exception(JText::_('JERROR_NOT_A_FORM'));
		}
		if ($asset) {
			// get the asset data & bind it to the form
			$db = JFactory::getDbo();
			$db->setQuery($db->getQuery(true)
				->select('id, rules')
				->from('#__assets')
				->where('name = ' . $db->Quote($asset))
			);
			$rules = $db->loadObject();
			if (!empty($rules)) {
				$form->bind(array('asset_id' => $rules->id, 'rules' => $rules->rules));
			}
		}
		// all done - return form
		return $form;
	}
	
	
	public static function getFields($id) {
		static $collection;
		if (!is_array($collection)) {
			$collection = array();
		}
		if (!$id || -1 == $id) {
			return false;
		}
		if (!array_key_exists("key_$id", $collection)) {
			// go ahead and just load from the db
			$db = JFactory::getDbo();
			$fields = $db->setQuery($db->getQuery(true)
				->select('Field.*')
				->from('#__jcalpro_fields AS Field')
				->where('Field.published = 1')
				->group('Field.id')
				// join over Xref
				->leftJoin('#__jcalpro_form_fields AS Xref ON Xref.field_id = Field.id')
				->where('Xref.form_id = ' . (int) $id)
				->order('Xref.ordering ASC')
				// join over form just to ensure it's published
				->leftJoin('#__jcalpro_forms AS Form ON Xref.form_id = Form.id')
				->where('Form.published = 1')
			)->loadObjectList();
			if (!empty($fields)) {
				foreach ($fields as &$field) {
					$reg = new JRegistry;
					$reg->loadString($field->params);
					$field->params = $reg->toArray();
				}
			}
			$collection["key_$id"] = $fields;
		}
		return $collection["key_$id"];
	}
}
