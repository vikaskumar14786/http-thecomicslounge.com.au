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

abstract class JCalProHelperUrl
{
	/**
	 * array of known Itemids, for creating our own internal urls
	 * 
	 * @var array
	 */
	protected static $lookup;
	
	/**
	 * Link to JCal Pro documentation
	 * 
	 * @return string
	 */
	public static function help() {
		return 'http://anything-digital.com/jcal-pro/learn-more/user-manual-v3.html';
	}
	
	/**
	 * creates an internal (Joomla) url string
	 * 
	 * @param unknown_type $params
	 * @param unknown_type $sef
	 */
	public static function _($params = array(), $sef = true) {
		// start building
		$urlparams = array();
		// always have com first
		$urlparams['option'] = 'com_jcalpro';
		if (isset($params['option'])) {
			$urlparams['option'] = $params['option'];
			unset($params['option']);
		}
		$urlparams = array_merge($urlparams, $params);
		// check our new array to see if we're handling our own urls (from com_jcalpro)
		// if so, we need to fetch the appropriate Itemid and add this to the url
		if ('com_jcalpro' == $urlparams['option']) {
			// here comes some fun - we can't just call this method without constructing a needles array :(
			$needles = null;
			// check if we have a view set in this url - if we do, add that Itemid
			if (array_key_exists('view', $urlparams)) {
				switch ($urlparams['view']) {
					case 'events':
						$needles = array();
						if (array_key_exists('layout', $urlparams)) {
							$layout  = $urlparams['layout'];
							$klayout = $urlparams['layout'];
							// hmm, I guess we don't want to do this with the modules?
							if (!JFactory::getApplication()->getUserState('com_jcalpro.events.jcalpro.module')) {
								// check the CURRENT config & see if we have category filters
								$filters = JCalPro::config('filter_category');
								$invert  = JCalPro::config('filter_category_invert');
								if (is_array($filters) && !empty($filters)) {
									// ok, we have filters - strip out "Root" if it's there
									asort($filters);
									if (0 == $filters[0]) {
										array_shift($filters);
									}
									// if we STILL have filters, implode them & get an md5, then append to the key
									if (!empty($filters)) {
										$klayout .= '_' . $invert . '_' . md5(serialize($filters));
									}
								}
							}
							$needles['events'] = array($klayout);
							if ($layout != $klayout) {
								$needles['events'][] = $layout;
							}
						}
						break;
					default:
						break;
				}
			}
			// apparently we can only append the Itemid if we don't have a task 
			if (array_key_exists('task', $urlparams)) {
				if (array_key_exists('Itemid', $urlparams)) {
					unset($urlparams['Itemid']);
				}
			}
			else {
				$Itemid = self::findItemid($needles);
				if ($Itemid && !array_key_exists('Itemid', $urlparams)) {
					$urlparams['Itemid'] = $Itemid;
				}
			}
		}
		// round 2
		$url = array();
		foreach ($urlparams as $name => $attr) {
			if (is_numeric($name)) {
				continue;
			}
			$url[] = $name . '=' . rawurlencode($attr);
		}
		$url = 'index.php?' . implode('&', $url);
		if ($sef) {
			$url = JRoute::_($url, false);
		}
		return $url;
	}
	
	/**
	 * static method to return the current url, with optional parameters
	 * 
	 * @param unknown_type $extra
	 * @param unknown_type $remove
	 * @param unknown_type $uri
	 */
	public static function page($extra = array(), $remove = array(), $uri = 'SERVER') {
		// get the current URI
		$uri = clone JURI::getInstance($uri);
		// add any extras if they are set
		if (!empty($extra)) {
			foreach ($extra as $key => $value) {
				// don't set numeric keys
				if (is_numeric($key)) {
					continue;
				}
				// set extra to the URI
				$uri->setVar($key, $value);
			}
		}
		// remove any keys if they are set
		if (!empty($remove)) {
			foreach ($remove as $key) {
				// don't use numeric keys
				if (is_numeric($key)) {
					continue;
				}
				// set extra to the URI
				$uri->delVar($key);
			}
		}
		// send back the URI as a string
		return $uri->toString();
	}
	
	/**
	 * static method to generate a JCalPro url based on "view"
	 * 
	 * @param unknown_type $view
	 * @param unknown_type $sef
	 * @param unknown_type $extra
	 */
	public static function view($view, $sef = true, $extra = array()) {
		$url = array('view'=>$view);
		if (!empty($extra)) {
			$url = array_merge($url, $extra);
		}
		return self::_($url, $sef);
	}
	
	/**
	 * static method to generate a JCalPro url based on "task"
	 * 
	 * @param $task
	 * @param $sef
	 * @param $extra
	 */
	public static function task($task, $sef = true, $extra = array()) {
		$url = array('task'=>$task);
		if (!empty($extra)) {
			$url = array_merge($url, $extra);
		}
		return self::_($url, $sef);
	}
	
	
	/**
	 * static method to generate a JCalPro location view url
	 * 
	 * @param unknown_type $id
	 * @param unknown_type $sef
	 * @param unknown_type $extra
	 */
	public static function location($id, $sef = true, $extra = array()) {
		return self::_slug($id, 'location', $sef, $extra);
	}
	
	
	/**
	 * static method to generate a JCalPro event view url
	 * 
	 * @param unknown_type $id
	 * @param unknown_type $sef
	 * @param unknown_type $extra
	 */
	public static function event($id, $sef = true, $extra = array()) {
		return self::_slug($id, 'event', $sef, $extra);
	}
	
	/**
	 * static method to generate a JCalPro url with a slug from "alias"
	 * 
	 * @param unknown_type $id
	 * @param unknown_type $view
	 * @param unknown_type $sef
	 * @param unknown_type $extra
	 */
	private static function _slug($id, $view, $sef = true, $extra = array()) {
		$view = strtolower($view);
		// we're going to store the event slugs here so we only load once
		static $slugs;
		if (!is_array($slugs)) {
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/tables');
			$slugs = array();
		}
		if (!array_key_exists($view, $slugs)) {
			$slugs[$view] = array();
		}
		// force our id to an int
		$id = intval($id);
		// set the main url properties
		$url = array('view' => $view, 'id' => $id);
		// add extra params if necessary
		if (!empty($extra)) {
			// check if we've been passed a slug and if not load the event & obtain it
			if (array_key_exists('slug', $extra)) {
				$slugs[$view][$id] = $extra['slug'];
				unset($extra['slug']);
			}
			// merge the data
			$url = array_merge($url, $extra);
		}
		// if we still don't have a slug, load it from the database
		if (!array_key_exists($id, $slugs[$view])) {
			$table = JTable::getInstance(ucwords($view), 'JCalProTable');
			if ($table->load($id)) {
				$slugs[$view][$id] = (!empty($table->alias) ? $table->alias : JApplication::stringURLSafe($table->title));
			}
		}
		// if we have a slug, reset id
		if (array_key_exists($id, $slugs[$view])) {
			$url['id'] = $id . ':' . $slugs[$view][$id];
		}
		// return our url
		return self::_($url, $sef);
	}
	
	/**
	 * static method to generate a JCalPro category view url
	 * 
	 * @param unknown_type $id
	 * @param unknown_type $sef
	 * @param unknown_type $extra
	 */
	public static function category($id, $sef = true, $extra = array()) {
		$url = array('view'=>'category', 'id'=>intval($id));
		if (!empty($extra)) {
			$url = array_merge($url, $extra);
		}
		return self::_($url, $sef);
	}
	
	/**
	 * static method to generate a JCalPro events view url
	 * 
	 * @param unknown_type $date
	 * @param unknown_type $layout
	 * @param unknown_type $sef
	 * @param unknown_type $extra
	 */
	public static function events($date = '', $layout = 'month', $sef = true, $extra = array()) {
		$url = array('view' => 'events');
		if (!empty($date)) {
			$url['date'] = $date;
		}
		if (!empty($layout)) {
			$url['layout'] = $layout;
		}
		if (!empty($extra)) {
			$url = array_merge($url, $extra);
		}
		return self::_($url, $sef);
	}
	
	/**
	 * static method to fetch the JCalPro media url
	 * 
	 * @param bool true for full, false for relative
	 */
	public static function media($relative = true) {
		$prefix = (JFactory::getApplication()->isAdmin() ? '../' : '');
		$root   = rtrim(JUri::base(), '/');
		return str_replace('/administrator/..', '', (($relative ? '' : "{$root}/") . "{$prefix}media/jcalpro"));
	}
	
	/**
	 * static method to get the user's uploaded files asset path
	 * this method will create an empty folder if none is found
	 * 
	 * @return string
	 */
	static public function uploads($relative = false) {
		jimport('joomla.filesystem.folder');
		// build the base for the user
		$user = JFactory::getUser();
		return self::media($relative) . '/uploads/' . $user->id;
	}
	
	/**
	 * static method to force a relative url to an absolute one
	 * 
	 * @param string $url
	 * @param bool $admin
	 * @return string the absolute url
	 */
	public static function toFull($url, $admin = false) {
		if (preg_match('/^https?\:\/{2}/', $url)) {
			return $url;
		}
		if ($admin) {
			return str_replace('/administrator/administrator', '/administrator', rtrim(str_replace('/administrator', '', JUri::root()), '/') . '/administrator/' . ltrim(str_replace(JUri::root(true), '', $url), '/'));
		}
		return str_replace('/administrator', '', rtrim(str_replace('/administrator', '', JUri::root()), '/') . '/' . ltrim(str_replace(JUri::root(true), '', $url), '/'));
	}
	
	/**
	 * private class method to find an Itemid for jcalpro
	 * code mostly borrowed from a core route helper
	 * 
	 * @param $needles
	 */
	public static function findItemid($needles = null) {
		// TODO: should we always return null in admin? hmm...
		// import the component helper so we can get the component id
		jimport('joomla.application.component.helper');
		// get our menu items
		$menus  = JFactory::getApplication()->getMenu('site');
		$active = $menus->getActive();
		// the following views have no ids - use layouts
		$noids  = array('events', 'categories', 'search');
		// prepare the lookup array
		if (is_null(self::$lookup)) {
			// start by setting as an array
			self::$lookup = array();
			// get the component
			$component = JComponentHelper::getComponent('com_jcalpro');
			// get the items from the menu that correspond to our component
			$items = $menus->getItems('component_id', $component->id);
			// debugging Itemids
			JCalPro::debugger('Menus', $menus);
			JCalPro::debugger('Active Menu', $active);
			JCalPro::debugger('Menu Items', $items);
			// if there ARE items, start looping them
			if (!empty($items)) {
				foreach ($items as $item) {
					// if there's a view associated with this menu item, use that as the key
					if (isset($item->query) && isset($item->query['view'])) {
						$view = $item->query['view'];
						// initialize this view's array in the lookup
						if (!isset(self::$lookup[$view])) {
							self::$lookup[$view] = array();
						}
						// events view will not have ids - it will have layouts :)
						if (in_array($view, $noids)) {
							$layout = (isset($item->query['layout']) ? $item->query['layout'] : 'default');
							// if the view is "events", we want to also key off the category filters
							if ('events' == $view) {
								$filters = $item->params->get('filter_category');
								$invert  = $item->params->get('filter_category_invert');
								$append  = '';
								// if there are no filters, we won't key off this one :)
								if (is_array($filters) && !empty($filters)) {
									// ok, we have filters - strip out "Root" if it's there
									asort($filters);
									if (0 == $filters[0]) {
										array_shift($filters);
									}
									// if we STILL have filters, implode them & get an md5, then append to the key
									if (!empty($filters)) {
										$append .= '_' . $invert . '_' . md5(serialize($filters));
									}
								}
								// if we have an append, we need to force the Itemid for ALL views based on this append
								if (!empty($append)) {
									foreach (array('month', 'flat', 'week', 'day', 'admin') as $l) {
										self::$lookup[$view][$l . $append] = $item->id;
									}
									// add append to layout
									$layout .= $append;
								}
							}
							self::$lookup[$view][$layout] = array_key_exists($layout, self::$lookup[$view]) && !empty(self::$lookup[$view][$layout]) ? self::$lookup[$view][$layout] : $item->id;
						}
						// add the menu item's id (Itemid) as the value of this lookup
						else if (isset($item->query['id'])) {
							self::$lookup[$view][$item->query['id']] = array_key_exists($item->query['id'], self::$lookup[$view]) ? self::$lookup[$view][$item->query['id']] : $item->id;
						}
					}
				}
			}
		}
		JCalPro::debugger('Itemids', self::$lookup);
		// if we have needles, look them up in our lookup array
		if (!empty($needles)) {
			foreach ($needles as $view => $ids) {
				if (isset(self::$lookup[$view])) {
					foreach($ids as $id) {
						// since we're doing lookups using params, we can't just check for the values in the $noids array
						//$id = (in_array($view, $noids) ? $id : intval($id));
						if (isset(self::$lookup[$view][$id])) {
							return self::$lookup[$view][$id];
						}
					}
					// reset the lookup & return the current
					reset(self::$lookup[$view]);
					return current(self::$lookup[$view]);
				}
			}
		}
		// no needles - return the active id, or prioritize the following list:
		// * categories view
		// * events view, month layout
		// * events view, flat layout
		// * events view, week layout
		// * events view, day layout
		// * search view
		//else {
			// NOTE: even if we have an active menu item, we only want to use it if it's ours
			if ($active && 'com_jcalpro' == $active->component) {
				return $active->id;
			}
			else {
				foreach ($noids as $noidview) {
					if (array_key_exists($noidview, self::$lookup) && !empty(self::$lookup[$noidview])) {
						// reset the lookup & return the current
						reset(self::$lookup[$noidview]);
						return current(self::$lookup[$noidview]);
					}
				}
			}
		//}
		// ouch, couldn't find anything - send back either the active id or nothing at all
		return $active ? $active->id : null;
	}
	
	/**
	 * Fetch/build the category path for constructing sef urls
	 * 
	 * @param int $id
	 * 
	 * @return array
	 */
	public static function getCategoryPath($id) {
		static $paths;
		$db  = JFactory::getDbo();
		$key = "cat_$id";
		$cat = JCategories::getInstance('JCalPro')->get($id);
		
		if (!is_array($paths)) {
			$paths = array();
		}
		if (!array_key_exists($key, $paths)) {
			if (is_object($cat) && method_exists($cat, 'getPath')) {
				$path = $cat->getPath();
			}
			if (empty($path)) {
				$db->setQuery($db->getQuery(true)
					->select('CASE WHEN CHAR_LENGTH(' . $db->quoteName('path') . ') THEN ' . $db->quoteName('path') . ' ELSE CONCAT_WS(' . $db->quote('-') . ', ' . $db->quoteName('id') . ', ' . $db->quoteName('alias') . ') END AS catpath')
					->from('#__categories')
					->where($db->quoteName('id') . ' = ' . (int) $id)
				);
				$path = array();
				try {
					$pathString = $db->loadResult();
				}
				catch (Exception $e) {
					$pathString = '';
				}
				if (!empty($pathString)) {
					$path = explode('/', (string) $pathString);
				}
			}
			$paths[$key] = $path;
		}
		return $paths[$key];
	}
	
}
