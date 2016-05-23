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
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('form');
JCalPro::registerHelper('url');

class JCalProViewRegistrations extends JCalProListView
{
	/**
	 * Default sorting column
	 * 
	 * @var string
	 */
	protected $_sortColumn = 'Event.start_date';
	
	function display($tpl = null, $safeparams = false) {
		try {
			$this->form = JCalProHelperForm::getForm(JCalPro::COM . '.registrations', 'registrations', JCalPro::COM . '.registrations');
		}
		catch (Exception $e) {
			JError::raiseError(500, $e->getMessage());
			jexit();
		}
		parent::display($tpl, $safeparams);
	}
	
	public function addToolBar() {
		// add our export buttons
		JToolBarHelper::custom('registrations.export','export.png','export.png','JTOOLBAR_EXPORT', false);
		JToolBarHelper::divider();
		// this is copy/pasted from the base view list, as we cannot use the base here
		// if we do, we end up with "(Un)Published" instead of "(Un)Confirmed"
		$single = preg_replace('/s$/', '', $this->_name);
		// set the toolbar title
		JToolBarHelper::title(JText::_(strtoupper(JCalPro::COM . '_' . $this->_name . '_MANAGER')), 'jcalpro-'.strtolower($this->_name));
		if (JFactory::getUser()->authorise('core.create')) {
			JToolBarHelper::addNew($single . '.add', 'JTOOLBAR_NEW');
		}
		if (JFactory::getUser()->authorise('core.edit') || JFactory::getUser()->authorise('core.edit.own')) {
			JToolBarHelper::editList($single . '.edit', 'JTOOLBAR_EDIT');
			JToolBarHelper::divider();
		}
		if (JFactory::getUser()->authorise('core.edit.state')) {
			JToolBarHelper::publish($this->_name . '.publish', JCalPro::COM . '_REGISTRATIONS_CONFIRM', true);
			JToolBarHelper::unpublish($this->_name . '.unpublish', JCalPro::COM . '_REGISTRATIONS_UNCONFIRM', true);
			JToolBarHelper::checkin($this->_name . '.checkin');
			JToolBarHelper::divider();
		}
		if ($this->state->get('filter.published') == -2 && JFactory::getUser()->authorise('core.delete', JCalPro::COM)) {
			JToolBarHelper::deleteList('', $this->_name . '.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		else if (JFactory::getUser()->authorise('core.edit.state')) {
			JToolBarHelper::trash($this->_name . '.trash');
			JToolBarHelper::divider();
		}
		// from the base view
		if (JFactory::getUser()->authorise('core.manage', JCalPro::COM)) {
			JToolBarHelper::preferences(JCalPro::COM);
		}
		
		JToolBarHelper::divider();
		// help!!!
		JToolBarHelper::help(JCalPro::COM . '_HELP', false, JCalProHelperUrl::help());
	}
	
	/**
	 * Returns an array of fields the table can be sorted by
	 * 
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields() {
		return array(
			'Registration.altname'    => JText::_('COM_JCALPRO_ALTNAME')
		,	'Registration.event_id'   => JText::_('COM_JCALPRO_EVENT')
		,	'Event.start_date'        => JText::_('COM_JCALPRO_START_DATE')
		,	'Registration.created_by' => JText::_('COM_JCALPRO_CREATED_BY')
		,	'Registration.published'  => JText::_('COM_JCALPRO_CONFIRMED')
		);
	}
}