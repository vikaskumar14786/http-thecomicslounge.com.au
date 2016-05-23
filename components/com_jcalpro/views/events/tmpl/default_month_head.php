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
<h1 style="text-align:left" class="componentheading">COMEDY CALENDAR </h1></br>
<div class="headBott">CLICK ON THE HIGHLIGHTED DATES BELOW FOR EVENT DETAILS.</div>
<div class="jcl_subtitlebar">
	<div class="jcl_left"><?php echo JText::_('COM_JCALPRO_MONTH_SECTION_TITLE'); ?></div>
	<div class="jcl_right ajaxheader"><?php echo $this->header; ?></div>
	<div class="jcl_clear"><!--  --></div>
</div>
<?php echo $this->loadTemplate('navbar'); ?>
<div class="startcalender">
<div class="jcl_month_table">
	<table class="jcl_month_row" cellpadding="0" cellspacing="0">
		<tr>
<?php if ($this->showWeek) : ?>
			<td class="jcl_month_week_number">&nbsp;</td>
<?php endif; ?>
			<td>
				<table class="jcl_month_inner_row">
					<tr>
<?php foreach ($this->dates->weekdays as $wd) : foreach ($wd as $k => $v) $$k = $v; ?>
						<td class="jcl_weekday <?php echo JCalProHelperFilter::escape($class); ?>"><?php echo JCalProHelperFilter::escape($name); ?></td>
<?php endforeach; ?>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</div>