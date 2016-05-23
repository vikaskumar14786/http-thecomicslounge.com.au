<?php

/**
 * Copyright (C)2010-2015 GWE Systems Ltd
 *
 * All rights reserved.
 *
 */
defined('_JEXEC') or die('No Direct Access');

/**
 * Attendstate explanations
 * 0 = not attending
 * 1 = attendingp
 * 2 = maybe attending
 * 3 = attending but subject to approval i.e.pending
 * 4 = attending but has outstanding balance to pay
 */

class RsvpAttendeeHelper
{

	private $params;
	private $jomsocial = false;

	public function __construct($params)
	{
		$this->params = $params;
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

	public function isAttending($rsvpdata, $row, $emailaddress = "", $anystatus = false)
	{

		// Cannot say if email based attendance is true so ignore this
		$user = JFactory::getUser();

		if ($user->id == 0 && !$this->params->get("attendemails", 0))
		{
			return false;
		}
		// if no email address then can't be an attendee
		if ($this->params->get("attendemails", 0) && $user->id == 0 && $emailaddress == "")
		{
			return false;
		}

		$db = JFactory::getDBO();
		if ($this->params->get("attendemails", 0) && $user->id == 0)
		{
			$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and email_address=" . $db->Quote($emailaddress);
		}
		else
		{
			if ($user->email){
				$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and (user_id=" . $user->id . " or email_address = ". $db->Quote($user->email). ")";
			}
			else if ($user->id>0) {
				$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and user_id=" . $user->id;
			}
			else {
				$sql = "SELECT * FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and email_address='something invalid'";
			}
		}
		if (!$rsvpdata->allrepeats)
		{
			$sql .= " AND rp_id=" . $row->rp_id();
		}
		$db->setQuery($sql);
		$this->attendee = $db->loadObject();

		// Fix attendee record for user who has registered in Joomla AFTER signing up for an event
		if ($this->attendee && $this->attendee->id && $this->attendee->user_id==0 &&  $user->id>0 && $this->attendee->email_address == $user->email ){
			$db->setQuery("Update #__jev_attendees  set user_id = ".$user->id."  where id=".$this->attendee->id);
			$db->query();
		}

		// Check user exists when logged in as unlogged in user
		static $checkeduser = false;
		if (!$checkeduser  && $this->attendee && $this->attendee->email_address && $user->id==0 && $emailaddress){
			$checkeduser = true;
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__users'));
			$query->where($db->quoteName('email') . ' = ' . $db->quote($this->attendee->email_address));
			$db->setQuery($query, 0, 1);
			$testuser = $db->loadObject();
			if ($testuser && $testuser->activation){
				if ($this->attendee->user_id==0) {
					$db->setQuery("Update #__jev_attendees  set user_id = ".$testuser->id."  where id=".$this->attendee->id);
					$db->query();
				}
				$lang = JFactory::getLanguage();
				$lang->load(RSVP_COM_COMPONENT, JPATH_ADMINISTRATOR);

				JFactory::getApplication()->enqueueMessage(JText::_("JEV_INACTIVE_USER_ACCOUNT_EXISTS_PLEASE_ACTIVATE"));
			}
		}

		$this->analyseGuests($this->attendee, $rsvpdata, $row);
		$this->countAttendees($rsvpdata->id);

		// attending or wit outstanding payment amounts
		if ($this->attendee && ($this->attendee->attendstate == 1 || $this->attendee->attendstate == 4 ) && $this->attendee->confirmed == 1)
		{
			return $this->attendee;
		}
		else if ($anystatus)
		{
			return $this->attendee;
		}
		else
		{
			return false;
		}

	}

	public function calculateBalances(&$view, $rsvpdata, $row)
	{
		// This MUST be called before renderToBasicArray to populate the balance fields - so we do it here to be safe
		if (isset($this->attendee) && isset($this->attendee->params))
		{

			// ensure plugins like virtuemart that don't have a universal notify mechanism are up to date!
			JPluginHelper::importPlugin("rsvppro");
			$dispatcher = JDispatcher::getInstance();
			$dispatcher->trigger('updatePaymentStatus', array($rsvpdata, $this->attendee, $row));

			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				$params = new JevRsvpParameter($this->attendee->params, $xmlfile, $rsvpdata, $row);
				$feesAndBalances = $params->outstandingBalance($this->attendee);
				$view->assignRef("attendeeParams", $params);
			}
		}

	}

	public function getEmailAddress($em = "em")
	{
		$emailaddress = "";
		if ($this->params->get("attendemails", 0))
		{
			$em = JRequest::getString($em, "");

			if ($em != "")
			{
				$emd = base64_decode($em);
				if (strpos($emd, ":") > 0)
				{
					list ( $emailaddress, $code ) = explode(":", $emd);
					if ($em != base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress)) &&
							$em != base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress . "invited")))
					{
						$emailaddress = "";
					}
				}
			}
		}
		return $emailaddress;

	}

	public function adminCancelAttendance($rsvpdata, $row)
	{
		$user = JFactory::getUser();

		$db = JFactory::getDBO();
		// admin/creator cancelling user?
		if ($user->id == $row->created_by() || JEVHelper::canPublishEvent($row))
		{
			$jevattend_id = JRequest::getInt("jevattendlist_id", 0);
			if ($jevattend_id > 0)
			{

				// auto remind attendees
				if ($this->params->get("autoremind", 0) == 1 && ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0)))
				{
					// cancel reminder
					$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $jevattend_id . " AND attendstate=1";
					$db->setQuery($sql);
					$attendee = $db->loadObject();
					$attendeeuser = JEVHelper::getUser($attendee->user_id);

					$this->jevrreminders->unremindUser($rsvpdata, $row, $user, $attendee->email_address);
				}

				if ($this->params->get("notifyadmincancelled", 0))
				{

					$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $jevattend_id;
					$db->setQuery($sql);
					$attendee = $db->loadObject();

					$attendeeuser = JEVHelper::getUser($attendee->user_id);
					if ($attendeeuser->id == 0 && $this->params->get("attendemails", 0))
					{
						$name = $attendee->email_address;
						$username = $attendee->email_address;
					}
					else
					{
						$name = $attendeeuser->name;
						$username = $attendeeuser->username;
					}
					$attendee->attendstate = 0;
					$this->notifyUser($rsvpdata, $row, $attendeeuser, $name, $username, $attendee, 'admincancelled', false);
				}

				$sql = "DELETE FROM #__jev_attendees WHERE id=" . $jevattend_id;
				$db->setQuery($sql);
				$db->query();

				$mainframe = JFactory::getApplication();
				$Itemid = JRequest::getInt("Itemid");
				//list ( $year, $month, $day ) = JEVHelper::getYMD();
				$year = $row->yup();
				$month = $row->mup();
				$day = $row->dup();
				$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);

				// Make sure the counts are in sync!
				$this->countAttendees($rsvpdata->id);

				$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_CANCELLED_BY_ADMIN"));
			}
		}

	}

	public function adminApproveAttendance($rsvpdata, $row)
	{
		$user = JFactory::getUser();

		// admin/creator cancelling user?
		if ($user->id == $row->created_by() || JEVHelper::canPublishEvent($row))
		{
			$jevattend_id = JRequest::getInt("jevattendlist_id_approve", 0);
			if ($jevattend_id > 0)
			{

				$db = JFactory::getDBO();
				$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $jevattend_id;
				$db->setQuery($sql);
				$attendee = $db->loadObject();

				if (!$attendee)
					return;
				// auto remind attendees
				if ($this->params->get("autoremind", 0) == 1 && ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0)))
				{
					// cancel reminder
					$user = JEVHelper::getUser($attendee->user_id);

					$this->jevrreminders->remindUser($rsvpdata, $row, $user, $attendee->email_address);
				}

				$db = JFactory::getDBO();
				$sql = "UPDATE #__jev_attendees SET attendstate=1, waiting=0 WHERE id=" . $attendee->id;

				$db->setQuery($sql);
				$db->query();

				$mainframe = JFactory::getApplication();
				$Itemid = JRequest::getInt("Itemid");
				//list ( $year, $month, $day ) = JEVHelper::getYMD();
				$year = $row->yup();
				$month = $row->mup();
				$day = $row->dup();
				$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);

				// Make sure the counts are in sync!
				$this->countAttendees($rsvpdata->id);

				// notify user?
				if ($this->params->get("notifyapproved", 0))
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
					$this->notifyUser($rsvpdata, $row, $user, $name, $username, $attendee, 'approve', $onWaitingList);
				}


				$cache =  JFactory::getCache('com_jevents');
				$cache->clean();

				$mainframe->redirect($link, JText::_("JEV_ATTENDANCE_APPROVED"));
			}
		}

	}

	public function notifyCreator($rsvpdata,& $row, $name, $username, $attendee = null, $cancellation = false, $onWaitingList = false)
	{
		$this->event = $row;
		$creator = JFactory::getUser(isset($this->event->created_by) ? $this->event->created_by : $this->event->created_by());
		if ($creator)
		{
			// if attendee then reload from database to make sure latest status is included in the message- if its a cancellation  then we've already forcd the new state in the active record
			if ($attendee && !$cancellation){
				$db = JFactory::getDbo();
				$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $attendee->id;
				$db->setQuery($sql);
				$newattendee = $db->loadObject();
				if ($newattendee){
					$attendee = $newattendee;
				}
				else {
					// attendee has been deleted so set the attendance state to not attending for the message
					$attendee->attendstate = 0;
				}
			}

			$Itemid = JRequest::getInt("Itemid");
			//list ( $year, $month, $day ) = JEVHelper::getYMD();
			$year = $row->yup();
			$month = $row->mup();
			$day = $row->dup();

			$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);
			$root = JURI::root();
			// NO sef since sub solder installs get the path twice

			$link = $root . $link. "&login=1";

			$templateParams  = RsvpHelper::getTemplateParams($rsvpdata);
			$comparams = JComponentHelper::getParams("com_rsvppro");
			$bcccreator = $comparams->get("bcccreator", "");
			$bcccreator = str_replace(" ", "", $bcccreator);
			if (strpos($bcccreator, ",")>0){
				$bcccreator = explode(",", $bcccreator);
			}

			$transaction = false;

			// Do not use the normal link for the event from parseMessage since it might include em=xxxxx
			$speciallink=$link;

			if ($cancellation)
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "cancelsubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "cancelmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
			}
			else
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "notifysubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "notifymessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
			}

			$this->sendMail($creator->email, $creator->name, $creator->email, $subject, $message, 1, null, $bcccreator, isset($rsvpdata->pdftickets)?$rsvpdata->pdftickets : null, null, null,true );
		}

	}

	public function notifyUser($rsvpdata, & $row, $user, $name, $username, $attendee = null, $messagetype = 'ack', $onWaitingList = false, $transaction = false, $subject = "", $message = "", $speciallink=false)
	{
		if (!isset($this->event)) {
			$this->event = $row;
		}

		$this->log("Notifying user" );
		$creator = JFactory::getUser(isset($this->event->created_by) ? $this->event->created_by : $this->event->created_by());
		if ($creator)
		{
			$this->log("Have creator " );
			// if attendee then reload from database to make sure latest status is included in the message
			if ($attendee){
				$this->log("Have attendee " );
				$db = JFactory::getDbo();
				$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $attendee->id;
				$db->setQuery($sql);
				$newattendee = $db->loadObject();
				if ($newattendee){
					if ($messagetype != 'admincancelled' && $messagetype != 'usercancel'){
						$attendee = $newattendee;
					}
					else {
						// attendee has been deleted so set the attendance state to not attending for the message
						$attendee->attendstate = 0;
					}
				}
				else {
					// attendee has been deleted so set the attendance state to not attending for the message
					$attendee->attendstate = 0;
				}
			}

			$Itemid = JRequest::getInt("Itemid");
			//list ( $year, $month, $day ) = JEVHelper::getYMD();
			$year = $row->yup();
			$month = $row->mup();
			$day = $row->dup();

			// calling RsvpHelper::getTemplateParams overwrites the global params with the values from the template e.g. BCC addresses etc,
			$templateParams  = RsvpHelper::getTemplateParams($rsvpdata);
			$comparams = JComponentHelper::getParams("com_rsvppro");

			$notifybcc = null;
			$notifybcc = $comparams->get("notifybcc", "");
			$notifybcc = str_replace(" ", "", $notifybcc);
			if (strpos($notifybcc, ",")>0){
				$notifybcc = explode(",", $notifybcc);
			}

			if ($messagetype == 'ack')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "acksubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "ackmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'approve')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "approvedsubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "approvedmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'admincancelled')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "admincancelsubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "admincancelmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if (strlen($messagetype )>3 && (strlen($messagetype ) ==strrpos($messagetype, "pay")+3 || strlen($messagetype ) ==strrpos($messagetype, "pay2")+4) )
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, $messagetype."subject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, $messagetype."message"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'usercancel')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "usercancelsubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "usercancelmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else if ($messagetype == 'cem')
			{
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "emailaddressconfirmationsubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction,$speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "emailaddressconfirmationmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction,$speciallink, false);
			}
			else if ($messagetype == 'waitupdate')
			{
				$this->messageIsWaitUpdate = true;
				$subject = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "waitingliststatussubject"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction,$speciallink, true);
				$message = $this->parseMessage($this->getMessageTemplate($this->params, $rsvpdata, "waitingliststatusmessage"), $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction,$speciallink,false);
				$this->messageIsWaitUpdate = false;
			}
			else if ($subject != "" && $message != "")
			{
				$subject = $this->parseMessage($subject, $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, true);
				$message = $this->parseMessage($message, $rsvpdata, $row, $name, $username, $attendee, true, $onWaitingList, $transaction, $speciallink, false);
			}
			else
			{
				$this->log("invalid messagetype " );
				return;
			}

			$this->log("Parsed and unsent message is $message" );

			// do not send empty messages
			if ($subject == "" && $message == "")
			{
				return;
			}

			if ($user->id > 0)
			{
				/*
				  if (JFactory::getUser()->id == 79)
				  {
				  JFactory::getApplication()->enqueueMessage("get BCC and send message to  " . $user->id);
				  }
				 */

				$bcc = $this->getBCC($user->id);
				if ($notifybcc) {
					if (is_string($notifybcc)){
						$notifybcc = array($notifybcc);
					}
					 if (!is_null($bcc)) {
						$notifybcc[]= $bcc;
					 }
				}
				else if (!is_null($bcc)) {
					$notifybcc = array($bcc);
				}
				/*
				  if (JFactory::getUser()->id == 79)
				  {
				  JFactory::getApplication()->enqueueMessage("got BCC of " . $bcc);
				  }
				 *
				 */
				$success = $this->sendMail($creator->email, $creator->name, $user->email, $subject, $message, 1, null, $notifybcc, isset($rsvpdata->pdftickets)?$rsvpdata->pdftickets : null);
				$this->log("Sent message to ".$user->email. " from ".$creator->email. " success = ".($success?"true":"false") );
			}
			else
			{
				$success = $this->sendMail($creator->email, $creator->name, $name, $subject, $message, 1, null, $notifybcc, isset($rsvpdata->pdftickets)?$rsvpdata->pdftickets : null);
				$this->log("Sent message to ".$name. " from ".$creator->email. " success = ".($success?"true":"false") );
			}
			$this->log($message);
		}

	}

	/*
	  public function updateWaitingList(&$rsvpdata, $atdid = 0)
	  {
	  $atdid = intval($atdid);
	  $db = JFactory::getDBO ();

	  if ($atdid == 0) {
	  // Now make sure the atdcount is in sync
	  $db->setQuery("SELECT atd.id FROM #__jev_attendance as atd");
	  $rows = $db->loadObjectList();

	  foreach ($rows as $row) {
	  if (intval($row->id) == 0)
	  continue;
	  $this->updateWaitingList($rsvpdata, $row->id);
	  }
	  } else if ($atdid > 0) {
	  // Now make sure the atdcount is in sync
	  $db->setQuery("SELECT atdees.id as atdee_id , atdees.*, atd.*, atdcnt.* FROM #__jev_attendance as atd
	  LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id
	  LEFT JOIN #__jev_attendeecount as atdcnt ON atdcnt.at_id=atd.id AND atdcnt.rp_id=atdees.rp_id
	  WHERE atd.id =" . intval($atdid) . " AND atdees.waiting=1 AND atdees.attendstate=1 ORDER BY atdees.id asc ");
	  $rows = $db->loadObjectList();
	  // may not have any attendees so get a null here
	  if (!$rows)
	  return;

	  $updatedCount = 0;
	  foreach ($rows as $row) {
	  if ($row->atdcount - count($rows) + $updatedCount >= $row->capacity)
	  break;
	  // TODO FIX THIS
	  $db->setQuery("UPDATE #__jev_attendees SET waiting=0 where id=" . $row->atdee_id);
	  if ($db->query()) {
	  $updatedCount++;

	  // auto remind attendees
	  if ($this->params->get("autoremind", 0) == 1 && $rsvpdata->allowreminders) {
	  // create reminder
	  // NB email address must be in the request object
	  $user = JEVHelper::getUser($row->user_id);
	  $this->jevrreminders->remindUser($rsvpdata, $row, $user);
	  $mainframe = JFactory::getApplication();
	  $mainframe->enqueueMessage(JText::_("JEV_REMINDER_CONFIRMED"));
	  }

	  if ($this->params->get("notifyuser", 0))
	  $this->notifyWaitingUser($row);
	  } else
	  echo $db->getErrorMsg();
	  }
	  }
	  }
	 */

	public function notifyWaitingUser($attendee)
	{

		// Fetch reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = JRegistry::getInstance("jevents");
		$rsvpdata = $registry->get("rsvpdata");
		$row = $registry->get("event");
		$user = JEVHelper::getUser($attendee->user_id );

		$Itemid = JRequest::getInt("Itemid");

		if ($attendee->user_id > 0)
		{
			$attendeeuser = JEVHelper::getUser($attendee->user_id );
			$name = $attendeeuser->name;
			$username = $attendeeuser->username;
		}
		else
		{
			$name = $attendee->email_address;
			$username = $attendee->email_address;
		}

		// get the true attendeem record
		$db = JFactory::getDbo();
		$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $attendee->atdee_id;
		$db->setQuery($sql);
		$newattendee = $db->loadObject();

		// not an iCalRepeat object to get the correct one
		if (isset($attendee->rp_id) && !isset($row->_rp_id)){
			$datamodel = new JEventsDataModel();
			if ($attendee->rp_id > 0)
			{
				$evt = $datamodel->queryModel->listEventsById($attendee->rp_id, 1, "icaldb");
			}
			else
			{
				$evt = $datamodel->queryModel->getEventById($rsvpdata->ev_id, 1, "icaldb");
			}
			$row=$evt;
		}

		$this->notifyUser($rsvpdata, $row, $user, $name, $username, $newattendee, 'waitupdate', $newattendee->waiting);

	}

	public function parseMessage($message, & $rsvpdata,& $row, $name, $username, $attendee = null, $requirelogin = false, $onWaitingList = false, $transaction = false,$speciallink=false, $issubject=false)
	{
		// Stop email cloaking in email messages
		$message ="{emailcloak=off}".$message;

		$cparams = JComponentHelper::getParams("com_rsvppro");

		// do we run through the jevents plugins
		if ($cparams->get("remindplugins", 0)) {
			JPluginHelper::importPlugin('jevents');
			$dispatcher	= JDispatcher::getInstance();
			JRequest::setVar("repeating",$rsvpdata->allrepeats);
			JRequest::setVar("atd_id",array($rsvpdata->id."|".$row->rp_id()));

			$dispatcher->trigger( 'onDisplayCustomFields', array( &$row) );
		}

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $rsvpdata);
		$registry->set("event", $row);

		// any balances outstanding?
		if ($attendee && isset($attendee->params))
		{
			if (!isset($attendee->outstandingBalances))
			{
				// ensure plugins like virtuemart that don't have a universal notify mechanism are up to date!
				JPluginHelper::importPlugin("rsvppro");
				$dispatcher = JDispatcher::getInstance();
				$dispatcher->trigger('updatePaymentStatus', array($rsvpdata, $attendee, $row));

				$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

				if (is_int($xmlfile) || file_exists($xmlfile))
				{
					$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $row);
					$feesAndBalances = $params->outstandingBalance($attendee);
				}
			}
			if (isset($attendee->outstandingBalances))
			{
				if ($attendee->outstandingBalances["feebalance"] > 0.000001)
				{
					$attendee->attendstate = 4;
				}
				else if ($attendee->outstandingBalances["feebalance"] <= 0 && $attendee->attendstate == 4)
				{
					$attendee->attendstate = 1;
				}
			}
		}

		if (isset($attendee->outstandingBalances) && isset($attendee->outstandingBalances['hasfees']) && $attendee->outstandingBalances['hasfees'])
		{
			$message = str_ireplace("{BALANCE}", RsvpHelper::phpMoneyFormat($attendee->outstandingBalances['feebalance']), $message);
			$message = str_ireplace("{FEESPAID}", RsvpHelper::phpMoneyFormat($attendee->outstandingBalances['feepaid']), $message);
			$message = str_ireplace("{TOTALFEES}", RsvpHelper::phpMoneyFormat($attendee->outstandingBalances['totalfee']), $message);
		}

		if (stripos($message, "{TICKETS}")!==false || stripos($message, "{PDFTICKETS}")!==false || stripos($message, "{ATTENDEESUMMARY}")!==false) {
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{
				$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $row);
				if (stripos($message, "{TICKETS}")!==false || stripos($message, "{PDFTICKETS}")!==false) {
					if ($params->ticket != "") {
						$ticket = $params->getTicket($attendee, $rsvpdata, $row);
						$message = str_ireplace("{TICKETS}",$ticket, $message);
						if ($ticket && stripos($message, "{PDFTICKETS}")!==false){
							// place pdf ticket in rsvpdata  record for use later
							$pdftickets = $this->generatePdfTicket($ticket, $attendee, $rsvpdata, $row);
							if ($pdftickets){
								$tmpdir= sys_get_temp_dir();
								if (file_put_contents($tmpdir."/tickets".$attendee->id.".pdf", $pdftickets)){
									$rsvpdata->pdftickets = $tmpdir."/tickets".$attendee->id.".pdf";
								}
							}
							else {
								$rsvpdata->pdftickets = JPATH_SITE."/components/com_rsvppro/pdftickets.pdf";
							}
							//$message = str_ireplace("{PDFTICKETS}",$rsvpdata->pdftickets, $message);
						}
					}
					else {
						$message = str_ireplace("{TICKETS}","", $message);
					}
					// Always replace PDFTICKETS with blank since the ticket is an attachement
					$message = str_ireplace("{PDFTICKETS}","" , $message);
				}
				if (stripos($message, "{ATTENDEESUMMARY}")!==false)
				{
					// This MUST be called before renderToBasicArray to populate the balance fields
					$feesAndBalances = isset($attendee->outstandingBalances) ? $attendee->outstandingBalances : false;
					$html = "";
					$paramsarray = $params->renderToBasicArray('xmlfile', $attendee);
					if (count($paramsarray) > 0)
					{
						// we have guests
						if ($attendee->guestcount > 0)
						{
							$html .= '<table class="attendeesummary" style="border:none;">';
							$html .= '<tr>';
							$html .= '<th />';
							$html .= '<th>' . JText::_('JEV_PRIMARY_ATTENDEE') . '</th>';
							for ($guest = 1; $guest < $attendee->guestcount; $guest++)
							{
								$html .= '<th>' . JText::sprintf("JEV_ATTENDEE_NUMBER", $guest + 1) . '</th>';
							}
							$html .= '</tr>';
							foreach ($paramsarray as $param)
							{
								if ($param['formonly'] || !$param['showindetail'] || !$param["accessible"])
								{
									continue;
								}
								if ($param['peruser'] == 2 && $attendee->guestcount <= 1)
									continue;
								// if group conditional then skip altogether
								if ($param['peruser'] == 0 && isset($param['conditionalfield']) && $param['conditionalfield'] != "")
								{
									if (isset($paramsarray[$param['conditionalfield']]) && $paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
									{
										continue;
									}
								}
								// Make sure balance info output is displayed correctly
								if ($param["type"]=="jevrbalance" && $feesAndBalances){
									$param["value"]=RsvpHelper::phpMoneyFormat($feesAndBalances[$param["name"]]);
								}
								// if not group conditional but no matches then skip too
								if ($param['peruser'] > 0 && isset($param['conditionalfield']) && $param['conditionalfield'] != "")
								{
									if (isset($paramsarray[$param['conditionalfield']]))
									{
										if (is_array($paramsarray[$param['conditionalfield']]['rawvalue']))
										{
											$showany = false;
											foreach ($paramsarray[$param['conditionalfield']]['rawvalue'] as $rawvalue)
											{
												if ($rawvalue == $param['conditionalfieldvalue'])
												{
													$showany = true;
												}
											}
											if (!$showany)
											{
												continue;
											}
										}
										else if ($paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
										{
											continue;
										}
									}
								}

								$html .= "<tr>";
								$html .='<td class="rsvpoptionlabel">' . stripslashes(RsvpHelper::translate($param['label'])) . ' : </td>';
								if (!isset($param['peruser']) || $param['peruser'] <= 0)
								{
									$val = $param['value'];
									if (is_array($val))
									{
										$val = implode(",", $val);
									}
									$val = stripslashes($val);
									$html .='<td class="rsvpgroupoptionvalue" colspan="' . $attendee->guestcount . '">' . $val . '</td>';
								}
								else
								{

									// fix non-array values
									// TODO find why this is happening on pistol site
									if (!is_array($param['value']) && $attendee->guestcount==1){
										$param['value'] = array($param['value']);
									}
									if (is_array($param['value']) && count($param['value']) == $attendee->guestcount)
									{
										for ($guest = 0; $guest < $attendee->guestcount; $guest++)
										{

											if ($param['peruser'] == 2 && $guest == 0)
											{
												$html .='<td class="rsvpnaoption"></td>';
											}
											else
											{
												$val = $param['value'][$guest];
												if (is_array($val))
												{
													$val = implode(",", $val);
												}
												$val = stripslashes($val);

												// should we skip this output because its a conditional field
												if (isset($param['conditionalfield']) && $param['conditionalfield'] != "" && isset($paramsarray[$param['conditionalfield']]))
												{
													if (is_array($paramsarray[$param['conditionalfield']]['rawvalue']) )
													{
														if (isset($paramsarray[$param['conditionalfield']]['rawvalue'][$guest]) && $paramsarray[$param['conditionalfield']]['rawvalue'][$guest] != $param['conditionalfieldvalue'])
														{
															$val = "";
														}
													}
													else if ($paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
													{
														$val = "";
													}
												}


												$html .='<td class="rsvpuseroptionvalue">' . $val . '</td>';
											}
										}
									}
									else
									{
										// WE HAVE A PROBLEM
										$html .='<td class="rsvpproblemvalue" colspan="' . $attendee->guestcount . '">' . JText::_('JEV_PROBLEM_CONTACT_ORGANISER') . '</td>';
									}
								}
								$html .= "</tr>";
							}

							$html .= '</table>';
						}
						// its just the one!
						else
						{
							foreach ($paramsarray as $param)
							{
								if ($param['formonly'])
								{
									continue;
								}
								// if group conditional then skip altogether
								if ($param['peruser'] == 0 && isset($param['conditionalfield']) && $param['conditionalfield'] != "")
								{
									if (isset($paramsarray[$param['conditionalfield']]) && $paramsarray[$param['conditionalfield']]['rawvalue'] != $param['conditionalfieldvalue'])
									{
										continue;
									}
								}

								$html .='<span class="rsvpoptionlabel">' . stripslashes(RsvpHelper::translate($param['label'])) . ' : </span>';
								if (is_array($param['value']))
								{
									$values = $param['value'];
									$html .='<span class="rsvpoptionvalue">' . stripslashes(implode(",", $values)) . '</span>';
								}
								else
								{
									$html .='<span class="rsvpoptionvalue">' . stripslashes($param['value']) . '</span>';
								}
								$html .= "<br/>";
							}
						}
					}

					$message = str_ireplace("{ATTENDEESUMMARY}", $html , $message);
				}
			}

		}

		$attendstate = array(JText::_('RSVP_NOT_ATTENDING'), JText::_('RSVP_ATTENDING'), JText::_('RSVP_MAYBE_ATTENDING'), JText::_('RSVP_PENDING_APPROVAL'), JText::_('RSVP_OUTSTANDING_BALANCE'),JText::_("JEV_WAITING_MESSAGE"));
		$confirmedstate = array(JText::_('RSVP_USER_NOT_CONFIRMED'), JText::_('RSVP_USER_CONFIRMED'));

		$user = JFactory::getUser();
		if (!is_null($attendee))
		{
			if (isset($attendee->username))
			{
				$message = str_replace("{USERNAME}", $attendee->username, $message);
			}
			else if (isset($attendee->user_id))
			{
				$user = JEVHelper::getUser($attendee->user_id);
				$message = str_replace("{USERNAME}", $user->username, $message);
			}
			else
			{
				$message = str_replace("{USERNAME}", $name, $message);
			}
			if (isset($attendee->email_address) && $attendee->email_address != "")
			{
				$message = str_replace("{EMAIL}", $attendee->email_address, $message);
			}
			else if (isset($attendee->user_id))
			{
				$user = JEVHelper::getUser($attendee->user_id);
				$message = str_replace("{EMAIL}", $user->email, $message);
			}
			else
			{
				$message = str_replace("{EMAIL}", $name, $message);
			}

			if ($attendee->waiting) {
				$attendee->attendstate=5;
			}
			$message = str_replace("{ATTENDSTATE}", $attendstate[$attendee->attendstate], $message);
			$message = str_replace("{CONFIRMED}", $confirmedstate[$attendee->confirmed], $message);

			$regex = "#{REGID}(.*?){/REGID}#s";
			preg_match($regex, $message, $matches);
			if (count($matches) == 2)
			{
				$message = preg_replace($regex, sprintf($matches[1], $attendee->id), $message);
			}
			$regex = "#{REGDATE}(.*?){/REGDATE}#s";
			preg_match($regex, $message, $matches);
			if (count($matches) == 2)
			{
				$date = new JevDate($attendee->created);
				$message = preg_replace($regex, $date->toFormat($matches[1]), $message);
			}

		}
		else
		{
			$message = str_replace("{USERNAME}", $name, $message);
			$message = str_replace("{ATTENDSTATE}", "?", $message);
			$message = str_replace("{EMAIL}", "", $message);
			$message = str_replace("{CONFIRMED}", "?", $message);
		}
		$message = str_replace("{EVENT}", $row->title(), $message);

		$message = str_replace("{LOCATION}", $row->location(), $message);
		$message = str_replace("{CATEGORY}", $row->catname(),$message);

		if ($row->created_by() > 0)
		{
			$creator = JEVHelper::getUser($row->created_by());
			$creator_email = $creator->email;
			$creator = $creator->name;
		}
		else
		{
			$db = JFactory::getDBO();
			$db->setQuery("SELECT * FROM #__jev_anoncreator where ev_id=" . intval($row->ev_id()));
			$anonrow = @$db->loadObject();
			if ($anonrow)
			{
				$creator = $anonrow->name;
				$creator_email = $anonrow->email;
			}
			else
			{
				$creator = "unknown";
			}
		}

		$message = str_replace("{CREATOR}", $creator, $message);
		$message = str_replace("{CREATOR_EMAIL}", $creator_email, $message);


		// this is needed for the repeat summary to work
		$event_up = new JEventDate($row->publish_up());
		$row->start_date = JEventsHTML::getDateFormat($event_up->year, $event_up->month, $event_up->day, 0);
		$row->start_time = JEVHelper::getTime($row->getUnixStartTime());
		$event_down = new JEventDate($row->publish_down());
		$row->stop_date = JEventsHTML::getDateFormat($event_down->year, $event_down->month, $event_down->day, 0);
		$row->stop_time = JEVHelper::getTime($row->getUnixEndTime());
		$row->stop_time_midnightFix = $row->stop_time;
		$row->stop_date_midnightFix = $row->stop_date;
		if ($event_down->second == 59)
		{
			$row->stop_time_midnightFix = JEVHelper::getTime($row->getUnixEndTime() + 1);
			$row->stop_date_midnightFix = JEventsHTML::getDateFormat($event_down->year, $event_down->month, $event_down->day + 1, 0);
		}

		$message = str_replace("{REPEATSUMMARY}", $row->repeatSummary(), $message);

		if (!$onWaitingList)
		{
			if ($rsvpdata->waitingcapacity>0 && isset($this->messageIsWaitUpdate) && $this->messageIsWaitUpdate) {
				$message = str_replace("{WAITINGMESSAGE}",  JText::_("JEV_NOT_WAITING_MESSAGE"), $message);
			}
			else {
				$message = str_replace("{WAITINGMESSAGE}",  "", $message);
			}
		}
		else
		{
			// I can't do this since it would add this to the subject line too!
			if (!$issubject){
				if (strpos($message, "{WAITINGMESSAGE}") === false)
				{
					//$message .= "<br>" . JText::_("JEV_WAITING_MESSAGE");
				}
				else
				{
				$message = str_replace("{WAITINGMESSAGE}", JText::_("JEV_WAITING_MESSAGE"), $message);
				}
			}
		}

		$regex = "#{DATE}(.*?){/DATE}#s";
		$matches = array();
		preg_match($regex, $message, $matches);
		if (count($matches) == 2)
		{
			jimport('joomla.utilities.date');
			$date = new JevDate($row->getUnixStartTime());
			$message = preg_replace($regex, $date->toFormat($matches [1]), $message);
		}

		$regex = "#{TIME}(.*?){/TIME}#s";
		$matches = array();
		preg_match($regex, $message, $matches);
		if (count($matches) == 2)
		{
			jimport('joomla.utilities.date');
			$date = new JevDate($row->getUnixStartTime());
			$message = preg_replace($regex, $date->toFormat($matches [1]), $message);
		}

		$regex = "#{ENDTIME}(.*?){/ENDTIME}#s";
		$matches = array();
		preg_match($regex, $message, $matches);
		if (count($matches) == 2)
		{
			jimport('joomla.utilities.date');
			$date = new JevDate($row->getUnixEndTime());
			$message = preg_replace($regex, $date->toFormat($matches [1]), $message);
		}


		$regex = "#{TRANSACTIONID}(.*?){/TRANSACTIONID}#s";
		$matches = array();
		preg_match($regex, $message, $matches);
		if (count($matches) == 2 && $transaction)
		{
			$message = preg_replace($regex, sprintf($matches [1], $transaction->transaction_id), $message);
		}

		// New parameterised fields
		if ($rsvpdata->template != "" && !is_null($attendee))
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if (is_int($xmlfile) || file_exists($xmlfile))
			{

				$html = "<table><tr>";
				$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $row);
				$params = $params->renderToBasicArray();
				foreach ($params as $param)
				{
					$html .= '<th>' . JText::_($param ['label']) . '</th>';
				}

				$html .= '</tr>';

				$html .= '<tr>';

				$rowspan = $attendee->guestcount > 0 ? " rowspan='" . $attendee->guestcount . "' " : "";
				// in this scenario we don't need the rowspan!
				if ($user->id != $row->created_by() && !JEVHelper::isAdminUser($user) && !JEVHelper::canDeleteEvent($row, $user) && !$this->params->get("showcf", 0))
				{
					$rowspan = "";
				}

				if (isset($attendee->params))
				{
					$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $row);
					$feesAndBalances = $params->outstandingBalance($attendee);
				}
				else
				{
					$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $row);
				}

				$params = $params->renderToBasicArray(null, $attendee);

				$message = preg_replace("/({[^}]*:label:)/U", '{fieldlabel:', $message);
				$message = preg_replace("/({[^}]*:field:)/U", '{field:', $message);

				foreach ($params as $param)
				{

					$value = $param["value"];
					$label = $param["label"];
					$fieldname = "name";

					// is this a name field?  If so then use this in preference to the user's profile name
					if (isset($param["isname"]) && $param["isname"])
					{
						if (is_array($value) && $value[0] != "")
						{
							$name = $value[0];
						}
						else if (!is_array($value) && $value != "")
						{
							$name = $value;
						}
					}

					$html .= '<td>' . (is_array($param ['value'])?implode(", ", $param ['value']):$param ['value'] ) . '</td>';


					if ($label != "")
					{
						// must handle labels with special symbols in them
						$message = preg_replace("/{fieldlabel:([^#{}]*?)#" . $param[$fieldname] . "#([^#{}]*?)}/", '${1}rrTTqqZZ123$2', $message);
						//$message = preg_replace("/{fieldlabel:(.*)#" . $param[$fieldname] . "#(.*)}/U", '${1}rrTTqqZZ123$2', $message);
						$message = str_replace("rrTTqqZZ123",$label,$message);
					}
					else
					{
						$message = preg_replace("/{fieldlabel:.*#" . $param[$fieldname] . "#.*}/U", '', $message);
					}
					if ($value != "")
					{
						if (is_array($value))
						{
							// guest only fields should not have a comma at the start
							if ($param["peruser"] == 2 && count($value) >= 2)
							{
								array_shift($value);
							}
							$value = implode(", ", $value);
						}
						// must handle values with special symbols in them
						$message = preg_replace("/{field:([^#{}]*?)#" . $param[$fieldname] . "#([^#{}]*?)}/", '${1}rrTTqqZZ123$2', $message);
						//$message = preg_replace("/{field:(.*)#" . $param[$fieldname] . "#(.*)}/U", '${1}rrTTqqZZ123$2', $message);
						$message = str_replace("rrTTqqZZ123",$value,$message);
					}
					else
					{
						$message = preg_replace("/{field:.*#" . $param[$fieldname] . "#.*}/U", '', $message);
					}

					// total fees etc. are parameterised differently and so we need to check the fieldname field too!
					if (isset($param["fieldname"]) && $param["fieldname"] != $param["name"])
					{
						$fieldname = "fieldname";
						if ($label != "")
						{
							// must handle labels with special symbols in them
							$message = preg_replace("/{fieldlabel:(.*)#" . $param[$fieldname] . "#(.*)}/U", '${1}rrTTqqZZ123$2', $message);
							$message = str_replace("rrTTqqZZ123",$label,$message);
						}
						else
						{
							$message = preg_replace("/{fieldlabel:.*#" . $param[$fieldname] . "#.*}/U", '', $message);
						}
						if ($value != "")
						{
							if (is_array($value))
							{
								// guest only fields should not have a comma at the start
								if ($param["peruser"] == 2 && count($value) >= 2)
								{
									array_shift($value);
								}
								$value = implode(", ", $value);
							}
							// There was a server that didn't match with preg_replace !!!
							$message = str_replace("{field:#" . $param[$fieldname] . "#}", $value, $message);
							// must handle values with special symbols in them
							$message = preg_replace("/{field:(.*)#" . $param[$fieldname] . "#(.*)}/U", '${1}rrTTqqZZ123$2', $message);
							$message = str_replace("rrTTqqZZ123",$value,$message);
						}
						else
						{
							$message = preg_replace("/{field:.*#" . $param[$fieldname] . "#.*}/U", '', $message);
						}
					}
				}

				$html .= '</tr><table>';

				$message = str_replace("{CUSTOM}", $html, $message);
			}
		}

		// do the name LATE so we can change the name field value from the parameters.
		$message = str_replace("{NAME}", $name, $message);

		$regex = "#{LINK}(.*?){/LINK}#s";
		preg_match($regex, $message, $matches);
		if (count($matches) == 2 || strpos($message, "{RAWLINK}") !== false)
		{
			$Itemid = JRequest::getInt("Itemid");
			//list ( $year, $month, $day ) = JEVHelper::getYMD();
			$year = $row->yup();
			$month = $row->mup();
			$day = $row->dup();
			$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);

			if (isset($attendee) && isset($attendee->email_address) && $attendee->email_address != "" && $attendee->user_id == 0)
			{
				$emailaddress = $attendee->email_address;
			}
			else
			{
				$emailaddress = $this->getEmailAddress();
			}
			if ($this->params->get("attendemails", 0) && $emailaddress != "")
			{
				$code = base64_encode($emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $emailaddress));
				$link = $row->viewDetailLink($year, $month, $day, false, $Itemid) . "&em=" . $code;
				$requirelogin = false;
			}

			if ($requirelogin)
			{
				$link .= "&login=1";
			}

			// do not SEF since root() and sef add the path!
			$root = JURI::root();
			$link = $root . $link;

			if ($speciallink){
				$link = $speciallink;
			}

			if (count($matches) == 2) {
				$message = preg_replace($regex, "<a href='$link'>" . $matches [1] . "</a>", $message);
			}
			if ( strpos($message, "{RAWLINK}") !== false) {
				$message =  str_replace("{RAWLINK}", $link, $message);
			}

		}
        $regex = "#{ICALLINK}(.*?){/ICALLINK}#s";
        preg_match($regex, $message, $matches);
        if (count($matches) == 2 || strpos($message, "{ICALRAWLINK}") !== false) {

            $root = JURI::root();

            //Ok now included, lets replace the tag
            $jevparams = JComponentHelper::getParams("com_jevents");
	   if ($jevparams->get("icalformatted", 1) == 1){
		   $icf =   "&icf=1";
	   }
	   else {
		   $icf =   "";
	   }
            if ($row->hasRepetition()) {
                $link = $root.$row->vCalExportLink(false, false).$icf;
            } else {
                $link = $root.$row->vCalExportLink(false,true).$icf;
            }
	    if (JComponentHelper::getParams("com_jevents")->get("icalformatted", 0)) {
		$link .= "&icf=1";
	   }
            if (count($matches) == 2) {
                $message = preg_replace($regex, "<a href='$link' target='_blank'>" . $matches [1] . "</a>", $message);
            }

            if ( strpos($message, "{ICALRAWLINK}") !== false) {
                $message =  str_replace("{ICALRAWLINK}", $link, $message);
            }
        }

		if ($transaction)
		{
			$regex = "#{TIMEPAID}(.*?){/TIMEPAID}#s";
			$matches = array();
			preg_match($regex, $message, $matches);
			if (count($matches) == 2)
			{
				jimport('joomla.utilities.date');
				$date = new JevDate("+0 second");
				$message = preg_replace($regex, $date->toFormat($matches [1]), $message);
			}

			//{AMOUNTPAID}
			// The plugin should not need to be loaded since its only ever used if there is a valid transaction in which case we have a real template to fall back on!
			$plugin = JPluginHelper::getPlugin("rsvppro", "manual");
			if (!$plugin)
			{
				$plugin = JPluginHelper::getPlugin("rsvppro", "paypalipn");
				if (!$plugin)
				{
					$plugin = JPluginHelper::getPlugin("rsvppro", "virtuemart");
				}
			}
			$params = new JRegistry($plugin ? $plugin->params : null);
			$comparams = JComponentHelper::getParams("com_rsvppro");

			$currency = $comparams->get("Defaultcurrency", "USD");

			if (isset($rsvpdata->template) && is_numeric($rsvpdata->template))
			{
				$db = JFactory::getDBO();
				$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($rsvpdata->template));
				$templateParams = $db->loadObject();
				if ($templateParams)
				{
					$templateParams = json_decode($templateParams->params);
				}
				else
				{
					$templateParams = $params;
				}
			}
			else
			{
				$templateParams = $params;
			}

			$message = str_replace("{AMOUNTPAID}", RsvpHelper::phpNewMoneyFormat($transaction->amount, $templateParams), $message);
		}

		if (false)
		{
			echo "Itemid = " . JRequest::getVar("Itemid", "not set");
			echo "<br/>";
			var_dump($row);
			exit();
		}

		include_once(JPATH_SITE . "/components/com_jevents/views/default/helpers/defaultloadedfromtemplate.php");
		ob_start();
		DefaultLoadedFromTemplate(false, false, $row, 0, $message);
		$newmessage = ob_get_clean();
		if ($newmessage != "" && strpos($newmessage, "<script ")===false)
		{
			$message = $newmessage;
		}

		// Make URLs absolute
		$uri =  JURI::getInstance(JURI::base());
		$base = $uri->toString(array('scheme', 'host', 'port'))."/";

		$message = str_replace("href='index.php", "href='".$base."index.php", $message);
		$message = str_replace("href='/", "href='".$base."/", $message);
		$message = str_replace('href="index.php', 'href="'.$base.'index.php', $message);
		$message = str_replace('href="/', 'href="'.$base.'/', $message);
		$message = str_replace("src='index.php", "src='".$base."index.php", $message);
		$message = str_replace("src='/", "src='".$base."/", $message);
		$message = str_replace('src="index.php', 'src="'.$base.'index.php', $message);
		$message = str_replace('src="/', 'src="'.$base.'/', $message);

		// Stop email cloaking in email messages
		$message =str_replace(array("{emailcloak=off}", "{* emailcloak=off}"), "", $message);

		return $message;

	}

	public function analyseGuests(&$attendee, $rsvpdata, $event, $attendeeparams = false)
	{
		if (is_null($attendee) || empty($attendee))
		{
			return;
		}
		if (!isset($attendee->guestcount))
		{
			$attendee->guestcount = 1;
		}

		if ($rsvpdata->template != "")
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

			if ((is_int($xmlfile) || file_exists($xmlfile)) && ($attendee->lockedtemplate == 0 || $attendee->lockedtemplate == $xmlfile))
			{
				if (!$attendeeparams)
				{
					if (isset($attendee->params))
					{
						$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $event);
						// get fees and balances to ensure we can reuse the cache from  renderToBasicArray
						$feesAndBalances = $params->outstandingBalance($attendee);
					}
					else
					{
						// if we don't have an attendee we should not be here!
						return;
					}

					$countAttendeeAndGuests = 1;
					$paramsarray = $params->renderToBasicArray(null, $attendee);
				}
				else
				{
					$paramsarray = $attendeeparams;
					$countAttendeeAndGuests = 1;
				}

				$allFormOnly =true;
				if (count($paramsarray) > 0)
				{
					foreach ($paramsarray as $param)
					{
						if (!$param['formonly'] && $param['peruser']>0) {
							$allFormOnly = false;
						}
						if ($param['formonly'] || !isset($param['value']) || !isset($param['peruser']) || $param['peruser'] <= 0)
						{
							continue;
						}
						if (is_array($param['value']))
						{
							$countAttendeeAndGuests = count($param['value']) > $countAttendeeAndGuests ? count($param['value']) : $countAttendeeAndGuests;
						}
					}
				}

				if ($countAttendeeAndGuests != $attendee->guestcount && !$allFormOnly)
				{
					$attendee->guestcount = $countAttendeeAndGuests;
					// Update the attendee record if we have the wrong guest count
					$db = JFactory::getDBO();
					$sql = "UPDATE #__jev_attendees set guestcount= " . $countAttendeeAndGuests . " WHERE id=" . $attendee->id;
					$db->setQuery($sql);
					$db->query();
				}
			}
		}

	}

	public function countAttendees($atdid = 0, $forcecount = false)
	{
		// only do this on jevents detail pages and any rsvp pro pages or if we want a forced count e.g. from payment status update
		if (!(JRequest::getCmd("option") == "com_rsvppro" || $forcecount ||
				(JRequest::getCmd("option") == "com_jevents" && (JRequest::getCmd("jevtask") == "icalrepeat.detail" || JRequest::getCmd("jevtask") == "icalevent.detail"))
				))
		{
			return;
		}
		JLoader::register('JevRsvpParameter', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrsvpparameter.php");

		$db = JFactory::getDBO();
		$dataModel = new JEventsDataModel();
		$queryModel = new JEventsDBModel($dataModel);

		$where = "";
		$where2 = "";
		$templateParams = false;
		$countconfirmedonly = " and  atdees.confirmed=1 ";
		if ($atdid != 0)
		{
			$where = " atd.id =" . intval($atdid) . " AND ";
			$where2 = "  WHERE at_id=" . intval($atdid);

			// needed to find any "includeintotalcapacity" fields
			$sql = "SELECT * FROM #__jev_attendance WHERE id=" . intval($atdid);
			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();

			if ($rsvpdata->template != "")
			{
				$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($rsvpdata->template));

				$templateParams = $db->loadObject();
				if ($templateParams)
				{
					$templateParams = json_decode($templateParams->params);
					if (isset($templateParams->unconfirmedcapacity) && $templateParams->unconfirmedcapacity == 1){
						$countconfirmedonly = " ";
					}
				}
			}

		}

		// Now make sure the atdcount is in sync
		//$db->setQuery("SELECT count(DISTINCT atdees.id) as atcnt, atdees.rp_id, atdees.at_id  FROM #__jev_attendance as atd  LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id WHERE $where atdees.attendstate=1 and  atdees.confirmed=1 GROUP BY atdees.rp_id");
		// config option to allow unpaid/part paid attendees to count towards capacity is checked further down the page
		$db->setQuery("SELECT sum(guestcount) as atcnt, count(atdees.id) as regcnt, atdees.id, atdees.rp_id, atdees.at_id  FROM #__jev_attendance as atd  "
				. "LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id "
				. "WHERE $where (atdees.attendstate=1 OR  atdees.attendstate=4) $countconfirmedonly "
				. "GROUP BY atdees.rp_id");
		$rows = $db->loadObjectList();

		$db->setQuery("DELETE FROM #__jev_attendeecount $where2");
		$db->query();

		$processedRepeats = array();

		if (is_array($rows))
		{
			foreach ($rows as $row)
			{
				if (intval($row->at_id) == 0)
					continue;

				// Now find any "includeintotalcapacity" fields
				$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
				$db->setQuery($sql);
				$rsvpdata = $db->loadObject();

				if ($row->rp_id == 0)
				{
					// Find the first repeat
					$vevent = $dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
					if (!$vevent)
						continue;
					$repeat = $vevent->getFirstRepeat();
				}
				else
				{
					list($year, $month, $day) = JEVHelper::getYMD();
					// include unpublished events
					$repeatdata = $dataModel->getEventData(intval($row->rp_id), "icaldb", $year, $month, $day);
					if ($repeatdata && isset($repeatdata["row"]))
						$repeat = $repeatdata["row"];
					else
						continue;
				}

				// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
				$registry = JRegistry::getInstance("jevents");
				$registry->set("rsvpdata", $rsvpdata);
				$registry->set("event", $repeat);

				// do we want to include unpaid users in capacity
				$db = JFactory::getDBO();
				$templateParams = false;
				if ($rsvpdata->template != "")
				{
					$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($rsvpdata->template));

					$templateParams = $db->loadObject();
					if ($templateParams)
					{
						$templateParams = json_decode($templateParams->params);
					}
				}
				if (!$templateParams || !isset($templateParams->unpaidcapacity) || $templateParams->unpaidcapacity == 0)
				{
					$confirmed = "and atdees.confirmed=1";
					if (isset($templateParams) && isset($templateParams->unconfirmedcapacity) && $templateParams->unconfirmedcapacity == 1) {
						$confirmed = "";
					}

					$where = " atd.id =" . intval($row->at_id) . " AND atdees.rp_id=" . intval($row->rp_id) . " AND ";
					$db->setQuery("SELECT sum(guestcount) as atcnt, count(atdees.id) as regcnt, atdees.id, atdees.rp_id, atdees.at_id FROM #__jev_attendance as atd "
							. "LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id "
							. "WHERE $where atdees.attendstate=1 $confirmed "
							. "GROUP BY atdees.rp_id");
					$sql = $db->getQuery()."<Br/>";

					// replace row details
					$row = $db->loadObject();
					if (!$row || is_null($row->id))
					{
						continue;
					}
				}
				else if (isset($templateParams) && isset($templateParams->unconfirmedcapacity) && $templateParams->unconfirmedcapacity == 1)
				{
					$confirmed = "";
					$attendstate =  "atdees.attendstate=1";
					if (!isset($templateParams->unpaidcapacity) || $templateParams->unpaidcapacity == 1) {
						 $attendstate =  "(atdees.attendstate=1 OR  atdees.attendstate=4)";
					}

					$where = " atd.id =" . intval($row->at_id) . " AND atdees.rp_id=" . intval($row->rp_id) . " AND ";
					$db->setQuery("SELECT sum(guestcount) as atcnt, count(atdees.id) as regcnt, atdees.id, atdees.rp_id, atdees.at_id FROM #__jev_attendance as atd "
							. "LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id "
							. "WHERE $where $attendstate $confirmed "
							. "GROUP BY atdees.rp_id");
					$sql = $db->getQuery()."<Br/>";

					// replace row details
					$row = $db->loadObject();
					if (!$row || is_null($row->id))
					{
						continue;
					}
				}


				if (in_array($row->rp_id, $processedRepeats))
					continue;
				$processedRepeats[] = $row->rp_id;

				// New parameterised fields
				if ($rsvpdata->template != "")
				{
					$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

					if (is_int($xmlfile) || file_exists($xmlfile))
					{
						$rsvpparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, $row);
						$params = $rsvpparams->renderToBasicArray();

						foreach ($params as $param)
						{
							if ((isset($param ["includeintotalcapacity"]) && $param ["includeintotalcapacity"] > 0) && (isset($param ["capacitycount"]) || $rsvpdata->capacity > 0))
							{
								// now get the capacity summary - this recalculates the whole thing so we know for certain the numbers for all attendees
								$atdee = false;
								$rsvpparams->calculateRowContributionsToCapacity($param, $param['type'], $atdee);

								$row->atcnt += intval($param ["capacitycount"]);

								if (isset($param ["reducevaluefortotalcapacity"]) && (intval($param ["capacitycount"]) > 0 || $rsvpdata->capacity > 0))
								{
									$row->atcnt -= $param ["reducetotalcapacity"];
								}


							}

						}
					}
				}
				//Guest count will always be Total number minus the primary.
				$guestcnt = $row->atcnt - $row->regcnt;

				$db->setQuery("INSERT INTO #__jev_attendeecount (at_id, rp_id, atdcount, gucount) VALUES ($row->at_id,$row->rp_id,$row->atcnt, $guestcnt)");
				if (!$db->query())
				{
					//echo (string) $db->getQuery();
					echo $db->getErrorMsg();
				}
			}
		}

		if (count($rows) > 0 && isset($rsvpdata))
		{
			$this->updateWaitingList($rsvpdata, $atdid);
		}

		// process coupons to make sure each one is not over used
		if (is_array($rows))
		{
			foreach ($rows as $row)
			{
				if (intval($row->at_id) == 0)
					continue;

				static $atdcoupons = array();
				if (isset($atdcoupons[$row->at_id]))
				{
					continue;
				}
				$atdcoupons[$row->at_id] = 1;
				if ($row->at_id != $rsvpdata->id)
				{
					// Now find any "includeintotalcapacity" fields
					$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
					$db->setQuery($sql);
					$rsvpdata = $db->loadObject();
				}

				static $couponCodeUsages = array();
				if ($rsvpdata->template != "")
				{
					$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

					if (is_int($xmlfile) || file_exists($xmlfile))
					{
						$rsvpparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, null);
						$params = $rsvpparams->renderToBasicArray();
						foreach ($params as $param)
						{
							// record even if no max usage is set (in case template implements this later)
							if ($param["type"] == "jevrcoupon")
							{
								$couponelement = $rsvpparams->getField($param["name"] , "xmlfile");
								if (!$couponelement) continue;
								// This calls the function to fill in the pricesArray and surchargesArray
								$couponelement->getLabel();
								$couponcodes = array();
								if (isset($couponelement->pricesArray) && count($couponelement->pricesArray)>0){
									$couponcodes =array_keys($couponelement->pricesArray);
								}
								if (isset($couponelement->surchargesArray) && count($couponelement->surchargesArray)>0){
									$couponcodes =array_merge($couponcodes,array_keys($couponelement->surchargesArray));
								}
								$couponcodes = array_unique($couponcodes);
								
								$db = JFactory::getDbo();
								$sql = "SELECT id, attendstate, params FROM #__jev_attendees WHERE at_id=" . $rsvpdata->id . " and params like ('%\"" . $param["name"] . "\"%')";
								//$sql .=  " AND attendstate=1";
								$db->setQuery($sql);
								$attendees = $db->loadObjectList();
								foreach ($attendees as $atdee)
								{
									$couponfieldname = $param["name"];
									$atdeeparams = json_decode($atdee->params);

									if ($atdeeparams->$couponfieldname != "" && in_array($atdeeparams->$couponfieldname, $couponcodes))
									{
										if (!isset($couponCodeUsages[$couponfieldname]))
										{
											$couponCodeUsages[$couponfieldname] = array();
										}
										if (!isset($couponCodeUsages[$couponfieldname][trim($atdeeparams->$couponfieldname)]))
										{
											$couponCodeUsages[$couponfieldname][trim($atdeeparams->$couponfieldname)] = 0;
										}
										$couponCodeUsages[$couponfieldname][trim($atdeeparams->$couponfieldname)]++;
									}
								}

								$sql = "SELECT * FROM #__jev_rsvp_couponusage  where atd_id=" . intval($rsvpdata->id);
								if (!$rsvpdata->allrepeats){
									$sql .= " AND rp_id=".intval($row->rp_id);
								}
								$db->setQuery($sql);
								$exstingcouponusage = $db->loadObject();

								if (count($couponCodeUsages) > 0 || $exstingcouponusage)
								{
									$db = JFactory::getDbo();
									$sql = "REPLACE INTO #__jev_rsvp_couponusage SET atd_id=$rsvpdata->id, params=" . $db->quote(json_encode($couponCodeUsages));
									$db->setQuery($sql);
									$db->query();
									echo $db->getErrorMsg();
								}
							}
						}
					}
				}
				if (count($couponCodeUsages) > 0)
				{
					$db = JFactory::getDbo();
					$sql = "CREATE TABLE SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
					$db->setQuery($sql);
				}
			}
		}

	}

	public function updateWaitingList($rsvpdata = null, $atdid = 0)
	{
		$atdid = intval($atdid);
		$db = JFactory::getDBO();

		static $params;
		if (!isset($params))
		{
			$params = JComponentHelper::getParams("com_rsvppro");
		}

		if ($atdid == 0)
		{
			// Run over all attendance records
			$db->setQuery("SELECT atd.id FROM #__jev_attendance as atd");
			$rows = $db->loadObjectList();

			foreach ($rows as $row)
			{
				if (intval($row->id) == 0)
					continue;
				$this->updateWaitingList($rsvpdata, $row->id);
			}
		}
		else if ($atdid > 0)
		{
			$unpaidWaitingList = "";
			$db = JFactory::getDBO();
			$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($rsvpdata->template));
			$templateParams = $db->loadObject();
			if ($templateParams)
			{
				$templateParams = json_decode($templateParams->params);
				if (isset($templateParams->waitingnopay) && $templateParams->waitingnopay){
					$unpaidWaitingList = "  OR atdees.attendstate=4";
					//JFactory::getApplication()->enqueueMessage("Waiting no pay");
				}
			}

			// Now make sure the atdcount is in sync - NB only update status of CONFIRMED attendees!
			$db->setQuery("SELECT atdees.id as atdee_id , atdees.*, atd.*, atdcnt.* FROM #__jev_attendance as atd
			LEFT JOIN #__jev_attendees as atdees ON atdees.at_id=atd.id
			LEFT JOIN #__jev_attendeecount as atdcnt ON atdcnt.at_id=atd.id AND atdcnt.rp_id=atdees.rp_id
			WHERE atd.id =" . intval($atdid) . " AND atdees.waiting=1 AND atdees.confirmed=1 AND (atdees.attendstate=1 $unpaidWaitingList)
			ORDER BY atdees.id asc");
			$rows = $db->loadObjectList();
			// may not have any attendees so get a null here
			if (!$rows)
				return;

			$updatedCount = array();
			foreach ($rows as $row)
			{
				if (!isset($updatedCount[$row->rp_id])){
					$updatedCount[$row->rp_id] = 0;
				}
				// Must refetch the waiting attendee count because its messed up when invitees are also present!
				$query = "SELECT sum(atdees.guestcount) as waitingcount "
				. "\n FROM #__jev_attendees AS atdees "
						. "WHERE atdees.at_id = " . intval($atdid) . " AND atdees.rp_id=".$row->rp_id." AND atdees.waiting=1 AND atdees.confirmed=1 AND (atdees.attendstate=1 $unpaidWaitingList)"	;
				$db->setQuery( $query );
				$row->waitingcount = $db->loadResult();

				if ($row->capacity > 0 && $row->waitingcapacity > 0) {
                                         $row->atdcount -= $row->waitingcount;
                                    }

				if ($row->atdcount  + $row->guestcount + $updatedCount[$row->rp_id] > $row->capacity) {
					// We are doing promotions in order so must not let any other be promoted if there are not enough spaces for the guests of this registration
					// we therefore simulate this by making updateCount really big!
					$updatedCount[$row->rp_id]  = $row->capacity + 100;
					continue;
				}
				
				// TODO FIX THIS
				$db->setQuery("UPDATE #__jev_attendees SET waiting=0 where id=" . $row->atdee_id);
				if ($db->query())
				{
					$updatedCount[$row->rp_id]+=$row->guestcount;

					// auto remind attendees
					if ($params->get("autoremind", 0) == 1)
					{
						if (is_null($rsvpdata))
						{
							if ($atdid > 0)
							{
								$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $row->at_id;
								$db->setQuery($sql);
								$rsvpdata = $db->loadObject();
							}
						}

						// always allow auto reminders for attendees if forced reminders enabled
						if ($rsvpdata->allowreminders || $this->params->get("forceautoremind", 0) == 1)
						{
							// create reminder
							// NB email address must be in the request object
							$user = JEVHelper::getUser($row->user_id);
							// create reminder
							$datamodel = new JEventsDataModel();
							if ($row->rp_id > 0)
							{
								$evt = $datamodel->queryModel->listEventsById($row->rp_id, 1, "icaldb");
							}
							else
							{
								$evt = $datamodel->queryModel->getEventById($rsvpdata->ev_id, 1, "icaldb");
							}
							if ($evt)
							{
								$this->jevrreminders->remindUser($rsvpdata, $evt, $user, $row->email_address);
								$mainframe = JFactory::getApplication();
								$mainframe->enqueueMessage(JText::_("JEV_REMINDER_CONFIRMED"));
							}
						}
					}

					if ($params->get("notifyuser", 0)){
						$this->notifyWaitingUser($row);
					}
				}
				else
					echo $db->getErrorMsg();
			}
		}

	}

	public function getMessageTemplate($params, $rsvpdata, $msgkey)
	{

		$templateParams = false;
		if ($rsvpdata->template != "")
		{
			if (isset($rsvpdata->templateParams))
			{
				$templateParams = $rsvpdata->templateParams;
			}
			else
			{
				$db = JFactory::getDbo();
				$db->setQuery("Select params from #__jev_rsvp_templates where id=" . intval($rsvpdata->template));

				$templateParams = $db->loadObject();
				if ($templateParams)
				{
					$templateParams = json_decode($templateParams->params);
					$rsvpdata->templateParams = $templateParams;
				}
				else
				{
					$templateParams = false;
				}
			}
			if ($templateParams && isset($templateParams->$msgkey) && $templateParams->$msgkey != "")
			{
				return $templateParams->$msgkey;
			}
		}
		return $params->get($msgkey);

	}

	public function getBCC($userid)
	{
		$bcc = null;
		$params = JComponentHelper::getParams("com_rsvppro");
		if ($userid > 0 && $params->get("cbbcc") != "")
		{
			$bccfield = $params->get("cbbcc");
			$db = JFactory::getDBO();
			$sql = "select $bccfield from #__comprofiler where user_id = $userid";
			$db->setQuery($sql);
			$bcc = $db->loadResult();
			/*
			  if (JFactory::getUser()->id == 79)
			  {
			  JFactory::getApplication()->enqueueMessage("get BCC query = " . $db->getQuery() .  " : " . $bcc);
			  }
			 *
			 */
		}
		return $bcc;

	}

	public function sendMail($from, $fromname, $recipient, $subject, $body, $mode = 0, $cc = null, $bcc, $attachment = null, $replyto = null, $replytoname = null, $toCreator=false)
	{
		$params = JComponentHelper::getParams("com_rsvppro");
		$from = $params->get("overridesenderemail", $from);
		$fromname = $params->get("overridesendername", $fromname);
		if ($bcc == "")
		{
			$bcc = null;
		}
		// send message to bcc address only for send to Creator
		if ($toCreator && $this->params->get("notifycreator", 0) == 4 && $bcc != null )
		{
			$recipient = $bcc;
			$bcc = null;
		}

		/*
		  if (JFactory::getUser()->id == 79)
		  {
		  if ($bcc)
		  {
		  JFactory::getApplication()->enqueueMessage("Sending message from $from to $recipient with bcc to $bcc");
		  }
		  else
		  {
		  JFactory::getApplication()->enqueueMessage("Sending message from $from to $recipient with NO bcc ");
		  }
		  }
		 *
		 */
		$mail = JFactory::getMailer();
		if (is_array($bcc)) {
			$bcc = array_unique($bcc);
		}

		// Set AltBody so we get plain text version in the message too!
		if (JevJoomlaVersion::isCompatible("3.4")) {
			$mail->AltBody = $mail->normalizeBreaks($mail->html2text($body, true));
		}
		else {
			$textMsg = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s', '', $body)));
			if (!empty($textMsg)) {
				$mail->AltBody = html_entity_decode($textMsg, ENT_QUOTES, $mail->CharSet);
			}
		}

		return $mail->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);

	}

	static public function isVisibleStatic($node, $attendee, $guest, $nodes)
	{
		// always return false if dependent node is for guests and we have guest 0 = prime attendee
		if (is_object($node->attribute("peruser")) || is_object($guest)){
			return false;
		}
		if ($node->attribute("peruser") == 2 && $guest == 0)
		{
			return false;
		}

		$conditionnode = false;
		$cf = $node->attribute("cf");
		if ($cf == "")
		{
			return true;
		}
		if (!isset($attendee->params) || $attendee->params == "")
		{
			return true;
		}
		$attendeefields = json_decode($attendee->params);
		if (!isset($attendeefields->$cf))
		{
			return true;
		}

		// search for field on which this node is conditioned
		foreach ($nodes as $cnode)
		{
			if ($cnode->fieldname == $cf)
			{
				$conditionnode = $cnode;
				break;
			}
		}
		if (!$conditionnode)
		{
			return true;
		}

		$cfieldvalue = $attendeefields->$cf;
		// global condition
		if ($conditionnode->attribute("peruser") == 0)
		{
			// condition is visible then use the guest count
			if ($cfieldvalue == $node->attribute("cfvfv"))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		// individual or guest condition
		else
		{
			if ($node->attribute("peruser") > 0)
			{
				// check the index
				if ((is_array($cfieldvalue) && array_key_exists($guest, $cfieldvalue) && $cfieldvalue[$guest] == $node->attribute("cfvfv") )
						|| (is_object($cfieldvalue) && isset($cfieldvalue->$guest) && $cfieldvalue->$guest == $node->attribute("cfvfv")))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				// you can't have an individual condition field driving a group value!
				return false;
			}
		}

	}

	private function generatePdfTicket($ticket, $attendee, $rsvpdata, $event) {

		if (!$ticket){
			return false;
		}

		$_name		= 'joomla';

		$_header	= null;
		$_header_font = 'courier';
		$_footer_font = 'courier';

		$_margin_header	= 5;
		$_margin_footer	= 10;
		$_margin_top	= 27;
		$_margin_bottom	= 25;
		$_margin_left	= 15;
		$_margin_right	= 15;

		// Scale ratio for images [number of points in user unit]
		$_image_scale	= 1;

		// Define cache folder for images etc.
		$cache =  JPATH_CACHE."/tcpdf/";
		if (!defined('K_PATH_CACHE')) {
		//	define ('K_PATH_CACHE', 	JPATH_CACHE."/tcpdf/");
		}

		// Default settings are a portrait layout with an A4 configuration using millimeters as units
		if(!class_exists('TCPDF')) {
			try {
				require_once(JPATH_ROOT.'/libraries/tcpdf/tcpdf.php');
			}
			catch (Exception $e) {
				JFactory::getApplication()->enqueueMessage("Please ensure you have a PDF library installed for Joomla at librares/tcpdf/");
				return false;
				die ("Please ensure you have a PDF library installed for Joomla at librares/tcpdf/");
			}
		}

		$pdf = new TCPDF();

		//set margins
		$pdf->SetMargins($_margin_left, $_margin_top, $_margin_right);
		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, $_margin_bottom);
		$pdf->SetHeaderMargin($_margin_header);
		$pdf->SetFooterMargin($_margin_footer);
		$pdf->setImageScale($_image_scale);

		// Set PDF Metadata
		$pdf->SetCreator("creator");
		$pdf->SetTitle("the title");
		$pdf->SetSubject("the subject");
		//$pdf->SetKeywords("keywords);

		// Set PDF Header data
		// logo, logowidth, title, headertext
		//$pdf->setHeaderData('',0,"the title", "header string");

		// Set PDF Header and Footer fonts
		 $lang = JFactory::getLanguage();

		$pdf->setRTL($lang->isRTL());
		//$pdf->SetFont('helvetica', '', 8, '', 'false');
		//$pdf->setHeaderFont(array($_header_font, '', 10));
		//$pdf->setFooterFont(array($_footer_font, '', 8));

		// Initialize PDF Document
		$pdf->AddPage();

		// Make images absolute - should already be done!
		//$ticket = str_replace(' src="/', ' src="'.JURI::base(false), $ticket);
		
		$staticQR = false;
/*
		$matches = array();
		preg_match_all("@components\/com_rsvppro\/assets\/images\/qrimage.php\?bc=(.*)' alt@", $ticket, $matches);

		if (count($matches)==2){
			 $staticQR = array();
			foreach ($matches[1] as $count => $match) {
				$http = JHttpFactory::getHTTP();
				// create the local file!
				$base = str_replace("/administrator","", JURI::base(false));
				$iamge = $http->get($base."components/com_rsvppro/assets/images/qrimage.php?bc=".$match."&fc=1");
				$ticket = str_replace("components/com_rsvppro/assets/images/qrimage.php?bc=","cache/qr/QRC_", $ticket);
				$ticket = str_replace($match, urlencode($match).".png", $ticket);
				$staticQR[] = JPATH_SITE."/cache/qr/QRC_".$match.".png";
			}
		}
*/

		// remove the {PPDFTICKETS} code  !!
		$ticket = str_replace("{PDFTICKETS}", "", $ticket);

		// Build the PDF Document string from the document buffer
		$pdf->WriteHTML($ticket, true);
		$data = $pdf->Output('', 'S');

		// now remove the file that is no longer needed
		if ($staticQR){
			foreach ($staticQR as $k => $img){
				JFile::delete($img);
			}
		}

		return $data;
	}

	protected function log( $text )
	{
return;
		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		$logfile = JPATH_SITE. str_replace("//","/",$params->get('PayPalLogFileLocation','/administrator/components/com_rsvppro/logs/') . '/helper_log.txt');

		$fp = @fopen($logfile, 'a' );
		if ($fp){
			@fwrite( $fp, $text . "\n\n" );
			@fclose( $fp );
		}
	}

}
