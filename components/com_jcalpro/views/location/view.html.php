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
 * JCalPro location view.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProViewLocation extends JCalProView
{
	function display($tpl = null, $safeparams = false) {
		$app = JFactory::getApplication();
		// we need to set the format in the model's state in case of ical
		$format = $app->input->get('format', 'html', 'cmd');
		$this->extmode = 'location';
		$item = $this->get('Item');
		$user = JFactory::getUser();
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		
		// check this location
		if (!$item || ($item && !$item->id) || ($item && $item->id && 1 != $item->published)) {
			JError::raiseError(404, JText::_('COM_JCALPRO_ERROR_PAGE_NOT_FOUND'));
			jexit();
		}
		
		// Assign the Data
		$this->item       = $item;
		$this->user       = $user;
		$this->title      = $item->title;
		$this->address    = $app->input->get('address', '', 'string');
		$this->linkdata   = array('current' => '');
		
		// add the script
		JCalPro::mapScript();
		
		JCalPro::debugger('Item', $this->item);
		
		parent::display($tpl, $safeparams);
	}
	
	/**
	 * Prepares the document
	 */
	protected function _prepareDocument() {
		parent::_prepareDocument();
		if (!empty($this->item->address)) {
			$this->document->setDescription(JCalProHelperFilter::truncate(str_replace("\n", "  ", trim(strip_tags($this->item->address)))), 160);
		}
		$this->document->setTitle(str_replace("\n", "  ", trim(strip_tags($this->item->title))));
	}
}
