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

if (JCalPro::version()->isCompatible('3.0.0')) : ?>
<script type="text/javascript">
(function($){
	JCalPro.onLoad(function(){
		try {
			var s = JCalPro.getSize(window.parent.document);
			if (800 > s.x && 600 > s.y) {
				s.x = s.x - 65;
				s.y = s.y - 65;
				var i = window.parent.JCalPro.el('#sbox-content iframe');
				JCalPro.setAttribute(i, 'width', '100%');
				JCalPro.setAttribute(i, 'height', '100%');
				window.parent.SqueezeBox.resize(s);
			}
		}
		catch (err) {
		}
	});
})();
</script>
<?php endif;

