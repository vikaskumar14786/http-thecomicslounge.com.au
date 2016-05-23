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


if (!empty($this->item->location_data)) :
	JFactory::getDocument()->addScript('//maps.googleapis.com/maps/api/js?sensor=false');
?>
<div class="eventdescright_map" itemprop="location" itemscope itemtype="http://schema.org/Place">
	<meta itemprop="url" content="<?php echo JCalProHelperFilter::escape(JCalProHelperUrl::toFull(JCalProHelperUrl::location($this->item->location_data->id))); ?>" />
	<meta itemprop="name" content="<?php echo JCalProHelperFilter::escape($this->item->location_data->title); ?>" />
	<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
		<meta itemprop="streetAddress" content="<?php echo JCalProHelperFilter::escape($this->item->location_data->address); ?>" />
<?php if (!empty($this->item->location_data->city)) : ?>
		<meta itemprop="addressLocality" content="<?php echo JCalProHelperFilter::escape($this->item->location_data->city); ?>" />
<?php endif; ?>
<?php if (!empty($this->item->location_data->state)) : ?>
		<meta itemprop="addressRegion" content="<?php echo JCalProHelperFilter::escape($this->item->location_data->state); ?>" />
<?php endif; ?>
<?php if (!empty($this->item->location_data->postal_code)) : ?>
		<meta itemprop="postalCode" content="<?php echo JCalProHelperFilter::escape($this->item->location_data->postal_code); ?>" />
<?php endif; ?>
	</div>
	<div itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
		<meta itemprop="latitude" content="<?php echo JCalProHelperFilter::escape($this->item->location_data->latitude); ?>" />
		<meta itemprop="longitude" content="<?php echo JCalProHelperFilter::escape($this->item->location_data->longitude); ?>" />
	</div>
	<div id="jcl_event_map_container">
		<div id="jcl_event_map"> </div>
	</div>
</div>
<script type="text/javascript">
	window.jcl_map_default_zoom_level = <?php echo max(0, min(18, (int) JCalPro::config('default_zoom', 8))); ?>;
	JCalPro.onLoad(function() {
		try {
			jclEventMapInit(<?php echo (float) $this->item->location_data->latitude; ?>, <?php echo (float) $this->item->location_data->longitude; ?>, '<?php echo JCalProHelperFilter::escape_js(JCalProHelperUrl::location($this->item->location_data->id)); ?>');
		}
		catch (err) {
			alert(err);
		}
	});
</script>
<?php endif;


