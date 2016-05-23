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

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/helpers/jcalpro.php');
JCalPro::registerHelper('path');

JLoader::register('JCalProBaseModel', JCalProHelperPath::library('models/basemodel.php'));
JLoader::register('JCalProCustomFormModel', JCalProHelperPath::library('models/basemodelcustomform.php'));
JLoader::register('JCalProListEventsModel', JCalProHelperPath::library('models/basemodelevents.php'));
// bugfix: ensure our table exists no matter what
JLoader::register('JCalProTableEvent', JCalProHelperPath::admin('/tables/event.php'));
JLoader::register('JCalProTableRegistration', JCalProHelperPath::admin('/tables/registration.php'));

// load the event-specific language file
JCalPro::language('com_jcalpro.event', JPATH_ADMINISTRATOR);

/**
 * This model acts as a base for registration models
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProRegistrationModel extends JCalProCustomFormModel
{
	/**
	 * The event to trigger before saving the data.
	 * 
	 * @var    string
	 */
	protected $event_before_save = 'onJCalBeforeSave';
	/**
	 * The event to trigger after saving the data.
	 * 
	 * @var    string
	 */
	protected $event_after_save = 'onJCalAfterSave';
	
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $context  = 'com_jcalpro.registration';
	
	public function getForm($data = array(), $loadData = true) {
		// Get the form.
		$form = $this->loadForm($this->option.'.'.$this->name, $this->name, array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}
		
		// we need to get the event_id so we know what form to add
		// just like in the event model, we'll start by loading the value
		// directly from the form
		$event_id = $form->getValue('event_id');
		// if we don't have an event_id here, try to pull it from the data
		if (array_key_exists('event_id', $data) && !empty($data['event_id']) && empty($event_id)) {
			$event_id = (int) $data['event_id'];
		}
		// if there is still no event id, try to pull from the request
		if (empty($event_id)) {
			$event_id = JFactory::getApplication()->input->get('event_id', 0, 'uid');
		}
		// reset the data value
		$data['event_id'] = $event_id;
		
		// let's do the same with the altname, except pull it from the current user
		if (!isset($data['altname']) || empty($data['altname'])) {
			$data['altname'] = JFactory::getUser()->name;
		}
		
		// we have a form - now we need to attach the assigned custom form, if any
		// however, if we're adding a new event we need to load the category based on the request
		
		// load this model now, before we add the admin path - if not we seem to get the wrong model
		$eventModel = JCalPro::getModelInstance('Event', 'JCalProModel');
		
		// we'll likely need this
		JCalProBaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/models');
		
		// start with no custom form
		$defaultForm = false;
		// load the event
		$item = $eventModel->getItem((int) $data['event_id']);
		$catform = 0;
		if (!empty($item->id)) {
			// for some reason, the save & close action gives us an error
			if (!property_exists($item, 'categories')
			|| !property_exists($item->categories, 'canonical')
			|| !is_object($item->categories->canonical)
			|| !property_exists($item->categories->canonical, 'params')
			|| !method_exists($item->categories->canonical->params, 'get')
			) {
				return $form;
			}
			$catform = $item->categories->canonical->params->get('jcalpro_registrationform');
			if (-1 == $catform) return $form;
			$formModel = JCalPro::getModelInstance('Form', 'JCalProModel');
			$defaultForm = $formModel->getItem($catform);
			if (!empty($defaultForm) && empty($defaultForm->id)) $defaultForm = false;
		}
		
		// if this is not empty, we have a form for this category
		if (empty($defaultForm)) {
			$formsModel = JCalPro::getModelInstance('Forms', 'JCalProModel');
			// these filters will always be used
			$formsModel->setState('filter.published', '1');
			$formsModel->setState('filter.formtype', 1);
			$formsModel->setState('list.limit', 0);
			$formsModel->setState('list.start', 1);
			$formsModel->setState('filter.default', '1');
			$defaultForm = $formsModel->getItems();
			// at this point, if there's no form, just bail
			if (empty($defaultForm)) return $form;
			// if for some reason we have 2+ forms, only use the first one
			$defaultForm = array_shift($defaultForm);
		}
		// ok, we SHOULD have a form now, but let's double check it is indeed an object before continuing
		if (!is_object($defaultForm)) return $form;
		// load our fields with the helper lib
		$fields = JCalProHelperForm::getFields(intval($defaultForm->id));
		// don't bother continuing if we have no fields (this shouldn't happen, but you never know!)
		if (empty($fields)) return $form;
		// now that we have the fields, we need to add them to the form
		$this->addFieldsToForm($fields, $form, $defaultForm->title);
		// return the form
		return $form;
	}
}
