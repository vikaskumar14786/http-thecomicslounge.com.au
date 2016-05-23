<?php
/**
 * @package		JCalPro
 * @subpackage	mod_jcalpro_locations

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
<div id="jcalpro_locations_<?php echo (int) $module->id; ?>" class="jcalpro_locations<?php if (!empty($moduleclass_sfx)) : echo " jcalpro_locations{$moduleclass_sfx}"; endif; ?>">
	<div class="jcalpro_locations_map"><?php echo JText::_('MOD_JCALPRO_LOCATIONS_LOADING_MAP'); ?></div>
</div>
<?php if (defined('JDEBUG') && JDEBUG && (int) $params->get('debug', 0)) : JCalProHelperTheme::addStyleSheet('module_debug'); ?>
<div><button onclick="(function(){localStorage.removeItem('jcl_mod_geo_lat');localStorage.removeItem('jcl_mod_geo_lng');})();"><?php echo JText::_('MOD_JCALPRO_LOCATIONS_CLEAR_LOCAL_DATA'); ?></button></div>
<div class="jcalpro_module_debug">
	<h3>$module</h3>
	<?php JCalPro::debug($module); ?>
	<h3>$params</h3>
	<?php JCalPro::debug($params); ?>
</div>
<?php endif; ?>
