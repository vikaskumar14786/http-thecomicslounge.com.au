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
<div class="row-fluid">
	<div class="span12 form-vertical">
		<?php echo JHtml::_('bootstrap.startTabSet', 'JCalProEmailTab', array('active' => 'email')); ?>
		<?php echo JHtml::_('bootstrap.addTab', 'JCalProEmailTab', 'email', JText::_('COM_JCALPRO_EMAIL', true)); ?>
		<div class="row-fluid">
			<div class="span8">
				<div class="row-fluid">
					<?php
					foreach ($this->form->getFieldset('email') as $field) :
						$label = $field->label;
						if (empty($label)) :
							echo $field->input;
							continue;
						endif;
						?>
						<div class="control-group">
							<div class="control-label">
								<?php echo $label; ?>
							</div>
							<div class="controls">
								<?php echo $field->input; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="span4">
				<div class="row-fluid jcl_email_tags">
					<div class="alert alert-info">
						<h3><?php echo JText::_('COM_JCALPRO_EMAIL_EDITOR_TAGLIST'); ?></h3>
						<div><?php echo JText::_('COM_JCALPRO_EMAIL_EDITOR_TAGLIST_DESCRIPTION'); ?></div>
						<?php foreach (JCalProHelperMail::getEmailTags(null, false) as $tag => $description) : ?>
						<div class="row-fluid">
							<div class="span6">
								<strong onclick="try{jInsertEditorText('<?php echo "{%$tag%}"; ?>', 'jform_body');}catch(err){}"><?php echo "{%{$tag}%}"; ?>: </strong>
							</div>
							<div class="span4"><?php echo JText::_($description); ?></div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	</div>
</div>