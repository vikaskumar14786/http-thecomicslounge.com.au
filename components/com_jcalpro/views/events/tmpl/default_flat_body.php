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

$defaultformat = JText::_('COM_JCALPRO_DATE_FORMAT_FULL_DATE');
$dayformat     = JCalPro::config('flat_format_days', $defaultformat);

for ($i=1; $i<=count($this->items); $i++) :
	if (!empty($this->items[$i]['events'])) :
		try {
			$itemdate = $this->items[$i]['user_datetime']->format($dayformat);
		}
		catch (Exception $e) {
			$itemdate = $this->items[$i]['user_datetime']->format($defaultformat);
		}
?>
<h2 class="jcl_header"><a id="w<?php echo $i; ?>" name="w<?php echo $i; ?>"><!--  --></a><?php
	echo JCalProHelperFilter::escape($itemdate);
?></h2>
<?php
		foreach ($this->items[$i]['events'] as $n => $event) :
?>
<div class="jcl_row jcl_row_<?php echo (0 == ($n + 1) % 2 ? 'even' : 'odd'); ?>" itemscope itemtype="http://schema.org/Event">
	<meta itemprop="url" content="<?php echo JCalProHelperFilter::escape($event->fullhref); ?>" />
	<meta itemprop="name" content="<?php echo JCalProHelperFilter::escape($event->title); ?>" />
	<meta itemprop="startDate" content="<?php echo JCalProHelperFilter::escape($event->datetime->format(DateTime::ISO8601)); ?>" />
	<div class="jcl_event_body jcl_nooverflow<?php echo $event->featured ? ' eventfeatured' : ''; ?>" style="border-left-color: <?php echo @$event->color; ?>;">
		<h3>
			<?php
				$title = $event->title;
				if ($this->title_limit) $title = JCalProHelperFilter::truncate($title, $this->title_limit);
				$title = JCalProHelperFilter::escape($title);
				
				$time = $event->user_date_display->flat;
				if (JCalPro::JCL_EVENT_DURATION_ALL == $event->duration_type) {
					$time = JText::_('COM_JCALPRO_ALL_DAY');
				}
				else if (JCalPro::config('flat_show_only_start_times')) {
					$time = $event->user_date_display->flat_start;
				}
				
				if ($this->tpl) :
					echo $title; ?> (<?php echo $time; ?>)<?php
				else :
					?><a href="<?php echo $event->href; ?>" class="eventtitle noajax"><?php echo $title; ?> (<?php echo $time; ?>)</a><?php
				endif;
			?>
		</h3>
		<?php if ($this->show_description) : ?>
		<div class="jcl_event_description"><?php
			if ($event->show_readmore) :
				$description = $event->description_intro;
			else :
				$description = $event->description;
				if ($this->description_limit) $description = JCalProHelperFilter::truncate($description, $this->description_limit);
			endif;
			echo JCalProHelperFilter::purify($description);
			echo $event->description_readmore;
		?></div>
		<?php endif; ?>
	</div>
</div>
<?php
		endforeach;
	endif;
endfor;
	
if (!$this->tpl) echo $this->loadTemplate('categories');
