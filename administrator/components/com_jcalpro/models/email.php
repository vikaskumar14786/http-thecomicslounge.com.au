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

JLoader::register('JCalProAdminModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodeladmin.php');
JLoader::register('JCalProHelperMail', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/mail.php');
JLoader::register('JCalProHelperUrl', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/url.php');

/**
 * This models supports a single email template.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelEmail extends JCalProAdminModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $context  = 'com_jcalpro.email';
	
	public function getTable($type='Email', $prefix='JCalProTable', $config=array()) {
		return parent::getTable($type, $prefix, $config);
	}

	public function getForm($data=array(), $loadData=true) {
		return parent::getForm($data, $loadData);
	}
	
	public function getDemo() {
		$app  = JFactory::getApplication();
		$db   = JFactory::getDbo();
		$user = JFactory::getUser();
		
		$db->setQuery((string) $db->getQuery(true)
			->select('id')
			->from('#__jcalpro_events')
			->where($db->quoteName('published') . ' = 1')
			->order($db->quoteName('registration'))
		);
		try {
			if ($id = $db->loadResult()) {
				$model = JCalPro::getModelInstance('Event');
				$event = $model->getItem($id);
			}
			else {
				throw new Exception('COM_JCALPRO_EMAIL_DEMO_NO_EVENT');
			}
		}
		catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}
		
		// set up registration data in the event
		if (!property_exists($event, 'registration_data')) {
			$event->registration_data = new stdClass;
		}
		$event->registration_data->current_entry = new stdClass;
		$event->registration_data->current_entry->user_id     = 0;
		$event->registration_data->current_entry->user_name   = 'JCal Pro';
		$event->registration_data->current_entry->user_email  = 'demo@domain.com';
		$event->registration_data->current_entry->confirmhref = JCalProHelperUrl::toFull(JCalProHelperUrl::task('registration.confirm', true, array('token' => md5('token'))));
		$event->registration_data->current_entry->details     = JCalProHelperMail::buildEventData($event);
		
		$demo = new stdClass;
		// WARNING WARNING WARNING
		// As of right now, JInput won't hand back html, but JRequest will ...
		$demo->body    = JCalProHelperMail::replaceTags(JFilterInput::getInstance(null, null, 1, 1)->clean(JRequest::getVar('body', null, '', 'string', JREQUEST_ALLOWRAW)), $event, $user);
		$demo->subject = JCalProHelperMail::replaceTags($app->input->get('subject', '', 'string'), $event, $user);
		
		return $demo;
	}
	
	public function setDefault($id = 0) {
		// Initialise variables.
		$user = JFactory::getUser();
		$db   = $this->getDbo();
		
		// Access checks.
		if (!$user->authorise('core.edit.state', 'com_jcalpro')) {
			throw new Exception(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
		}
		
		$table = JTable::getInstance('Email', 'JCalProTable');
		if (!$table->load((int)$id)) {
			throw new Exception(JText::_('COM_JCALPRO_ERROR_EMAIL_NOT_FOUND'));
		}
		
		// Reset the default field
		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__jcalpro_emails'))
				->set($db->quoteName('default') . ' = 0')
				->where($db->quoteName('context') . ' = ' . $db->quote($table->context))
				->where($db->quoteName('default') . ' = 1')
		);
		
		try {
			if (!$db->query()) {
				throw new Exception($db->getErrorMsg());
			}
		}
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		// Set the new default form
		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__jcalpro_emails'))
				->set($db->quoteName('default') . ' = 1')
				->where($db->quoteName('id') . ' = ' . (int) $id)
		);
		
		try {
			if (!$db->query()) {
				throw new Exception($db->getErrorMsg());
			}
		}
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		// Clean the cache.
		$this->cleanCache();
		
		return true;
	}
	
	public function unsetDefault($id = 0) {
		// Initialise variables.
		$user = JFactory::getUser();
		$db   = $this->getDbo();
		
		// Access checks.
		if (!$user->authorise('core.edit.state', 'com_jcalpro')) {
			throw new Exception(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
		}
		
		$table = JTable::getInstance('Email', 'JCalProTable');
		if (!$table->load((int)$id)) {
			throw new Exception(JText::_('COM_JCALPRO_ERROR_EMAIL_NOT_FOUND'));
		}
		
		// Set the new default form
		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__jcalpro_emails'))
				->set($db->quoteName('default') . ' = 0')
				->where($db->quoteName('id') . ' = ' . (int) $id)
		);
		
		try {
			if (!$db->query()) {
				throw new Exception($db->getErrorMsg());
			}
		}
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		// Clean the cache.
		$this->cleanCache();
		
		return true;
	}
}
