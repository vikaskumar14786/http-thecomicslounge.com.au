<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');

$html = "";

// New parameterised fields
$hasparams = false;
if ($this->rsvpdata->template != "")
{
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);

	if (is_int($xmlfile) && $this->attendee->lockedtemplate != 0 && $this->attendee->lockedtemplate != $xmlfile) {

		$html .= "<h3>".JText::_("RSVP_SESSION_TEMPLATE_CHANGED_RECREATE_ENTRY")."</h3>";
		$lockedXmlfile = $this->attendee->lockedtemplate;

		if (isset($this->attendee->params))
		{
			$lockedparams = new JevRsvpParameter($this->attendee->params, $lockedXmlfile, $this->rsvpdata, $this->repeat);
			$feesAndBalances = $lockedparams->outstandingBalance($this->attendee);
		}
		else
		{
			$lockedparams = new JevRsvpParameter("", $lockedXmlfile, $this->rsvpdata, $this->repeat);
		}

		$paramsarray = $lockedparams->renderToBasicArray('xmlfile', $this->attendee);

		if (count($paramsarray) > 0)
		{
			// we have guests
			if ($this->attendee->guestcount > 0)
			{
				$html .= '<table class="attendeesummary" style="border:none;">';
				$html .= '<tr>';
				$html .= '<th />';
				$html .= '<th>' . JText::_('JEV_PRIMARY_ATTENDEE') . '</th>';
				for ($guest = 1; $guest < $this->attendee->guestcount; $guest++)
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
					if ($param['peruser'] == 2 && $this->attendee->guestcount <= 1)
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
					$html .='<td class="rsvpoptionlabel">' . stripslashes(JText::_($param['label'])) . ' : </td>';
					if (!isset($param['peruser']) || $param['peruser'] <= 0)
					{
						$val = $param['value'];
						if (is_array($val))
						{
							$val = implode(",", $val);
						}
						$val = stripslashes($val);
						$html .='<td class="rsvpgroupoptionvalue" colspan="' . $this->attendee->guestcount . '">' . $val . '</td>';
					}
					else
					{

						// fix non-array values
						// TODO find why this is happening on pistol site
						if (!is_array($param['value']) && $this->attendee->guestcount==1){
							$param['value'] = array($param['value']);
						}
						if (is_array($param['value']) && count($param['value']) == $this->attendee->guestcount)
						{
							for ($guest = 0; $guest < $this->attendee->guestcount; $guest++)
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
							$html .='<td class="rsvpproblemvalue" colspan="' . $this->attendee->guestcount . '">' . JText::_('JEV_PROBLEM_CONTACT_ORGANISER') . '</td>';
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

					$html .='<span class="rsvpoptionlabel">' . stripslashes(JText::_($param['label'])) . ' : </span>';
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

	}

	if (is_int($xmlfile) || file_exists($xmlfile))
	{
		if (isset($this->attendee) && isset($this->attendee->params))
		{
			$params = new JevRsvpParameter($this->attendee->params, $xmlfile, $this->rsvpdata, $this->repeat);
			$feesAndBalances = $params->outstandingBalance($this->attendee);
		}
		else
		{
			$params = new JevRsvpParameter("", $xmlfile, $this->rsvpdata, $this->repeat);
		}

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $this->rsvpdata);
		$registry->set("event", $this->repeat);

		JHtml::_('behavior.tooltip');
		if ($params->getNumParams() > 0)
		{

			$attendstate = -1;
			if (isset($this->attendee->attendstate))
				$attendstate = $this->attendee->attendstate;
			$initialstate = $this->rsvpdata->initialstate ? 1 : 3;

			if ($params->isMultiAttendee())
			{
				$html .= '<div id="registration-tab-pane" class="tab-page">';
				$html .= '<ul class="nav nav-tabs">';
				$html .= '<li title="' . JText::_("JEV_PRIMARY_ATTENDEE", true) . '" class="active"><a href="#attendeetab0" data-toggle="tab">' . JText::_('JEV_PRIMARY_ATTENDEE') . '</a></li>';
				$currentattenddees = $params->curentAttendeeCount();
				if ($currentattenddees > 0)
				{
					for ($ca = 1; $ca < $currentattenddees; $ca++)
					{
						$html .= '<li title="' . addslashes(JText::sprintf("JEV_ATTENDEE_NUMBER", $ca + 1)) . '"><a href="#attendeetab'.$ca.'" data-toggle="tab">' . JText::sprintf("JEV_ATTENDEE_NUMBER", $ca + 1) . '</a></li>';
					}
				}
				$html .= '</ul>';
				$html .= '<div class="tab-content">';
				$html .= '<div class="tab-pane active" id="attendeetab0">';

				$html .= $params->render('params', "xmlfile");

				$waitingcapacity = $this->rsvpdata->capacity+$this->rsvpdata->waitingcapacity-$this->attendeeCount;

				// if one some 'guests' can be added - block 'mixed' confirmed and waiting attendees
				$realcapacity = $this->rsvpdata->capacity-$this->attendeeCount;
				if (isset ($this->attendee->guestcount) && $this->attendee->guestcount>0){
					$realcapacity += $this->attendee->guestcount;
					$waitingcapacity  += $this->attendee->guestcount;
				}
				$capacity = $realcapacity>0 ? $realcapacity : $waitingcapacity;

				$sessionparams = JComponentHelper::getParams("com_rsvppro");

				if ($this->rsvpdata->capacity==0 && $this->rsvpdata->waitingcapacity==0){
					$capacity = 0;
				}
		 
				// is this constrained further by the template
				if ($sessionparams->get("maxguesttabs",0)>0 && $capacity>0){
					$capacity = min(array($sessionparams->get("maxguesttabs",0), $capacity));
				}
				else 	if ($sessionparams->get("maxguesttabs",0)>0){
					$capacity = $sessionparams->get("maxguesttabs",0);
				}

				if ($params->isMultiAttendee() )
				{
					if ($capacity==1){
						$addGuestButton = "";
					}
					else {
						$addGuestButton = '<div class="button2-left"  id="addguest" >
					<div class="blank">
						<a style="padding: 0px 5px; text-decoration: none;" title="' . JText::_("JEV_ADD_GUEST", true) . '" onclick="addGuest('.$capacity.');return false;" href="javascript:void();">' . JText::_('JEV_ADD_GUEST') . '</a>
					</div>
				</div>';
					}
					// Add new guest button
					$html .= '
			<div style="margin-top:5px;clear:left;min-height:20px;">'
				. $addGuestButton .'
				<div id="killguest" onclick="removeGuest('.$capacity.');">
					<div class="button2-left" >
						<div class="blank">
							<a style="padding: 0px 5px; text-decoration: none;" title="' . JText::_("RSVP_REMOVE_GUEST", true) . '" href="javascript:void();">
								' . JText::_("RSVP_REMOVE_GUEST") . '
							</a>
						</div>
					</div>
			    </div>
		    </div>
			<br/>
					';
					// labels for new guest tab
					$html .= '<input type="hidden" id="jevnexttabtitle" value="' . addslashes(JText::sprintf("JEV_ATTENDEE_NUMBER", 'xxx')) . '" />';
				}

				// Attend this event
				$html .= '<div style="clear:left;min-height:20px;">';
				$html .= '<strong><label>' . ($this->repeat->hasrepetition() ? JText::_("JEV_ATTEND_ALL_REPEATS") : JText::_("JEV_ATTEND_THIS_EVENT")) . '</label></strong>';
				$html .= '<div class="controls"><div class="radio btn-group">';
				$html .= '<label for="jevattend_yes"><input type="radio" name="jevattend" class="inputbox btn '.(($attendstate == 1 || $attendstate == 4)?"active":"").'" id="jevattend_yes" value="' . $initialstate . '"  ' . (($attendstate == 1 || $attendstate == 4) ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_YES") . '</label>'
						. '<label for="jevattend_no"><input type="radio" name="jevattend" class="inputbox btn '.($attendstate == 0?"active":"").'" id="jevattend_no" value="0"  ' . ($attendstate == 0 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_No") . '</label>'
						. ($this->params->get("allowmaybe", 0) ? '<label for="jevattend_maybe"><input type="radio" name="jevattend" class="inputbox btn  '.($attendstate == 2?"active":"").'" id="jevattend_maybe" value="2"  ' . ($attendstate == 2 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_MAYBE") . '</label>' : '');
				$html .= '</div>'; // attend this event

				$html .= '</div>';// tab-pane
				$html .= '</div>';// tab-content
				$html .= '</div>';// tab-page
				JFactory::getDocument()->addScriptDeclaration('jQuery(document).ready(function() {regTabs.initialise("registration-tab-pane",{mouseOverClass:"active",	activateOnLoad:"attendeetab0"	});});');
				}
			else
			{
				$html .= '<div id="registration-tab-pane" class="tab-page">';
				$html .= '<div class="tab-content">';

				$html .= $params->render('params', "xmlfile",
								array('<label>' . ($this->repeat->hasrepetition() ? JText::_("JEV_ATTEND_ALL_REPEATS") : JText::_("JEV_ATTEND_THIS_EVENT")) . '</label><br/>',
									'<div class="controls"><div class="radio btn-group">'
									. '<label for="jevattend_yes"><input type="radio" name="jevattend" class="inputbox btn  '.(($attendstate == 1 || $attendstate == 4)?"active":"").'" id="jevattend_yes" value="' . $initialstate . '"  ' . (($attendstate == 1 || $attendstate == 4) ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_YES") . '</label>'
									. '<label for="jevattend_no"><input type="radio" name="jevattend" class="inputbox btn  '.(($attendstate == 0)?"active":"").'" id="jevattend_no" value="0"  ' . ($attendstate == 0 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_No") . '</label>'
									. ($this->params->get("allowmaybe", 0) ? '<label for="jevattend_maybe"><input type="radio" name="jevattend" class="inputbox btn '.(($attendstate == 2)?"active":"").'" id="jevattend_maybe" value="2"  ' . ($attendstate == 2 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_MAYBE") . '</label>' : '')
									. '</div></div>'
								)
				);
				$html .= '</div>';
				$html .= '</div>';
			}
			$hasparams = true;
		}
	}
}
else
{
	if (isset($this->attendee) && isset($this->attendee->params))
	{
		$params = new JevRsvpParameter($this->attendee->params, null, $this->rsvpdata, $this->repeat);
		$feesAndBalances = $params->outstandingBalance($this->attendee);
	}
	else
	{
		$params = new JevRsvpParameter("", null, $this->rsvpdata, $this->repeat);
	}
}

// guest count
$html .='<input type="hidden" name="guestcount" id="guestcount" value="' . (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1) . '" />';
$html .='<input type="hidden" name="lastguest" id="lastguest" value="' . (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1) . '" />';

if ($hasparams)
{
	$html .='
	    <input type="text" name="jevattend_hidden" value="1" style="display:none"/>
	    <input type="hidden" name="jevattend_id" id="jevattend_id" value="0" />
	    <input type="hidden" name="jevattend_id_approve" id="jevattend_id_approve" value="0" />
		<br/>';
	if (isset($this->attendee) && $this->attendee !== false && ($this->attendee->user_id!=0 || $this->attendee->email_address!=""))
	{
		$html .='
		<input type="submit" value="' . JText::_("JEV_CLICK_TO_UPDATE") . '" onclick="submitbutton(\'attendees.save\');return false;" id="jevattendsubmit" />';
	}
	else
	{
		$html .='
		<input type="submit" value="' . JText::_("JEV_CLICK_TO_ATTEND") . '" onclick="submitbutton(\'attendees.save\');return false;" id="jevattendsubmit" />';
	}
	$html .='<Br/>
		<noscript><input type="submit" value="' . JText::_("JEV_CONFIRM") . '" /></noscript>
';
}
else
{
	$attendstate = -1;
	if (isset($this->attendee->attendstate))
		$attendstate = $this->attendee->attendstate;
	$initialstate = $this->rsvpdata->initialstate ? 1 : 3;

	$html .=
			'<strong>' . ($this->repeat->hasrepetition() ? JText::_("JEV_ATTEND_ALL_REPEATS") : JText::_("JEV_ATTEND_THIS_EVENT")) . '</strong><br/>' 
			. '<div class="controls"><div class="radio btn-group">'
			. '<label for="jevattend_yes"><input type="radio" name="jevattend"  class="inputbox btn '.(($attendstate == 1 || $attendstate == 4)?"active":"").'" id="jevattend_yes" value="' . $initialstate . '"  ' . ($attendstate == 1 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_YES") . '</label>'
			. '<label for="jevattend_no"><input type="radio" name="jevattend"  class="inputbox btn '.(($attendstate == 0)?"active":"").'" id="jevattend_no" value="0"  ' . ($attendstate == 0 ? "checked='checked'" : "") . ' onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_No") . '</label>'
			. ($this->params->get("allowmaybe", 0) ? '<label for="jevattend_maybe"><input type="radio" name="jevattend"  class="inputbox btn '.(($attendstate == 2)?"active":"").'" id="jevattend_maybe" value="2" ' . ($attendstate == 2 ? "checked='checked'" : "") . '  onclick="showSubmitButton();" />' . JText::_("JEV_ATTEND_MAYBE") . '</label>' : '')
			. '</div></div>'
			. '<br/>';

	if (isset($this->attendee) && $this->attendee !== false && ($this->attendee->user_id!=0 || $this->attendee->email_address!=""))
	{
		$html .='
		<input type="submit" value="' . JText::_("JEV_CLICK_TO_UPDATE") . '" onclick="submitbutton(\'attendees.save\');return false;" id="jevattendsubmit" />';
	}
	else
	{
		$style = "";//'style="display:none" ';
		if ($attendstate>=0){
			$style='';
		}
		$html .='
		<input type="submit" value="' . JText::_("JEV_CLICK_TO_SUBMIT") . '" onclick="submitbutton(\'attendees.save\');return false;" '.$style.' id="jevattendsubmit" />';
	}

	$html .='<Br/>
		<noscript><input type="submit" value="' . JText::_("JEV_CONFIRM") . '" /></noscript>'
			. '<input type="text" name="jevattend_hidden" value="1" style="display:none"/>
	    <input type="text" name="jevattend_hidden" value="1" style="display:none"/>
	    <input type="hidden" name="jevattend_id" id="jevattend_id" value="0" />
	    <input type="hidden" name="jevattend_id_approve" id="jevattend_id_approve" value="0" />
	    <!--<input type="hidden" name="tmpl" value="component" />//-->
		<noscript><input type="submit" value="' . JText::_("JEV_CONFIRM") . '" /></noscript>
	';
}

echo $html;