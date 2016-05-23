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
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('filter');

class JFormFieldModal_JCalLocation extends JFormField
{
	public $type = 'Modal_Jcallocation';
	
	public function getExposedInput() {
		return $this->getInput();
	}

	protected function getInput() {
		// Load the modal behavior script.
		JHtml::_('behavior.modal', 'a.modal-button');
		
		// Build the script.
		$script = array();
		$script[] = 'function jclSelectLocation_'.$this->id.'(id, title, catid, object) {';
		$script[] = '	document.id("'.$this->id.'").value = id;';
		$script[] = '	document.id("'.$this->id.'_name").value = title;';
		$script[] = '	SqueezeBox.close();';
		$script[] = '}';
		
		// Add the script to the document head.
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));
		
		// Setup variables for display.
		$html = array();
		$link = 'index.php?option=com_jcalpro&amp;view=locations&amp;layout=modal&amp;tmpl=component&amp;function=jclSelectLocation_'.$this->id;
		
		// filters
		$filters = $this->element['filters'] ? (string) $this->element['filters'] : '';
		$link .= $filters;
		
		$db = JFactory::getDBO();
		$db->setQuery((string) $db->getQuery(true)
			->select('title')
			->from('#__jcalpro_locations')
			->where('id = '.(int) $this->value)
		);
		$title = $db->loadResult();
		
		if ($error = $db->getErrorMsg()) {
			JError::raiseWarning(500, $error);
		}
		
		$buttontext = JText::_('COM_JCALPRO_CHANGE_LOCATION_BUTTON');
		
		if (empty($title)) {
			$title = JText::_('COM_JCALPRO_SELECT_A_LOCATION');
			$buttontext = JText::_('COM_JCALPRO_SELECT_A_LOCATION_BUTTON');
		}
		$title = JCalProHelperFilter::escape($title);
		$buttontext = JCalProHelperFilter::escape($buttontext);
		
		$modalopts = '{handler: \'iframe\', size: {x: 800, y: 450}}';
		
		// create the input
		if (JCalPro::version()->isCompatible('3.1.0')) {
			$html[] = '<input type="text" id="'.$this->id.'_name" value="'.$title.'" disabled="disabled" size="35" class="inpubox" />';
			$html[] = '<a id="' . $this->id . '_modal" class="modal-button btn btn-primary" title="' . JText::_('COM_JCALPRO_CHANGE_LOCATION') . '"  href="' . $link . '" rel="' . $modalopts . '">' . $buttontext . '</a>';
			if (!JCalPro::config('require_location')) {
				$html[] = '<a id="'.$this->id.'_clear" title="'.JText::_('COM_JCALPRO_CHANGE_LOCATION').'" class="btn" href="#" onclick="document.id(\''.$this->id.'_name\').value=\'' . JCalProHelperFilter::escape_js(JText::_('COM_JCALPRO_SELECT_A_LOCATION')) . '\';document.id(\''.$this->id.'\').value=\'\';return false;" >'.JText::_('JLIB_FORM_BUTTON_CLEAR').'</a>';
			}
		}
		else {
			// The current user display field.
			$html[] = '<div class="fltlft">';
			$html[] = '	<input type="text" id="'.$this->id.'_name" value="'.$title.'" disabled="disabled" size="35" />';
			$html[] = '</div>';
			
			// The user select button.
			$html[] = '<div class="button2-left">';
			$html[] = '	<div class="blank">';
			$html[] = '		<a id="' . $this->id . '_modal" class="modal-button" title="' . JText::_('COM_JCALPRO_CHANGE_LOCATION') . '"  href="' . $link . '" rel="' . $modalopts . '">' . $buttontext . '</a>';
			$html[] = '	</div>';
			$html[] = '</div>';
			
			// The clear button.
			if (!JCalPro::config('require_location')) {
				$html[] = '<div class="button2-left">';
				$html[] = '	<div class="blank">';
				$html[] = '		<a id="'.$this->id.'_clear" title="'.JText::_('COM_JCALPRO_CHANGE_LOCATION').'"  href="#" onclick="document.id(\''.$this->id.'_name\').value=\'' . JCalProHelperFilter::escape_js(JText::_('COM_JCALPRO_SELECT_A_LOCATION')) . '\';document.id(\''.$this->id.'\').value=\'\';return false;" >'.JText::_('JLIB_FORM_BUTTON_CLEAR').'</a>';
				$html[] = '	</div>';
				$html[] = '</div>';
			}
		}
		
		// The active event id field.
		if (0 == (int)$this->value) {
			$value = '';
		}
		else {
			$value = (int)$this->value;
		}
		
		// class='required' for client side validation
		$class = '';
		if ($this->required) {
			$class = ' class="required modal-value"';
		}
		
		$html[] = '<input type="hidden" id="'.$this->id.'"'.$class.' name="'.$this->name.'" value="'.$value.'" />';
		
		return implode("\n", $html);
	}
}
