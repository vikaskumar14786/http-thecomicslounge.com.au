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

// load the default search form if we're in search view
if ('search' == $this->getName()) :
	$searchword = (isset($this->searchword) && !empty($this->searchword) ? $this->searchword : JText::_('COM_JCALPRO_SEARCH_DEFAULT'));

?>
<div class="jcl_search_form jcl_center">
	<div class="jcl_search_form_search">
		<form action="<?php echo JCalProHelperUrl::task('search.search'); ?>" method="post">
			<div class="input-append">
				<input type="text" name="searchword" class="inputbox" value="<?php echo JCalProHelperFilter::escape($searchword); ?>" onfocus="if(this.value == '<?php echo JCalProHelperFilter::escape_js(JText::_('COM_JCALPRO_SEARCH_DEFAULT')); ?>') this.value='';" onblur="if(!this.value) this.value = '<?php echo JCalProHelperFilter::escape_js(JText::_('COM_JCALPRO_SEARCH_DEFAULT')); ?>';" size="25" />
				<button type="submit" class="btn btn-primary"><i class="icon-search"> </i> <span><?php echo JCalProHelperFilter::escape(JText::_('COM_JCALPRO_SEARCH_BUTTON')); ?></span></button>
			</div>
		</form>
	</div>
</div>

<?php
endif;
