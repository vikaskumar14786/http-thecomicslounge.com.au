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

JHtml::_('behavior.calendar');

$searchword  = (isset($this->searchword) && !empty($this->searchword) ? $this->searchword : '');
$showToolbar = (bool) (int) JCalPro::config('show_top_navigation_bar', 1);

if (!$this->tpl) :
	$menu     = JHtml::_('jcalpro.menu');
	$filters  = JHtml::_('jcalpro.filters', array('data-stopPropagation' => 'true'));
	$haslinks = property_exists($this, 'linkdata') && is_array($this->linkdata) && array_key_exists('prev', $this->linkdata) && array_key_exists('next', $this->linkdata);
	if ($haslinks) {
		$prevtext = trim(array_key_exists('shorttext', $this->linkdata['prev']) && !empty($this->linkdata['prev']['shorttext']) ? $this->linkdata['prev']['shorttext'] : $this->linkdata['prev']['text']);
		$nexttext = trim(array_key_exists('shorttext', $this->linkdata['next']) && !empty($this->linkdata['next']['shorttext']) ? $this->linkdata['next']['shorttext'] : $this->linkdata['next']['text']);
	}
	
	JCalPro::debugger('View Menu', $menu);
	JCalPro::debugger('View Filters', $filters);
?>
<script type="text/javascript">
JCalPro.onLoad(function(){
	// skip setup, use raw cal
	if ('undefined' === typeof window.jcal_nav_calendar) {
		window.jcal_nav_calendar = new Calendar(null, null, function(cal) {
			var yyyy = cal.date.getFullYear().toString()
	        ,   mm   = (cal.date.getMonth()+1).toString()
	        ,   dd   = cal.date.getDate().toString()
	        ,   date = yyyy + '-' + (mm[1]?mm:"0"+mm[0]) + '-' + (dd[1]?dd:"0"+dd[0])
	        ;
			if ('undefined' === typeof window.jcl_ajax_mode_active) {
				var url = window.location.href;
				// no variables at all
				if (!url.match(/\?/)) {
					window.location.href = url + '?date=' + date;
				}
				// variables
				else {
					if (url.match(/date\=/)) {
						url = url.replace(/date\=[0-9]{4}\-[0-9]{2}\-[0-9]{2}/, 'date=' + date);
						window.location.href = url;
					}
					else {
						window.location.href = url + '&date=' + date;
					}
				}
				return;
			}
			window.location.hash = date;
		}, function(cal) { cal.hide(); });
		
		window.jcal_nav_calendar.create();
	}
	var thisDate = new Date(<?php echo isset($this->dates) ? "'" . $this->dates->date->format('Y-m-d') . "'" : ''; ?>);
	window.jcal_nav_calendar.setDate(thisDate);
	window.jcal_nav_calendar.refresh();
});
</script>
<?php if ($showToolbar) : ?>
<script type="text/javascript">
JCalPro.onLoad(function(){
	if ('undefined' !== typeof jQuery) {
		(function($){
			// prevent clicks on the toolbar elements from closing the dropdown
			$(document.body).on('click', 'ul.dropdown-menu [data-stopPropagation]', function(e) {
				JCalPro.stopEvent(e);
				e.stopPropagation();
			});
		})(jQuery);
	}
});
</script>
<?php endif; ?>
<div class="jcl_toolbar btn-toolbar">
	<?php if ($showToolbar) : ?>
	<div class="jcl_views btn-group">
	<?php
		$icons = array(
			'month' => array('icon' => 'calendar', 'label' => JText::_('COM_JCALPRO_MONTH'))
		,	'flat'  => array('icon' => 'book',     'label' => JText::_('COM_JCALPRO_CONFIG_FLAT_VIEW'))
		,	'week'  => array('icon' => 'list',     'label' => JText::_('COM_JCALPRO_WEEK'))
		,	'day'   => array('icon' => 'bookmark', 'label' => JText::_('COM_JCALPRO_DAY'))
		);
		
		foreach ($icons as $btn => $icon) :
			if (!array_key_exists($btn, $menu)) continue;
			$this->button = $menu[$btn];
			$this->button['class'] = array('btn');
			$this->button['html']  = '<i class="icon-' . $icon['icon'] . '"></i> <span>' . (array_key_exists('label', $icon) ? $icon['label'] : $menu[$btn]['title']) . '</span>';
			if (property_exists($this, 'extmode') && $btn == $this->extmode) :
				$this->button['class'][] = 'active';
			endif;
			echo $this->loadTemplate('toolbar_button');
		endforeach;
	?>
			<a class="btn dropdown-toggle" href="#" data-toggle="dropdown">
				<i class="icon-cog"></i> <span> </span>
			</a>
		<ul class="dropdown-menu">
<?php if (JCalProHelperToolbar::getButton('register')) : ?>
			<li><a href="<?php echo JCalProHelperFilter::escape($menu['register']['href']); ?>" title="<?php echo JCalProHelperFilter::escape($menu['register']['title']); ?>"><i class="icon-star"></i> <?php echo JCalProHelperFilter::escape($menu['register']['title']); ?> </a></li>
<?php endif; ?>
<?php if (JCalProHelperToolbar::getButton('add')) : ?>
			<li><a class="noajax" href="<?php echo JCalProHelperFilter::escape($menu['add']['href']); ?>" title="<?php echo JText::_('JNEW'); ?>"><i class="icon-new"></i> <?php echo JText::_('JNEW'); ?> </a></li>
<?php endif; ?>
<?php if (JCalProHelperToolbar::getButton('categories')) : ?>
			<li><a href="<?php echo JCalProHelperFilter::escape($menu['categories']['href']); ?>" title="<?php echo JText::_('COM_JCALPRO_MAINMENU_CATEGORIES'); ?>"><i class="icon-folder-open"></i> <?php echo JText::_('COM_JCALPRO_MAINMENU_CATEGORIES'); ?> </a></li>
<?php endif; ?>
<?php if (JCalProHelperToolbar::getButton('ical')) : ?>
			<li><a class="noajax" href="<?php echo JCalProHelperFilter::escape($menu['ical']['href']); ?>" title="<?php echo JText::_('COM_JCALPRO_MAINMENU_ICAL'); ?>"><i class="icon-calendar"></i> <?php echo JText::_('COM_JCALPRO_MAINMENU_ICAL'); ?> </a></li>
<?php endif; ?>
<?php if (JCalProHelperToolbar::getButton('print')) : ?>
			<li><a class="noajax" href="<?php echo JCalProHelperFilter::escape($menu['print']['href']); ?>" title="<?php echo JText::_('COM_JCALPRO_MAINMENU_PRINT'); ?>" onclick="window.open(this.href,'win2','status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no'); return false;" rel="nofollow"><i class="icon-print"></i> <?php echo JText::_('COM_JCALPRO_MAINMENU_PRINT'); ?> </a></li>
<?php endif; ?>
			<li data-stopPropagation="true"><?php if (!empty($filters)) echo $filters; ?></li>
			<li data-stopPropagation="true">
				<form class="navbar-search" action="<?php echo JCalProHelperUrl::task('search.search'); ?>" method="post">
					<input name="searchword" type="text" class="search-query" placeholder="<?php echo JText::_('COM_JCALPRO_MAINMENU_SEARCH'); ?>" value="<?php echo JCalProHelperFilter::escape($searchword); ?>" data-stopPropagation="true">
				</form>
			</li>
		</ul>
	</div>
	<?php endif; ?>
	<?php if ($haslinks) : ?>
	<div class="jcl_navbar btn-group">
		<a class="btn ajaxlayoutlink ajaxprev nohide<?php if (empty($prevtext)) {echo ' disabled';} ?>" href="<?php echo JCalProHelperFilter::escape($this->linkdata['prev']['href']); ?>">
			<i class="icon-arrow-left"></i><?php echo JCalProHelperFilter::escape($prevtext); ?>
		</a>
		<a class="btn nohide" type="button" href="javascript:;" onclick="window.jcal_nav_calendar.showAtElement(this,'BC');return false;">
			<i class="icon-arrow-down"></i>
		</a>
		<a class="btn ajaxlayoutlink ajaxnext nohide<?php if (empty($nexttext)) {echo ' disabled';} ?>" href="<?php echo JCalProHelperFilter::escape($this->linkdata['next']['href']); ?>">
			<i class="icon-arrow-right"></i><?php echo JCalProHelperFilter::escape($nexttext); ?>
		</a>
	</div>
	<?php endif; ?>
</div>

<?php
endif;
