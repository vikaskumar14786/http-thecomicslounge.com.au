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

JLoader::register('JCalProView', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/views/baseview.php');

class JCalProItemView extends JCalProView
{
	private $script = '';
	
	function display($tpl = null, $safeparams = false) {
		$form = $this->get('Form');
		$item = $this->get('Item');
//		$script = $this->get('Script');
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		// quickfix
		if (is_object($item) && !property_exists($item, 'id')) {
			$item->id = 0;
		}
		// Assign the Data
		$this->form = $form;
		$this->item = $item;
//		$this->script = $script;
		$this->canDo = JCalPro::getActions();
		$this->addToolBar();
		
		JCalPro::debugger('Item', $this->item);
		JCalPro::debugger('Form', $this->form);
		
		parent::display($tpl, $safeparams);
		$this->setDocument();
	}

	public function addToolBar() {
		// only fire in administrator
		$app = JFactory::getApplication();
		if (!$app->isAdmin()) {
			return;
		}
		$app->input->set('hidemainmenu', true);
		$user = JFactory::getUser();
		$userId = $user->id;
		$isNew = (@$this->item->id == 0);
		$checkedOut = false;
		if ($this->item && property_exists($this->item, 'checked_out')) {
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
		}
		JCalPro::registerHelper('access');
		$canDo = JCalProHelperAccess::getActions();

		JToolBarHelper::title(JText::_(strtoupper(JCalPro::COM) . '_' . strtoupper($this->_name) . '_MANAGER_' . ($checkedOut ? 'VIEW' : ($isNew ? 'ADD' : 'EDIT'))), 'jcalpro-'.strtolower($this->_name));

		if ($isNew) {
			if ($canDo->get('core.create')) {
				JToolBarHelper::apply(strtolower($this->_name).'.apply', 'JTOOLBAR_APPLY');
				JToolBarHelper::save(strtolower($this->_name).'.save', 'JTOOLBAR_SAVE');
				JToolBarHelper::custom(strtolower($this->_name).'.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
			}
			JToolBarHelper::cancel(strtolower($this->_name).'.cancel', 'JTOOLBAR_CANCEL');
		} else {
			// Can't save the record if it's checked out.
			if (!$checkedOut) {
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
					JToolBarHelper::apply(strtolower($this->_name).'.apply', 'JTOOLBAR_APPLY');
					JToolBarHelper::save(strtolower($this->_name).'.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create')) {
						JToolBarHelper::custom(strtolower($this->_name).'.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
					}
				}
			}

			// If checked out, we can still save
			if ($canDo->get('core.create')) {
				JToolBarHelper::custom(strtolower($this->_name).'.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
			}
			JToolBarHelper::cancel(strtolower($this->_name).'.cancel', 'JTOOLBAR_CLOSE');
		}
	}

	public function setDocument() {
		jimport('joomla.filesystem.file');
		$isNew = ($this->item->id < 1);
		$document = JFactory::getDocument();
		$document->setTitle(JText::_(strtoupper(JCalPro::COM).'_'.strtoupper($this->_name).'_'.($isNew ? 'CREATING' : 'EDITING')));
		if (isset(self::$script) && !empty(self::$script) && JFile::exists(JPATH_ROOT . self::$script)) {
			$document->addScript(JURI::root() . self::$script);
		}
		$submitbutton = "/administrator/components/" . strtolower(JCalPro::COM) . "/views/".strtolower($this->_name)."/submitbutton.js";
		if (JFile::exists(JPATH_ROOT . $submitbutton)) {
			$document->addScript(JURI::root() . $submitbutton);
		}
		if (!$this->tpl && JCalPro::version()->isCompatible('3.0.0')) {
			JHtml::_('formbehavior.chosen', 'select');
		}
	}
}
