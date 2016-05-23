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

// load the event-specific language file
JFactory::getLanguage()->load('com_jcalpro.event', JPATH_ADMINISTRATOR);

/**
 * This model acts as a base for the event models
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProEventModel extends JCalProCustomFormModel
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $context  = 'com_jcalpro.event';
	
	/**
	 * The event to trigger after saving the data.
	 * 
	 * @var    string
	 */
	protected $event_after_save = 'onJCalAfterSave';
	
	/**
	 * The event to trigger before saving the data.
	 * 
	 * @var    string
	 */
	protected $event_before_save = 'onJCalBeforeSave';
	
	/**
	 * The event to trigger before changing state.
	 * 
	 * @var    string
	 */
	protected $event_change_state = 'onJCalChangeState';
	
	/**
	 * The event to trigger after changing approval.
	 * 
	 * @var    string
	 */
	protected $event_change_approval = 'onJCalChangeApproval';
	
	/**
	 * The event to trigger after changing feature status.
	 * 
	 * @var    string
	 */
	protected $event_change_featured = 'onJCalChangeFeatured';
	
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$defaults = array(
			'event_change_approval' => 'onJCalChangeApproval'
		,	'event_change_featured' => 'onJCalChangeFeatured'
		);
		
		foreach ($defaults as $key => $default) {
			if (isset($config[$key])) {
				$this->$key = $config[$key];
			}
			elseif (empty($this->$key)) {
				$this->$key = $default;
			}
		}
	}
	
	
	public function getItem($id = null) {
		// get our item
		$item = parent::getItem($id);
		// since Joomla! uses JTable to load a single item on the frontend, we need to go ahead
		// and set the different times (UTC, user) for this event
		// as well we need to go ahead and parse the categories and load those
		if ($item) {
			// prepare the event using the base events model
			$baseModel = JCalPro::getModelInstance('Events', 'JCalProModel');
			// for whatever reason, sometimes a fatal error is reached later if the event's
			// canonical category cannot be found
			// so let's try to combat this by resetting the filters (puke)
			$originalfilter = $baseModel->getCategoryFilters();
			$baseModel->setCategoryFilters(array());
			// prepare the event
			$baseModel->prepareEvent($item);
			// reset the filter
			$baseModel->setCategoryFilters($originalfilter);
		}
		// remember to send our item along!
		return $item;
	}
	
	public function getForm($data = array(), $loadData = true) {
		// Get the form.
		$form = $this->loadForm($this->option.'.'.$this->name, $this->name, array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}
		
		$user = JFactory::getUser();
		$app  = JFactory::getApplication();
		
		// joomla! versions less than 3.1.0 don't support tags
		if (!JCalPro::version()->isCompatible('3.1.0')) {
			$form->removeField('tags', 'metadata');
		}
		
		// apparently the core "editor" field is bugged & only loads the global editor
		// we need to fix this here & allow the user's editor to be used
		$editor = $user->getParam('editor');
		if (!empty($editor)) {
			$form->setFieldAttribute('description', 'editor', $editor);
		}
		
		// ensure description is required, if configured
		if (JCalPro::config('require_description')) {
			$form->setFieldAttribute('description', 'required', 'true');
		}
		
		// we don't allow alias editing unless the user has some rights
		if (!$app->isAdmin()) {
			$form->setFieldAttribute('alias', 'type', 'hidden');
			$form->setFieldAttribute('alias', 'readonly', 'true');
			
			// disable captcha if none is provided
			switch(JCalPro::config('captcha', '')) {
				// no captcha enabled - remove
				case '0':
					$form->removeField('captcha');
					break;
				// using something - let core handle which one
				default:
					break;
			}
		}
		// we are in admin - disable captcha
		else {
			$form->removeField('captcha');
		}
		
		// fix the editor buttons, if configured
		$buttons = JCalPro::config('editor_buttons');
		if (is_array($buttons) && !empty($buttons)) {
			$db = JFactory::getDbo();
			
			$exclude = array();
			foreach ($buttons as $button) $exclude[] = $db->quote($button);
			
			try
			{
				$allowedButtons = $db->setQuery($db->getQuery(true)
					->select('element')
					->from('#__extensions')
					->where('type="plugin"')
					->where('folder="editors-xtd"')
					->where('element NOT IN (' . implode(',', $exclude) . ')')
				)->loadColumn();
			}
			catch (Exception $e)
			{
				$allowedButtons = array();
			}
			
			$form->setFieldAttribute('description', 'buttons', implode(',', $allowedButtons));
			// BUGFIX: J3 core broke the "buttons" attribute and made it dependent on "hide"
			$form->setFieldAttribute('description', 'hide', implode(',', $allowedButtons));
		}
		
		
		// ensure location is required, if configured
		if (JCalPro::config('require_location')) {
			$form->setFieldAttribute('location', 'required', 'true');
		}
		
		
		// we have a form - now we need to attach the assigned custom form, if any
		// however, if we're adding a new event we need to load the category based on the request
		
		// add the admin model path, so we can use the form models
		JCalProBaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/models');
		
		// check for registration while we're at it
		$registration = false;
		// start with no custom form
		$defaultForm = false;
		// load the category based on the canonical value
		$canonical = $form->getValue('canonical');
		// when validating, the canonical value is in the data and not the form
		// so what happens is the default form is loaded during validation
		// if the default form contains required fields, but there's an alternate form
		// and that form does not share the same required fields, we end up with a validation error
		if (array_key_exists('canonical', $data) && !empty($data['canonical']) && empty($canonical)) {
			$canonical = (int) $data['canonical'];
		}
		
		// yuk
		$catform = -2;
		
		// check canonical
		if ($canonical) {
			
			// add actions to debugger
			if (JDEBUG) JCalPro::debugger('Category Actions', array(
				'core.admin' => JCalPro::canDo('core.admin', $canonical)
			,	'core.manage' => JCalPro::canDo('core.manage', $canonical)
			,	'core.create' => JCalPro::canDo('core.create', $canonical)
			,	'core.create.private' => JCalPro::canDo('core.create.private', $canonical)
			,	'core.delete' => JCalPro::canDo('core.delete', $canonical)
			,	'core.edit' => JCalPro::canDo('core.edit', $canonical)
			,	'core.edit.state' => JCalPro::canDo('core.edit.state', $canonical)
			,	'core.edit.own' => JCalPro::canDo('core.edit.own', $canonical)
			,	'core.moderate' => JCalPro::canDo('core.moderate', $canonical)
			));
			
			$categories = $this->getCategories();
			if (!empty($categories)) foreach ($categories as $cat) {
				if ($cat->id == $canonical) {
					$catform = $cat->params->get('jcalpro_eventform');
					// check if we're removing the registration fieldset
					// NOTE: this value comes in as a string (puke)
					if ('0' === $cat->params->get('jcalpro_registration')) $this->_removeRegistrationFields($form);
					// check if this category has no form
					if (-1 != $catform) {
						$formModel = JCalPro::getModelInstance('Form', 'JCalProModel');
						$defaultForm = $formModel->getItem($catform);
						if (empty($defaultForm) || empty($defaultForm->id)) $defaultForm = false;
					}
				}
			}
		}
		// we have no canonical category
		else {
			// remove the registration if necessary
			if (!JCalPro::config('registration')) $this->_removeRegistrationFields($form);
			// null this out
			$canonical = null;
		}
		
		// check permissions for both create and create.private and fix the "private" element if necessary
		$canCreatePrivate = JCalPro::canDo('core.create.private', $canonical);
		$canCreatePublic  = JCalPro::canDo('core.create', $canonical);
		// this user can create private events but not public events
		// force the value of "private" to 1 and disable the field
		if ($canCreatePrivate && !$canCreatePublic) {
			$form->setValue('private', null, '1');
			$form->setFieldAttribute('private', 'default', '1');
			$form->setFieldAttribute('private', 'disabled', 'true');
		}
		// this user can create public events but not private events
		// do the opposite of above
		else if (!$canCreatePrivate) {
			$form->setValue('private', null, '0');
			$form->setFieldAttribute('private', 'default', '0');
			$form->setFieldAttribute('private', 'disabled', 'true');
		}
		// remove the featured field if needed
		if (!JCalPro::canFeatureEvents($canonical)) {
			$form->removeField('featured');
		}
		// remove the approval field if the user cannot moderate
		// we will disable this in frontend if the event is private
		if (!JCalPro::canModerateEvents($canonical)) {
			$form->removeField('approved');
		}
		// remove the published field only if the user cannot edit state AND they cannot create private
		// if they cannot edit state but can add private events (thus they can edit THOSE states),
		// go ahead and show the field, but disable via js depending on private value
		if (!JCalPro::canPublishEvents($canonical) && !$canCreatePrivate) {
			$form->removeField('published');
		}
		// only completely remove the registration if the user cannot create publis events
		if (!$canCreatePublic) {
			$this->_removeRegistrationFields($form);
		}
		/*
		// handle the "approved" value
		$private  = $form->getValue('private');
		$approved = $form->getValue('approved');
		// we automatically set "approved" in the table if the event is private or the user cannot moderate, so remove it
		if (1 == $private || (0 == $private && !JCalPro::canModerateEvents($canonical))) $form->removeField('approved');
		// we automatically handle the published status if the event is not private and the user cannot publish
		if (0 == $private && !JCalPro::canPublishEvents($canonical)) $form->removeField('published');
		// remove the registration and return the form if this is a private event
		if (1 == $private) {
			// remove the registration (as nobody will be able to see it anyways)
			$this->_removeRegistrationFields($form);
		}
		*/
		
		// if this is not empty, we have a form for this category
		if (empty($defaultForm) && -2 != $catform) {
			$formsModel = JCalPro::getModelInstance('Forms', 'JCalProModel');
			// these filters will always be used
			$formsModel->setState('filter.published', '1');
			$formsModel->setState('filter.formtype', 0);
			$formsModel->setState('list.limit', 0);
			$formsModel->setState('list.start', 1);
			$formsModel->setState('filter.search', '');
			$formsModel->setState('filter.default', '1');
			$defaultForm = $formsModel->getItems();
			// at this point, if there's no form, just bail
			if (empty($defaultForm)) {
				return $form;
			}
			// if for some reason we have 2+ forms, only use the first one
			$defaultForm = array_shift($defaultForm);
		}
		// ok, we SHOULD have a form now, but let's double check it is indeed an object before continuing
		if (is_object($defaultForm)) {
			// load our fields with the helper lib
			$fields = JCalProHelperForm::getFields(intval($defaultForm->id));
			// don't bother continuing if we have no fields (this shouldn't happen, but you never know!)
			if (!empty($fields)) {
				// now that we have the fields, we need to add them to the form
				$this->addFieldsToForm($fields, $form, $defaultForm->title);
			}
		}
		// return the form
		return $form;
	}
	
	private function _removeRegistrationFields(&$form) {
		// TODO: fix this & make it nicer
		$fields = array(
			'registration'
		,	'registration_capacity'
		,	'registration_start_date_array'
		,	'registration_until_event'
		,	'registration_end_date_array'
		);
		foreach ($fields as $field) {
			$form->removeField($field);
		}
	}
	
	/**
	 * Method to test whether a record can be deleted.
	 * 
	 * @param   object   $record  A record object.
	 * 
	 * @return  boolean  True if allowed to delete the record.
	 */
	protected function canDelete($record) {
		$user = JFactory::getUser();
		if ($record->private && $user->id == $record->created_by) return true;
		return $user->authorise('core.delete', $this->option);
	}
	
	/**
	 * Method to test whether a record can be deleted.
	 * 
	 * @param   object   $record    A record object.
	 * 
	 * @return  boolean  True if allowed to change the state of the record.
	 */
	protected function canEditState($record) {
		$user = JFactory::getUser();
		if ($record->private && $user->id == $record->created_by) return true;
		return $user->authorise('core.edit.state', $this->option);
	}
	
	/**
	 * override to handle deletion of xref entries
	 * 
	 * @param unknown_type $pks
	 */
	public function delete(&$pks) {
		$delete = parent::delete($pks);
		if (!empty($pks) && $delete) {
			JArrayHelper::toInteger($pks);
			$db = JFactory::getDbo();
			$db->setQuery((string) $db->getQuery(true)->delete('#__jcalpro_event_categories')->where('event_id IN (' . implode(',', $pks) . ')'));
			$db->query();
		}
		return $delete;
	}

	/**
	 * method to import events from .ics files
	 * 
	 * @param bool $local
	 */
	public function import($local) {
		$app = JFactory::getApplication();
		// load JFile
		jimport('joomla.filesystem.file');
		// we need to get the ics file - either from $_FILES or from the remote url
		if ($local) {
			$ics = $this->_getIcsFromUpload();
		}
		else {
			$ics = $this->_getIcsFromRemote();
		}
		// check to make sure we actually have a file
		if (empty($ics)) {
			// NOTE: private methods should have already set the error message
			return false;
		}
		// ensure that we can access the file
		if (!JFile::exists($ics)) {
			$this->setError(JText::_('COM_JCALPRO_IMPORT_FILE_DOES_NOT_EXIST'));
			return false;
		}
		// get the category id we default to if we can't find one
		$catid = $app->input->get('import_catid', 0, 'uint');
		// should we try to guess the category?
		$guess = (bool) $app->input->get('guesscats', 0, 'uint');
		// register our iCal helper
		JLoader::register('JCalProHelperIcal', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/ical.php');
		// read the ical file
		$events = JCalProHelperIcal::fromIcal($ics, $catid, $guess);
		// awesome, we should have some events now :)
		// start keeping track of how many we're adding, so we can tell the user later
		$added = 0;
		$errored = 0;
		if (empty($events)) {
			$this->setError(JText::_('COM_JCALPRO_IMPORT_NO_EVENTS_IN_FILE'));
			return false;
		}
		// we need to NOT set errors if something goes wrong here
		$app = JFactory::getApplication();
		// add our table path
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/tables');
		// loop
		foreach ($events as $event) {
			$table = JTable::getInstance('Event', 'JCalProTable');
			// try to load the event by common id
			if (array_key_exists('common_event_id', $event) && !empty($event['common_event_id'])) {
				$table->loadByCommonId($event['common_event_id']);
			}
			// FIX for wonky timezones
			if (array_key_exists('timezone', $event) && 'Z' === $event['timezone']) {
				$event['timezone'] = 'UTC';
			}
			// if we can't bind our data, we need to let the user know why and go on to the next event
			if (!$table->bind($event)) {
				$errored++;
				if (3 < $errored) $app->enqueuemessage(JText::sprintf('COM_JCALPRO_IMPORT_CANNOT_BIND', $event['title'], $table->getError()), 'error');
				continue;
			}
			// same with check
			if (!$table->check()) {
				if (3 < $errored) $app->enqueuemessage(JText::sprintf('COM_JCALPRO_IMPORT_CANNOT_CHECK', $event['title'], $table->getError()), 'error');
				continue;
			}
			// now try to store
			if (!$table->store()) {
				if (3 < $errored) $app->enqueuemessage(JText::sprintf('COM_JCALPRO_IMPORT_CANNOT_STORE', $event['title'], $table->getError()), 'error');
				continue;
			}
			// if we made it this far, the event was successfully stored - increment the counter
			$added++;
		}
		// if we had too many errors, tell the user that
		if (3 < $errored) $app->enqueuemessage(JText::_('COM_JCALPRO_IMPORT_TOO_MANY_ERRORS'), 'error');
		// done looping, tell the user how many events we added (if any)
		if (0 == $added) {
			$this->setError(JText::_('COM_JCALPRO_IMPORT_NO_EVENTS_ADDED'));
			return false;
		}
		else {
			$app->enqueuemessage(JText::sprintf('COM_JCALPRO_IMPORT_ADDED_X_EVENTS', $added));
			return true;
		}
		// this would mean logic broke down
		$this->setError(JText::_('COM_JCALPRO_IMPORT_BROKEN_LOGIC'));
		return false;
	}
	
	/**
	 * private method to get an .ics file from $_FILES
	 * 
	 * @return mixed path to .ics file, or false
	 */
	private function _getIcsFromUpload() {
		$data = JFactory::getApplication()->input->files->get('localics');
		// make sure php allows uploads
		if (!(bool) ini_get('file_uploads')) {
			$this->setError(JText::_('COM_JCALPRO_IMPORT_LOCAL_UPLOAD_DISABLED'));
			return false;
		}
		// no uploaded data?
		if (!is_array($data)) {
			$this->setError(JText::_('COM_JCALPRO_IMPORT_LOCAL_NO_FILE_UPLOADED'));
			return false;
		}
		// problem uploading data
		if ($data['error'] || $data['size'] < 1) {
			$this->setError(JText::_('COM_JCALPRO_IMPORT_LOCAL_FILE_EMPTY'));
			return false;
		}
		// create path
		$base = JFactory::getConfig()->get('tmp_path');
		$file = $base . '/' . md5($data['name']) . '.tmp.ics';
		// upload file
		if (!JFile::upload($data['tmp_name'], $file)) {
			$this->setError(JText::_('COM_JCALPRO_IMPORT_LOCAL_CANNOT_MOVE_FILE'));
			return false;
		}
		return $file;
	}
	
	/**
	 * private method to get an .ics file from a remote site
	 * 
	 * @return mixed path to locally downloaded .ics file, or false
	 */
	private function _getIcsFromRemote() {
		$url = JFactory::getApplication()->input->post->get('remoteics', '', 'string');
		JCalPro::registerHelper('ical');
		return JCalProHelperIcal::downloadIcsFile($url);
	}
	
	/**
	 * Method to test whether a record can be moderated.
	 * TODO: hand this off to the JCalPro helper
	 *
	 * @param   object   $record  A record object.
	 *
	 * @return  boolean  True if allowed to moderate the record. Defaults to the permission for the component.
	 */
	protected function canModerate($record) {
		$user = JFactory::getUser();
		return $user->authorise('core.moderate', $this->option);
	}
	
	/**
	 * Method to approve one or more records.
	 *
	 * @param   array    $pks    An array of record primary keys.
	 * @param   int      $state  value to set records to
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function approve(&$pks, $state = 1) {
		return $this->toggle('approve', $pks, $state, $this->event_change_approval);
	}
	
	/**
	 * Method to feature one or more records.
	 *
	 * @param   array    $pks    An array of record primary keys.
	 * @param   int      $state  value to set records to
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function feature(&$pks, $state = 1) {
		return $this->toggle('feature', $pks, $state, $this->event_change_featured);
	}
	
	public function toggle($method, &$pks, $state = 1, $event = null) {
		// hack - we currently only support these two methods here
		if ('feature' != $method && 'approve' != $method) {
			return false;
		}
		// get the variable name based on the "ed" version of the word
		// we shouldn't do it this way but "featured" and "approved" both just end in "d" :)
		$variable = "{$method}d";
		
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('content');
		// Initialise variables.
		$dispatcher = JDispatcher::getInstance();
		$user       = JFactory::getUser();
		$pks        = (array) $pks;
		$table      = $this->getTable();
		
		// Iterate the items to delete each one.
		foreach ($pks as $i => $pk) {
			if ($table->load($pk)) {
				if ($this->canModerate($table)) {
					$context = $this->option.'.'.$this->name;
					// make sure the object has this variable
					if (!property_exists($table, $variable)) {
						continue;
					}
					// check to see if the object already has this value
					if ($state && $table->$variable == $state) {
						continue;
					}
					// handle the method
					if (!$table->$method($pk, $state)) {
						$this->setError($table->getError());
						return false;
					}
					// fire the event, if there is one
					if ($event) {
						$result = $dispatcher->trigger($event, array($context, array($pk), $state));
						if (in_array(false, $result, true)) {
							$this->setError($table->getError());
							return false;
						}
					}
				}
				else {
					// Prune items that you can't change.
					unset($pks[$i]);
					$error = $this->getError();
					if ($error) {
						JError::raiseWarning(500, $error);
						return false;
					}
					else {
						JError::raiseWarning(403, JText::_('COM_JCALPRO_ERROR_MODERATE_NOT_PERMITTED'));
						return false;
					}
				}
			}
			else {
				$this->setError($table->getError());
				return false;
			}
		}
		
		// Clear the component's cache
		$this->cleanCache();
		
		return true;
	}
}
