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
<div id="jcl_install" style="text-align:left">
	<div style="padding-left:13px">
		<?php
			echo JHtml::_('jcalpro.image', 'jcal_logo.png', '', array('alt' => JText::_('COM_JCALPRO_INSTALLER_TITLE')));
		?>
	</div>
	<div class="clr"></div>
	<div class="m" style="width:70%;float:left;margin-top:8px">
		<p style="font-size:1.1em;font-weight:bold;"><?php echo JText::sprintf('COM_JCALPRO_ABOUT_JCALPRO_TEXT', $this->details['version']); ?></p>
		<?php echo JText::_('COM_JCALPRO_INSTALLER_DESCRIPTION'); ?>
	</div>
	<div style="width:20%;float:right">
		<form id="jcl_install_default" method="post" action="index.php?option=com_jcalpro&task=installDefault">
			<fieldset id="jcl_install_default_fieldset" class="fltrt" style="width:98%">
				<legend><?php echo JText::_('COM_JCALPRO_INSTALLER_GETTING_STARTED'); ?></legend>
				<ul>
					<li><a href="index.php?option=com_categories&extension=com_jcalpro"><?php echo JText::_('COM_JCALPRO_INSTALLER_MANAGE_CATEGORIES'); ?></a></li>
					<li><a href="index.php?option=com_jcalpro&view=events"><?php echo JText::_('COM_JCALPRO_INSTALLER_MANAGE_EVENTS'); ?></a></li>
					<li><a href="index.php?option=com_jcalpro&view=forms"><?php echo JText::_('COM_JCALPRO_INSTALLER_CREATE_CUSTOM_FORMS'); ?></a></li>
					<li><a href="index.php?option=com_jcalpro&view=fields"><?php echo JText::_('COM_JCALPRO_INSTALLER_CREATE_FORM_FIELDS'); ?></a></li>
					<li><a href="index.php?option=com_jcalpro&view=help"><?php echo JText::_('COM_JCALPRO_INSTALLER_READ_DOCUMENTATION'); ?></a></li>
				</ul>
				<div id="jcl_install_elements">
<?php if ($this->showSampleDataButton) : ?><input type="button" style="float:none;display:block;" id="jcl_sample_data" value="<?php echo JText::_('COM_JCALPRO_INSTALLER_SAMPLE_DATA'); ?>" /><?php endif; ?>
<?php if ($this->showMigrationButton)  : ?><input type="button" style="float:none;display:block;" id="jcl_migrate_data" value="<?php echo JText::_('COM_JCALPRO_INSTALLER_MIGRATE_DATA'); ?>" /><?php endif; ?>
<?php if ($this->showCategoriesButton)  : ?><input type="button" style="float:none;display:block;" id="jcl_fixcategories_data" value="<?php echo JText::_('COM_JCALPRO_INSTALLER_FIX_CATEGORIES'); ?>" /><?php endif; ?>
<?php if ($this->showAssetsButton)  : ?><input type="button" style="float:none;display:block;" id="jcl_fixassets_data" value="<?php echo JText::_('COM_JCALPRO_INSTALLER_FIX_ASSETS'); ?>" /><?php endif; ?>
				</div>
				<div class="clr"></div>
			</fieldset>
		</form>
	</div>
	<div class="clr"></div>
	<!--
	<div class="width-100 fltlft">
		<?php echo JText::_('COM_JCALPRO_LICENSE_INFO'); ?>
	</div>
	-->
</div>
