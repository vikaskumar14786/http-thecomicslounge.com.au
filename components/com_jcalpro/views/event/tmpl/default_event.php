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

$this->event = $this->item;

?>
<div class="jcl_subtitlebar" itemscope itemtype="http://schema.org/Event">
	<meta itemprop="url" content="<?php echo JCalProHelperFilter::escape($this->item->fullhref); ?>" />
	<meta itemprop="startDate" content="<?php echo JCalProHelperFilter::escape($this->item->datetime->format(DateTime::ISO8601)); ?>" />
	<div class="jcl_left"><h2 itemprop="name"><?php echo JCalProHelperFilter::escape($this->item->title); ?></h2></div>
	<div class="jcl_right"><?php echo $this->loadTemplate('event_admin'); ?></div>
	<div class="jcl_clear"><!--  --></div>
</div>
<div class="jcl_clear"><!--  --></div>
<?php echo $this->loadTemplate('event_tags'); ?>
<div class="jcal_categories">
	<div class="jcl_event_left jcl_left">
		<ul class="jcl_event_categories">
			<li class="jcl_event_categories_canonical">
				<span><?php
					if ($this->raw) :
						echo JCalProHelperFilter::escape($this->item->categories->canonical->title);
					else :
					?><a href="<?php echo JCalProHelperUrl::category($this->item->categories->canonical->id); ?>" class="eventtitle"><?php echo JCalProHelperFilter::escape($this->item->categories->canonical->title); ?></a><?php
					endif;
					?><strong>*</strong><?php
					if (!empty($this->item->categories->categories)) :
						?>,<?php
					endif;
				?></span>
			</li>
			<?php if (!empty($this->item->categories->categories)) : ?>
			<?php foreach ($this->item->categories->categories as $i => $cat) : ?>
			<li>
				<span><?php
					if ($this->raw) :
						echo JCalProHelperFilter::escape($cat->title);
					else :
						?><a href="<?php echo JCalProHelperUrl::category($cat->id); ?>" class="eventtitle"><?php echo JCalProHelperFilter::escape($cat->title); ?></a><?php
					endif;
					if ($i + 1 != count($this->item->categories->categories)) :
						?>,<?php
					endif;
				?></span>
			</li>
			<?php endforeach; ?>
			<?php endif; ?>
		</ul>
	</div>
	<div class="jcl_event_right jcl_right">
		
		<div class="atomic"><span class="label"><?php echo JText::_('COM_JCALPRO_STARTDATE'); ?>:</span> <?php
			echo $this->item->user_date_display->event;
		?></div>
		
		<?php if (!empty($this->item->duration_string)) : ?>
		<div class="atomic"><span class="label"><?php echo JText::_('COM_JCALPRO_DURATION'); ?>:</span> <?php echo $this->item->duration_string; ?></div>
		<?php endif; ?>
		<?php if (!empty($this->item->location_data)) : ?>
		<div class="atomic"><span class="label"><?php echo JText::_('COM_JCALPRO_LOCATION'); ?>:</span> <a href="<?php echo JCalProHelperFilter::escape(JCalProHelperUrl::location($this->item->location_data->id)); ?>"><?php echo JCalProHelperFilter::escape($this->item->location_data->title); ?></a></div>
		<?php endif; ?>
		<?php if (!empty($this->item->custom_fields->header)) : foreach ($this->item->custom_fields->header as $field) : if (!empty($this->item->params[$field->name])) : ?>
		<div class="atomic atomic-custom jcl_field_<?php echo JCalProHelperFilter::escape($field->name); ?>"><span class="label"><?php echo JCalProHelperFilter::escape($field->title); ?>:</span> <?php echo JHtml::_('jcalpro.formfieldvalue', $field, $this->item->params[$field->name]); ?></div>
		<?php endif; endforeach; endif; ?>
	</div>
	<div class="jcl_clear"><!--  --></div>
	<?php if (!empty($this->item->custom_fields->top)) : ?>
	<div>
		<?php foreach ($this->item->custom_fields->top as $field) : if (!empty($this->item->params[$field->name])) : ?>
		<div class="atomic atomic-custom jcl_field_<?php echo JCalProHelperFilter::escape($field->name); ?>"><span class="label"><?php echo JCalProHelperFilter::escape($field->title); ?>:</span> <?php echo JHtml::_('jcalpro.formfieldvalue', $field, $this->item->params[$field->name]); ?></div>
		<?php endif; endforeach; ?>
	</div>
	<?php endif; ?>
</div>
<?php echo $this->loadTemplate('event_registration'); ?>
<div class="jcl_row">
	<div class="jcl_event_body jcl_nocolor">
		<?php if (!empty($this->item->custom_fields->side) || !empty($this->item->location_data)) : ?>
		<div class="eventdescright">
			<?php if (!empty($this->item->custom_fields->side)) : ?>
			<div class="eventdescright_custom">
				<?php foreach ($this->item->custom_fields->side as $field) : if (!empty($this->item->params[$field->name])) : ?>
				<div class="atomic atomic-custom jcl_field_<?php echo JCalProHelperFilter::escape($field->name); ?>"><span class="label"><?php echo JCalProHelperFilter::escape($field->title); ?>:</span> <?php echo JHtml::_('jcalpro.formfieldvalue', $field, $this->item->params[$field->name]); ?></div>
				<?php endif; endforeach; ?>
			</div>
			<?php endif; ?>
<?php echo $this->loadTemplate('event_map'); ?>
		</div>
		<?php endif; ?>
		<div class="eventdesclarge"><?php echo JCalProHelperFilter::purify($this->item->description); ?></div>
		<div class="jcl_clear"><!--  --></div>
		<?php if (!empty($this->item->custom_fields->bottom)) : foreach ($this->item->custom_fields->bottom as $field) : if (!empty($this->item->params[$field->name])) : ?>
		<div class="atomic atomic-custom jcl_field_<?php echo JCalProHelperFilter::escape($field->name); ?>"><span class="label"><?php echo JCalProHelperFilter::escape($field->title); ?>:</span> <?php echo JHtml::_('jcalpro.formfieldvalue', $field, $this->item->params[$field->name]); ?></div>
		<?php endif; endforeach; endif; ?>
	</div>
</div>
