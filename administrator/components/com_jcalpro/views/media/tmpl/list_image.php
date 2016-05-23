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
<div class="item">
	<a href="javascript:ImageManager.populateFields('<?php echo $this->_tmp_img->path_relative; ?>')" title="<?php echo $this->_tmp_img->name; ?>" >
		<?php
			if (property_exists($this->_tmp_img, 'width_60')) :
				$image_path = JCalProHelperUrl::uploads() . '/' . $this->_tmp_img->path_relative;
				$image_text = JText::sprintf('COM_MEDIA_IMAGE_TITLE', $this->_tmp_img->title, MediaHelper::parseSize($this->_tmp_img->size));
				$image_opts = array('width' => $this->_tmp_img->width_60, 'height' => $this->_tmp_img->height_60);
			else :
				$image_path = $this->_tmp_img->icon_32;
				$image_text = $this->_tmp_img->name;
				$image_opts = null;
			endif;
			echo JHtml::_('image', $image_path, $image_text, $image_opts, true); ?>
		<span title="<?php echo $this->_tmp_img->name; ?>"><?php echo $this->_tmp_img->title; ?></span></a>
</div>