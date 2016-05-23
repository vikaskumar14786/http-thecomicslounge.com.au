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

class JCalProListView extends JCalProView
{
	
	protected $items;
	protected $pagination;
	protected $state;
	
	/**
	 * Default sorting column
	 * 
	 * @var string
	 */
	protected $_sortColumn = false;

	function display($tpl = null, $safeparams = false) {
		$items      = $this->get('Items');
		$pagination = $this->get('Pagination');
		$state      = $this->get('State');
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		$this->items      = $items;
		$this->pagination = $pagination;
		$this->state      = $state;
		
		$published = $this->get('PublishedStatus');
		if (is_array($published) && 1 < count($published)) {
			$this->addFilter(JText::_('COM_JCALPRO_SELECT_PUBLISHED'), 'filter_published', $published, $state->get('filter.published'));
		}
		
		$this->addToolBar();
		$this->addMenuBar();
		
		JCalPro::debugger('Items', $this->items);
		JCalPro::debugger('State', $this->state);
		JCalPro::debugger('Pagination', $this->pagination);
		
		parent::display($tpl, $safeparams);
	}
	
	public function addFilter($label, $name, $options, $default) {
		$filter = new stdClass;
		$filter->label   = $label;
		$filter->name    = $name;
		$filter->options = $options;
		$filter->default = $default;
		if (!is_array($this->_filters)) $this->_filters = array();
		return $this->_filters[] = $filter;
	}
	
	public function renderFilters() {
		if (empty($this->_filters)) return;
		if (JCalPro::version()->isCompatible('3.0')) {
			foreach ($this->_filters as $filter) {
				array_shift($filter->options);
				$options = JHtml::_('select.options', $filter->options, 'value', 'text', $filter->default, true);
				JSubMenuHelper::addFilter($filter->label, $filter->name, $options);
			}
			return;
		}
		foreach ($this->_filters as $filter) {
			$this->currentFilter = JHtml::_('select.genericlist', $filter->options, $filter->name, sprintf('id="%s" class="listbox" onchange="this.form.submit()"', $filter->name), 'value', 'text', $filter->default);
			echo $this->loadTemplate('filter');
		}
	}

	public function addToolBar() {
		// only fire in administrator
		if (!JFactory::getApplication()->isAdmin()) {
			return;
		}
		// see if we need to filter by category
		$catid = null;
		try {
			$state = $this->get('State');
			if (is_object($state)) {
				$catid = $state->get('filter.catid', null);
			}
		} catch (Exception $ex) {
			// force catid
			$catid = null;
		}
		// get the user permissions
		JCalPro::registerHelper('access');
		$canDoCom  = JCalProHelperAccess::getActions();
		$canDoView = JCalProHelperAccess::getActions($this->_name);
		$canDoCat  = JCalProHelperAccess::getActions($catid);
		
		$canCreate = $canDoCom->get('core.create') || $canDoView->get('core.create') || $canDoCat->get('core.create');
		$canEdit   = $canDoCom->get('core.edit') || $canDoCom->get('core.edit.own') || $canDoView->get('core.edit') || $canDoView->get('core.edit.own') || $canDoCat->get('core.edit') || $canDoCat->get('core.edit.own');
		$canChange = $canDoCom->get('core.edit.state') || $canDoView->get('core.edit.state') || $canDoCat->get('core.edit.state');
		$canDelete = $canDoCom->get('core.delete') || $canDoView->get('core.delete') || $canDoCat->get('core.delete');
		// yuk - fix this later :P
		// TAKE NOTE: so far all the views in JCalPro3 have single names that are pluralized by adding an 's'
		// except for categories, which is handled by core
		// if this ever changes, this needs to be replaced with more comprehensive inflector code
		$single = preg_replace('/s$/', '', $this->_name);
		// set the toolbar title
		JToolBarHelper::title(JText::_(strtoupper(JCalPro::COM.'_'.$this->_name.'_MANAGER')), 'jcalpro-'.strtolower($this->_name));
		if ($canCreate) {
			JToolBarHelper::addNew($single . '.add', 'JTOOLBAR_NEW');
		}
		if ($canEdit) {
			JToolBarHelper::editList($single . '.edit', 'JTOOLBAR_EDIT');
			JToolBarHelper::divider();
		}
		if ($canChange) {
			JToolBarHelper::publish($this->_name . '.publish', 'JTOOLBAR_PUBLISH', true);
			JToolBarHelper::unpublish($this->_name . '.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolBarHelper::checkin($this->_name . '.checkin');
			JToolBarHelper::divider();
		}
		if ($this->state->get('filter.published') == -2 && $canDelete) {
			JToolBarHelper::deleteList('', $this->_name . '.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		else if ($canChange) {
			JToolBarHelper::trash($this->_name . '.trash');
			JToolBarHelper::divider();
		}
		// add parent toolbar
		parent::addToolBar();
	}

	/**
	 * Prepares the document
	 */
	protected function _prepareDocument() {
		parent::_prepareDocument();
		
		if (JCalPro::version()->isCompatible('3.0.0')) {
			JHtml::_('formbehavior.chosen', 'select');
		}
	}
	
	/**
	 * Returns an array of fields the table can be sorted by
	 * 
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields() {
		return array();
	}
	
	public function getDefaultOrdering() {
		return $this->_sortColumn;
	}
}
