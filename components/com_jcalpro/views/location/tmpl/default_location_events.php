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

if (!empty($this->events)) :
?>
<div class="jcl_location_events">
	<h3><?php echo JText::_('COM_JCALPRO_' . strtoupper($this->events_type)); ?></h3>
	<?php foreach ($this->events as $i => $event) : ?>
	<div class="jcl_row jcl_row_<?php echo (0 == ($i + 1) % 2 ? 'even' : 'odd'); ?>">
		<div class="jcl_event_body jcl_nooverflow<?php echo $event->featured ? ' eventfeatured' : ''; ?>" style="border-left-color: <?php echo @$event->color; ?>;" itemscope itemtype="http://schema.org/Event">
			<meta itemprop="url" content="<?php echo JCalProHelperFilter::escape($event->fullhref); ?>" />
			<meta itemprop="name" content="<?php echo JCalProHelperFilter::escape($event->title); ?>" />
			<meta itemprop="startDate" content="<?php echo JCalProHelperFilter::escape($event->datetime->format(DateTime::ISO8601)); ?>" />
			<h3>
				<?php
					$title = $event->title;
					if ($this->title_limit) $title = JCalProHelperFilter::truncate($title, $this->title_limit);
					$title = JCalProHelperFilter::escape($title);
				
					$time = $event->user_date_display->location;
					if (JCalPro::config('location_show_only_start_times')) {
						$time = $event->user_date_display->location_start;
					}
					
					if ($this->tpl) :
						echo $title;
					else :
						?><a href="<?php echo $event->href; ?>" class="eventtitle noajax"><?php echo $title; ?></a><?php
					endif;
				?>
			</h3>
			<h5><?php echo $time; ?></h5>
		</div>
	</div>
	<?php endforeach; ?>
</div>
<?php endif;