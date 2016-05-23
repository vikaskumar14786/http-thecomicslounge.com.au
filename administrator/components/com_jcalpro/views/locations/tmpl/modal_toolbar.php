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

$function = JFactory::getApplication()->input->get('function', 'jclSelectLocation', 'cmd');
$new      = JURI::base() . 'index.php?option=com_jcalpro&task=location.add&tmpl=component&function=' . $function;

if (JFactory::getUser()->authorise('core.create', 'com_jcalpro.locations')) :

?>
<div class="right pull-right toolbar-list">
<?php if (JCalPro::version()->isCompatible('3.0.0')) : ?>
	<div class="btn-group">
		<a class="btn tip hasTooltip" href="<?php echo $this->escape($new); ?>" title="<?php echo JText::_('JTOOLBAR_NEW'); ?>"><i class="icon-new"> </i> <?php echo JText::_('JTOOLBAR_NEW'); ?></a>
	</div>
<?php else: ?>
	<ul>
		<li id="toolbar-new" class="button">
			<a class="toolbar" href="<?php echo $this->escape($new); ?>">
				<span class="icon-32-new"> </span>
				<?php echo JText::_('JTOOLBAR_NEW'); ?>
			</a>
		</li>
	</ul>
<?php endif; ?>
</div>
<?php

endif;