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

// we need the user for the actions forms
$user = JFactory::getUser();
$script = 'document.getElementById(\'%s\').value=\'%s\';document.getElementById(\'%s\').submit();';

?>
<div class="row-fluid">
	<div class="span12 form-inline">
		<div class="control-group">
			<?php echo JText::_('COM_JCALPRO_ADMIN_FILTER_EVENTS'); ?>
			<div class="controls">
				<?php echo $this->admin_filter; ?>
			</div>
		</div>
	</div>
</div>
<div class="row-fluid">
	<?php if (!empty($this->items)) : ?>
	<table class="table table-striped">
		<thead>
			<tr>
				<th><?php echo JText::sprintf('COM_JCALPRO_ADMIN_EVENTS_FOUND', $this->pagination->get('total')); ?></th>
				<th><?php echo JText::_('COM_JCALPRO_DATE'); ?></th>
				<th nowrap="nowrap"><?php echo JText::_('COM_JCALPRO_ACTIONS'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($this->items as $i => $event) : ?>
			<tr>
				<td>
					<a href="<?php echo $event->href; ?>" class="eventtitle noajax"><?php
						echo JCalProHelperFilter::escape($event->title);
					?></a>
				</td>
				<td class="text-center"><?php echo JCalProHelperFilter::escape($event->user_microdisplay); ?></td>
				<td class="text-center" nowrap="nowrap">
					<?php
						$this->event = $event;
						echo $this->loadTemplate('event_admin');
					?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php else: ?>
	<div class="alert alert-info">
		<button type="button" class="close" data-dismiss="alert">×</button>
		<?php echo JText::_('COM_JCALPRO_ADMIN_NO_EVENTS'); ?>
	</div>
	<?php endif; ?>
</div>
<?php if (1 < $this->pagination->get('pages.total')) : ?>
<div class="row-fluid">
	<div class="span12">
		<div class="row-fluid">
			<div class="span10">
				<div class="pagination"><?php echo $this->pagination->getPagesLinks(); ?></div>
			</div>
			<div class="span2">
				<div class="pagination">
					<p class="counter"><?php echo $this->pagination->getPagesCounter(); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>