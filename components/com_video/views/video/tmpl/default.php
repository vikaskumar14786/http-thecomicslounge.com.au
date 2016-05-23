<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Video
 * @author     vikas Kumar <vikaskumar14786@gmail.com>
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
			<th><?php echo JText::_('COM_VIDEO_FORM_LBL_VIDEO_STATE'); ?></th>
			<td>
			<i class="icon-<?php echo ($this->item->state == 1) ? 'publish' : 'unpublish'; ?>"></i></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_VIDEO_FORM_LBL_VIDEO_CREATED_BY'); ?></th>
			<td><?php echo $this->item->created_by_name; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_VIDEO_FORM_LBL_VIDEO_MODIFIED_BY'); ?></th>
			<td><?php echo $this->item->modified_by_name; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_VIDEO_FORM_LBL_VIDEO_VIDEO_NAME'); ?></th>
			<td><?php echo $this->item->video_name; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_VIDEO_FORM_LBL_VIDEO_VIDEO_URL'); ?></th>
			<td><?php echo $this->item->video_url; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_VIDEO_FORM_LBL_VIDEO_HOME'); ?></th>
			<td><?php echo $this->item->home; ?></td>
</tr>
<tr>
			<th><?php echo JText::_('COM_VIDEO_FORM_LBL_VIDEO_PUBLISHED'); ?></th>
			<td><?php echo $this->item->published; ?></td>
</tr>

		</table>
	</div>
	
	<?php
else:
	echo JText::_('COM_VIDEO_ITEM_NOT_LOADED');
endif;
