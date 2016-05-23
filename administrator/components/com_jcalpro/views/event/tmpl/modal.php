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

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');

$input = JFactory::getApplication()->input;

JText::script('COM_JCALPRO_CATSELECT_SELECT_ONE');
JText::script('COM_JCALPRO_CATSELECT_COULD_NOT_CHANGE');
JFactory::getDocument()->addScript(JCalProHelperUrl::media() . '/js/catselect.js');
JFactory::getDocument()->addScriptDeclaration('window.jclCatselectUrl = \''.JCalProHelperFilter::escape_js(JCalProHelperUrl::task('event.add', false)).'\';');

$options = JHtml::_('jcalpro.calendarlistoptions', array(), false, true);
$select  = JHtml::_('select.genericlist', $options, 'catid', 'class="listbox" size="12" width="100%"', 'value', 'text', $input->get('catid', 0, 'uint'));
$Itemid  = $input->get('Itemid', 0, 'uint');
$Itemid  = $Itemid ? '<input type="hidden" name="Itemid" value="' . $Itemid . '" />' : '';
?>
<div id="jcl_component" class="<?php echo $this->viewClass; ?>">
	<form action="<?php echo JCalProHelperUrl::task('event.add'); ?>" method="post" name="adminform" onsubmit="return false;" class="catselectForm">
		<div class="modal-header">
			<button id="jclCatidSelectButton"><?php
				echo JText::_('COM_JCALPRO_CATSELECT_BUTTON');
			?></button>
			<span class="modal-title"><?php echo JText::_('COM_JCALPRO_CATSELECT_SELECT_CATEGORY'); ?></span>
		</div>
		<fieldset class="adminform">
			<?php
				echo $select;
				echo $Itemid;
			?>
			<input id="event-date" type="hidden" name="date" value="<?php echo JCalProHelperFilter::escape($input->get('date', '', 'string')); ?>" />
		</fieldset>
	</form>
</div>
<?php

echo $this->loadTemplate('responsive', 'modal');

