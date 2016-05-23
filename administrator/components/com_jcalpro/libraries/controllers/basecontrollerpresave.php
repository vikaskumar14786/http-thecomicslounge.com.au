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

JCalPro::registerHelper('path');

JLoader::register('JCalProBaseControllerForm', JCalProHelperPath::library() . '/controllers/basecontrollerform.php');

class JCalProPreSaveController extends JCalProBaseControllerForm
{
	/**
	 * method to add a new event
	 * 
	 */
	public function add() {
		$add = parent::add();
		$this->_saveEventFormData();
		return $add;
	}
	
	/**
	 * method to edit an event
	 * 
	 */
	public function edit() {
		$edit = parent::edit();
		$this->_saveEventFormData();
		return $edit;
	}
	
	/**
	 * we use this to save form data
	 * 
	 */
	private function _saveEventFormData() {
		$context = "$this->option.edit.$this->context";
		$app     = JFactory::getApplication();
		$data    = $app->input->post->get('jform', array(), 'array');
		// BUG: if a site admin ignores the reqs to disable magic quotes
		// this will probably screw up the data - barf
		if (@get_magic_quotes_gpc()) {
			// fix the data
			$data = $this->_cleanData($data);
		}
		$app->setUserState($context.'.data', $data);
	}
	
	/**
	 * override these methods so we can also add the Itemid
	 * 
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id') {
		// get the results of the parent method
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		// check if event_id is set & if so, add it
		$app    = JFactory::getApplication();
		$data   = $app->input->post->get('jform', array(), 'array');
		if (array_key_exists('event_id', $data)) {
			$eid = (int) $data['event_id'];
			if ($eid) {
				$append .= '&event_id=' . $eid;
			}
		}
		// return final append string
		return $append;
	}
	
	private function _cleanData($data) {
		if (!is_array($data)) {
			return stripslashes((string) $data);
		}
		foreach ($data as $key => &$value) {
			$value = $this->_cleanData($value);
		}
		return $data;
	}
}
