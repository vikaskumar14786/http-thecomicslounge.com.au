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
JCalPro::registerHelper('url');

JLoader::register('JCalProPreSaveController', JCalProHelperPath::library() . '/controllers/basecontrollerpresave.php');

class JCalProEventController extends JCalProPreSaveController
{
	/**
	 * The prefix to use with controller messages.
	 * 
	 * @var    string
	 */
	protected $text_prefix = 'COM_JCALPRO_EVENT';
	
	/**
	 * method to add a new event
	 * 
	 */
	public function add() {
		return parent::add();
	}
	
	/**
	 * method to edit an event
	 * 
	 */
	public function edit() {
		return parent::edit();
	}
	
	/**
	 * Method to check if you can add a new record.
	 * 
	 * Overriding this method because of private events
	 * 
	 * @param   array  $data  An array of input data.
	 * 
	 * @return  boolean
	 */
	protected function allowAdd($data = array()) {
		return JCalPro::canAddEvents();
	}

	/**
	 * Method to check if you can edit a record.
	 * 
	 * Overriding this method because of private events
	 * 
	 * @param   array  $data  An array of input data.
	 * 
	 * @return  boolean
	 */
	protected function allowEdit($data = array(), $key = 'id') {
		$user = JFactory::getUser();
		// ensure we have an id
		if (!array_key_exists($key, $data) || 'id' != $key) {
			return false;
		}
		$id = (int) $data[$key];
		// check if the id is 0 - if so, we may be creating a new event and pre-saving
		if (empty($id)) {
			// we probably don't have the data in the state yet, so check request
			$formdata = JFactory::getApplication()->input->post->get('jform', array(), 'array');
			// check for canonical
			return (
				array_key_exists('canonical', $formdata)
				&& is_numeric($formdata['canonical'])
				&& $user->authorise('core.create', $this->option . '.category.' . (int) $formdata['canonical'])
			) || $user->authorise('core.create', $this->option);
		}
		// get the data for this event
		$db = JFactory::getDbo();
		try {
			// load our info
			$info = $db->setQuery((string) $db->getQuery(true)
				->select('Event.private')->select('Event.created_by')->select('Xref.category_id')
				->from('#__jcalpro_events AS Event')
				->leftJoin('#__jcalpro_event_categories AS Xref ON Xref.event_id = Event.id AND Xref.canonical = 1')
				->where('Event.id = ' . $id)
				->group('Event.id')
			)->loadObject();
			
			if (empty($info)) {
				throw new Exception('.');
			}
		}
		catch (Exception $e) {
			// dummy object to prevent notices in later code
			$info = new stdClass;
			$info->category_id = 0;
			$info->created_by  = 0;
			$info->private     = 0;
		}
		// set our context for the category
		$context = $this->option . '.category.' . $info->category_id;
		
		// return successive checks
		return $user->authorise('core.edit', $this->option) || $user->authorise('core.edit', $context)
			|| ($user->id == $info->created_by && (
				$info->private || $user->authorise('core.edit.own', $context) || $user->authorise('core.edit.own', $this->option)
			))
		;
	}
	
	/**
	 * intermediary form for canonical category selection
	 */
	public function catselect() {
		$extra = array('layout' => 'modal');
		// redirect
		$this->setRedirect(JRoute::_(JCalProHelperUrl::view('event', false, $extra) . $this->getRedirectToListAppend(), false));
		return true;
	}
	
	/**
	 * outputs a qrcode to the event's url
	 */
	public function qrcode() {
		jimport('joomla.filesystem.file');
		jimport('phpqrcode.loader');
		$id = (int) JFactory::getApplication()->input->get('id', '0', 'int');
		$cache = JCalProHelperPath::media() . '/qrcodes/event-' . $id . '.png';
		if (!JFile::exists($cache)) {
			QRcode::png(JCalProHelperUrl::toFull(JCalProHelperUrl::event($id)), $cache);
		}
		// output the image
		header('Content-type: image/png');
		echo JFile::read($cache);
		die;
	}
	
	/**
	 * override these methods so we can also add the Itemid
	 * 
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id') {
		// get the results of the parent method
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		// add base
		$append = $this->_redirectAppend($append);
		// return final append string
		return $append;
	}
	
	protected function getRedirectToListAppend() {
		// get the results of the parent method
		$append = parent::getRedirectToListAppend();
		// add base
		$append = $this->_redirectAppend($append);
		// for some reason we're not getting the Itemid
		// only do so in frontend
		if (!JFactory::getApplication()->isAdmin()) {
			$Itemid = JFactory::getApplication()->input->get('Itemid', 0, 'uint');
			if (empty($Itemid)) $Itemid = JCalProHelperUrl::findItemid();
			if (!empty($Itemid)) $append .= '&Itemid=' . (int) $Itemid;
		}
		// return final append string
		return $append;
	}
	
	private function _redirectAppend($append) {
		// check for requested dates
		$date = JFactory::getApplication()->input->get('date', '', 'string');
		if (!empty($date)) $append .= '&date=' . $date;
		// return final append string
		return $append;
	}
}
