<?php
/**
 * JEvents Locations Component for Joomla 1.5.x
 *
 * @version     $Id: jevboolean.php 1331 2010-10-19 12:35:49Z geraintedwards $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!

defined('JPATH_BASE') or die;

if (file_exists(JPATH_SITE."/libraries/joomla/form/fields/editor.php")){
	include_once(JPATH_SITE."/libraries/joomla/form/fields/editor.php");
}
	
jimport('joomla.html.editor');

class JFormFieldJevhtml extends JFormFieldEditor
{
	protected function getInput()
	{
		// Trap to stop the config from being editing from the categories page
		// Updated to redirect to the correct edit page, Joomla 3.x Config actually loads this page when configuration components.
		if (JRequest::getString("option") =="com_config"){
			$redirect_url  =  "index.php?option=com_rsvppro&task=params.edit"; // get rid of any ampersands
			$app  =  JFactory::getApplication();
			$app->redirect($redirect_url); //redirect
			exit();
		}

		//$this->value = str_replace('<br />', "\n", JText::_($this->value));
		$this->element['buttons'] = 0;
		if (JText::_($this->value) !=  $this->value){
			$this->value = JText::_($this->value);
		}
		$html =  parent::getInput();
		
		if (JRequest::getCmd("task")=="templates.edit"){			
			ob_start();
			?>
			<div>
			<h4 style='display:inline'><?php echo JText::_("RSVP_SELECT_FIELD_TO_INSERT");?> : </h4>
			<select onchange="messagesEditorPlugin.insert('<?php echo $this->id;?>fields' )" id="<?php echo $this->id;?>fields" class="messagesEditorPlugin"  >
				<option value="Select ...:">Select ...</option>
				<optgroup label="<?php echo JText::_("RSVP_EVENT_FIELDS",true);?>" >
					<option value="EVENT"><?php echo JText::_("RSVP_EVENT_TITLE");?></option>
					<option value="LINK"><?php echo JText::_("RSVP_EVENT_LINK");?></option>						
					<option value="DATE}%Y %m %d{/DATE"><?php echo JText::_("RSVP_EVENT_DATE");?></option>
					<option value="TIME}%H:%M{/TIME"><?php echo JText::_("RSVP_EVENT_TIME");?></option>
					<option value="LOCATION"><?php echo JText::_("RSVP_EVENT_LOCATION");?></option>
					<option value="CATEGORY"><?php echo JText::_("RSVP_EVENT_CATEGORY");?></option>
					<option value="REGDATE}%Y-%m-%d{/REGDATE"><?php echo JText::_("RSVP_EVENT_REGISTRATION_DATE"); ?></option>
					<option value="REGID}Ticket Number : %s{/REGID"><?php echo JText::_("RSVP_EVENT_REGISTRATION_ID"); ?></option>
					<?php if (strpos($this->fieldname, "paymessage")>0  || strpos($this->fieldname, "pay2message") >0)  {?>
					<option value="TRANSACTIONID}%010s{/TRANSACTIONID"><?php echo JText::_("RSVP_TRANSACTION_NUMBER");?></option>
					<option value="AMOUNTPAID"><?php echo JText::_("RSVP_PAYMENTAMOUNT");?></option>
					<option value="TIMEPAID}%d %B %Y{/TIMEPAID"><?php echo JText::_("RSVP_TIMEPAYMENTMADE");?></option>
					<?php } ?>
					<?php 
					// Bad choices of variable names !
					// templatebody is manual payment gateway layout
					//  is paypal payment gateway layout				
					if ($this->fieldname=="templatebody" || $this->fieldname=="template" || $this->fieldname=="paypaltemplate" || $this->fieldname=="manualtemplate"  || $this->fieldname=="hikashoptemplate" )  {?>
					<option value="TOTALFEES"><?php echo JText::_("RSVP_TOTALFEES");?></option>						
					<option value="FEESPAID"><?php echo JText::_("RSVP_FEESPAID");?></option>						
					<option value="BALANCE"><?php echo JText::_("RSVP_BALANCE");?></option>						
					<option value="FORM"><?php echo JText::_("RSVP_PAYMENT_FORM");?></option>
					<option value="DEPOSIT"><?php echo JText::_("RSVP_DEPOSIT");?></option>
					<?php } ?>
					<option value="CREATOR"><?php echo JText::_("RSVP_EVENT_CREATOR");?></option>						
					<option value="CUSTOM"><?php echo JText::_("RSVP_EVENT_CUSTOMFIELD_SUMMARY");?></option>						
					<option value="REPEATSUMMARY"><?php echo JText::_("RSVP_EVENT_REPEATSUMMARY");?></option>						
					<option value="WAITINGMESSAGE"><?php echo JText::_("RSVP_WAITINGMESSAGE");?></option>						
					<option value="TICKETS"><?php echo JText::_("RSVP_TICKET_LINK");?></option>
					<option value="PDFTICKETS"><?php echo JText::_("RSVP_PDF_TICKETS");?></option>
					<option value="ATTENDEESUMMARY"><?php echo JText::_("RSVP_ATTENDEE_SUMMARY");?></option>
				</optgroup>
				<optgroup label="<?php echo JText::_("RSVP_TEMPLATE_FIELDS",true);?>" class="templatefields">
				</optgroup>
			</select>
			</div>
			<?php
			$html .= ob_get_clean();
		}
		else if (JRequest::getCmd("option")=="com_plugins"){
			$html = "<div style='clear:left'>".$html."</div>";
		}
		return $html;
	}
	
}