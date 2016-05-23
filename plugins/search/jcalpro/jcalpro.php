<?php
/**
 * @package		JCalPro
 * @subpackage	plg_search_jcalpro

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

jimport('joomla.plugin.plugin');
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');

class plgSearchJCalPro extends JPlugin
{
	/**
	 * Constructor
	 * 
	 * @param unknown_type $subject
	 * @param unknown_type $config
	 */
	public function __construct(&$subject, $config) {
		JCalPro::language('plg_search_jcalpro.sys', JPATH_ADMINISTRATOR);
		parent::__construct($subject, $config);
	}
	
	/**
	 * @return array An array of search areas
	 */
	function onContentSearchAreas() {
		static $areas = array();
		if (empty($areas)) $areas['jcalpro'] = JText::_('PLG_SEARCH_JCALPRO_JCALPRO');
		return $areas;
	}
	
	/**
	 * JCalPro Search method
	 *
	 * The sql must return the following fields that are used in a common display
	 * routine: href, title, section, created, text, browsernav
	 * 
	 * @param string Target search string
	 * @param string matching option, exact|any|all
	 * @param string ordering option, newest|oldest|popular|alpha|category
	 * @param mixed An array if the search it to be restricted to areas, null if search all
	 */
	function onContentSearch($text, $phrase='', $ordering='', $areas=null) {
		// check areas
		if (is_array($areas)) {
			if (!array_intersect($areas, array_keys($this->onContentSearchAreas()))) {
				return array();
			}
		}
		// make sure search is not empty!!!
		if (empty($text)) {
			return array();
		}
		// get our model
		JCalProBaseModel::addIncludePath(JPATH_SITE . '/components/com_jcalpro/models', 'JCalProModel');
		$model = JCalProBaseModel::getInstance('Events', 'JCalProModel', array('ignore_request' => true));
		// set the state
		$model->setState('filter.search', $text);
		$model->setState('filter.search.phrase', $phrase);
		// set up the ordering
		$catorder  = false;
		$order     = false;
		$direction = 'ASC';
		switch ($ordering) {
			case 'oldest':
				$order = 'Event.created';
				break;
			case 'alpha':
				$order = 'Event.title';
				break;
			case 'category':
				$catorder = true;
				break;
			case 'popular':
			case 'newest':
			default:
				$order     = 'Event.created';
				$direction = 'DESC';
		}
		if ($order) {
			$model->setState('list.ordering', $order);
			$model->setState('list.direction', $direction);
		}
		// get the items
		try {
			$items = $model->getItems();
		}
		catch (Exception $e) {
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return array();
		}
		// now we fiter the items to only send back what we want
		$results = array();
		if (!empty($items)) {
			// since the categories don't come through the main category, we have to order it ourselves
			if ($catorder) {
				usort($items, array('plgSearchJCalPro', 'sortByCategoryHandler'));
			}
			foreach ($items as $item) {
				$result = new stdClass();
				$result->href       = $item->href;
				$result->title      = $item->title;
				$result->section    = property_exists($item->categories, 'canonical') && !empty($item->categories->canonical) ? $item->categories->canonical->title : '';
				$result->created    = $item->created;
				$result->text       = $item->description;
				$result->browsernav = 1;
				$results[] = $result;
			}
		}
		// all done
		return $results;
	}
	
	/**
	 * callback handler for ordering by category
	 * 
	 * @param object $a
	 * @param object $b
	 * 
	 * @return int
	 */
	static public function sortByCategoryHandler($a, $b) {
		// get our categories
		$atitle = strtolower($a->categories->canonical->title);
		$btitle = strtolower($b->categories->canonical->title);
		// check
		return ($atitle == $btitle ? 0 : ($atitle > $btitle ? 1 : -1));
	}
}
