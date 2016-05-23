<?php

/**
 * Copyright (C)2010-2015 GWE Systems Ltd
 *
 * All rights reserved.
 *
 */
defined('_JEXEC') or die('No Direct Access');

class RsvpInviteeHelper
{

	private
			$params;

	public
			function __construct($params)
	{
		$this->params = $params;

	}

	public
			function updateInvitees($rsvpdata, $row, $redirect = true)
	{
		$user = JFactory::getUser();
		if ($user->id == 0)
		{
			return "";
		}
		if ($user->id == $row->created_by() || JEVHelper::isAdminUser($user) || JEVHelper::canDeleteEvent($row, $user))
		{
			$jevattend_hiddeninitees = JRequest::getInt("jevattend_hiddeninitees", 0);
			if ($jevattend_hiddeninitees)
			{
				$jevinvitees = JRequest::getVar("jevinvitee", array(), 'post', 'array');
				// Numeric invites first for registered users
				$invitees = array();
				foreach ($jevinvitees as $invitee)
				{
					$id = intval(str_replace("rsvp_inv_", "", $invitee));
					if ($id > 0)
						$invitees[] = $id;
				}
				JArrayHelper::toInteger($invitees);
				$idlist = implode(",", $invitees);
				$db = JFactory::getDBO();
				// remove invitees not in the list
				if (count($invitees) > 0)
				{
					$sql = "DELETE FROM #__jev_invitees WHERE user_id NOT IN (" . $idlist . ") AND at_id=" . intval($rsvpdata->id) . " AND email_address=''";
				}
				else
				{
					$sql = "DELETE FROM #__jev_invitees WHERE at_id=" . intval($rsvpdata->id) . " AND email_address=''";
				}
				if (!$rsvpdata->allinvites && $row->hasrepetition())
				{
					$sql .= " AND rp_id=" . $row->rp_id();
				}
				$db->setQuery($sql);
				$db->query();
				// Email based invites for non-registered users
				$db->setQuery("SELECT id , email_address from #__jev_invitees WHERE at_id=" . $rsvpdata->id . " AND email_address<>''");
				$currentlist = $db->loadObjectList('email_address');
				$emailaddresses = array();
				$emailnames = array();
				$keeplist = array();
				foreach ($jevinvitees as $invitee)
				{
					$invitee = str_replace(array("rsvp_inv_", "}", ")"), "", $invitee);
					$parts = explode("{", $invitee);
					if (count($parts) != 2)
						$parts = explode("(", $invitee);
					if (count($parts) != 2)
						continue;
					$emailaddresses[] = $parts[1];
					$emailnames[] = $parts[0];
					if (array_key_exists($parts[1], $currentlist))
					{
						$keeplist[] = $currentlist[$parts[1]]->id;
						unset($currentlist[$parts[1]]);
					}
				}
				// remove invitees not in the list
				$currentids = array();
				foreach ($currentlist as $currentinvitee)
				{
					$currentids[] = $currentinvitee->id;
				}
				$idlist = implode(",", $currentids);
				$ids = explode(",", $idlist);
				JArrayHelper::toInteger($ids);
				$idlist = implode(",", $ids);
				$db = JFactory::getDBO();
				if (count($keeplist) > 0)
				{
					$keeplist = implode(",", $keeplist);
					$sql = "DELETE FROM #__jev_invitees WHERE id  NOT IN (" . $keeplist . ") AND at_id=" . intval($rsvpdata->id) . " AND email_address<>''";
					if (!$rsvpdata->allinvites && $row->hasrepetition())
					{
						$sql .= " AND rp_id=" . $row->rp_id();
					}
					$db->setQuery($sql);
					$db->query();
				}
				if (count($jevinvitees) == 0)
				{
					$sql = "DELETE FROM #__jev_invitees WHERE at_id=" . $rsvpdata->id . " AND email_address<>'' ";
					if (!$rsvpdata->allinvites && $row->hasrepetition())
					{
						$sql .= " AND rp_id=" . $row->rp_id();
					}
					$db->setQuery($sql);
					$db->query();
				}
				// Are we saving the list of invitees
				if (JRequest::getString("rsvp_email", "", "post") == "savelist")
				{
					$listname = trim(JRequest::getString("jevrsvp_listid", ""));
					if ($listname != "")
					{
						$db = JFactory::getDBO();
						// does the list exist already
						$db->setQuery("SELECT * FROM #__jev_invitelist WHERE user_id=" . $user->id . " AND  listname=" . $db->Quote($listname));
						$list = $db->loadObject();
						if ($list)
						{
							$listid = $list->id;
						}
						else
						{
							$db->setQuery("REPLACE INTO #__jev_invitelist SET user_id=" . $user->id . ", listname=" . $db->Quote($listname));
							$db->query();
							$listid = $db->insertid();
						}
						// empty the current list members
						$db->setQuery("DELETE FROM #__jev_invitelist_member WHERE list_id=" . $listid);
						$db->query();
						// if its an empty list then remove it
						if (count($jevinvitees) == 0)
						{
							// empty the current list members
							$db->setQuery("DELETE FROM #__jev_invitelist WHERE id=" . $listid);
							$db->query();
							JFactory::getApplication()->enqueueMessage(JText::_("RSVP_INVITEE_LIST_DELETED"), "error");
						}
					}
				}
				// insert new records
				foreach ($invitees as $invitee)
				{
					$iuser = JEVHelper::getUser($invitee);
					if (JRequest::getString("rsvp_email", "", "post") == "savelist" && isset($listid) && $listid > 0)
					{
						$db = JFactory::getDBO();
						JTable::addIncludePath(RSVP_TABLES);
						$listmember = JTable::getInstance('jev_invitelist_member');
						//$listmember = new JTable("#__jev_invitelist_member","id",$db);
						$listmember->list_id = $listid;
						$listmember->user_id = $iuser->id;
						$listmember->store();
					}
					$currentinvitee = $this->fetchInvitee($row, $rsvpdata, $invitee);
					if (!$currentinvitee)
					{
						JTable::addIncludePath(RSVP_TABLES);
						$currentinvitee = JTable::getInstance('jev_invitees');
						//$currentinvitee = new JTable("#__jev_invitees","id",$db);
						$currentinvitee->id = 0;
						$currentinvitee->user_id = $invitee;
						if (!$rsvpdata->allinvites && $row->hasrepetition())
						{
							$currentinvitee->rp_id = $row->rp_id();
						}
						else
						{
							$currentinvitee->rp_id = 0;
						}
						$currentinvitee->at_id = $rsvpdata->id;
						if (class_exists("JevDate"))
						{
							$datenow = JevDate::getDate();
							$currentinvitee->invitedate = $datenow->toMySQL();
						}
						else
						{
							$datenow = JFactory::getDate();
							$currentinvitee->invitedate = $datenow->toSql();
						}
						$currentinvitee->save(array());
						$currentinvitee->attending = false;
						$currentinvitee->iid = $currentinvitee->id;
						// email new invitees
						if (JRequest::getString("rsvp_email", "", "post") == "email" || JRequest::getString("rsvp_email", "", "post") == "reemail" || JRequest::getString("rsvp_email", "", "post") == "failed")
						{
							if ($iuser)
							{
								list ($message, $subject) = $this->processMessage($rsvpdata, $row, $iuser->name, true, $iuser);
								$bcc = $this->getBCC($iuser->id);
								$success = $this->sendMail($user->email, $user->name, $iuser->email, $subject, $message, 1, null, $bcc);
								$mainframe = JFactory::getApplication();
								if ($success === true)
								{
									$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_SENT_TO", $iuser->name));
									$sql = "UPDATE #__jev_invitees set sentmessage=1 WHERE id=" . $currentinvitee->iid;
									$db->setQuery($sql);
									$db->query();
								}
								else
								{
									$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_NOT_SENT_TO", $iuser->name), "error");
									$sql = "UPDATE #__jev_invitees set sentmessage=0 WHERE id=" . $currentinvitee->iid;
									$db->setQuery($sql);
									$db->query();
								}
							}
						}
					}
					else
					{
						// re-send email invitations
						if (JRequest::getString("rsvp_email", "", "post") == "reemail" || JRequest::getString("rsvp_email", "", "post") == "failed")
						{
							if (JRequest::getString("rsvp_email", "", "post") == "failed" && $currentinvitee->sentmessage == 1)
							{
								continue;
							}
							// Do not send message to confirmed attendees
							if ($currentinvitee->attending)
							{
								continue;
							}
							list ($message, $subject) = $this->processMessage($rsvpdata, $row, $currentinvitee->name, true, $currentinvitee);
							$bcc = null;
							if ($invitee)
							{
								$bcc = $this->getBCC($invitee);
							}
							$success = $this->sendMail($user->email, $user->name, $currentinvitee->email, $subject, $message, 1, null, $bcc);
							$mainframe = JFactory::getApplication();
							if ($success === true)
							{
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_SENT_TO", $currentinvitee->name));
								$sql = "UPDATE #__jev_invitees set sentmessage=1 WHERE id=" . $currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}
							else
							{
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_NOT_SENT_TO", $currentinvitee->name), "error");
								$sql = "UPDATE #__jev_invitees set sentmessage=0 WHERE id=" . $currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}
						}
					}
				}
				// Now process email address based invites
				for ($i = 0; $i < count($emailaddresses); $i++)
				{
					$emailaddress = $emailaddresses[$i];
					$emailname = $emailnames[$i];
					if (JRequest::getString("rsvp_email", "", "post") == "savelist" && isset($listid) && $listid > 0)
					{
						$db = JFactory::getDBO();
						JTable::addIncludePath(RSVP_TABLES);
						$listmember = JTable::getInstance('jev_invitelist_member');
						$listmember->list_id = $listid;
						$listmember->email_address = $emailaddress;
						$listmember->email_name = $emailname;
						$listmember->store();
					}
					$currentinvitee = $this->fetchInviteeByEmail($row, $rsvpdata, $emailaddress, true);
					if (!$currentinvitee)
					{
						JTable::addIncludePath(RSVP_TABLES);
						$currentinvitee = JTable::getInstance('jev_invitees');
						$currentinvitee->id = 0;
						$currentinvitee->email_address = $emailaddress;
						$currentinvitee->email_name = $emailname;
						if (!$rsvpdata->allinvites && $row->hasrepetition())
						{
							$currentinvitee->rp_id = $row->rp_id();
						}
						else
						{
							$currentinvitee->rp_id = 0;
						}
						$currentinvitee->at_id = $rsvpdata->id;
						if (class_exists("JevDate"))
						{
							$datenow = JevDate::getDate();
							$currentinvitee->invitedate = $datenow->toMySQL();
						}
						else
						{
							$datenow = JFactory::getDate();
							$currentinvitee->invitedate = $datenow->toSql();
						}
						$currentinvitee->save(array());
						$currentinvitee->attending = false;
						$currentinvitee->iid = $currentinvitee->id;
						// email new invitees
						if (JRequest::getString("rsvp_email", "", "post") == "email" || JRequest::getString("rsvp_email", "", "post") == "reemail" || JRequest::getString("rsvp_email", "", "post") == "failed")
						{
							list ($message, $subject) = $this->processMessage($rsvpdata, $row, $emailname, false, $currentinvitee);
							$success = $this->sendMail($user->email, $user->name, $emailaddress, $subject, $message, 1);
							$mainframe = JFactory::getApplication();
							if ($success === true)
							{
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_SENT_TO", $emailname));
								$sql = "UPDATE #__jev_invitees set sentmessage=1 WHERE id=" . $currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}
							else
							{
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_NOT_SENT_TO", $emailname), "error");
								$sql = "UPDATE #__jev_invitees set sentmessage=0 WHERE id=" . $currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}
						}
					}
					else
					{
						// re-send email invitations
						if (JRequest::getString("rsvp_email", "", "post") == "reemail" || JRequest::getString("rsvp_email", "", "post") == "failed")
						{
							if (JRequest::getString("rsvp_email", "", "post") == "failed" && $currentinvitee->sentmessage == 1)
							{
								continue;
							}
							// Do not send message to confirmed attendees
							if ($currentinvitee->attending)
							{
								continue;
							}
							list ($message, $subject) = $this->processMessage($rsvpdata, $row, $currentinvitee->email_name, false, $currentinvitee);
							$success = $this->sendMail($user->email, $user->name, $emailaddress, $subject, $message, 1);
							$mainframe = JFactory::getApplication();
							if ($success === true)
							{
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_SENT_TO", $currentinvitee->email_address));
								$sql = "UPDATE #__jev_invitees set sentmessage=1 WHERE id=" . $currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}
							else
							{
								$mainframe->enqueueMessage(JText::sprintf("JEV_INVITE_NOT_SENT_TO", $currentinvitee->email_address), "error");
								$sql = "UPDATE #__jev_invitees set sentmessage=0 WHERE id=" . $currentinvitee->iid;
								$db->setQuery($sql);
								$db->query();
							}
						}
					}
				}
				// Do we auto remind invitees
				if ($this->params->get("autoremind", "") == 2)
				{
					JLoader::register('JevRsvpReminders', JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/jevrreminders.php");
					$this->jevrreminders = new JevRsvpReminders($this->params, 0);
					$this->jevrreminders->remindUsers($rsvpdata, $row, $this->params->get("autoremind", 0));
				}
				if (!$redirect)
				{
					return true;
				}
				$mainframe = JFactory::getApplication();
				if ($mainframe->isAdmin())
				{
					$repeating = JRequest::getInt("repeating", 0);
					$atd_id = JRequest::getVar("atd_id", "post", "array");
					if (!isset($atd_id[0]) || strpos($atd_id[0], "|") === false)
					{
						JError::raiseError("403", JText::_("RSVP_MISSING_ATDID"));
					}
					list($atd_id, $rp_id) = explode("|", $atd_id[0]);
					$atd_id = intval($atd_id);
					$rp_id = intval($rp_id);
					$link = "index.php?option=com_rsvppro&task=invitees.overview&atd_id[]=$atd_id|$rp_id&repeating=$repeating";
				}
				else
				{
					$Itemid = JRequest::getInt("Itemid");
					list($year, $month, $day) = JEVHelper::getYMD();
					$link = $row->viewDetailLink($year, $month, $day, true, $Itemid);
				}
				if ($redirect)
					$mainframe->redirect($link, JText::_("JEV_INVITES_UPDATED"));
			}
		}
		return true;

	}

	public
			function isInvitee($row, $rsvpdata, $allowSuperAdmin = true, $emailaddress = "")
	{
		$user = JFactory::getUser();
		if ($user->id == $row->created_by() || ($allowSuperAdmin && (JEVHelper::isAdminUser($user) || JEVHelper::canDeleteEvent($row, $user))))
		{
			return true;
		}
		// call fetchInvitee (not user restricited)
		$invitee = $this->fetchInvitee($row, $rsvpdata, $user->id, true, $emailaddress);
		if (is_null($invitee))
		{
			$invitee = $this->fetchInviteeByEmail($row, $rsvpdata, $emailaddress, true);
		}
		return !is_null($invitee);

	}

	public
			function fetchInvitee($row, $rsvpdata, $userid, $open = false, $emailaddress = "")
	{
		$user = JFactory::getUser();
		if ($user->id == 0)
		{
			return null;
		}
		if ($open || $user->id == $row->created_by() || JEVHelper::isAdminUser($user) || JEVHelper::canDeleteEvent($row, $user))
		{
			$db = JFactory::getDBO();
			$sql = "SELECT i.*, u.*, u.id as uid, a.id as attending , a.attendstate, i.id as iid FROM #__jev_invitees as i"
					. " LEFT JOIN #__users as u ON u.id=i.user_id"
					. " LEFT JOIN #__jev_attendees as a ON a.user_id=i.user_id AND a.at_id=i.at_id AND a.rp_id=i.rp_id"
					. " WHERE i.at_id=" . $rsvpdata->id;
			if (!$rsvpdata->allinvites && $row->hasrepetition())
			{
				$sql .= " AND i.rp_id=" . $row->rp_id();
			}
			$sql .= " AND i.user_id=" . $userid;
			$sql .= " ORDER BY i.sentmessage DESC, i.viewedevent DESC ";
			$db->setQuery($sql);
			$invitees = $db->loadObjectList();
			if (is_null($invitees))
				return $invitees;
			// clean up bad data
			if (count($invitees) > 1)
			{
				$ids = array();
				for ($i = 1; $i < count($invitees); $i++)
				{
					$ids[] = $invitees[$i]->iid;
				}
				$db->setQuery("DELETE FROM #__jev_invitees  WHERE id IN (" . implode(",", $ids) . ")");
				$db->query();
			}
			return isset($invitees[0]) ? $invitees[0] : null;
		}
		return null;

	}

	public
			function fetchInviteeByEmail($row, $rsvpdata, $emailaddress, $open = false)
	{
		$user = JFactory::getUser();
		/*
		  if ($user->id==0){
		  return null;
		  }
		 */
		if ($emailaddress == "")
			return null;
		if ($open || $user->id == $row->created_by() || JEVHelper::isAdminUser($user) || JEVHelper::canDeleteEvent($row, $user))
		{
			$db = JFactory::getDBO();
			$sql = "SELECT i.*, a.id as attending , a.attendstate, i.id as iid FROM #__jev_invitees as i"
					. " LEFT JOIN #__jev_attendees as a ON a.user_id=i.user_id AND a.at_id=i.at_id AND a.rp_id=i.rp_id  AND a.email_address=i.email_address"
					. " WHERE i.at_id=" . $rsvpdata->id;
			if (!$rsvpdata->allinvites && $row->hasrepetition())
			{
				$sql .= " AND i.rp_id=" . $row->rp_id();
			}
			$sql .= " AND i.email_address=" . $db->Quote($emailaddress);
			$sql .= " ORDER BY i.sentmessage DESC, i.viewedevent DESC ";
			$db->setQuery($sql);
			$invitees = $db->loadObjectList();
			if (is_null($invitees))
				return $invitees;
			// clean up bad data
			if (count($invitees) > 1)
			{
				$ids = array();
				for ($i = 1; $i < count($invitees); $i++)
				{
					$ids[] = $invitees[$i]->iid;
				}
				$db->setQuery("DELETE FROM #__jev_invitees  WHERE id IN (" . implode(",", $ids) . ")");
				$db->query();
			}
			return isset($invitees[0]) ? $invitees[0] : null;
		}
		return null;

	}

	public
			function fetchInvitees($row, $rsvpdata)
	{
		$user = JFactory::getUser();
		if ($user->id == 0)
		{
			return array();
		}
		if ($user->id == $row->created_by() || JEVHelper::isAdminUser($user) || JEVHelper::canDeleteEvent($row, $user))
		{
			$db = JFactory::getDBO();
			// First of all the registered invitees
			$sql = "SELECT i.*, u.*, a.id as attending, a.attendstate FROM #__jev_invitees as i"
					. " LEFT JOIN #__users as u ON u.id=i.user_id"
					. " LEFT JOIN #__jev_attendees as a ON a.user_id=i.user_id AND a.at_id=i.at_id AND a.rp_id=i.rp_id"
					. " WHERE i.at_id=" . $rsvpdata->id;
			if (!$rsvpdata->allinvites && $row->hasrepetition())
			{
				$sql .= " AND i.rp_id=" . $row->rp_id();
			}
			$sql .= " AND u.id IS NOT NULL";
			$db->setQuery($sql);
			$invitees = $db->loadObjectList('username');
			// Then the email based invitees
			$sql = "SELECT i.*, a.id as attending, a.attendstate FROM #__jev_invitees as i"
					. " LEFT JOIN #__jev_attendees as a ON LOWER(a.email_address)=LOWER(i.email_address) AND a.at_id=i.at_id AND a.rp_id=i.rp_id"
					. " WHERE i.at_id=" . $rsvpdata->id;
			if (!$rsvpdata->allinvites && $row->hasrepetition())
			{
				$sql .= " AND i.rp_id=" . $row->rp_id();
			}
			$sql .= " AND i.email_address <> ''";
			$db->setQuery($sql);
			$invitees2 = $db->loadObjectList('email_address');
			$invitees = array_merge($invitees, $invitees2);
			ksort($invitees);
			return $invitees;
		}
		return array();

	}

	public
			function recordViewed($rsvpdata, $row)
	{
		$user = JFactory::getUser();

		if ($user->id == 0)
		{
			// record as email address viewing
			$params = JComponentHelper::getParams("com_rsvppro");
			$emailaddress = $this->getEmailAddress("em");
			if (!$emailaddress)
				$emailaddress = $this->getEmailAddress("em2");
			if (!$emailaddress)
				return false;

			$emailinvitee = false;
			$db = JFactory::getDBO();
			$db->setQuery("SELECT * FROM #__users WHERE email=".$db->quote($emailaddress));
			$emailinvitee = $db->loadObject();

			if ($emailinvitee){
				$sql = "UPDATE #__jev_invitees set viewedevent=1  WHERE user_id =" . $emailinvitee->id . " AND at_id=" . $rsvpdata->id;
				if (!$rsvpdata->allinvites && $row->hasrepetition())
				{
					$sql .= " AND rp_id=" . $row->rp_id();
				}
				$db->setQuery($sql);
				$db->query();
				return true;
			}
			
			$sql = "UPDATE #__jev_invitees set viewedevent=1  WHERE user_id =0 AND email_address=" . $db->Quote($emailaddress) . " AND at_id=" . $rsvpdata->id;
			if (!$rsvpdata->allinvites && $row->hasrepetition())
			{
				$sql .= " AND rp_id=" . $row->rp_id();
			}
			$db->setQuery($sql);
			$db->query();
			return true;
		}
		if (!$rsvpdata->invites)
			return true;
		$db = JFactory::getDBO();
		$sql = "UPDATE #__jev_invitees set viewedevent=1  WHERE user_id =" . $user->id . " AND at_id=" . $rsvpdata->id;
		if (!$rsvpdata->allinvites && $row->hasrepetition())
		{
			$sql .= " AND rp_id=" . $row->rp_id();
		}
		$db->setQuery($sql);
		$db->query();
		return true;

	}

	public
			function getEmailAddress($em = "em")
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

	private
			function processMessage($rsvpdata, & $row, $name, $requirelogin = false, $currentinvitee)
	{
		$output = array();
		$output[] = $this->parseMessage($rsvpdata->message, $rsvpdata, $row, $name, $requirelogin, $currentinvitee, "message");
		$output[] = $this->parseMessage($rsvpdata->subject, $rsvpdata, $row, $name, $requirelogin, $currentinvitee);
		return $output;

	}

	private
			function parseMessage($message, $rsvpdata, & $row, $name, $requirelogin = false, $currentinvitee, $parsetype = "subject")
	{
		// Stop email cloaking in email messages
		$message = "{emailcloak=off}" . $message;

		$params = JComponentHelper::getParams("com_rsvppro");
		$jevparams = JComponentHelper::getParams("com_jevents");

		// do we run through the jevents plugins
		if ($params->get("remindplugins", 0))
		{
			JPluginHelper::importPlugin('jevents');
			$dispatcher = JDispatcher::getInstance();
			JRequest::setVar("repeating", $rsvpdata->allrepeats);
			JRequest::setVar("atd_id", array($rsvpdata->id . "|" . $row->rp_id()));
			$dispatcher->trigger('onDisplayCustomFields', array(&$row));
		}
		$user = JFactory::getUser();
		$message = str_replace("{NAME}", $name, $message);
		$message = str_replace("{EVENT}", $row->title(), $message);
		if ($row->created_by() > 0)
		{
			$creator = JEVHelper::getUser($row->created_by());
			$creatormail = $creator->email;
			$creator = $creator->name;
		}
		else
		{
			$db = JFactory::getDBO();
			$db->setQuery("SELECT * FROM #__jev_anoncreator where ev_id=" . intval($row->ev_id()));
			$anonrow = @$db->loadObject();
			if ($anonrow)
			{
				$creatormail = $anonrow->email;
				$creator = $anonrow->name;
			}
			else
			{
				$creator = "unknown";
			}
		}

		// iCal invite Generation
		if ($this->params->get("invite_icals", 0) && $parsetype == "message")
		{

			// Generate invite link:
			//
			//
			$Itemid = JRequest::getInt("Itemid");
			list($year, $month, $day) = JEVHelper::getYMD();
			// Do NOT use SEF because not consistent between frontend and backend!
			$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);
			// make into frontend link!
			if (strpos($link, "/administrator") === 0)
			{
				$link = substr($link, 14);
			}
			$uri = JURI::getInstance();
			//$prefix = $uri->toString( array('scheme','host', 'port'));
			$prefix = JURI::root();
			/*
			  if (strpos($link,"/")===0) {
			  $link = substr($link,1);
			  }
			 */
			// backend doesn't add the / in the URL so fix this
			if (substr($link, 0, 1) != "/" && substr($prefix, strlen($prefix) - 1, 1) != "/")
			{
				$prefix .= "/";
			}
			else if (substr($link, 0, 1) == "/" && substr($prefix, strlen($prefix) - 1, 1) == "/")
			{
				$prefix = substr($prefix, 0, strlen($prefix) - 1);
			}
			$link = $prefix . $link;
			if ($requirelogin)
			{
				if (strpos($link, "?") > 0)
				{
					$link .= "&login=1";
				}
				else
				{
					$link .= "?login=1";
				}
			}
			if (isset($currentinvitee->user_id) && $currentinvitee->user_id == 0)
			{

				if ($params->get("attendemails", 0))
				{
					$emailaddress = $currentinvitee->email_address;
					$em = base64_encode($emailaddress . ":" . md5($params->get("emailkey", "email key") . $emailaddress . "invited"));
					// use em2 since em implies attendance confirmation !!
					if (strpos($link, "?") > 0)
					{
						$link .= "&em2=$em";
					}
					else
					{
						$link .= "?em2=$em";
					}
				}
			}
			$invite_link = "$link";

			//Check if Freq is more than none, if not rename to Daily for single event.
			$icalEvents = array($row);
			if (ob_get_contents())
				ob_end_clean();
			if ($jevparams->get('outlook2003icalexport'))
				$html = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//JEvents 3.1 for Joomla//EN\r\n";
			else
				$html = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//JEvents 3.1 for Joomla//EN\r\n";
			$html .= "CALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n";
			if (!empty($icalEvents))
			{
				ob_start();
				$tzid = $this->vtimezone($icalEvents);
				$html .= ob_get_clean();
				// Build Exceptions dataset - all done in big batches to save multiple queries
				$exceptiondata = array();
				$ids = array();
				foreach ($icalEvents as $a)
				{
					$ids[] = $a->ev_id();
					if (count($ids) > 100)
					{
						$db = JFactory::getDBO();
						$db->setQuery("SELECT * FROM #__jevents_exception where eventid IN (" . implode(",", $ids) . ")");
						$rows = $db->loadObjectList();
						foreach ($rows as $exrow)
						{
							if (!isset($exceptiondata[$exrow->eventid]))
							{
								$exceptiondata[$exrow->eventid] = array();
							}
							$exceptiondata[$exrow->eventid][$exrow->rp_id] = $exrow;
						}
						$ids = array();
					}
				}
				// mop up the last ones
				if (count($ids) > 0)
				{
					$db = JFactory::getDBO();
					$db->setQuery("SELECT * FROM #__jevents_exception where eventid IN (" . implode(",", $ids) . ")");
					$rows = $db->loadObjectList();
					foreach ($rows as $exrow)
					{
						if (!isset($exceptiondata[$exrow->eventid]))
						{
							$exceptiondata[$exrow->eventid] = array();
						}
						$exceptiondata[$exrow->eventid][$row->rp_id] = $exrow;
					}
				}
				// make sure the array is now reindexed for the sake of the plugins!
				$icalEvents = array_values($icalEvents);
				// Call plugin on each event
				$dispatcher = JDispatcher::getInstance();
				ob_start();
				JEVHelper::onDisplayCustomFieldsMultiRow($icalEvents);
				ob_end_clean();
				foreach ($icalEvents as $a)
				{
					// if event has repetitions I must find the first one to confirm the dates
					if ($a->hasrepetition())
					{
						$a = $a->getOriginalFirstRepeat();
					}
					if (!$a)
						continue;

					// Fix for end time of first repeat if its an exception
					if (array_key_exists($a->ev_id(), $exceptiondata) && array_key_exists($a->rp_id(), $exceptiondata[$a->ev_id()]))
					{
						$exception = $exceptiondata[$a->ev_id()][$a->rp_id()];
						// if its the first repeat that has had its end time changes we have not stored this data so need to determine it again
						if ($exception->startrepeat == $exception->oldstartrepeat && $exception->exception_type == 1)
						{
							// look for repeats that are not exceptions
							$testrepeat = $a->getFirstRepeat(false);
							if ($testrepeat)
							{
								$enddatetime = $a->getUnixStartTime() + ($testrepeat->getUnixEndTime() - $testrepeat->getUnixStartTime());
								$a->_endrepeat = JevDate::strftime("%Y-%m-%d %H:%M:%S", $enddatetime);
								$a->_dtend = $enddatetime;
								$a->_unixendtime = $enddatetime;
							}
						}
						elseif ($exception->exception_type == 1)
						{

							// look for repeats that are not exceptions
							$testrepeat = $a->getFirstRepeat(false);
							if ($testrepeat)
							{
								$enddatetime = $a->_olddtstart + ($testrepeat->getUnixEndTime() - $testrepeat->getUnixStartTime());
								$a->_endrepeat = JevDate::strftime("%Y-%m-%d %H:%M:%S", $enddatetime);
								$a->_dtend = $enddatetime;
								$a->_unixendtime = $enddatetime;
							}
						}
					}

					$html .= "BEGIN:VEVENT\r\n";
					$html .= "ORGANIZER;CN=" . $creator . ":MAILTO:" . $creatormail . "\r\n";
					$html .= "UID:" . $a->uid() . "\r\n";
					$html .= "CATEGORIES:" . $a->catname() . "\r\n";
					$html .= "PRIORITY:5\r\n";
					if (!empty($a->_class))
					{
						$html .= "CLASS:" . $a->_class . "\r\n";
					}
					$html .= "SUMMARY:" . $a->title() . "\r\n";
					if ($a->location() != "")
					{
						if (!is_numeric($a->location()))
						{
							$html .= "LOCATION:" . $this->wraplines($this->replacetags($a->location())) . "\r\n";
						}
						else if (isset($a->_loc_title))
						{
							$html .= "LOCATION:" . $this->wraplines($this->replacetags($a->_loc_title)) . "\r\n";
						}
						else
						{
							$html .= "LOCATION:" . $this->wraplines($this->replacetags($a->location())) . "\r\n";
						}
					}
					// We Need to wrap this according to the specs
					/* $html .= "DESCRIPTION:".preg_replace("'<[\/\!]*?[^<>]*?>'si","",preg_replace("/\n|\r\n|\r$/","",$a->content()))."\n"; */
					//Lets set the description as the link for the event
					$html .= $this->setDescription(strip_tags($a->content()) . " " . $invite_link);
					if ($a->hasContactInfo())
						$html .= "CONTACT:" . $this->replacetags($a->contact_info()) . "\r\n";
					if ($a->hasExtraInfo())
						$html .= "X-EXTRAINFO:" . $this->wraplines($this->replacetags($a->_extra_info)) . "\r\n";
					$alldayprefix = "";
					// No doing true timezones!
					if ($tzid == "" && is_callable("date_default_timezone_set"))
					{
						// UTC!
						$start = $a->getUnixStartTime();
						$end = $a->getUnixEndTime();
						// in case the first repeat has been changed
						if (array_key_exists($a->_eventid, $exceptiondata) && array_key_exists($a->rp_id(), $exceptiondata[$a->_eventid]))
						{
							$start = JevDate::strtotime($exceptiondata[$a->_eventid][$a->rp_id()]->oldstartrepeat);
						}
						// Change timezone to UTC
						$current_timezone = date_default_timezone_get();
						// If all day event then don't show the start time or end time either
						if ($a->alldayevent())
						{
							$alldayprefix = ";VALUE=DATE";
							$startformat = "%Y%m%d";
							$endformat = "%Y%m%d";
							// add 10 seconds to make sure its not midnight the previous night
							$start += 10;
							$end += 10;
						}
						else
						{
							date_default_timezone_set("UTC");
							$startformat = "%Y%m%dT%H%M%SZ";
							$endformat = "%Y%m%dT%H%M%SZ";
						}
						// Do not use JevDate version since this sets timezone to config value!
						$start = strftime($startformat, $start);
						$end = strftime($endformat, $end);
						$stamptime = strftime("%Y%m%dT%H%M%SZ", time());
						// Change back
						date_default_timezone_set($current_timezone);
					}
					else
					{
						$start = $a->getUnixStartTime();
						$end = $a->getUnixEndTime();
						// If all day event then don't show the start time or end time either
						if ($a->alldayevent())
						{
							$alldayprefix = ";VALUE=DATE";
							$startformat = "%Y%m%d";
							$endformat = "%Y%m%d";
							// add 10 seconds to make sure its not midnight the previous night
							$start += 10;
							$end += 10;
						}
						else
						{
							$startformat = "%Y%m%dT%H%M%S";
							$endformat = "%Y%m%dT%H%M%S";
						}
						$start = JevDate::strftime($startformat, $start);
						$end = JevDate::strftime($endformat, $end);
						$current_timezone = date_default_timezone_get();

						if (is_callable("date_default_timezone_set"))
						{
							date_default_timezone_set("UTC");
							$stamptime = JevDate::strftime("%Y%m%dT%H%M%SZ", time());
							// Change back
							date_default_timezone_set($current_timezone);
						}
						else
						{
							$stamptime = JevDate::strftime("%Y%m%dT%H%M%SZ", time());
						}
						// in case the first repeat is changed
						if (array_key_exists($a->_eventid, $exceptiondata) && array_key_exists($a->rp_id(), $exceptiondata[$a->_eventid]))
						{
							$start = JevDate::strftime($startformat, JevDate::strtotime($exceptiondata[$a->_eventid][$a->rp_id()]->oldstartrepeat));
						}
					}
					$html .= "\r\nDTSTAMP:" . $stamptime . "\r\n";
					$html .= "DTSTART$tzid$alldayprefix:" . $start . "\r\n";
					// events with no end time don't give a DTEND
					if (!$a->noendtime())
					{
						$html .= "DTEND$tzid$alldayprefix:" . $end . "\r\n";
					}
					$html .= "SEQUENCE:" . $a->_sequence . "\r\n";
					if ($a->hasrepetition())
					{
						$html .= 'RRULE:';
						// TODO MAKE SURE COMPAIBLE COMBINATIONS
						$html .= 'FREQ=' . $a->_freq;
						if ($a->_until != "" && $a->_until != 0)
						{
							// Do not use JevDate version since this sets timezone to config value!
							// GOOGLE HAS A PROBLEM WITH 235959!!!
							//$html .= ';UNTIL=' . strftime("%Y%m%dT235959Z", $a->_until);
							$html .= ';UNTIL=' . strftime("%Y%m%dT000000Z", $a->_until + 86400);
						}
						else if ($a->_count != "")
						{
							$html .= ';COUNT=' . $a->_count;
						}
						if ($a->_rinterval != "")
							$html .= ';INTERVAL=' . $a->_rinterval;
						if ($a->_freq == "DAILY")
						{

						}
						else if ($a->_freq == "WEEKLY")
						{
							if ($a->_byday != "")
								$html .= ';BYDAY=' . $a->_byday;
						}
						else if ($a->_freq == "MONTHLY")
						{
							if ($a->_bymonthday != "")
							{
								$html .= ';BYMONTHDAY=' . $a->_bymonthday;
								if ($a->_byweekno != "")
									$html .= ';BYWEEKNO=' . $a->_byweekno;
							}
							else if ($a->_byday != "")
							{
								$html .= ';BYDAY=' . $a->_byday;
								if ($a->_byweekno != "")
									$html .= ';BYWEEKNO=' . $a->_byweekno;
							}
						}
						else if ($a->_freq == "YEARLY")
						{
							if ($a->_byyearday != "")
								$html .= ';BYYEARDAY=' . $a->_byyearday;
						}
						$html .= "\r\n";
					}
					// Now handle Exceptions
					$exceptions = array();
					if (array_key_exists($a->ev_id(), $exceptiondata))
					{
						$exceptions = $exceptiondata[$a->ev_id()];
					}
					$deletes = array();
					$changed = array();
					$changedexceptions = array();
					if (count($exceptions) > 0)
					{
						foreach ($exceptions as $exception)
						{
							if ($exception->exception_type == 0)
							{
								$exceptiondate = JevDate::strtotime($exception->startrepeat);
								// No doing true timezones!
								if ($tzid == "" && is_callable("date_default_timezone_set"))
								{
									// Change timezone to UTC
									$current_timezone = date_default_timezone_get();
									date_default_timezone_set("UTC");
									// Do not use JevDate version since this sets timezone to config value!
									$deletes[] = strftime("%Y%m%dT%H%M%SZ", $exceptiondate);
									// Change back
									date_default_timezone_set($current_timezone);
								}
								else
								{
									$deletes[] = JevDate::strftime("%Y%m%dT%H%M%S", $exceptiondate);
								}
							}
							else
							{
								$changed[] = $exception->rp_id;
								$changedexceptions[$exception->rp_id] = $exception;
							}
						}
						if (count($deletes) > 0)
						{
							$html .= "EXDATE$tzid:" . $this->wraplines(implode(",", $deletes)) . "\r\n";
						}
					}
					$html .= "TRANSP:OPAQUE\r\n";
					if ($this->params->get("invite_icals_reminders", 0) == 1)
					{
						$html .= "BEGIN:VALARM\r\n";
						$html .= "TRIGGER:-PT" . $this->params->get("invite_icals_reminders_interval", "60") . "M\r\n";
						$html .= "ACTION:DISPLAY\r\n";
						$html .= "DESCRIPTION: " . JText::_("RSVP_REMINDER") . $a->title() . "\r\n";
						$html .= "END:VALARM\r\n";
					}
					$html .= "END:VEVENT\r\n";
					$changedrows = array();
					if (count($changed) > 0 && $changed[0] != 0)
					{
						foreach ($changed as $rpid)
						{
							if (!isset($this->dataModel))
							{
								$this->dataModel = new JEventsDataModel();
							}
							$a = $this->dataModel->getEventData($rpid, "icaldb", 0, 0, 0);
							if ($a && isset($a["row"]))
							{
								$a = $a["row"];
								$changedrows[] = $a;
							}
						}
						ob_start();
						$dispatcher->trigger('onDisplayCustomFieldsMultiRow', array(&$changedrows));
						ob_end_clean();
						foreach ($changedrows as $a)
						{
							$html .= "BEGIN:VEVENT\r\n";
							$html .= "UID:" . $a->uid() . "\r\n";
							$html .= "CATEGORIES:" . $a->catname() . "\r\n";
							if (!empty($a->_class))
								$html .= "CLASS:" . $a->_class . "\r\n";
							$html .= "SUMMARY:" . $a->title() . "\r\n";
							if ($a->location() != "")
								$html .= "LOCATION:" . $this->wraplines($this->replacetags($a->location())) . "\r\n";
							// We Need to wrap this according to the specs
							$html .= $this->setDescription(strip_tags($a->content())) . $invite_link;
							if ($a->hasContactInfo())
								$html .= "CONTACT:" . $this->replacetags($a->contact_info()) . "\r\n";
							if ($a->hasExtraInfo())
								$html .= "X-EXTRAINFO:" . $this->wraplines($this->replacetags($a->_extra_info));
							$html .= "\r\n";
							$exception = $changedexceptions[$rpid];
							$originalstart = JevDate::strtotime($exception->oldstartrepeat);
							$chstart = $a->getUnixStartTime();
							$chend = $a->getUnixEndTime();
							// No doing true timezones!
							if ($tzid == "" && is_callable("date_default_timezone_set"))
							{
								// UTC!
								// Change timezone to UTC
								$current_timezone = date_default_timezone_get();
								date_default_timezone_set("UTC");
								// Do not use JevDate version since this sets timezone to config value!
								$chstart = strftime("%Y%m%dT%H%M%SZ", $chstart);
								$chend = strftime("%Y%m%dT%H%M%SZ", $chend);
								$stamptime = strftime("%Y%m%dT%H%M%SZ", time());
								$originalstart = strftime("%Y%m%dT%H%M%SZ", $originalstart);
								// Change back
								date_default_timezone_set($current_timezone);
							}
							else
							{
								$chstart = JevDate::strftime("%Y%m%dT%H%M%S", $chstart);
								$chend = JevDate::strftime("%Y%m%dT%H%M%S", $chend);
								$stamptime = JevDate::strftime("%Y%m%dT%H%M%S", time());
								$originalstart = JevDate::strftime("%Y%m%dT%H%M%S", $originalstart);
							}
							$html .= "DTSTAMP$tzid:" . $stamptime . "\r\n";
							$html .= "DTSTART$tzid:" . $chstart . "\r\n";
							$html .= "DTEND$tzid:" . $chend . "\r\n";
							$html .= "RECURRENCE-ID$tzid:" . $originalstart . "\r\n";
							$html .= "SEQUENCE:" . $a->_sequence . "\r\n";
							$html .= "TRANSP:OPAQUE\r\n";
							if ($this->params->get("invite_icals_reminders", 0) == 1)
							{
								$html .= "BEGIN:VALARM\r\n";
								$html .= "TRIGGER:-PT" . $this->params->get("invite_icals_reminders_interval", "60") . "M\r\n";
								$html .= "ACTION:DISPLAY\r\n";
								$html .= "DESCRIPTION: " . JText::_("RSVP_REMINDER") . $a->title() . "\r\n";
								$html .= "END:VALARM\r\n";
							}
							$html .= "END:VEVENT\r\n";
						}
					}
				}
			}
			$html .= "END:VCALENDAR";
			$message = $html;
		}
		else
		{
			$message = str_replace("{CREATOR}", $creator, $message);
			$message = str_replace("{REPEATSUMMARY}", $row->repeatSummary(), $message);
			$message = str_replace("{DESCRIPTION}", $row->content(), $message);
			$message = str_replace("{EXTRA}", $row->extra_info(), $message);
			$message = str_replace("{CONTACT}", $row->contact_info(), $message);
			$message = str_replace("{USERNAME}", isset($currentinvitee->username) ? $currentinvitee->username : $name, $message);
			$regex = "#{DATE}(.*?){/DATE}#s";
			$matches = array();
			preg_match($regex, $message, $matches);
			if (count($matches) == 2)
			{
				jimport('joomla.utilities.date');
				$date = new JevDate($row->getUnixStartDate());
				$message = preg_replace($regex, $date->toFormat($matches[1]), $message);
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
			$regex = "#{LINK}(.*?){/LINK}#s";
			preg_match($regex, $message, $matches);
			if (count($matches) == 2)
			{
				$Itemid = JRequest::getInt("Itemid");
				list($year, $month, $day) = JEVHelper::getYMD();
				// Do NOT use SEF because not consistent between frontend and backend!
				$link = $row->viewDetailLink($year, $month, $day, false, $Itemid);
				// make into frontend link!
				if (strpos($link, "/administrator") === 0)
				{
					$link = substr($link, 14);
				}
				$uri = JURI::getInstance();
				//$prefix = $uri->toString( array('scheme','host', 'port'));
				$prefix = JURI::root();
				/*
				  if (strpos($link,"/")===0) {
				  $link = substr($link,1);
				  }
				 */
				// backend doesn't add the / in the URL so fix this
				if (substr($link, 0, 1) != "/" && substr($prefix, strlen($prefix) - 1, 1) != "/")
				{
					$prefix .= "/";
				}
				else if (substr($link, 0, 1) == "/" && substr($prefix, strlen($prefix) - 1, 1) == "/")
				{
					$prefix = substr($prefix, 0, strlen($prefix) - 1);
				}
				$link = $prefix . $link;
				if ($requirelogin && false)
				{
					if (strpos($link, "?") > 0)
					{
						$link .= "&login=1";
					}
					else
					{
						$link .= "?login=1";
					}
				}
				if (isset($currentinvitee->user_id) && $currentinvitee->user_id == 0)
				{
					$params = JComponentHelper::getParams("com_rsvppro");
					if ($params->get("attendemails", 0))
					{
						$emailaddress = $currentinvitee->email_address;
						$em = base64_encode($emailaddress . ":" . md5($params->get("emailkey", "email key") . $emailaddress . "invited"));
						// use em2 since em implies attendance confirmation !!
						if (strpos($link, "?") > 0)
						{
							$link .= "&em2=$em";
						}
						else
						{
							$link .= "?em2=$em";
						}
					}
				}
				/*
				 // experiment in allowing joomla users to automatically access invitee only events directly.
				else if (isset($currentinvitee->user_id) && $currentinvitee->user_id > 0)
				{
					$currentInvteeUser = JFactory::getUser($currentinvitee->user_id);
					$emailaddress = $currentInvteeUser->email;
					$em = base64_encode($emailaddress . ":" . md5($params->get("emailkey", "email key") . $emailaddress . "invited"));
					// use em2 since em implies attendance confirmation !!
					if (strpos($link, "?") > 0)
					{
						$link .= "&em2=$em";
					}
					else
					{
						$link .= "?em2=$em";
					}
				}
				*/
				$message = preg_replace($regex, "<a href='$link'>" . $matches[1] . "</a>", $message);
			}
			// convert relative to absolute URLs
			$message = preg_replace('#(href|src|action|background)[ ]*=[ ]*\"(?!(https?://|\#|mailto:|/))(?:\.\./|\./)?#', '$1="' . JURI::root(), $message);
			$message = preg_replace('#(href|src|action|background)[ ]*=[ ]*\"(?!(https?://|\#|mailto:))/#', '$1="' . JURI::root(), $message);
			$message = preg_replace("#(href|src|action|background)[ ]*=[ ]*\'(?!(https?://|\#|mailto:|/))(?:\.\./|\./)?#", "$1='" . JURI::root(), $message);
			$message = preg_replace("#(href|src|action|background)[ ]*=[ ]*\'(?!(https?://|\#|mailto:))/#", "$1='" . JURI::root(), $message);
			include_once(JPATH_SITE . "/components/com_jevents/views/default/helpers/defaultloadedfromtemplate.php");
			ob_start();
			DefaultLoadedFromTemplate(false, false, $row, 0, $message);
			$newmessage = ob_get_clean();
			if ($newmessage != "" && strpos($newmessage, "<script ") === false)
			{
				$message = $newmessage;
			}
		}

		// Stop email cloaking in email messages
		$message =str_replace(array("{emailcloak=off}", "{* emailcloak=off}"), "", $message);

		return $message;

	}

	public
			function getBCC($userid)
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
		}
		return $bcc;

	}

	public
			function sendMail($from, $fromname, $recipient, $subject, $body, $mode = 0, $cc = null, $bcc = null, $attachment = null, $replyto = null, $replytoname = null)
	{
		$params = JComponentHelper::getParams("com_rsvppro");
		$from = $params->get("overridesenderemail", $from);
		$fromname = $params->get("overridesendername", $fromname);
		if ($this->params->get("invite_icals", 0) == 1 && strpos($body, "BEGIN:VCALENDAR") !== false)
		{
			//OK time to set the iCal email stuff so email Apps read it as an iCalendar Message
			$mail = JFactory::getMailer();
			$mail->Encoding = "7bit";
			$mail->CharSet = "UTF-8";
			$mail->ContentType = "text/calendar; method=REQUEST";
			//Below is used for custom headers, although Joomla! strips body headers, so cannot use the below too include an email/iCal message.
			//$mail->AddCustomHeader("Content-Disposition:","attachment; filename=event-invite.ics" );
			$body = $body;

			return $mail->sendMail($from, $fromname, $recipient, $subject, $body, 0, $cc, $bcc, $attachment, $replyto, $replytoname);
		}
		else
		{
			$mail = JFactory::getMailer();

			// Set AltBody so we get plain text version in the message too!
			if (JevJoomlaVersion::isCompatible("3.4"))
			{
				$mail->AltBody = $mail->normalizeBreaks($mail->html2text($body, true));
			}
			else
			{
				$textMsg = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s', '', $body)));
				if (!empty($textMsg))
				{
					$mail->AltBody = html_entity_decode($textMsg, ENT_QUOTES, $mail->CharSet);
				}
			}

			return $mail->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);
		}

	}

	// Special methods ONLY user for iCal invitations
	protected
			function setDescription($desc)
	{
		// TODO - run this through plugins first ?
		$icalformatted = JRequest::getInt("icf", 0);
		if (!$icalformatted)
			$description = $this->replacetags($desc);
		else
			$description = $desc;
		// wraplines	from vCard class
		$cfg = JEVConfig::getInstance();
		if ($cfg->get("outlook2003icalexport", 0))
		{
			return "DESCRIPTION:" . $this->wraplines($description, 76, false);
		}
		else
		{
			return "DESCRIPTION;ENCODING=QUOTED-PRINTABLE:" . $this->wraplines($description);
		}

	}

	protected
			function replacetags($description)
	{
		$description = str_replace('<p>', '\n\n', $description);
		$description = str_replace('<P>', '\n\n', $description);
		$description = str_replace('</p>', '\n', $description);
		$description = str_replace('</P>', '\n', $description);
		$description = str_replace('<p/>', '\n\n', $description);
		$description = str_replace('<P/>', '\n\n', $description);
		$description = str_replace('<br />', '\n', $description);
		$description = str_replace('<br/>', '\n', $description);
		$description = str_replace('<br>', '\n', $description);
		$description = str_replace('<BR />', '\n', $description);
		$description = str_replace('<BR/>', '\n', $description);
		$description = str_replace('<BR>', '\n', $description);
		$description = str_replace('<li>', '\n - ', $description);
		$description = str_replace('<LI>', '\n - ', $description);
		$description = strip_tags($description);
		//$description 	= strtr( $description,	array_flip(get_html_translation_table( HTML_ENTITIES ) ) );
		//$description 	= preg_replace( "/&#([0-9]+);/me","chr('\\1')", $description );
		return $description;

	}

	protected
			function wraplines($input, $line_max = 76, $quotedprintable = false)
	{
		$hex = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
		$eol = "\r\n";
		$input = str_replace($eol, "", $input);
		// new version
		$output = '';
		while (JString::strlen($input) >= $line_max)
		{
			$output .= JString::substr($input, 0, $line_max - 1);
			$input = JString::substr($input, $line_max - 1);
			if (strlen($input) > 0)
			{
				$output .= $eol . " ";
			}
		}
		if (strlen($input) > 0)
		{
			$output .= $input;
		}
		return $output;
		$escape = '=';
		$output = '';
		$outline = "";
		$newline = ' ';
		$linlen = JString::strlen($input);
		for ($i = 0; $i < $linlen; $i++)
		{
			$c = JString::substr($input, $i, 1);
			/*
			  $dec = ord($c);
			  if (!$quotedprintable) {
			  if (($dec == 32) && ($i == ($linlen - 1))) { // convert space at eol only
			  $c = '=20';
			  } elseif (($dec == 61) || ($dec < 32 ) || ($dec > 126)) { // always encode "\t", which is *not* required
			  $h2 = floor($dec / 16);
			  $h1 = floor($dec % 16);
			  $c = $escape . $hex["$h2"] . $hex["$h1"];
			  }
			  }
			 */
			if ((strlen($outline) + 1) >= $line_max)
			{ // CRLF is not counted
				$output .= $outline . $eol . $newline; // soft line break; "\r\n" is okay
				$outline = $c;
				//$newline .= " ";
			}
			else
			{
				$outline .= $c;
			}
		} // end of for
		$output .= $outline;
		return trim($output);

	}

	protected
			function vtimezone($icalEvents)
	{
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		$tzid = "";
		if (is_callable("date_default_timezone_set"))
		{
			$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
			$tz = $params->get("icaltimezonelive", "");
			if ($tz == "")
			{
				return "";
			}
			$current_timezone = $tz;
			// Do the Timezone definition
			// replace any spaces with _ underscores
			$current_timezone = str_replace(" ", "_", $current_timezone);
			$tzid = ";TZID=$current_timezone";
			// find the earliest start date
			$firststart = false;
			foreach ($icalEvents as $a)
			{
				if (!$firststart || $a->getUnixStartTime() < $firststart)
					$firststart = $a->getUnixStartTime();
			}
			// Subtract 1 leap year to make sure we have enough transitions
			$firststart -= 31622400;
			$timezone = new DateTimeZone($current_timezone);
			if (version_compare(PHP_VERSION, "5.3.0") >= 0)
			{
				$transitions = $timezone->getTransitions($firststart);
			}
			else
			{
				$transitions = $timezone->getTransitions();
			}
			$tzindex = 0;
			while (isset($transitions[$tzindex]) && JevDate::strtotime($transitions[$tzindex]['time']) < $firststart)
			{
				$tzindex++;
			}
			$transitions = array_slice($transitions, $tzindex);
			if (count($transitions) >= 2)
			{
				$lastyear = $params->get("com_latestyear", 2020);
				echo "BEGIN:VTIMEZONE\r\n";
				echo "TZID:$current_timezone\r\n";
				for ($t = 0; $t < count($transitions); $t++)
				{
					$transition = $transitions[$t];
					if ($transition['isdst'] == 0)
					{
						if (JevDate::strftime("%Y", $transition['ts']) > $lastyear)
							continue;
						echo "BEGIN:STANDARD\r\n";
						echo "DTSTART:" . JevDate::strftime("%Y%m%dT%H%M%S\r\n", $transition['ts']);
						if ($t < count($transitions) - 1)
						{
							echo "RDATE:" . JevDate::strftime("%Y%m%dT%H%M%S\r\n", $transitions[$t + 1]['ts']);
						}
						// if its the first transition then assume the old setting is the same as the next otherwise use the previous value
						$prev = $t;
						$prev += ( $t == 0) ? 1 : -1;
						$offset = $transitions[$prev]["offset"];
						$sign = $offset >= 0 ? "+" : "-";
						$offset = abs($offset);
						$offset = $sign . sprintf("%04s", (floor($offset / 3600) * 100 + $offset % 60));
						echo "TZOFFSETFROM:$offset\r\n";
						$offset = $transitions[$t]["offset"];
						$sign = $offset >= 0 ? "+" : "-";
						$offset = abs($offset);
						$offset = $sign . sprintf("%04s", (floor($offset / 3600) * 100 + $offset % 60));
						echo "TZOFFSETTO:$offset\r\n";
						echo "TZNAME:$current_timezone " . $transitions[$t]["abbr"] . "\r\n";
						echo "END:STANDARD\r\n";
					}
				}
				for ($t = 0; $t < count($transitions); $t++)
				{
					$transition = $transitions[$t];
					if ($transition['isdst'] == 1)
					{
						if (JevDate::strftime("%Y", $transition['ts']) > $lastyear)
							continue;
						echo "BEGIN:DAYLIGHT\r\n";
						echo "DTSTART:" . JevDate::strftime("%Y%m%dT%H%M%S\r\n", $transition['ts']);
						if ($t < count($transitions) - 1)
						{
							echo "RDATE:" . JevDate::strftime("%Y%m%dT%H%M%S\r\n", $transitions[$t + 1]['ts']);
						}
						// if its the first transition then assume the old setting is the same as the next otherwise use the previous value
						$prev = $t;
						$prev += ( $t == 0) ? 1 : -1;
						$offset = $transitions[$prev]["offset"];
						$sign = $offset >= 0 ? "+" : "-";
						$offset = abs($offset);
						$offset = $sign . sprintf("%04s", (floor($offset / 3600) * 100 + $offset % 60));
						echo "TZOFFSETFROM:$offset\r\n";
						$offset = $transitions[$t]["offset"];
						$sign = $offset >= 0 ? "+" : "-";
						$offset = abs($offset);
						$offset = $sign . sprintf("%04s", (floor($offset / 3600) * 100 + $offset % 60));
						echo "TZOFFSETTO:$offset\r\n";
						echo "TZNAME:$current_timezone " . $transitions[$t]["abbr"] . "\r\n";
						echo "END:DAYLIGHT\r\n";
					}
				}
				echo "END:VTIMEZONE\r\n";
			}
		}
		return $tzid;

	}

}
