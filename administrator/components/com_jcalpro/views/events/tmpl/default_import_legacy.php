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
<div class="width-100 importform">
	<fieldset class="adminform">
		<legend><?php echo JText::_('COM_JCALPRO_IMPORT_EVENTS'); ?></legend>
		<ul class="adminformlist">
			<li>
				<label for="catid"><?php echo JText::_('COM_JCALPRO_IMPORT_SELECT_CATEGORY'); ?></label>
			</li>
			<li>
				<div class="fltlft"><?php echo $this->importcats; ?>&nbsp;</div>
				<div class="fltlft">
					<input type="checkbox" id="guesscats" name="guesscats" value="1" />
				</div>
				<div class="fltlft">
					<?php echo JText::_('COM_JCALPRO_IMPORT_GUESS_CATEGORY'); ?>
				</div>
			</li>
			<li>
				<label for="localics"><?php echo JText::_('COM_JCALPRO_IMPORT_FROM_LOCAL_FILE_LABEL'); ?></label>
			</li>
			<li>
	      <input id="localics" name="localics" size="80" type="file">
	      <button type="submit" onclick="Joomla.submitbutton('event.importlocal');">
	      	<?php echo JText::_('COM_JCALPRO_IMPORT_FROM_LOCAL_FILE'); ?>
	      </button>
	    </li>
			<li>
				<label for="remoteics"><?php echo JText::_('COM_JCALPRO_IMPORT_FROM_REMOTE_FILE_LABEL'); ?></label>
			</li>
			<li>
	      <input id="remoteics" name="remoteics" size="80" type="text">
	      <button type="submit" onclick="Joomla.submitbutton('event.importremote');">
	      	<?php echo JText::_('COM_JCALPRO_IMPORT_FROM_REMOTE_FILE'); ?>
	      </button>
	    </li>
		</ul>
	</fieldset>
</div>