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

$folder = JFactory::getApplication()->input->get('folder', '', 'path');
// handle return url
$returnVars = array('tmpl' => 'component');
if ($this->state->get('field.id')) $returnVars['fieldid'] = $this->state->get('field.id');
?>

<script type='text/javascript'>
var image_base_path = '<?php echo JCalProHelperFilter::escape_js(JCalProHelperUrl::uploads() . '/');?>';
</script>
<form action="<?php echo $this->escape(JCalProHelperUrl::view('media', false)); ?>" id="imageForm" method="post" enctype="multipart/form-data">
	<fieldset>
		<div class="fltlft">
			<label for="folder"><?php echo JText::_('COM_MEDIA_DIRECTORY') ?></label>
			<?php echo $this->folderList; ?>
			<button type="button" id="upbutton" title="<?php echo JText::_('COM_MEDIA_DIRECTORY_UP') ?>"><?php echo JText::_('COM_MEDIA_UP') ?></button>
		</div>
		<div class="fltrt">
			<button type="button" onclick="<?php if ($this->state->get('field.id')):?>window.parent.jInsertFieldValue(document.id('f_url').value,'<?php echo $this->state->get('field.id');?>');<?php else:?>ImageManager.onok();<?php endif;?>window.parent.SqueezeBox.close();"><?php echo JText::_('COM_MEDIA_INSERT') ?></button>
			<button type="button" onclick="window.parent.SqueezeBox.close();"><?php echo JText::_('JCANCEL') ?></button>
		</div>
	</fieldset>
	
	<iframe id="imageframe" name="imageframe" src="<?php echo $this->escape(JCalProHelperUrl::view('media', false, array('tmpl' => 'component', 'layout' => 'list', 'folder' => $folder))); ?>"></iframe>

	<fieldset>
		<table class="properties">
			<tr>
				<td><label for="f_url"><?php echo JText::_('COM_MEDIA_IMAGE_URL') ?></label></td>
				<td><input type="text" id="f_url" value="" /></td>
			</tr>
		</table>

		<?php if (!$this->state->get('field.id')):?>
		<div style="display:none;">
			<input type="hidden" id="f_alt" value="" />
			<input type="hidden" id="f_title" value="" />
			<select size="1" id="f_caption" >
				<option value="" selected="selected" ><?php echo JText::_('JNO') ?></option>
				<option value="1"><?php echo JText::_('JYES') ?></option>
			</select>
			<select size="1" id="f_align" >
				<option value="" selected="selected"><?php echo JText::_('COM_MEDIA_NOT_SET') ?></option>
				<option value="left"><?php echo JText::_('JGLOBAL_LEFT') ?></option>
				<option value="right"><?php echo JText::_('JGLOBAL_RIGHT') ?></option>
			</select>
		</div>
		<?php endif;?>
		<input type="hidden" id="dirPath" name="dirPath" />
		<input type="hidden" id="f_file" name="f_file" />
		<input type="hidden" id="tmpl" name="component" />
	</fieldset>
</form>

<?php if (JFactory::getUser()->authorise('core.create', 'com_media')): ?>
<form action="<?php echo $this->escape(JURI::base() . JCalProHelperUrl::task('media.folder', false, array('tmpl' => 'component', JSession::getFormToken() => 1))); ?>" id="folderForm" name="folderForm" method="post" enctype="multipart/form-data">
	<fieldset id="folderview">
		<legend><?php echo JText::_('COM_MEDIA_CREATE_FOLDER'); ?></legend>
		<div class="path">
			<input class="inputbox" type="text" id="foldername" name="foldername"  />
			<input class="update-folder" type="hidden" name="folderbase" id="folderbase" value="<?php echo $this->state->folder; ?>" />
			<?php if ($this->state->get('field.id')) : ?><input type="hidden" name="fieldid" value="<?php echo $this->state->get('field.id'); ?>" /><?php endif; ?>
			<button type="submit"><?php echo JText::_('COM_MEDIA_CREATE_FOLDER'); ?></button>
		</div>
		<?php echo JHtml::_('form.token'); ?>
	</fieldset>
</form>
<form action="<?php echo $this->escape(JURI::base() . JCalProHelperUrl::task('media.upload', false, array('tmpl' => 'component', 'folder' => $folder, JSession::getFormToken() => 1))); ?>" id="uploadForm" name="uploadForm" method="post" enctype="multipart/form-data">
	<fieldset id="uploadform">
		<legend><?php echo $this->config->get('upload_maxsize')=='0' ? JText::_('COM_MEDIA_UPLOAD_FILES_NOLIMIT') : JText::sprintf('COM_MEDIA_UPLOAD_FILES', $this->config->get('upload_maxsize')); ?></legend>
		<fieldset id="upload-noflash" class="actions">
			<label for="upload-file" class="hidelabeltxt"><?php echo JText::_('COM_MEDIA_UPLOAD_FILE'); ?></label>
			<input type="file" id="upload-file" name="Filedata" />
			<label for="upload-submit" class="hidelabeltxt"><?php echo JText::_('COM_MEDIA_START_UPLOAD'); ?></label>
			<input type="submit" id="upload-submit" value="<?php echo JText::_('COM_MEDIA_START_UPLOAD'); ?>"/>
		</fieldset>
		<div id="upload-flash" class="hide">
			<ul>
				<li><a href="#" id="upload-browse"><?php echo JText::_('COM_MEDIA_BROWSE_FILES'); ?></a></li>
				<li><a href="#" id="upload-clear"><?php echo JText::_('COM_MEDIA_CLEAR_LIST'); ?></a></li>
				<li><a href="#" id="upload-start"><?php echo JText::_('COM_MEDIA_START_UPLOAD'); ?></a></li>
			</ul>
			<div class="clr"> </div>
			<p class="overall-title"></p>
			<?php echo JHtml::_('image', 'media/bar.gif', JText::_('COM_MEDIA_OVERALL_PROGRESS'), array('class' => 'progress overall-progress'), true); ?>
			<div class="clr"> </div>
			<p class="current-title"></p>
			<?php echo JHtml::_('image', 'media/bar.gif', JText::_('COM_MEDIA_CURRENT_PROGRESS'), array('class' => 'progress current-progress'), true); ?>
			<p class="current-text"></p>
		</div>
		<ul class="upload-queue" id="upload-queue">
			<li style="display: none"></li>
		</ul>
		<input type="hidden" name="return-url" value="<?php echo base64_encode(JCalProHelperUrl::view('media', false, $returnVars)); ?>" />
	</fieldset>
</form>
<?php  endif; ?>