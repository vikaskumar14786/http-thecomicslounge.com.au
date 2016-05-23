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

jimport('joomla.application.component.view');
JLoader::register('JCalProView', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/views/baseview.php');

/**
 * JCalPro categories view.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProViewCategories extends JCalProView
{
	protected $state = null;
	protected $item = null;
	protected $items = null;

	/**
	 * Display the view
	 *
	 * @return	mixed	False on error, null otherwise.
	 */
	function display($tpl = null, $safeparams = false)
	{
		// set extmode
		$this->extmode = 'categories';
		
		// get the items from the model
		$items = $this->get('Items');
		
		// get the pagination
		$pagination = $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseWarning(500, implode("\n", $errors));
			return false;
		}
		
		$this->items      = $items;
		$this->linkdata   = array('current' => JText::_('COM_JCALPRO_CATEGORIES_SECTION_TITLE'));
		$this->pagination = $pagination;
		
		JCalPro::debugger('Items', $this->items);
		JCalPro::debugger('Linkdata', $this->linkdata);

		parent::display($tpl, $safeparams);
		
	}
	
	/**
	 * Handle breadcrumbs
	 * 
	 * @param array
	 * 
	 * @return array
	 */
	public function getBreadcrumbs(&$menu) {
		$app    = JFactory::getApplication();
		$crumbs = array();
		// make sure we have a menu!
		if (!$menu) {
			return $crumbs;
		}
		// some variables
		$option = @$menu->query['option'];
		$view   = @$menu->query['view'];
		
		if (JCalPro::COM == $option && 'categories' == $view) {
			// this is ours - leave it
			return $crumbs;
		}
		$crumbs[] = $this->getCrumb(JText::_(JCalPro::COM . '_CATEGORIES'), '');
		
		return $crumbs;
	}
}