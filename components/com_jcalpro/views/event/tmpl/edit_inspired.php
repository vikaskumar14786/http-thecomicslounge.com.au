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

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');

JText::script('COM_JCALPRO_VALIDATION_FORM_FAILED');
JText::script('COM_JCALPRO_VALIDATION_JFORM_TITLE_FAILED');
JText::script('COM_JCALPRO_VALIDATION_JFORM_CANONICAL_ID_FAILED');
JText::script('COM_JCALPRO_INVALID_DATE');
if (JCalPro::version()->isCompatible('3.0.0')) {
	JHtml::_('formbehavior.chosen', 'select');
}

$registrationForm = $this->form->getFieldset('registration');
$nonextra = array('event', 'hidden', 'repeat', 'contact', 'duration', 'startdate', 'registration', 'jmetadata');

// permissions
$canonical = null;
$formcanonical = $this->form->getValue('canonical');
if (!empty($formcanonical)) {
	$canonical = $formcanonical;
}
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
		var form = document.getElementById('adminForm');
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
				if (found) {
					return;
				}
				if ('' == JCalPro.getValue(JCalPro.id(el))) {
					found = true;
					alert(Joomla.JText._('COM_JCALPRO_VALIDATION_' + el.toUpperCase() + '_FAILED'));
				}
			});
			if (!found) {
				alert(Joomla.JText._('COM_JCALPRO_VALIDATION_FORM_FAILED'));
			}
		}
	}
</script>

<div id="jcl_component" class="well edit<?php echo $this->viewClass; ?>">
	<header class="jcl_header page-header clearfix">
		<h1><?php echo JText::_('COM_JCALPRO_MAINMENU_' . ($this->item->id ? 'EDIT' : 'ADD')); ?></h1>
	</header>
	<form action="<?php echo JRoute::_('index.php?option=com_jcalpro&task=event.save&id=' . (int) $this->item->id); ?>" method="post" id="adminForm" name="adminForm" enctype="multipart/form-data" class="form-validate form-vertical">
	
		<!-- Button Toolbar -->
		<div class="btn-toolbar">
			<div class="btn-group">
				<button class="btn btn-primary" type="button" onclick="Joomla.submitbutton('event.save')">
					<i class="icon-ok"></i> <?php echo JText::_('JSAVE') ?>
				</button>
				<button class="btn" type="button" onclick="Joomla.submitbutton('event.cancel')">
					<i class="icon-cancel"></i> <?php echo JText::_('JCANCEL') ?>
				</button>
			</div>
			
			<?php echo JHtml::_('form.token'); ?>
			<?php foreach ($this->form->getFieldset('hidden') as $field) echo $field->input; ?>
			<input type="hidden" id="event-task" name="task" value="event.save" />
			<input type="hidden" id="event-catid" name="catid" value="<?php echo JFactory::getApplication()->input->get('catid', 0, 'uint'); ?>" />
			<input type="hidden" id="event-id" name="id" value="<?php echo $this->item->id; ?>" />
		</div>
		
		<div class="jcal_fieldsets">
			<?php echo JHtml::_('jcalpro.startTabSet', 'JCalProEventTab', array('active' => 'details')); ?>
			<?php echo JHtml::_('jcalpro.addTab', 'JCalProEventTab', 'details', JText::_('COM_JCALPRO_EVENT_DETAILS', true)); ?>
			<fieldset>
				<div class="row-fluid">
					<div class="span6">
						<div class="control-group form-inline">
							<?php /* title */ ?>
							<?php echo $this->form->getLabel('title'); ?>
							<?php echo $this->form->getInput('title'); ?>
						</div>
					</div>
					<div class="span6">
						<div class="control-group form-inline">
							<?php /* alias */ ?>
							<?php echo $this->form->getLabel('alias'); ?>
							<?php echo $this->form->getInput('alias'); ?>
						</div>
					</div>
				</div>
				<div class="row-fluid">
					<div class="span12">
						<?php echo $this->form->getInput('description'); ?>
					</div>
				</div>
				
				<div class="row-fluid">
					<div class="span6">
						<div class="control-group form-vertical">
							<?php echo $this->form->getLabel('canonical'); ?>
							<?php echo $this->form->getInput('canonical'); ?>
						</div>
						
						<div class="control-group form-vertical">
							<?php echo $this->form->getLabel('cat'); ?>
							<?php echo $this->form->getInput('cat'); ?>
						</div>
					</div>
					
					<div class="span6">
						<div class="control-group form-vertical">
							<?php echo $this->form->getLabel('language'); ?>
							<?php echo $this->form->getInput('language'); ?>
						</div>
						
						<div class="control-group form-vertical">
							<?php echo $this->form->getLabel('location'); ?>
							<?php echo $this->form->getInput('location'); ?>
						</div>
						
						<?php if (JCalPro::version()->isCompatible('3.1.0')) : ?>
						<div class="control-group form-vertical">
							<?php echo $this->form->getLabel('tags', 'metadata'); ?>
							<?php echo $this->form->getInput('tags', 'metadata'); ?>
						</div>
						<?php endif; ?>
					</div>
					
				</div>
			</fieldset>
			<?php echo JHtml::_('jcalpro.endTab'); ?>
			
			<?php echo JHtml::_('jcalpro.addTab', 'JCalProEventTab', 'datetime', JText::_('COM_JCALPRO_EVENT_DATE', true)); ?>
			<fieldset>
				<div class="control-group">
					<div class="control-label">
						<?php echo JText::_('COM_JCALPRO_EVENT_DATE_AND_TIME'); ?>
					</div>
					<div class="controls">
					<?php
						echo $this->form->getInput('start_date_array');
						echo $this->form->getInput('timezone');
					?>
					</div>
				</div>
				
				<div class="control-group">
					<div class="control-label">
						<?php echo JText::_('COM_JCALPRO_DURATION'); ?>
					</div>
					<div class="controls">
					<?php
						printf($this->form->getInput('duration_type')
							, '</label>'
							. $this->form->getLabel('end_date_array')
							. $this->form->getInput('end_date_array')
							. '<label>'
							, '</label>'
							. $this->form->getInput('end_days')
							. $this->form->getLabel('end_days')
							. $this->form->getInput('end_hours')
							. $this->form->getLabel('end_hours')
							. $this->form->getInput('end_minutes')
							. $this->form->getLabel('end_minutes')
							. '<label class="jcl_block">'
						);
					?>
					</div>
				</div>
			</fieldset>
			<?php echo JHtml::_('jcalpro.endTab'); ?>
			
			<?php echo JHtml::_('jcalpro.addTab', 'JCalProEventTab', 'repeat', JText::_('COM_JCALPRO_REPEAT_METHOD', true)); ?>
			<fieldset>
				<div class="control-group">
					<div class="control-label">
						<?php echo JText::_('COM_JCALPRO_REPEAT_METHOD'); ?>
					</div>
					<div class="controls">
						<?php echo $this->form->getInput('recur_type'); ?>
						
						<div id="jcl_rec_none_options"> </div>
						<div id="jcl_rec_daily_options">
							<ul>
								<li><?php
									printf($this->form->getLabel('rec_daily_period'), 'X', '</label>' . $this->form->getInput('rec_daily_period') . '<label>');
								?></li>
							</ul>
						</div>
						<div id="jcl_rec_weekly_options">
							<ul>
								<li><?php
									printf($this->form->getLabel('rec_weekly_period'), 'X', '</label>' . $this->form->getInput('rec_weekly_period') . '<label>');
								?><br /><?php
									foreach (JCalDate::$days as $day) {
										echo $this->form->getInput('rec_weekly_on_' . strtolower($day));
										echo $this->form->getLabel('rec_weekly_on_' . strtolower($day));
									}
								?></li>
							</ul>
						</div>
						<div id="jcl_rec_monthly_options">
							<ul>
								<li><?php
									printf($this->form->getLabel('rec_monthly_period'), 'X', '</label>' . $this->form->getInput('rec_monthly_period') . '<label>');
								?><br /><?php
									printf(
										$this->form->getInput('rec_monthly_type')
									, '</label>' . $this->form->getInput('rec_monthly_day_number') . '<label>'
									, '</label>' . $this->form->getInput('rec_monthly_day_order') . ' ' . $this->form->getInput('rec_monthly_day_type') . '<label>'
									);
								?></li>
							</ul>
						</div>
						<div id="jcl_rec_yearly_options">
							<ul>
								<li><?php
									printf($this->form->getLabel('rec_yearly_period'), 'X', 'X', '</label>' . $this->form->getInput('rec_yearly_period') . '<label>', '</label>' . $this->form->getInput('rec_yearly_on_month') . '<label>');
								?><br /><?php
									printf(
										$this->form->getInput('rec_yearly_type')
									, '</label>' . $this->form->getInput('rec_yearly_day_number') . '<label class="jcl_block jcl_clear">'
									, '</label>' . $this->form->getInput('rec_yearly_day_order') . ' ' . $this->form->getInput('rec_yearly_day_type') . '<label>'
									);
								?></li>
							</ul>
						</div>
					
					</div>
				</div>
				
				<div class="control-group">
					<div class="control-label">
						<?php echo JText::_('COM_JCALPRO_REPEAT_END_DATE'); ?>
					</div>
					<div class="controls">
						<?php printf($this->form->getInput('recur_end_type'), '</label>' . $this->form->getInput('recur_end_count') . '<label>', '</label>' . $this->form->getInput('recur_end_until') . '<label>'); ?>
					</div>
				</div>
			</fieldset>
			<?php echo JHtml::_('jcalpro.endTab'); ?>
			
			<?php if ($this->item->allow_registration && !empty($registrationForm)) : ?>
				<?php echo JHtml::_('jcalpro.addTab', 'JCalProEventTab', 'registration', JText::_('COM_JCALPRO_REGISTRATION', true)); ?>
				<fieldset>
					<div class="control-group">
						<div class="control-label">
							<?php echo JText::_('COM_JCALPRO_REGISTRATION'); ?>
						</div>
						<div class="controls">
							<?php echo $this->form->getInput('registration'); ?>
							<div id="jcl_registration_off_options"> </div>
							<div id="jcl_registration_on_options">
								<ul class="formlist">
								<?php foreach ($registrationForm as $name => $field) : if ('jform_registration' == $name) continue; ?>
									<li><?php
										echo $field->label . ' ' . $field->input;
									?></li>
								<?php endforeach; ?>
								</ul>
							</div>
						</div>
					</div>
				</fieldset>
				<?php echo JHtml::_('jcalpro.endTab'); ?>
			<?php endif; ?>


<?php
	foreach ($this->form->getFieldsets() as $fieldset) :
		if (in_array($fieldset->name, $nonextra)) continue;
		$fields = $this->form->getFieldset($fieldset->name);
		if (empty($fields)) continue;
		echo JHtml::_('jcalpro.addTab', 'JCalProEventTab', $fieldset->name, JText::_($fieldset->label, true)); ?>
		<fieldset>
		<?php foreach ($this->form->getFieldset($fieldset->name) as $name => $field) : ?>
			<div class="control-group">
				<div class="control-label">
					<?php echo $field->label; ?>
				</div>
				<div class="controls">
					<?php echo $field->input; ?>
				</div>
			</div>
		<?php endforeach; ?>
		</fieldset>
		<?php
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

<?php echo $this->loadTemplate('debug');
