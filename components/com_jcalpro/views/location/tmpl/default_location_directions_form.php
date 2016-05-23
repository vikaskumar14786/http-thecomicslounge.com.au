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

JHtml::_('behavior.modal');

?>
<div id="jcl_location_directions">
	<div class="row-fluid">
		<span class="pull-left jcl_location_buttons">
			<span class="btn-toolbar">
				<span class="btn-group">
					<button type="button" id="jcl_location_directions_geo" class="btn hasTip" title="<?php echo JText::_('COM_JCALPRO_LOCATION_GET_DIRECTIONS_FROM_GEO'); ?>"><i class="icon-home"> </i> <span class="hidden-tablet hidden-desktop"><?php echo JText::_('COM_JCALPRO_LOCATION_GET_DIRECTIONS_FROM_GEO_BUTTON'); ?></span></button>
					<button type="button" id="jcl_location_directions_link" class="btn hasTip" title="<?php echo JText::_('COM_JCALPRO_LOCATION_GET_DIRECTIONS_LINK'); ?>" data-linkpattern="<?php echo JCalProHelperFilter::escape(JCalProHelperUrl::toFull(JCalProHelperUrl::location($this->item->id, true, array('address' => '')))); ?>"><i class="icon-share-alt"> </i> <span class="hidden-tablet hidden-desktop"><?php echo JText::_('COM_JCALPRO_LOCATION_GET_DIRECTIONS_LINK_BUTTON'); ?></span></button>
				</span>
			</span>
		</span>
		<span class="form-search pull-left jcl_location_search">
			<span class="input-append">
				<input id="jcl_location_directions_address" type="text" class="search-query" placeholder="<?php echo JText::_('COM_JCALPRO_LOCATION_GET_DIRECTIONS_PLACEHOLDER'); ?>" value="<?php echo JCalProHelperFilter::escape($this->address); ?>" />
				<button type="button" id="jcl_location_directions_search" class="btn btn-primary hasTip" title="<?php echo JText::_('COM_JCALPRO_LOCATION_GET_DIRECTIONS_DESC'); ?>"><i class="icon-search"> </i> <span class="hidden-phone"><?php echo JText::_('COM_JCALPRO_LOCATION_GET_DIRECTIONS'); ?></span></button>
			</span>
		</span>
	</div>
	<div id="jcl_location_directions_panel"></div>
</div>
<noscript>
	<style>
	#jcl_location_directions{display:none;visibility:hidden}
	</style>
</noscript>
