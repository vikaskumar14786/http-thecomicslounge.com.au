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

global $shMosConfig_locale, $sh_LANG;

// parameters
$input      = JFactory::getApplication()->input;
$view       = $input->get('view', '', 'word');
$layout     = $input->get('layout', '', 'word');
$tmpl       = $input->get('tmpl', '', 'word');
$id         = $input->get('id', 0, 'uint');
$searchword = $input->get('searchword', '', 'string');
$Itemid     = $input->get('Itemid', 0, 'uint');
$lang       = $input->get('lang', '', 'string');

if (class_exists('Sh404sefFactory') && method_exists('Sh404sefFactory', 'getPageInfo'))
{
	$shPageInfo = &Sh404sefFactory::getPageInfo(); // get page details gathered by system plugin
}
else
{
	$shPageInfo = new stdClass;
	$shPageInfo->currentLanguageTag = $shMosConfig_locale;
}

// Build the root of the url
$shName = shGetComponentPrefix($option);
$shName = empty($shName) ? getMenuTitle($option, (isset($view) ? @$view : null), $Itemid) : $shName;

// load language strings
$shLangName = empty($lang) ? $shMosConfig_locale : (function_exists('shGetNameFromIsoCode') ? shGetNameFromIsoCode($lang) : Sh404sefHelperLanguage::getLangTagFromUrlCode($lang));
$shLangIso = isset($lang) ? $lang : (function_exists('shGetIsoCodeFromName') ? shGetIsoCodeFromName($shPageInfo->currentLanguageTag) : Sh404sefHelperLanguage::getUrlCodeFromTag($shPageInfo->currentLanguageTag));
//$shLangIso = shLoadPluginLanguage('com_jcalpro_lang', $shLangIso, '_JCL_SH404SEF_CALENDAR', realpath(dirname(__FILE__) . '../sef_ext'));

//-------------------------------------------------------------

global 	$shCustomTitleTag, $shCustomDescriptionTag, $shCustomKeywordsTag, $shCustomLangTag, $shCustomRobotsTag;

$shCustomLangTag = $shLangIso;
$shCustomRobotsTag = ($tmpl = 'component' ? 'noindex, nofollow' : 'index, follow');
$title = array();

// TODO: check for category in session
// build page title
$title[] = $shName;

// main display mode
// please note that we're not adding the date like we did in v2
// this is because this won't work properly with the new ajax mode
// (and let's not kid ourselves, it didn't really work in ajax mode w/ v2 either)
switch ($view) {
	case 'events':
		switch ($layout) {
			case 'month':
			case 'flat':
			case 'week':
			case 'day':
			case 'admin':
				$title[] = JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_' . strtoupper($layout));
				break;
		}
		break;
	case 'categories':
		$title[] = JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_CATEGORIES');
		break;
	case 'search' :
		$searchTitle = JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_SEARCH');
		if (!empty($searchword)) $searchTitle .= ' : ' . $searchword;
		$title[] = $searchTitle;
		break;
	case 'category':
		if (!$id) break;
		// find the current category & get the path
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
			// get the path
			$path = $category->getPath();
			if (!empty($path)) foreach ($path as $p) {
				// get the numeric id for the parent category in the path
				if (false !== strpos(':', $p)) {
					list($p, $tmp) = explode(':', $p, 2);
				}
				$title[] = JCategories::getInstance('JCalPro')->get($p)->title;
			}
		}
		break;
	case 'event':
		switch ($layout) {
			case 'edit':
				$title[] = JText::_('PLG_SH404SEFEXTPLUGINS_SH404SEFEXTPLUGINCOM_JCALPRO_SEF_' . (empty($id) ? 'ADD' : 'EDIT') . '_EVENT');
				break;
			default:
				if (!$id) break;
				jimport('joomla.application.categories');
				if (false !== strpos(':', $id)) {
					list($id, $tmp) = explode(':', $id, 2);
				}
				else if (false !== strpos('-', $id)) {
					list($id, $tmp) = explode('-', $id, 2);
				}
				$id = (int) $id;
				// fetch the title and the canonical category id from the database
				$db = JFactory::getDbo();
				$db->setQuery($db->getQuery(true)
					// the event itself
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
					$category = JCategories::getInstance('JCalPro')->get($data->category_id);
					if (is_object($category) && method_exists($category, 'getPath')) {
						$path = $category->getPath();
						if (!empty($path)) foreach ($path as $p) {
							// get the numeric id for the parent category in the path
							if (false !== strpos(':', $p)) {
								list($p, $tmp) = explode(':', $p, 2);
							}
							$title[] = JCategories::getInstance('JCalPro')->get($p)->title;
						}
					}
					$title[] = $data->title;
				}
				break;
		}
		break;
}

// finalize title
$title = array_reverse( $title);
$shCustomTitleTag = JString::ltrim(implode( ' | ', $title), '/ | ');
