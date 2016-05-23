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

// we need the user for the actions forms
$user = JFactory::getUser();
$script = 'document.getElementById(\'%s\').value=\'%s\';document.getElementById(\'%s\').submit();';

?>
<div class="jcal_categories">
	<div class="jcl_admin_filter">
		<span class="atomic"><?php echo JText::_('COM_JCALPRO_ADMIN_FILTER_EVENTS'); ?></span>
		<div><?php echo $this->admin_filter; ?></div>
	</div>
</div>
<?php if (!empty($this->items)) : ?>
<table class="jcl_table">
	<thead>
		<tr class="jcl_header">
			<th class="event-title">
				<h3 class="jcl_header"><?php
					echo JText::sprintf('COM_JCALPRO_ADMIN_EVENTS_FOUND', $this->pagination->get('total'));
				?></h3>
			</th>
			<th class="event-date">
				<h3 class="jcl_header"><?php
					echo JText::_('COM_JCALPRO_DATE');
				?></h3>
			</th>
			<th class="event-moderation" nowrap="nowrap">
				<h3 class="jcl_header"><?php
					echo JText::_('COM_JCALPRO_ACTIONS');
				?></h3>
			</th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($this->items as $i => $event) : ?>
		<tr class="jcl_row jcl_row_<?php echo (0 == ($i + 1) % 2 ? 'even' : 'odd'); ?>">
			<td class="jcl_event_admin_row_left">
				<table class="jcl_month_inner_row">
					<tr>
						<td>
							<div class="jcl_event_body jcl_nooverflow" style="border-left-color: <?php echo @$event->color; ?>;">
								<a href="<?php echo $event->href; ?>" class="eventtitle noajax"><?php
									echo JCalProHelperFilter::escape($event->title);
								?></a>
							</div>
						</td>
					</tr>
				</table>
			</td>
			<td align="center"><?php echo JCalProHelperFilter::escape($event->user_microdisplay); ?></td>
			<td align="center" nowrap="nowrap">
				<?php
					$this->event = $event;
					echo $this->loadTemplate('event_admin');
				?>
			</td>
		</tr>
	<?php endforeach; ?>
<?php if (1 < $this->pagination->get('pages.total')) : ?>
		<tr class="jcal_categories">
			<td align="left">
        <div class="pagination"><?php echo $this->pagination->getPagesLinks(); ?></div>
			</td>
			<td align="right" colspan="2">
				<div class="pagination">
					<p class="counter"><?php echo $this->pagination->getPagesCounter(); ?></p>
				</div>
			</td>
		</tr>
<?php endif; ?>
	</tbody>
</table>
<?php else: ?>
<div class="jcl_row">
	<div class="jcl_empty jcl_message"><?php echo JText::_('COM_JCALPRO_ADMIN_NO_EVENTS'); ?></div>
</div>
<?php endif; ?>