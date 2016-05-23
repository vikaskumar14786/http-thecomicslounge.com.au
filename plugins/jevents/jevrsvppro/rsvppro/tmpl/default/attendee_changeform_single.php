<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

JHtml::stylesheet(  'components/com_rsvppro/assets/css/rsvpattend.css' );
JHtml::script( 'components/com_rsvppro/assets/js/tabs.js');

$user = JFactory::getUser();

$html = "";
$formclass="";
if (isset($this->attendee->attendstate)){
	if (!$this->rsvpdata->allowcancellation && $this->attending  && $this->attendee->attendstate==1){
		$html .= "<div class='jevattendstate'>".JText::_( 'JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW' )."</div>";
		$formclass="rsvp-existing-attendee";
	}
	else if ($this->attendee->attendstate==1){
		$html .= "<div class='jevattendstate'>". ($this->attendee->waiting ? JText::_('JEV_YOU_ARE_ON_WAITINGLIST') :  JText::_('JEV_YOU_ARE_ATTENDING')) ."<br/>";
		$html .=JText::_( 'JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW' )."</div>";
		$formclass="rsvp-existing-attendee";
	}
	else if ($this->attendee->attendstate==0){
		$html .="<div class='jevattendstate'>".JText::_( 'JEV_ARE_NOT_ATTENDING' )."<br/>";
		$html .=JText::_( 'JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW' )."</div>";
		//$html .="<div class='jevattendstate'>".JText::_( 'JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW' )."</div>";
	}
	else if ($this->attendee->attendstate==2){
		$html .="<div class='jevattendstate'>";
		$html .=JText::_( 'JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW' )."</div>";
	}
	else if ($this->attendee->attendstate==4){
		$html .="<div class='jevattendstate'>" . JText::_('JEV_TO_CHANGE_YOUR_RESITRATION_USE_THE_FORM_BELOW') . "</div>";
		$formclass="rsvp-existing-attendee";
	}
}

$html .= '<form action="' . $this->link . '"  method="post"  name="updateattendance"  enctype="multipart/form-data" class="'.$formclass.'" >';
$html .=  JHtml::_('form.token');

// New parameterised fields
$hasparams = false;
if ($this->rsvpdata->template != "")
{
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
	if (is_int($xmlfile) || file_exists($xmlfile) )	{

		if (isset($this->attendee) && isset($this->attendee->params))
		{
			$params = new JevRsvpParameter($this->attendee->params, $xmlfile, $this->rsvpdata, $this->row);
			$feesAndBalances = $params->outstandingBalance($this->attendee);
		}
		else
		{
			$params = new JevRsvpParameter("", $xmlfile, $this->rsvpdata, $this->row);
		}

		// set the potential attendee in the params - needed for rendering
		$params->potentialAttendee = $user;

		// Add reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = JRegistry::getInstance("jevents");
		$registry->set("rsvpdata", $this->rsvpdata);
		$registry->set("event", $this->row);

		JHtml::_('behavior.tooltip');
		if ($params->getNumParams() > 0)
		{

			$attendstate = $this->params->get("defaultattendstate", -1);
			if (isset($this->attendee->attendstate))
				$attendstate = $this->attendee->attendstate;
			$initialstate = $this->rsvpdata->initialstate ? 1 : 3;
			
			// if subject to payment or approval then must be a yes!
			if ($attendstate==4 || $attendstate==3) {
				$attendstate = 1;
			}
			
			if ($params->isMultiAttendee())
			{
				$html .= '<div id="registration-tab-pane" class="tab-page">';
				$html .= '<ul class="nav nav-tabs">';
				$html .= '<li title="' . JText::_("JEV_PRIMARY_ATTENDEE", true) . '" class="active"><a href="#attendeetab0" data-toggle="tab">' . JText::_( 'JEV_PRIMARY_ATTENDEE' ) . '</a></li>';
				$currentattenddees = $params->curentAttendeeCount();
				if ($currentattenddees > 0)
				{
					for ($ca = 1; $ca < $currentattenddees; $ca++)
					{
						$html .= '<li title="' . addslashes(JText::sprintf("JEV_ATTENDEE_NUMBER", $ca + 1)) . '" ><a href="#attendeetab'.$ca.'" data-toggle="tab">' . JText::sprintf("JEV_ATTENDEE_NUMBER", $ca + 1) . '</a></li>';
					}
				}
				$html .= '</ul>';
				$html .= '<div class="tab-content">';
				$html .= '<div class="tab-pane active" id="attendeetab0">';

				$byemail = $this->loadTemplate("byemail");

				$html .= $params->render('params',  "xmlfile", array(), $byemail );

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
				<div id="killguest" >
					<div class="button2-left" >
						<div class="blank">
							<a style="padding: 0px 5px; text-decoration: none;" title="' . JText::_("RSVP_REMOVE_GUEST", true) . '" onclick="removeGuest('.$capacity.');return false;" href="javascript:void();">' . JText::_( 'RSVP_REMOVE_GUEST' ) . '</a>
						</div>
					</div>
			    </div>
		    </div>
			<br/>
					';
					// labels for new guest tab
					$html .= '<input type="hidden" id="jevnexttabtitle" value="' . addslashes(JText::sprintf("JEV_ATTENDEE_NUMBER", 'xxx')) . '" />';

				}

				$this->initialstate = $initialstate;
				$this->attendstate = $attendstate;
				$attendyes =  '<input type="hidden" name="jevattend" id="jevattend_yes" value="'.$attendstate.'" />';

				// Attend this event
				$html .= $attendyes ;

				$html .= '</div>';// tab-pane
				$html .= '</div>';// tab-content
				$html .= '</div>';// tab-page

				JFactory::getDocument()->addScriptDeclaration('jQuery(document).ready(function() {regTabs.initialise("registration-tab-pane",{mouseOverClass:"active",	activateOnLoad:"attendeetab0"	});});');
			}
			else
			{
				$this->initialstate = $initialstate;
				$this->attendstate = $attendstate;
				$attendyes =  '<input type="hidden" name="jevattend" id="jevattend_yes" value="'.$attendstate.'" />';

				$byemail = $this->loadTemplate("byemail");

				$html .= $params->render('params', "xmlfile",array('',$attendyes), $byemail 	);
			}
			$hasparams = true;
		}
	}
}
else
{
	$html .= '<table width="100%" class="paramlist admintable" cellspacing="1">';
	$html .= $this->loadTemplate("byemail");
	$html .= '</table>';

	if (isset($this->attendee) && isset($this->attendee->params))
	{
		$params = new JevRsvpParameter($this->attendee->params, null, $this->rsvpdata, $this->row);
		$feesAndBalances = $params->outstandingBalance($this->attendee);
	}
	else
	{
		$params = new JevRsvpParameter("", null, $this->rsvpdata, $this->row);
	}
}

// guest count
$html .='<input type="hidden" name="guestcount" id="guestcount" value="' . (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1) . '" />';
$html .='<input type="hidden" name="lastguest" id="lastguest" value="' . (isset($this->attendee->guestcount) ? $this->attendee->guestcount : 1) . '" />';


if ($hasparams)
{
	$this->attendstate = $attendstate;
	$html .=  $this->loadTemplate("attendanceform_updateattendbutton");
	$html .='<noscript><input type="submit" value="' . JText::_( 'JEV_CONFIRM' ) . '" /></noscript>';
	$html .='<input type="hidden" name="Itemid"  value="' .  JRequest::getInt("Itemid" ,1) . '" />';
	$html .='</form>';
}
else
{
	$attendstate = $this->params->get("defaultattendstate", -1);
	if (isset($this->attendee->attendstate))
		$attendstate = $this->attendee->attendstate;
	$initialstate = $this->rsvpdata->initialstate ? 1 : 3;

	// if subject to payment then must be a yes!
	if ($attendstate==4) {
		$attendstate = 1;
	}
	
	$this->initialstate = $initialstate;
	$this->attendstate = $attendstate;

	$html .= $this->loadTemplate("attendanceform_attendyesnomaybe");
	$this->attendstate = $attendstate;
	$html .=  $this->loadTemplate("attendanceform_updateattendbutton");
	$html .='<input type="hidden" name="Itemid"  value="' .  JRequest::getInt("Itemid" ,1) . '" />';
	$html .='
		<noscript><input type="submit" value="' . JText::_( 'JEV_CONFIRM' ) . '" /></noscript>
</form>';
}

echo $html;
