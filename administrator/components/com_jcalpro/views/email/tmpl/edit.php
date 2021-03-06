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

JHtml::_('behavior.modal');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');

JText::script('COM_JCALPRO_EMAIL_DEMO_FAILED');
JText::script('JGLOBAL_VALIDATION_FORM_FAILED');

$hidden = array();

$demourl = JCalProHelperUrl::view('email', false, array('format' => 'raw'));
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task == 'email.demo') {
			var opts = {
				url: '<?php echo JCalProHelperFilter::escape_js($demourl); ?>'
			,	data: {
					body: JCalPro.getValue(JCalPro.id('jform_body'))
				,	subject: JCalPro.getValue(JCalPro.id('jform_subject'))
				}
			,	requestType: 'raw'
			,	onSuccess: function(data) {
					try {
						SqueezeBox.setContent('adopt', data);
					}
					catch (err) {
						alert(Joomla.JText._('COM_JCALPRO_EMAIL_DEMO_FAILED'));
					}
				}
			,	onFail: function() {
					alert(Joomla.JText._('COM_JCALPRO_EMAIL_DEMO_FAILED'));
				}
			};
			JCalPro.request(opts);
		}
		else if (task == 'email.cancel' || document.formvalidator.isValid(document.id('email-form'))) {
			Joomla.submitform(task, document.getElementById('email-form'));
		}
		else {
			alert(Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED'));
		}
	}
</script>
<div id="jcl_component" class="<?php echo $this->viewClass; ?>">
	<form action="<?php echo JRoute::_('index.php?option=com_jcalpro&task=email.save&id=' . (int) $this->item->id); ?>" method="post" id="email-form" name="adminForm" class="form-validate">
		<?php echo $this->loadTemplate(JCalPro::version()->isCompatible('3.0') ? 'form' : 'legacy'); ?>
		<div>
			<?php if (!empty($hidden)) foreach ($hidden as $field) echo $field->input; ?>
			<input type="hidden" name="task" value="" />
			<?php echo JHtml::_('form.token'); ?>
		</div>
	</form>
</div>
<?php echo $this->loadTemplate('debug'); ?>