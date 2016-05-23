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

?>
<div class="width-40 fltlft">
	<fieldset class="adminform">
		<legend><?php echo JText::_('COM_JCALPRO_FORM'); ?></legend>
		<ul class="adminformlist">
		<?php foreach ($this->form->getFieldset('form') as $name => $field): ?>
			<li class="jcl_form_label"><?php echo $field->label; ?></li>
			<li class="jcl_form_input"><?php echo $field->input; ?></li>
		<?php endforeach; ?>
		<?php foreach ($this->form->getFieldset('details') as $name => $field): ?>
			<li class="jcl_form_label"><?php echo $field->label; ?></li>
			<li class="jcl_form_input"><?php echo $field->input; ?></li>
		<?php endforeach; ?>
		</ul>
	</fieldset>
</div>
<div class="width-60 fltlft">
	<fieldset class="adminform">
		<legend><?php echo JText::_('COM_JCALPRO_FORM_FIELDS'); ?></legend>
		<ul class="adminformlist">
		<?php foreach ($this->form->getFieldset('form_fields') as $name => $field): ?>
			<li class="jcl_form_label"><?php echo $field->label; ?></li>
			<li class="jcl_form_input"><?php echo $field->input; ?></li>
		<?php endforeach; ?>
		</ul>
	</fieldset>
</div>