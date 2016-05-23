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
<div class="jcl_subtitlebar">
	<div class="jcl_left"><?php echo $this->linkdata['current']; ?></div>
	<div class="jcl_clear"><!--  --></div>
</div>
<table class="jcl_table" width="100%">
	<thead>
		<tr class="jcl_header">
			<th width="90%" nowrap="nowrap">
				<h2 class="jcl_header"><?php
					echo JText::_('COM_JCALPRO_CATEGORY_NAME');
				?></h2>
			</th>
			<th align="center" nowrap="nowrap">
				<h2 class="jcl_header"><?php
					echo JText::_('COM_JCALPRO_UPCOMING_EVENTS');
				?></h2>
			</th>
			<th align="center" nowrap="nowrap">
				<h2 class="jcl_header"><?php
					echo JText::_('COM_JCALPRO_TOTAL_EVENTS');
				?></h2>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($this->items as $i => $item) : $indent = ((($item->level - 1) * 6) + 1); ?>
		<tr class="jcl_row jcl_row_<?php echo (0 == ($i + 1) % 2 ? 'even' : 'odd'); ?>">
			<td width="90%" nowrap="nowrap">
				<div class="jcl_event_body" style="border-left-color: <?php echo $item->params->get('jcalpro_color'); ?>;">
					<h3>
						<?php
							$title = $item->title;
							if ($this->title_limit) $title = JCalProHelperFilter::truncate($title, $this->title_limit);
							$title = JCalProHelperFilter::escape($title);
							
							if ($this->print) :
								echo $title;
							else :
								?><a href="<?php echo JCalProHelperUrl::category($item->id); ?>" class="eventtitle"><?php echo $title; ?></a><?php
							endif;
						?>
					</h3>
					<?php if ($this->show_description) : ?>
					<div class="jcl_event_description"><?php
						$description = $item->description;
						if ($this->description_limit) $description = JCalProHelperFilter::truncate($description, $this->description_limit);
						echo JCalProHelperFilter::purify($description);
					?></div>
					<?php endif; ?>
				</div>
			</td>
			<td align="center" nowrap="nowrap"><?php echo (int) $item->upcoming_events; ?></td>
			<td align="center" nowrap="nowrap"><?php echo (int) $item->total_events; ?></td>
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
