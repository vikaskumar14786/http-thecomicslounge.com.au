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

// this is awful
global $sh404sefextplugincom_jcalpro_params;

// get a database object
$db = JFactory::getDbo();

// we need to get the params from the plugin Sh404sefExtpluginCom_jcalpro
if (!isset($extPlugin)) {
	JError::raiseError(E_ERROR, JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_ERROR_NO_PLUGIN'));
	jexit();
}
// ensure the plugin has params - if it doesn't then we need to get them directly
if (!is_object($extPlugin) || !property_exists($extPlugin, 'params') || empty($extPlugin->params)) {
	if (empty($sh404sefextplugincom_jcalpro_params)) {
		$db->setQuery($db->getQuery(true)
			->select('params')
			->from('#__extensions')
			->where($db->quoteName('type') . ' = ' . $db->Quote('plugin'))
			->where($db->quoteName('folder') . ' = ' . $db->Quote('sh404sefextplugins'))
			->where($db->quoteName('element') . ' = ' . $db->Quote('sh404sefextplugincom_jcalpro'))
		);
		$sh404sefextplugincom_jcalpro_params = $db->loadResult();
	}
	$params = $sh404sefextplugincom_jcalpro_params;
	if (empty($params)) {
		$params = '';
	}
}
else {
	$params = $extPlugin->params;
}
// ensure we have plugin params as a JRegistry
if (!($params instanceof JRegistry)) {
	$registry = new JRegistry();
	$registry->loadString($params);
}
else {
	$registry = $params;
}
// now set our params as variables for easier reference later
$pCategories    = $registry->def('seo_categories', '');
$pEventAlias    = (int) $registry->def('seo_event_alias', 1);
$pCategoryAlias = (int) $registry->def('seo_category_alias', 1);
$pMenuAlias     = (int) $registry->def('seo_menu_item_alias', 0);
$pViewName      = (int) $registry->def('seo_insert_view_name', 1);
$pEventId       = (int) $registry->def('seo_insert_event_id', 1);
$pCategoryId    = (int) $registry->def('seo_insert_category_id', 1);
$pDate          = (int) $registry->def('seo_insert_date', 1);
$pDateSep       = (int) $registry->def('seo_date_separator', 0);

// ------------------  standard plugin initialize function - don't change ---------------------------
global $sh_LANG;
$sefConfig = & Sh404sefFactory::getConfig();
$shLangName = '';
$shLangIso = '';
$title = array();
$shItemidString = '';
$dosef = shInitializePlugin($lang, $shLangName, $shLangIso, $option);
if ($dosef == false) {
	return;
}
// ------------------  standard plugin initialize function - don't change ---------------------------

// ------------------  load language file - adjust as needed ----------------------------------------
//$shLangIso = shLoadPluginLanguage('com_jcalpro_lang', $shLangIso, '_JCL_SH404SEF_CALENDAR', dirname(__FILE__));
// ------------------  load language file - adjust as needed ----------------------------------------

// remove common URL from GET vars list, so that they don't show up as query string in the URL
shRemoveFromGETVarsList('option');
shRemoveFromGETVarsList('lang');
if (!empty($Itemid)) {
	shRemoveFromGETVarsList('Itemid');
}
if (!empty($limit)) {
	shRemoveFromGETVarsList('limit');
}
if (isset($limitstart)) {
	shRemoveFromGETVarsList('limitstart'); // limitstart can be zero
}

// start by inserting the menu element title
foreach (array('view', 'layout', 'format', 'task', 'tmpl', 'id', 'Itemid') as $var) {
	$$var = isset($$var) ? $$var : null;
}
$shJCalPro = shGetComponentPrefix($option);
if (empty($shJCalPro)) {
	$shJCalPro = getMenuTitle($option, $view, $Itemid, null, $shLangName);
}
if (empty($shJCalPro) || $shJCalPro == '/') {
	$shJCalPro = 'JCalPro';
}

if ($pMenuAlias) {
	$title[] = $shJCalPro;
	$title[] = '/';
}

// skip all modal/tmpl requests

if ('modal' == $layout || 'component' == $tmpl || 'json' == $format || !empty($task)) {
	$dosef = false;
}

if ($dosef) {switch ($view) {
	case 'media':
	default:
		$dosef = false;
		break;
	case '':
		shRemoveFromGETVarsList('view');
		break;
	case 'search':
	case 'categories':
		// we're going to ignore the "view name" param here because, well, how else will we get here?
		$title[] = JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_' . strtoupper($view));
		shRemoveFromGETVarsList('view');
		break;
	case 'location':
		// we're going to ignore the "view name" param here because, well, how else will we get here?
		$title[] = JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_' . strtoupper($view));
		switch ($layout) {
			case 'edit':
				$title[] = JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_' . (empty($id) ? 'ADD' : 'EDIT') . '_LOCATION');
				shRemoveFromGETVarsList('view');
				shRemoveFromGETVarsList('layout');
				break;
			default:
				if (is_null($id)) {
					break;
				}
				if (false !== strpos(':', $id)) {
					list($id, $tmp) = explode(':', $id, 2);
				}
				else if (false !== strpos('-', $id)) {
					list($id, $tmp) = explode('-', $id, 2);
				}
				$id = (int) $id;
				// fetch the proper alias and the canonical category id from the database
				$db->setQuery($db->getQuery(true)
					// the event itself
					->select('Location.alias, Location.title')
					->from('#__jcalpro_locations AS Location')
					->where('Location.id='.$id)
				);
				// NOTE assignment here, NOT checking for equals!!!
				if ($data = $db->loadObject()) {
					// add the title/id
					$title[] = "$id-" . (!empty($data->alias) ? $data->alias : JApplication::stringURLSafe($data->title));
					shRemoveFromGETVarsList('view');
					shRemoveFromGETVarsList('id');
				}
				break;
		}
		break;
	case 'category':
		if (empty($id)) {
			break;
		}
		// add the view name if configured to
		if ($pViewName) {
			$title[] = JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_CATEGORY');
			$title[] = '/';
		}
		jimport('joomla.application.categories');
		if (false !== strpos(':', $id)) {
			list($id, $tmp) = explode(':', $id, 2);
		}
		else if (false !== strpos('-', $id)) {
			list($id, $tmp) = explode('-', $id, 2);
		}
		$id = (int) $id;
		$category = JCategories::getInstance('JCalPro')->get($id);
		if (is_object($category) && method_exists($category, 'getPath')) {
			$path = $category->getPath();
			if (!empty($path)) {
				$parts = array();
				// alter the path data as needed by the params
				// we ALWAYS want the last category in this instance, so pop the last one off
				$parts[] = array_pop($path);
				if (!empty($path)) {
					// if we're not adding categories, do nothing else
					if (1 == $pCategories) {
						// do nothing
					}
					// if we still have data left, add what's necessary
					else if ('' != $pCategories) {
						// go through the rest of the options
						if (2 == $pCategories) { // first only
							$parts[] = array_shift($path);
						}
						else if (3 == $pCategories) { // last
							$parts[] = array_pop($path);
						}
						else if (4 == $pCategories) { // first 2
							$p1 = array_shift($path);
							if (!empty($path)) {
								$parts[] = array_shift($path);
							}
							$parts[] = $p1;
						}
						else { // last 2
							$p1 = array_pop($path);
							if (!empty($path)) {
								$parts[] = array_pop($path);
							}
							$parts[] = $p1;
						}
					}
					else {
						$path[] = $parts[0];
						$parts = $path;
					}
				}
				array_reverse($parts);
				// loop the categories
				foreach ($parts as $p) {
					$part = '';
					list($cid, $calias) = explode(':', $p, 2);
					if ($pCategoryId) {
						$part .= "$cid-";
					}
					if ($pCategoryAlias) {
						$part .= $calias;
					}
					if (!empty($part)) {
						$title[] = $part;
						$title[] = '/';
					}
				}
			}
			shRemoveFromGETVarsList('view');
			shRemoveFromGETVarsList('id');
		}
		break;
	case 'events':
		switch ($layout) {
			case 'all':
				$layout_title = JText::_('JALL');
			case 'month':
			case 'flat':
			case 'week':
			case 'day':
			case 'admin':
				// add the view name if configured to
				if ($pViewName) {
					$title[] = JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_EVENTS');
					$title[] = '/';
				}
				$title[] = isset($layout_title) ? $layout_title : JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_' . strtoupper($layout));
				$title[] = '/';
				shRemoveFromGETVarsList('layout');
				break;
		}
		shRemoveFromGETVarsList('view');
		break;
	case 'event':
		// add the view name if configured to
		if ($pViewName) {
			$title[] = JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_EVENT');
			$title[] = '/';
		}
		switch ($layout) {
			case 'edit':
				$title[] = JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_' . (empty($id) ? 'ADD' : 'EDIT') . '_EVENT');
				shRemoveFromGETVarsList('view');
				shRemoveFromGETVarsList('layout');
				break;
			default:
				if (is_null($id)) {
					break;
				}
				jimport('joomla.application.categories');
				if (false !== strpos(':', $id)) {
					list($id, $tmp) = explode(':', $id, 2);
				}
				else if (false !== strpos('-', $id)) {
					list($id, $tmp) = explode('-', $id, 2);
				}
				$id = (int) $id;
				// fetch the proper alias and the canonical category id from the database
				$db->setQuery($db->getQuery(true)
					// the event itself
					->select('Event.alias, Event.start_date')
					->from('#__jcalpro_events AS Event')
					->where('Event.id='.$id)
					->group('Event.id')
					// join the xref table so we can get the category
					->leftJoin('#__jcalpro_event_categories AS Xref ON Xref.event_id = Event.id AND Xref.canonical = 1')
					->select('Xref.category_id')
				);
				// NOTE assignment here, NOT checking for equals!!!
				if ($data = $db->loadObject()) {
					$category = JCategories::getInstance('JCalPro')->get($data->category_id);
					if (is_object($category) && method_exists($category, 'getPath')) {
						$path = $category->getPath();
						if (!empty($path)) {
							$parts = array();
							// alter the path data as needed by the params
							if (!empty($path)) {
								// if we're not adding categories, do nothing else
								if (1 == $pCategories) {
									// do nothing
								}
								// if we still have data left, add what's necessary
								else if ('' != $pCategories) {
									// go through the rest of the options
									if (2 == $pCategories) { // first only
										$parts[] = array_shift($path);
									}
									else if (3 == $pCategories) { // last
										$parts[] = array_pop($path);
									}
									else if (4 == $pCategories) { // first 2
										$p1 = array_shift($path);
										if (!empty($path)) {
											$parts[] = array_shift($path);
										}
										$parts[] = $p1;
									}
									else { // last 2
										$p1 = array_pop($path);
										if (!empty($path)) {
											$parts[] = array_pop($path);
										}
										$parts[] = $p1;
									}
								}
								else {
									$parts = $path;
								}
							}
							array_reverse($parts);
							foreach ($parts as $p) {
								$part = '';
								list($cid, $calias) = explode(':', $p, 2);
								if ($pCategoryId) {
									$part .= "$cid-";
								}
								if ($pCategoryAlias) {
									$part .= $calias;
								}
								if (!empty($part)) {
									$title[] = $part;
									$title[] = '/';
								}
							}
						}
					}
					// add the date
					if ($pDate) {
						jimport('jcaldate.date');
						$date = JCalDate::createFromMySQLFormat($data->start_date);
						$y = $date->year(false);
						$m = $date->month(false);
						$d = $date->day(false);
						switch ($pDateSep) {
							case 1:
								$title[] = $y . '-' . $m . '-' . $d;
								$title[] = '/';
								break;
							case 0:
							default:
								$title[] = $y;
								$title[] = '/';
								$title[] = $m;
								$title[] = '/';
								$title[] = $d;
								$title[] = '/';
								break;
						}
					}
					// add the title/id
					$etitle = '';
					if ($pEventId) {
						$etitle .= "$id-";
					}
					if ($pEventAlias) {
						$etitle .= $data->alias;
					}
					if (!empty($etitle)) {
						$title[] = $etitle;
					}
					shRemoveFromGETVarsList('view');
					shRemoveFromGETVarsList('id');
				}
				break;
		}
		break;
}}

// ------------------  standard plugin finalize function - don't change ---------------------------  
if ($dosef) {
   $string = shFinalizePlugin($string, $title, $shAppendString, $shItemidString, 
      (isset($limit) ? $limit : null), (isset($limitstart) ? $limitstart : null), 
      (isset($shLangName) ? $shLangName : null));
}
// ------------------  standard plugin finalize function - don't change ---------------------------
