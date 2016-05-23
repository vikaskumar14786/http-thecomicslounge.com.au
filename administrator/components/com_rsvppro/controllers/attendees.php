<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: icalevent.php 1712 2010-03-04 07:33:11Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd, 2006-2008 JEvents Project Group
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');

jimport('joomla.application.component.controller');

class AdminAttendeesController extends JControllerLegacy
{

	protected $params;
	protected $jomsocial = false;
	protected $jevrreminders;

	/**
	 * Controler for Sessions 
	 * @param array		configuration
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask('list', 'overview');
		$this->registerTask('newtransaction', 'edittransaction');
		//$this->registerTask( 'record',  'recordAttendance' );
		//$this->registerTask( 'save',  'recordAttendance' );
		$this->registerDefaultTask("overview");

		// Must load admin language files
		$lang = JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);

		$this->dataModel = new JEventsDataModel();
		$this->queryModel = new JEventsDBModel($this->dataModel);

		$plugin = JPluginHelper::getPlugin("jevents", "jevrsvppro");
		$this->params = JComponentHelper::getParams("com_rsvppro");

		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/attendeehelper.php");
		$this->helper = new RsvpAttendeeHelper($this->params);

		jimport('joomla.filesystem.file');
		if (JFile::exists(JPATH_SITE . '/components/com_community/community.php'))
		{
			if (JComponentHelper::isEnabled("com_community"))
			{
				$this->jomsocial = true;
			}
		}
		JLoader::register('JevRsvpReminders', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrreminders.php");
		$this->jevrreminders = new JevRsvpReminders($this->params, $this->jomsocial);

	}

	/**
	 * List Atendees
	 *
	 */
	function overview()
	{

		// get the view
		$this->view = $this->getView("attendees", "html");

		$this->view->assignRef("dataModel", $this->dataModel);
		$mainframe = JFactory::getApplication();
		$option = JRequest::getCmd("option");

		$db = JFactory::getDBO();

		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}
		list($atd_id, $rp_id) = explode("|", $atd_id[0]);

		$atd_id = intval($atd_id);
		$rp_id = intval($rp_id);

		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $atd_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();

		if ($rp_id == 0)
		{
			$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, JFactory::getApplication()->isAdmin(), "icaldb");
			$repeat = $vevent->getFirstRepeat();
		}
		else
		{
			list($year, $month, $day) = JEVHelper::getYMD();
			$repeatdata = $this->dataModel->getEventData($rp_id, "icaldb", $year, $month, $day);
			if ($repeatdata && isset($repeatdata["row"]))
				$repeat = $repeatdata["row"];
		}

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $rsvpdata);
		$registry->set("event", $repeat);

		$this->countAttendees($atd_id);

		$repeating = JRequest::getInt('repeating', 0);

		$limit = intval($mainframe->getUserStateFromRequest("attendeelistlimit", 'limit', JFactory::getApplication()->get("list_limit", 10)));
		$limitstart = intval($mainframe->getUserStateFromRequest("attendeelimitstart", 'limitstart', 0));

		$where = array();
		$join = array();

		$where[] = "ev.ev_id IS NOT NULL";
		$where[] = "atd.id = $atd_id";
		$where[] = "atdees.rp_id = $rp_id";

		$user = JFactory::getUser();
		if ($user->id != $repeat->created_by() && !JEVHelper::isAdminUser($user) && !JEVHelper::canDeleteEvent($repeat, $user))
		{
			if (!$this->params->get("showunpaidattendees", 0))
			{
				$where[] = " atdees.attendstate!=4";
			}
			if (!$this->params->get("allowmaybe", 0))
			{
				$where[] = " atdees.attendstate!=2";
			}
			if (!$this->params->get("allowpending", 0))
			{
				$where[] = " atdees.attendstate!=3";
			}
			if (!$this->params->get("shownonattendees", 0))
			{
				$where[] = " atdees.attendstate!=0";
			}
		}

		// non-Admin users should not see pending attendees
		if (!($user->id == $repeat->created_by() || JEVHelper::isAdminUser($user) || JEVHelper::canDeleteEvent($repeat, $user)))
		{
			$where[] = " atdees.attendstate!=3";
		}

		$filter_order = $mainframe->getUserStateFromRequest($option . 'atd_filter_order', 'filter_order', 'atd.id', 'cmd');
		$filter_order_Dir = $mainframe->getUserStateFromRequest($option . 'atd_filter_order_Dir', 'filter_order_Dir', ' ASC', 'word');
		$orderby = ' ORDER BY ' . $filter_order . ' ' . $filter_order_Dir;

		$filter_waiting = $mainframe->getUserStateFromRequest($option . 'atd_filter_waiting', 'filter_waiting', -1, 'int');
		if ($filter_waiting >= 0)
		{
			$where[] = "atdees.waiting =" . $filter_waiting;
		}

		$filter_confirmed = $mainframe->getUserStateFromRequest($option . 'atd_filter_confirmed', 'filter_confirmed', -1, 'int');
		if ($filter_confirmed >= 0)
		{
			$where[] = "atdees.confirmed =" . $filter_confirmed;
		}

		$filter_attendstate = $mainframe->getUserStateFromRequest($option . 'atd_filter_attendstate', 'filter_attendstate', -1, 'int');
		if ($filter_attendstate >= 0)
		{
			$where[] = "atdees.attendstate =" . $filter_attendstate;
		}

		$search = $mainframe->getUserStateFromRequest("atsearch{" . RSVP_COM_COMPONENT . "}", 'search', '');
		$search = $db->escape(trim(strtolower($search)));

		if ($search)
		{
			// New parameterised fields may have a name field that we should use that in the search too
			$fieldsearch  = "";
			if ($rsvpdata->template != "")
			{
				$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);
				if (is_int($xmlfile) || file_exists($xmlfile))
				{
					$tempparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, $repeat);
					$tempparams = $tempparams->renderToBasicArray( 'xmlfile', null);
					foreach ($tempparams as $param)
					{
						// is this a name field?  If so then use this in preference to the user's profile name
						if (isset($param["isname"]) && $param["isname"])
						{
							$fieldsearch  = " OR atdees.params LIKE '%\"".$param["name"]. "\":[\"$search%'";
						}
					}
				}
			}
			$where[] = "(atdees.email_address LIKE '%$search%' OR ju.username LIKE '%$search%' OR ju.email LIKE '%$search%'  OR atdees.id = '$search' $fieldsearch)";
		}

		if ($repeating)
		{
			$where[] = "atd.allrepeats=1 AND atd.allowregistration>0 ";
		}
		else
		{
			$where[] = "atd.allrepeats=0  AND atd.allowregistration>0 ";
		}

		$query = "SELECT count(distinct atdees.id)"
				. "\n FROM #__jevents_vevent AS ev "
				. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
				. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
				. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id"
				. "\n LEFT JOIN #__users AS ju ON ju.id = atdees.user_id"
				. ( count($join) ? "\n LEFT JOIN  " . implode(' LEFT JOIN ', $join) : '' )
				. ( count($where) ? "\n WHERE " . implode(' AND ', $where) : '' );
		$db->setQuery($query);
		//echo $db->_sql;
		$total = $db->loadResult();

		echo $db->getErrorMsg();
		if ($limit > $total)
		{
			$limitstart = 0;
		}

		$namefields = array("ju.username", "ju.name", "ju.name, ju.username");
		$namefield = $namefields [$this->params->get("userdatatype", 0)];
		
		
		$query = "SELECT det.*, atd.* , atd.id as atd_id, atdc.atdcount, atdees.*,atdees.id as atdee_id, ju.username, ju.email, "
				. " CASE WHEN atdees.user_id=0 THEN atdees.email_address ELSE CONCAT_WS(' - ',$namefield,ju.email) END as attendee, "
				. " CASE WHEN atdees.user_id=0 THEN atdees.email_address ELSE ju.name END as name "
				. "\n FROM #__jevents_vevent as ev "
				. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
				. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
				. "\n LEFT JOIN #__jev_attendeecount AS atdc ON atd.id = atdc.at_id"
				. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id"
				. "\n LEFT JOIN #__users AS ju ON ju.id = atdees.user_id"
				. ( count($join) ? "\n LEFT JOIN  " . implode(' LEFT JOIN ', $join) : '' )
				. ( count($where) ? "\n WHERE " . implode(' AND ', $where) : '' )
				. "\n GROUP BY atdees.id $orderby"
		;

		if ($limit > 0)
		{
			$query .= "\n LIMIT $limitstart, $limit";
		}
		$db->setQuery($query);

		$rows = $db->loadObjectList();

		echo $db->getErrorMsg();

		// attach repeat into row
		foreach ($rows as $key => $val)
		{
			$rows[$key]->eventrepeat = $repeat;
			// Make sure guest count is up to date
			$this->analyseGuests($rows[$key], $rsvpdata, $repeat);

			// New parameterised fields may have a name field that we should use
			if ($rsvpdata->template != "")
			{
				$attendee = & $rows[$key];
				$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);
				if ( (is_int($xmlfile) || file_exists($xmlfile)) && ($attendee->lockedtemplate==0 || $attendee->lockedtemplate==$xmlfile))
				{
					if (isset($attendee->params))
					{
						$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $repeat);
					}
					else
					{
						$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $repeat);
					}

					$params = $params->renderToBasicArray( 'xmlfile', $attendee);
					foreach ($params as $param)
					{
						// is this a name field?  If so then use this in preference to the user's profile name
						if (isset($param["isname"]) && $param["isname"])
						{
							if (!$attendee->email && $attendee->name){
								$attendee->email = $attendee->name;
							}
							$value = $param["value"];
							if (is_array($value) && $value[0] != "")
							{
								$attendee->name = $value[0];
								$attendee->attendee = $attendee->name . " - " . $attendee->email;
							}
							else if (!is_array($value) && $value != "")
							{
								$attendee->name = $value;
								$attendee->attendee = $attendee->name . " - " . $attendee->email;
							}
						}
					}
				}
			}
		}

		// update the count to reflect the guest counts!
		$this->countAttendees($atd_id);

		$options = array();
		$options[] = JHtml::_('select.option', '-1', JText::_('RSVP_CONFIRMED'));
		$options[] = JHtml::_('select.option', '0', JText::_('RSVP_PENDING'));
		$options[] = JHtml::_('select.option', '1', JText::_('RSVP_IS_CONFIRMED'));
		$confirmed = JHtml::_('select.genericlist', $options, 'filter_confirmed', 'class="inputbox" size="1" onchange="document.adminForm.task.value=\'attendees.list\';document.adminForm.submit();"', 'value', 'text', $filter_confirmed);

		$options = array();
		$options[] = JHtml::_('select.option', '-1', JText::_('RSVP_IS_WAITING'));
		$options[] = JHtml::_('select.option', '0', JText::_('RSVP_NOT_WAITING'));
		$options[] = JHtml::_('select.option', '1', JText::_('RSVP_WAITING'));
		$waiting = JHtml::_('select.genericlist', $options, 'filter_waiting', 'class="inputbox" size="1" onchange="document.adminForm.task.value=\'attendees.list\';document.adminForm.submit();"', 'value', 'text', $filter_waiting);

		$options = array();
		$options[] = JHtml::_('select.option', '-1', JText::_('RSVP_ATTEND_STATUS'));
		$options[] = JHtml::_('select.option', '0', JText::_('RSVP_NOT_ATTENDING'));
		$options[] = JHtml::_('select.option', '1', JText::_('RSVP_ATTENDING'));
		$options[] = JHtml::_('select.option', '2', JText::_('RSVP_MAYBE_ATTENDING'));
		$options[] = JHtml::_('select.option', '3', JText::_('RSVP_PENDING_APPROVAL'));
		$attendstate = JHtml::_('select.genericlist', $options, 'filter_attendstate', 'class="inputbox" size="1" onchange="document.adminForm.task.value=\'attendees.list\';document.adminForm.submit();"', 'value', 'text', $filter_attendstate);

		jimport('joomla.html.pagination');
		$pageNav = new JPagination($total, $limitstart, $limit);

		// Set the layout
		$this->view->setLayout('overview');

		$this->view->assign('atd_id', $atd_id);
		$this->view->assign('rp_id', $rp_id);
		$this->view->assign('rows', $rows);
		$this->view->assign('rsvpdata', $rsvpdata);
		$this->view->assign('event', $repeat);
		$this->view->assign('confirmed', $confirmed);
		$this->view->assign('attendstate', $attendstate);
		$this->view->assign('repeat', $repeat);
		$this->view->assign('search', $search);
		$this->view->assign('waiting', $waiting);
		$this->view->assign('order', $filter_order);
		$this->view->assign('orderdir', $filter_order_Dir);
		$this->view->assign('repeating', $repeating);
		$this->view->assign('pageNav', $pageNav);

		$this->view->display();

	}

	function ticket()
	{
		$attendee = JRequest::getInt("attendee");

		$user = JFactory::getUser();

		// Check for request forgeries
		//JRequest::checkToken('request') or jexit( 'Invalid Token' );

		if ($user->id == 0 && !$this->params->get("attendemails", 0))
		{
			return false;
		}

		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $attendee;
		$db->setQuery($sql);
		$attendee = $db->loadObject();
		if (!$attendee)
			return false;
		if ($attendee->attendstate != 1 && $attendee->attendstate != 4)
			return;

		$at_id = JRequest::getInt("at_id", -1);
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		if (!$rsvpdata)
			return false;

		if ($attendee->user_id != 0 && $attendee->user_id != $user->id )
		{
			$this->authoriseAttendee($rsvpdata);
		}
		else 	if ($attendee->user_id == 0 && $user->id>0 )
		{
			$this->authoriseAttendee($rsvpdata);
		}

		if ($rsvpdata->allrepeats)
		{
			$ev_id = $rsvpdata->ev_id;
			$datamodel = new JEventsDataModel();
			$row = $datamodel->queryModel->getEventById($ev_id, 1, "icaldb");
		}
		else
		{
			$rp_id = $attendee->rp_id;
			$datamodel = new JEventsDataModel();
			$row = $datamodel->queryModel->listEventsById($rp_id, 1, "icaldb");
		}
		if (!$row)
			return false;

		if ($attendee->user_id == 0 && $user->id==0)
		{
			$emailaddress = $this->getEmailAddress();
			if ($emailaddress == "" || strtolower($attendee->email_address) != strtolower($emailaddress)){
				echo "Invalid security code - please click the link in your confirmation email";
				return false;
			}
		}

		// get the view
		$this->view = $this->getView("attendees", "html");

		if (isset($attendee->params))
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $row);
				$feesAndBalances = $params->outstandingBalance($attendee);
				$this->view->assignRef("attendeeParams", $params);
			}
		}
		else
		{
			return;
		}

		if ( $attendee->attendstate == 4) {
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);
			if (is_int($xmlfile) &&  $xmlfile>0){
				$db = JFactory::getDbo();
				$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($xmlfile));

				$templateParams = $db->loadObject();
				if ($templateParams)
				{
					$templateParams = json_decode($templateParams->params);
					if (isset($templateParams->whentickets) && is_array($templateParams->whentickets) && in_array("outstandingbalance", $templateParams->whentickets))
					{
						// do nothing here - just an access test
					}
					else {
						return;
					}
				}
				else return;
			}
			else return;
		}


		// Set the layout
		$this->view->setLayout('ticket');
		$this->view->assign('event', $row);
		$this->view->assign('rsvpdata', $rsvpdata);
		$this->view->assign('attendee', $attendee);

		JRequest::setVar("atd_id", array($rsvpdata->id."|".$row->rp_id()));
		$this->view->display();

	}

	/**
	 * Export Attendees
	 *
	 */
	function export()
	{
		// get the view
		$this->view = $this->getView("attendees", "html");

		$mainframe = JFactory::getApplication();
		$option = JRequest::getCmd("option");

		$db = JFactory::getDBO();

		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}
		list($atd_id, $rp_id) = explode("|", $atd_id[0]);

		$atd_id = intval($atd_id);
		$rp_id = intval($rp_id);

		$repeating = JRequest::getInt('repeating', 0);

		$where = array();
		$join = array();

		$where[] = "ev.ev_id IS NOT NULL";
		$where[] = "atd.id = $atd_id";
		$where[] = "atdees.rp_id = $rp_id";
		
		$cid = JRequest::getVar('cid', array());
		JArrayHelper::toInteger($cid);
		if (count($cid)>0 && $cid[0]!=0){
			$where[] = "atdees.id in (".implode(",",$cid).")";
		}
			
		$filter_order = $mainframe->getUserStateFromRequest($option . 'atd_filter_order', 'filter_order', 'atd.id', 'cmd');
		$filter_order_Dir = $mainframe->getUserStateFromRequest($option . 'atd_filter_order_Dir', 'filter_order_Dir', ' ASC', 'word');
		$orderby = ' ORDER BY ' . $filter_order . ' ' . $filter_order_Dir;

		$filter_waiting = $mainframe->getUserStateFromRequest($option . 'atd_filter_waiting', 'filter_waiting', -1, 'int');
		if ($filter_waiting >= 0)
		{
			$where[] = "atdees.waiting =" . $filter_waiting;
		}

		$filter_confirmed = $mainframe->getUserStateFromRequest($option . 'atd_filter_confirmed', 'filter_confirmed', -1, 'int');
		if ($filter_confirmed >= 0)
		{
			$where[] = "atdees.confirmed =" . $filter_confirmed;
		}

		$search = $mainframe->getUserStateFromRequest("search{" . RSVP_COM_COMPONENT . "}", 'search', '');
		$search = $db->escape(trim(strtolower($search)));

		if ($search)
		{
			$where[] = "(atdees.email_address LIKE '%$search%' OR ju.username LIKE '%$search%' OR ju.email LIKE '%$search%' )";
		}

		if ($repeating)
		{
			$where[] = "atd.allrepeats=1 AND atd.allowregistration>0";
		}
		else
		{
			$where[] = "atd.allrepeats=0  AND atd.allowregistration>0 ";
		}
		/*
		  $query = "SELECT count(distinct atdees.id)"
		  . "\n FROM #__jevents_vevent AS ev "
		  . "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
		  . "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
		  . "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id"
		  . "\n LEFT JOIN #__users AS ju ON ju.id = atdees.user_id"
		  . ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
		  . ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' );
		  $db->setQuery( $query);
		  //echo $db->_sql;
		  $total = $db->loadResult();

		  echo $db->getErrorMsg();
		  if( $limit > $total ) {
		  $limitstart = 0;
		  }
		 */
		$query = "SELECT det.*, atd.* , atd.id as atd_id, atdc.atdcount, atdees.*,atdees.id as atdee_id,  "
				. " CASE WHEN atdees.user_id=0 THEN atdees.email_address ELSE CONCAT_WS(' - ',ju.username,ju.email) END as attendee, "
				. " CASE WHEN atdees.user_id=0 THEN '' ELSE ju.name END as attendeename, "
				. " CASE WHEN atdees.user_id=0 THEN '' ELSE ju.username END as attendeeusername, "
				. " CASE WHEN atdees.user_id=0 THEN atdees.email_address ELSE ju.email END as attendeemail "
				. "\n FROM #__jevents_vevent as ev "
				. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
				. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
				. "\n LEFT JOIN #__jev_attendeecount AS atdc ON atd.id = atdc.at_id"
				. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id"
				. "\n LEFT JOIN #__users AS ju ON ju.id = atdees.user_id"
				. ( count($join) ? "\n LEFT JOIN  " . implode(' LEFT JOIN ', $join) : '' )
				. ( count($where) ? "\n WHERE " . implode(' AND ', $where) : '' )
				. "\n GROUP BY atdees.id $orderby"
		;
		/*
		  if ($limit>0){
		  $query .= "\n LIMIT $limitstart, $limit";
		  }
		 */
		$db->setQuery($query);

		$rows = $db->loadObjectList();

		echo $db->getErrorMsg();

		if (count($rows) > 0)
		{

			$row = $rows[0];

			$db = JFactory::getDBO();
			$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();
		}
		else
		{
			$rsvpdata = false;
			return;
		}

		$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
		if ($rp_id == 0)
		{
			$repeat = $vevent->getFirstRepeat();
		}
		else
		{
			list($year, $month, $day) = JEVHelper::getYMD();
			$repeatdata = $this->dataModel->getEventData($rp_id, "icaldb", $year, $month, $day);
			if ($repeatdata && isset($repeatdata["row"]))
				$repeat = $repeatdata["row"];
		}

		$this->authoriseTransactions($repeat, false);

		// Store details in registry - will need them for waiting lists!
		$registry = JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $rsvpdata);
		$registry->set("event", $repeat);


		// attach repeat into row
		foreach ($rows as $key => $val)
		{
			$rows[$key]->eventrepeat = $repeat;
			// Make sure guest count is up to date
			//$this->analyseGuests($rows[$key], $rsvpdata, $repeat);
		}
		// update the count to reflect the guest counts!
		//$this->countAttendees($atd_id);
		// Set the layout
		$this->view->setLayout('export');

		$this->view->assign('rsvpdata', $rsvpdata);
		$this->view->assign('atd_id', $atd_id);
		$this->view->assign('rp_id', $rp_id);
		$this->view->assign('rows', $rows);
		$this->view->assign('order', $filter_order);
		$this->view->assign('orderdir', $filter_order_Dir);
		$this->view->assign('repeat', $repeat);

		$this->view->display();

	}

	function message()
	{
		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}
		list($atd_id, $rp_id) = explode("|", $atd_id[0]);
		$atd_id = intval($atd_id);
		$rp_id = intval($rp_id);

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATTENDEE"));
		}
		$message = JRequest::getString("message", "",'default', JREQUEST_ALLOWHTML );
		$subject = JRequest::getString("subject", "");
		$bcc= JRequest::getString("messagebcc", "");

		$repeating = JRequest::getInt("repeating", 0);

		$this->authoriseTransactions(false, $cid[0]);

		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . intval($atd_id);
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();

		$this->dataModel = new JEventsDataModel();
		$this->queryModel = new JEventsDBModel($this->dataModel);

		// Find the first repeat
		$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
		if ($rp_id == 0)
		{
			$repeat = $vevent->getFirstRepeat();
		}
		else
		{
			list($year, $month, $day) = JEVHelper::getYMD();
			$repeatdata = $this->dataModel->getEventData(intval($rp_id), "icaldb", $year, $month, $day);
			if ($repeatdata && isset($repeatdata["row"]))
				$repeat = $repeatdata["row"];
		}

		$user = JFactory::getUser();
		 if ($user->id != $repeat ->created_by() && !JEVHelper::isAdminUser($user) && !JEVHelper::canDeleteEvent($repeat, $user) && ! $user->authorise('core.sendmessage', 'com_rsvppro')){
			 return;
		 }
				
		if ($message != "" && $subject != "")
		{
			// Override BCC if required
			// calling RsvpHelper::getTemplateParams overwrites the global params with the values from the template e.g. BCC addresses etc,
			$templateParams  = RsvpHelper::getTemplateParams($rsvpdata);
			$comparams = JComponentHelper::getParams("com_rsvppro");
			
			if ($bcc!=""){
				//$notifybcc = null;
				//$notifybcc = $comparams->get("notifybcc", "");
				//$notifybcc = explode(",",$notifybcc);
				//$bcc = explode(",",$bcc);
				//$notifybcc = array_merge($notifybcc, $bcc);
				//$notifybcc = implode(",",$notifybcc);
				//$comparams->set("notifybcc", $notifybcc);
				$templateParams->set("notifybcc", $bcc);
				$comparams->set("notifybcc", $bcc);
			}

			// send the message
			$db = JFactory::getDBO();
			$sql = "SELECT * FROM #__jev_attendees WHERE id IN (" . implode(",", $cid) . ")";
			$db->setQuery($sql);
			$attendees = $db->loadObjectList();

			$row = $repeat;

			foreach ($attendees as $attendee)
			{

				$user = JEVHelper::getUser($attendee->user_id);
				if ($user->id == 0 && $this->params->get("attendemails", 0))
				{
					$name = $attendee->email_address;
					$username = $attendee->email_address;
				}
				else
				{
					$name = $user->name;
					$username = $user->username;
				}
				$onWaitingList = false;
				$this->notifyUser($rsvpdata, $row, $user, $name, $username, $attendee, 'SPECIAL_MESSAGE', $onWaitingList, false, $subject, $message);
			}
			// send the copy to the creatpr
			if (isset($attendee)){
				$creator = JEVHelper::getUser($repeat->created_by());
				$onWaitingList = false;
				$this->notifyUser($rsvpdata, $row, $creator, $creator->name, "", $attendee, 'SPECIAL_MESSAGE', $onWaitingList, false, $subject, $message);
			}
			
			$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" .$atd_id."|".$rp_id . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_MESSAGE_SENT"));
			return;
		}

		// get the view
		$this->view = $this->getView("attendees", "html");
		// Set the layout
		$this->view->setLayout('message');

		$this->view->assign('rsvpdata', $rsvpdata);
		$this->view->assign('cid', $cid);
		$this->view->assign('repeat', $repeat);
		$this->view->assign('repeating', $repeating);
		$this->view->assign('atd_id', $atd_id);
		$this->view->assign('rp_id', $rp_id);
		$this->view->assign('message', $message);
		$this->view->assign('subject', $subject);

		$this->view->assign('params', JComponentHelper::getParams("com_rsvppro"));

		$this->view->display();

	}

	function notwaiting()
	{
		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATTENDEE"));
		}

		$repeating = JRequest::getInt("repeating", 0);

		$atdee = $cid[0];

		$this->authoriseTransactions(false, $cid[0]);

		$db = JFactory::getDBO();
		$db->setQuery("UPDATE #__jev_attendees set waiting=0 where id=" . $atdee);
		$db->query();

		$params = JComponentHelper::getParams("com_rsvppro");

		// Now make sure the atdcount is in sync - NB only update status of CONFIRMED attendees!
		$db->setQuery("SELECT atdees.id as atdee_id , atdees.*, atd.*, atdcnt.* FROM #__jev_attendance as atd
		LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id
		LEFT JOIN #__jev_attendeecount as atdcnt ON atdcnt.at_id=atd.id AND atdcnt.rp_id=atdees.rp_id
		WHERE atdees.id=$atdee ");
		$row = $db->loadObject();

		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();

		$datamodel = new JEventsDataModel();
		if ($row->rp_id > 0)
		{
			$evt = $datamodel->queryModel->listEventsById($row->rp_id, 1, "icaldb");
		}
		else
		{
			$evt = $datamodel->queryModel->getEventById($rsvpdata->ev_id, 1, "icaldb");
		}

		// auto remind attendees
		if ($params->get("autoremind", 0) == 1 && $evt)
		{

			// always allow auto reminders for attendees if forced reminders enabled
			if ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0) == 1)
			{
				// create reminder
				// NB email address must be in the request object
				$user = JEVHelper::getUser($row->user_id);
				// create reminder
				$this->jevrreminders->remindUser($rsvpdata, $evt, $user, $row->email_address);
				$mainframe = JFactory::getApplication();
				$mainframe->enqueueMessage(JText::_("JEV_REMINDER_CONFIRMED"));
			}
		}

		if ($params->get("notifyuser", 0) && $evt){
			// I need the event object to be able to send the message
			if (!isset($this->helper->event) || !$this->helper->event){
				$this->helper->event = $evt;
			}

			$registry = JRegistry::getInstance("jevents");
			$registry->set("rsvpdata", $rsvpdata);
			$registry->set("event", $evt);

			$this->helper->notifyWaitingUser($row);
		}


		$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_ATTENDEE_NO_LONGER_ON_WAITING_LIST"));
	}

	function waiting()
	{
		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATTENDEE"));
		}

		$repeating = JRequest::getInt("repeating", 0);

		$atdee = $cid[0];

		$this->authoriseTransactions(false, $cid[0]);

		$db = JFactory::getDBO();
		$db->setQuery("UPDATE #__jev_attendees set waiting=1 where id=" . $atdee);
		$db->query();

		$params = JComponentHelper::getParams("com_rsvppro");

		// Now make sure the atdcount is in sync - NB only update status of CONFIRMED attendees!
		$db->setQuery("SELECT atdees.id as atdee_id , atdees.*, atd.*, atdcnt.* FROM #__jev_attendance as atd
		LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id
		LEFT JOIN #__jev_attendeecount as atdcnt ON atdcnt.at_id=atd.id AND atdcnt.rp_id=atdees.rp_id
		WHERE atdees.id=$atdee ");
		$row = $db->loadObject();

		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();

		$datamodel = new JEventsDataModel();
		if ($row->rp_id > 0)
		{
			$evt = $datamodel->queryModel->listEventsById($row->rp_id, 1, "icaldb");
		}
		else
		{
			$evt = $datamodel->queryModel->getEventById($rsvpdata->ev_id, 1, "icaldb");
		}

		// auto remind attendees
		if ($params->get("autoremind", 0) == 1 && $evt)
		{

			// always allow auto reminders for attendees if forced reminders enabled
			if ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0) == 1)
			{
				// create reminder
				// NB email address must be in the request object
				$user = JEVHelper::getUser($row->user_id);
				// create reminder
				$this->jevrreminders->remindUser($rsvpdata, $evt, $user, $row->email_address);
				$mainframe = JFactory::getApplication();
				$mainframe->enqueueMessage(JText::_("JEV_REMINDER_CONFIRMED"));
			}
		}

		if ($params->get("notifyuser", 0) && $evt){
			// I need the event object to be able to send the message
			if (!isset($this->helper->event) || !$this->helper->event){
				$this->helper->event = $evt;
			}

			$registry = JRegistry::getInstance("jevents");
			$registry->set("rsvpdata", $rsvpdata);
			$registry->set("event", $evt);

			$this->helper->notifyWaitingUser($row);
		}


		$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_ATTENDEE_IS_NOW_ON_WAITING_LIST"));
	}

	function confirm()
	{
		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATTENDEE"));
		}

		$repeating = JRequest::getInt("repeating", 0);

		$atdee = $cid[0];

		$this->authoriseTransactions(false, $cid[0]);

		$db = JFactory::getDBO();
		$db->setQuery("UPDATE #__jev_attendees set confirmed=1 where id=" . $atdee);
		$db->query();

		$this->postUpdateActions($this->rsvpdata, $this->event, $this->attendee,0 , $this->getRSVPParmeters($this->rsvpdata, $this->event));
		
		$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_ATTENDEE_CONFIRMED"));
	}

	function remindconfirm()
	{
		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATTENDEE"));
		}

		list($atd_id, $rp_id) = explode("|", $atd_id[0]);
		$atd_id = intval($atd_id);
		$rp_id = intval($rp_id);

		$repeating = JRequest::getInt("repeating", 0);

		$atdee = $cid[0];

		$this->authoriseTransactions(false, $cid[0]);

		$db = JFactory::getDbo();
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $atd_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();

		$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
		if ($rp_id == 0)
		{
			$repeat = $vevent->getFirstRepeat();
		}
		else
		{
			list($year, $month, $day) = JEVHelper::getYMD();
			$repeatdata = $this->dataModel->getEventData($rp_id, "icaldb", $year, $month, $day);
			if ($repeatdata && isset($repeatdata["row"]))
				$repeat = $repeatdata["row"];
		}

		// send email with confirmation details
		$creator = JEVHelper::getUser($repeat->created_by());
		if ($creator)
		{
			$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $cid[0];
			$db->setQuery($sql);
			$attendee = $db->loadObject();
			if ($attendee->email_address == "")
			{
				return;
			}
			$emailaddress = $attendee->email_address;
			$code = base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress));
			list($year, $month, $day) = JEVHelper::getYMD();
			$link = JRoute::_($repeat->viewDetailLink($year, $month, $day, false) . "&em=" . $code, false);

			if (strpos($link, "/") !== 0)
			{
				$link = "/" . $link;
			}

			$uri =  JURI::getInstance(JURI::base());
			$root = $uri->toString(array('scheme', 'host', 'port'));

			$link = $root . $link;

			// confirm email message 
			$user = JEVHelper::getUser($attendee->user_id);
			$this->notifyUser($rsvpdata,$repeat, $user, $emailaddress, $emailaddress, $attendee,'cem', $attendee->waiting, "","",$link);
			/*
			$subject = JText::sprintf("JEV_CONFIRM_EMAIL_ATTEND_SUBJECT", $repeat->title());
			$message = JText::sprintf("JEV_CONFIRM_EMAIL_ATTEND", $link, $repeat->title(), $repeat->title());
			if ($rsvpdata->allowcancellation)
			{
				$message .= JText::_("JEV_ATTENDANCE_PENDING");
			}

			$this->helper->sendMail($creator->email, $creator->name, $emailaddress, $subject, $message, 1);
			 */
		}

		$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id . "|" . $rp_id . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_ATTENDEE_REMINDED"));

	}

	function approve()
	{
		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}
		list($atd_id, $rp_id) = explode("|", $atd_id[0]);
		$atd_id = intval($atd_id);
		$rp_id = intval($rp_id);

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATTENDEE"));
		}

		$repeating = JRequest::getInt("repeating", 0);

		$atdee = $cid[0];

		$this->authoriseTransactions(false, $cid[0]);

		$db = JFactory::getDBO();
		$db->setQuery("UPDATE #__jev_attendees set attendstate=1 where id=" . $atdee);
		$db->query();

		// notify user?
		if ($this->params->get("notifyapproved", 0))
		{

			$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $atd_id;
			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();

			$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
			if ($rp_id == 0)
			{
				$repeat = $vevent->getFirstRepeat();
			}
			else
			{
				list($year, $month, $day) = JEVHelper::getYMD();
				$repeatdata = $this->dataModel->getEventData($rp_id, "icaldb", $year, $month, $day);
				if ($repeatdata && isset($repeatdata["row"]))
					$repeat = $repeatdata["row"];
			}

			JTable::addIncludePath(RSVP_TABLES);
			$attendee =  JTable::getInstance('jev_attendee');

			if ($atdee > 0)
			{
				$attendee->load($atdee);
			}

			$user = JEVHelper::getUser($attendee->user_id);
			if ($user->id == 0 && $this->params->get("attendemails", 0))
			{
				$name = $attendee->email_address;
				$username = $attendee->email_address;
			}
			else
			{
				$name = $user->name;
				$username = $user->username;
			}

			$onWaitingList = false;
			$this->notifyUser($rsvpdata, $repeat, $user, $name, $username, $attendee, 'approve', false);
		}


		$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id . "|" . $rp_id . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("JEV_ATTENDANCE_APPROVED"));

	}

	function attend()
	{
		$atd_id = JRequest::getVar("atd_id", "method", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		$cid = JRequest::getVar("cid", "method", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATTENDEE"));
		}

		$repeating = JRequest::getInt("repeating", 0);

		$atdee = $cid[0];
		$notes = JRequest::getVar("notes","method","array");
		if (array_key_exists($atdee, $notes)) {
			$notes = $notes[$atdee];
		}
		else if (array_key_exists(0, $notes)) {
			$notes = $notes[0];
		}
		else {
			$notes="";
		}

		if (!$this->authoriseTransactions(false, $atdee)){
			return;
		}

		// which guests
		JTable::addIncludePath(RSVP_TABLES);
		$attendee =  JTable::getInstance('jev_attendee');
		$attendee->load($atdee);

		$gc = JRequest::getInt("gc", "method");
		// This is clicking the icon - all guests attending
		if ($gc==0){

			$guests = array();
			for ($g=1;$g<=$attendee->guestcount;$g++){
				$guests[]=$g;
			}
			$guestatttend = implode(",",$guests);
		}
		else {
			$guests = $attendee->guestattend!=""? explode(",",$attendee->guestattend) : array();
			if (!in_array($gc, $guests)){
				$guests[]=$gc;
			}
			else {
				$lang = JFactory::getLanguage();
				$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);
				echo "<div style='height:100%;width:100%;background-color:red;padding:200px 50px;'><H1 style='padding:100px 20px!important;background-color:white!important;color:red!important;'>".
				 JText::_("RSVP_ATTENDANCE_ALREADY_RECORDED")
						. "</h1></div>";
				JRequest::setVar("tmpl","component");
				return false;

			}
			sort($guests);
			$guestatttend = implode(",",$guests);
		}

		$db = JFactory::getDBO();
		$db->setQuery("UPDATE #__jev_attendees set didattend=1 , notes=".$db->quote($notes).", guestattend =".$db->quote($guestatttend)." where id=" . $atdee);
		$db->query();

		$notes= JRequest::getVar("notes");
		if (is_array($notes) && isset($notes[0]) && $notes[0]=="qrscanned"){
			$msg =  "<div style='height:100%;width:100%;background-color:green;padding:100px 50px;'><H1 style='padding:100px 20px!important;background-color:white!important;color;green!important;'>".
					JText::_("JEV_ATTENDANCE_MARKED")
						   . "</h1></div>";
			JRequest::setVar("tmpl","component");
			$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0)."&tmpl=component", $msg);
			return;
		}
		$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("JEV_ATTENDANCE_MARKED"));

	}

	function notattend()
	{
		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATTENDEE"));
		}

		$repeating = JRequest::getInt("repeating", 0);

		$atdee = $cid[0];
		$notes = JRequest::getVar("notes","post","array");
		if (array_key_exists($atdee, $notes)) {
			$notes = $notes[$atdee];
		}
		$this->authoriseTransactions(false, $atdee);

		$db = JFactory::getDBO();
		$db->setQuery("UPDATE #__jev_attendees set didattend=0, guestattend='', notes=".$db->quote($notes)."  where id=" . $atdee);
		$db->query();

		$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("JEV_ATTENDANCE_UNMARKED"));

	}

	function close(){
		ob_end_clean();
		?>
		<script type="text/javascript">
			try {
				window.parent.jQuery('.jevmodal').modal('hide');
				//window.parent.jQuery('#translationPopup').modal('hide');
			}
			catch (e){}
			try {
				window.parent.SqueezeBox.close();
			}
			catch (e){}
			try {
				window.parent.closedialog();
			}
			catch (e){}
		</script>
		<?php
		exit();
	}

	function delete()
	{
		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}
		list($atdid, $rp_id) = explode("|", $atd_id[0]);

		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $atdid;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();

		$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
		if ($rp_id == 0)
		{
			$repeat = $vevent->getFirstRepeat();
		}
		else
		{
			list($year, $month, $day) = JEVHelper::getYMD();
			$repeatdata = $this->dataModel->getEventData($rp_id, "icaldb", $year, $month, $day);
			if ($repeatdata && isset($repeatdata["row"]))
				$repeat = $repeatdata["row"];
		}

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATTENDEE"));
		}

		$repeating = JRequest::getInt("repeating", 0);

		$atdee = implode(",", $cid);

		$this->authoriseTransactions(false, $cid[0]);

		if ($this->params->get("notifyadmincancelled", 0))
		{

			foreach ($cid as $jevattend_id)
			{
				$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $jevattend_id;
				$db = JFactory::getDBO();
				$db->setQuery($sql);
				$attendee = $db->loadObject();

				$user = JEVHelper::getUser($attendee->user_id);
				if ($user->id == 0 && $this->params->get("attendemails", 0))
				{
					$name = $attendee->email_address;
					$username = $attendee->email_address;
				}
				else
				{
					$name = $user->name;
					$username = $user->username;
				}

				// attendee has been deleted so set the attendance state to not attending for the message
				$attendee->attendstate = 0;
				
				$this->notifyUser($rsvpdata, $repeat, $user, $name, $username, $attendee, 'admincancelled', false);
			}
		}

		// cancel all their reminders
		if ($vevent && $rsvpdata->allowreminders)
		{
			foreach ($cid as $jevattend_id)
			{
				$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $jevattend_id;
				$db = JFactory::getDBO();
				$db->setQuery($sql);
				$attendee = $db->loadObject();
				$user = JEVHelper::getUser($attendee->user_id);
				$this->jevrreminders->unremindUser($rsvpdata, $vevent, $user, $attendee->email_address);
			}
			$mainframe = JFactory::getApplication();
			$mainframe->enqueueMessage(JText::_("JEV_REMINDER_CANCELLED"));
		}

		$db = JFactory::getDBO();
		$db->setQuery("DELETE FROM #__jev_attendees where id IN (" . $atdee . ")");
		$db->query();
		
		// Reset the counts
		list($atdid, $rp_id) = explode("|", $atd_id[0]);
		$this->countAttendees(intval($atdid));

		$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_ATTENDEES_DELETED"));

	}

	function edit()
	{
		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			$atdee = 0;
		}
		else
		{
			$atdee = $cid[0];
		}

		$repeating = JRequest::getInt("repeating", 0);

		// reset counts and wiating list first
		list($atdid, $rp_id) = explode("|", $atd_id[0]);
		$this->countAttendees(intval($atdid));

		JTable::addIncludePath(RSVP_TABLES);
		$attendee =  JTable::getInstance('jev_attendee');

		if ($atdee > 0)
		{
			$attendee->load($atdee);
		}

		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . intval($atdid);
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();

		$this->dataModel = new JEventsDataModel();
		$this->queryModel = new JEventsDBModel($this->dataModel);

		// Find the first repeat
		$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
		if ($rp_id == 0)
		{
			$repeat = $vevent->getFirstRepeat();
		}
		else
		{
			list($year, $month, $day) = JEVHelper::getYMD();
			$repeatdata = $this->dataModel->getEventData(intval($rp_id), "icaldb", $year, $month, $day);
			if ($repeatdata && isset($repeatdata["row"]))
				$repeat = $repeatdata["row"];
		}


		$this->authoriseAttendee($rsvpdata);

		// get the view
		$this->view = $this->getView("attendees", "html");
		// Set the layout
		$this->view->setLayout('edit');

		$this->view->assign('attendee', $attendee);
		$this->view->assign('rsvpdata', $rsvpdata);
		$this->view->assign('vevent', $vevent);
		$this->view->assign('atdee', $atdee);
		$this->view->assign('repeat', $repeat);
		$this->view->assign('repeating', $repeating);
		$this->view->assign('atd_id', $atdid);
		$this->view->assign('rp_id', $rp_id);

		$this->view->assign('params', JComponentHelper::getParams("com_rsvppro"));

		$this->view->display();

	}

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		$atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		$atdee = JRequest::getInt("atdee", 0);

		$repeating = JRequest::getInt("repeating", 0);

		// reset counts and wiating list first
		list($atdid, $rp_id) = explode("|", $atd_id[0]);
		$this->countAttendees(intval($atdid));

		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . intval($atdid);
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();

		$this->authoriseAttendee($rsvpdata);

		$templateParams  = RsvpHelper::getTemplateParams($rsvpdata);

		$params = "";
		if ($rsvpdata->template != "")
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				$params = JRequest::getVar("xmlfile", array(), "post");
				$jregistry = new JRegistry();
				$jregistry->loadArray($params);
				$params = $jregistry->toString();
			}
		}

		JTable::addIncludePath(RSVP_TABLES);
		$attendee =  JTable::getInstance('jev_attendee');

		if ($atdee > 0)
		{
			$attendee->load($atdee);
		}

		$sql = "SELECT atdcount FROM #__jev_attendeecount as a WHERE a.at_id=" . $rsvpdata->id;
		if (!$rsvpdata->allrepeats)
		{
			$sql .= " and a.rp_id=" . $rp_id;
		}
		$db->setQuery($sql);
		$attendeeCount = $db->loadResult();

		$attendee->at_id = intval($atdid);
		$attendee->rp_id = $rsvpdata->allrepeats ? 0 : intval($rp_id);
		$attendee->user_id = JRequest::getInt("user_id", 0);
		$attendee->email_address = JRequest::getString("jevattend_email");
		// ALWAYS CONFIRMED IS MANUAL ADDITION
		$attendee->confirmed = 1;
		$jevattend = JRequest::getInt("jevattend", 0);
		$attendee->attendstate = $jevattend;

		// Need the creation date for these records
		if (class_exists("JevDate"))
		{
			$date = JevDate::getDate("+0 seconds");
		}
		else
		{
			$date = JFactory::getDate("+0 seconds");
		}
		$created = $date->toSql();
		if (!isset($attendee->created) || $attendee->created == "" || $attendee->created == "0000-00-00 00:00:00")
		{
			$attendee->created = $created;
		}
		$attendee->modified = $created;

		$attendee->params = $params;
		
		// set the guest count correctly
		$guestcount = JRequest::getInt("guestcount", 1);
		$attendee->guestcount =  $guestcount;

		
		$plugin = JPluginHelper::getPlugin("jevents", "jevrsvppro");
		//$this->params = new JRegistry($plugin->params);
		// Should this be on the waiting list
		$attendee->waiting = 0;
		if ($this->params->get("capacity", 0) && $rsvpdata->capacity > 0)
		{
			if ($attendeeCount >= intval($rsvpdata->capacity))
			{
				$attendee->waiting = 1;
			}
		}
		
		// And the locked template
		$attendee->lockedtemplate= intval($rsvpdata->template);
		
		// Make sure not already covered!!
		if (intval($attendee->user_id) > 0)
		{
			$db->setQuery("SELECT atdees.id FROM #__jev_attendees as atdees  WHERE atdees.at_id=" . intval($atdid) . " AND atdees.rp_id=" . intval($rp_id) . " AND  atdees.user_id=" . $attendee->user_id);
		}
		else if (trim($attendee->email_address) !== "")
		{
			$db->setQuery("SELECT atdees.id FROM #__jev_attendees as atdees  WHERE atdees.at_id=" . intval($atdid) . " AND atdees.rp_id=" . intval($rp_id) . " AND  LOWER(atdees.email_address)=" . $db->Quote(strtolower($attendee->email_address)));
		}
		else
		{
			$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_INVALID_INPUTS"));
		}

		$row = $db->loadObject();
		if ($row && $row->id != $atdee)
		{
			$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_ATTENDEE_ALREADY_EXISTS"));
			return;
		}

		$attendee->store();

		// auto remind attendees
		if ($this->params->get("autoremind", 0) == 1 && !$attendee->waiting && ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0)))
		{
			// create reminder
			$datamodel = new JEventsDataModel();
			if ($rp_id > 0)
			{
				$row = $datamodel->queryModel->listEventsById($rp_id, 1, "icaldb");
			}
			else
			{
				$row = $datamodel->queryModel->getEventById($rsvpdata->ev_id, 1, "icaldb");
			}
			if ($row)
			{
				$mainframe = JFactory::getApplication();
				$user = JEVHelper::getUser($attendee->user_id);
				if ($this->jevrreminders->remindUser($rsvpdata, $row, $user,$attendee->email_address))
				{
					$mainframe->enqueueMessage(JText::_("JEV_REMINDER_CONFIRMED"));
				}
			}
		}

		// send notifications ot new attendees
		if ($this->params->get("notifyuser", 0) && $atdee ==0 )
		{

			$user = JEVHelper::getUser($attendee->user_id);
			$username = $attendee->user_id > 0 ? $user->username : $attendee->email_address;
			$name = $attendee->user_id ? $user->name : $attendee->email_address;
			
			$datamodel = new JEventsDataModel();
			if ($rp_id > 0)
			{
				$row = $datamodel->queryModel->listEventsById($rp_id, 1, "icaldb");
			}
			else
			{
				$row = $datamodel->queryModel->getEventById($rsvpdata->ev_id, 1, "icaldb");
			}
			$this->notifyUser($rsvpdata, $row, $user, $name, $username, $attendee, 'ack', false);
		}
		$this->countAttendees($atd_id[0]);
		$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_ATTENDEE_SAVED"));

	}

	function changestate()
	{     
            $atd_id = JRequest::getVar("atd_id", "post", "array");
		if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATTENDEE"));
		}

		$repeating = JRequest::getInt("repeating", 0);

		$atdee = $cid[0];

		JTable::addIncludePath(RSVP_TABLES);
		$attendee =  JTable::getInstance('jev_attendee');

		if ($atdee > 0)
		{
			$attendee->load($atdee);
		}
		if (!$attendee)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		// get the view
		$this->view = $this->getView("attendees", "html");

		$this->view->assignRef("dataModel", $this->dataModel);

		$rp_id = $attendee->rp_id;

		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		if (!$rsvpdata)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}
		$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
		if ($rp_id == 0)
		{
			$repeat = $vevent->getFirstRepeat();
		}
		else
		{
			list($year, $month, $day) = JEVHelper::getYMD();
			$repeatdata = $this->dataModel->getEventData($rp_id, "icaldb", $year, $month, $day);
			if ($repeatdata && isset($repeatdata["row"]))
				$repeat = $repeatdata["row"];
		}

		$this->authoriseTransactions($repeat, $atdee);

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $rsvpdata);
		$registry->set("event", $repeat);

		$template = $rsvpdata->template;
		// New parameterised fields
                $eventrow = clone $repeat;
		if ($template != "")
		{
			$this->helper->attendee = $attendee;
			$this->helper->calculateBalances($this->view, $rsvpdata, $eventrow);
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				// transfer attendee specific information into the event row
				
				if (isset($attendee->params))
				{
					$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $eventrow);
					$feesAndBalances = $params->outstandingBalance($attendee);
				}
				else
				{
					$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
					$feesAndBalances = false;
                                 }
                        }
		}		
               $db->setQuery("SELECT * FROM #__jev_attendees where id=" . $atdee);
		//$attendee = $db->loadObject();
                if (floatval($feesAndBalances["feebalance"])<=0.001 ||!isset($feesAndBalances["feebalance"])){
                    $newstate = ($attendee->attendstate + 1) % 3;
                    $this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("JEV_ATTEND_STATE_CHANGED"));

               }
                else if ($attendee->attendstate==0){
                    $newstate = ($attendee->attendstate+4);
                    JFactory::getApplication()->enqueueMessage(JText::_("JEV_CANNOT_CONFIRM_ATTENDEE_WITH_OUTSTANDING_BALANCE"));
                    $this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("JEV_ATTEND_STATE_NOT_CHANGED"));
                    }
               else {
                   $newstate = ($attendee->attendstate + 1) % 3;
                    $this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("JEV_ATTEND_STATE_CHANGED"));
               }
		$db->setQuery("UPDATE #__jev_attendees set attendstate=$newstate where id=" . $atdee);
		$db->query();

		//$this->setRedirect("index.php?option=com_rsvppro&task=attendees.overview&atd_id[]=" . $atd_id[0] . "&repeating=" . $repeating . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("JEV_ATTEND_STATE_CHANGED"));

	}
	
	/**
	 * List Atendee transactions
	 *
	 */
	function transactions()
	{

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			$atdee = 0;
		}
		else
		{
			$atdee = $cid[0];
		}

		JTable::addIncludePath(RSVP_TABLES);
		$attendee =  JTable::getInstance('jev_attendee');

		if ($atdee > 0)
		{
			$attendee->load($atdee);
		}
		if (!$attendee)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		// get the view
		$this->view = $this->getView("attendees", "html");

		$this->view->assignRef("dataModel", $this->dataModel);

		$mainframe = JFactory::getApplication();
		$option = JRequest::getCmd("option");

		$rp_id = $attendee->rp_id;

		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		if (!$rsvpdata)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}
		$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
		if ($rp_id == 0)
		{
			$repeat = $vevent->getFirstRepeat();
		}
		else
		{
			list($year, $month, $day) = JEVHelper::getYMD();
			$repeatdata = $this->dataModel->getEventData($rp_id, "icaldb", $year, $month, $day);
			if ($repeatdata && isset($repeatdata["row"]))
				$repeat = $repeatdata["row"];
		}

		$this->authoriseTransactions($repeat, $atdee);

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $rsvpdata);
		$registry->set("event", $repeat);

		$template = $rsvpdata->template;
		// New parameterised fields
		if ($template != "")
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				// transfer attendee specific information into the event row
				$eventrow = clone $repeat;
				if (isset($attendee->params))
				{
					$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $eventrow);
					$feesAndBalances = $params->outstandingBalance($attendee);
				}
				else
				{
					$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
					$feesAndBalances = false;
				}
			}
		}
		else
		{
			// no template means no fees meand no transactions
			return;
		}

		$limit = intval($mainframe->getUserStateFromRequest("translistlimit", 'limit', JFactory::getApplication()->get("list_limit", 10)));
		$limitstart = intval($mainframe->getUserStateFromRequest("translimitstart", 'limitstart', 0));

		if ($feesAndBalances && isset($feesAndBalances['transactions']))
		{
			$rows = $feesAndBalances['transactions'];
		}
		else
		{
			$rows = array();
		}

		// attach repeat into row
		foreach ($rows as $key => $val)
		{
			$rows[$key]->eventrepeat = $repeat;
		}

		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/models/transactions.php");
		$model = new TransactionsModelTransactions();
		// clone so we can get ALL the transactions
		$tempAttendee = clone $attendee;
		$tempAttendee->_feedata = null;
		// get fees with no check on payment state!
		$model->getFeesPaid($tempAttendee, false);
		$transactions = $tempAttendee->_feedata;

		// Set the layout
		$this->view->setLayout('transactions');

		$this->view->assign('feesAndBalances', $feesAndBalances);
		$this->view->assign('attendee', $attendee);
		$this->view->assign('rp_id', $rp_id);
		$this->view->assign('transactions', $transactions);
		$this->view->assign('rsvpdata', $rsvpdata);
		$this->view->assign('event', $repeat);
		$this->view->assign('vevent', $vevent);
		$this->view->assign('repeat', $repeat);

		$this->view->display();

	}

	function edittransaction()
	{
		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			$cid = 0;
		}
		else
		{
			$cid = $cid[0];
		}
		require_once(RSVP_TABLES . "/transaction.php");
		$transaction = new rsvpTransaction();
		if ($cid > 0)
		{
			$transaction->load($cid);
		}
		if (!$transaction)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		$atdee_id = JRequest::getInt("atdee_id", 0);
		if ($atdee_id == 0)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		JTable::addIncludePath(RSVP_TABLES);
		$attendee =  JTable::getInstance('jev_attendee');

		if ($atdee_id > 0)
		{
			$attendee->load($atdee_id);
		}
		if (!$attendee)
		{
			JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
		}

		// get the view
		$this->view = $this->getView("attendees", "html");

		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . intval($attendee->at_id);
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		$rp_id = $attendee->rp_id;

		$this->dataModel = new JEventsDataModel();
		$this->queryModel = new JEventsDBModel($this->dataModel);

		// Find the first repeat
		$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
		if ($rp_id == 0)
		{
			$repeat = $vevent->getFirstRepeat();
		}
		else
		{
			list($year, $month, $day) = JEVHelper::getYMD();
			$repeatdata = $this->dataModel->getEventData(intval($rp_id), "icaldb", $year, $month, $day);
			if ($repeatdata && isset($repeatdata["row"]))
				$repeat = $repeatdata["row"];
		}

		$this->authoriseTransactions($repeat, $atdee_id);

		$template = $rsvpdata->template;
		// New parameterised fields
		if ($template != "")
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				// transfer attendee specific information into the event row
				$eventrow = clone $repeat;
				if (isset($attendee->params))
				{
					$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $eventrow);
					$feesAndBalances = $params->outstandingBalance($attendee);
				}
				else
				{
					$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
					$feesAndBalances = false;
				}
			}
		}
		else
		{
			// no template means no fees meand no transactions
			return;
		}

		// Set the layout
		$this->view->setLayout('edittransaction');

		$this->view->assign('feesAndBalances', $feesAndBalances);
		$this->view->assign('attendee', $attendee);
		$this->view->assign('transaction', $transaction);
		$this->view->assign('rsvpdata', $rsvpdata);
		$this->view->assign('template', $template);
		$this->view->assign('event', $repeat);
		$this->view->assign('vevent', $vevent);
		$this->view->assign('repeat', $repeat);

		$this->view->display();

	}

	function deletetransaction()
	{
		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		require_once(RSVP_TABLES . "/transaction.php");
		$transaction = new rsvpTransaction();
		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);

		foreach ($cid as $tid)
		{
			$transaction->load($tid);

			JTable::addIncludePath(RSVP_TABLES);
			$attendee =  JTable::getInstance('jev_attendee');
			$attendee->load($transaction->attendee_id);

			$this->authoriseTransactions(false, $attendee->id);

			if ($transaction)
			{
				// updates the attendee automatically
				$transaction->delete();
			}

			if ($attendee)
			{
				$this->countAttendees($attendee->at_id);
			}
		}

		$this->setRedirect("index.php?option=com_rsvppro&task=attendees.transactions&cid[]=" . JRequest::getInt("atdee_id", 0) . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_TRANSACTION_DELETED"));

	}

	function savetransaction()
	{
		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			$cid = 0;
		}
		else
		{
			$cid = $cid[0];
		}
		require_once(RSVP_TABLES . "/transaction.php");
		$transaction = new rsvpTransaction();
		if ($cid > 0)
		{
			$transaction->load($cid);
		}

		$transaction->bind(JRequest::get("post", JREQUEST_ALLOWHTML));

		JTable::addIncludePath(RSVP_TABLES);
		$attendee =  JTable::getInstance('jev_attendee');
		$attendee->load($transaction->attendee_id);
		$this->authoriseTransactions(false, $attendee->id);

		$transaction->store();

		if ($attendee)
		{
			$this->countAttendees($attendee->at_id);

			$this->notifyManualPayment($transaction, $attendee);
		}
		$this->setRedirect("index.php?option=com_rsvppro&task=attendees.transactions&cid[]=" . JRequest::getInt("attendee_id", 0) . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_TRANSACTION_SAVED"));

	}

	function resendtransactionnotice()
	{
		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			$cid = 0;
		}
		else
		{
			$cid = $cid[0];
		}
		require_once(RSVP_TABLES . "/transaction.php");
		$transaction = new rsvpTransaction();
		if ($cid > 0)
		{
			$transaction->load($cid);
		}
		else {
			echo JText::_("RSVP_NO_TRANSACTION_SELECTED");
			return;
		}

		$transaction->bind(JRequest::get("post", JREQUEST_ALLOWHTML));

		JTable::addIncludePath(RSVP_TABLES);
		$attendee =  JTable::getInstance('jev_attendee');
		$attendee->load($transaction->attendee_id);
		$this->authoriseTransactions(false, $attendee->id);

		if ($attendee)
		{
			// resend the notice regardless
			$this->notifyManualPayment($transaction, $attendee, true);
			$this->setRedirect("index.php?option=com_rsvppro&task=attendees.transactions&cid[]=" . JRequest::getInt("atdee_id", 0) . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_TRANSACTION_NOTICE_SENT"));
		}

	}

	private function notifyManualPayment($transaction, $attendee, $ignorestate = false)
	{
		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . intval($attendee->at_id);
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();

		$templateParams = RsvpHelper::getTemplateParams($rsvpdata);

		$class = "plgRsvppro" . ucfirst($transaction->gateway);
		$pluginpath = JPATH_SITE . "/plugins/rsvppro/$transaction->gateway/" ;
		JLoader::register($class, $pluginpath . $transaction->gateway . ".php");
		
		// if sending manual or paypal payment with notification enabled
		// either we ignore the payment state or the payment has been completed then send the message
		if ($ignorestate ||
				 ((is_callable($class."::NotifyPayment") && call_user_func($class."::NotifyPayment", $templateParams))
				&& $transaction->paymentstate == 1))
		{

			$rp_id = $attendee->rp_id;

			$this->dataModel = new JEventsDataModel();
			$this->queryModel = new JEventsDBModel($this->dataModel);

			// Find the first repeat
			$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
			if ($rp_id == 0)
			{
				$repeat = $vevent->getFirstRepeat();
			}
			else
			{
				list($year, $month, $day) = JEVHelper::getYMD();
				$repeatdata = $this->dataModel->getEventData(intval($rp_id), "icaldb", $year, $month, $day);
				if ($repeatdata && isset($repeatdata["row"]))
					$repeat = $repeatdata["row"];
			}

			JPluginHelper::importPlugin('jevents');
			JRequest::setVar("repeating",$rsvpdata->allrepeats);
			JRequest::setVar("atd_id",array($rsvpdata->id."|".$repeat->rp_id()));

			$dispatcher	= JDispatcher::getInstance();
			$dispatcher->trigger( 'onDisplayCustomFields', array( &$repeat) );
			
			$user = JEVHelper::getUser($attendee->user_id);
			if ($user->id == 0 && $this->params->get("attendemails", 0))
			{
				$name = $attendee->email_address;
				$username = $attendee->email_address;
			}
			else
			{
				$name = $user->name;
				$username = $user->username;
			}

			$messagetype =  "pplpay";
			if (is_callable($class."::PaymentMessageType")) {
				$messagetype = call_user_func($class."::PaymentMessageType");
			}
			$this->notifyUser($rsvpdata, $repeat, $user, $name, $username, $attendee, $messagetype, false, $transaction);
		}

	}

	function invalidtransaction()
	{
		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			$cid = 0;
		}
		else
		{
			$cid = $cid[0];
		}
		require_once(RSVP_TABLES . "/transaction.php");
		$transaction = new rsvpTransaction();
		if ($cid == 0)
		{
			JError::raiseError(403, "Missing Transaction Id");
		}

		$transaction->load($cid);
		$transaction->paymentstate = 0;

		JTable::addIncludePath(RSVP_TABLES);
		$attendee =  JTable::getInstance('jev_attendee');
		$attendee->load($transaction->attendee_id);
		$this->authoriseTransactions(false, $attendee->id);

		$transaction->store();

		if ($attendee)
		{
			$this->countAttendees($attendee->at_id);
			// I must reload the attendee since the payment state has changed!
			$attendee->load($transaction->attendee_id);

			$this->setRedirect("index.php?option=com_rsvppro&task=attendees.transactions&cid[]=" . $attendee->id . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_TRANSACTION_SAVED"));
		}
		else
		{
			$this->setRedirect("index.php?option=com_rsvppro&task=cpanel.cpanel" . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_TRANSACTION_SAVED"));
		}

	}

	function validtransaction()
	{
		// Check for request forgeries
		JRequest::checkToken('request') or jexit('Invalid Token');

		$cid = JRequest::getVar("cid", "post", "array");
		JArrayHelper::toInteger($cid);
		if (count($cid) == 0 || !isset($cid[0]) || $cid[0] < 0)
		{
			$cid = 0;
		}
		else
		{
			$cid = $cid[0];
		}
		require_once(RSVP_TABLES . "/transaction.php");
		$transaction = new rsvpTransaction();
		if ($cid == 0)
		{
			JError::raiseError(403, "Missing Transaction Id");
		}

		$transaction->load($cid);
		$transaction->paymentstate = 1;

		JTable::addIncludePath(RSVP_TABLES);
		$attendee =  JTable::getInstance('jev_attendee');
		$attendee->load($transaction->attendee_id);

		$this->authoriseTransactions(false, $attendee->id);

		$transaction->store();

		if ($attendee)
		{
			$this->countAttendees($attendee->at_id);
			// I must reload the attendee since the payment state has changed!
			$attendee->load($transaction->attendee_id);

			$this->notifyManualPayment($transaction, $attendee);

			$this->setRedirect("index.php?option=com_rsvppro&task=attendees.transactions&cid[]=" . $attendee->id . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_TRANSACTION_SAVED"));
		}
		else
		{
			$this->setRedirect("index.php?option=com_rsvppro&task=cpanel.cpanel" . "&Itemid=" . JRequest::getInt("Itemid", 0), JText::_("RSVP_TRANSACTION_SAVED"));
		}

	}

	protected function countAttendees($atdid=0)
	{
		return $this->helper->countAttendees($atdid);

	}

	public function updateWaitingList($rsvpdata, $atdid=0)
	{
		return $this->helper->updateWaitingList($rsvpdata, $atdid);

	}

	private function parseMessage($message, $rsvpdata, & $row, $name, $username, $attendee = null, $requirelogin = false, $onWaitingList = false, $transaction = false,$speciallink=false)
	{

		return $this->helper->parseMessage($message, $rsvpdata, $row, $name, $username, $attendee, $requirelogin, $onWaitingList, $transaction,$speciallink);

	}

	private function getMessageTemplate($params, $rsvpdata, $msgkey)
	{
		return $this->helper->getMessageTemplate($params, $rsvpdata, $msgkey);

	}

	// This redirects calls to the helper class is possible
	public function __call($name, $arguments)
	{
		if (is_callable(array($this->helper, $name)))
		{
			return call_user_func_array(array($this->helper, $name), $arguments);
		}

	}
	
	/*
	 * We cannot rely on __call magic function to pass variables by reference!
	 */
	private function notifyUser($rsvpdata, & $row, $user, $name, $username, $attendee = null, $messagetype = 'ack', $onWaitingList = false, $transaction = false, $subject = "", $message = "", $speciallink=false) {
		if (isset($this->event) && !isset($this->helper->event)){
			$this->helper->event = $this->event;
		}
		return $this->helper->notifyUser($rsvpdata,  $row, $user, $name, $username, $attendee, $messagetype, $onWaitingList, $transaction, $subject, $message, $speciallink);
	}

	private function notifyCreator($rsvpdata,& $row, $name, $username, $attendee = null, $cancellation = false, $onWaitingList = false) {
		if (isset($this->event) && !isset($this->helper->event)){
			$this->helper->event = $this->event;
		}
		return $this->helper->notifyCreator($rsvpdata, $row, $name, $username, $attendee, $cancellation, $onWaitingList) ;

	}


	public function calculateBalances(&$view, $rsvpdata, $row)
	{
		return $this->helper->calculateBalances($view, $rsvpdata, $row);

	}

	public function analyseGuests(&$attendee, $rsvpdata, $event)
	{
		return $this->helper->analyseGuests($attendee, $rsvpdata, $event);

	}

	protected function authoriseTransactions($repeat, $atdee)
	{
		if (!$repeat)
		{
			JTable::addIncludePath(RSVP_TABLES);
			$attendee =  JTable::getInstance('jev_attendee');
			$attendee->load($atdee);
			if (!$attendee || !$attendee->at_id)
			{
				$lang = JFactory::getLanguage();
				$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);
				JError::raiseError(403, JText::_("RSVP_NOT_AUTHORISED"));
			}
			$this->attendee = $attendee;
			$db = JFactory::getDBO();
			$sql = "SELECT * FROM #__jev_attendance WHERE id=" . intval($attendee->at_id);
			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();
			$this->rsvpdata  = $rsvpdata;
			$rp_id = $attendee->rp_id;

			$this->dataModel = new JEventsDataModel();
			$this->queryModel = new JEventsDBModel($this->dataModel);

			// Find the first repeat
			$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
			if ($rp_id == 0)
			{
				$repeat = $vevent->getFirstRepeat();
			}
			else
			{
				list($year, $month, $day) = JEVHelper::getYMD();
				$repeatdata = $this->dataModel->getEventData(intval($rp_id), "icaldb", $year, $month, $day);
				if ($repeatdata && isset($repeatdata["row"]))
					$repeat = $repeatdata["row"];
			}
		}

		if (!$repeat	) {
			$lang = JFactory::getLanguage();
			$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);
			JError::raiseError(403, JText::_("RSVP_NOT_AUTHORISED") . " xxx ");
		}

		// special case for QR Code did-attend check
		if (JRequest::getCmd("task")=="attendees.attend" || JRequest::getCmd("task")=="attendees.notattend") {
			$params = JComponentHelper::getParams("com_rsvppro");

			$user = JFactory::getUser();
			$userGroups = $user->getAuthorisedGroups();

			// is can approve ticket or can edit event allow through otherwise block here
			if (JEVHelper::isAdminUser($user) || array_intersect($params->get('approveticketlevel', array()), array_values($userGroups)) || JEVHelper::canEditEvent($repeat))
			{
				$gc = JRequest::getInt("gc");
				$securitycheck = md5($params->get("emailkey", "email key")." gwe did attend ".$atdee . "-" . $gc);
				$securitycheck2 = md5($params->get("emailkey", "email key")." attendee list ".$user->id);
				$sc = trim(JRequest::getString("sc"));

				if ($sc === $securitycheck || $sc === $securitycheck2 || JFactory::getApplication()->isAdmin()){
					$this->event = $repeat;
					return true;
				}
				else {
					$lang = JFactory::getLanguage();
					$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);
					echo "<div style='height:100%;width:100%;background-color:red;padding:200px 50px;'><H1 style='padding:100px 20px!important;background-color:white!important;color:red!important;'>".
					 JText::_("RSVP_NOT_AUTHORISED_FAILED_SECURITY_CHECK")
							. "</h1></div>";
					JRequest::setVar("tmpl","component");
					return false;

				}
			}
			else {
				if ($user->get("id",0)==0) {
					echo "<div style='height:100%;width:100%;background-color:red;padding:200px 50px;'><H1 style='padding:100px 20px!important;background-color:white!important;color:red!important;'>".
					 JText::_("RSVP_NOT_AUTHORISED_TO_VALIDATE_TICKET")
							. "</h1></div>";
					JRequest::setVar("tmpl","component");
					return false;
				}
				echo "<div style='height:100%;width:100%;background-color:red;padding:200px 50px;'><H1 style='padding:100px 20px!important;background-color:white!important;color:red!important;'>".
				 JText::_("RSVP_USER_NOT_AUTHORISED_TO_VALIDATE_TICKET")
						. "</h1></div>";
				JRequest::setVar("tmpl","component");
				return false;
				//JError::raiseError(403, JText::_("RSVP_NOT_AUTHORISED_TO_VALIDATE_TICKET")."  user= ". implode(",",array_values($userGroups))."  allowed= ". implode(",",$params->get('approveticketlevel', array())));
			}
		}
		if (!JEVHelper::canEditEvent($repeat))
		{
			$lang = JFactory::getLanguage();
			$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);
			JError::raiseError(403, JText::_("RSVP_NOT_AUTHORISED") );
		}
		$this->event = $repeat;
		return true;
	}

	protected function authoriseAttendee($rsvpdata)
	{
		$this->dataModel = new JEventsDataModel();
		$this->queryModel = new JEventsDBModel($this->dataModel);

		// Find the first repeat
		if (JRequest::getCmd("task")=="attendees.edit"){
			$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, true, "icaldb");
		}
		else {
			$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
		}
		$repeat = $vevent->getFirstRepeat();

		$this->event = $repeat;
		if (!$repeat || !JEVHelper::canEditEvent($repeat))
		{
			$lang = JFactory::getLanguage();
			$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);
			JError::raiseError(403, JText::_("RSVP_NOT_AUTHORISED"));
		}

	}

	// post confirmation actions in parameter fields e.g. signup for newsletters etc.
	protected function postUpdateActions($rsvpdata, $row, $attendee, $onWaitingList, $rsvpparams) {
		if ($rsvpparams) {
			return $rsvpparams->postUpdateActions($rsvpdata, $row, $attendee, $onWaitingList);
		}
	}

	protected function getRsvpDataParams($rsvpdata)
	{
		$params = "";
		if ($rsvpdata->template != "")
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				// Data now comes in via xmlfile field array
				$params = JRequest::getVar("xmlfile", JRequest::getVar("params", array(), "post"), "post");
				$jregistry = new JRegistry ();				
				$jregistry->loadArray($params);

				// checkboxes do not return any data which causes problems when we have guests!
				$rsvpparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, null);
				$emptyarray = $rsvpparams->renderToBasicArray();
				foreach ($emptyarray as $key=>$ea){
					if (!isset($params[$key])){
						$jregistry->set($key,"");
					}
				}
				
				// TODO VERY IMPORTANT - CALCULATE THE FEES AND BALANCE HERE - DON'T RELY ON JAVASCRIPT!!
				if ($jregistry->get("totalfee", -1) != -1)
				{
					$jregistry->set("totalfee", 0);
					$jregistry->set("feepaid", 7.00);
					$jregistry->set("feebalance", 0);
				}

				$params = $jregistry->toString();
			}
		}
		return $params;

	}
	
	protected function getRSVPParmeters($rsvpdata, $row) {
		if ($rsvpdata->template != "")
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				$rsvpparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, $row);
				return $rsvpparams;
			}
		}
		return false;	
	}
	
	
	
}
