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
<div id="panes">
<?php
	// start pane
	echo JHtml::_('sliders.start');

	// news feed
	if ($this->feed) {
		echo JHtml::_('sliders.panel', JText::_('COM_JCALPRO_NEWS_FEED'), 'news-feed');
		echo $this->loadTemplate('feed');
	}

	// latest events
	if (!empty($this->latest_events)) {
		echo JHtml::_('sliders.panel', JText::_('COM_JCALPRO_LATEST_EVENTS'), 'latest-events');
		$this->events = $this->latest_events;
		echo $this->loadTemplate('common_event_list');
	}

	// upcoming events
	if (!empty($this->upcoming_events)) {
		echo JHtml::_('sliders.panel', JText::_('COM_JCALPRO_UPCOMING_EVENTS'), 'upcoming-events');
		$this->events = $this->upcoming_events;
		echo $this->loadTemplate('common_event_list');
	}

	// all done
	echo JHtml::_('sliders.end');
?>
</div>