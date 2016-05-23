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
<script type="text/javascript">
jcl_lat = <?php echo (float) $this->item->latitude; ?>;
jcl_lng = <?php echo (float) $this->item->longitude; ?>;
</script>

<div itemprop="location" itemscope itemtype="http://schema.org/Place">
	<meta itemprop="url" content="<?php echo JCalProHelperFilter::escape(JCalProHelperUrl::toFull(JCalProHelperUrl::location($this->item->id))); ?>" />
	<header class="jcl_header page-header clearfix">
		<h1 itemprop="name"><?php echo JCalProHelperFilter::escape($this->item->title); ?></h1>
	</header>
	<div class="row-fluid" itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
		<div itemprop="streetAddress"><?php echo nl2br(JCalProHelperFilter::escape($this->item->address)); ?></div>
<?php if (!empty($this->item->city)) : ?>
		<div itemprop="addressLocality"><?php echo JCalProHelperFilter::escape($this->item->city); ?></div>
<?php endif; ?>
<?php if (!empty($this->item->state)) : ?>
		<div itemprop="addressRegion"><?php echo JCalProHelperFilter::escape($this->item->state); ?></div>
<?php endif; ?>
<?php if (!empty($this->item->postal_code)) : ?>
		<div itemprop="postalCode"><?php echo JCalProHelperFilter::escape($this->item->postal_code); ?></div>
<?php endif; ?>
	</div>
	<div id="map_canvas_container" class="row-fluid">
		<div id="map_canvas"><?php echo JText::_('COM_JCALPRO_LOCATION_LOADING_MAP'); ?></div>
	</div>
	<?php echo $this->loadTemplate('location_directions_form'); ?>
	<div itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates">
		<meta itemprop="latitude" content="<?php echo JCalProHelperFilter::escape($this->item->latitude); ?>" />
		<meta itemprop="longitude" content="<?php echo JCalProHelperFilter::escape($this->item->longitude); ?>" />
	</div>

<?php
foreach (array('upcoming_events', 'past_events') as $type) {
	if (property_exists($this->item, $type)) {
		$this->events = $this->item->{$type};
		$this->events_type = $type;
		echo $this->loadTemplate('location_events');
	}
}
?>

</div>
