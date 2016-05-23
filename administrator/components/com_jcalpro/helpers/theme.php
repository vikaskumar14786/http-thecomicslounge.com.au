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

// register the path helper
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
// register the other helpers
JCalPro::registerHelper('filter');
JCalPro::registerHelper('path');
JCalPro::registerHelper('url');

abstract class JCalProHelperTheme
{
	/**
	 * static method to get the current theme
	 * 
	 * @return string
	 */
	static public function current() {
		static $theme;
		if (!isset($theme)) {
			jimport('joomla.filesystem.folder');
			$app = JFactory::getApplication();
			$theme  = basename($app->getUserStateFromRequest(JCalPro::COM . '.theme', 'theme', JCalPro::config('default_theme', ''), 'string'));
			// check if the theme is actually available
			if (!JFolder::exists(JCalProHelperPath::theme() . '/' . $theme)) {
				$theme = '';
				$app->setUserState(JCalPro::COM . '.theme', '');
			}
		}
		return $theme;
	}
	
	/**
	 * static method to add a stylesheet to the document
	 * 
	 * @param string $file
	 * @param string $base
	 * @param string $template
	 */
	static public function addStyleSheet($file, $base = 'css', $template = '') {
		// get our document
		$doc = JFactory::getDocument();
		// if the document cannot have styles added, bail
		if (!method_exists($doc, 'addStyleSheet')) return;
		// sanitise the filename a little
		$css = self::getFilePath("$file.css", $base, $template);
		if ($css) {
			$doc->addStyleSheet($css);
		}
	}
	
	static public function addIEStyleSheet($file, $version = 0, $diff = '') {
		$document = JFactory::getDocument();
		if (!method_exists($document, 'addCustomTag')) return;
		$version = (int) $version;
		if (!in_array($diff, array('gt', 'gte', 'lt', 'lte'))) $diff = '';
		$tag   = array('');
		$tag[] = '<!--[if ' . (empty($diff) ? '' : "$diff ") . 'IE' . ($version ? " $version" : '') . ']>';
		$tag[] = '<link href="' . JCalProHelperFilter::escape($file) . '" rel="stylesheet" type="text/css" />';
		$tag[] = '<![endif]-->';
		$tag[] = '';
		$document->addCustomTag(implode("\n", $tag));
	}
	
	/**
	 * removes a stylesheet from the doc, if possible
	 * 
	 * @param unknown_type $file
	 */
	static public function removeStyleSheet($file) {
		return self::_removeAsset($file, 'stylesheet');
	}
	
	static public function removeScript($file) {
		return self::_removeAsset($file, 'script');
	}
	
	static private function _removeAsset($file, $type) {
		switch (strtolower($type)) {
			case 'script':
				$prop = '_scripts';
				break;
			case 'stylesheet':
				$prop = '_styleSheets';
				break;
			default: return false;
		}
		$document = JFactory::getDocument();
		if (property_exists($document, $prop) && is_array($document->$prop) && array_key_exists($file, $document->$prop)) {
			unset($document->$prop[$file]);
			JCalPro::debugger('Remove Asset ' . $file, $document->$prop);
		}
		return true;
	}
	
	/**
	 * static method to get the url of a file
	 * 
	 * @param string $file
	 * @param string $base
	 * @param string $template
	 */
	static public function getFilePath($file, $base, $template = '') {
		jimport('joomla.filesystem.file');
		// sanitise the filename a little
		$file = basename($file);
		$base = trim($base, '/');
		// now load the css file for the theme, if available
		$theme = basename(empty($template) ? self::current() : $template);
		if (!empty($theme) && JFile::exists(JCalProHelperPath::media() . "/themes/$theme/$base/$file")) {
			return JCalProHelperUrl::media() . "/themes/$theme/$base/$file";
		}
		// no theme? load the default css, if it exists
		else if (JFile::exists(JCalProHelperPath::media() . "/$base/$file")) {
			return JCalProHelperUrl::media() . "/$base/$file";
		}
		return false;
	}
	
	/**
	 * static method to get a list of available themes
	 * 
	 * @return array
	 */
	static public function getList() {
		jimport('joomla.filesystem.folder');
		// JCalPro 3 uses the "file" type extension for its themes
		// convention will follow that we'll load our data from the #__extensions table
		// basing our search on enabled rows with names like FILES_JCALTHEME_%
		// then cross-reference the "element" column with folders in media/jcalpro/themes
		$db = JFactory::getDbo();
		// go ahead and build the query to load the themes
		$query = $db->getQuery(true)
			->select('element, name')
			->from('#__extensions')
			->where('LOWER(' . $db->quoteName('name') . ') LIKE "files_jcaltheme_%"')
			->where('enabled = 1')
			->order($db->quoteName('name'))
		;
		// load the enabled themes
		$db->setQuery((string) $query);
		$dbthemes = $db->loadObjectList();
		// start building our select options
		$list = array();
		// get our xref array from the folders in media/jcalpro/themes
		$fsthemes = JFolder::folders(JCalProHelperPath::theme());
		// go ahead and add the default theme - this is the standard images used (and is not installed like the others)
		$list[] = JHtml::_('select.option', '', JText::_('COM_JCALPRO_THEMES_DEFAULT'), '_id', '_name');
		// loop our enabled themes to ensure they're in both the database AND filesystem
		foreach ($dbthemes as $theme) {
			// we have to remove the "jcaltheme_" prefix before searching
			// BUGFIX as of Joomla 3.4 this is different
			$themename = preg_replace('/^(files_)?jcaltheme_/i', '', strtolower($theme->element));
			// don't bother if it's not in the filesystem
			if (!in_array($themename, $fsthemes)) continue;
			// load up the language file
			JCalPro::language(strtolower($theme->name . '.sys'), JPATH_ROOT);
			// add to the list
			$list[] = JHtml::_('select.option', $themename, JText::_($theme->name . '_NAME'), '_id', '_name');
		}
		return $list;
	}
	
	static public function getConfig($key = null) {
		static $config;
		static $defaults;
		$current = 'jcaltheme_' . JCalProHelperTheme::current();
		if (!is_array($defaults)) {
			$defaults = array(
				'load_common' => 1
			);
		}
		if (!is_array($config)) {
			$db = JFactory::getDbo();
			$db->setQuery($db->getQuery(true)
				->select($db->quoteName('extension_id'))
				->select($db->quoteName('element'))
				->select($db->quoteName('params'))
				->from('#__extensions')
				->where($db->quoteName('element') . ' LIKE "jcaltheme_%"')
			);
			try {
				$config = $db->loadObjectList('element');
				if (!empty($config)) {
					foreach ($config as $theme => &$data) {
						$data->params = json_decode($data->params);
						/*
						foreach ($defaults as $defkey => $defvalue) {
							if (!is_object($data->params)) {
								$data->params = new stdClass;
							}
							if (!property_exists($data->params, $defkey)) {
								$data->params->{$defkey} = $defvalue;
							}
						}
						*/
					}
				}
				// force this to be an array
				else {
					$config = array();
				}
			}
			catch (Exception $e) {
				JCalProHelperLog::error($e->getMessage());
				$config = array();
			}
			
			// if we have no config from database, see if the current theme has defaults
			// this should help get around a known installer bug (that we haven't fixed yet and probably won't)
			if (empty($config)) {
				// get the current theme's manifest, if any
				jimport('joomla.filesystem.file');
				$xmlFile = JPATH_ADMINISTRATOR . '/manifests/files/' . $current . '.xml';
				if (JFile::exists($xmlFile)) {
					// piggyback code from core
					jimport('joomla.installer.installer') or jimport('cms.installer.installer');
					$installer = new JInstaller;
					// get our manifest as an internal joomla object
					$manifest = $installer->isManifest($xmlFile);
					if (!is_null($manifest)) {
						$installer->manifest = $manifest;
						$installer->setPath('manifest', $xmlFile);
						$config = $installer->getParams();
					}
				}
			}
			
			JCalPro::debugger('Theme Configuration', $config);
		}
		if (is_array($config) && array_key_exists($current, $config) && is_object($config[$current]) && property_exists($config[$current], 'params') && is_object($config[$current]->params) && property_exists($config[$current]->params, $key)) {
			return $config[$current]->params->{$key};
		}
		if (array_key_exists($key, $defaults)) {
			return $defaults[$key];
		}
		return null;
	}
	
	static public function isTooWhite($color) {
		$color = (string) $color;
		// force to lower case to process
		$color = JString::strtolower($color);
		// check for color codes like "white" or "blue"
		// TODO
		switch ($color) {
			case 'yellow':
			case 'white':
				return true;
			case 'black':
			case 'blue':
			case 'green':
			case 'red':
				return false;
			default:
				$rgb = JCalProHelperTheme::hexToRGB($color);
		}
		if (!is_array($rgb)) {
			return false;
		}
		return 130 < (($rgb['r'] * .299) + ($rgb['g'] * .587) + ($rgb['b'] * .114));
	}
	
	/**
	 * Converts a 3 or 6 character hex color to an rgb array
	 * 
	 * @param string $color
	 * 
	 * @return array parsed rgb array
	 * @return bool  false if color could not be parsed
	 */
	static public function hexToRGB($color) {
		$color = (string) $color;
		// remove css hash from color, if applicable
		if (false !== strpos($color, '#')) {
			$color = trim($color, '#');
		}
		// force to lower case to process
		$color = JString::strtolower($color);
		if (preg_match('/[^0-9a-f]/', $color)) {
			return false;
		}
		// check for hex codes - either in RRGGBB or RGB
		if (6 === JString::strlen($color)) {
			$r = hexdec(JString::substr($color, 0, 2));
			$g = hexdec(JString::substr($color, 2, 2));
			$b = hexdec(JString::substr($color, 4, 2));
		}
		else if (3 === JString::strlen($color)) {
			$r = hexdec(str_repeat(JString::substr($color, 0, 1), 2));
			$g = hexdec(str_repeat(JString::substr($color, 1, 1), 2));
			$b = hexdec(str_repeat(JString::substr($color, 2, 1), 2));
		}
		// not a hex code based on length - no clue
		else {
			return false;
		}
		return array('r' => $r, 'g' => $g, 'b' => $b);
	}
}
