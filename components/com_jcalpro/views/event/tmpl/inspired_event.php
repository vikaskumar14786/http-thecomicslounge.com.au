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

$this->event = $this->item;

?>

<div class="jcal_event" itemscope itemtype="http://schema.org/Event">
	<header class="jcl_header page-header clearfix">
		<meta itemprop="url" content="<?php echo JCalProHelperFilter::escape($this->item->fullhref); ?>" />
		<h1 itemprop="name"><?php echo JCalProHelperFilter::escape($this->item->title); ?></h1>
	</header>
	<?php
	if (JCalPro::version()->isCompatible('3.1.0')) :
		$tags = $this->item->tags->getItemTags(JCalPro::COM . '.event' , $this->item->id);
		if (!empty($tags)) :
			$this->item->tagLayout = new JLayoutFile('joomla.content.tags');
			echo $this->item->tagLayout->render($tags);
		endif;
	endif;
	?>
	<?php echo $this->loadTemplate('toolbar'); ?>
	<?php if (!$this->print) : ?>
	<div class="jcl_event_admin"><?php echo $this->loadTemplate('event_admin'); ?></div>
	<?php endif; ?>

	<div class="jcl_event_detail">
		<meta itemprop="startDate" content="<?php echo JCalProHelperFilter::escape($this->item->datetime->format(DateTime::ISO8601)); ?>" />
		<span class="labels"><?php echo JText::_('COM_JCALPRO_STARTDATE'); ?>:</span> <?php
			echo $this->item->user_date_display->event;
	?></div>

	<?php if (!empty($this->item->duration_string)) : ?>
	<div class="jcl_event_detail"><span class="labels"><?php echo JText::_('COM_JCALPRO_DURATION'); ?>:</span> <?php echo $this->item->duration_string; ?></div>
	<?php endif; ?>
	
	<div class="jcl_event_detail"><span class="labels"><?php echo JText::_('COM_JCALPRO_CATEGORIES'); ?>:</span>
	<?php
		if ($this->print) :
			echo JCalProHelperFilter::escape($this->item->categories->canonical->title);
			?><strong>*</strong><?php
		else :
			$color = $this->item->categories->canonical->params->get('jcalpro_color');
			$class = '';
			if (JCalProHelperTheme::isTooWhite($color)) {
				$class = ' dark';
			}
		?><a href="<?php echo JCalProHelperUrl::category($this->item->categories->canonical->id); ?>" class="label jcl_event_category<?php echo $class; ?>" style="background-color: <?php echo JCalProHelperFilter::escape($color); ?>"><?php echo JCalProHelperFilter::escape($this->item->categories->canonical->title); ?><strong>*</strong></a><?php
		endif;
		if (!empty($this->item->categories->categories)) :
			?>&nbsp;<?php
		endif;
		
		if (!empty($this->item->categories->categories)) :
			foreach ($this->item->categories->categories as $i => $cat) :
				if ($this->print) :
					echo JCalProHelperFilter::escape($cat->title);
				else :
					$color = $cat->params->get('jcalpro_color');
					$class = '';
					if (JCalProHelperTheme::isTooWhite($color)) {
						$class = ' dark';
					}
					?><a href="<?php echo JCalProHelperUrl::category($cat->id); ?>" class="label jcl_event_category<?php echo $class; ?>" style="background-color: <?php echo JCalProHelperFilter::escape($color); ?>;"><?php echo JCalProHelperFilter::escape($cat->title); ?></a><?php
				endif;
				if ($i + 1 != count($this->item->categories->categories)) :
					?>&nbsp;<?php
				endif;
			endforeach;
		endif;
		
	?>
	</div>
	
	<?php if (!empty($this->item->location_data)) : ?>
	<div class="jcl_event_detail"><span class="labels"><?php echo JText::_('COM_JCALPRO_LOCATION'); ?>:</span> <a href="<?php echo JCalProHelperFilter::escape(JCalProHelperUrl::location($this->item->location_data->id)); ?>"><?php echo JCalProHelperFilter::escape($this->item->location_data->title); ?></a></div>
	<?php endif; ?>
	
	
	<?php if (!empty($this->item->custom_fields->header)) : foreach ($this->item->custom_fields->header as $field) : if (!empty($this->item->params[$field->name])) : ?>
	<div class="jcl_event_detail jcl_field_<?php echo JCalProHelperFilter::escape($field->name); ?>"><span class="labels"><?php echo JCalProHelperFilter::escape($field->title); ?>:</span> <?php echo JHtml::_('jcalpro.formfieldvalue', $field, $this->item->params[$field->name]); ?></div>
	<?php endif; endforeach; endif; ?>
	
	<?php if (!empty($this->item->custom_fields->top)) : ?>
	<div>
		<?php foreach ($this->item->custom_fields->top as $field) : if (!empty($this->item->params[$field->name])) : ?>
		<div class="jcl_event_detail jcl_field_<?php echo JCalProHelperFilter::escape($field->name); ?>"><span class="labels"><?php echo JCalProHelperFilter::escape($field->title); ?>:</span> <?php echo JHtml::_('jcalpro.formfieldvalue', $field, $this->item->params[$field->name]); ?></div>
		<?php endif; endforeach; ?>
	</div>
	<?php endif; ?>

	<?php if ($this->item->registration) : ?>
	<h3 class="jcl_header"><?php echo JText::_('COM_JCALPRO_EVENT_REGISTRATION'); ?></h3>
	<div class="jcl_event_detail"><span class="labels"><?php echo JText::_('COM_JCALPRO_REGISTRATION_START_DATE'); ?>:</span> <?php
		echo $this->item->registration_data->start_date->format(JText::_('COM_JCALPRO_DATE_FORMAT_MINI_DATE'));
	?></div>
	<div class="jcl_event_detail"><span class="labels"><?php echo JText::_('COM_JCALPRO_REGISTRATION_END_DATE'); ?>:</span> <?php
		echo $this->item->registration_data->end_date->format(JText::_('COM_JCALPRO_DATE_FORMAT_MINI_DATE'));
	?></div>
	<?php if ($this->item->registration_capacity) : ?>
	<div class="jcl_event_detail"><span class="labels"><?php echo JText::_('COM_JCALPRO_REGISTRATION_CAPACITY'); ?>:</span> <?php
		echo JText::sprintf('COM_JCALPRO_REGISTRATION_CAPACITY_DISPLAY', $this->item->registration_capacity);
	?></div>
	<?php endif; ?>
	<?php if ($this->item->registration_data->can_register) : ?>
	<div>
		<a class="btn jcalpro_register_button" href="<?php echo JCalProHelperUrl::task('registration.add', true, array('event_id' => $this->item->id)); ?>"><i class="icon-star"></i> <?php echo JText::_('COM_JCALPRO_MAINMENU_REGISTER'); ?></a>
	</div>
	<?php endif; ?>

	<?php endif; ?>
	
	<?php if (!empty($this->item->location_data)) : JFactory::getDocument()->addScript('//maps.googleapis.com/maps/api/js?sensor=false'); ?>
	<div class="jcl_event_map well" itemprop="location" itemscope itemtype="http://schema.org/Place">
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
	</div>
	<?php endif; ?>
	
	<div class="jcl_event_body well">
		<?php if (!empty($this->item->custom_fields->side)) : ?>
		<div class="eventdescright_custom">
			<?php foreach ($this->item->custom_fields->side as $field) : if (!empty($this->item->params[$field->name])) : ?>
			<div class="jcl_event_detail jcl_field_<?php echo JCalProHelperFilter::escape($field->name); ?>"><span class="labels"><?php echo JCalProHelperFilter::escape($field->title); ?>:</span> <?php echo JHtml::_('jcalpro.formfieldvalue', $field, $this->item->params[$field->name]); ?></div>
			<?php endif; endforeach; ?>
		</div>
		<?php endif; ?>
		<div class="eventdesclarge"><?php echo $this->item->description; ?></div>
		<?php if (!empty($this->item->custom_fields->bottom)) : foreach ($this->item->custom_fields->bottom as $field) : if (!empty($this->item->params[$field->name])) : ?>
		<div class="jcl_event_detail jcl_field_<?php echo JCalProHelperFilter::escape($field->name); ?>"><span class="labels"><?php echo JCalProHelperFilter::escape($field->title); ?>:</span> <?php echo JHtml::_('jcalpro.formfieldvalue', $field, $this->item->params[$field->name]); ?></div>
		<?php endif; endforeach; endif; ?>
	</div>
</div>