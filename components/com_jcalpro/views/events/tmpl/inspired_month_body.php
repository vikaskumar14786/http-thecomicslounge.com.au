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

// begin calendar table
$archive   = (int) JCalPro::config('archive');
$showIcons = (int) JCalPro::config('month_show_icons', 1);
$showTimes = (int) JCalPro::config('month_show_time', 1);

?>
<div class="monthview clearfix">

<?php if (0 == $this->itemscount) : ?>
<div class="alert alert-error">
	<button type="button" class="close" data-dismiss="alert">×</button>
	<?php echo JText::_('COM_JCALPRO_EVENTS_EMPTY'); ?>
</div>
<?php endif; ?>

<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<?php foreach ($this->dates->weekdays as $wd) : foreach ($wd as $k => $v) $$k = $v; ?>
				<th><?php echo JCalProHelperFilter::escape($name); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>

<tbody>
<?php for ($row=1-JCalProHelperDate::getFirstDay(); $row<=count($this->items); $row+=7) : ?>

<tr class="jcl_month_row">

<?php for ($col=0; $col<7; $col++) : $cell_day = $row + $col;
	// check if we have a stack for this day
	if (!array_key_exists($cell_day, $this->items)) :
	// cells outside the month - if negative, they are in the previous month and if not they are in the next
		$month = ($cell_day < 1 ? $this->dates->prev_month : $this->dates->next_month); ?>
		<td class ="disabled">
			<?php echo $month->format(JText::_('COM_JCALPRO_DATE_FORMAT_MONTH_YEAR')); ?>
		</td><?php
	// cells for this month
	else :
	// define our stack
	$stack = $this->items[$cell_day];
?>

<td class="calendar-weekday <?php echo $stack['class']; if (!empty($stack['events'])) echo ' cell_events'; ?>">
	<div class="calendar-weekday-top">
		<div class="calendar-day pull-left">
			<?php if (!empty($stack['events']) && !$this->tpl) : ?>
				<a class="calendar-date" href="<?php echo JCalProHelperUrl::events($stack['user_datetime']->toRequest(), 'day'); ?>" rel="nofollow">
					<?php echo $cell_day; ?>
				</a>
			<?php else : ?>
					<?php echo $cell_day; ?>
			<?php endif; ?>
		</div>
	
		<div class="jcl_month_add pull-right">
			<?php
			// check if user is allowed to create events
				if (JCalPro::canAddEvents() && !$this->tpl && (!$archive || ($archive && $stack['user_datetime'] >= $this->dates->today))) : 
					echo JHtml::_('jcalpro.addlink', JHtml::_('jcalpro.image', 'addsign.gif', $this->template, array(
						'name'   => "add$cell_day"
					,	'alt'    => JCalProHelperFilter::escape(JText::sprintf('COM_JCALPRO_ADD_NEW_EVENT_ON', $stack['user_datetime']->format(JText::_('COM_JCALPRO_DATE_FORMAT_FULL_DATE'))))
					,	'border' => 0
					)), 'noajax', array('date'=>$stack['user_datetime']->toRequest()));
				endif; ?>
		</div>
	</div>

	<?php if (!empty($stack['events'])) : foreach ($stack['events'] as $event) : ?>

<div class="calendar-events<?php echo $event->featured ? ' eventfeatured' : ''; ?>" itemscope itemtype="http://schema.org/Event">
	<meta itemprop="url" content="<?php echo JCalProHelperFilter::escape($event->fullhref); ?>" />
	<meta itemprop="name" content="<?php echo JCalProHelperFilter::escape($event->title); ?>" />
	<meta itemprop="startDate" content="<?php echo JCalProHelperFilter::escape($event->datetime->format(DateTime::ISO8601)); ?>" />
	<?php
		$icon = '';
		if ($showIcons) {
			$icon = JHtml::_('jcalpro.image', 'events/icon-event-' . $event->icon . '.gif', $this->template, array(
				'alt'    => ''
			,	'border' => 0
			));
		}

		$title = $event->title;
		if ($this->title_limit) $title = JCalProHelperFilter::truncate($title, $this->title_limit);
		$title = JCalProHelperFilter::escape($title);
		
		$time = '';
		if ($showTimes && JCalPro::JCL_EVENT_DURATION_ALL !== (int) $event->duration_type) {
			$time = $event->user_timedisplay;
			if (1 == (int) JCalPro::config('show_only_start_times') && property_exists($event, 'user_start_timedisplay')) {
				$time = $event->user_start_timedisplay;
			}
			$time = sprintf(' <small>%s</small>', $time);
		}
		$titleDisplay = $icon . $title . $time;
		
		if ($event->show_readmore) :
			$description = $event->description_intro;
		else :
			$description = $event->description;
			if ($this->description_limit) $description = JCalProHelperFilter::truncate($description, $this->description_limit);
		endif;
		
		$description = JCalProHelperFilter::escape(strip_tags(JCalProHelperFilter::purify($description)));
		
		if ($this->tpl) :
			echo $titleDisplay;
		else :
	?>
		<a class="eventtitle noajax label<?php if (JCalProHelperTheme::isTooWhite($event->color)) echo ' dark'; ?>" style="background: <?php echo $event->color; ?>" href="<?php echo JRoute::_($event->href);
			if ($this->show_description) :
				?>" rel="tooltip hasTip" title="<?php echo $title . (empty($description) ? '' : '::' . $description);
			endif;
			?>"><?php
			echo $titleDisplay;
		?></a>
	<?php endif; ?>
</div>


<?php
			endforeach;
		endif;
?>
</td>
<?php
	endif;
endfor;

?>


<?php endfor; ?>
</tr>
</tbody>
</table>
</div>
<?php if (!$this->tpl) echo $this->loadTemplate('categories_legend'); ?>