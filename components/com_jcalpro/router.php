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

jimport('joomla.application.categories');
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('url');

/**
 * Build the route for JCalPro
 * 
 * @param array $query
 * @return array The URL arguments to use to assemble the url
 */
function JcalproBuildRoute(&$query) {
	$segments = array();
	
	// fetch our menu
	$menu = JFactory::getApplication()->getMenu();
	
	// we need a menu item
	if (empty($query['Itemid'])) {
		$menuItem = $menu->getActive();
		$menuItemGiven = false;
	}
	else {
		$menuItem = $menu->getItem($query['Itemid']);
		$menuItemGiven = true;
	}
	
	$task = false;
	
	// handle tasks first!
	if (array_key_exists('task', $query)) {
		// save the task
		$task = $query['task'];
		// this menu item may point to a task
		if (!(($menuItem instanceof stdClass) && array_key_exists('task', $menuItem->query) && $menuItem->query['task'] == $query['task'])) {
			// split up the task into its view/layout combo & create a segment for layout-view
			$segments[] = preg_replace('/^([^\.]*?)\.(.*?)$/', '$2-$1', $task);
		}
		unset($query['task']);
		// special - registration tasks have an event_id
		if (preg_match('/^registration/', $task) && array_key_exists('event_id', $query)) {
			// TODO
		}
		return $segments;
	}
	
	// we may need to do things for particular views and layouts
	$view = false;
	$layout = false;
	
	// deal with the view being passed, if any
	if (array_key_exists('view', $query)) {
		// store the view for later
		$view = $query['view'];
		// are we dealing with a view that is attached to a menu item?
		// if not, append the view to the segment
		if (!(($menuItem instanceof stdClass) && array_key_exists('view', $menuItem->query) && $menuItem->query['view'] == $query['view'])) {
			// if the view is "event" or "category" we don't want the view in the url
			// unless the view is "event" and the layout is "edit" or "modal"
			if (!in_array($view, array('event', 'category'))
			|| ('event' == $view && array_key_exists('layout', $query) && in_array($query['layout'], array('edit', 'modal')))
			) {
				$segments[] = $view;
			}
		}
		// remove the view
		unset($query['view']);
	}
	
	// we only append & remove the layout in "events" view
	if (array_key_exists('layout', $query) && 'modal' != $query['layout']) {
		$layout = $query['layout'];
		// check the menu item to make sure the layout isn't already assigned
		// if not, add it to the segments
		if (!(($menuItem instanceof stdClass) && array_key_exists('layout', $menuItem->query) && $menuItem->query['layout'] == $query['layout'])) {
			$segments[] = $query['layout'];
		}
		// remove the layout
		unset($query['layout']);
	}
	
	// event views will have a segment for each category in the canonical path
	// and a final segment for the event itself
	if ('event' == $view) {
		// check for an id
		if (array_key_exists('id', $query)) {
			$id = $query['id'];
			if (false !== strpos(':', $id)) {
				list($id, $tmp) = explode(':', $id, 2);
			}
			$id = (int) $id;
			// fetch the proper alias and the canonical category id from the database
			$db = JFactory::getDbo();
			$db->setQuery($db->getQuery(true)
				// the event itself
				->select('Event.alias')
				->select('Event.title')
				->from('#__jcalpro_events AS Event')
				->where('Event.id='.$id)
				->group('Event.id')
				// join the xref table so we can get the category
				->leftJoin('#__jcalpro_event_categories AS Xref ON Xref.event_id = Event.id AND Xref.canonical = 1')
				->select('Xref.category_id')
			);
			// NOTE assignment here, NOT checking for equals!!!
			if ($data = $db->loadObject()) {
				// add the category path if we're not editing
				if ('edit' != $layout) {
					// merge the path into the segments
					$segments = array_merge($segments, JCalProHelperUrl::getCategoryPath($data->category_id));
				}
				// set the id segment
				$segments[] = $id . ':' . (empty($data->alias) ? JApplication::stringURLSafe($data->title) : $data->alias);
				unset($query['id']);
			}
		}
	}
	
	// handle locations
	if ('location' == $view) {
		// check for an id
		if (array_key_exists('id', $query)) {
			$id = $query['id'];
			if (false !== strpos(':', $id)) {
				list($id, $tmp) = explode(':', $id, 2);
			}
			$id = (int) $id;
			// fetch the proper alias
			$db = JFactory::getDbo();
			$db->setQuery($db->getQuery(true)
				->select('Location.alias')
				->select('Location.title')
				->from('#__jcalpro_locations AS Location')
				->where('Location.id='.$id)
			);
			// NOTE assignment here, NOT checking for equals!!!
			if ($data = $db->loadObject()) {
				// set the id segment
				$segments[] = $id . ':' . (empty($data->alias) ? JApplication::stringURLSafe($data->title) : $data->alias);
				unset($query['id']);
			}
		}
	}
	
	// handle single categories
	if ('category' == $view) {
		if (array_key_exists('id', $query)) {
			$id = $query['id'];
			if (false !== strpos(':', $id)) {
				list($id, $tmp) = explode(':', $id, 2);
			}
			$id = (int) $id;
			// add the category path
			$segments = array_merge($segments, JCalProHelperUrl::getCategoryPath($id));
			unset($query['id']);
		}
	}
	return $segments;
}

/**
 * Parse the route for JCalPro
 * 
 * @param array $segments
 * @return array An associative array containing the parts of the route
 */
function JcalproParseRoute($segments) {
	$vars = array();
	// fetch our menu
	$menu = JFactory::getApplication()->getMenu();
	$item = $menu->getActive();
	$db   = JFactory::getDBO();
	
	// Count route segments
	$count = count($segments);
	
	// switch over the first segment and act accordingly
	switch ($segments[0]) {
		// layout-view tasks
		// event
		case 'add:event':
		case 'edit:event':
		case 'checkdate:event':
		case 'catselect:event':
		// registration
		case 'add:registration':
		case 'confirm:registration':
		// search
		case 'search:search':
		// locations
		case 'add:location':
		case 'edit:location':
			// parse the segment back into the task
			$vars['task'] = preg_replace('/^([^:]*?):(.*?)$/', '$2.$1', $segments[0]);
			// TODO: check for id or event_id
			return $vars;
		// oddball views
		case 'locations':
		case 'categories':
		case 'search':
			$vars['view'] = $segments[0];
			return $vars;
		// "events" view layouts
		case 'events': // this is for missing Itemids
		case 'day':
		case 'flat':
		case 'month':
		case 'week':
		case 'admin':
		case 'all':
			$vars['view']   = 'events';
			$layoutKey = 'events' == $segments[0] ? 1 : 0;
			if (array_key_exists($layoutKey, $segments)) {
				$vars['layout'] = $segments[$layoutKey];
			}
			return $vars;
		// "registration" add/edit form
		// "event" add/edit form
		// "location" add/edit form
		// "media" add/edit form
		case 'event':
		case 'location':
		case 'media':
		case 'registration':
			// the first segment should be the view
			$vars['view']   = $segments[0];
			// if we have no more segments, just return
			if (1 == $count) {
				return $vars;
			}
			if ('location' == $segments[0]) {
				// check if the next segment is "edit" because if it is, this is the edit form
				// otherwise it's the actual location
				if ('edit' == $segments[1]) {
					$vars['layout'] = $segments[1];
				}
				else {
					$vars['id'] = (int) $segments[1];
				}
				return $vars;
			}
			else {
				$vars['layout'] = $segments[1];
				// if we have an id for event edit, add it
				if ('event' == $segments[0] && 2 < count($segments)) {
					$vars[('registration' == $segments[0] ? 'event_' : '') . 'id'] = (int) $segments[2];
				}
				return $vars;
			}
		// location view
		// "event" or "category" view
		default:
			// if there's only one segment, it's a root category - use our found catid and bail
			if (1 == $count) {
				if (false === strpos(':', $segments[0])) {
					$category = $segments[0];
				}
				else {
					list($category, $tmp) = explode(':', $segments[0], 2);
				}
				$vars['view'] = 'category';
				$vars['id'] = $category;
				return $vars;
			}
			// 2+ segments means it's either an event or a child category
			else {
				// we have 2+ segments, so this is either an event or a child category
				// so what we're going to do is try to load the xref for the last two segments
				// then check the event's alias - if it matches, we found an event
				if (array_key_exists($count - 1, $segments)) {
					if (false !== strpos($segments[$count - 1], ':')) {
						list($event, $tmp) = explode(':', $segments[$count - 1], 2);
					}
					else {
						$event = (int) $segments[$count - 1];
					}
				}
				else {
					$event = 0;
				}
				if (array_key_exists($count - 2, $segments)) {
					if (false !== strpos($segments[$count - 2], ':')) {
						list($category, $tmp) = explode(':', $segments[$count - 2], 2);
					}
					else {
						$category = (int) $segments[$count - 2];
					}
				}
				else {
					$category = 0;
				}
				
				// check for an xref entry - if we find one, we're dealing with an event
				$db->setQuery((string) $db->getQuery(true)
					->select('Xref.*')
					->from('#__jcalpro_event_categories AS Xref')
					->where('Xref.' . $db->quoteName('category_id') . ' = ' . (int) $category)
					->where('Xref.' . $db->quoteName('event_id') . ' = ' . (int) $event)
					// join in the event alias so we only have to make one trip
					->leftJoin('#__jcalpro_events AS Event ON Event.id = Xref.event_id')
					->select('Event.alias')
				);
				$xref = $db->loadObject();
				// if we have an xref, then we know we have an event
				if ($xref) {
					$vars['view'] = 'event';
					$vars['id'] = $event;
					//$vars['catid'] = $category;
					return $vars;
				}
				
				// we made it this far, so now we just assume we're looking for a child category
				$id = $event;
				$category = JCategories::getInstance('JCalPro')->get($id);
				// we must have found it
				if (!empty($category)) {
					$vars['view'] = 'category';
					$vars['id'] = $event;
					return $vars;
				}
			}
			break;
	}
	// now we have a problem - we don't know what the hell the user was after
	// so let's just throw a generic 404 that says "page not found"
	JError::raiseError(404, JText::_('COM_JCALPRO_ERROR_PAGE_NOT_FOUND'));
	return $vars;
}
