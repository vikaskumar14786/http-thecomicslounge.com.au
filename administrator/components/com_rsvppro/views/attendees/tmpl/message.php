<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: edit.php 1438 2009-05-02 09:25:42Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');

$editor =  JFactory::getEditor();
?>		
<div class='jevrsvppro'>
	<div id="jevents">
        <?php
        if (isset($this->warning))
        {
            ?>
            <dl id="system-message">
                <dt class="notice">Message</dt>
                <dd class="notice">
                    <ul>
                        <li><?php echo $this->warning; ?></li>
                    </ul>
                </dd>
            </dl>
        <?php
        }
        ?>
		<form action="index.php" method="post" name="adminForm"  id="adminForm"  >
            <?php if (!empty($this->sidebar))
            {
                ?>
                <div id="j-sidebar-container" class="span2">

                    <?php echo $this->sidebar; ?>

                </div>
            <?php }
            $mainspan = 10;
            $fullspan = 12;

            ?><div id="j-main-container" class="span<?php echo (!empty($this->sidebar)) ? $mainspan : $fullspan; ?>  ">
            <div id="messagearea" class="well well-small clearfix ">
						<input type="hidden" name="customise" value="<?php echo JRequest::getInt("customise"); ?>" />
						<?php foreach ($this->cid as $cid){ ?>
						<input name="cid[]" type="hidden" value ="<?php echo $cid; ?>" />
						<?php } ?>
						<input type="hidden" name="rp_id" id="evid" value="<?php echo $this->rp_id; ?>" />
						<input type="hidden" name="atd_id[]" value="<?php echo $this->atd_id . "|" . $this->rp_id ;?>" />
						<input type="hidden" name="repeating" value="<?php echo $this->repeating; ?>" />
						
						<script type="text/javascript" >
							Joomla.submitbutton = function (pressbutton) {
								if (pressbutton == 'cancel' || pressbutton == 'attendees.overview') {
									submitform( pressbutton );
									return;
								}
								var form = document.adminForm;
<?php
echo $editor->getContent('message');
echo $editor->save('message');
?>
		// do field validation
		if (form.message.value == "") {
			alert ( "<?php echo html_entity_decode(JText::_('RSVP_MISSING_MESSAGE')); ?>" );
		}
		else {
			submitform(pressbutton);
		}
	}

						</script>
						<div class="adminform" align="left">
							<h4><?php echo JText::_("RSVP_EDIT_ATTENDEE_MESSAGE_SUBJECT"); ?> : </h4>
							<input name="subject" id="subject" value="<?php echo htmlspecialchars($this->subject, ENT_QUOTES, 'UTF-8');?> "  size="80" maxlength="250"/>
							<h4><?php echo JText::_("RSVP_EDIT_ATTENDEE_MESSAGE_BODY"); ?> : </h4>
							<?php
							echo $editor->display('message', htmlspecialchars($this->message, ENT_QUOTES, 'UTF-8'), 500, 150, '70', '10', false);
							?>
							<h4><?php echo JText::_("RSVP_SELECT_FIELD_TO_INSERT"); ?> : </h4>
							<select onchange="ticketsEditorPlugin.insert('message','messagefields' )" id="messagefields">
								<option value="Select ...:">Select ...</option>
								<optgroup label="<?php echo JText::_("RSVP_EVENT_FIELDS", true); ?>" >									
									<option value="NAME"><?php echo JText::_("RSVP_ATTENDEE_NAME"); ?></option>
									<option value="EVENT"><?php echo JText::_("RSVP_EVENT_TITLE"); ?></option>
									<option value="DATE}%Y %m %d{/DATE"><?php echo JText::_("RSVP_EVENT_DATE"); ?></option>
									<option value="LOCATION"><?php echo JText::_("RSVP_EVENT_LOCATION"); ?></option>
									<option value="CATEGORY"><?php echo JText::_("RSVP_EVENT_CATEGORY");?></option>
									<option value="LINK}{EVENT}{/LINK"><?php echo JText::_("RSVP_EVENT_LINK"); ?></option>
									<option value="CREATOR"><?php echo JText::_("RSVP_EVENT_CREATOR"); ?></option>						
									<option value="REPEATSUMMARY"><?php echo JText::_("RSVP_EVENT_REPEATSUMMARY"); ?></option>
									<option value="TICKETS"><?php echo JText::_("RSVP_TICKET_LINK");?></option>
									<option value="PDFTICKETS"><?php echo JText::_("RSVP_PDF_TICKETS");?></option>
									<option value="ATTENDEESUMMARY"><?php echo JText::_("RSVP_ATTENDEE_SUMMARY");?></option>
								</optgroup>
								<!--
								<optgroup label="<?php echo JText::_("RSVP_TEMPLATE_FIELDS", true); ?>" class="templatefields">
								</optgroup>
								//-->
							</select>
							<h4><?php echo JText::_("JEV_COPY_MESSAGE_TO_USER"); ?> : </h4>
							<input name="messagebcc" id="messagebcc" value=""  size="40" maxlength="250"/>


						</div>
                <div class="clear"></div>
            </div>
    </div>
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="task" value="attendees.message" />
			<input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT; ?>" />
			<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt("Itemid", 0); ?>" />
		</form>
	</div>
</div>