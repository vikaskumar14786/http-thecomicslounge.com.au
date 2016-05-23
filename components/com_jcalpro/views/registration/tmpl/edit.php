<?php
/**
 * @package		JCalPro
 * @subpackage	com_jcalpro

**********************************************
JCal Pro
Copyright (c) 2006-2012 Anything-Digital.com
**********************************************
JCalPro is a native Joomla! calendar component for Joomla!

JCal Pro was once a fork of the existing Extcalendar component for Joomla!
(com_extcal_0_9_2_RC4.zip from mamboguru.com).
Extcal (http://sourceforge.net/projects/extcal) was renamed
and adapted to become a Mambo/Joomla! component by
Matthew Friedman, and further modified by David McKinnis
(mamboguru.com) to repair some security holes.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This header must not be removed. Additional contributions/changes
may be added to this header as long as no information is deleted.
**********************************************
Get the latest version of JCal Pro at:
http://anything-digital.com/
**********************************************

 */

defined('JPATH_PLATFORM') or die;

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');

JText::script('JGLOBAL_VALIDATION_FORM_FAILED');

$event_id = JCalProHelperFilter::escape($this->item->event_id ? $this->item->event_id : JFactory::getApplication()->input->get('event_id', 0, 'int'));
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task == 'registration.cancel' || document.formvalidator.isValid(document.id('registration-form'))) {
			Joomla.submitform(task, document.getElementById('registration-form'));
		}
		else {
			alert(Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED'));
		}
	}
</script>
<div id="jcl_component" class="<?php echo $this->viewClass; ?>">
	<div class="jcl_registration">
		<div class="jcl_subtitlebar">
			<div class="jcl_left"><?php echo JText::_('COM_JCALPRO_EVENT_REGISTRATION'); ?></div>
			<div class="jcl_clear"><!--  --></div>
		</div>
		<form action="<?php echo JRoute::_('index.php?option=com_jcalpro&task=registration.save&id=' . (int) $this->item->id); ?>" method="post" id="registration-form" name="adminForm" class="form-validate">
<?php

foreach ($this->form->getFieldsets() as $fieldset):
	if ('admin' == $fieldset->name) : continue;
	elseif ('hidden' == $fieldset->name) : ?>
			<div style="display:none"><?php
				foreach ($this->form->getFieldset($fieldset->name) as $name => $field) :
					echo $field->input;
				endforeach;
			?></div><?php
	else : ?>
			<h3 class="jcl_header"><?php
				echo JText::_($fieldset->label);
			?></h3><?php
		foreach ($this->form->getFieldset($fieldset->name) as $name => $field) :
			if ('jform_event_id' == $name && !JFactory::getUser()->authorise('core.edit', 'com_jcalpro')) : ?>
			<div style="display:hidden;">
				<input type="hidden" name="jform[event_id]" value="<?php echo $event_id; ?>" />
			</div>
			<?php else : ?>
			<div class="jcl_row">
				<div class="jcl_form_label jcl_left"><?php
					echo $field->label;
				?></div>
				<div class="jcl_form_element jcl_left"><?php
					echo $field->input;
				?></div>
				<div class="jcl_clear"><!--  --></div>
			</div><?php
			endif;
		endforeach;
	endif;
endforeach; ?>
			<div class="jcal_categories">
				<input class="button" type="submit" value="<?php echo JText::_('COM_JCALPRO_BUTTON_REGISTER'); ?>" />
				<?php echo JHtml::_('form.token'); ?>
				<input type="hidden" id="registration-task" name="task" value="registration.save" />
				<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
			</div>
		</form>
	</div>
<?php
// display the footer
echo JHtml::_('jcalpro.footer', $this->template);
?>
</div>
<?php echo $this->loadTemplate('debug'); ?>
