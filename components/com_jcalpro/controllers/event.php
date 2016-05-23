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

JLoader::register('JCalProEventController', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/controllers/basecontrollerevent.php');

/**
 * JCalPro Event Controller
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProControllerEvent extends JCalProEventController
{
	public function getModel($name='Event', $prefix = 'JCalProModel') {
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}
	
	public function getView($name = 'Event', $type = '', $prefix = 'JCalProView', $config = array()) {
		return parent::getView($name, $type, $prefix, $config);
	}
	
	public function save($key = null, $urlVar = null) {
		return parent::save($key, $urlVar);
	}
	
	protected function postSaveHook(&$model, $validData = array()) {
		$task = $this->getTask();
		switch ($task) {
			// we don't do anything on apply
			case 'apply': return;
			// on save/save2new we need to redirect to the item if it's published & approved
			default:
				try {
					$id = (int) $model->getState('event.id');
					$isNew = (bool) $model->getState('event.new');
					if ($id && !$isNew) {
						// ensure it's published & approved by loading the event
						JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/tables');
						$table = JTable::getInstance('Event', 'JCalProTable');
						if ($table->load($id) && $table->id == $id && $table->published && $table->approved) {
							$this->setRedirect(JCalProHelperUrl::event($id, true, array('slug' => $table->alias)));
						}
					}
				}
				catch (Exception $e) {
					JFactory::getApplication()->enqueueMessage($e->getMessage());
				}
				return;
		}
	}
}
