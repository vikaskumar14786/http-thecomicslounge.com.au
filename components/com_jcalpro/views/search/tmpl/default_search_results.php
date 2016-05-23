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

?>
<table class="jcl_table">
	<thead>
		<tr class="jcl_header">
			<th width="60%" nowrap="nowrap">
				<h3 class="jcl_header"><?php
					echo JText::sprintf('COM_JCALPRO_N_RESULTS_FOUND', $this->pagination->total);
				?></h3>
			</th>
			<th align="center" nowrap="nowrap">
				<h3 class="jcl_header"><?php
					echo JText::_('COM_JCALPRO_CATEGORY');
				?></h3>
			</th>
			<th align="center" nowrap="nowrap">
				<h3 class="jcl_header"><?php
					echo JText::_('COM_JCALPRO_DATE');
				?></h3>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($this->items as $i => $item) : ?>
		<tr class="jcl_row jcl_row_<?php echo (0 == ($i + 1) % 2 ? 'even' : 'odd'); ?><?php echo $item->featured ? ' eventfeatured' : ''; ?>" itemscope itemtype="http://schema.org/Event">
			<td>
				<meta itemprop="url" content="<?php echo JCalProHelperFilter::escape($item->fullhref); ?>" />
				<meta itemprop="name" content="<?php echo JCalProHelperFilter::escape($item->title); ?>" />
				<meta itemprop="startDate" content="<?php echo JCalProHelperFilter::escape($item->datetime->format(DateTime::ISO8601)); ?>" />
				<div class="jcl_event_body jcl_nooverflow" style="border-left-color: <?php echo @$item->color; ?>;">
					<h3>
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
			</td>
			<td align="center">
				<a href="<?php echo JCalProHelperFilter::escape(JCalProHelperUrl::category($item->categories->canonical->id)); ?>"><?php
					echo JCalProHelperFilter::escape($item->categories->canonical->title);
				?></a>
			</td>
			<td align="center">
				<span class="atomic"><?php echo JCalProHelperFilter::escape($item->user_date_display->search); ?></span>
			</td>
		</tr>
		<?php endforeach; ?>
<?php if (1 < $this->pagination->get('pages.total')) : ?>
		<tr class="jcal_categories">
			<td align="left">
        <div class="pagination"><?php echo $this->pagination->getPagesLinks(); ?></div>
			</td>
			<td align="right" colspan="2">
				<div class="pagination">
					<p class="counter"><?php echo $this->pagination->getPagesCounter(); ?></p>
				</div>
			</td>
		</tr>
<?php endif; ?>
	</tbody>
</table>