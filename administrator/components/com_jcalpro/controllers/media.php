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

JLoader::register('JCalProBaseController', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/controllers/basecontroller.php');
JLoader::register('JCalProHelperPath', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/path.php');
JLoader::register('JCalPro', JCalProHelperPath::helper('jcalpro'));
JLoader::register('JCalProHelperUrl', JCalProHelperPath::helper('url'));
JLoader::register('MediaHelper', JPATH_ADMINISTRATOR . '/components/com_media/helpers/media.php');

JCalPro::language('com_media', JPATH_ADMINISTRATOR);

class JCalProControllerMedia extends JCalProBaseController
{
	public function upload() {
		// Check for request forgeries
		JSession::checkToken('request') or jexit(JText::_('JINVALID_TOKEN'));
		
		$input = JFactory::getApplication()->input;

		// Get the user
		$user		= JFactory::getUser();

		// Get some data from the request
		$file     = $input->files->get('Filedata');
		$folder   = $input->get('folder', '', 'path');
		$return   = $input->post->get('return-url', null, 'base64');


		// Set FTP credentials, if given
		JClientHelper::setCredentialsFromRequest('ftp');

		// Set the redirect
		if ($return) {
			$this->setRedirect(base64_decode($return).'&folder='.$folder);
		}

		// Make the filename safe
		$file['name']	= JFile::makeSafe($file['name']);

		if (isset($file['name']))
		{
			// The request is valid
			$err = null;
			if (!MediaHelper::canUpload($file, $err))
			{
				// The file can't be upload
				JError::raiseNotice(100, JText::_($err));
				return false;
			}

			$filepath = JPath::clean(JCalProHelperPath::uploads() . '/' . $folder . '/' . strtolower($file['name']));

			// Trigger the onContentBeforeSave event.
			JPluginHelper::importPlugin('content');
			$dispatcher	= JDispatcher::getInstance();
			$object_file = new JObject($file);
			$object_file->filepath = $filepath;
			$result = $dispatcher->trigger('onContentBeforeSave', array('com_media.file', &$object_file));
			if (in_array(false, $result, true)) {
				// There are some errors in the plugins
				JError::raiseWarning(100, JText::plural('COM_MEDIA_ERROR_BEFORE_SAVE', count($errors = $object_file->getErrors()), implode('<br />', $errors)));
				return false;
			}
			$file = (array) $object_file;

			if (JFile::exists($file['filepath']))
			{
				// File exists
				JError::raiseWarning(100, JText::_('COM_MEDIA_ERROR_FILE_EXISTS'));
				return false;
			}
			elseif (!$user->authorise('core.create', 'com_media'))
			{
				// File does not exist and user is not authorised to create
				JError::raiseWarning(403, JText::_('COM_MEDIA_ERROR_CREATE_NOT_PERMITTED'));
				return false;
			}

			if (!JFile::upload($file['tmp_name'], $file['filepath']))
			{
				// Error in upload
				JError::raiseWarning(100, JText::_('COM_MEDIA_ERROR_UNABLE_TO_UPLOAD_FILE'));
				return false;
			}
			else
			{
				// Trigger the onContentAfterSave event.
				$dispatcher->trigger('onContentAfterSave', array('com_media.file', &$object_file, true));
				$this->setMessage(JText::sprintf('COM_MEDIA_UPLOAD_COMPLETE', substr($file['filepath'], strlen(JCalProHelperPath::uploads()))));
				return true;
			}
		}
		else
		{
			$this->setRedirect('index.php', JText::_('COM_MEDIA_INVALID_REQUEST'), 'error');
			return false;
		}
	}
	
	public function folder() {
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$input = JFactory::getApplication()->input;
		
		$user = JFactory::getUser();
		
		$folder      = $input->get('foldername', '', 'cmd');
		$folderCheck = $input->get('foldername', null, null);
		$parent      = $input->get('folderbase', '', 'path');
		
		$returnVars  = array('folder' => $parent, 'tmpl' => $input->get('tmpl', 'index', 'cmd'));
		$fieldid     = $input->get('fieldid', '', 'cmd');
		if (!empty($fieldid)) $returnVars['fieldid'] = $fieldid;
		
		$redirect = JCalProHelperUrl::view('media', false, $returnVars);
		$this->setRedirect($redirect);
		
		if (strlen($folder)) {
			if (!$user->authorise('core.create', 'com_media')) {
				// User is not authorised to delete
				JError::raiseWarning(403, JText::_('JLIB_APPLICATION_ERROR_CREATE_NOT_PERMITTED'));
				return false;
			}
		
			// Set FTP credentials, if given
			JClientHelper::setCredentialsFromRequest('ftp');
		
			$input->set('folder', $parent);
		
			if (($folderCheck !== null) && ($folder !== $folderCheck)) {
				$this->setMessage(JText::_('COM_MEDIA_ERROR_UNABLE_TO_CREATE_FOLDER_WARNDIRNAME'));
				return false;
			}
			
			$base = JCalProHelperPath::uploads();
			$path = JPath::clean($base . '/' . $parent . '/' . $folder);
			if (!is_dir($path) && !is_file($path)) {
				// Trigger the onContentBeforeSave event.
				$object_file = new JObject(array('filepath' => $path));
				JPluginHelper::importPlugin('content');
				$dispatcher	= JDispatcher::getInstance();
				$result = $dispatcher->trigger('onContentBeforeSave', array('com_media.folder', &$object_file));
				if (in_array(false, $result, true)) {
					// There are some errors in the plugins
					JError::raiseWarning(100, JText::plural('COM_MEDIA_ERROR_BEFORE_SAVE', count($errors = $object_file->getErrors()), implode('<br />', $errors)));
					continue;
				}
		
				JFolder::create($path);
				$data = "<html>\n<body bgcolor=\"#FFFFFF\">\n</body>\n</html>";
				JFile::write($path . "/index.html", $data);
		
				// Trigger the onContentAfterSave event.
				$dispatcher->trigger('onContentAfterSave', array('com_media.folder', &$object_file, true));
				$this->setMessage(JText::sprintf('COM_MEDIA_CREATE_COMPLETE', substr($path, strlen($base))));
			}
			$input->set('folder', ($parent) ? $parent.'/'.$folder : $folder);
		}
	}
}
