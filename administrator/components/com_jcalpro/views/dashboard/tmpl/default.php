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

JText::script('COM_JCALPRO_EXTENSIONUPDATE_UPDATEFOUND');
JText::script('COM_JCALPRO_EXTENSIONUPDATE_UPTODATE');
JText::script('COM_JCALPRO_EXTENSIONUPDATE_ERROR');

$urlopts = array('option' => 'com_installer', 'view' => 'update');

$updateurl = JCalProHelperFilter::escape_js(JCalProHelperUrl::_(array_merge($urlopts, array('task' => 'update.ajax', 'eid' => $this->component_id))));
$updateaction = JCalProHelperFilter::escape_js(JCalProHelperUrl::_(array_merge($urlopts, array(	'task' => 'update.update'))));
$updatetoken = JFactory::getSession()->getFormToken();

JCalPro::loadJsFramework();

$doc = JFactory::getDocument();
$doc->addScript(JCalProHelperUrl::media() . '/js/updatecheck.js');
$doc->addScriptDeclaration("window.jcalproupdateurl = '$updateurl';");
$doc->addScriptDeclaration("window.jcalproupdateaction = '$updateaction';");
$doc->addScriptDeclaration("window.jcalproupdatetoken = '$updatetoken';");

?>
<div id="jcl_component" class="<?php echo $this->viewClass; ?>">
	<div id="jcalpro_extensionupdate"><!-- Loading updates ... --></div>
	<div id="cpanel">
		<table class="adminform" width="100%">
			<tr>
				<td width="60%" valign="top" class="hidden-phone">
<?php
foreach ($this->buttons as $button) {
	$this->currentButton = $button;
	echo $this->loadTemplate('button');
}
?>
				</td>
				<td valign="top">
					<form id="adminForm" action="<?php echo JRoute::_('index.php?option=com_jcalpro');?>" method="post" name="adminForm">
						<?php echo $this->loadTemplate('panes'); ?>
					</form>
				</td>
			</tr>
		</table>
	</div>
</div>