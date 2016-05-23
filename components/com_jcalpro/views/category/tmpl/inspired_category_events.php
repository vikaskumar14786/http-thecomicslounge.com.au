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

foreach ($this->items as $i => $item) :
	$color = JCalProHelperTheme::hexToRGB('' . @$item->color);
	if (is_array($color)) {
		$color = vsprintf("rgba(%d,%d,%d,0.3)", array_values($color));
	}
	$style = '';
	if (!empty($color)) {
		$style = ' style="border:1px solid ' . $color . ';-webkit-background-clip:padding-box;background-clip:padding-box"';
	}
?>
<div class="row-fluid list-item<?php echo $item->featured ? ' eventfeatured' : ''; ?>" itemscope itemtype="http://schema.org/Event">
	<meta itemprop="url" content="<?php echo JCalProHelperFilter::escape($item->fullhref); ?>" />
	<meta itemprop="name" content="<?php echo JCalProHelperFilter::escape($item->title); ?>" />
	<meta itemprop="startDate" content="<?php echo JCalProHelperFilter::escape($item->datetime->format(DateTime::ISO8601)); ?>" />
	<div class="alert alert-info"<?php echo $style ?>>
		<button type="button" class="close" data-dismiss="alert">×</button>
		<h3 class="list-item-title"><a id="w<?php echo $i; ?>" name="w<?php echo $i; ?>"><!--  --></a><?php
			$title = $item->title;
			if ($this->title_limit) $title = JCalProHelperFilter::truncate($title, $this->title_limit);
			$title = JCalProHelperFilter::escape($title);
			
			$time = $item->user_date_display->category;
			if (JCalPro::config('category_show_only_start_times')) {
				$time = $item->user_date_display->category_start;
			}
			
			if ($this->tpl) :
				echo $title;
			else :
				?><a href="<?php echo $item->href; ?>" class="eventtitle"><?php echo $title; ?></a><?php
			endif;
		?></h3>
		<h5><?php echo $time; ?></h5>
		<div>
			<?php echo $item->repeat_display_values->category; ?>
		</div>
<?php
if (JCalPro::version()->isCompatible('3.1.0')) :
	if (!empty($item->tags->itemTags)) :
		$item->tagLayout = new JLayoutFile('joomla.content.tags');
		echo $item->tagLayout->render($item->tags->itemTags);
	endif;
endif;
?>
		<?php if ($this->show_description) : ?>
		<div class="jcl_event_description"><?php
			if ($item->show_readmore) :
				$description = $item->description_intro;
			else :
				$description = $item->description;
				if ($this->description_limit) $description = JCalProHelperFilter::truncate($description, $this->description_limit);
			endif;
			
			echo JCalProHelperFilter::purify($description);
			echo $item->description_readmore;
		?></div>
		<?php endif; ?>
	</div>
</div>
<?php endforeach; ?>
<?php if (1 < $this->pagination->get('pages.total')) : ?>
<div class="row-fluid">
	<div class="span10 pagination"><?php echo $this->pagination->getPagesLinks(); ?></div>
	<div class="span2 pagination-counter"><?php echo $this->pagination->getPagesCounter(); ?></div>
</div>
<?php endif; ?>

