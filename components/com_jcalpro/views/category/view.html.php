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
JLoader::register('JCalProListView', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/views/baseviewlist.php');

/**
 * JCalPro category view.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProViewCategory extends JCalProListView
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
		$this->extmode = 'category';
		$category = $this->get('Category');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseWarning(500, implode("\n", $errors));
			return false;
		}
		
		// assign items to the view
		$this->category = $category;
		$this->linkdata = array('current' => JText::sprintf('COM_JCALPRO_CATEGORY_EVENTS_UNDER', $this->category->title));
		
		JCalPro::debugger('Linkdata', $this->linkdata);
		JCalPro::debugger('Category', $this->category);
		
		// this is only for debugging purposes
		if (defined('JDEBUG') && JDEBUG) {
			JCalPro::registerHelper('mail');
			JCalPro::debugger('Category Moderators', JCalProHelperMail::getModeratorEmails($this->category->id));
		}
		
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
		$id     = @$menu->query['id'];
		
		if (JCalPro::COM == $option && 'category' == $view && $id == @$this->category->id) {
			// this is ours - leave it
			return $crumbs;
		}
		$crumbs[] = $this->getCrumb($this->category->title, '');
		
		return $crumbs;
	}
}