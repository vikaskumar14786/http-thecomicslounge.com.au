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

JLoader::register('JCalProBaseControllerAdmin', JCalProHelperPath::library() . '/controllers/basecontrolleradmin.php');

JCalPro::registerHelper('access');

class JCalProControllerForms extends JCalProBaseControllerAdmin
{
	public function getModel($name='Form', $prefix = 'JCalProModel') {
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}
	
	public function setDefault() {
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		// Initialise variables.
		$pks = JFactory::getApplication()->input->post->get('cid', array(), 'array');
		
		try {
			if (empty($pks)) {
				throw new Exception(JText::_('COM_JCALPRO_NO_FORM_SELECTED'));
			}
			JArrayHelper::toInteger($pks);
			
			// Pop off the first element.
			$id = array_shift($pks);
			$model = $this->getModel();
			$model->setDefault($id);
			$this->setMessage(JText::_('COM_JCALPRO_SUCCESS_DEFAULT_FORM_SET'));
		}
		catch (Exception $e) {
			JError::raiseWarning(500, $e->getMessage());
		}
		$this->setRedirect('index.php?option=com_jcalpro&view=forms');
	}
	
	public function unsetDefault() {
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		// Initialise variables.
		$pks = JFactory::getApplication()->input->post->get('cid', array(), 'array');
		
		try {
			if (empty($pks)) {
				throw new Exception(JText::_('COM_JCALPRO_NO_FORM_SELECTED'));
			}
			JArrayHelper::toInteger($pks);
			
			// Pop off the first element.
			$id = array_shift($pks);
			$model = $this->getModel();
			$model->unsetDefault($id);
			$this->setMessage(JText::_('COM_JCALPRO_SUCCESS_DEFAULT_FORM_UNSET'));
		}
		catch (Exception $e) {
			JError::raiseWarning(500, $e->getMessage());
		}
		$this->setRedirect('index.php?option=com_jcalpro&view=forms');
	}
	
	public function saverules() {
		JCalProHelperAccess::saveRulesWithRedirect('forms');
	}
}
