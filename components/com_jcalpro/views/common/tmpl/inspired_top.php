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

?>

<div id="jcl_component" class="<?php echo $this->viewClass; ?>">
	<div class="jcl_<?php echo JCalProHelperFilter::escape($this->extmode); ?>">
<?php if ($this->print) : ?>
		<div id="jcl_print_image" class="jcl_print_title">
			<a onclick="document.getElementById('jcl_print_image').style.display='none';window.print();window.close();return false;" href="#" class="jcl_print_button btn"><i class="icon-print"></i> <?php
				echo JText::_('COM_JCALPRO_MAINMENU_PRINT');
			?></a>
		</div>
<?php endif; ?>
		<div class="jcl_mainview">
