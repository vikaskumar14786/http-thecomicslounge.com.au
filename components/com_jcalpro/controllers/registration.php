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
JLoader::register('JCalProPreSaveController', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/controllers/basecontrollerpresave.php');
JLoader::register('JCalProHelperMail', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/mail.php');
JLoader::register('JCalProHelperUrl', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/url.php');

class JCalProControllerRegistration extends JCalProPreSaveController
{
	/**
	 * method to confirm a registration
	 * 
	 */
	public function confirm() {
		// we're always going to redirect to the events view
		$this->setRedirect(JRoute::_(JCalProHelperUrl::view('events')));
		// get token from request
		$token = JFactory::getApplication()->input->get('token', '', 'cmd');
		// no token? no confirm
		if (empty($token)) {
			$this->setError(JText::_('COM_JCALPRO_REGISTRATION_EMPTY_CONFIRMATION_TOKEN'));
			$this->setMessage($this->getError(), 'error');
			return false;
		}
		// check the token length
		if (40 != strlen($token)) {
			$this->setError(JText::_('COM_JCALPRO_REGISTRATION_INVALID_CONFIRMATION_TOKEN'));
			$this->setMessage($this->getError(), 'error');
			return false;
		}
		// unfortunately we need the event afterwards, otherwise we could blanket update
		$db = JFactory::getDbo();
		$db->setQuery((string) $db->getQuery(true)
			->select('*')
			->from('#__jcalpro_registration')
			->where($db->quoteName('confirmation') . ' = ' . $db->Quote($token))
		);
		try {
			if (!($data = $db->loadObject())) {
				throw new Exception('no data');
			}
		}
		catch (Exception $e) {
			// we failed
			$this->setError(JText::_('COM_JCALPRO_REGISTRATION_INVALID_CONFIRMATION_TOKEN'));
			$this->setMessage($this->getError(), 'error');
			return false;
		} 
		
		// take this data and load the event
		$model = JCalPro::getModelInstance('Event', 'JCalProModel');
		$event = $model->getItem($data->event_id);
		
		// update the registration
		$db = JFactory::getDbo();
		$db->setQuery((string) $db->getQuery(true)
			->update('#__jcalpro_registration')
			->set($db->quoteName('confirmation') . ' = ' . $db->Quote(''))
			->set($db->quoteName('published') . ' = 1')
			->where($db->quoteName('id') . ' = ' . (int) $data->id)
		);
		// run the query then check the rows
		try {
			$db->query();
		}
		catch (Exception $e) {
			return false;
		}
		if ($db->getAffectedRows()) {
			// get the updated registration
			$db->setQuery((string) $db->getQuery(true)
				->select('*')
				->from('#__jcalpro_registration')
				->where($db->quoteName('id') . ' = ' . (int) $data->id)
			);
			try {
				if (!($registration = $db->loadObject())) {
					throw new Exception(JText::_('COM_JCALPRO_REGISTRATION_NOT_FOUND'));
				}
			}
			catch (Exception $e) {
				$this->setError(JText::_('COM_JCALPRO_REGISTRATION_NOT_FOUND'));
				$this->setMessage($this->getError(), 'error');
				return false;
			}
			$registration->confirmhref = JCalProHelperUrl::toFull(JCalProHelperUrl::task('registration.confirm', true, array('token' => $registration->confirmation)));
			$registration->details = JCalProHelperMail::buildEventData($event);
			
			if (property_exists($registration, 'params') && is_string($registration->params)) {
				$reg = new JRegistry;
				$reg->loadString($registration->params);
				$registration->params = $reg;
			}
			
			if (!property_exists($event, 'registration_data')) {
				$event->registration_data = new stdClass;
			}
			$event->registration_data->current_entry = $registration;
			
			// build the registered user
			$reguser = JFactory::getUser($data->user_id);
			if ($reguser->guest) {
				$reguser = new stdClass;
				$reguser->id    = (int) $data->user_id;
				$reguser->name  = $data->user_name;
				$reguser->email = $data->user_email;
			}
			JCalProHelperMail::send('registration.confirmed', $event, $reguser);
			// now inform the site creator, if available
			$creator = JFactory::getUser($event->created_by);
			if (!empty($creator->email)) {
				JCalProHelperMail::send('registration.notify', $event, $creator);
			}
			unset($event->registration_data->current_entry);
			// send a message back to the user
			$this->setMessage(JText::_('COM_JCALPRO_REGISTRATION_CONFIRMED'), 'message');
			return true;
		}
	}
	
	/**
	 * method to add a new registration
	 * 
	 */
	public function add() {
		$app = JFactory::getApplication();
		// adjust user id, if necessary
		$data = $app->input->get('jform', array(), 'array');
		if (is_array($data)) {
			if (!array_key_exists('user_id', $data)) {
				$data['user_id'] = JFactory::getUser()->id;
				$app->input->set('jform', $data);
			}
		}
		// add
		$add = parent::add();
		// check the id of the event to be registered
		$id = $app->input->get('event_id', 0, 'int');
		// find the event using the Event model (so we can have the event auto-prepared)
		$model = JCalPro::getModelInstance('Event', 'JCalProModel');
		$item = $model->getItem($id);
		// if we have no items at all, there was a problem
		if (empty($item) || empty($item->id)) {
			$this->setError(JText::_('COM_JCALPRO_EVENT_DOES_NOT_EXIST'));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_(JCalProHelperUrl::view('events')));
			return false;
		}
		// now check if we can register, and if not set the message and redirect
		if (!$item->registration || !isset($item->registration_data)) {
			$this->setError(JText::_('COM_JCALPRO_CANNOT_REGISTER_REGISTRATION_DISABLED'));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect($item->href);
			return false;
		}
		// check if the user can register for this event
		if (!$item->registration_data->can_register) {
			$this->setError(empty($item->registration_data->register_error) ? JText::_('COM_JCALPRO_CANNOT_REGISTER_UNSPECIFIED_ERROR') : $item->registration_data->register_error);
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect($item->href);
			return false;
		}
		// set the redirect & return depending on add status
		if ($add) {
			$this->setRedirect(JRoute::_(JCalProHelperUrl::view('registration', false, array('event_id' => $id)).$this->getRedirectToItemAppend(), false));
		}
		else {
			$this->setRedirect(JRoute::_(JCalProHelperUrl::view('events', false).$this->getRedirectToListAppend(), false));
		}
		return $add;
	}
	
	public function save($key = null, $urlVar = null) {
		$id = JFactory::getApplication()->input->get('event_id', 0, 'uint');
		$save = parent::save($key, $urlVar);
		if (!$save) {
			$this->setRedirect(JCalProHelperUrl::view($this->view_item, false).$this->getRedirectToItemAppend($recordId, $urlVar));
		}
		else {
			$this->setMessage(JText::_('COM_JCALPRO_REGISTRATION_ADDED'), 'message');
			$this->setRedirect(JCalProHelperUrl::event($id));
		}
		return $save;
	}
	
	/**
	 * we're overriding this because registration addition isn't so simple as checking the acls
	 * all of this is precalculated in the event model when the even is loaded
	 * so we just need to load the correct event and check the data
	 * 
	 * @param unknown_type $data
	 */
	protected function allowAdd($data = array()) {
		$event_id = JFactory::getApplication()->input->get('event_id', 0, 'uint');
		if (empty($event_id)) {
			$event_id = @$data['event_id'];
		}
		
		if (empty($event_id)) {
			$this->setError('COM_JCALPRO_EVENT_DOES_NOT_EXIST');
			return false;
		}
		// get the event model and load the event requested
		$eventModel = JCalPro::getModelInstance('Event', 'JCalProModel');
		$event = $eventModel->getItem($event_id);
		if (empty($event) || empty($event->id)) {
			$this->setError('COM_JCALPRO_EVENT_DOES_NOT_EXIST');
			return false;
		}
		// check if the event allows registration, or even has registration data
		// if not, bail with an error
		if (!$event->registration || !property_exists($event, 'registration_data')) {
			$this->setError('COM_JCALPRO_EVENT_NO_REGISTRATION');
			return false;
		}
		// now check if we can register and if not, send back the appropriate error
		if (!$event->registration_data->can_register) {
			$this->setError($event->registration_data->register_error);
			return false;
		}
		return true;
	}
}
