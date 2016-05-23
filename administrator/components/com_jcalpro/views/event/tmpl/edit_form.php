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
// show recurrence warnings
$showRecurrence = true;
if ($this->item->id && $this->item->rec_id && !$this->item->detached_from_rec) {
	$showRecurrence = false;
	$warning = JText::_('COM_JCALPRO_EVENT_CHILD_DETACHED_WARNING');
	if (!$this->item->detached_from_rec) {
		$warning = JText::sprintf('COM_JCALPRO_EVENT_CHILD_NOT_DETACHED_WARNING', JCalProHelperUrl::task('event.edit', false, array('id'=>$this->item->rec_id)));
	}
	JFactory::getApplication()->enqueuemessage($warning, 'warning');
}

$detailsForm      = $this->form->getFieldset('event');
$adminForm        = $this->form->getFieldset('admin');
$hiddenForm       = $this->form->getFieldset('hidden');
$repeatForm       = $this->form->getFieldset('repeat');
$contactForm      = $this->form->getFieldset('contact');
$durationForm     = $this->form->getFieldset('duration');
$startDateForm    = $this->form->getFieldset('startdate');
$registrationForm = $this->form->getFieldset('registration');
$customfieldsForm = $this->form->getFieldset('customfields');
$nonextra = array('admin', 'event', 'hidden', 'repeat', 'contact', 'duration', 'startdate', 'registration', 'customfields', 'jmetadata');
// this is kinda backwards :)
$customfieldsFormTitle = '';
$fieldsets = $this->form->getFieldsets();
foreach ($fieldsets as $fieldset) {
	if ('customfields' != $fieldset->name) continue;
	$customfieldsFormTitle = $fieldset->label;
	break;
}

?>

<div class="row-fluid">
	<div class="span10 form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'JCalProEventTab', array('active' => 'details')); ?>
		<?php echo JHtml::_('bootstrap.addTab', 'JCalProEventTab', 'details', JText::_('COM_JCALPRO_EVENT_DETAILS', true)); ?>
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
				<div class="control-group form-inline">
					<?php echo $this->form->getLabel('location'); ?>
					<?php echo $this->form->getInput('location'); ?>
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
			</div>
			<div class="span6">
				<div class="control-group form-vertical">
					<?php echo $this->form->getLabel('cat'); ?>
					<?php echo $this->form->getInput('cat'); ?>
				</div>
			</div>
			
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		
		<?php echo JHtml::_('bootstrap.addTab', 'JCalProEventTab', 'eventdate', JText::_('COM_JCALPRO_EVENT_DATE', true)); ?>
		<div class="row-fluid form-vertical">
			<div class="span6">
				<div class="control-group">
					<?php echo JText::_('COM_JCALPRO_START_TIME'); ?>
					<div class="controls">
						<?php echo $this->form->getInput('start_date_array'); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo JText::_('COM_JCALPRO_TIMEZONE'); ?>
					<div class="controls">
						<?php echo $this->form->getInput('timezone'); ?>
					</div>
				</div>
			</div>
			<div class="span6">
				<div class="control-group">
					<?php echo JText::_('COM_JCALPRO_DURATION'); ?>
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
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		
	<?php if ($this->item->allow_registration && !empty($registrationForm)) : ?>
		<?php echo JHtml::_('bootstrap.addTab', 'JCalProEventTab', 'registration', JText::_('COM_JCALPRO_REGISTRATION', true)); ?>
		<div class="row-fluid">
			<div class="span4">
				<div class="control-group">
					<?php echo $this->form->getLabel('registration'); ?>
					<div class="controls">
						<?php echo $this->form->getInput('registration'); ?>
					</div>
				</div>
			</div>
			<div class="span8">
				<div id="jcl_registration_off_options"> </div>
				<div id="jcl_registration_on_options">
					<div class="control-group">
					<?php foreach ($registrationForm as $name => $field): if ('jform_registration' == $name) continue; ?>
						<?php echo $field->label; ?>
						<div class="control">
							<?php echo $field->input; ?>
						</div>
					<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
	<?php endif; ?>
	
	<?php if ($showRecurrence) : ?>
		<?php echo JHtml::_('bootstrap.addTab', 'JCalProEventTab', 'repeat', JText::_('COM_JCALPRO_REPEAT_METHOD', true)); ?>
		<div class="row-fluid">
			<div class="span4">
				<fieldset class="adminform form-vertical">
					<div class="control-group">
						<div class="controls">
							<?php echo $this->form->getInput('recur_type'); ?>
						</div>
					</div>
				</fieldset>
			</div>
			<div class="span8">
				<div id="jcl_rec_none_options"> </div>
				<div id="jcl_rec_daily_options">
					<div class="control-group">
						<?php
							printf($this->form->getLabel('rec_daily_period'), 'X', '</label>' . $this->form->getInput('rec_daily_period') . '<label>');
						?>
					</div>
				</div>
				<div id="jcl_rec_weekly_options">
					<div class="control-group">
						<?php
							printf($this->form->getLabel('rec_weekly_period'), 'X', '</label>' . $this->form->getInput('rec_weekly_period') . '<label>');
						?>
					</div>
					<?php foreach (JCalDate::$days as $day) : ?>
					<div class="control-group">
						<?php echo $this->form->getInput('rec_weekly_on_' . strtolower($day)); ?>
						<?php echo $this->form->getLabel('rec_weekly_on_' . strtolower($day)); ?>
					</div>
					<?php endforeach; ?>
				</div>
				<div id="jcl_rec_monthly_options">
					<div class="control-group">
						<?php
							printf($this->form->getLabel('rec_monthly_period'), 'X', '</label>' . $this->form->getInput('rec_monthly_period') . '<label>');
						?>
					</div>
					<div class="control-group">
						<?php
							printf(
								$this->form->getInput('rec_monthly_type')
							, '</label>' . $this->form->getInput('rec_monthly_day_number') . '<label class="jcl_block jcl_clear">'
							, '</label>' . $this->form->getInput('rec_monthly_day_order') . ' ' . $this->form->getInput('rec_monthly_day_type') . '<label>'
							);
						?>
					</div>
				</div>
				<div id="jcl_rec_yearly_options">
					<div class="control-group">
						<?php
							printf($this->form->getLabel('rec_yearly_period'), 'X', 'X', '</label>' . $this->form->getInput('rec_yearly_period') . '<label>', '</label>' . $this->form->getInput('rec_yearly_on_month') . '<label>');
						?>
					</div>
					<div class="control-group">
						<?php
							printf(
								$this->form->getInput('rec_yearly_type')
							, '</label>' . $this->form->getInput('rec_yearly_day_number') . '<label class="jcl_block jcl_clear">'
							, '</label>' . $this->form->getInput('rec_yearly_day_order') . ' ' . $this->form->getInput('rec_yearly_day_type') . '<label>'
							);
						?>
					</div>
				</div>
			</div>
		</div>
		<div class="row-fluid jcalrepeatend">
			<div class="span4">
				<div class="control-group">
					<div class="control-label"><?php echo JText::_('COM_JCALPRO_REPEAT_END_DATE'); ?></div>
				</div>
			</div>
			<div class="span8">
				<div class="control-group">
					<?php
						printf($this->form->getInput('recur_end_type')
						,	'</label>'  .$this->form->getInput('recur_end_count') . '<label>'
						,	'</label>' . $this->form->getInput('recur_end_until') . '<label>'
						);
					?>
				</div>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
	<?php endif; ?>
	
	<?php if (!empty($customfieldsFormTitle)) : ?>
		<?php echo JHtml::_('bootstrap.addTab', 'JCalProEventTab', 'custom', JCalProHelperFilter::escape($customfieldsFormTitle)); ?>
		<div class="row-fluid">
			<div class="span12 form-horizontal">
				<?php foreach ($customfieldsForm as $name => $field) : ?>
				<div class="control-group">
					<div class="control-label">
						<?php echo $field->label; ?>
					</div>
					<div class="controls">
						<?php echo $field->input; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
	<?php endif; ?>
	
	<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	</div>
	
	<div class="span2">
		<fieldset class="adminform form-vertical">
		
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('private'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('private'); ?>
				</div>
			</div>
			
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('featured'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('featured'); ?>
				</div>
			</div>
		
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('published'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('published'); ?>
				</div>
			</div>
			
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('approved'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('approved'); ?>
				</div>
			</div>
			
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('language'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('language'); ?>
				</div>
			</div>
			
			<?php if (JCalPro::version()->isCompatible('3.1.0')) : ?>
			<div class="control-group">
				<div class="control-label">
					<?php echo $this->form->getLabel('tags', 'metadata'); ?>
				</div>
				<div class="controls">
					<?php echo $this->form->getInput('tags', 'metadata'); ?>
				</div>
			</div>
			<?php endif; ?>
			
		</fieldset>
	</div>
</div>
