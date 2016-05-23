<?php
/**
 * @package		JCalPro
 * @subpackage	mod_jcalpro_calendar

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
<div class="jcalpro_calendar_horizontal<?php echo $moduleclass_sfx; ?> jcalpro_calendar<?php echo $moduleclass_sfx; ?>">
	<div class="jcalpro_calendar_month"><?php echo JCalProHelperFilter::escape($dates->date->monthName()); ?></div>
	<ul class="jcalpro_calendar_list">
<?php
foreach ($list as $day => $stack) :
	$class = 'jcalpro_calendar_day';
	if ('todayclr' === $stack['class']) $class .= ' jcalpro_calendar_today';
?>
		<li class="<?php echo $class; ?>">
			<?php
				$text = JText::_('MOD_JCALPRO_CALENDAR_DAY_' . JCalDate::$days[$stack['user_datetime']->weekday()]) . ' ' . $stack['user_datetime']->format('d');
				if (empty($stack['events'])) :
					echo $text;
				else :
					?><a href="<?php echo JCalProHelperFilter::escape(JCalProHelperUrl::events($stack['user_datetime']->toRequest(), 'day', true, $urlparams)); ?>"><?php echo $text; ?></a><?php
				endif;
			?>
		</li>
<?php
endforeach;
?>
	</ul>
	<?php if (defined('JDEBUG') && JDEBUG && (int) $params->get('debug', 0)) : JCalProHelperTheme::addStyleSheet('module_debug'); ?>
	<div class="jcalpro_module_debug">
		<h3>$list</h3>
		<?php JCalPro::debug($list); ?>
		<h3>$module</h3>
		<?php JCalPro::debug($module); ?>
		<h3>$params</h3>
		<?php JCalPro::debug($params); ?>
	</div>
	<?php endif; ?>
</div>