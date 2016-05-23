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

$user      = JFactory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$saveOrder = ($listOrder == 'Email.id');
$trashed   = (-2 == $this->state->get('filter.published'));

if (JCalPro::version()->isCompatible('3.0')) JHtml::_('dropdown.init');

if (!empty($this->items)) :
	foreach($this->items as $i => $item):
		$canEdit    = $user->authorise('core.edit', 'com_jcalpro.email.'.$item->id);
		$canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
		$canEditOwn = $user->authorise('core.edit.own', 'com_jcalpro.email.'.$item->id) && $item->created_by == $userId;
		$canChange  = $user->authorise('core.edit.state', 'com_jcalpro.email.'.$item->id) && $canCheckin;
	?>
	<tr class="row<?php echo $i % 2; ?>">
		<td class="hidden-phone">
			<?php echo JHtml::_('grid.id', $i, $item->id); ?>
		</td>
		<td class="center">
			<?php echo JHtml::_('jgrid.isdefault', $item->default, $i, 'emails.', $canChange && !$item->default); ?>
		</td>
		<td class="nowrap has-context">
			<div class="pull-left">
				<?php if ($item->checked_out) : ?>
					<?php echo JHtml::_('jgrid.checkedout', $i, $item->author_name, $item->checked_out_time, 'emails.', $canCheckin); ?>
				<?php endif; ?>
				<?php if ($canEdit || $canEditOwn) : ?>
					<a href="<?php echo JCalProHelperUrl::_(array('task'=>'email.edit', 'id'=>$item->id));?>">
						<?php echo JCalProHelperFilter::escape($item->subject); ?>
					</a>
				<?php else : ?>
					<?php echo JCalProHelperFilter::escape($item->subject); ?>
				<?php endif; ?>
				<p class="smallsub small hidden-desktop">(<?php echo JCalProHelperFilter::escape($item->context); ?>)</p>
			</div>
			<?php /*if (JCalPro::version()->isCompatible('3.0')) : ?>
			<div class="pull-left"><?php
			
				JHtml::_('dropdown.edit', $item->id, 'email.');
				JHtml::_('dropdown.divider');
				JHtml::_('dropdown.' . ($item->published ? 'un' : '') . 'publish', 'cb' . $i, 'emails.');
				if ($item->checked_out) :
					JHtml::_('dropdown.checkin', 'cb' . $i, 'emails.');
				endif;
				JHtml::_('dropdown.' . ($trashed ? 'un' : '') . 'trash', 'cb' . $i, 'emails.');
				
				echo JHtml::_('dropdown.render');
				
			?></div>
			<?php endif; */ ?>
		</td>
		<td class="hidden-phone hidden-tablet">
			<?php echo JCalProHelperFilter::escape($item->context); ?>
		</td>
		<td>
			<?php echo JCalProHelperFilter::escape($item->language); ?>
		</td>
		<td class="hidden-phone hidden-tablet">
			<?php echo JCalProHelperFilter::escape($item->author_name); ?>
			<p class="smallsub small"><span>(<?php echo JCalProHelperFilter::escape($item->author_username); ?>)</span></p>
		</td>
		<td class="nowrap hidden-phone hidden-tablet">
			<?php echo $item->id; ?>
		</td>
	</tr>
	<?php endforeach;
endif;