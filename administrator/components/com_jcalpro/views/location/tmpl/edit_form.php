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

JHtml::_('behavior.framework');

$input = JFactory::getApplication()->input;

$tabspan  = 'span10';
$formspan = 'span4';
$mapspan  = 'span8';

if ('component' == $input->get('tmpl', '', 'cmd')) :
	$tabspan = 'span12';
	$formspan = $mapspan = 'span6';
?>
<div class="row-fluid">
	<div class="right pull-right toolbar-list">
		<div class="btn-group">
			<button class="btn tip hasTooltip" onclick="Joomla.submitbutton('location.refresh');return false;" title="<?php echo JText::_('COM_JCALPRO_REFRESH_MAP'); ?>"><i class="icon-refresh"> </i> <?php echo JText::_('COM_JCALPRO_REFRESH_MAP'); ?></button>
			<button class="btn btn-primary tip hasTooltip" onclick="Joomla.submitbutton('location.save');return false;" title="<?php echo JText::_('JTOOLBAR_SAVE'); ?>"><i class="icon-save"> </i> <?php echo JText::_('JTOOLBAR_SAVE'); ?></button>
			<button class="btn tip hasTooltip" onclick="Joomla.submitbutton('location.apply');return false;" title="<?php echo JText::_('JTOOLBAR_APPLY'); ?>"><i class="icon-apply"> </i> <?php echo JText::_('JTOOLBAR_APPLY'); ?></button>
			<button class="btn tip hasTooltip" onclick="Joomla.submitbutton('location.cancel');return false;" title="<?php echo JText::_('JTOOLBAR_CANCEL'); ?>"><i class="icon-cancel"> </i> <?php echo JText::_('JTOOLBAR_CANCEL'); ?></button>
		</div>
	</div>
</div>
<?php endif; ?>
<div class="row-fluid">
	<div class="<?php echo $tabspan; ?>">
		<?php echo JHtml::_('bootstrap.startTabSet', 'JCalProLocationTab', array('active' => 'location')); ?>
		<?php echo JHtml::_('bootstrap.addTab', 'JCalProLocationTab', 'location', JText::_('COM_JCALPRO_LOCATION', true)); ?>
		<div class="row-fluid">
			<div class="<?php echo $formspan; ?>">
				<?php foreach ($this->form->getFieldset('location') as $name => $field): ?>
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
			<div class="<?php echo $mapspan; ?>">
				<div id="map_canvas_container">
					<div id="map_canvas"><?php echo JText::_('COM_JCALPRO_LOCATION_LOADING_MAP'); ?></div>
				</div>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	</div>
<?php if ('component' != $input->get('tmpl', '', 'cmd')) : ?>
	<div class="span2">
		<h4><?php echo JText::_('COM_JCALPRO_FORM_DETAILS'); ?></h4>
		<hr />
		<fieldset class="form-vertical">
			<?php foreach ($this->form->getFieldset('details') as $name => $field): ?>
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
	</div>
<?php else : ?>
	<div style="display:none;">
		<?php foreach ($this->form->getFieldset('details') as $name => $field) echo $field->input; ?>
	</div>
<?php endif; ?>
</div>
