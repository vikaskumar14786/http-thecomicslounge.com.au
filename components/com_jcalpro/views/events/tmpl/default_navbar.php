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

if (!$this->tpl) :
	JHtml::_('behavior.calendar');
?>
<div class="jcl_navbar">
	<div class="jcl_nav jcl_previous jcl_left">
		&nbsp;<a class="ajaxlayoutlink ajaxprev" href="<?php echo JCalProHelperFilter::escape($this->linkdata['prev']['href']); ?>"<?php if (empty($this->linkdata['prev']['text'])) : ?> style="display:none"<?php endif; ?>>
		<?php echo JCalProHelperFilter::escape($this->linkdata['prev']['text']); ?></a>
	</div>
	<div class="jcl_nav jcl_current jcl_left">
		<div class="ajaxcurrent" id="jcl_nav_calendar_date_button"><?php echo JCalProHelperFilter::escape($this->linkdata['current']); ?></div>
		<input type="hidden" id="jcl_nav_calendar_date" value="" />
	</div>
	<div class="jcl_nav jcl_next jcl_left">
		<a class="ajaxlayoutlink ajaxnext" href="<?php echo JCalProHelperFilter::escape($this->linkdata['next']['href']); ?>">
		<?php echo JCalProHelperFilter::escape($this->linkdata['next']['text']); ?></a>&nbsp;
	</div>
	<div class="jcl_clear"><!--  --></div>
</div>
<div class="jcl_clear"><!--  --></div>
<script type="text/javascript">
JCalPro.onLoad(function(){
	Calendar.setup({
		inputField: 'jcl_nav_calendar_date',
		ifFormat: '%Y-%m-%d',
		button: 'jcl_nav_calendar_date_button',
		align: "Bl",
		singleClick: true,
		date: new Date('<?php echo $this->dates->date->format('Y-m-d'); ?>'),
		onUpdate: function(cal) {
			var yyyy = cal.date.getFullYear().toString()
			,   mm   = (cal.date.getMonth()+1).toString()
			,   dd   = cal.date.getDate().toString()
			,   date = yyyy + '-' + (mm[1]?mm:"0"+mm[0]) + '-' + (dd[1]?dd:"0"+dd[0])
			;
			if ('undefined' == typeof window.jcl_ajax_mode_active) {
				var url = window.location.href;
				// no variables at all
				if (!url.match(/\?/)) {
					window.location.href = url + '?date=' + date;
				}
				// variables
				else {
					if (url.match(/date\=/)) {
						url = url.replace(/date\=[0-9]{4}\-[0-9]{2}\-[0-9]{2}/, 'date=' + date);
						window.location.href = url;
					}
					else {
						window.location.href = url + '&date=' + date;
					}
				}
				return;
			}
			window.location.hash = date;
		}
	});
});
</script>
<?php
endif;
