<?php
defined('_JEXEC') or die('Restricted access');
?>

<div style="margin:20px;">
	<a href="javascript:void(0);" onclick="javascript:window.print(); return false;" title="<?php echo JText::_('JEV_CMN_PRINT'); ?>" class="printticket">
	<?php echo JEVHelper::imagesite( 'printButton.png',JText::_('JEV_CMN_PRINT'));?>
	</a>
	<br/>
	<br/>
	<?php

if (isset($this->templateParams->whentickets) && count($this->templateParams->whentickets)>0 && ($this->rsvpdata->allowcancellation || $this->rsvpdata->allowchanges )){
	echo 22;
}
echo $this->attendeeParams->getTicket($this->attendee, $this->rsvpdata, $this->event);
?>
</div>
