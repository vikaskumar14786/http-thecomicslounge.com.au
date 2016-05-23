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
JLoader::register('JCalProAdminModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodeladmin.php');

/**
 * This models supports retrieving lists of forms.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelForm extends JCalProAdminModel
{
	public function getForm($data = array(), $loadData = true) {
		// Get the form.
		$form = $this->loadForm($this->option.'.'.$this->name, $this->name, array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}
		if (!JCalPro::version()->isCompatible('3.0')) {
			$form->setFieldAttribute('formfields', 'type', 'jcalformfields');
		}
		return $form;
	}
	
	/**
	 * Method to override the parent getItem method to add the field xref data
	 */
	public function getItem($id = null) {
		// load the item
		$item = parent::getItem($id);
		// we may not have a form
		if ($item) {
			// initialize the database object
			$db = JFactory::getDbo();
			// load the data from the xref table from the database 
			$db->setQuery('SELECT CAST(GROUP_CONCAT(field_id ORDER BY ordering ASC SEPARATOR "|") AS CHAR) AS fields FROM #__jcalpro_form_fields WHERE form_id = ' . intval($item->id) . ' GROUP BY form_id');
			$fields = $db->loadResult();
			if (empty($fields)) $fields = '';
			// append fields to the item
			$item->formfields = $fields;
		}
		// return the item
		return $item;
	}
	
	public function setDefault($id = 0) {
		// Initialise variables.
		$user = JFactory::getUser();
		$db   = $this->getDbo();
		
		// Access checks.
		if (!$user->authorise('core.edit.state', 'com_jcalpro')) {
			throw new Exception(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
		}
		
		$table = JTable::getInstance('Form','JCalProTable');
		if (!$table->load((int)$id)) {
			throw new Exception(JText::_('COM_JCALPRO_ERROR_FORM_NOT_FOUND'));
		}
		
		// Reset the default field
		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__jcalpro_forms'))
				->set($db->quoteName('default') . ' = 0')
				->where($db->quoteName('type') . ' = ' . $db->quote($table->type))
				->where($db->quoteName('default') . ' = 1')
		);
		
		try {
			if (!$db->query()) {
				throw new Exception($db->getErrorMsg());
			}
		}
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		// Set the new default form
		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__jcalpro_forms'))
				->set($db->quoteName('default') . ' = 1')
				->where($db->quoteName('id') . ' = ' . (int) $id)
		);
		
		try {
			if (!$db->query()) {
				throw new Exception($db->getErrorMsg());
			}
		}
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		// Clean the cache.
		$this->cleanCache();
		
		return true;
	}
	
	public function unsetDefault($id = 0) {
		// Initialise variables.
		$user = JFactory::getUser();
		$db   = $this->getDbo();
		
		// Access checks.
		if (!$user->authorise('core.edit.state', 'com_jcalpro')) {
			throw new Exception(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
		}
		
		$table = JTable::getInstance('Form','JCalProTable');
		if (!$table->load((int)$id)) {
			throw new Exception(JText::_('COM_JCALPRO_ERROR_FORM_NOT_FOUND'));
		}
		
		// Set the new default form
		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__jcalpro_forms'))
				->set($db->quoteName('default') . ' = 0')
				->where($db->quoteName('id') . ' = ' . (int) $id)
		);
		
		try {
			if (!$db->query()) {
				throw new Exception($db->getErrorMsg());
			}
		}
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		// Clean the cache.
		$this->cleanCache();
		
		return true;
	}
}
