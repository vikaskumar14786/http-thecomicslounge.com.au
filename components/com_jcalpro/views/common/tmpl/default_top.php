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

if ($this->show_page_heading) :
	$title = isset($this->title) ? $this->title : $this->params->get('page_heading');
?>
<h2 class="componentheading<?php echo JCalProHelperFilter::escape($this->params->get('pageclass_sfx')); ?>"><?php echo JCalProHelperFilter::escape($title); ?></h2>
<?php endif; ?>

<div id="jcl_component" class="<?php echo $this->viewClass; ?>">
<?php
if (!$this->raw && !$this->tpl) :
	// display the header if not in raw mode
	if ((int) JCalPro::config('show_top_navigation_bar', 1)) :
		echo $this->loadTemplate('toolbar');
	endif;
	
	// display the moderator button as well, if allowed (and there's events to be moderated)
	if (property_exists($this, 'pending') && $this->pending && 'admin' != $this->extmode) : ?>
	<div class="jcl_toolbar jcl_admin_toolbar">
		<div class="atomic">
			<a class="button" href="<?php
				echo JCalProHelperUrl::events('', 'admin', true, array('filter_approved' => 0));
				?>"><?php
					echo JText::_('COM_JCALPRO_ADMIN_SECTION_TITLE_UNAPPROVED') . ' (' . $this->pending . ')';
			?></a>
		</div>
	</div>
	<?php
	endif;
endif;
?>
	<div class="jcl_<?php echo JCalProHelperFilter::escape($this->extmode); ?>">
		<div class="jcl_center">
<?php if ($this->print) : ?>
			<div class="jcl_print_title">
				<h1><?php echo JCalProHelperFilter::escape($this->linkdata['current']); ?></h1>
				<a onclick="document.getElementById('jcl_print_image').style.display='none';window.print();window.close();return false;" href="#" class="jcl_print_button"><?php
					echo JHtml::_('jcalpro.image', 'icon-print.gif', $this->template, array(
						'border' => 0
					,	'alt' => JText::_('COM_JCALPRO_MAINMENU_PRINT')
					,	'id' => 'jcl_print_image'
					));
				?></a>
			</div>
<?php endif; ?>
			<div class="jcl_mainview">
