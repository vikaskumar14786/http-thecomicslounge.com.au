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

if ($this->raw) :
?>
<div style="display:none;">
	<form action="" method="post" id="jcl_layout_values">
		<input type="hidden" id="jcl_layout_value_next_href" value="<?php echo $this->linkdata['next']['href']; ?>" />
		<input type="hidden" id="jcl_layout_value_next_text" value="<?php echo $this->linkdata['next']['text']; ?>" />
		<input type="hidden" id="jcl_layout_value_prev_href" value="<?php echo $this->linkdata['prev']['href']; ?>" />
		<input type="hidden" id="jcl_layout_value_prev_text" value="<?php echo $this->linkdata['prev']['text']; ?>" />
		<input type="hidden" id="jcl_layout_value_header_text" value="<?php echo $this->header; ?>" />
		<input type="hidden" id="jcl_layout_value_current_text" value="<?php echo $this->linkdata['current']; ?>" />
		<input type="hidden" id="jcl_layout_value_date" value="<?php echo $this->dates->date->toRequest(); ?>" />
		<div id="jcl_layout_toolbar"><?php echo $this->loadTemplate('toolbar'); ?></div>
	</form>
</div>
<?php
endif;
