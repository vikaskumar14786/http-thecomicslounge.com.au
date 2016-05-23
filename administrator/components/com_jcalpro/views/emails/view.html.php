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

JLoader::register('JCalProListView', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/views/baseviewlist.php');

class JCalProViewEmails extends JCalProListView
{
	/**
	 * Default sorting column
	 * 
	 * @var string
	 */
	protected $_sortColumn = 'Email.subject';
	
	public function addToolBar() {
		// only fire in administrator
		if (!JFactory::getApplication()->isAdmin()) return;
		// yuk - fix this later :P
		// TAKE NOTE: so far all the views in JCalPro3 have single names that are pluralized by adding an 's'
		// except for categories, which is handled by core
		// if this ever changes, this needs to be replaced with more comprehensive inflector code
		$single = preg_replace('/s$/', '', $this->_name);
		// set the toolbar title
		JToolBarHelper::title(JText::_(strtoupper(JCalPro::COM.'_'.$this->_name.'_MANAGER')), 'jcalpro-'.strtolower($this->_name));
		if (JFactory::getUser()->authorise('core.create')) {
			JToolBarHelper::addNew($single . '.add', 'JTOOLBAR_NEW');
		}
		if (JFactory::getUser()->authorise('core.edit') || JFactory::getUser()->authorise('core.edit.own')) {
			JToolBarHelper::editList($single . '.edit', 'JTOOLBAR_EDIT');
			JToolBarHelper::divider();
		}
		if (JFactory::getUser()->authorise('core.edit.state')) {
			JToolBarHelper::checkin($this->_name . '.checkin');
			JToolBarHelper::divider();
		}
		if ($this->state->get('filter.published') == -2 && JFactory::getUser()->authorise('core.delete', K)) {
			JToolBarHelper::deleteList('', $this->_name . '.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		else if (JFactory::getUser()->authorise('core.edit.state')) {
			JToolBarHelper::trash($this->_name . '.trash');
			JToolBarHelper::divider();
		}
		// add parent toolbar
		JCalProView::addToolBar();
	}
	
	/**
	 * Returns an array of fields the table can be sorted by
	 * 
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields() {
		return array(
			'Email.subject'   => JText::_('COM_JCALPRO_EMAIL_SUBJECT')
		,	'Email.context'   => JText::_('COM_JCALPRO_EMAIL_CONTEXT')
		,	'Email.language'  => JText::_('JFIELD_LANGUAGE_LABEL')
		,	'Email.default'   => JText::_('COM_JCALPRO_DEFAULT')
		,	'Form.created_by' => JText::_('COM_JCALPRO_CREATED_BY')
		);
	}
}
