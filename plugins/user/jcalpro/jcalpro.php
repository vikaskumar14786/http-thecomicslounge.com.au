<?php
/**
 * @package		JCalPro
 * @subpackage	plg_user_jcalpro

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

jimport('joomla.filesystem.file');
jimport('joomla.plugin.plugin');
// we HAVE to force-load the helper here to prevent fatal errors!
$helper = JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/jcalpro.php';
if (JFile::exists($helper)) require_once $helper;

class plgUserJCalPro extends JPlugin
{
	public static $com = 'com_jcalpro';
	
	/**
	 * Constructor
	 * 
	 * @param unknown_type $subject
	 * @param unknown_type $config
	 */
	public function __construct(&$subject, $config) {
		// if something happens & the helper class can't be found, we don't want a fatal error here
		if (class_exists('JCalPro')) {
			JCalPro::language('plg_user_jcalpro.sys', JPATH_ADMINISTRATOR);
		}
		else {
			JFactory::getLanguage()->load('plg_user_jcalpro.sys', JPATH_ADMINISTRATOR);
			JFactory::getApplication()->enqueueMessage(JText::_('PLG_USER_JCALPRO_COMPONENT_NOT_INSTALLED'));
		}
		parent::__construct($subject, $config);
	}
	
	/**
	 * @param	string	$context	The context for the data
	 * @param	int		$data		The user id
	 * @param	object
	 *
	 * @return	boolean
	 */
	function onContentPrepareData($context, $data) {
		// Check we are manipulating a valid form.
		if (!in_array($context, array('com_users.profile','com_users.user', 'com_users.registration', 'com_admin.profile'))) {
			return true;
		}

		if (is_object($data)) {
			$userId = isset($data->id) ? $data->id : 0;

			if (!isset($data->jcalpro) and $userId > 0) {
				// Load the profile data from the database.
				$db = JFactory::getDbo();
				$db->setQuery(
					'SELECT profile_key, profile_value FROM #__user_profiles' .
					' WHERE user_id = '.(int) $userId." AND profile_key LIKE 'jcalpro.%'" .
					' ORDER BY ordering'
				);
				$results = $db->loadRowList();

				// Check for a database error.
				if ($db->getErrorNum()) {
					$this->_subject->setError($db->getErrorMsg());
					return false;
				}

				// Merge the profile data.
				$data->jcalpro = array();

				foreach ($results as $v) {
					$k = str_replace('jcalpro.', '', $v[0]);
					$data->jcalpro[$k] = $v[1];
				}
			}
			if (!JHtml::isRegistered('users.jcalpro_send_mail')) {
				JHtml::register('users.jcalpro_send_mail', array(__CLASS__, 'jhtmlSendEmail'));
			}
		}

		return true;
	}
	
	/**
	 * method to display the email status
	 * 
	 * @param  mixed $value
	 * @return string
	 */
	public function jhtmlSendEmail($value) {
		// TODO: make this nicer, with an ajax update action or at least an icon :)
		return JText::_((1 == (int) $value ? 'JYES' : 'JNO'));
	}
	
	/**
	 * Adds the necessary fields to the user profile
	 * 
	 * @param JForm $form
	 */
	public function onContentPrepareForm($form) {
		// make sure form is a JForm
		if (!($form instanceof JForm)) {
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}
		// Check we are manipulating a valid form.
		if (!in_array($form->getName(), array('com_admin.profile', 'com_users.user', 'com_users.registration', 'com_users.profile'))) {
			return true;
		}
		// Add the email fields to the form.
		JForm::addFormPath(dirname(__FILE__).'/forms');
		$form->loadFile('jcaluser', false);
		
		return true;
	}
	
	/**
	 * Saves the user data to the profiles table
	 * 
	 * @param $data
	 * @param $isNew
	 * @param $result
	 * @param $error
	 */
	function onUserAfterSave($data, $isNew, $result, $error) {
		$userId	= JArrayHelper::getValue($data, 'id', 0, 'int');

		if ($userId && $result && isset($data['jcalpro']) && (count($data['jcalpro']))) {
			try {
				$db = JFactory::getDbo();
				$db->setQuery(
					'DELETE FROM #__user_profiles WHERE user_id = '.$userId .
					" AND profile_key LIKE 'jcalpro.%'"
				);

				if (!$db->query()) {
					throw new Exception($db->getErrorMsg());
				}

				$tuples = array();
				$order	= 1;

				foreach ($data['jcalpro'] as $k => $v) {
					$tuples[] = '('.$userId.', '.$db->quote('jcalpro.'.$k).', '.$db->quote($v).', '.$order++.')';
				}

				$db->setQuery('INSERT INTO #__user_profiles VALUES '.implode(', ', $tuples));

				if (!$db->query()) {
					throw new Exception($db->getErrorMsg());
				}

			}
			catch (JException $e) {
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}

		return true;
	}

	/**
	 * Remove all user profile information for the given user ID
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param	array		$user		Holds the user data
	 * @param	boolean		$success	True if user was succesfully stored in the database
	 * @param	string		$msg		Message
	 */
	function onUserAfterDelete($user, $success, $msg) {
		if (!$success) {
			return false;
		}

		$userId	= JArrayHelper::getValue($user, 'id', 0, 'int');

		if ($userId) {
			try {
				$db = JFactory::getDbo();
				$db->setQuery(
					'DELETE FROM #__user_profiles WHERE user_id = '.$userId .
					" AND profile_key LIKE 'jcalpro.%'"
				);

				if (!$db->query()) {
					throw new Exception($db->getErrorMsg());
				}
			}
			catch (JException $e) {
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}

		return true;
	}
}
