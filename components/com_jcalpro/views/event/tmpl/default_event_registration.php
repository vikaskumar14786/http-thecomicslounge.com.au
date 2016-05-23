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

if ($this->item->registration) : ?>
<h3 class="jcl_header"><?php echo JText::_('COM_JCALPRO_EVENT_REGISTRATION'); ?></h3>
<div class="jcl_row">
	<div>
		<div class="atomic"><span class="label"><?php echo JText::_('COM_JCALPRO_REGISTRATION_START_DATE'); ?>:</span> <?php
			echo $this->item->registration_data->start_date->format(JText::_('COM_JCALPRO_DATE_FORMAT_MINI_DATE'));
		?></div>
		<div class="atomic"><span class="label"><?php echo JText::_('COM_JCALPRO_REGISTRATION_END_DATE'); ?>:</span> <?php
			echo $this->item->registration_data->end_date->format(JText::_('COM_JCALPRO_DATE_FORMAT_MINI_DATE'));
		?></div>
		<?php if ($this->item->registration_capacity) : ?>
		<div class="atomic"><span class="label"><?php echo JText::_('COM_JCALPRO_REGISTRATION_CAPACITY'); ?>:</span> <?php
			echo JText::sprintf('COM_JCALPRO_REGISTRATION_CAPACITY_DISPLAY', $this->item->registration_capacity);
		?></div>
		<?php endif; ?>
		<?php if ($this->item->registration_data->can_register) : ?>
		<div>
			<a class="btn jcalpro_register_button" href="<?php echo JCalProHelperUrl::task('registration.add', true, array('event_id' => $this->item->id)); ?>"><?php echo JText::_('COM_JCALPRO_MAINMENU_REGISTER'); ?></a>
		</div>
		<?php endif; ?>
	</div>
</div>
<?php endif;
