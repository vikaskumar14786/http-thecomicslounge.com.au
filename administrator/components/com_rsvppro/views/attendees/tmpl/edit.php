<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: overview.php 1676 2010-01-20 02:50:34Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');

JHtml::_('behavior.tooltip');

JHtml::_('bootstrap.framework');
JHtml::_('bootstrap.loadCss');
JHtml::stylesheet('components/com_rsvppro/assets/css/rsvpattend.css');
JHtml::script('components/com_rsvppro/assets/js/tabs.js');

$db = JFactory::getDBO();

$emailaddress = $this->attendee->email_address;
if ($this->attendee->user_id > 0) {
    $auser = JEVHelper::getUser($this->attendee->user_id);
    $attendeename = $auser->name;
    $username = $auser->username;
} else {
    $attendeename = "";
}
$html = "";

$db = JFactory::getDBO();

// Until we incorporate registration deadline we stop registrations from the time the event starts
jimport('joomla.utilities.date');

// Must use strtotime format for force JevDate to not just parse the date itself!!!
$jnow = new JevDate("+1 second");
$now = $jnow->toUnix();
?>
<script type="text/javascript">

    function doTheform(pressbutton) {
        if (pressbutton) {
            document.updateattendance.task.value = pressbutton;
        }
        if (typeof document.updateattendance.onsubmit == "function") {
            document.updateattendance.onsubmit();
        }
        document.updateattendance.submit();
    }

    function submitbutton(pressbutton) {
        if (pressbutton == 'attendees.overview') {
            doTheform(pressbutton);
            return;
        }
        var form = document.updateattendance;
        // do field validation
        if (form.user_id.value == "0" && form.jevattend_email.value == "") {
            alert("<?php echo JText::_('RSVP_MISSING_USER_AND_EMAIL', true); ?>");
            return false;
        }
        else if (typeof(jevrsvpRequiredFields) !== "undefined" && !jevrsvpRequiredFields.verify(form)) {
            return false;
        }
        else {
            // sets the date for the page after save
            doTheform(pressbutton);
        }
    }
	Joomla.submitbutton = submitbutton;

</script>
<div id="jevents">
    <?php
    if (isset($this->warning)) {
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

    if (!empty($this->sidebar)) {
        ?>
        <div id="j-sidebar-container" class="span2">

            <?php echo $this->sidebar; ?>

        </div>
    <?php }
    $mainspan = 10;
    $fullspan = 12;

    ?>
    <div id="j-main-container" class="span<?php echo (!empty($this->sidebar)) ? $mainspan : $fullspan; ?>  ">
        <div id="message" class="well well-small clearfix ">
            <?php
            // We see if regisrations are open
            // if attendance tracked for the event as a whole then must compare the time of the start of the event
            if ($this->rsvpdata->allrepeats) {
                $regclose = $this->rsvpdata->regclose == "0000-00-00 00:00:00" ? $this->repeat->dtstart() : strtotime($this->rsvpdata->regclose);
                $regopen = $this->rsvpdata->regopen == "0000-00-00 00:00:00" ? strtotime("-1 year") : strtotime($this->rsvpdata->regopen);
                if ($now > $regclose) {
                    $html .= "<h3>" . JText::_("JEV_REGISTRATIONS_CLOSED") . "</h3>";
                } else if ($now < $regopen) {
                    $html .= "<h3>" . JText::_("JEV_REGISTRATIONS_NOT_YET_OPEN") . "</h3>";
                }
            } // otherwise the start of the repeat
            else {
                $regclose = $this->rsvpdata->regclose == "0000-00-00 00:00:00" ? $this->repeat->dtstart() : strtotime($this->rsvpdata->regclose);
                $regopen = $this->rsvpdata->regopen == "0000-00-00 00:00:00" ? strtotime("-1 year") : strtotime($this->rsvpdata->regopen);
                $eventstart = $this->repeat->dtstart();
                $repeatstart = $this->repeat->getUnixStartTime();
                $adjustedregclose = $regclose + ($repeatstart - $eventstart);
                $adjustedregopen = $regopen + ($repeatstart - $eventstart);
                if ($now > $adjustedregclose) {
                    $html .= "<h3>" . JText::_("JEV_REGISTRATIONS_CLOSED") . "</h3>";
                } else if ($now < $adjustedregopen) {
                    $html .= "<h3>" . JText::_("JEV_REGISTRATIONS_NOT_YET_OPEN") . "</h3>";
                }
            }

            // if there is an intro to the form display it here:
            if ($this->rsvpdata->attendintro != "") {
                $html .= $this->loadTemplate("intro");
            }

            // if tracking capacity find how many spaces are used up/left
            if ($this->params->get("capacity", 0) && $this->rsvpdata->capacity > 0) {

                $sql = "SELECT atdcount FROM #__jev_attendeecount as a WHERE a.at_id=" . $this->rsvpdata->id;
                if (!$this->rsvpdata->allrepeats) {
                    $sql .= " and a.rp_id=" . $this->repeat->rp_id();
                }
                $db->setQuery($sql);
                $attendeeCount = $db->loadResult();

                if ($attendeeCount >= $this->rsvpdata->capacity) {
                    // I need the attendance form if I'm administering and attending the event otherwise I can't cancel attendees!
                    $html .= "<div class='jevcapacityfull' style='font-weight:bold'>" . JText::_("JEV_EVENT_FULL") . "</div>";
                    if ($attendeeCount < $this->rsvpdata->capacity + $this->rsvpdata->waitingcapacity) {
                        $html .= "<div class='jevwaitinglist' style='font-weight:bold;color:red;'>" . JText::_("JEV_EVENT_WAITINGLIST_AVAILABLE") . "</div>";
                    }
                    $this->assign("attendeeCount", $attendeeCount);
                } else {
                    $this->assign("attendeeCount", $attendeeCount);
                    $html .= $this->loadTemplate("capacityremaing");
                }
            } else {
                $this->assign("attendeeCount", 0);
            }

            $rp_id = intval($this->repeat->rp_id());
            $atd_id = intval($this->rsvpdata->id);
            $repeating = intval($this->repeating);
            $link = "index.php?option=com_rsvppro&task=attendees.record&atd_id=$atd_id&rp_id=$rp_id&repeating=$repeating";

            $html .= '<form action="' . $link . '"  method="post"  name="updateattendance"  id="updateattendance"  enctype="multipart/form-data" >';
            $script = "JevRsvpLanguage.strings['JEV_DO_YOU_WANT_TO_CHANGE_USER']='" . JText::_("JEV_DO_YOU_WANT_TO_CHANGE_USER", true) . "';";
	   if (JFactory::getApplication()->isAdmin()){
		   $script = "JevRsvpLanguage.strings['JEV_CONTINUE_EVEN_THOUGH_NOT_ALL_REQUIRED_FIELDS_FILLED']='" . JText::_("JEV_CONTINUE_EVEN_THOUGH_NOT_ALL_REQUIRED_FIELDS_FILLED", true) . "';";
	   }
            $document = JFactory::getDocument();
            $document->addScriptDeclaration($script);

	JLoader::register('JevTypeahead', JPATH_LIBRARIES . "/jevents/jevtypeahead/jevtypeahead.php");
	$datapath = JRoute::_("index.php?option=com_rsvppro&ttoption=com_rsvppro&typeaheadtask=gwejson&file=finduser", false);
	ob_start();
	?>
	<input type="hidden" name='user_id' id='user_id' value="<?php echo  $this->attendee->user_id ;?> " />
	<div id="scrollable-dropdown-menu" style="float:left">
		<input name="userid_notused"  id="ta_userid" class="jevtypeahead" placeholder="<?php echo JText::_("RSVP_TYPE_NAME_USERNAME_OR_EMAIL");?>"  type="text" autocomplete="off" size="50">
	</div>
	<?php
	JevTypeahead::typeahead('#ta_userid', array('remote' => $datapath,
		'data_value' => 'title',
		'data_id' => 'id',
		'field_selector' => '#user_id',
		'minLength' => 2,
		'limit' => 10,
		'scrollable' => 1,
		'json' => json_encode( array('rp_id' =>$rp_id) )
		));
	$typeahead = ob_get_clean();
	
            $html .= '
	<table width="100%" cellspacing="1" class="paramlist admintable">
	<tr>
	<td width="40%" class="paramlist_key">
	<div class="jevusername">' . JText::_("JEV_ATTENDEE_SEARCH") . '</div>
	</td>
	<td class="paramlist_value">
		'.$typeahead.'
		</td>
	</tr>
';

            // if not logged in and allowing email based attendence then put in the input box
            if ($this->params->get("attendemails", 0)) {
                $html .= '
				<tr class="type0param ">
				<td width="40%" class="paramlist_key"><label for="jevattend_email">' . JText::_("JEV_OR_ATTEND_EMAIL") . '</label></td>
				<td class="paramlist_value"><input type="text" name="jevattend_email" id="jevattend_email" value="' . $emailaddress . '" size="50" onchange="return false;" /></td>
				</tr>
				';
                $registry = JRegistry::getInstance("jevents");
                $registry->set("showingemailaddress", true);
            }
            $html .= '</table>';

            $this->checkemail = "";
            if ($this->rsvpdata->allrepeats) {
                $html .= $this->loadTemplate("single");
            } // or just this repeat
            else if ($this->repeat->hasrepetition()) {
                $html .= $this->loadTemplate("repeating");
            }
            $html .= JHtml::_('form.token');
            $html .= '<input type="hidden" name="atd_id[]" value="' . $this->atd_id . "|" . $this->rp_id . '"/>';
            $html .= '<input type="hidden" name="atdee" value="' . $this->atdee . '"/>';
            $html .= '<input type="hidden" name="ev_id" value="' . $this->repeat->ev_id() . '"  id="rsvp_evid" />';
            $html .= '<input type="hidden" name="repeating" value="' . $this->repeating . '"/>';
            $html .= '<input type="hidden" name="task" value="attendees.edit"/>';
            $html .= '<input type="hidden" name="option" value="com_rsvppro"/>';
            $html .= '</form>';

            echo $html;
            // second form just for cancel button to work!
            ?>
            <form action="index.php" method="post" name="adminForm" id="adminForm">
                <input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT; ?>"/>
                <input type="hidden" name="task" value="attendees.edit"/>
                <input type="hidden" name="atd_id[]" value="<?php echo $this->atd_id . "|" . $this->rp_id; ?>"/>
                <input type="hidden" name="atdee" value="<?php echo $this->atdee ?>"/>
                <input type="hidden" name="repeating" value="<?php echo $this->repeating ?>"/>
                <?php echo JHtml::_('form.token'); ?>
            </form>
            <div class="clear"></div>
        </div>
    </div>

    <script type="text/javascript">
        window.setTimeout("setupRSVPTemplateBootstrap()", 500);

        function setupRSVPTemplateBootstrap() {
	if (typeof jQuery !="undefined"){
            (function ($) {
                // Turn radios into btn-group
                $('.radio.btn-group label').addClass('btn');
                var el = $(".radio.btn-group label:not(.active)");

                // Isis template and others may already have done this so remove these!
                $(".radio.btn-group label:not(.active)").unbind('click');

                $(".radio.btn-group label:not(.active)").click(function () {
                    var label = $(this);
                    var input = $('#' + label.attr('for'));
                    if (!input.prop('checked') && !input.prop('disabled')) {
                        label.closest('.btn-group').find("label").removeClass('active btn-success btn-danger btn-primary');
                        if (input.prop('value') != 0) {
                            label.addClass('active btn-success');
                        }
                        else {
                            label.addClass('active btn-danger');
                        }
                        input.prop('checked', true);
                    }
                });

                // Turn checkboxes into btn-group
                $('.checkbox.btn-group label').addClass('btn');

                // Isis template and others may already have done this so remove these!
                $(".checkbox.btn-group label").unbind('click');

                $(".checkbox.btn-group label").click(function (event) {
                    event || (event = window.event);
                    var label = $(this);
                    var input = $('#' + label.attr('for'));
                    //alert(label.val()+ " checked? "+input.prop('checked')+ " disabled? "+input.prop('disabled')+ " label disabled? "+label.hasClass('disabled'));
                    if (input.prop('disabled')) {
                        label.removeClass('active btn-success btn-danger btn-primary');
                        input.prop('checked', false);
                        event.stopImmediatePropagation();
                        return false;
                    }
                    if (!input.prop('checked')) {
                        if (input.prop('value') != 0) {
                            label.addClass('active btn-success');
                        }
                        else {
                            label.addClass('active btn-danger');
                        }
                    }
                    else {
                        label.removeClass('active btn-success btn-danger btn-primary');
                    }
                    // bootstrap takes care of the checkboxes themselves!
                });

                $(".btn-group input[type=checkbox]").each(function () {
                    var input = $(this);
                    input.css('display', 'none');
                });
            })(jQuery);

            initialiseRSVPTemplateBootstrapButtons();
		}
        }

        function initialiseRSVPTemplateBootstrapButtons() {
	if (typeof jQuery !="undefined"){
            (function ($) {
                // this doesn't seem to find just the checked ones!'
                //$(".btn-group input[checked=checked]").each(function() {
                $(".btn-group input").each(function () {
                    var label = $("label[for=" + $(this).attr('id') + "]");
                    var elem = $(this);
                    if (elem.prop('disabled')) {
                        label.addClass('disabled');
                        label.removeClass('active btn-success btn-danger btn-primary');
                        return;
                    }
                    label.removeClass('disabled');
                    if (!elem.prop('checked')) {
                        label.removeClass('active btn-success btn-danger btn-primary');
                        return;
                    }
                    if (elem.prop('value') != 0) {
                        label.addClass('active btn-success');
                    }
                    else {
                        label.addClass('active btn-danger');
                    }
                });

            })(jQuery);
		}
        }

    </script>
