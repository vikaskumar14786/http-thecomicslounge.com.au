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

jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JLoader::register('JCalProHelperPath', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/path.php');
JLoader::register('JCalProHelperFilter', JCalProHelperPath::helper().'/filter.php');

class JFormFieldJCalProMedia extends JFormField
{
	public $type = 'Jcalpromedia';

	protected function getInput() {
		// Load the modal behavior script.
		JHtml::_('behavior.modal', 'a.modal');
		
		// sanitize the value
		$value = JCalProHelperFilter::escape($this->value);
		
		// link to modal target
		$link = JCalProHelperFilter::escape(JCalProHelperUrl::view('media', false, array('tmpl' => 'component', 'layout' => 'default', 'fieldid' => $this->id, 'folder' => '')));
		
		// Build the script.
		$script   = array();
		$script[] = 'function jInsertFieldValue(value, id) {';
		$script[] = '	var old_id = document.id(id).value;';
		$script[] = '	if (old_id != id) {';
		$script[] = '		var elem = document.id(id)';
		$script[] = '		elem.value = value;';
		$script[] = '		elem.fireEvent("change");';
		$script[] = '	}';
		$script[] = '}';
		
		
		// Add the script to the document head.
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));
		
		// start the html
		$html = array();
		
		// The current display field.
		$html[] = '<div class="fltlft">';
		$html[] = '	<input class="inputbox" type="text" id="'.$this->id.'" name="'.$this->name.'" value="'.$value.'" size="35" />';
		$html[] = '</div>';
		
		// The select button.
		$html[] = '<div class="button2-left">';
		$html[] = '	<div class="blank">';
		$html[] = '		<a id="'.$this->id.'_modal" class="modal button" title="'.JText::_('JLIB_FORM_BUTTON_SELECT').'"  href="'.$link.'" rel="{handler: \'iframe\', size: {x: 600, y: 450}}">'.JText::_('JLIB_FORM_BUTTON_SELECT').'</a>';
		$html[] = '	</div>';
		$html[] = '</div>';
		$html[] = '<div class="button2-left">';
		$html[] = '	<div class="blank">';
		$html[] = '	'
		. '<a title="' . JText::_('JLIB_FORM_BUTTON_CLEAR') . '" href="#" onclick="'
		. 'document.id(\'' . $this->id . '\').value=\'\';'
		. 'document.id(\'' . $this->id . '\').fireEvent(\'change\');'
		. 'return false;'
		. '">';
		$html[] = JText::_('JLIB_FORM_BUTTON_CLEAR') . '</a>';
		$html[] = '	</div>';
		$html[] = '</div>';
		
		// return the html
		return implode("\n", $html);
	}
}
