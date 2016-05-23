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

JText::script('COM_JCALPRO_CONFIRM_DETACH');
JText::script('COM_JCALPRO_CONFIRM_DETACH_MULTI');

$is3       = JCalPro::version()->isCompatible('3.0.0');
$user      = JFactory::getUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$trashed   = (-2 == $this->state->get('filter.published'));
$saveOrder = ($listOrder == 'Event.id');

if ($is3) JHtml::_('dropdown.init');

if (!empty($this->items)) :
	foreach($this->items as $i => $item):
		// permissions on this item
		$context     = 'com_jcalpro.event.'.$item->id;
		$canEdit     = $user->authorise('core.edit', $context);
		$canDelete   = $user->authorise('core.delete', $context);
		$canCheckin  = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
		$canEditOwn  = $user->authorise('core.edit.own', $context) && $item->created_by == $userId;
		$canChange   = $user->authorise('core.edit.state', $context) && $canCheckin;
		$canModerate = $user->authorise('core.moderate', $context) && $canCheckin;
		$showDrop    = $is3 && ($canEdit || $canEditOwn || $canChange || $canDelete);
		// shorten the title & alias
		$lim = 50;
		foreach (array('title', 'alias') as $var) {
			$$var = $item->{$var};
			if ($lim < JString::strlen($$var)) {
				$$var = JString::substr($$var, 0, $lim) . ' ...';
			}
		}
		// build date html now as we're displaying it differently depending on screen size
		$startDateHtml = JCalProHelperFilter::escape($item->minidisplay . (isset($item->start_timedisplay) ? ' ' . $item->start_timedisplay : ''));
		// add the timezone for phones only
		$startDateHtml .= '<span class="hidden-tablet hidden-desktop">(' . JCalProHelperFilter::escape($item->timezone) . ')</span>';
		$showEndTime = false;
		switch ($item->duration_type) {
			case JCalPro::JCL_EVENT_DURATION_NONE:
				$endDateHtml = JText::_('COM_JCALPRO_NO_END');
				break;
			case JCalPro::JCL_EVENT_DURATION_TIME:
			case JCalPro::JCL_EVENT_DURATION_DATE:
				$showEndTime = true;
				$endDateHtml = JCalProHelperFilter::escape($item->end_minidisplay) . (isset($item->end_timedisplay) ? " " . JCalProHelperFilter::escape($item->end_timedisplay) : '');
				break;
			case JCalPro::JCL_EVENT_DURATION_ALL:
				$endDateHtml = JText::_('COM_JCALPRO_ALL_DAY');
				break;
			default:
				$endDateHtml = '';
				break;
		}
		if ('UTC' != $item->timezone) {
			$utcHtml = '<p class="smallsub small"><span>%s (' . JText::_('COM_JCALPRO_UTC') . ')</span></p>';
			$startDateUtcHtml = JCalProHelperFilter::escape($item->utc_minidisplay . ' ' . @$item->utc_start_timedisplay);
			$startDateHtml .= sprintf($utcHtml, $startDateUtcHtml);
			if ($showEndTime) {
				$endDateUtcHtml = JCalProHelperFilter::escape($item->utc_end_minidisplay . ' ' . @$item->utc_end_timedisplay);
				$endDateHtml .= sprintf($utcHtml, $endDateUtcHtml);
			}
		}
		$approve  = JHtml::_('jgrid.action', $i, ($item->approved ? 'unapprove' : 'approve'), 'events.', 'COM_JCALPRO_APPROVE_TOGGLE', 'COM_JCALPRO_APPROVE_APPROVE', 'COM_JCALPRO_APPROVE_UNAPPROVE', true, ($item->approved ? 'publish' : 'unpublish'), ($item->approved ? 'publish' : 'unpublish'), $canModerate);
		$publish  = JHtml::_('jgrid.published', $item->published, $i, 'events.', $canChange, 'cb');
		$feature  = JHtml::_('jgrid.action', $i, ($item->featured ? 'unfeature' : 'feature'), 'events.', 'COM_JCALPRO_FEATURED_TOGGLE', 'COM_JCALPRO_FEATURED_FEATURE', 'COM_JCALPRO_FEATURED_UNFEATURE', true, ($is3 ? 'star' . ($item->featured ? '' : '-empty') : ($item->featured ? '' : 'not') . 'default'), ($is3 ? 'star' . ($item->featured ? '' : '-empty') : ($item->featured ? '' : 'not') . 'default'), $canChange);
		
		?>
	<tr class="event-row-<?php echo $item->id; ?> row<?php echo $i % 2; ?>">
		<td class="hidden-phone">
			<?php echo JHtml::_('grid.id', $i, $item->id); ?>
		</td>
		<?php if ($is3) : ?>
		<td class="event-row-approve event-row-confirm center">
			<div class="btn-group">
			<?php echo $feature . $approve . $publish; ?>
			</div>
		</td>
		<?php else : ?>
		<td class="event-row-approve event-row-confirm center">
			<div class="btn-group">
			<?php echo $feature; ?>
			</div>
		</td>
		<td class="event-row-approve event-row-confirm center">
			<div class="btn-group">
			<?php echo $approve; ?>
			</div>
		</td>
		<td class="event-row-approve event-row-confirm center">
			<div class="btn-group">
			<?php echo $publish; ?>
			</div>
		</td>
		<?php endif; ?>
		<td class="nowrap has-context">
			<div class="pull-left">
				<?php if ($item->checked_out) : ?>
					<?php echo JHtml::_('jgrid.checkedout', $i, $item->author_name, $item->checked_out_time, 'events.', $canCheckin); ?>
				<?php endif; ?>
				<?php if (!empty($item->location_data)) : ?>
					<img class="hasTip" title="<?php echo JCalProHelperFilter::escape($item->location_data->title . '::' . $item->location_data->address); ?>" src="<?php echo JCalProHelperTheme::getFilePath('icon-event-location.png', 'images/events'); ?>" />
				<?php endif; ?>
				<?php if ($canEdit || $canEditOwn) : ?>
					<a href="<?php echo JCalProHelperUrl::_(array('task'=>'event.edit', 'id'=>$item->id));?>">
						<?php echo JCalProHelperFilter::escape($title); ?>
					</a>
				<?php else : ?>
					<?php echo JCalProHelperFilter::escape($title); ?>
				<?php endif; ?>
				<?php if (!empty($alias)) : ?>
				<p class="smallsub small">(<span><?php echo JText::_('COM_JCALPRO_ALIAS'); ?></span>) <?php echo JCalProHelperFilter::escape($alias); ?></p>
				<?php endif; ?>
			</div>
			<?php if ($showDrop) : ?>
			<div class="pull-left"><?php
				if ($canEdit) {
					JHtml::_('dropdown.edit', $item->id, 'event.');
					JHtml::_('dropdown.divider');
				}
				if ($canChange) {
					JHtml::_('dropdown.' . ($item->published ? 'un' : '') . 'publish', 'cb' . $i, 'events.');
				}
				if ($item->checked_out && $canCheckin) {
					JHtml::_('dropdown.checkin', 'cb' . $i, 'events.');
				}
				if ($canDelete) {
					JHtml::_('dropdown.' . ($trashed ? 'un' : '') . 'trash', 'cb' . $i, 'events.');
				}
				
				echo JHtml::_('dropdown.render');
				
			?></div>
			<?php endif; ?>
		</td>
		<td class="hidden-phone">
			<strong><?php echo JCalProHelperFilter::escape($item->categories->canonical->title); ?></strong>
			<?php
				if (!empty($item->categories->categories)) :
					?><ul class="subcats"><?php
					foreach ($item->categories->categories as $cat) :
						?><li><span><?php echo JCalProHelperFilter::escape($cat->title); ?></span></li><?php
					endforeach;
					?></ul><?php
				endif;
			?>
		</td>
		<td class="hidden-phone">
			<?php echo JCalProHelperFilter::escape($item->timezone); ?>
		</td>
		<td class="hidden-phone hidden-tablet">
			<?php echo $startDateHtml; ?>
		</td>
		<td class="hidden-phone hidden-tablet">
			<?php echo $endDateHtml; ?>
		</td>
		<td class="hidden-desktop">
			<?php
				echo $startDateHtml;
				echo $endDateHtml;
			?>
		</td>
		<td class="hidden-phone">
			<?php
				if (0 == $item->recur_type)
					echo JText::_('COM_JCALPRO_RECUR_TYPE_STATIC');
				else if (0 == $item->rec_id)
					echo JText::_('COM_JCALPRO_RECUR_TYPE_REPEAT_PARENT');
				else if (0 == $item->detached_from_rec)
					echo JText::_('COM_JCALPRO_RECUR_TYPE_REPEAT_CHILD');
				else
					echo JText::_('COM_JCALPRO_RECUR_TYPE_REPEAT_DETACHED');
			?>
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
