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

jimport('joomla.filesystem.folder');
JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR . '/components/com_jcalpro/libraries/models/basemodel.php');
JLoader::register('JCalProHelperPath', JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/path.php');
JLoader::register('JCalPro', JCalProHelperPath::helper().'/jcalpro.php');
JLoader::register('MediaHelper', JPATH_ADMINISTRATOR . '/components/com_media/helpers/media.php');

JCalPro::language('com_media', JPATH_ADMINISTRATOR);

/**
 * custom media model for JCal Pro private user folders
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelMedia extends JCalProBaseModel
{
	public function getState($property = null, $default = null) {
		static $set;
		
		if (!$set) {
			$input  = JFactory::getApplication()->input;
			$folder = $input->get('folder', '', 'path');
			$this->setState('folder', $folder);

			$fieldid = $input->get('fieldid', '', 'cmd');
			$this->setState('field.id', $fieldid);
			
			$parent = str_replace("\\", "/", dirname($folder));
			$parent = ($parent == '.') ? null : $parent;
			$this->setState('parent', $parent);
			$set = true;
		}
		
		return parent::getState($property, $default);
	}
	
	public function getFolderList() {
		// Get some paths from the request
		if (empty($base)) {
			$base = JCalProHelperPath::uploads();
		}
		//corrections for windows paths
		$base = str_replace(DIRECTORY_SEPARATOR, '/', $base);
		$com_media_base_uni = str_replace(DIRECTORY_SEPARATOR, '/', JCalProHelperPath::uploads());

		// Get the list of folders
		jimport('joomla.filesystem.folder');
		$folders = JFolder::folders($base, '.', true, true);

		$document = JFactory::getDocument();
		$document->setTitle(JText::_('COM_MEDIA_INSERT_IMAGE'));

		// Build the array of select options for the folder list
		$options[] = JHtml::_('select.option', "", "/");

		foreach ($folders as $folder) {
			$folder    = str_replace($com_media_base_uni, "", str_replace(DIRECTORY_SEPARATOR, '/', $folder));
			$value     = substr($folder, 1);
			$text      = str_replace(DIRECTORY_SEPARATOR, "/", $folder);
			$options[] = JHtml::_('select.option', $value, $text);
		}

		// Sort the folder list array
		if (is_array($options)) {
			sort($options);
		}

		// Get asset and author id (use integer filter)
		$input = JFactory::getApplication()->input;
		$asset = $input->get('asset', 0, 'integer');
		$author = $input->get('author', 0, 'integer');

		// Create the drop-down folder select list
		$list = JHtml::_('select.genericlist',  $options, 'folderlist', 'class="inputbox" size="1" onchange="ImageManager.setFolder(this.options[this.selectedIndex].value,\''.$asset.'\',\''.$author.'\')" ', 'value', 'text', $base);

		return $list;
	}
	
	public function getImages() {
		return $this->_getFileList('images');
	}
	
	public function getFolders() {
		return $this->_getFileList('folders');
	}
	
	public function getDocuments() {
		return $this->_getFileList('docs');
	}
	
	public function getFiles() {
		return $this->_getFileList('files');
	}
	
	private function _getFileList($what) {
		switch ($what) {
			case 'images':
			case 'folders':
			case 'docs':
			case 'files':
				$list = $this->getList();
				return $list[$what];
			default:
				return false;
		}
	}
	
	function getList() {
		static $list;
	
		// Only process the list once per request
		if (is_array($list)) {
			return $list;
		}
	
		// Get current path from request
		$current = $this->getState('folder');
	
		// If undefined, set to empty
		if ($current == 'undefined') {
			$current = '';
		}
		
		$base = JCalProHelperPath::uploads();
	
		// Initialise variables.
		if (strlen($current) > 0) {
			$basePath = $base . '/' . $current;
		}
		else {
			$basePath = $base;
		}
	
		$mediaBase = str_replace(DIRECTORY_SEPARATOR, '/', $base . '/');
	
		$images  = array ();
		$folders = array ();
		$docs    = array ();
		$files   = array();
	
		$fileList = false;
		$folderList = false;
		if (file_exists($basePath)) {
			// Get the list of files and folders from the given folder
			$fileList	= JFolder::files($basePath);
			$folderList = JFolder::folders($basePath);
		}
	
		// Iterate over the files if they exist
		if ($fileList !== false) {
			foreach ($fileList as $file) {
				if (is_file($basePath.'/'.$file) && substr($file, 0, 1) != '.' && strtolower($file) !== 'index.html') {
					$tmp = new JObject();
					$tmp->name = $file;
					$tmp->title = $file;
					$tmp->path_media = $mediaBase;
					$tmp->path = str_replace(DIRECTORY_SEPARATOR, '/', JPath::clean($basePath . '/' . $file));
					$tmp->path_relative = str_replace($mediaBase, '', $tmp->path);
					$tmp->size = filesize($tmp->path);
	
					$ext = strtolower(JFile::getExt($file));
					switch ($ext) {
						// Image
						case 'jpg':
						case 'png':
						case 'gif':
						case 'xcf':
						case 'odg':
						case 'bmp':
						case 'jpeg':
						case 'ico':
							$info = @getimagesize($tmp->path);
							$tmp->width		= @$info[0];
							$tmp->height	= @$info[1];
							$tmp->type		= @$info[2];
							$tmp->mime		= @$info['mime'];
	
							if (($info[0] > 60) || ($info[1] > 60)) {
								$dimensions = MediaHelper::imageResize($info[0], $info[1], 60);
								$tmp->width_60 = $dimensions[0];
								$tmp->height_60 = $dimensions[1];
							}
							else {
								$tmp->width_60 = $tmp->width;
								$tmp->height_60 = $tmp->height;
							}
	
							if (($info[0] > 16) || ($info[1] > 16)) {
								$dimensions = MediaHelper::imageResize($info[0], $info[1], 16);
								$tmp->width_16 = $dimensions[0];
								$tmp->height_16 = $dimensions[1];
							}
							else {
								$tmp->width_16 = $tmp->width;
								$tmp->height_16 = $tmp->height;
							}
	
							$images[] = $tmp;
							$files[] = $tmp;
							break;
	
							// Non-image document
						default:
							$tmp->icon_32 = "media/mime-icon-32/".$ext.".png";
							$tmp->icon_16 = "media/mime-icon-16/".$ext.".png";
							$docs[] = $tmp;
							$files[] = $tmp;
							break;
					}
				}
			}
		}
	
		// Iterate over the folders if they exist
		if ($folderList !== false) {
			foreach ($folderList as $folder) {
				$tmp = new JObject();
				$tmp->name = basename($folder);
				$tmp->path = str_replace(DIRECTORY_SEPARATOR, '/', JPath::clean($basePath . '/' . $folder));
				$tmp->path_relative = str_replace($mediaBase, '', $tmp->path);
				$count = MediaHelper::countFiles($tmp->path);
				$tmp->files = $count[0];
				$tmp->folders = $count[1];
	
				$folders[] = $tmp;
			}
		}
	
		$list = array('folders' => $folders, 'docs' => $docs, 'images' => $images, 'files' => $files);
	
		return $list;
	}
	
	public function isImage($file) {
		$file = basename($file);
		$ext = strtolower(JFile::getExt($file));
		switch ($ext) {
			// Image
			case 'jpg':
			case 'png':
			case 'gif':
			case 'xcf':
			case 'odg':
			case 'bmp':
			case 'jpeg':
			case 'ico':
				return true;
			default:
				return false;
		}
	}
}
