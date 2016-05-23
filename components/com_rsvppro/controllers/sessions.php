<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

include_once(RSVP_ADMINPATH."/controllers/sessions.php");

class FrontSessionsController extends AdminSessionsController   {

	function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask( 'list',  'overview' );
		$this->registerDefaultTask("overview");

		// Load abstract "view" class
		$cfg = JEVConfig::getInstance();
		$theme = JEV_CommonFunctions::getJEventsViewName();
		JLoader::register('JEvents'.ucfirst($theme).'View',JEV_VIEWS."/$theme/abstract/abstract.php");
		
		// Load admin language file
		$lang = JFactory::getLanguage();
		$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);
	}

	// frontend version is not interested invitations initially
	function overview( )
	{

		$user = JFactory::getUser();

		// Fix attendee records for user who has registered in Joomla AFTER signing up for an event
		$db	= JFactory::getDBO();
		if ($user->id>0 && $user->email!=""){
			$db->setQuery("Update #__jev_attendees  set user_id = ".$user->id."  where email_address = ". $db->Quote($user->email));
			$db->query();
			$db->setQuery("Update #__jev_invitees set user_id = ".$user->id."  where email_address = ". $db->Quote($user->email));
			$db->query();
			$db->setQuery("Update #__jev_reminders set user_id = ".$user->id."  where email_address = ". $db->Quote($user->email));
			$db->query();
		}

		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);
		
		// get the view
		$this->view = $this->getView("sessions","html");

		$mainframe = JFactory::getApplication();
		$Itemid=JRequest::getInt("Itemid");
		$option = JRequest::getCmd("option");

		$search		= $mainframe->getUserStateFromRequest( "search{".RSVP_COM_COMPONENT."}", 'search', '' );
		$search		= $db->escape( trim( strtolower( $search ) ) );
		$limit		= intval( $mainframe->getUserStateFromRequest( "sessions.viewlistlimit", 'limit', 15 ));

		$searchattendees = $mainframe->getUserStateFromRequest("atsearchattendees{" . RSVP_COM_COMPONENT . "}", 'searchattendees', '');
		$searchattendees = $db->escape(trim(strtolower($searchattendees)));

		$searchinvitees = $mainframe->getUserStateFromRequest("atsearchinvitees{" . RSVP_COM_COMPONENT . "}", 'searchinvitees', '');
		$searchinvitees = $db->escape(trim(strtolower($searchinvitees)));

		// Block showing ALL 
		if ($limit==0 || $limit>100){
			$limit=100;
		}
		$limitstart = JRequest::getInt('limitstart', 0 );
		$created_by	=  intval($mainframe->getUserStateFromRequest( "createdbyrsvp", 'created_by', 0 ));
		$whichsessions = $params->get("whichsessions","SBD");
		$repeatingFilter = in_array($whichsessions, array("SBD","SO"))?1:0;
		$nonrepeating = intval($mainframe->getUserStateFromRequest( "repeatingrsvp", 'repeating', $repeatingFilter ));
		if ( in_array($whichsessions, array("RO","SO"))) {
			$nonrepeating = $repeatingFilter;
		}
		$hidepast = intval($mainframe->getUserStateFromRequest( "hidepastrsvp", 'hidepast', 1 ));

		$catid = intval(JFactory::getApplication()->getUserStateFromRequest("catidIcalEvents", 'catid', 0));
		$catidtop = $catid;

		$where = array();
		$join = array();

		if( $search ){
			$where[] = "LOWER(det.summary) LIKE '%$search%'";
		}

		if ($searchattendees) {
			  $where[] = "(atdees.email_address LIKE '%$searchattendees%' OR jua.username LIKE '%$searchattendees%' OR jua.email LIKE '%$searchattendees%'  OR atdees.id = '$searchattendees' )";
			  $join [] = " #__users AS jua ON jua.id = atdees.user_id"	;
		}

		if ($searchinvitees) {
			  $where[] = "(inv.email_address LIKE '%$searchinvitees%' OR jui.username LIKE '%$searchinvitees%' OR jui.email LIKE '%$searchinvitees%' )";
			  $join [] = " #__users AS jui ON jui.id = atdees.user_id"	;
		}
		
		if ($created_by!==0 ) {
			$where[] = "ev.created_by=".$db->Quote($created_by);
		}
		else if ( $params->get("personallist")==4) {
			$where[] = "ev.created_by=".$db->Quote($user->id);			
		}

		$where[] = "ev.ev_id IS NOT NULL";

		$user = JFactory::getUser();

		$jevparams =  JComponentHelper::getParams(JEV_COM_COMPONENT);

		if ($params->get("personallist") && $user->id>0){
			// yes, maybe, pending, unpaid or overpaid
			$approved = "attendstate IN (1,2,3,4,5) AND ";
			if ($params->get("personallist") ==2 || $params->get("personallist") ==3){
				// yes or pending payment
				$approved = "attendstate IN (1,4) AND ";
			}
			// attended by someone / anyone at all
			if ($params->get("personallist")==3){
				if ($nonrepeating){
					$where[] = "atd.id IN (SELECT distinct at_id FROM #__jev_attendees AS atdees WHERE $approved 1 )";
				}
				else {
					// for individual repeats
					$where[] = "rpt.rp_id IN (SELECT distinct rp_id FROM #__jev_attendees AS atdees WHERE $approved rp_id>0)";
				}				
			}
			else if ($params->get("personallist")<3) {
				if ($nonrepeating){
					$where[] = "atd.id IN (SELECT at_id FROM #__jev_attendees AS atdees WHERE $approved (user_id=".$user->id." OR email_address = ". $db->Quote($user->email)." ) )";
				}
				else {
					// for individual repeats
					$where[] = "rpt.rp_id IN (SELECT rp_id FROM #__jev_attendees AS atdees WHERE $approved (user_id=".$user->id." OR email_address = ". $db->Quote($user->email)." ) AND rp_id>0)";
				}
			}
		}
		if ($params->get("personallist") && $user->id==0){
			$where[] = '0';
		}
		
		$user = JFactory::getUser();
		// if user is not logged in then restrict to sessions they can see
		if ($user->get('id')==0 && !$params->get("attendemails")){
			$where[] = " 0";
		}

		// by invitation only
		if ($user->get('id')==0 ){
			$where[] = " atd.allowregistration=1";
		}
		// admin users can see all sessions!
		else if (!JEVHelper::isAdminUser ()) {
			$where[] = " ((atd.allowregistration=1) OR (atd.allowregistration=2 AND (inv2.id IS NOT NULL OR ev.created_by=".$user->get('id').")))";
			$join[] = "#__jev_invitees as inv2 ON inv2.at_id=atd.id AND inv2.user_id=".$user->get('id');
		}
		
		if (!JEVHelper::isAdminUser($user)){
			// we will strip these out at display time
			//$where[] = "(atd.showattendees=1 OR ev.created_by=".intval($user->id).")";
		}

		// get the total number of records in two blocks
		// first the whole event sessions
		if ($nonrepeating){
			if ($hidepast){
				if (class_exists("JevDate")) {
					$datenow = JevDate::getDate("-1 day");
				}
				else {
					$datenow = JFactory::getDate("-1 day");
				}
				$where[] = "\n (det.dtstart>'".$datenow->toUnix()."' OR atd.regclose>".$db->quote($datenow->toSql()).")";
			}

			$where[] = "atd.allrepeats=1 AND atd.allowregistration>0 ";
			$where[] = "ev.ev_id is not null ";
			// fix the access
			$where[] = "ev.access " . ' IN (' . JEVHelper::getAid($user) . ')' ;
			$where[] = "(atd.sessionaccess=-1 OR atd.sessionaccess " .  ' IN (' . JEVHelper::getAid($user) . ')' .")";
					
			$query = "SELECT count(distinct ev.ev_id)"
			. "\n FROM #__jevents_vevent AS ev "
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid=ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. "\n LEFT JOIN #__jev_invitees AS inv ON atd.id = inv.at_id  AND inv.rp_id=0"
			. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id AND atdees.rp_id=0"
			. "\n LEFT JOIN #__jev_attendeecount AS atdc ON atd.id = atdc.at_id AND atdc.rp_id=0"
			. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
			. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' );
			$db->setQuery( $query);
			//echo (string) $db->getQuery();
			$total = $db->loadResult();

			echo $db->getErrorMsg();
			if( $limit > $total ) {
				$limitstart = 0;
			}

			$filter_order		= $mainframe->getUserStateFromRequest( $option.'sess_filter_order',		'filter_order',		'det.dtstart',	'cmd' );
			if ($filter_order=='rpt.startrepeat' || $filter_order=='attendee' || strpos($filter_order, 'atdees.')) $filter_order='det.dtstart';
			$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'sess_filter_order_Dir',	'filter_order_Dir',	' ASC',				'word' );
			$orderby 	= ' ORDER BY '.$filter_order.' '.$filter_order_Dir;

			// WE CAN'T GET INIVTEE COUNT and waiting count at the same time !!!
			$query = "SELECT ev.ev_id as evd, ev.state as evstate, det.*, atd.* ,atd.id as atd_id, atdc.atdcount, atdc.gucount,
				count(distinct inv.id) as invcount,
				sum(atdees.guestcount) as waitingcount, det.dtstart as startdate"
			. ", 0 as rp_id "
			. "\n FROM #__jevents_vevent as ev "
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid=ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. "\n LEFT JOIN #__jev_invitees AS inv ON atd.id = inv.at_id  AND inv.rp_id=0"
			. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id AND atdees.rp_id=0"
			. "\n LEFT JOIN #__jev_attendeecount AS atdc ON atd.id = atdc.at_id AND atdc.rp_id=0"
			. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
			. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' )
			. "\n GROUP BY ev.ev_id $orderby"
			;

			if ($limit>0){
				$query .= "\n LIMIT $limitstart, $limit";
			}
			$db->setQuery( $query );
			//echo (string) $db->getQuery();
			$rows = $db->loadObjectList();

			echo $db->getErrorMsg();

			foreach ($rows as $key=>$val) {

				// Must refetch the waiting attendee count because its messed up when invitees are also present!
				$query = "SELECT sum(atdees.guestcount) as waitingcount "
				. "\n FROM #__jev_attendees AS atdees WHERE atdees.at_id = $val->atd_id AND atdees.rp_id=0 AND atdees.waiting=1"
				;
				$db->setQuery( $query );
				$waitingcount = $db->loadResult();
				echo $db->getErrorMsg();
				if (!is_null($waitingcount)) {
					$rows[$key]->waitingcount = $waitingcount;
				}
				else {
					$rows[$key]->waitingcount = 0;
				}

				// set state variable to the event value not the event detail value
				$rows[$key]->state = $rows[$key]->evstate;

				// Find the first repeat
				$vevent = $this->dataModel->queryModel->getEventById(  $val->ev_id, false, "icaldb");
				// trap orphans!
				if (is_null($vevent)) {
					unset($rows[$key]);
					continue;
				}
				$repeat = $vevent->getFirstRepeat();

				$rows[$key]->repeat = $repeat;
				$rows[$key]->starttime = $repeat->getUnixStartTime();
			}
			if ($total>count($rows) && $limit>$total){
				$total = count($rows);
			}
		}
		else {
			if ($hidepast){
				if (class_exists("JevDate")) {
					$datenow = JevDate::getDate("-1 day");
					$where[] = "\n rpt.endrepeat>'".$datenow->toMySQL()."'";
				}
				else {
					$datenow = JFactory::getDate("-1 day");
					$where[] = "\n rpt.endrepeat>'".$datenow->toSql()."'";
				}
			}


			$where[] = "atd.allrepeats=0 AND atd.allowregistration>0";
			// fix the access
			$where[] = "ev.access " .  ' IN (' . JEVHelper::getAid($user) . ')';
			$where[] = "(atd.sessionaccess=-1 OR atd.sessionaccess " . ' IN (' . JEVHelper::getAid($user) . ')' .")";

			$query = "SELECT count(distinct rpt.rp_id)"
			. "\n FROM #__jevents_vevent AS ev "
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid=ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. "\n LEFT JOIN #__jev_invitees AS inv ON atd.id = inv.at_id  AND inv.rp_id=rpt.rp_id"
			. "\n LEFT JOIN #__jev_attendeecount AS atdc ON atd.id = atdc.at_id AND atdc.rp_id=rpt.rp_id"
			. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id AND atdees.rp_id=rpt.rp_id"
			. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
			. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' );
			$db->setQuery( $query);
			//echo $db->_sql;
			$total = $db->loadResult();

			echo $db->getErrorMsg();
			if( $limit > $total ) {
				$limitstart = 0;
			}

			$filter_order		= $mainframe->getUserStateFromRequest( $option.'sess_filter_order',		'filter_order',		'rpt.startrepeat',	'cmd' );
			if ($filter_order=='det.dtstart' || $filter_order=='attendee' || strpos($filter_order, 'atdees.')) $filter_order='rpt.startrepeat';
			$filter_order_Dir	= $mainframe->getUserStateFromRequest( $option.'sess_filter_order_Dir',	'filter_order_Dir',	' ASC',				'word' );
			$orderby 	= ' ORDER BY '.$filter_order.' '.$filter_order_Dir;

			$query = "SELECT rpt.rp_id,  ev.state as evstate, det.*, atd.* ,atd.id as atd_id, atdc.atdcount, atdc.gucount,
				count(distinct inv.id) as invcount,
				sum(atdees.guestcount) as waitingcount,
				rpt.startrepeat as startdate"
			. ", rpt.rp_id "
			. "\n FROM #__jevents_vevent as ev "
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid=ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. "\n LEFT JOIN #__jev_invitees AS inv ON atd.id = inv.at_id  AND inv.rp_id=rpt.rp_id"
			. "\n LEFT JOIN #__jev_attendeecount AS atdc ON atd.id = atdc.at_id AND atdc.rp_id=rpt.rp_id"
			. "\n LEFT JOIN #__jev_attendees AS atdees ON atdees.at_id = atd.id AND atdees.rp_id=rpt.rp_id"
			. ( count( $join) ? "\n LEFT JOIN  " . implode( ' LEFT JOIN ', $join) : '' )
			. ( count( $where ) ? "\n WHERE " . implode( ' AND ', $where ) : '' )
			. "\n GROUP BY rpt.rp_id $orderby"
			;

			if ($limit>0){
				$query .= "\n LIMIT $limitstart, $limit";
			}
			$db->setQuery( $query );

			$rows = $db->loadObjectList();

			echo $db->getErrorMsg();

			foreach ($rows as $key=>$val) {
				// Must refetch the waiting attendee count because its messed up when invitees are also present!
				$query = "SELECT sum(atdees.guestcount) as waitingcount "
				. "\n FROM #__jev_attendees AS atdees WHERE atdees.at_id = $val->atd_id AND atdees.rp_id=$val->rp_id AND atdees.waiting=1"
				;
				$db->setQuery( $query );
				$waitingcount = $db->loadResult();
				echo $db->getErrorMsg();
				if (!is_null($waitingcount)) {
					$rows[$key]->waitingcount = $waitingcount;
				}

				// set state variable to the event value not the event detail value
				$rows[$key]->state = $rows[$key]->evstate;

				// Find the first repeat
				$repeat = $this->dataModel->queryModel->listEventsById(  $val->rp_id, false, "icaldb");
				// trap orphans!
				if (is_null($repeat)) {
					unset($rows[$key]);
					continue;
				}

				$rows[$key]->repeat = $repeat;
				$rows[$key]->starttime = $repeat->getUnixStartTime();
			}
			if ($total>count($rows)  && $limit>$total){
				$total = count($rows);
			}
		}

		// reset for any missing orphan rows
		$rows = array_values($rows);

		// get list of creators
		$sql = "SELECT distinct u.id, u.* FROM #__jevents_vevent as jev LEFT JOIN #__users as u on u.id=jev.created_by";
		$db= JFactory::getDBO();
		$db->setQuery( $sql );
		$users = $db->loadObjectList();
		$userOptions = array();
		$userOptions[] = JHtml::_('select.option', 0, JText::_("JEV_EVENT_CREATOR") );
		foreach( $users as $user )
		{
			$userOptions[] = JHtml::_('select.option', $user->id, $user->name. " ($user->username)" );
		}
		$userlist = JHtml::_('select.genericlist', $userOptions, 'created_by', 'class="inputbox" size="1"  onchange="document.adminForm.submit();"', 'value', 'text', $created_by);

		if ($whichsessions=="SBD" || $whichsessions=="RBD" ) {
			$options = array();
			$options[] = JHtml::_('select.option', '0', JText::_('RSVP_SEPARATE_REPEATS'));
			$options[] = JHtml::_('select.option', '1', JText::_('RSVP_SINGLE_EVENTS'));
			$repeattypelist = JHtml::_('select.genericlist', $options, 'repeating', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'value', 'text', $nonrepeating );
		}
		else {
			$repeattypelist = false;
		}

		$options = array();
		$options[] = JHtml::_('select.option', '0', JText::_('RSVP_NO'));
		$options[] = JHtml::_('select.option', '1', JText::_('RSVP_YES'));
		$hidepast = JHtml::_('select.genericlist', $options, 'hidepast', 'class="inputbox" size="1" onchange="document.adminForm.submit();"', 'value', 'text', $hidepast );

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $limitstart, $limit  );

		// Set the layout
		$this->view->setLayout('overview');

		$this->view->assign('rows',$rows);
		$this->view->assign('order',$filter_order);
		$this->view->assign('orderdir',$filter_order_Dir);
		$this->view->assign('userlist',$userlist);
		//$this->view->assign('catlist',$clist);
		$this->view->assign('hidepast',$hidepast);
		$this->view->assign('hidepast',$hidepast);
		$this->view->assign('nonrepeating',$nonrepeating);
		$this->view->assign('repeattypelist',$repeattypelist);
		$this->view->assign('search',$search);
		$this->view->assign('searchattendees',$searchattendees);
		$this->view->assign('searchinvitees',$searchinvitees);
		$this->view->assign('pageNav',$pageNav);

		$this->view->display();
	}
	
	function delete(){
		echo "Not implemented yet";return;
	}

}