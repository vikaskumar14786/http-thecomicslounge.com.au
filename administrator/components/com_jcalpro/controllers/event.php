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
JLoader::register('JCalProEventController', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/controllers/basecontrollerevent.php');

class JCalProControllerEvent extends JCalProEventController
{
	public function __construct($config = array()) {
		JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');
		JCalProBaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/models');
		$model      = JCalPro::getModelInstance('Events', 'JCalProModel');
		$categories = $model->getCategories();
		if (empty($categories)) {
			JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_jcalpro&view=events', false), JText::_('COM_JCALPRO_CANNOT_CREATE_EVENTS_WITHOUT_CATEGORIES'), 'error');
			jexit();
		}
		parent::__construct($config);
	}
	
	/**
	 * method to import local files (wrapper method)
	 * 
	 * @return bool
	 */
	public function importlocal() {
		return $this->_import(true);
	}
	
	/**
	 * method to import remote files (wrapper method)
	 * 
	 * @return bool
	 */
	public function importremote() {
		return $this->_import(false);
	}
	
	/**
	 * private method to call model to import files
	 * 
	 * @param bool $local
	 * @return bool
	 */
	private function _import($local) {
		// check our token
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$app = JFactory::getApplication();
		// Set the model
		$model  = JCalPro::getModelInstance('Event', 'JCalProModel');
		// Preset the redirect
		$this->setRedirect(JRoute::_('index.php?option=com_jcalpro&view=events'.$this->getRedirectToListAppend(), false));
		// wtf?
		if (!method_exists($model, 'import')) {
			$app->enqueuemessage(JText::sprintf('COM_JCALPRO_MODEL_NO_METHOD', get_class($model), 'import'), 'error');
			return false;
		}
		// attempt to import
		if ($model->import($local)) {
			$app->enqueuemessage(JText::_('COM_JCALPRO_IMPORT_SUCCESS'));
			return true;
		}
		else {
			$app->enqueuemessage(JText::sprintf('COM_JCALPRO_IMPORT_FAILURE', $model->getError()), 'error');
			return false;
		}
	}
}
