<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Imagegallery
 * @author     vikaskumar <vikaskumar14786@gmail.com>
 * @copyright  Copyright (C) 2016. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;


?>
<?php if ($this->item) : ?>

	<div class="item_fields">
		<table class="table">
			<tr>
			<th><?php echo JText::_('COM_IMAGEGALLERY_FORM_LBL_GALLERYIMAGES_STATE'); ?></th>
			<td>
			<i class="icon-<?php echo ($this->item->state == 1) ? 'publish' : 'unpublish'; ?>"></i></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMAGEGALLERY_FORM_LBL_GALLERYIMAGES_CREATED_BY'); ?></th>
			<td><?php echo $this->item->created_by_name; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMAGEGALLERY_FORM_LBL_GALLERYIMAGES_MODIFIED_BY'); ?></th>
			<td><?php echo $this->item->modified_by_name; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMAGEGALLERY_FORM_LBL_GALLERYIMAGES_IMAGE_NAME'); ?></th>
			<td><?php echo $this->item->image_name; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_IMAGEGALLERY_FORM_LBL_GALLERYIMAGES_GALLERY_ID'); ?></th>
			<td><?php echo $this->item->gallery_id; ?></td>
</tr>

		</table>
	</div>
	
	<?php
else:
	echo JText::_('COM_IMAGEGALLERY_ITEM_NOT_LOADED');
endif;
