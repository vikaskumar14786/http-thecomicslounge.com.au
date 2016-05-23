<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
JLoader::register('JevJoomlaVersion',JPATH_ADMINISTRATOR."/components/com_jevents/libraries/version.php");

class plgJEventsjevfiles extends JPlugin
{

	var
			$_dbvalid = 0;
	private
			$anonupload = false;

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		JFactory::getLanguage()->load('plg_jevents_jevfiles', JPATH_ADMINISTRATOR);

		jimport('joomla.application.component.view');

		if (version_compare(JVERSION, "1.6.0", 'ge'))
		{
			$this->_basepath = JPATH_SITE . "/plugins/jevents/jevfiles/";
		}
		else
		{
			$this->_basepath = JPATH_SITE . "/plugins/jevents/";
		}
		//$this->view = new JView(array('base_path' => $this->_basepath, "template_path" => $this->_basepath . "tmpl", "name" => 'jevfiles'));
		//$this->view->addTemplatePath(JPATH_SITE . / . 'templates' . / . JFactory::getApplication ()->getTemplate() . / . 'html' . / . "plg_jevfiles" );

	}

	function onEditCustom(&$row, &$customfields)
	{
		if (!$this->authorised(false))
			return;

		$files = JFactory::getApplication()->input->files;
		$filecount = $files->count();
		
		// intercept this to save the file
		if ($filecount > 0)
		{
			$filedata = $files->getArray();
			$hasfile = false;
			$uploadfile = "";
			foreach ($filedata as $key => $val)
			{
				if ((strpos($key, "upload_image") === 0 || strpos($key, "upload_file") === 0) && $val["size"] > 0)
				{
					$hasfile = true;
					$uploadfile = $key;
					break;
				}
			}

			if ($hasfile)
				return $this->processUpload($uploadfile);
		}

		// Only setup when editing an event (not a repeat)
		if (JRequest::getString("jevtask", "") != "icalevent.edit" && JRequest::getString("jevtask", "") != "icalevent.editcopy")
			return;

		$jevuser = JEVHelper::getAuthorisedUser();

		// Get the media component configuration settings
		$params = JComponentHelper::getParams('com_media');
		// Set the path definitions
		if (!defined('JEVP_MEDIA_BASE'))
		{
			define('JEVP_MEDIA_BASE', JPATH_ROOT . '/' . $params->get('image_path', 'images/stories'));
			define('JEVP_MEDIA_BASEURL', JURI::root(true) . '/' . $params->get('image_path', 'images/stories'));
		}
		// folder relative to media folder
		$folder = $this->params->get("folder", "");
		if ($folder == "")
		{
			echo JText::_("JEV_SAVE_PLUGIN_PARAMETERS");
			return;
		}
		// ensure folder exists
		jimport('joomla.filesystem.folder');
		if (!JFolder::exists(JEVP_MEDIA_BASE . "/" . $folder))
		{
			JFolder::create(JEVP_MEDIA_BASE . "/" . $folder);
		}

		// get the data from database and attach to row
		$evid = intval($row->ev_id());
		$sql = "SELECT *, CONCAT(filetype,file_num) as filecode FROM #__jev_files WHERE ev_id=" . $evid . " ORDER BY filecode";
		$db = JFactory::getDBO();
		$db->setQuery($sql);
		$filemappings = $db->loadObjectList('filecode');

		// need session id to ensure login is maintained
		$session = JFactory::getSession();
		$mainframe = JFactory::getApplication();
		if (version_compare(JVERSION, "3.0.0", 'ge'))
		{
			if ($mainframe->isAdmin())
			{
				//$targetURL = JURI::root(true) . '/administrator/index.php?tmpl=component&folder=' . $folder . '&' . $session->getName() . '=' . $session->getId() . '&' . JSession::getFormToken() . '=1';
				$targetURL = JURI::root(true) . '/administrator/index.php?tmpl=component&folder=' . $folder ;
			}
			else
			{
				//$targetURL = JURI::root(true) . '/index.php?tmpl=component&folder=' . $folder . '&' . $session->getName() . '=' . $session->getId() . '&' . JSession::getFormToken() . '=1';
				$targetURL = JURI::root(true) . '/index.php?tmpl=component&folder=' . $folder;
			}
		}
		else
		{
			if ($mainframe->isAdmin())
			{
				//$targetURL = JURI::root(true) . '/administrator/index.php?tmpl=component&folder=' . $folder . '&' . $session->getName() . '=' . $session->getId() . '&' . JUtility::getToken() . '=1';
				$targetURL = JURI::root(true) . '/administrator/index.php?tmpl=component&folder=' . $folder;
			}
			else
			{
				//$targetURL = JURI::root(true) . '/index.php?tmpl=component&folder=' . $folder . '&' . $session->getName() . '=' . $session->getId() . '&' . JUtility::getToken() . '=1';
				$targetURL = JURI::root(true) . '/index.php?tmpl=component&folder=' . $folder;
			}
		}

		$uploaderInit = "
		var oldAction = '';
		var oldTarget = '';
		var oldTask = '';
		var oldOption = '';
		function uploadFileType(field){
			form = document.adminForm;
			oldAction = form.action;
			oldTarget = form.target;
			oldTask = form.task.value;
			oldOption = form.option.value;
			form.action = '" . $targetURL . "&field='+field;

			form.target = 'uploadtarget';";
		if ($mainframe->isAdmin())
		{
			$uploaderInit .= "form.task.value = 'icalevent.edit';";
		}
		else
		{
			$uploaderInit .= "form.task.value = 'day.listevents';";
		}
		$uploaderInit .= "form.option.value = 'com_jevents';
			form.submit();
			form.action = oldAction ;
			form.target = oldTarget ;
			form.task.value = oldTask ;
			form.option.value = oldOption;

			var loading = document.getElementById(field+'_loading');
			loading.style.display='block';
			var loaded = document.getElementById(field+'_loaded');
			loaded.style.display='none';
		}
		function setImageFileName(fname, filename,oname){
			if(!fname) return;

			var elemname =  fname.substr(0,fname.length-5);
			var titleelem = document.getElementById('custom_' + elemname + '_title');
			var settitle = false;
			if (titleelem && titleelem.value=='')  settitle = true;

			// msie fix - it doens't clear the upload fieldfile  after upload
			var elem = document.getElementById(fname);
			if (jQuery.browser.msie){
				elem.parentNode.innerHTML = elem.parentNode.innerHTML;
			}

			// contorted because of Msie fix!
			if (settitle) {
				titleelem = document.getElementById('custom_' + elemname + '_title');
				titleelem.value = oname;
				//titleelem.value = document.getElementById('title').value;
			}

			elem = document.getElementById('custom_' + elemname);
			if (elem) elem.value = filename;
			elem = document.getElementById(fname);
			if (elem) elem.value = '';
			img = document.getElementById(elemname+'_img');
			img.src = '" . JEVP_MEDIA_BASEURL . "/$folder/thumbnails/thumb_'+filename;
			img.style.display='block';
			img.style.marginRight='10px';

			var loading = document.getElementById(elemname+'_loading');
			loading.style.display='none';
			var loaded = document.getElementById(elemname+'_loaded');
			loaded.style.display='block';

		}
		function clearImageFile(elemname){

			img = document.getElementById(elemname+'_img');
			img.src = ''
			img.style.display='none';
			img.style.marginRight='0px';
			elem = document.getElementById('custom_' + elemname);
			if (elem) elem.value = '';
			elem = document.getElementById('custom_' + elemname+'_title');
			if (elem) elem.value = '';
		}

		function clearFile(elemname){
			img = document.getElementById(elemname+'_link');
			img.href = ''
			img.innerHTML='';
			img.style.display='none';
			elem = document.getElementById('custom_'+elemname);
			if (elem) elem.value = '';
		}

		function setLinkFileHref(fname, filename, oname){
			if(!fname) return;

			// msie fix - it doens't clear the upload fieldfile  after upload
			elem = document.getElementById(fname);
			if (jQuery.browser.msie){
				elem.parentNode.innerHTML = elem.parentNode.innerHTML;
			}

			elemname = fname.substr(0,fname.length-5);
			elem = document.getElementById('custom_' + elemname);
			if (elem) elem.value = filename;

			var titleelem = document.getElementById('custom_' + elemname + '_title');
			var settitle = false;
			if (titleelem && titleelem.value=='')  settitle = true;

			if (settitle) {
				titleelem.value = oname;
			}

			elem = document.getElementById(fname);
			if (elem) elem.value = '';
			mylink = document.getElementById(elemname+'_link');
			mylink.href = '" . JEVP_MEDIA_BASEURL . "/$folder/'+filename;
			mylink.innerHTML = oname;

			var loading = document.getElementById(elemname+'_loading');
			loading.style.display='none';
			var loaded = document.getElementById(elemname+'_loaded');
			loaded.style.display='block';
		}

		";
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($uploaderInit);

		$imagemaps = array();
		$filemaps = array();
		if ($filemappings)
		{
			foreach ($filemappings as $fm)
			{
				if ($fm->filetype == "image")
				{
					$imagemaps[$fm->file_num] = $fm;
				}
				else
				{
					$filemaps[$fm->file_num] = $fm;
				}
			}
		}

		$result = '<iframe src="about:blank" style="display:none" name="uploadtarget" id="uploadtarget"></iframe>';
		$result .= '<input type="hidden" name="' . $session->getName() . '" id="sessions" value="' . $session->getId() . '" />';
		$result .= '<input type="hidden" name="' .  JSession::getFormToken() . '" id="token" value="1" />';

		$imagenos = intval($this->params->get("imnum", 1));
		// index from 1 !!!
		for ($i = 1; $i <= $imagenos; $i++)
		{

			if (count($imagemaps) > 0 && array_key_exists($i, $imagemaps))
			{
				$filename = $imagemaps[$i]->filename;
				$filetitle = $imagemaps[$i]->filetitle;
			}
			else
			{
				$filename = "";
				$filetitle = "";
			}
			if ($filename)
			{
				$src = JEVP_MEDIA_BASEURL . "/$folder/thumbnails/thumb_$filename";
				$visibility = "visibility:visible;";
				$visibility = "margin-right:10px;";
			}
			else
			{
				$src = "about:blank";
				$visibility = "margin-right:0px;";
				$visibility = "display:none;";
			}

			$fieldname = "upload_image" . $i;
			$id_to_check = "custom_" . $fieldname;
			$result .= '<img id="' . $fieldname . '_img" src="' . $src . '" style="float:left;' . $visibility . 'max-width:inherit!important;"/>';
			$result .= JHTML::_('form.token');
			$result .= '<input type="hidden" name="custom_' . $fieldname . '" id="custom_' . $fieldname . '" value="' . $filename . '" size="50"/>';
			if ($this->params->get("imagetitle", 1))
			{
				$result .= '<label for="custom_' . $fieldname . '_file">' . JText::_("JEV_UPLOAD_IMAGE_TITLE") . '</label><br/>';
				$result .= '<input type="text" name="custom_' . $fieldname . '_title" id="custom_' . $fieldname . '_title" value="' . $filetitle . '" size="50"/><br/>';
			}
			else
			{
				$result .= '<input type="hidden" name="custom_' . $fieldname . '_title" id="custom_' . $fieldname . '_title" value="' . $filetitle . '" size="50"/>';
			}
			$result .= '<label for="' . $fieldname . '_file">' . JText::sprintf("JEV_UPLOAD_IMAGE", number_format($this->params->get("maxupload", 1000000) / 1000000, 2)) . '</label><br/>';
			$result .= '<span><input type="file" name="' . $fieldname . '_file" id="' . $fieldname . '_file" size="50"/ class="btn"></span>';
			$result .= ' <input type="button" onclick="uploadFileType(\'' . $fieldname . '\')" value="' . JText::_("jev_upload") . '"  class="btn"/> ';
			$result .= '<input type="button" onclick="clearImageFile(\'' . $fieldname . '\')" value="' . JText::_("jev_Delete") . '"  class="btn"/>';
			$result .= '<div id="' . $fieldname . '_loading" class="loading" style="display:none">' . JText::_("JEV_UPLOADING_WAIT") . '</div>';
			$result .= '<div id="' . $fieldname . '_loaded" class="loaded" style="display:none">' . JText::_("JEV_UPLOAD_COMPLETE") . '</div>';
			$result .= '<br style="clear:both"/>';

			$label = JText::_("JEV_STANDARD_IMAGE_" . $i);
			if ($label == "JEV_STANDARD_IMAGE_" . $i)
				$label = JText::_("JEV_STANDARD_IMAGE");

			$user = JFactory::getUser();
			$jevparams = JComponentHelper::getParams('com_jevents');
			if (($jevparams->get("authorisedonly", 0) && $jevuser && $jevuser->canuploadimages) || $this->anonupload)
			{
				$customfield = array("label" => $label, "input" => $result, "default_value" => "", "id_to_check" => $id_to_check);
				$customfields["image$i"] = $customfield;
			}
			else if (!$jevparams->get("authorisedonly", 0))
			{
				// restrict usage to certain user types
				if (version_compare(JVERSION, "1.6.0", 'ge'))
				{
					$userGroups = JFactory::getUser()->getAuthorisedGroups();
					if (!array_intersect($this->params->get('upimageslevel', array(8)), array_values($userGroups)))
					{
						return;
					}
				}
				else
				{
					$groupID = JEVHelper::getGid($user); // RSH Change to make J!1.6 Compatible - used to be: $user->get('gid');
					if ($groupID < $this->params->get('upimageslevel', 19))
					{
						return;
					}
				}
				$customfield = array("label" => $label, "input" => $result, "default_value" => "", "id_to_check" => $id_to_check);
				$customfields["image$i"] = $customfield;
			}

			$result = "";
		}

		$filenos = intval($this->params->get("filnum", 1));
		// index from 1 !!!
		for ($i = 1; $i <= $filenos; $i++)
		{

			if (count($filemaps) > 0 && array_key_exists($i, $filemaps))
			{
				$filename = $filemaps[$i]->filename;
				$filetitle = $filemaps[$i]->filetitle;
			}
			else
			{
				$filename = "";
				$filetitle = "";
			}
			if ($filename)
			{
				$href = JEVP_MEDIA_BASEURL . "/$folder/$filename";
			}
			else
			{
				$href = "about:blank";
			}
			$fieldname = "upload_file" . $i;
			$id_to_check = "custom_" . $fieldname;
			$result .= '<a id="' . $fieldname . '_link" href="' . $href . '" style="float:left;margin-right:10px;" target="_blank">' . ($filename ? $filetitle : "") . "</a>";
			$result .= JHTML::_('form.token');
			$result .= '<input type="hidden" name="' . $id_to_check . '" id="custom_' . $fieldname . '" value="' . $filename . '" size="50"/>';
			if ($this->params->get("filetitle", 1))
			{
				$result .= '<label for="custom_' . $fieldname . '_file">' . JText::_("JEV_UPLOAD_FILE_TITLE") . '</label><br/>';
				$result .= '<input type="text" name="custom_' . $fieldname . '_title" id="custom_' . $fieldname . '_title" value="' . $filetitle . '" size="50"/><br/>';
			}
			else
			{
				$result .= '<input type="hidden" name="custom_' . $fieldname . '_title" id="custom_' . $fieldname . '_title" value="' . $filetitle . '" size="50"/>';
			}

			$result .= '<label for="' . $fieldname . '_file">' . JText::sprintf("JEV_UPLOAD_FILE", number_format($this->params->get("maxupload", 1000000) / 1000000, 2)) . '</label><br/>';
			$result .= '<input type="file" name="' . $fieldname . '_file" id="' . $fieldname . '_file" size="50"/ class="btn">';
			$result .= ' <input type="button" onclick="uploadFileType(\'' . $fieldname . '\')" value="' . JText::_("jev_upload") . '" class="btn" /> ';
			$result .= '<input type="button" onclick="clearFile(\'' . $fieldname . '\')" value="' . JText::_("jev_Delete") . '" class="btn"/>';
			$result .= '<div id="' . $fieldname . '_loading" class="loading" style="display:none">' . JText::_("JEV_UPLOADING_WAIT") . '</div>';
			$result .= '<div id="' . $fieldname . '_loaded" class="loaded" style="display:none">' . JText::_("JEV_UPLOAD_COMPLETE") . '</div>';
			$result .= '<br style="clear:both"/>';

			$label = JText::_("JEV_STANDARD_FILE_" . $i);
			if ($label == "JEV_STANDARD_FILE_" . $i)
				$label = JText::_("JEV_STANDARD_FILE");

			$user = JFactory::getUser();
			$jevparams = JComponentHelper::getParams('com_jevents');
			if (($jevparams->get("authorisedonly", 0) && $jevuser && $jevuser->canuploadmovies) || $this->anonupload)
			{
				$customfield = array("label" => $label, "input" => $result, "default_value" => "", "id_to_check" => $id_to_check);
				$customfields["file$i"] = $customfield;
			}
			else if (!$jevparams->get("authorisedonly", 0))
			{
				if (!(JEVHelper::isAdminUser($user))){
					// restrict usage to certain user types
					if (version_compare(JVERSION, "1.6.0", 'ge'))
					{
						$userGroups = JFactory::getUser()->getAuthorisedGroups();
						if (!array_intersect($this->params->get('upfileslevel', array(8)), array_values($userGroups)))
						{
							return;
						}
					}
					else
					{
						$groupID = JEVHelper::getGid($user); // RSH Change to make J!1.6 Compatible - used to be: $user->get('gid');
						if ($groupID < $this->params->get('upfileslevel', 19))
						{
							return;
						}
					}
				}
				$customfield = array("label" => $label, "input" => $result, "default_value" => "", "id_to_check" => $id_to_check);
				$customfields["file$i"] = $customfield;
			}


			$result = "";
		}

		return true;

	}

	/**
	 * Clean out custom fields for event details not matching global event detail
	 *
	 * @param unknown_type $idlist
	 */
	function onDeleteEventDetails($idlist)
	{
		return true;

	}

	/**
	 * Store custom fields
	 *
	 * @param iCalEventDetail $evdetail
	 */
	function onStoreCustomDetails($evdetail)
	{
		// are we importing events with image details contained within them
		if (isset($evdetail->_customFields) && strpos(JRequest::getCmd("task"), "icals") !== false)
		{
			foreach ($evdetail->_customFields as $key => $val)
			{
				if (strpos($key, "IMPORT_IMAGE_") === 0)
				{
					$x = 1;
				}
			}
		}

	}

	/**
	 * Store custom fields
	 *
	 * @param iCalEventDetail $evdetail
	 */
	// TODO update reminder timestamps when event times have changed
	function onStoreCustomEvent($event)
	{
		if (!$this->authorised(false))
			return;

		$success = true;
		$evdetail = $event->_detail;

		$imagenos = intval($this->params->get("imnum", 1));
		// index from 1 !!!
		for ($i = 1; $i <= $imagenos; $i++)
		{
			$filename = "upload_image" . $i;

			if (!isset($evdetail->_customFields) || !array_key_exists($filename, $evdetail->_customFields))
				continue;

			$noHtmlFilter = JFilterInput::getInstance(/* $tags, $attr, $tag_method, $attr_method, $xss_auto */);
			$image = $noHtmlFilter->clean($evdetail->_customFields[$filename]);
			$imagetitle = $noHtmlFilter->clean($evdetail->_customFields[$filename . "_title"]);

			$db = JFactory::getDBO();

			$eventid = $event->ev_id;

			$sql = "SELECT * FROM #__jev_files WHERE ev_id=" . $eventid . " && filetype='image'  &&  file_num=" . $i;
			$db->setQuery($sql);
			$filedata = $db->loadObject();

			if ($filedata && $filedata->ev_id > 0)
			{

				$sql = "UPDATE #__jev_files "
						. " SET file_num=" . $i
						. ", filetype='image'"
						. ", filename=" . $db->Quote($image)
						. ", filetitle=" . $db->Quote($imagetitle)
						. " WHERE ev_id=" . intval($filedata->ev_id)
						. " AND file_num=" . $i
						. " AND filetype='image'";
			}
			else
			{
				$sql = "INSERT INTO #__jev_files "
						. " SET file_num=" . $i
						. ", filetype='image'"
						. ", filename=" . $db->Quote($image)
						. ", filetitle=" . $db->Quote($imagetitle)
						. ", ev_id=" . intval($eventid)
				;
			}
			$db->setQuery($sql);
			$success = $db->query();
		}

		$filenos = intval($this->params->get("filnum", 1));
		// index from 1 !!!
		for ($i = 1; $i <= $filenos; $i++)
		{
			$filename = "upload_file" . $i;

			if (!isset($evdetail->_customFields) || !array_key_exists($filename, $evdetail->_customFields))
				continue;

			$noHtmlFilter = JFilterInput::getInstance(/* $tags, $attr, $tag_method, $attr_method, $xss_auto */);
			$image = $noHtmlFilter->clean($evdetail->_customFields[$filename]);
			$imagetitle = $noHtmlFilter->clean($evdetail->_customFields[$filename . "_title"]);

			$db = JFactory::getDBO();

			$eventid = $event->ev_id;

			$sql = "SELECT * FROM #__jev_files WHERE ev_id=" . $eventid . " && filetype='file' && file_num=" . $i;
			$db->setQuery($sql);
			$filedata = $db->loadObject();

			if ($filedata && $filedata->ev_id > 0)
			{

				$sql = "UPDATE #__jev_files "
						. " SET file_num=" . $i
						. ", filetype='file'"
						. ", filename=" . $db->Quote($image)
						. ", filetitle=" . $db->Quote($imagetitle)
						. " WHERE ev_id=" . intval($filedata->ev_id)
						. " AND file_num=" . $i
						. " AND filetype='file'";
			}
			else
			{
				$sql = "INSERT INTO #__jev_files "
						. " SET file_num=" . $i
						. ", filetype='file'"
						. ", filename=" . $db->Quote($image)
						. ", filetitle=" . $db->Quote($imagetitle)
						. ", ev_id=" . intval($eventid)
				;
			}
			$db->setQuery($sql);
			$success = $db->query();
		}

		return $success;

	}

	/**
	 * Clean out custom details for deleted event details
	 *
	 * @param comma separated list of event detail ids $idlist
	 */
	function onDeleteCustomEvent($idlist)
	{

		$ids = explode(",", $idlist);
		JArrayHelper::toInteger($ids);
		$idlist = implode(",", $ids);

		// Find the referenced files
		$db = JFactory::getDBO();
		$sql = "SELECT filename FROM #__jev_files WHERE ev_id IN (" . $idlist . ")";
		$db->setQuery($sql);
		$eventfiles = $db->loadColumn();

		// Get the media component configuration settings
		$params = JComponentHelper::getParams('com_media');
		$path = JPATH_ROOT . "/" . $params->get('image_path', 'images/stories');
		$folder = $this->params->get("folder", "jevents");

		jimport('joomla.filesystem.file');
		foreach ($eventfiles as $file)
		{
			if (trim($file) == "")
				continue;

			// make sure not used in a copied event
			$sql = "SELECT count(ev_id) FROM #__jev_files WHERE filename = " . $db->quote($file);
			$db->setQuery($sql);
			$count = $db->loadResult();
			if ($count > 1)
				continue;
			
			$files = JFolder::files(JEVP_MEDIA_BASE . "/" . $folder, $imgfile, true,true);
			foreach ($files as $file) {
				JFile::delete($file);
			}
			if (JFile::exists($path . "/" . $folder . "/" . 'thumbnails' . "/" . 'thumb_' . $file))
			{
				JFile::delete($path . "/" . $folder . "/" . 'thumbnails' . "/" . 'thumb_' . $file);
			}
		}

		// delete the metatags too
		$sql = "DELETE FROM #__jev_files WHERE ev_id IN (" . $idlist . ")";
		$db->setQuery($sql);
		$db->query();

		return;
		/*
		 * This code may be unreliable on Windows servers - test throughly before using
		  // clean up unassociated images
		  // Get the media component configuration settings
		  $params = JComponentHelper::getParams('com_media');
		  $path = JPATH_ROOT."/".$params->get('image_path', 'images/stories');
		  $folder = $this->params->get("folder","jevents");

		  jimport('joomla.filesystem.folder');
		  $files = JFolder::files($path."/".$folder);

		  $sql = "SELECT DISTINCT filename FROM #__jev_files where filename <>''";
		  $db = JFactory::getDBO();
		  $db->setQuery($sql);
		  $usedfiles = $db->loadColumn();

		  // Must also add in images used in custom fields
		  if ($cfplugin = JPluginHelper::getPlugin("jevents","jevcustomfields")){
		  $this->params = new JRegistry($cfplugin->params);
		  $template = $this->params->get("template","");
		  if ($template!=""){
		  JLoader::register('JevCfForm', JPATH_SITE."/plugins/jevents/jevcustomfields/jevcfform.php");
		  $xmlfile = JPATH_SITE."/plugins/jevents/jevcustomfields/customfields/templates/".$template;

		  if (file_exists($xmlfile)){
		  include_once(JPATH_ADMINISTRATOR . "/components/com_jevents/jevents.defines.php");
		  $this->fieldparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
		  $this->fieldparams->setEvent(null);

		  $groups = $this->fieldparams->getFieldsets();
		  foreach ($groups as $group => $element)
		  {
		  $count = $this->fieldparams->getFieldCountByFieldSet($group);
		  $groupparams = $this->fieldparams->getFieldset($group);
		  foreach ($groupparams as $p => $node)
		  {
		  if ($node->attribute("type")=="jevcfimage" || $node->attribute("type")=="jevcffile")
		  {
		  $db = JFactory::getDBO();
		  $sql = "SELECT DISTINCT value FROM #__jev_customfields where value<>'' AND name=".$db->quote($node->fieldname);
		  $db->setQuery($sql);
		  $usedfiles2 = $db->loadColumn();
		  $usedfiles = array_merge($usedfiles2, $usedfiles);
		  }
		  }
		  }
		  }
		  }

		  jimport('joomla.filesystem.file');
		  if (JFile::exists(JPATH_SITE."/components/com_jevlocations/jevlocations.php") && $locparams = JComponentHelper::getParams("com_jevlocations")){
		  if ($locparams->get("template")!=""){
		  $template = $locparams->get("fieldtemplate");
		  $xmlfile = JPATH_SITE."/plugins/jevents/jevcustomfields/customfields/templates/".$template;

		  if (file_exists($xmlfile)){
		  include_once(JPATH_ADMINISTRATOR . "/components/com_jevents/jevents.defines.php");
		  $this->fieldparams = JevCfForm::getInstance("com_jeventlocations.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
		  $this->fieldparams->setEvent(null);

		  $groups = $this->fieldparams->getFieldsets();
		  foreach ($groups as $group => $element)
		  {
		  $count = $this->fieldparams->getFieldCountByFieldSet($group);
		  $groupparams = $this->fieldparams->getFieldset($group);
		  foreach ($groupparams as $p => $node)
		  {
		  if ($node->attribute("type")=="jevcfimage" || $node->attribute("type")=="jevcffile")
		  {
		  $db = JFactory::getDBO();
		  $sql = "SELECT DISTINCT value FROM #__jev_customfields3 where value<>'' AND name=".$db->quote($node->fieldname);
		  $db->setQuery($sql);
		  $usedfiles2 = $db->loadColumn();
		  $usedfiles = array_merge($usedfiles2, $usedfiles);
		  }
		  }
		  }
		  }

		  }
		  }

		  if (JFile::exists(JPATH_SITE."/components/com_jevpeople/jevpeople.php") && $peopparams = JComponentHelper::getParams("com_jevpeople")){
		  if ($peopparams->get("template")!=""){
		  $template = $peopparams->get("template");
		  $xmlfile = JPATH_SITE."/plugins/jevents/jevcustomfields/customfields/templates/".$template;

		  if (file_exists($xmlfile)){
		  include_once(JPATH_ADMINISTRATOR . "/components/com_jevents/jevents.defines.php");
		  $this->fieldparams = JevCfForm::getInstance("com_jeventpeople.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
		  $this->fieldparams->setEvent(null);

		  $groups = $this->fieldparams->getFieldsets();
		  foreach ($groups as $group => $element)
		  {
		  $count = $this->fieldparams->getFieldCountByFieldSet($group);
		  $groupparams = $this->fieldparams->getFieldset($group);
		  foreach ($groupparams as $p => $node)
		  {
		  if ($node->attribute("type")=="jevcfimage" || $node->attribute("type")=="jevcffile")
		  {
		  $db = JFactory::getDBO();
		  $sql = "SELECT DISTINCT value FROM #__jev_customfields2 where value<>'' AND name=".$db->quote($node->fieldname);
		  $db->setQuery($sql);
		  $usedfiles2 = $db->loadColumn();
		  $usedfiles = array_merge($usedfiles2, $usedfiles);
		  }
		  }
		  }
		  }

		  }
		  }
		  }

		  jimport('joomla.filesystem.file');
		  foreach ($files as $file) {
		  if (!in_array($file,$usedfiles)){
		  JFile::delete($path."/".$folder."/".$file);
		  if (JFile::exists($path."/".$folder."/".'thumbnails'."/".'thumb_'.$file)){
		  JFile::delete($path."/".$folder."/".'thumbnails'."/".'thumb_'.$file);
		  }
		  }
		  }

		  return true;
		 */

	}

	function onDisplayCustomFields(&$row)
	{

		$evid = intval($row->ev_id());
		$sql = "SELECT *, CONCAT(filetype,file_num) as filecode FROM #__jev_files WHERE ev_id=" . $evid . " ORDER BY filecode";
		$db = JFactory::getDBO();
		$db->setQuery($sql);
		$row->filedata = $db->loadObjectList('filecode');

		$row->_image = "";
		if (is_null($row->filedata) || count($row->filedata) == 0)
			return;

		// Get the media component configuration settings
		$params = JComponentHelper::getParams('com_media');
		// Set the path definitions
		if (!defined('JEVP_MEDIA_BASE'))
		{
			define('JEVP_MEDIA_BASE', JPATH_ROOT . "/" . $params->get('image_path', 'images/stories'));
			define('JEVP_MEDIA_BASEURL', JURI::root(true) . '/' . $params->get('image_path', 'images/stories'));
		}

		$result = "";
		// folder relative to media folder
		$folder = $this->params->get("folder", "jevents");
		if (count($row->filedata) > 0)
		{

			$imagenos = intval($this->params->get("imnum", 1));
			// index from 1 !!!
			for ($i = 1; $i <= $imagenos; $i++)
			{

				if (!array_key_exists("image" . $i, $row->filedata))
				{
					$name = "_image$i";
					$row->$name = "";
					$name = "_thumbimage$i";
					$row->$name = "";
					continue;
				}
				$filename = $row->filedata["image" . $i]->filename;
				$filetitle = $row->filedata["image" . $i]->filetitle;

				if ($filename == "")
				{
					$name = "_image$i";
					$row->$name = "";
					$name = "_thumbimage$i";
					$row->$name = "";
					continue;
				}

				$thumbsrc = JEVP_MEDIA_BASEURL . "/$folder/thumbnails/thumb_$filename";
				$src = JEVP_MEDIA_BASEURL . "/$folder/$filename";
				$name = "_thumbimage$i";
				$row->$name = '<img  src="' . $thumbsrc . '" alt="' . htmlentities($filetitle) . '" class="jev_thumb jev_thumb1"/>';
				$name = "_image$i";
				$row->$name = '<img  src="' . $src . '" alt="' . htmlentities($filetitle) . '"  class="jev_image jev_image1"/>';

				$name = "_thumburl$i";
				$row->$name = $thumbsrc;
				$name = "_imageurl$i";
				$row->$name = $src;

				$name = "_imagetitle$i";
				$row->$name = $filetitle;

				$result .= ($result != "") ? "<br/>" : "";
				$name = "_image$i";
				$result .= $row->$name;
			}

			$filnos = intval($this->params->get("filnum", 1));
			// index from 1 !!!
			for ($i = 1; $i <= $filnos; $i++)
			{

				if (!array_key_exists("file" . $i, $row->filedata))
				{
					$name = "_file$i";
					$row->$name = "";
					$name = "_filetitle$i";
					$row->$name = "";
					continue;
				}
				$filename = $row->filedata["file" . $i]->filename;
				$filetitle = $row->filedata["file" . $i]->filetitle;

				if ($filename == "")
				{
					$name = "_file$i";
					$row->$name = "";
					$name = "_filetitle$i";
					$row->$name = "";
					continue;
				}

				$href = JEVP_MEDIA_BASEURL . "/$folder/$filename";
				$name = "_filetitle$i";
				$row->$name = $filetitle;
				$name = "_file$i";
				$row->$name = $filename;

				if (strpos($filename, '.pdf'))
				{
					$ispdf = "target='_blank'";
				}
				else
				{
					$ispdf = "";
				}

				$link = "<a href='$href' title='" . htmlentities($filetitle) . "' " . $ispdf . " class='jev_file jev_file1'>" . $filetitle . "</a>";
				$name = "_filelink$i";
				$row->$name = $link;
				$name = "_filehref$i";
				$row->$name = $href;

				$result .= ($result != "") ? "<br/>" : "";
				$result .= $link;
			}
		}

		return $result;

	}

	function onListIcalEvents(& $extrafields, & $extratables, & $extrawhere, & $extrajoin, & $needsgroupdby = false)
	{
		$files = JFactory::getApplication()->input->files;
		$filecount = $files->count();

		// intercept this to save the file
		if ($filecount > 0)
		{
			$filedata = $files->getArray();
			$hasfile = false;
			$uploadfile = "";
			foreach ($filedata as $key => $val)
			{
				if ((strpos($key, "upload_image") === 0 || strpos($key, "upload_file") === 0) && $val["size"] > 0)
				{
					$hasfile = true;
					$uploadfile = $key;
					break;
				}
			}

			if ($hasfile)
				return $this->processUpload($uploadfile);
		}

		if (!$this->params->get("inlist", 1))
			return "";


		$params = JComponentHelper::getParams('com_media');
		$mediabase = JURI::root(true) . '/' . $params->get('image_path', 'images/stories');
		// folder relative to media folder
		$folder = $this->params->get("folder", "jevents");

		$imagenos = intval($this->params->get("imnum", 1));

		// if loading multiple images then needs group by!!
		if ($imagenos > 1)
		{
			$needsgroupdby = true;
		}

		// index from 1 !!!
		for ($i = 1; $i <= $imagenos; $i++)
		{

			$extrajoin[] = " #__jev_files as jevfil" . $i . " ON jevfil" . $i . ".ev_id = ev.ev_id AND jevfil" . $i . ".filetype='image' AND jevfil" . $i . ".file_num=$i";
			$extrafields .= <<<SQL
		, CASE WHEN (jevfil$i.filename='') THEN '' ELSE CONCAT('$mediabase/$folder/',jevfil$i.filename) END as imageurl$i
		, CASE WHEN (jevfil$i.filename='') THEN '' ELSE CONCAT('<img src="$mediabase/$folder/',jevfil$i.filename,'" />') END as imageimg$i
		, CASE WHEN (jevfil$i.filename='') THEN '' ELSE CONCAT('$mediabase/$folder/thumbnails/thumb_',jevfil$i.filename) END as imagethumb$i
		, CASE WHEN (jevfil$i.filename='') THEN '' ELSE CONCAT('<img src="$mediabase/$folder/thumbnails/thumb_',jevfil$i.filename,'" />') END as thumbimg$i
SQL;
			// backwards compatability
			if ($i == 1)
			{
				$extrafields .= <<<SQL
		, CASE WHEN (jevfil$i.filename='') THEN '' ELSE CONCAT('$mediabase/$folder/',jevfil$i.filename) END as imageurl
		, CASE WHEN (jevfil$i.filename='') THEN '' ELSE CONCAT('<img src="$mediabase/$folder/',jevfil$i.filename,'" />') END as imageimg
		, CASE WHEN (jevfil$i.filename='') THEN '' ELSE CONCAT('$mediabase/$folder/thumbnails/thumb_',jevfil$i.filename) END as imagethumb
		, CASE WHEN (jevfil$i.filename='') THEN '' ELSE CONCAT('<img src="$mediabase/$folder/thumbnails/thumb_',jevfil$i.filename,'" />') END as thumbimg
		, CASE WHEN (jevfil$i.filetitle='') THEN '' ELSE jevfil$i.filetitle END as imagetitle$i
SQL;
			}
		}

		$filenos = intval($this->params->get("filnum", 1));
		for ($i = 1; $i <= $filenos; $i++)
		{
			$extrajoin[] = " #__jev_files as jevfile" . $i . " ON jevfile" . $i . ".ev_id = ev.ev_id AND jevfile" . $i . ".filetype='file' AND jevfile" . $i . ".file_num=$i";
			$extrafields .= <<<SQL
		, CASE WHEN (jevfile$i.filename='') THEN '' ELSE CONCAT('$mediabase/$folder/',jevfile$i.filename) END as filehref$i
		, jevfile$i.filetitle as filetitle$i
		, CASE WHEN (jevfile$i.filename='') THEN '' ELSE CONCAT('<a href="$mediabase/$folder/',jevfile$i.filename,'" />',jevfile$i.filetitle,'</a>') END as filelink$i
SQL;
		}

	}

	private
			function processUpload($uploadfile)
	{

		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		$this->authorised(true);

		// Get the media component configuration settings
		$params = JComponentHelper::getParams('com_media');
		// Set the path definitions
		if (!defined('JEVP_MEDIA_BASE'))
		{
			define('JEVP_MEDIA_BASE', JPATH_ROOT . "/" . $params->get('image_path', 'images/stories'));
			define('JEVP_MEDIA_BASEURL', JURI::root(true) . '/' . $params->get('image_path', 'images/stories'));
		}

		include_once(dirname(__FILE__) . "/files/uploadhelper.php");
		$uploadhelper = new JevUploadHelper($this->params);
		$filename = $uploadfile;

		$files = JFactory::getApplication()->input->files;
		$filedata = $files->getArray();

		foreach ($filedata as $fname => $file)
		{
			if (strpos($fname, $filename) === 0)
			{
				if (strpos($uploadfile, "upload_image") === 0)
				{
					$filename = $uploadhelper->processImageUpload($fname);
					$filetype = "image";
				}
				else
				{
					$filename = $uploadhelper->processFileUpload($fname, ".xml", explode(",", $this->params->get("allowedfiles", "csv,xml,pdf,doc,xls")));
					$filetype = "file";
				}
				$oname = $_FILES[$fname]['name'];
				?>
				<script  type="text/javascript">
					var oname = "<?php echo $oname; ?>";
					var fname = "<?php echo $fname; ?>";
					var filename = "<?php echo $filename; ?>";
					var filetype = "<?php echo $filetype; ?>";
				<?php if ($filetype == "image")
				{ ?>
						window.parent.setImageFileName(fname, filename, oname);
				<?php }
				else
				{ ?>
						window.parent.setLinkFileHref(fname, filename, oname);
				<?php } ?>
				</script>
				<?php
			}
		}
		exit();
		return;

	}

	private
			function erroralert($msg)
	{
		?>
		<html>
			<head>
				<script  type="text/javascript">
					alert("<?php echo $msg; ?>");
				</script>
			</head>
			<body>
			</body>
		</html>
		<?php
		exit();

	}

	private
			function authorised($kill = false)
	{
		$jevuser = JEVHelper::getAuthorisedUser();
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		$authorisedonly = $params->get("authorisedonly", 0);
		$user = JFactory::getUser();

		if ($user->id == 0 && $this->params->get("anonupload", 0))
		{
			$plugin = JPluginHelper::getPlugin('jevents', 'jevanonuser');
			$pluginparams = new JRegistry($plugin->params);
			if ($pluginparams->get("recaptchapublic", false))
			{
				$this->anonupload = true;
				return true;
			}
		}

		if ($authorisedonly)
		{
			if (is_null($jevuser))
			{
				// Set the layout
				if ($kill)
					$this->erroralert(JText::_("JEV_Not_authorised", true));
				return false;
			}
			else if (!$jevuser->canuploadimages && !$jevuser->canuploadmovies)
			{
				// Set the layout
				if ($kill)
					$this->erroralert(JText::_("JEV_Not_authorised", true));
				return false;
			}
		}
		else
		{
			// restrict usage to certain user types
			if (version_compare(JVERSION, "1.6.0", 'ge'))
			{
				$userGroups = JFactory::getUser()->getAuthorisedGroups();
				if (!is_array($userGroups) || !is_array($this->params->get('upimageslevel', array(8))) || !is_array($this->params->get('upfileslevel', array(8))))
				{
					$this->erroralert(JText::_("JEV_YOU_MUST_SET_PERMISSIONS_IN_JEVFILES_PLUGIN", true));
				}
				if (!array_intersect($this->params->get('upimageslevel', array(8)), array_values($userGroups)) && !array_intersect($this->params->get('upfileslevel', array(8)), array_values($userGroups)))
				{
					if ($kill)
						$this->erroralert(JText::_("JEV_Not_authorised", true));
					return false;
				}
			}
			else
			{
				$groupID = JEVHelper::getGid($user); // RSH Change to make J!1.6 Compatible - used to be: $user->get('gid');
				if ($groupID < $this->params->get('upimageslevel', 19) && $groupID < $this->params->get('upfileslevel', 19))
				{
					if ($kill)
						$this->erroralert(JText::_("JEV_Not_authorised", true));
					return false;
				}
			}
		}
		return true;

	}

	static
			function fieldNameArray($layout = 'detail')
	{
		$return = array();
		$return['group'] = JText::_("JEV_STANDARD_IMAGES_FILES", true);

		$labels = array();
		$values = array();

		JPluginHelper::importPlugin('jevents');
		$plugin = JPluginHelper::getPlugin("jevents", "jevfiles");
		$params = new JRegistry($plugin->params);
		$imagenos = intval($params->get("imnum", 1));
		$filenos = intval($params->get("filnum", 1));

		if ($layout == "edit")
		{
			if (count($imagenos) > 0)
			{
				// index from 1 !!!
				for ($i = 1; $i <= $imagenos; $i++)
				{
					$label = JText::_("JEV_STANDARD_IMAGE_EDIT_" . $i);
					if ($label == "JEV_STANDARD_IMAGE_EDIT_" . $i)
						$label = JText::_("JEV_STANDARD_IMAGE_".$i);
					if ($label == "JEV_STANDARD_IMAGE_" . $i)
						$label = JText::_("JEV_STANDARD_IMAGE_");
					$labels[] = $label;
					$values[] = "image$i";
				}

				for ($i = 1; $i <= $filenos; $i++)
				{
					$label = JText::_("JEV_STANDARD_FILE_EDIT_" . $i);
					if ($label == "JEV_STANDARD_FILE_EDIT_" . $i)
					$label = JText::_("JEV_STANDARD_FILE_" . $i);
					if ($label == "JEV_STANDARD_FILE_" . $i)
						$label = JText::_("JEV_STANDARD_FILE");
					$labels[] = $label;
					$values[] = "file$i";
				}
			}

			$return['values'] = $values;
			$return['labels'] = $labels;
			return $return;
		}

		if (count($imagenos) > 0)
		{
			if ($layout == "detail")
			{
				// index from 1 !!!
				for ($i = 1; $i <= $imagenos; $i++)
				{
					$labels[] = JText::_("JEV_STANDARD_IMAGE", true) . " " . $i;
					$labels[] = JText::_("JEV_IMAGE_LINK", true) . " " . $i;
					$labels[] = JText::_("JEV_STANDARD_THUMBNAIL", true) . " " . $i;
					$labels[] = JText::_("JEV_STANDARD_POPUP", true) . " " . $i;
					$values[] = "JEV_STANDARD_IMAGE_$i";
					$values[] = "JEV_IMAGE_LINK_$i";
					$values[] = "JEV_STANDARD_THUMBNAIL_$i";
					$values[] = "JEV_STANDARD_POPUP_$i";
				}
				for ($i = 1; $i <= $filenos; $i++)
				{
					$labels[] = JText::_("JEV_STANDARD_FILE_TITLE", true) . " " . $i;
					$labels[] = JText::_("JEV_STANDARD_FILE_LINK", true) . " " . $i;
					$labels[] = JText::_("JEV_STANDARD_FILE_HREF", true) . " " . $i;

					$values[] = "JEV_FILE_TITLE_$i";
					$values[] = "JEV_FILE_LINK_$i";
					$values[] = "JEV_FILE_HREF_$i";
				}
				$labels[] = JText::_("JEV_PINTEREST", true);
				$values[] = "PINTEREST";
			}
			else if ($layout == "list")
			{
				// index from 1 !!!
				for ($i = 1; $i <= $imagenos; $i++)
				{
					$labels[] = JText::_("JEV_STANDARD_IMAGE", true) . " " . $i;
					$labels[] = JText::_("JEV_STANDARD_THUMBNAIL", true) . " " . $i;
					$labels[] = JText::_("JEV_STANDARD_POPUP", true) . " " . $i;
					$labels[] = JText::_("JEV_THUMBNAIL_LINK", true) . " " . $i;

					$values[] = "JEV_LIST_IMAGE_$i";
					$values[] = "JEV_LIST_THUMBNAIL_$i";
					$values[] = "JEV_LIST_POPUP_$i";
					$values[] = "JEV_THUMBLINK_$i";
				}

				for ($i = 1; $i <= $filenos; $i++)
				{
					$labels[] = JText::_("JEV_STANDARD_FILE_TITLE", true) . " " . $i;
					$labels[] = JText::_("JEV_STANDARD_FILE_LINK", true) . " " . $i;
					$labels[] = JText::_("JEV_STANDARD_FILE_HREF", true) . " " . $i;

					$values[] = "JEV_FILE_TITLE_$i";
					$values[] = "JEV_FILE_LINK_$i";
					$values[] = "JEV_FILE_HREF_$i";
				}
			}
			// index from 1 !!!
			for ($i = 1; $i <= $imagenos; $i++)
			{
				$labels[] = JText::_("JEV_IMAGE_URL", true) . " " . $i;
				$labels[] = JText::_("JEV_THUMBNAIL_URL", true) . " " . $i;

				$values[] = "JEV_IMAGEURL_$i";
				$values[] = "JEV_THUMBURL_$i";
			}
			for ($i = 1; $i <= $imagenos; $i++)
			{
				$labels[] = JText::_("JEV_SIZED_IMAGE", true) . " " . $i;

				$values[] = "JEV_SIZEDIMAGE_$i;400x300";
			}
			for ($i = 1; $i <= $imagenos; $i++)
			{
				$labels[] = JText::sprintf("JEV_SIZED_IMAGE_URL",  $i, array('jsSafe'=>true)) ;

				$values[] = "JEV_SIZEDIMAGE_URL_$i;400x300";
			}
		}


		$return['values'] = $values;
		$return['labels'] = $labels;

		return $return;

	}

	// Special method called when plugins are saved
	// Have the image sizes changed ??
	/*
	static function onPluginBeforeSave($context, $article, $isNew)
	{
		if (!$isNew){
			$plugin = JPluginHelper::getPlugin("jevents", "jevfiles");
			if ($plugin)
			{
				$params = new JRegistry($plugin->params);
			}
		}
		$x = 1;
	}
	*/
	static
			function substitutefield($row, $code)
	{

		$plugin = JPluginHelper::getPlugin("jevents", "jevfiles");
		$params = new JRegistry($plugin->params);

		if (strpos($code, "JEV_STANDARD_IMAGE_") === 0 || strpos($code, "JEV_IMAGEURL_") === 0 )
		{
			$i = str_replace("JEV_STANDARD_IMAGE_", "", $code);
			$name = "_image" . $i;
			if (strpos($code, "JEV_IMAGEURL_") === 0) {
				$i = str_replace("JEV_IMAGEURL_", "", $code);
				$name = "_imageurl" . $i;
			}
			if ( !isset($row->$name) || $row->$name == "")
			{
				$defimage = $params->get("defaultimage", false);
				if ($params->get("defaultonlyforfirst", 0) && ! ($code == "JEV_STANDARD_IMAGE_1" || $code == "JEV_IMAGEURL_1" ))
				{
					$defimage = false;
				}
				if ($defimage)
				{
					if (strpos($code, "JEV_STANDARD_IMAGE_") === 0 ){
						return "<img src='" . $defimage . "' class='jev_image" . $i . "'  />";
					}
					else {
						return $defimage;
					}
				}
				$catimage = $row->getCategoryImage();
				if ($catimage)
				{
					return $catimage;
				}
				return "";
			}
			$fname = "_imagetitle$i";
			if (!empty($row->$fname))
				$filetitle = $row->$fname;
			else
				$filetitle = $row->title();
			if (strpos($code, "JEV_STANDARD_IMAGE_") === 0 ){
				return str_replace("<img ", "<img  alt='" . htmlentities($filetitle). "' ",  $row->$name);
			}
			else {
				return $row->$name;
			}
		}
		if (strpos($code, "JEV_SIZEDIMAGE_") === 0  || strpos($code, "JEV_SIZEDIMAGE_URL_") === 0 )
		{
			if (strpos($code, ";")===false || strpos($code, "x")===false){
				return "";
			}
			
			$base = JUri::base();
			$basepath = JUri::base(true);

			list($code, $dimensions) = explode(";", $code);
			list($width, $height) = explode("x",$dimensions);
			$i = str_replace(array("JEV_SIZEDIMAGE_URL_","JEV_SIZEDIMAGE_"), "", $code);

			$name = "_imageurl" . $i;
			$img = false;
			if ( !isset($row->$name) || $row->$name == "")
			{
				$defimage = $params->get("defaultimage", false);
				if ($defimage)
				{
					$img = $defimage;
				}
				else {
					$catimage = $row->getCategoryImage();
					if ($catimage)
					{
						$img = $catimage;
					}
				}
				if (!$img) return "";
			}
			else {
				$img = $row->$name;
			}

			$imgfile = str_replace($base, "", $img);
			$imgfile = str_replace($basepath, "", $imgfile);
			if (strpos($imgfile, "/")===0){
				$imgfile = substr($imgfile, 1);
			}

			// Get the media component configuration settings
			$mediaparams = JComponentHelper::getParams('com_media');
			// Set the path definitions
			if (!defined('JEVP_MEDIA_BASE'))
			{
				define('JEVP_MEDIA_BASE', JPATH_ROOT . '/' . $mediaparams->get('image_path', 'images/stories'));
				define('JEVP_MEDIA_BASEURL', JURI::root(true) . '/' . $mediaparams->get('image_path', 'images/stories'));
			}
			// folder relative to media folder
			$folder = $params->get("folder", "");
			if ($folder == "")
			{
				echo JText::_("JEV_SAVE_PLUGIN_PARAMETERS");
				return;
			}
			// ensure folder exists
			jimport('joomla.filesystem.folder');
			$imgfile = str_replace($mediaparams->get('image_path', 'images/stories') . "/" . $folder. "/", "", $imgfile);
			if (!JFolder::exists(JEVP_MEDIA_BASE . "/" . $folder . "/". $dimensions)) {
				JFolder::create(JEVP_MEDIA_BASE . "/" . $folder. "/". $dimensions);
			}

			// use basename($imgfile) in case default image is not in the main path!
			$targetfile = JEVP_MEDIA_BASE . "/" . $folder. "/". $dimensions. "/". basename($imgfile);
			if (!JFile::exists($targetfile)) {
				jimport("joomla.image.image");
				$src = JFile::exists(JEVP_MEDIA_BASE . "/" . $folder. "/originals/". $imgfile) ? JEVP_MEDIA_BASE . "/" . $folder. "/originals/". $imgfile : JEVP_MEDIA_BASE . "/" . $folder. "/". $imgfile;
				if (!JFile::exists($src) && JFile::exists(JPATH_SITE."/".$imgfile)){
					$src = JPATH_SITE."/".$imgfile;

				}
				else if (!JFile::exists($src)){
					return "";
				}
				$image = new JImage($src);
				$image = $image->resize($width, $height);
				$image->toFile($targetfile);
			}
			$img = JEVP_MEDIA_BASEURL . "/" . $folder. "/". $dimensions. "/". basename($imgfile);

			$fname = "_imagetitle$i";
			if (!empty($row->$fname))
				$filetitle = $row->$fname;
			else
				$filetitle = $row->title();
			if (strpos($code, "JEV_SIZEDIMAGE_URL_") === 0){
				return $img;
			}
			else {
				return "<img alt='" . htmlentities($filetitle). "' src='".  $img. "' width='" . $width . "' height='" . $height . "' />";
			}
		}
		if (strpos($code, "JEV_IMAGE_LINK_") === 0)
		{
			$i = str_replace("JEV_IMAGE_LINK_", "", $code);
			$name = "_imageurl" . $i;
			if (!isset($row->$name) || $row->$name == "")
			{
				$defimage = $params->get("defaultimage", false);
				if ($params->get("defaultonlyforfirst", 0) && $code != "JEV_IMAGE_LINK_1")
				{
					$defimage = false;
				}
				if ($defimage)
				{
					return $defimage;
				}
				$catimage = plgJEventsjevfiles::getCategoryImageUrl($row);
				if ($catimage)
				{
					return $catimage;
				}
				return "";
			}
			return $row->$name;
		}
		if (strpos($code, "JEV_STANDARD_THUMBNAIL_") === 0  || strpos($code, "JEV_THUMBURL_") === 0)
		{
			$i = str_replace("JEV_STANDARD_THUMBNAIL_", "", $code);
			$name = "_thumbimage" . $i;
			if (strpos($code, "JEV_THUMBURL_") === 0) {
				$i = str_replace("JEV_THUMBURL_", "", $code);
				$name = "_imagethumb" . $i;
				if (!isset($row->$name)) {
					$name = "_thumburl" . $i;
				}
			}
			if (isset($row->$name))
			{
				if ($row->$name == "")
				{
					$defimage = $params->get("defaultthumb", false);
					if ($params->get("defaultonlyforfirst", 0) && !($code == "JEV_STANDARD_THUMBNAIL_1" ||  $code == "JEV_THUMBURL_1" ))
					{
						$defimage = false;
					}
					if ($defimage)
					{
						if (strpos($code, "JEV_STANDARD_THUMBNAIL_") === 0 ){
							return "<img src='" . $defimage . "' class='jev_imagethumb" . $i . "'  />";
						}
						else {
							return $defimage;
						}

					}
					$catimage = $row->getCategoryImage();
					if ($catimage)
					{
						return $catimage;
					}
				}
				$fname = "_imagetitle$i";
				if (!empty($row->$fname))
					$filetitle = $row->$fname;
				else
					$filetitle = $row->title();

				if (strpos($code, "JEV_STANDARD_THUMBNAIL_") === 0 ){
					return str_replace("<img ", "<img  alt='" . htmlentities($filetitle). "' ",  $row->$name);
				}
				else {
					return $row->$name;
				}

			}
		}
		if (strpos($code, "JEV_THUMBLINK_") === 0)
		{
			$i = str_replace("JEV_THUMBLINK_", "", $code);
			$name = "_imagethumb" . $i;
			if (!isset($row->$name) || $row->$name == "")
			{
				$defimage = $params->get("defaultthumb", false);
				if ($params->get("defaultonlyforfirst", 0) && $code != "JEV_THUMBLINK_1")
				{
					$defimage = false;
				}
				if ($defimage)
				{
					$row->$name = $defimage;
				}
				else
				{
					$catimage = plgJEventsjevfiles::getCategoryImageUrl($row);
					if ($catimage)
					{
						$row->$name =  $catimage;
					}
				}
			}
			$fname = "_imagetitle$i";
			if (!empty($row->$fname))
				$filetitle = $row->$fname;
			else
				$filetitle = "";
			$img = "<img src='" . $row->$name . "' class='jev_imagethumb" . $i . "'  alt='" . htmlentities($filetitle) . "' />";

			// Title link
			$reg = JevRegistry::getInstance("jevents");
			static $datamodel;
			if (!isset($datamodel))
			{
				$datamodel = $reg->getReference("jevents.datamodel", false);
				if (!$datamodel)
				{
					$datamodel = new JEventsDataModel();
				}
			}

			$rowlink = $row->viewDetailLink($row->yup(), $row->mup(), $row->dup(), false);
			$rowlink = JRoute::_($rowlink . $datamodel->getCatidsOutLink());
			ob_start();
			?>
			<a class="ev_link_row" href="<?php echo $rowlink; ?>" style="font-weight:bold;" title="<?php echo JEventsHTML::special($row->title()); ?>">
			<?php echo $img; ?>
			</a>
			<?php
			$link = ob_get_clean();

			if (isset($row->$name))
				return $link;
		}

		if (strpos($code, "JEV_STANDARD_POPUP") === 0)
		{
			$i = str_replace("JEV_STANDARD_POPUP_", "", $code);
			$name = "_thumburl" . $i;
			if (!isset($row->$name) || $row->$name == "")
			{
				$defthumb = $params->get("defaultthumb", false);
				$defimg = $params->get("defaultimage", false);
				if ($params->get("defaultonlyforfirst", 0) && $code != "JEV_STANDARD_POPUP_1")
				{
					$defimg = false;
				}
				if ($defthumb && $defimg)
				{
					$thumb = $defthumb;
					$img = $defimg;
					$filetitle = "";
				}
				else
				{
					return "";
				}
			}
			else
			{
				$thumb = $row->$name;
				$name = "_imageurl" . $i;
				$img = $row->$name;
				$name = "_imagetitle$i";
				$filetitle = $row->$name;
			}

			JHTML::_('behavior.modal');
			static $cssdone = false;
			if (!$cssdone){
			JFactory::getDocument()->addStyleDeclaration("#jevents_body .modal {  display: inherit;  position: relative;}");
				$cssdone = true;
			}
			$src = "<a class='modal' rel='{handler: \"image\",}' href='" . $img . "' ><img src='" . $thumb . "' class='jev_imagethumb" . $i . "' alt='" . htmlentities($filetitle) . "' /></a>";
			return $src;
		}
		if (strpos($code, "JEV_LIST_POPUP") === 0)
		{
			$i = str_replace("JEV_LIST_POPUP_", "", $code);
			$name = "_imagethumb" . $i;
			if (!isset($row->$name) || $row->$name == "")
			{
				$defthumb = $params->get("defaultthumb", false);
				$defimg = $params->get("defaultimage", false);
				if ($params->get("defaultonlyforfirst", 0) && $code != "JEV_LIST_POPUP_1")
				{
					$defimg = false;
				}
				if ($defthumb && $defimg)
				{
					$thumb = $defthumb;
					$img = $defimg;
					$filetitle = "";
				}
				else
				{
					return "";
				}
			}
			else
			{
				$thumb = $row->$name;
				$name = "_imageurl" . $i;
				$img = $row->$name;

				$name = "_imagetitle$i";
				$filetitle = $row->title();
			}

			JHTML::_('behavior.modal');
			JFactory::getDocument()->addStyleDeclaration("#jevents_body .modal {  display: inherit;  position: relative;}");
			$src = "<a class='modal' rel='{handler: \"image\",}' href='" . $img . "' ><img src='" . $thumb . "' class='jev_imagethumb" . $i . "' alt='" . htmlentities($filetitle) . "' /></a>";
			return $src;
		}

		if (strpos($code, "JEV_LIST_IMAGE_") === 0)
		{
			$i = str_replace("JEV_LIST_IMAGE_", "", $code);
			$name = "_imageimg" . $i;
			if (!isset($row->$name) || $row->$name == "")
			{
				$defimage = $params->get("defaultimage", false);
				if ($params->get("defaultonlyforfirst", 0) && $code != "JEV_LIST_IMAGE_1")
				{
					$defimage = false;
				}
				if ($defimage)
				{
					return "<img src='" . $defimage . "' class='jev_image" . $i . "'  />";
				}
				$catimage = $row->getCategoryImage();
				if ($catimage)
				{
					return $catimage;
				}
				return "";
			}
			else
			{
				$fname = "_imagetitle$i";
				if (!empty($row->$fname))
					$filetitle = $row->$fname;
				else
					$filetitle = "";
				return str_replace("<img ", "<img  alt='" . htmlentities($filetitle). "' ",  $row->$name);
			}
		}
		if (strpos($code, "JEV_LIST_THUMBNAIL_") === 0)
		{
			$i = str_replace("JEV_LIST_THUMBNAIL_", "", $code);
			$name = "_imagethumb" . $i;
			if (!isset($row->$name) || $row->$name == "")
			{
				$defimage = $params->get("defaultthumb", false);
				if ($params->get("defaultonlyforfirst", 0) && $code != "JEV_LIST_THUMBNAIL_1")
				{
					$defimage = false;
				}
				if ($defimage)
				{
					$row->$name = $defimage;
					$filetitle = "";
					return "<img src='" . $row->$name . "' class='jev_imagethumb" . $i . "'  alt='" . htmlentities($filetitle) . "' />";
				}
				else
				{
					$catimage = $row->getCategoryImage();
					if ($catimage)
					{
						return $catimage;
					}
					return "";
				}
			}
			else
			{
				$fname = "_imagetitle$i";
				if (!empty($row->$fname))
					$filetitle = $row->$fname;
				else
					$filetitle = "";

				return "<img src='" . $row->$name . "' class='jev_imagethumb" . $i . "'  alt='" . htmlentities($filetitle) . "' />";
			}
		}

		if (strpos($code, "JEV_FILE_TITLE_") === 0)
		{
			$i = str_replace("JEV_FILE_TITLE_", "", $code);
			$name = "_filetitle" . $i;
			if (isset($row->$name))
				return $row->$name;
		}
		if (strpos($code, "JEV_FILE_LINK_") === 0)
		{
			$i = str_replace("JEV_FILE_LINK_", "", $code);
			$name = "_filelink" . $i;
			if (isset($row->$name))
				return $row->$name;
		}
		if (strpos($code, "JEV_FILE_HREF_") === 0)
		{
			$i = str_replace("JEV_FILE_HREF_", "", $code);
			$name = "_filehref" . $i;
			if (isset($row->$name))
				return $row->$name;
		}
		if ($code == "PINTEREST")
		{
			$name = "_imageurl1";
			if (!isset($row->$name) || $row->$name == "")
			{
				return "";
			}
			$reg = JevRegistry::getInstance("jevents");
			static $datamodel2;
			if (!isset($datamodel2))
			{
				$datamodel2 = $reg->getReference("jevents.datamodel", false);
				if (!$datamodel2)
				{
					$datamodel2 = new JEventsDataModel();
				}
			}

			$rowlink = $row->viewDetailLink($row->yup(), $row->mup(), $row->dup(), false);
			$rowlink = JRoute::_($rowlink . $datamodel2->getCatidsOutLink());

			$url = urlencode(substr(JUri::base(), 0, -1) . $rowlink);
			$image = "&media=" . urlencode(substr(JUri::base(), 0, -1) . $row->$name);
			$title = urlencode($row->title());
			return '<div style="margin:5px 0px" class="jevpinterest"><a href="http://pinterest.com/pin/create/button/?url=' . $url . '&description=' . $title . $image . '" class="pin-it-button" count-layout="horizontal">Pin It</a>
<script type="text/javascript" src="http://assets.pinterest.com/js/pinit.js"></script></div>';
		}

		return "";

	}

	static public function getCategoryImageUrl($row)
	{
		$data = $row->getCategoryData();
		if (is_array($data))
		{
			$data = $data[0];
		}
		if ($data)
		{
			$params = json_decode($data->params);
			if (isset($params->image) && $params->image != "")
			{
				return JURI::root() . $params->image;
			}
		}

	}

}