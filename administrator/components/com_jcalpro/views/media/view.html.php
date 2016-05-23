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

jimport('joomla.application.component.view');
JLoader::register('JCalProBaseView', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/views/baseview.php');
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('filter');
JCalPro::registerHelper('path');
JCalPro::registerHelper('url');

class JCalProViewMedia extends JCalProBaseView
{
	public static $option = 'com_jcalpro';
	
	function display($tpl = null, $safeparams = false) {
		// add the com_media language file
		JCalPro::language('com_media', JPATH_ADMINISTRATOR);
		$rtl = JFactory::getLanguage()->isRTL();
		JCalPro::loadJsFramework();
		// switch based on our layout
		switch ($this->_layout) {
			case 'list':
				$this->folders = $this->get('Folders');
				//$this->images  = $this->get('Images');
				$this->images  = $this->get('Files');
				JFactory::getDocument()->addScriptDeclaration("var ImageManager = window.parent.ImageManager;");
				JHtml::_('stylesheet', 'media/popup-imagelist.css', array(), true);
				if ($rtl) JHtml::_('stylesheet', 'media/popup-imagelist_rtl.css', array(), true);
				break;
			default:
				// prepare the document
				JHtml::_('script', 'media/popup-imagemanager.js', true, true);
				JHtml::_('stylesheet', 'media/popup-imagemanager.css', array(), true);
				if ($rtl) JHtml::_('stylesheet', 'media/popup-imagemanager_rtl.css', array(), true);
				// add our "fix" for the image manager script
				JFactory::getDocument()->addScript(JCalProHelperUrl::media() . '/js/imagemanager.js');
				// add the folder select list
				$this->folderList = $this->get('FolderList');
		}
		// always add these
		$this->state  = $this->get('State');
		$this->config = JComponentHelper::getParams('com_media');
		// display
		parent::display($tpl, $safeparams);
	}
	
	public function setFolder($index = 0) {
		if (isset($this->folders[$index])) {
			$this->_tmp_folder = &$this->folders[$index];
		} else {
			$this->_tmp_folder = new JObject;
		}
	}
	
	public function setImage($index = 0) {
		if (isset($this->images[$index])) {
			$this->_tmp_img = &$this->images[$index];
		} else {
			$this->_tmp_img = new JObject;
		}
	}
}
