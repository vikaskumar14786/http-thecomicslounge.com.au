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

class JFormFieldJCalKeyVal extends JFormField
{
	public $type = 'Jcalkeyval';

	protected function getInput() {
		// add our texts
		JText::script('COM_JCALPRO_JCALKEYVAL_EMPTY');
		JText::script('COM_JCALPRO_JCALKEYVAL_EMPTY_REMOVE');
		JText::script('COM_JCALPRO_JCALKEYVAL_ERROR');
		// prep the value - it SHOULD be an array, but who knows - maybe it won't be?
		// this is just some defensive coding, really - there's a slim to none chance this code will EVER be accessed!
		if (!is_array($this->value)) {
			if (false !== strpos((string) $this->value, ',')) {
				$this->value = explode(',', (string) $this->value);
			}
			else if (false !== strpos((string) $this->value, '|')) {
				$this->value = explode('|', (string) $this->value);
			}
			else if (!empty($this->value)) {
				$this->value = (array) $this->value;
			}
			else {
				$this->value = array();
			}
		}
		$filter = JFilterInput::getInstance();
		// get class for this element
		$class = $this->element['class'] ? ' '.$filter->clean((string) $this->element['class']) : '';
		// get labels
		$keylabel = $this->element['keylabel'] ? $filter->clean(JText::_((string) $this->element['keylabel'])) : '';
		$valuelabel = $this->element['valuelabel'] ? $filter->clean(JText::_((string) $this->element['valuelabel'])) : '';
		// start constructing the html
		$html = array();
		// start the main element
		$html[] = '<div class="jcalkeyval' . $class . '">';
		// create the key/value labels, if applicable
		if (!empty($keylabel) && !empty($valuelabel)) {
			$html[] = '<div class="jcalkeyval_labels">';
			$html[] = '	<span>' . $keylabel . '</span>';
			$html[] = '	<span>' . $valuelabel . '</span>';
			$html[] = '</div>';
		}
		// build our base element (for cloning later)
		$html[] = $this->_getDefaultRuleBlock();
		// start the stage
		$html[] = '<div class="jcalkeyval_stage">';
		// add values
		if (!empty($this->value)) {
			foreach ($this->value as $key => $value) {
				// add the block for this pair
				$html[] = $this->_getRuleBlock($key, $value);
			}
		}
		// add blank
		$html[] = $this->_getRuleBlock();
		// end the stage
		$html[] = '</div>';
		// end the main element
		$html[] = '</div>';
		// load the javascript that controls the field
		JFactory::getDocument()->addScript(rtrim(JUri::root(), '/') . '/media/jcalpro/js/keyval.js');
		// load the stylesheet that controls the display of this field
		JFactory::getDocument()->addStyleSheet(rtrim(JUri::root(), '/') . '/media/jcalpro/css/keyval.css');
		return implode("\n", $html);
	}

	private function _getDefaultRuleBlock() {
		return sprintf('<div class="jcalkeyval_default" style="display:none">%s</div>', $this->_getRuleBlock());
	}
	
	private function _getRuleBlock($key = '', $val = '') {
		// get our fields
		$useKey     = $this->_getInput($key, 'key');
		$useValue   = $this->_getInput($val, 'value');
		$useButtons = $this->_getButtons();
		// build & return html
		return sprintf('<div class="jcalkeyval_block">%s</div>', '<span class="jcalkeyval_inputs">' . $useKey.' '.$useValue.'</span> '.$useButtons);
	}
	
	private function _getInput($value, $name) {
		$filter = JFilterInput::getInstance();
		return '<input class="jcalkeyval_' . $filter->clean($name) . '" name="' . $filter->clean($this->name) . '[' . $filter->clean($name) . '][]" type="text" value="' . $filter->clean($value) . '" />';
	}
	
	private function _getButtons() {
		// build the buttons
		$button = '<input type="button" class="jcalkeyval_%s" value=" %s " />';
		$buttons = sprintf($button, 'add', '+') . ' ' . sprintf($button, 'sub', '-');
		// if this element has the ordering attribute, add ordering buttons too
		if ($this->element['ordering']) {
			$buttons .= ' ' . sprintf($button, 'up', '↑') . ' ' . sprintf($button, 'down', '↓');
		}
		// return the buttons
		return $buttons;
	}
	
}
