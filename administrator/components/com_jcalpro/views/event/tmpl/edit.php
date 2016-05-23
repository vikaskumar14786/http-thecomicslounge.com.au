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

JText::script('COM_JCALPRO_VALIDATION_FORM_FAILED');
JText::script('COM_JCALPRO_VALIDATION_JFORM_TITLE_FAILED');
JText::script('COM_JCALPRO_VALIDATION_JFORM_CANONICAL_ID_FAILED');
JText::script('COM_JCALPRO_INVALID_DATE');

// permissions
$canonical = null;
$formcanonical = $this->form->getValue('canonical');
if (!empty($formcanonical)) $canonical = $formcanonical;
$canCreatePrivate = JCalPro::canDo('core.create.private', $canonical);
$canCreatePublic  = JCalPro::canDo('core.create', $canonical);
$canModerate      = JCalPro::canDo('core.moderate', $canonical);
$canEditState     = JCalPro::canDo('core.edit.state', $canonical);
?>
<script type="text/javascript">
	window.jclAcl = {
		moderate: <?php echo (int) $canModerate; ?>
	,	createPrivate: <?php echo (int) $canCreatePrivate; ?>
	,	createPublic: <?php echo (int) $canCreatePublic; ?>
	,	editState: <?php echo (int) $canEditState; ?>
	};
	Joomla.submitbutton = function(task) {
		JCalPro.debug('Joomla.submitbutton');
		var form = document.getElementById('event-form');
		if ('undefined' != typeof window.jclDateTimeCheckActive) {
			JCalPro.debug('Cannot save yet - waiting for date validation...');
			window.jclDateTimeCheckSubmitTask = task;
			window.jclDateTimeCheckSubmitTimer = setTimeout(function() {
				Joomla.submitbutton(window.jclDateTimeCheckSubmitTask);
			}, 200);
			return;
		}
		if (task == 'event.cancel' || document.formvalidator.isValid(form)) {
			try {
				<?php echo $this->form->getField('description')->save(); ?>
			}
			catch (err) {
				// tinyMCE not in use
			}
			Joomla.submitform(task, form);
		}
		else {
			var fields = ['jform_title', 'jform_canonical_id'], found = false;
			JCalPro.each(fields, function(el, idx) {
				if (found) return;
				if ('' == JCalPro.getValue(JCalPro.id(el))) {
					found = true;
					alert(Joomla.JText._('COM_JCALPRO_VALIDATION_' + el.toUpperCase() + '_FAILED'));
				}
			});
			if (!found) alert(Joomla.JText._('COM_JCALPRO_VALIDATION_FORM_FAILED'));
		}
	}
</script>
<div id="jcl_component" class="<?php echo $this->viewClass; ?>">
	<form action="<?php echo JRoute::_('index.php?option=com_jcalpro&task=event.save&id=' . (int) $this->item->id); ?>" method="post" id="event-form" name="adminForm" class="form-validate">
		<?php echo $this->loadTemplate(JCalPro::version()->isCompatible('3.0') ? 'form' : 'legacy', 'edit'); ?>
		<div>
			<input type="hidden" id="event-id" name="id" value="<?php echo intval($this->item->id); ?>" />
			<input type="hidden" id="event-task" name="task" value="" />
			<input type="hidden" id="event-catid" name="catid" value="<?php echo JFactory::getApplication()->getUserState('com_jcalpro.events.jcal.catid'); ?>" />
			<?php echo JHtml::_('form.token'); ?>
		</div>
	</form>
</div>
<?php echo $this->loadTemplate('debug'); ?>