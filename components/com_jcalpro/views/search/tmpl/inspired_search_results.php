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

foreach ($this->items as $i => $item) : ?>
<div class="row-fluid list-item<?php echo $item->featured ? ' eventfeatured' : ''; ?>" itemscope itemtype="http://schema.org/Event">
	<meta itemprop="url" content="<?php echo JCalProHelperFilter::escape($item->fullhref); ?>" />
	<meta itemprop="name" content="<?php echo JCalProHelperFilter::escape($item->title); ?>" />
	<meta itemprop="startDate" content="<?php echo JCalProHelperFilter::escape($item->datetime->format(DateTime::ISO8601)); ?>" />
	<div class="alert alert-info">
		<button type="button" class="close" data-dismiss="alert">×</button>
		<h3 class="list-item-title" style="border-left-color: <?php echo @$item->color; ?>;">
			<?php
				$title = $item->title;
				if ($this->title_limit) $title = JCalProHelperFilter::truncate($title, $this->title_limit);
				$title = JCalProHelperFilter::escape($title);
				
				if ($this->tpl) :
					echo $title;
				else :
					?><a href="<?php echo $item->href; ?>" class="eventtitle"><?php echo $title; ?></a><?php
				endif;
			?>
		</h3>
		<h5><?php echo $item->user_date_display->search; ?></h5>
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
