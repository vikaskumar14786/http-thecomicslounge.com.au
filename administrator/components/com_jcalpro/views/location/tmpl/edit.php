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

$input = JFactory::getApplication()->input;

$hiddenFieldset   = $this->form->getFieldset('hidden');
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task == 'location.cancel') {
			Joomla.submitform(task, document.id('location-form'));
		}
		else if (document.formvalidator.isValid(document.id('location-form'))) {
			try {
				jcl_map_refresh('jform_address', 'jform_city', 'jform_state', 'jform_country', 'jform_postal_code');
			}
			catch (err) {
				alert(err);
				return false;
			}
			if ('location.refresh' == task) {
				return false;
			}
			// this is gross, but we need to allow a little time to let the map refresh
			setTimeout(function() {
				Joomla.submitform(task, document.id('location-form'));
			}, 1000);
		}
		else {
			alert(Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED'));
		}
	}
</script>
<div id="jcl_component" class="<?php echo $this->viewClass; ?>">
	<form action="<?php echo JURI::base() . 'index.php?option=com_jcalpro&task=location.save&id=' . (int) $this->item->id; ?>" method="post" id="location-form" name="adminForm" class="form-validate">
		<?php echo $this->loadTemplate(JCalPro::version()->isCompatible('3.0') ? 'form' : 'legacy'); ?>
		<div>
			<?php foreach ($hiddenFieldset as $name => $field) echo $field->input; ?>
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="function" value="<?php echo $input->get('function', '', 'cmd'); ?>" />
			<?php if ('component' == $input->get('tmpl', '', 'cmd')) : ?>
			<input type="hidden" name="tmpl" value="component" />
			<input type="hidden" name="mlayout" value="modal" />
			<?php endif; ?>
			<?php echo JHtml::_('form.token'); ?>
		</div>
	</form>
</div>

<?php echo $this->loadTemplate('debug'); ?>