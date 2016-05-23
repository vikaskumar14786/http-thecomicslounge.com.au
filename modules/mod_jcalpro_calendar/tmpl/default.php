<?php
/**
 * @package		JCalPro
 * @subpackage	mod_jcalpro_calendar

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

// shorthand
$id = (int) $module->id;

?>
<div id="jcalpro_calendar_<?php echo $id; ?>" class="jcalpro_calendar jcalpro_calendar<?php echo $moduleclass_sfx; ?>">
	<?php
	// we have to do this inside the main module element, so the script will exec in ajax mode
	modJCalProCalendarHelper::addScripts($module, $params);
	?>
	<div class="jcalpro_calendar_loader"></div>
	<div class="jcalpro_calendar_navbar">
		<a class="jcalpro_calendar_nav_prev jcalpro_calendar_nav_button" href="javascript:">&lt;</a>
		<a class="jcalpro_calendar_nav_next jcalpro_calendar_nav_button" href="javascript:">&gt;</a>
		<div class="jcalpro_calendar_month">
			<a href="<?php echo JCalProHelperFilter::escape(JCalProHelperUrl::events($dates->date->toRequest(), 'month')); ?>"><?php echo JCalProHelperFilter::escape($header); ?></a>
		</div>
	</div>
	<table class="jcalpro_calendar_table">
		<thead>
			<tr>
	<?php foreach ($dates->weekdays as $wd) : foreach ($wd as $k => $v) $$k = $v; ?>
				<!-- <?php echo JCalProHelperFilter::escape($rawname); ?> -->
				<th class="jcalpro_calendar_weekday"><?php echo JCalProHelperFilter::escape(JText::_('MOD_JCALPRO_CALENDAR_DAY_' . $rawname)); ?></th>
	<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
<?php for ($row=1-JCalProHelperDate::getFirstDay(); $row<=count($list); $row+=7) : ?>
			<tr>
<?php
		for ($col=0; $col<7; $col++) :
			$cell_day = $row + $col; 
			if (array_key_exists($cell_day, $list)) :
				$class_sfx = (0 == $list[$cell_day]['user_datetime']->weekday() ? 'sunday' : 'weekday');
				if ('todayclr' === $list[$cell_day]['class']) $class_sfx .= ' jcalpro_calendar_today';
				?><td class="jcalpro_calendar_<?php echo $class_sfx; ?>"><?php
				if (empty($list[$cell_day]['events'])) :
					echo $cell_day;
				else :
					// build tooltip
					$tip   = '';
					$class = 'jcalpro_calendar_link';
					if ($showtip) :
						$tip    = JText::sprintf('MOD_JCALPRO_CALENDAR_EVENTS_FOR_X', $list[$cell_day]['mod_calendar_date']) . '::';
						$class .= ' jcalpro_calendar_tip jcalpro_calendar_tip_' . $id;
						foreach ($list[$cell_day]['events'] as $event) :
							$title = JCalProHelperFilter::escape($event->title);
							$description = strip_tags(JCalProHelperFilter::purify($event->description));
							if ($limit) $description = JCalProHelperFilter::truncate($description, $limit);
							$description = JCalProHelperFilter::escape($description);
							$description = nl2br($description);
							$tip .= "<h3>$title</h3><div>$description</div>"; 
						endforeach;
					endif;
					?><a class="<?php echo $class; ?>" title="<?php echo JCalProHelperFilter::escape($tip); ?>" href="<?php echo JCalProHelperFilter::escape(JCalProHelperUrl::events($list[$cell_day]['user_datetime']->toRequest(), 'day', true, $urlparams)); ?>"><?php echo $cell_day; ?></a><?php
				endif;
				?></td><?php
			else :
				?><td class="jcalpro_calendar_empty"> </td><?php
			endif;
		endfor; ?>
			</tr>
<?php
		endfor; ?>
		</tbody>
	</table>
	<?php if (JCalPro::canAddEvents() && (int) $params->get('display_add', 1)) : ?>
	<span class="jcalpro_calendar_add">
		<a href="<?php echo JCalProHelperUrl::task('event.add', true, $urlparams); ?>" title="<?php echo JCalProHelperFilter::escape(JText::_('MOD_JCALPRO_CALENDAR_DISPLAY_ADD_TEXT')); ?>"><?php
			echo JCalProHelperFilter::escape(JText::_('MOD_JCALPRO_CALENDAR_DISPLAY_ADD_TEXT'));
		?></a>
	</span>
	<?php endif; ?>
	<?php if ((int) $params->get('display_events_link', 1)) : ?>
	<span class="jcalpro_events_link">
		<a href="<?php echo JCalProHelperUrl::events('', 'month', true, $urlparams); ?>" title="<?php echo JCalProHelperFilter::escape(JText::_('MOD_JCALPRO_CALENDAR_DISPLAY_EVENTS_LINK_TEXT')); ?>"><?php
			echo JCalProHelperFilter::escape(JText::_('MOD_JCALPRO_CALENDAR_DISPLAY_EVENTS_LINK_TEXT'));
		?></a>
	</span>
	<?php endif; ?>
	<?php if (defined('JDEBUG') && JDEBUG && (int) $params->get('debug', 0)) : JCalProHelperTheme::addStyleSheet('module_debug'); ?>
	<div class="jcalpro_module_debug">
		<h3>$list</h3>
		<?php JCalPro::debug($list); ?>
		<h3>$module</h3>
		<?php JCalPro::debug($module); ?>
		<h3>$params</h3>
		<?php JCalPro::debug($params); ?>
	</div>
	<?php endif; ?>
</div>