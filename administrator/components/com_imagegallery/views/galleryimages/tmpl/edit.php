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

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.keepalive');

// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet(JUri::root() . 'media/com_imagegallery/css/form.css');
?>
<script type="text/javascript">
	js = jQuery.noConflict();
	js(document).ready(function () {
		
	js('input:hidden.gallery_id').each(function(){
		var name = js(this).attr('name');
		if(name.indexOf('gallery_idhidden')){
			js('#jform_gallery_id option[value="'+js(this).val()+'"]').attr('selected',true);
		}
	});
	js("#jform_gallery_id").trigger("liszt:updated");
	});

	Joomla.submitbutton = function (task) {
		if (task == 'galleryimages.cancel') {
			Joomla.submitform(task, document.getElementById('galleryimages-form'));
		}
		else {
			
			if (task != 'galleryimages.cancel' && document.formvalidator.isValid(document.id('galleryimages-form'))) {
				
				Joomla.submitform(task, document.getElementById('galleryimages-form'));
			}
			else {
				alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
			}
		}
	}
</script>

<form
	action="<?php echo JRoute::_('index.php?option=com_imagegallery&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="galleryimages-form" class="form-validate">

	<div class="form-horizontal">
		<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_IMAGEGALLERY_TITLE_GALLERYIMAGES', true)); ?>
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">

									<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
				<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
				<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
				<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
				<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />

				<?php if(empty($this->item->created_by)){ ?>
					<input type="hidden" name="jform[created_by]" value="<?php echo JFactory::getUser()->id; ?>" />

				<?php } 
				else{ ?>
					<input type="hidden" name="jform[created_by]" value="<?php echo $this->item->created_by; ?>" />

				<?php } ?>
				<?php if(empty($this->item->modified_by)){ ?>
					<input type="hidden" name="jform[modified_by]" value="<?php echo JFactory::getUser()->id; ?>" />

				<?php } 
				else{ ?>
					<input type="hidden" name="jform[modified_by]" value="<?php echo $this->item->modified_by; ?>" />

				<?php } ?>			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('image_name'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('image_name'); ?></div>
			</div>
			<div class="control-group">
				<div class="control-label"><?php echo $this->form->getLabel('gallery_id'); ?></div>
				<div class="controls"><?php echo $this->form->getInput('gallery_id'); ?></div>
			</div>

			<?php
				foreach((array)$this->item->gallery_id as $value): 
					if(!is_array($value)):
						echo '<input type="hidden" class="gallery_id" name="jform[gallery_idhidden]['.$value.']" value="'.$value.'" />';
					endif;
				endforeach;
			?>

					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('version_note'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('version_note'); ?></div>
					</div>
				</fieldset>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>

		

		<?php echo JHtml::_('bootstrap.endTabSet'); ?>

		<input type="hidden" name="task" value=""/>
		<?php echo JHtml::_('form.token'); ?>

	</div>
</form>
