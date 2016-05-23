<?php

/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */
defined('JPATH_BASE') or die('Direct Access is not allowed.');

jimport('joomla.application.component.controlleradmin');

class AdminPeopleController extends JControllerAdmin
{

	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask('list', 'overview');
		$this->registerTask('unpublish', 'unpublish');
		$this->registerDefaultTask("overview");

		// check at least one people type has been created
		if ($model =  $this->getModel("types", "PeopleTypesModel"))
		{
			if ($model->getTotal() == 0)
			{
				$this->setRedirect("index.php?option=com_jevpeople&task=types.overview", JText::_('JEV_CREATE_TYPE_WARNING'));
			}
		}
		JPluginHelper::importPlugin('jevents');
	}

	function overview()
	{
		$this->_authoriseAccess();

                // get the view
		$viewName = "people";
		$this->view = $this->getView($viewName, "html");

		// Set the layout
		$this->view->setLayout('list');
		$this->view->assign('title', JText::_('PEOPLE_LIST'));
		$jevuser = JEVHelper::getAuthorisedUser();
		$this->view->assign('jevuser', $jevuser);

		// Get/Create the model
		if ($model =  $this->getModel($viewName, "PeopleModel"))
		{
			// Push the model into the view (as default)
			$this->view->setModel($model, true);
		}

		$this->view->overview();

	}
        
	function select()
	{
		//$this->_authoriseAccess();
		// get the view
		$viewName = "people";
		$this->view = $this->getView($viewName, "html");

		// Set the layout
		$this->view->setLayout('select');
		$this->view->assign('title', JText::_('PEOPLE_LIST'));

		$this->fixCreationPermissions();
		$jevuser = JEVHelper::getAuthorisedUser();
		$this->view->assign('jevuser', $jevuser);

		JRequest::setVar('filter_state', 'P', 'post');
		// Get/Create the model
		if ($model =  $this->getModel($viewName, "PeopleModel"))
		{
			// Push the model into the view (as default)
			$this->view->setModel($model, true);
		}

		$this->view->select();

	}

	function edit()
	{

		$cid = JRequest::getVar('cid', JRequest::getVar('pers_id', array(0), 'post', 'array'), 'post', 'array');
		$this->_authoriseAccess($cid);

		// get the view
		$viewName = "people";
		$this->view = $this->getView($viewName, "html");

		JRequest::setVar('hidemainmenu', 1);

		$returntask = JRequest::getVar('returntask', "people.overview");
		if ($returntask != "people.list" && $returntask != "people.overview" && $returntask != "people.select")
		{
			$returntask = "people.overview";
		}
		$this->view->assign('returntask', $returntask);

		// Set the layout
		$this->view->setLayout('edit');
		$this->view->assign('title', JText::_('PERSON_EDIT'));
		$jevuser = JEVHelper::getAuthorisedUser();
		$this->view->assign('jevuser', $jevuser);

		// Get the media component configuration settings
		$params = JComponentHelper::getParams('com_media');
		// Set the path definitions
		define('JEVP_MEDIA_BASE', JPATH_ROOT . "/" . $params->get('image_path', 'images' . "/" . 'stories'));
		define('JEVP_MEDIA_BASEURL', JURI::root(true) . '/' . $params->get('image_path', 'images/stories'));

		// Get/Create the models
		if ($model =  $this->getModel("person", "PeopleModel"))
		{
			// Push the model into the view (as default)
			$this->view->setModel($model, true);
		}
		$typemodel =  $this->getModel("type", "PeopleTypesModel");
		$this->view->setModel($typemodel, false);

		$this->view->edit();

	}

	function cancel()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$cid = JRequest::getVar('cid', JRequest::getVar('pers_id', array(0), 'post', 'array'), 'post', 'array');
		$this->_authoriseAccess((int) $cid[0]);

		$model =  $this->getModel("person", "PeopleModel");

		$model->getData();

		// Check the table in so it can be edited.... we are done with it anyway
		$model->checkin();

		$returntask = JRequest::getVar('returntask', "people.overview");
		if ($returntask != "people.list" && $returntask != "people.overview" && $returntask != "people.select")
		{
			$returntask = "people.overview";
		}
		$tmpl = "";

		if (method_exists($this, str_replace("people.", "", $returntask)))
		{
			$returntask = str_replace("people.", "", $returntask);
			return $this->$returntask();
		}
		if ($returntask == "people.select" || JRequest::getString("tmpl", "") == "component")
		{
			$tmpl = "&tmpl=component";
		}
		$link = JRoute::_('index.php?option=com_jevpeople&task=' . $returntask . $tmpl);
		$this->setRedirect($link);

	}

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$cid = JRequest::getVar('cid', JRequest::getVar('pers_id', array(0), 'post', 'array'), 'post', 'array');
		JRequest::setVar('pers_id', (int) $cid[0]);
		$this->_authoriseAccess((int) $cid[0]);

		// ensure no dodgy setting of global values !
		$jevuser = JEVHelper::getAuthorisedUser();

		if (!JevPeopleHelper::canCreateGlobal())
		{
			JRequest::setVar("global", 0);
		}

		$model =  $this->getModel("person", "PeopleModel");

		if ($model->store(JRequest::get('default', JREQUEST_ALLOWHTML)))
		{
			$msg = JText::_('PERSON_SAVED');
		}
		else
		{
			$msg = JText::_('ERROR_SAVING_PERSON') . " - " . $model->getError();
		}

		// Check the table in so it can be edited.... we are done with it anyway
		$model->checkin();

		$returntask = JRequest::getVar('returntask', "people.overview");
		if ($returntask != "people.list" && $returntask != "people.overview" && $returntask != "people.select")
		{
			$returntask = "people.overview";
		}
		if (method_exists($this, str_replace("people.", "", $returntask)))
		{
			$returntask = str_replace("people.", "", $returntask);
			return $this->$returntask();
		}

		$tmpl = "";
		if ($returntask == "people.select" || JRequest::getString("tmpl", "") == "component")
		{
			$tmpl = "&tmpl=component";
		}

		$mainframe = JFactory::getApplication();
		$Itemid = JRequest::getInt("Itemid");
		if (!JFactory::getApplication()->isAdmin())
		{
			$tmpl .="&Itemid=$Itemid";
		}

		$link = JRoute::_('index.php?option=com_jevpeople&task=' . $returntask . $tmpl);
		$this->setRedirect($link, $msg);

	}

	function delete()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$cid = JRequest::getVar('cid', JRequest::getVar('pers_id', array(), 'post', 'array'), 'post', 'array');
		JArrayHelper::toInteger($cid);

		$this->_authoriseAccess($cid);

		if (count($cid) < 1)
		{
			JError::raiseError(500, JText::_('SELECT_AN_ITEM_TO_DELETE'));
		}

		$model =  $this->getModel("person", "PeopleModel");
		if (!$model->delete($cid))
		{
			echo "<script> alert('" . $model->getError(true) . "'); window.history.go(-1); </script>\n";
		}

		$returntask = JRequest::getVar('returntask', "people.overview");
		if ($returntask != "people.list" && $returntask != "people.overview" && $returntask != "people.select")
		{
			$returntask = "people.overview";
		}
		if (method_exists($this, str_replace("people.", "", $returntask)))
		{
			$returntask = str_replace("people.", "", $returntask);
			return $this->$returntask();
		}

		$tmpl = "";
		if (JRequest::getString("tmpl", "") == "component")
		{
			$tmpl = "&tmpl=component";
		}

		$this->setRedirect(JRoute::_('index.php?option=com_jevpeople&task=people.list' . $tmpl));

	}

	function publish()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$cid = JRequest::getVar('cid', JRequest::getVar('pers_id', array(), 'post', 'array'), 'post', 'array');
		JArrayHelper::toInteger($cid);

		$this->_authoriseAccess($cid);

		if (count($cid) < 1)
		{
			JError::raiseError(500, JText::_('SELECT_AN_ITEM_TO_PUBLISH'));
		}

		$model =  $this->getModel("person", "PeopleModel");
		if (!$model->publish($cid, 1))
		{
			echo "<script> alert('" . $model->getError(true) . "'); window.history.go(-1); </script>\n";
		}

		$returntask = JRequest::getVar('returntask', "people.overview");
		if ($returntask != "people.list" && $returntask != "people.overview" && $returntask != "people.select")
		{
			$returntask = "people.overview";
		}
		if (method_exists($this, str_replace("people.", "", $returntask)))
		{
			$returntask = str_replace("people.", "", $returntask);
			return $this->$returntask();
		}

		$tmpl = "";
		if (JRequest::getString("tmpl", "") == "component")
		{
			$tmpl = "&tmpl=component";
		}

		$this->setRedirect(JRoute::_('index.php?option=com_jevpeople&task=people.list' . $tmpl));

	}

	function unpublish()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$cid = JRequest::getVar('cid', JRequest::getVar('pers_id', array(), 'post', 'array'), 'post', 'array');
		JArrayHelper::toInteger($cid);

		$this->_authoriseAccess($cid);

		if (count($cid) < 1)
		{
			JError::raiseError(500, JText::_('SELECT_AN_ITEM_TO_UNPUBLISH'));
		}

		$model =  $this->getModel("person", "PeopleModel");
		if (!$model->publish($cid, 0))
		{
			echo "<script> alert('" . $model->getError(true) . "'); window.history.go(-1); </script>\n";
		}

		$returntask = JRequest::getVar('returntask', "people.overview");
		if ($returntask != "people.list" && $returntask != "people.overview" && $returntask != "people.select")
		{
			$returntask = "people.overview";
		}
		if (method_exists($this, str_replace("people.", "", $returntask)))
		{
			$returntask = str_replace("people.", "", $returntask);
			return $this->$returntask();
		}

		$tmpl = "";
		if (JRequest::getString("tmpl", "") == "component")
		{
			$tmpl = "&tmpl=component";
		}

		$this->list();
		//$this->setRedirect( JRoute::_('index.php?option=com_jevpeople&task=people.list'.$tmpl) );

	}

	function globalise()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		if (JevPeopleHelper::canCreateGlobal())
		{

			$cid = JRequest::getVar('cid', JRequest::getVar('pers_id', array(), 'post', 'array'), 'post', 'array');
			JArrayHelper::toInteger($cid);

			$this->_authoriseAccess($cid);

			if (count($cid) < 1)
			{
				JError::raiseError(500, JText::_('SELECT_AN_ITEM_TO_PUBLISH'));
			}

			$model =  $this->getModel("person", "PeopleModel");
			if (!$model->globalise($cid, 1))
			{
				echo "<script> alert('" . $model->getError(true) . "'); window.history.go(-1); </script>\n";
			}

			$returntask = JRequest::getVar('returntask', "people.overview");
			if ($returntask != "people.list" && $returntask != "people.overview" && $returntask != "people.select")
			{
				$returntask = "people.overview";
			}
			if (method_exists($this, str_replace("people.", "", $returntask)))
			{
				$returntask = str_replace("people.", "", $returntask);
				return $this->$returntask();
			}

			$tmpl = "";
			if (JRequest::getString("tmpl", "") == "component")
			{
				$tmpl = "&tmpl=component";
			}
		}
		$this->setRedirect(JRoute::_('index.php?option=com_jevpeople&task=people.list' . $tmpl));

	}

	function privatise()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');


		if (JevPeopleHelper::canCreateGlobal())
		{

			$cid = JRequest::getVar('cid', JRequest::getVar('pers_id', array(), 'post', 'array'), 'post', 'array');
			JArrayHelper::toInteger($cid);

			$this->_authoriseAccess($cid);

			if (count($cid) < 1)
			{
				JError::raiseError(500, JText::_('SELECT_AN_ITEM_TO_UNPUBLISH'));
			}

			$model =  $this->getModel("person", "PeopleModel");
			if (!$model->globalise($cid, 0))
			{
				echo "<script> alert('" . $model->getError(true) . "'); window.history.go(-1); </script>\n";
			}

			$returntask = JRequest::getVar('returntask', "people.overview");
			if ($returntask != "people.list" && $returntask != "people.overview" && $returntask != "people.select")
			{
				$returntask = "people.overview";
			}
			if (method_exists($this, str_replace("people.", "", $returntask)))
			{
				$returntask = str_replace("people.", "", $returntask);
				return $this->$returntask();
			}

			$tmpl = "";
			if (JRequest::getString("tmpl", "") == "component")
			{
				$tmpl = "&tmpl=component";
			}
		}
		//$this->list();
		$this->setRedirect(JRoute::_('index.php?option=com_jevpeople&task=people.list' . $tmpl));

	}

	function upload()
	{

		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		$this->view = $this->getView("people", "html");


		if (!JevPeopleHelper::canUploadImages())
		{
			// Set the layout
			$this->view->setLayout('noauth');
			$this->view->assign('msg', JText::_('NOT_AUTHORISED'));
			$this->view->display();
			return;
		}

		$this->view->setLayout('upload');

		$folder = JRequest::getVar('folder', '', '', 'path');
		$field = JRequest::getVar('field', '', '');

		// Get the media component configuration settings
		$params = JComponentHelper::getParams('com_media');
		// Set the path definitions
		define('JEVP_MEDIA_BASE', JPATH_ROOT . "/" . $params->get('image_path', 'images' . "/" . 'stories'));
		define('JEVP_MEDIA_BASEURL', JURI::root(true) . '/' . $params->get('image_path', '/mages/stories'));

		$jevPeoepleHelper = new JevPeopleHelper();
		foreach ($_FILES as $fname => $file)
		{
			if ($fname != $field . "_file")
				continue;
			if (strpos($fname, "image") === 0)
			{
				$filename = $jevPeoepleHelper->processImageUpload($fname);
				$this->view->assign("filetype", "image");
				$oname = $_FILES[$fname]['name'];
				$this->view->assign("oname", $oname);
			}
		}
		$this->view->assign("fname", $field . "_file");
		$this->view->assign("filename", $filename);
		$this->view->display();

	}

	function orderup()
	{
		$this->_authoriseAccess();

		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$model =  $this->getModel("people", "PeopleModel");
		$model->move(-1);

		$tmpl = "";
		if (JRequest::getString("tmpl", "") == "component")
		{
			$tmpl = "&tmpl=component";
		}

		$this->setRedirect(JRoute::_('index.php?option=com_jevpeople&task=people.list' . $tmpl));

	}

	function orderdown()
	{
		$this->_authoriseAccess();

		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$model =  $this->getModel("people", "PeopleModel");
		$model->move(1);
		$tmpl = "";
		if (JRequest::getString("tmpl", "") == "component")
		{
			$tmpl = "&tmpl=component";
		}

		$this->setRedirect(JRoute::_('index.php?option=com_jevpeople&task=people.list' . $tmpl));

	}

	function saveorder()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');

		$cid = JRequest::getVar('cid', JRequest::getVar('pers_id', array(), 'post', 'array'), 'post', 'array');
		$order = JRequest::getVar('order', array(), 'post', 'array');
		JArrayHelper::toInteger($cid);
		JArrayHelper::toInteger($order);

		$this->_authoriseAccess($cid);

		$model =  $this->getModel("people", "PeopleModel");
		$model->saveorder($cid, $order);

		$tmpl = "";
		if (JRequest::getString("tmpl", "") == "component")
		{
			$tmpl = "&tmpl=component";
		}

		$msg = 'New ordering saved';
		$this->setRedirect(JRoute::_('index.php?option=com_jevpeople&task=people.overview' . $tmpl, false), $msg);

	}

	/**
	 * This mechanism currently only checks to see if user is authorised to do anything to people
	 *
	 * @param unknown_type $locid
	 */
	function _authoriseAccess($locid=0)
	{
		$jevuser = JEVHelper::getAuthorisedUser();
		$juser =  JFactory::getUser();

		if (!JevPeopleHelper::canCreateOwn() && !JevPeopleHelper::canCreateGlobal())
		{
			$this->setRedirect("index.php?option=$this->component&task=cpanel.cpanel", "NOT_AUTHORISED");
			$this->setRedirect('index.php', JText::_('NOT_AUTHORISED'));
			$this->redirect();
		}

	}

	function fixCreationPermissions()
	{
		$jevuser =  JEVHelper::getAuthorisedUser();

		if ($jevuser && ($jevuser->cancreateown || $jevuser->cancreateglobal))
		{
			if (JevPeopleHelper::canCreateOwn())
			{
				$jevuser->cancreateown = true;
			}
			else
			{
				$jevuser->cancreateown = false;
			}

			if (JevPeopleHelper::canCreateGlobal())
			{
				$jevuser->cancreateglobal = true;
			}
			else
			{
				$jevuser->cancreateglobal = false;
			}
		}

	}

}
