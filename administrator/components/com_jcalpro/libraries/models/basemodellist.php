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

jimport('joomla.application.component.modellist') or jimport('legacy.model.list');
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JLoader::register('JCalProHelperDate', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/date.php');

// load the event-specific language file
JCalPro::language('com_jcalpro.event', JPATH_ADMINISTRATOR);

/**
 * Base list model class
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProListModel extends JModelList
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $context  = 'com_jcalpro';

	/**
	 * The category context (allows other extensions to derived from this model).
	 *
	 * @var		string
	 */
	protected $_extension = 'com_jcalpro';

	private $_parent = null;

	private $_items = null;
	
	function __construct($config = array()) {
		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState($ordering, $direction);
		
		$this->setState('filter.extension', $this->_extension);
		
		$user = JFactory::getUser();
		// get published status
		$published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '', 'string');
		if ((!$user->authorise('core.edit.state', 'com_jcalpro')) &&  (!$user->authorise('core.edit', 'com_jcalpro'))) {
			// filter on published for those who do not have edit or edit.state rights.
			$this->setState('filter.published', 1);
		}
		else {
			$this->setState('filter.published', $published);
		}
		
		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		$params = JCalPro::config();
		$this->setState('params', $params);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 *
	 * @return	string		A store id.
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id	.= ':'.$this->getState('filter.extension');
		$id	.= ':'.$this->getState('filter.published');
		$id	.= ':'.$this->getState('filter.parentId');

		return parent::getStoreId($id);
	}
	
	/**
	 * The core "state" jhtml crap uses "archive" and we don't need that
	 * 
	 */
	public function getPublishedStatus() {
		$list = array(
			JHtml::_('select.option',  '',  JText::_('COM_JCALPRO_SELECT_PUBLISHED'))
		,	JHtml::_('select.option',  '1', JText::_('COM_JCALPRO_SELECT_PUBLISHED_OPTION_PUBLISHED'))
		,	JHtml::_('select.option',  '0', JText::_('COM_JCALPRO_SELECT_PUBLISHED_OPTION_UNPUBLISHED'))
		,	JHtml::_('select.option', '-2', JText::_('COM_JCALPRO_SELECT_PUBLISHED_OPTION_TRASHED'))
		);
		return $list;
	}
	
	/**
	 * give public read access to the model's context
	 * 
	 */
	public function getContext() {
		return (string) $this->context;
	}
	
	public function appendAuthorToQuery(&$query, $tablename, $created_by = 'created_by') {
		// clean our table name
		$tablename = JFilterInput::getInstance()->clean($tablename, 'cmd');
		// get our dbo for cleaning texts
		$db = JFactory::getDbo();
		// clean the created_by column
		$created_by = $db->quoteName($created_by);
		// our default names for guest & system
		$guest  = JText::_('COM_JCALPRO_AUTHOR_GUEST');
		$system = JText::_('COM_JCALPRO_AUTHOR_SYSTEM');
		// full column selection
		$column = $tablename . '.' . $created_by;
		// join over author
		$query
			->select('IF(' . $column . '=0,' . $db->Quote($guest) . ',IF(' . $column . '=-1,' . $db->Quote($system) . ',Author.name)) AS author_name')
			->select('IF(' . $column . '=0,' . $db->Quote(strtolower($guest)) . ',IF(' . $column . '=-1,' . $db->Quote(strtolower($system)) . ',Author.username)) AS author_username')
			->leftJoin("#__users AS Author ON Author.id = {$column}")
		;
	}
	
	/**
	 * Override internal _getListQuery method so we can fix JDatabaseQuery "union" bug
	 * 
	 * (non-PHPdoc)
	 * @see JModelList::_getListQuery()
	 */
	protected function _getListQuery() {
		$query = parent::_getListQuery();
		
		// check union - it's broken in core (?!)
		$union = (string) $query->union;
		if (!empty($union)) {
			$string = (string) $query;
			// check for "union" here - if we find it, then we know core is broken here
			if (false === stripos($string, 'union')) {
				$string = "$string " . (string) $query->union;
			}
			$query = $string;
		}
		else {
			$query = (string) $query;
		}
		
		return $query;
	}
	
	/**
	 * clear the model cache
	 * 
	 */
	public function clearModelCache() {
		$this->cache = array();
	}
}
