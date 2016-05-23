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

JHtml::_('behavior.tooltip');

$event   = $this->event;
$user    = JFactory::getUser();
$script  = 'document.getElementById(\'%s\').value=\'%s\';document.getElementById(\'%s\').submit();';
$context = 'com_jcalpro.category.' . $event->categories->canonical->id;
$task    = 'event-task-' . $event->id;
$form    = 'event-form-' . $event->id;
$isMine  = ($user->id == $event->created_by && $event->private);

?>
<form id="<?php echo $form; ?>" method="post" action="<?php echo JCalProHelperUrl::_(); ?>"><?php

if ($user->authorise('core.edit', $context) || $user->authorise('core.edit.own', $context) || $isMine) {
	echo JHtml::_('jcalpro.image', 'icon-edit.png', $this->template, array(
		'title' => JText::_('COM_JCALPRO_EVENT_EDIT_TOOLTIP')
	,	'onclick' => 'window.location.href=\'' . JCalProHelperUrl::task('event.edit', true, array('id'=>$event->id)) . '\';'
	,	'style' => 'cursor:pointer;'
	,	'class' => 'hasTip hasTooltip'
	));
}

if (0 == $event->approved && $user->authorise('core.moderate', $context)) {
	echo JHtml::_('jcalpro.image', 'icon-approve.png', $this->template, array(
		'title' => JText::_('COM_JCALPRO_EVENT_APPROVE_TOOLTIP')
	,	'onclick' => sprintf($script, $task, 'events.approve', $form)
	,	'style' => 'cursor:pointer;'
	,	'class' => 'hasTip hasTooltip'
	));
}

if (0 == $event->published && $user->authorise('core.edit.state', $context)) {
	echo JHtml::_('jcalpro.image', 'icon-publish.png', $this->template, array(
		'title' => JText::_('COM_JCALPRO_EVENT_PUBLISH_TOOLTIP')
	,	'onclick' => sprintf($script, $task, 'events.publish', $form)
	,	'style' => 'cursor:pointer;'
	,	'class' => 'hasTip hasTooltip'
	));
}

if ($user->authorise('core.delete', $context) || $isMine) {
	echo JHtml::_('jcalpro.image', 'icon-delete.png', $this->template, array(
		'title' => JText::_('COM_JCALPRO_EVENT_DELETE_TOOLTIP')
	,	'onclick' => 'if(confirm(\'' . JText::_('COM_JCALPRO_EVENT_DELETE_CONFIRM') . '\')){'.sprintf($script, $task, 'events.trash', $form).'}'
	,	'style' => 'cursor:pointer;'
	,	'class' => 'hasTip hasTooltip'
	));
}
?>
<div style="display:none;">
	<input name="cid[]" value="<?php echo $event->id; ?>" />
	<input name="id" value="<?php echo $event->id; ?>" />
	<input name="task" id="event-task-<?php echo $event->id; ?>" value="" />
	<?php echo JHtml::_('form.token'); ?>
</div>
</form>