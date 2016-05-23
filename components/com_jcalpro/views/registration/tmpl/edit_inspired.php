<?php
/**
 * @package		JCalPro
 * @subpackage	files_jcaltheme_inspired

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
<div id="jcl_component" class="well edit<?php echo $this->viewClass; ?>">
	<header class="jcl_header page-header clearfix">
		<h1><?php echo JText::_('COM_JCALPRO_EVENT_REGISTRATION'); ?></h1>
	</header>
	<form action="<?php echo JRoute::_('index.php?option=com_jcalpro&task=registration.save&id=' . (int) $this->item->id); ?>" method="post" id="registration-form" name="adminForm" class="form-validate">
	
		<!-- Button Toolbar -->
		<div class="btn-toolbar">
			<div class="btn-group">
				<button class="btn btn-primary" type="submit">
					<i class="icon-ok"></i> <?php echo JText::_('COM_JCALPRO_BUTTON_REGISTER') ?>
				</button>
			</div>
			
			<input type="hidden" id="registration-task" name="task" value="registration.save" />
			<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
			<?php echo JHtml::_('form.token'); ?>
			<?php foreach ($this->form->getFieldset('hidden') as $field) echo $field->input; ?>
		</div>
		
		<!-- Tabs Fieldset -->
		<div class="jcl_fieldsets">
<?php

echo JHtml::_('jcalpro.startTabSet', 'JCalProEventTab', array('active' => 'registration'));

foreach ($this->form->getFieldsets() as $fieldset) :
	if ('admin' == $fieldset->name || 'hidden' == $fieldset->name) continue;
	echo JHtml::_('jcalpro.addTab', 'JCalProEventTab', $fieldset->name, JText::_($fieldset->label, true));
	foreach ($this->form->getFieldset($fieldset->name) as $name => $field) :
		if ('jform_event_id' == $name && !JFactory::getUser()->authorise('core.edit', 'com_jcalpro')) : ?>
					<div style="display:hidden;">
						<input type="hidden" name="jform[event_id]" value="<?php echo $event_id; ?>" />
					</div>
<?php
		else :
?>
					<div class="control-group">
						<div class="control-label">
							<?php echo $field->label; ?>
						</div>
						<div class="controls">
							<?php echo $field->input; ?>
						</div>
					</div>
			<?php
		endif;
	endforeach;

	echo JHtml::_('jcalpro.endTab');
endforeach;

echo JHtml::_('jcalpro.endTabSet');
?>

		</div>
	</form>
<?php
// display the footer
echo JHtml::_('jcalpro.footer', $this->template);
?>
</div>
<?php echo $this->loadTemplate('debug'); ?>
