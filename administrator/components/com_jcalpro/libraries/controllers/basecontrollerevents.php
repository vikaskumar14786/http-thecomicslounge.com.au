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

class JCalProEventsController extends JCalProBaseControllerAdmin
{
	
	/**
	 * Constructor.
	 * 
	 * @param   array  $config  An optional associative array of configuration settings.
	 * 
	 * @see     JController
	 */
	public function __construct($config = array()) {
		parent::__construct($config);
		// Define standard task mappings.
		$this->registerTask('unpublish', 'publish');     // value = 0
		$this->registerTask('trash', 'publish');     // value = -2
		$this->registerTask('unapprove', 'approve');
		$this->registerTask('unfeature', 'feature');
	}
	
	
	public function getModel($name='Event', $prefix = 'JCalProModel') {
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}
	
	/**
	 * override publish so we can handle children
	 * 
	 * @return  void
	 */
	function publish() {
		$children = $this->_appendChildrenToRequest();
		$count    = count($children);
		// handle the actual publish
		$publish  = parent::publish();
		// now that the publication status has been changed for the events,
		// update the database to detach any children that do not share published status with their parents
		$this->_detachChanged('published');
		// if we handled any children, tell the user
		if (!empty($children)) {
			JFactory::getApplication()->enqueuemessage(JText::sprintf('COM_JCALPRO_' . strtoupper($this->getTask()) . 'ED_N_CHILDREN', $count, JText::_('COM_JCALPRO_CHILD' . (1 == $count ? '' : 'REN'))));
		}
		return $publish;
	}
	
	/**
	 * override delete so we can handle children 
	 * 
	 * @return  void
	 */
	function delete() {
		$children = $this->_appendChildrenToRequest();
		$count    = count($children);
		$delete   = parent::delete();
		// if we deleted any children, tell the user
		if (!empty($children)) {
			JFactory::getApplication()->enqueuemessage(JText::sprintf('COM_JCALPRO_DELETED_N_CHILDREN', $count, JText::_('COM_JCALPRO_CHILD' . (1 == $count ? '' : 'REN'))));
		}
		return $delete;
	}
	
	function feature() {
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

		// Get items to remove from the request.
		$cid	 = JFactory::getApplication()->input->get('cid', array(), 'array');
		$data  = array('feature' => 1, 'unfeature' => 0);
		$task  = $this->getTask();
		$value = JArrayHelper::getValue($data, $task, 0, 'int');

		if (!is_array($cid) || count($cid) < 1) {
			JError::raiseWarning(500, JText::_($this->text_prefix.'_NO_ITEM_SELECTED'));
		}
		else {
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);

			// Remove the items.
			if ($model->feature($cid, $value)) {
				$this->_detachChanged('featured');
				$this->setMessage(JText::plural($this->text_prefix.'_N_ITEMS_'.(0 == $value ? 'UN' : '').'FEATURED', count($cid)));
			} else {
				$this->setMessage($model->getError());
			}
		}

		$this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list/*.$this->getRedirectToListAppend()*/, false));
	}

	/**
	 * approves an item.
	 *
	 * @return  void
	 */
	function approve() {
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

		// Get items to remove from the request.
		$cid	 = JFactory::getApplication()->input->get('cid', array(), 'array');
		$data  = array('approve' => 1, 'unapprove' => 0);
		$task  = $this->getTask();
		$value = JArrayHelper::getValue($data, $task, 0, 'int');

		if (!is_array($cid) || count($cid) < 1) {
			JError::raiseWarning(500, JText::_($this->text_prefix.'_NO_ITEM_SELECTED'));
		}
		else {
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);

			// Remove the items.
			if ($model->approve($cid, $value)) {
				$this->_detachChanged('approved');
				$this->setMessage(JText::plural($this->text_prefix.'_N_ITEMS_'.(0 == $value ? 'UN' : '').'APPROVED', count($cid)));
			} else {
				$this->setMessage($model->getError());
			}
		}

		$this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list/*.$this->getRedirectToListAppend()*/, false));
	}
	
	private function _detachChanged($column) {
		$column = preg_replace('/[^a-z_]/', '', $column);
		$db = JFactory::getDbo();
		// we have to select, then update, as we'll get errors if we try to do this as a subquery
		$db->setQuery((string) $db->getQuery(true)
			->select('Child.id')
			->from('#__jcalpro_events AS Child')
			->leftJoin('#__jcalpro_events AS Parent ON Child.rec_id = Parent.id')
			->where('Child.detached_from_rec = 0')
			->where('Child.' . $column . ' <> Parent.' . $column)
			->group('Child.id')
		);
		try {
			$ids = $db->loadColumn();
		}
		catch (Exception $e) {
			JCalProHelperLog::errorMessage(JText::_($e->getMessage()));
			return;
		}
		if (!empty($ids)) {
			// exeucute real query
			$db->setQuery((string) $db->getQuery(true)
				->update('#__jcalpro_events')
				->set($db->quoteName('detached_from_rec') . '=1')
				->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')')
			);
			try {
				$db->query();
			}
			catch (Exception $e) {
				JCalProHelperLog::errorMessage(JText::_($e->getMessage()));
			}
		}
	}
	
	private function _appendChildrenToRequest() {
		$input    = JFactory::getApplication()->input;
		$cid      = $input->get('cid', array(), 'array');
		$db       = JFactory::getDbo();
		$children = array();
		if (is_array($cid) && !empty($cid)) {
			JArrayHelper::toInteger($cid);
			$db->setQuery((string) $db->getQuery(true)
				->select('id')
				->from('#__jcalpro_events')
				->where($db->quoteName('id') . ' NOT IN (' . implode(',', $cid) . ')')
				->where($db->quoteName('rec_id') . ' IN (' . implode(',', $cid) . ')')
				->where($db->quoteName('detached_from_rec') . ' = 0')
			);
			$children = $db->loadColumn();
			if (!empty($children)) {
				$input->set('cid', array_unique(array_merge($cid, $children)));
			}
			// reset $children, in case the query comes back as false
			else {
				$children = array();
			}
		}
		return $children;
	}
}
