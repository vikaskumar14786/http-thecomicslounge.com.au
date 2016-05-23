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

$listOrder	= $this->state->get('list.ordering');
$listDirn	  = $this->state->get('list.direction');
$saveOrder  = ($listOrder == 'Event.id');
?>
<tr>
	<th width="1%" class="hidden-phone">
		<input id="event-toggle" type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
	</th>
	<?php if (JCalPro::version()->isCompatible('3.0')) : ?>
	<th width="1%" class="nowrap">
		<?php echo JHtml::_('grid.sort', 'JSTATUS', 'Event.published', $listDirn, $listOrder); ?>
	</th>
	<?php else : ?>
	<th width="1%" class="nowrap">
		<?php echo JHtml::_('grid.sort', 'JFEATURED', 'Event.featured', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap">
		<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_APPROVED', 'Event.approved', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap">
		<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_PUBLISHED', 'Event.published', $listDirn, $listOrder); ?>
	</th>
	<?php endif; ?>
	<th>
		<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_TITLE', 'Event.title', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="hidden-phone">
		<?php echo JText::_('COM_JCALPRO_CATEGORIES'); ?>
	</th>
	<th width="5%" class="hidden-phone">
		<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_TIMEZONE', 'Event.timezone', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="hidden-phone hidden-tablet">
		<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_START_DATE', 'Event.start_date', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="hidden-phone hidden-tablet">
		<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_END_DATE', 'Event.end_date', $listDirn, $listOrder); ?>
	</th>
	<th class="hidden-desktop">
		<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_DATE', 'Event.start_date', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
		<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_KIND', 'Event.recur_type', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone hidden-tablet">
		<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_CREATED_BY', 'Event.created_by', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone hidden-tablet">
		<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_ID', 'Event.id', $listDirn, $listOrder); ?>
	</th>
</tr>