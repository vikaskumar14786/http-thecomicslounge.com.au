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

if (!empty($this->items)) : foreach ($this->items as $i => $event) :
	// some funky freshness for the borders ;)
	$color = JCalProHelperTheme::hexToRGB('' . @$event->color);
	if (is_array($color)) {
		$color = vsprintf("rgba(%d,%d,%d,0.3)", array_values($color));
	}
	$style = '';
	if (!empty($color)) {
		$style = ' style="border:1px solid ' . $color . ';-webkit-background-clip:padding-box;background-clip:padding-box"';
	}
?>
<div class="row-fluid list-item<?php echo $event->featured ? ' eventfeatured' : ''; ?>" itemscope itemtype="http://schema.org/Event">
	<meta itemprop="url" content="<?php echo JCalProHelperFilter::escape($event->fullhref); ?>" />
	<meta itemprop="name" content="<?php echo JCalProHelperFilter::escape($event->title); ?>" />
	<meta itemprop="startDate" content="<?php echo JCalProHelperFilter::escape($event->datetime->format(DateTime::ISO8601)); ?>" />
	<div class="alert alert-info"<?php echo $style ?>>
		<button type="button" class="close" data-dismiss="alert">×</button>
		<h3>
			<?php
				$title = $event->title;
				if ($this->title_limit) $title = JCalProHelperFilter::truncate($title, $this->title_limit);
				$title = JCalProHelperFilter::escape($title);
				
				$time = $event->user_date_display->day;
				if (JCalPro::JCL_EVENT_DURATION_ALL == $event->duration_type) {
					$time = JText::_('COM_JCALPRO_ALL_DAY');
				}
				else if (JCalPro::config('day_show_only_start_times')) {
					$time = $event->user_date_display->day_start;
				}
				
				if ($this->tpl) :
					echo $title;
				else :
					?><a href="<?php echo $event->href; ?>" class="eventtitle noajax"><?php echo $title; ?></a><?php
				endif;
			?>
		</h3>
		<h5><?php echo $time; ?></h5>
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
<?php
if (JCalPro::version()->isCompatible('3.1.0')) :
	$tags = $event->tags->getItemTags(JCalPro::COM . '.event' , $event->id);
	if (!empty($tags)) :
		$event->tagLayout = new JLayoutFile('joomla.content.tags');
		echo $event->tagLayout->render($tags);
	endif;
endif;
?>
	</div>
</div>
<?php endforeach; else: ?>
<div class="alert alert-error">
	<button type="button" class="close" data-dismiss="alert">×</button>
	<?php echo JText::_('COM_JCALPRO_DAY_NO_EVENTS'); ?>
</div>
<?php endif; ?>
<?php if (!$this->tpl) echo $this->loadTemplate('categories_legend'); ?>