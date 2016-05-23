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

$customFields = $this->form->getFieldset('customfields');
// this is kinda backwards :)
$customFieldsFormTitle = '';
$fieldsets = $this->form->getFieldsets();
foreach ($fieldsets as $fieldset) {
	if ('customfields' != $fieldset->name) continue;
	$customFieldsFormTitle = $fieldset->label;
	break;
}
?>
<div class="row-fluid">
	<div class="span12 form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'JCalProRegistrationTab', array('active' => 'registration')); ?>
		<?php foreach ($this->form->getFieldsets() as $fieldset) : ?>
			<?php
			if ('customfields' == $fieldset->name) continue;
			if ('hidden' == $fieldset->name) :
				?><div><?php
				foreach ($this->form->getFieldset($fieldset->name) as $name => $field) :
					echo $field->input;
				endforeach;
				?></div><?php
			else : ?>
				<?php echo JHtml::_('bootstrap.addTab', 'JCalProRegistrationTab', $fieldset->name, JText::_($fieldset->label, true)); ?>
				<div class="row-fluid">
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
				</div>
				<?php echo JHtml::_('bootstrap.endTab'); ?>
			<?php endif; ?>
		<?php endforeach; ?>
		<?php if (!empty($customFields)) : ?>
			<?php echo JHtml::_('bootstrap.addTab', 'JCalProRegistrationTab', 'custom', JText::_($customFieldsFormTitle, true)); ?>
			<div class="row-fluid">
				<?php foreach ($customFields as $name => $field) : ?>
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
			<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php endif; ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	</div>
</div>
