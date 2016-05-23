<?php

defined('JPATH_BASE') or die('Direct Access to this location is not allowed.');

$user = JFactory::getUser();
$html = "";
$this->checkemail = "";
// if not logged in and allowing email based attendence then put in the input box
if ($this->params->get("attendemails", 0) && $user->id == 0)
{
	$code = base64_encode($this->emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $this->emailaddress));
	$this->checkemail = "try {jevrsvpRequiredFields.emailcheck()}  catch (e) {if (document.getElementById('jevattend_email').value=='') {alert('" . JText::_("JEV_MISSING_EMAIL", true) . "');return false}};";

	//$html .= '<table width="100%" class="paramlist admintable" cellspacing="1">';
	$onchange = "return false;";
	$rsvpMustBeLoggedIn = "";
	// Check if the user needs to login
	if ($this->emailaddress=="" && $this->params->get("attendemailscheck", 1)){
		$client =JFactory::getApplication()->isAdmin()?"administrator":"site";
		$checkURL = JRoute::_("index.php?option=com_rsvppro&ttoption=com_rsvppro&typeaheadtask=gwejson&file=finduser&token=". JSession::getFormToken(), false);
		$onchange = "checkUserExists(event, this, '".$checkURL."', '".$client."');return false;";
		$uri = JURI::getInstance ();
		$loginhtml = JText::sprintf("JEV_EMAIL_ALREADY_IN_USE_LOGIN", JRoute::_("index.php?option=com_users&view=login&return=".base64_encode($uri->toString())));
		$rsvpMustBeLoggedIn = "<div id='rsvpMustBeLoggedIn' style='display:none'><strong>$loginhtml</strong><div>";
	}
	$html .= '<tr class="type0param ">
				<td class="paramlist_key">
					<label for="jevattend_email">' . JText::_( 'JEV_ATTEND_EMAIL' ) . JText::_("JEV_REQUIRED") .'</label>
				</td>
				<td class="paramlist_value">
				<input type="text" name="jevattend_email" id="jevattend_email" value="' . $this->emailaddress . '" size="50"'
			. ' onchange="' . $onchange . '"'
			. ' onblur="' . $onchange . '"'
			//. ' onfocus="' . $onchange . '"'
			. ' />'
			. $rsvpMustBeLoggedIn;

	if ($this->emailaddress != "")
	{
		$html .= '<input type="hidden" name="em" id="em" value="' . $code . '" />';
	}

	// if a link from an invitation email then skip the need to confirm.
	$code = base64_encode($this->emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $this->emailaddress."invited"));
	if (JRequest::getString("em2","")==$code){
		$html .= '<input type="hidden" name="em2" id="em2" value="' . $code . '" />';
	}

	$html .= '		</td>
			</tr>';

	// Do we need to offer Joomla registration too
	if ($this->params->get("autoregister", 0) && $this->emailaddress == ""){
		$script = 'function checkPasswords(form)
			{
				toggleBlankRequiredField("jevattend_username", false);
				toggleBlankRequiredField("jevattend_password", false);
				toggleBlankRequiredField("jevattend_password2", false);
				toggleBlankRequiredField("jevattend_password", "jevattend_password2");

				if(form.jevattend_username.value=="" || form.jevattend_password.value==""  || form.jevattend_password2.value==""  || form.jevattend_password.value!=form.jevattend_password2.value)  {
					if (form.jevattend_password.value!=form.jevattend_password2.value) {
					}
					return false;
				}
				return true;
			}
			function toggleBlankRequiredField(fieldid, secondfield){
				var match = document.getElementById(fieldid);
				var match2 = false;
				if (!match) return;
				var valid = true;
				if (!secondfield)
				{
					if(match.value)
					{
						return;
					}
					else
					{
						valid=false;
					}
				}
				else {
					match2 = document.getElementById(secondfield);
					if (match2.value != match.value){
						valid = false;
					}
				}
				if(!valid){
					match.style.backgroundColor="red";
					if (match2) match2.style.backgroundColor="red";
				}
				else {
					try {
						match.style.backgroundColor="inherit";
						if (match2) match2.style.backgroundColor="inherit";
					}
					catch (e){
						match.style.backgroundColor="transparent";
						if (match2) match2.style.backgroundColor="transparent";
					}
				}
			}
			'."\n";
		$script .= "jevrsvpRequiredFields.fields.push({'requiredCheckScript':'checkPasswords', 'reqmsg':'" . trim(JText::_("JEV_PASSWORDS_NOT_ENTERED_OR_DO_NOT_MATCH", true)) . "'}); ";
		JFactory::getDocument()->addScriptDeclaration($script);
		
		$html .= '<tr class="type0param ">
				<td class="paramlist_key">
					<label for="jevattend_username">' . JText::_( 'JEV_ATTEND_USERNAME' ) . JText::_("JEV_REQUIRED") .'</label>
				</td>
				<td class="paramlist_value">
				<input type="text" name="jevattend_username" id="jevattend_username" value="" size="50" onchange="return false;" />
				'.  JHtml::_('form.token') .'
				</td>
			</tr>';
		$html .= '<tr class="type0param ">
				<td class="paramlist_key">
					<label for="jevattend_password">' . JText::_( 'JEV_ATTEND_PASSWORD' ) . JText::_("JEV_REQUIRED") .'</label>
				</td>
				<td class="paramlist_value">
				<input type="password" name="jevattend_password" id="jevattend_password" value="" size="50" onchange="return false;" />
				</td>
			</tr>';
		$html .= '<tr class="type0param ">
				<td class="paramlist_key">
					<label for="jevattend_password2">' . JText::_( 'JEV_ATTEND_PASSWORD2' ) . JText::_("JEV_REQUIRED") .'</label>
				</td>
				<td class="paramlist_value">
				<input type="password" name="jevattend_password2" id="jevattend_password2" value="" size="50" onchange="return false;" />
				</td>
			</tr>';
	}

	//$html .= '</table>';


	$registry = JRegistry::getInstance("jevents");
	$registry->set("showingemailaddress", true);
}

echo $html;

/*
$user = JFactory::getUser();
$html = "";
$this->checkemail = "";
// if not logged in and allowing email based attendence then put in the input box
if ($this->params->get("attendemails", 0) && $user->id == 0)
{
	$code = base64_encode($this->emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $this->emailaddress));
	$this->checkemail = "if (document.getElementById('jevattend_email').value=='') {alert('" . JText::_("JEV_MISSING_EMAIL", true) . "');return false};";
	$html .= '
				<div class="jevattend_email type0param" >
				<label for="jevattend_email">' . JText::_( 'JEV_ATTEND_EMAIL' ) . JText::_("JEV_REQUIRED") .'</label>
				<input type="text" name="jevattend_email" id="jevattend_email" value="' . $this->emailaddress . '" size="50" onchange="return false;" />
				</div>';
	if ($this->emailaddress != "")
	{
		$html .= '<input type="hidden" name="em" id="em" value="' . $code . '" />';
	}

	// if a link from an invitation email then skip the need to confirm.
	$code = base64_encode($this->emailaddress . ":" . md5($this->params->get("emailkey", "email key") . $this->emailaddress."invited"));
	if (JRequest::getString("em2","")==$code){
		$html .= '<input type="hidden" name="em2" id="em2" value="' . $code . '" />';
	}
	$registry = JRegistry::getInstance("jevents");
	$registry->set("showingemailaddress", true);
}

echo $html;

 */