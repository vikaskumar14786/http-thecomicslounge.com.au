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
JCalPro::registerHelper('url');

abstract class JCalProHelperAccess
{
	private static $_actions = array(
		'core.admin', 'core.manage', 'core.create', 'core.create.private',
		'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete', 'core.moderate'
	);

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param	int		The category ID.
	 * @return	JObject
	 */
	public static function getActions($categoryId = 0)
	{
		$result	= new JObject;
		
		foreach (self::$_actions as $action) {
			$result->set($action,	self::canDo($action, $categoryId ? $categoryId : null));
		}
		
		return $result;
	}
	
	/**
	 * determines if the given (or current) user can add new events
	 * 
	 * @param mixed  $catid
	 * @param mixed  $uid
	 */
	public static function canAddEvents($catid = null, $uid = null) {
		$user = JFactory::getUser($uid);
		return (self::canDo('core.create', $catid, $uid) || (self::canDo('core.create.private', $catid, $uid) && $user->id));
	}
	
	/**
	 * determines if the given (or current) user can moderate events
	 * 
	 * @param mixed  $catid
	 * @param mixed  $uid
	 */
	public static function canModerateEvents($catid = null, $uid = null) {
		return self::canDo('core.moderate', $catid, $uid);
	}
	
	/**
	 * determines if the given (or current) user can feature events
	 * 
	 * @param mixed  $catid
	 * @param mixed  $uid
	 */
	public static function canFeatureEvents($catid = null, $uid = null) {
		return self::canModerateEvents($catid, $uid) && self::canPublishEvents($catid, $uid);
	}
	
	/**
	 * determines if the given (or current) user can change events states
	 * 
	 * @param mixed  $catid
	 * @param mixed  $uid
	 */
	public static function canPublishEvents($catid = null, $uid = null) {
		return self::canDo('core.edit.state', $catid, $uid);
	}
	
	/**
	 * determines if the given (or current) user can delete events
	 * 
	 * @param mixed  $catid
	 * @param mixed  $uid
	 */
	public static function canDeleteEvents($catid = null, $uid = null) {
		return self::canDo('core.delete', $catid, $uid);
	}
	
	/**
	 * determines if the given (or current) user can perform the given action(s)
	 * 
	 * @param string $action
	 * @param mixed  $catid
	 * @param mixed  $uid
	 */
	public static function canDo($action, $catid = null, $uid = null) {
		// set the catids array
		$catids = array();
		// some variables
		$user  = JFactory::getUser($uid);
		$app   = JFactory::getApplication();
		// special case - guests CAN NOT create private events!
		if ('core.create.private' == $action && empty($user->id)) {
			return false;
		}
		// catid is a bit of a mislabel
		switch ($catid) {
			// these special cases just need a return
			case 'registrations':
			case 'locations':
				return $user->authorise($action, JCalPro::COM . '.' . $catid);
				break;
		}
		// we need to know if the user has already selected a catid
		// because if they have, we can just check that category
		// if not, we have to check all the categories
		if ($catid) {
			// add to the stack
			$catids[] = $catid;
		}
		// get all the available categories using the events model and check each
		else {
			JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');
			JCalProBaseModel::addIncludePath(($app->isAdmin() ? JPATH_ADMINISTRATOR : JPATH_ROOT) . '/components/' . JCalPro::COM . '/models');
			$eventsModel = JCalPro::getModelInstance('Events', 'JCalProModel');
			$eventsModel->setState('filter.published', '1');
			$categories = $eventsModel->getCategories();
			if (!empty($categories)) {
				foreach ($categories as $cat) {
					$catids[] = $cat->id;
				}
			}
		}
		JArrayHelper::toInteger($catids);
		
		// start checking if we can add
		if (!empty($catids)) {
			foreach ($catids as $cid) {
				$addToCat = $user->authorise($action, JCalPro::COM . '.category.' . $cid);
				// great, we can add in one of the categories
				if ($addToCat) {
					return true;
				}
			}
		}
		else {
			return $user->authorise($action, JCalPro::COM);
		}
		
		// if we got this far, we can't add
		return false;
	}
	
	/**
	 * Gets an object containing id & level of parent asset for JCal Pro 
	 * 
	 * @param string $name Name of section, blank for none
	 * @return object
	 */
	static public function getParent($name = '') {
		$db = JFactory::getDbo();
		return $db->setQuery($db->getQuery(true)
			->select('id, level')
			->from('#__assets')
			->where('name = ' . $db->Quote('com_jcalpro' . (empty($name) ? '' : '.' . $name)))
		)->loadObject();
	}
	
	/**
	 * Creates an asset JTable and saves the bind data
	 * 
	 * @param mixed $bind data to bind to JTable
	 * 
	 * @throws Exception
	 */
	static public function saveAsset($bind) {
		$asset = JTable::getInstance('Asset');
		$bind  = (array) $bind;
		// save our asset
		if (!$asset->bind($bind)) {
			throw new Exception(JText::_('COM_JCALPRO_PERMISSIONS_ERROR_BIND'));
		}
		if (!$asset->check()) {
			throw new Exception(JText::_('COM_JCALPRO_PERMISSIONS_ERROR_CHECK'));
		}
		if (!$asset->store()) {
			throw new Exception(JText::_('COM_JCALPRO_PERMISSIONS_ERROR_STORE'));
		}
		if (!$asset->moveByReference($bind['parent_id'], 'last-child')) {
			throw new Exception(JText::_('COM_JCALPRO_PERMISSIONS_ERROR_MOVE'));
		}
	}
	
	static public function sanitizeRules($rules) {
		$saferules = array();
		// sanitize the rules
		foreach ($rules as $action => $identities) {
			if (!array_key_exists($action, $saferules)) {
				$saferules[$action] = array();
			}
			if (!empty($identities)) {
				foreach ($identities as $group => $permission) {
					if ('' == $permission) {
						continue;
					}
					$saferules[$action][$group] = (int) ((bool) $permission);
				}
			}
		}
		return $saferules;
	}
	
	static public function saveRules($name, $rules = null, $checktoken = true) {
		if ($checktoken) {
			JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		}
		if (!JFactory::getUser()->authorise('core.admin', JCalPro::COM)) {
			JError::raiseError(403, JText::_('COM_JCALPRO_PERMISSIONS_SAVE_NOT_AUTH'));
			jexit();
		}
		// pull rules from request if needed
		if (is_null($rules)) {
			$rules = JFactory::getApplication()->input->post->get('rules', array(), 'array');
		}
		// if we have no rules, then there's something amiss
		if (empty($rules)) {
			throw new Exception(JText::_('COM_JCALPRO_PERMISSIONS_NO_RULES'));
		}
		// save our rules to the assets table
		else {
			$saferules = self::sanitizeRules($rules);
			// find our parent asset
			$parent = self::getParent();
			// create our bind data
			$bind = array(
				'rules' => json_encode($saferules)
			,	'name' => JCalPro::COM . '.' . $name
			,	'title' => JText::_('COM_JCALPRO_' . $name . '_PERMISSIONS')
			,	'level' => ((int) $parent->level) + 1
			,	'parent_id' => $parent->id
			,	'id' => self::getParent($name)->id
			);
			// save our asset
			self::saveAsset($bind);
		}
		return true;
	}
	
	static public function saveRulesWithRedirect($name) {
		$app   = JFactory::getApplication();
		$url   = JCalProHelperUrl::view($name, false);
		$msg   = JText::_('COM_JCALPRO_PERMISSIONS_SAVE_SUCCESS');
		$type  = 'message';
	
		try {
			JCalProHelperAccess::saveRules($name);
		}
		catch (Exception $e) {
			$msg  = $e->getMessage();
			$type = 'error';
		}
		$app->redirect($url, $msg, $type);
		jexit();
	}
}
