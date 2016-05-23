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

jimport('joomla.filesystem.folder');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('theme');
JCalPro::registerHelper('url');

JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');

class JFormFieldJCalFields extends JFormField
{
	public $type = 'Jcalfields';

	protected function getInput() {
		// text
		JText::script('COM_JCALPRO_JCALFORMFIELD_ERROR');
		JText::script('COM_JCALPRO_JCALFORMFIELD_NOSORTABLE');
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
		// check if this is limited to certain types of forms
		$filter = '';
		switch (''.$this->element['formtype']) {
			case '0':
			case 'event':
				$filter = 0;
				break;
			case '1':
			case 'registration':
				$filter = 1;
				break;
		}
		// load the published fields - we'll sort them into two groups later
		JCalProBaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/models', 'JCalProModel');
		$model  = JCalProBaseModel::getInstance('Fields', 'JCalProModel');
		$model->getState('filter.published', 1);
		$model->setState('filter.published', 1);
		if (is_numeric($filter)) {
			$model->setState('filter.formtype', $filter);
		}
		$fields = $model->getItems();
		// make sure we actually HAVE fields to add :)
		if (empty($fields)) {
			return '<div>' . JText::_('COM_JCALPRO_FORMFIELDS_NO_FIELDS') . '</div>';
		}
		// fix our ordering
		$ordered = array();
		foreach ($this->value as $fieldid) {
			foreach ($fields as $field) {
				if ($field->id == $fieldid) {
					$ordered[] = $field;
					break;
				}
			}
		}
		foreach ($fields as $field) {
			if (!in_array($field->id, $this->value)) {
				$ordered[] = $field;
			}
		}
		// load up the core filter, to save keystrokes later
		$filter = JFilterInput::getInstance();
		// ok, we have each sorted - start constructing the html
		$html = array();
		// start by opening our element div
		$html[] = '<div class="jcalfields">';
		// loop through the available fields and create new tags for each
		// take note we're adding the ul tag regardless so we maintain the 2 lists
		$html[] = '<ul class="jcalfieldsavailable">';
		$input = '<input id="%s" name="%s[]" type="%s" value="%s" %s />';
		foreach ($ordered as $i => $field) {
			$id = $this->id . $field->id;
			$html[] = '<li class="jcalfieldsavailableitem" style="' . $this->_getIconStyle($this->_getIcon($field->type)) . '">';
			// add a checkbox
			$html[] = sprintf('<label for="%s">', $filter->clean($id));
			$html[] = sprintf('<span>%s</span>', $filter->clean($field->title));
			$html[] = sprintf($input, $filter->clean($id), $filter->clean($this->name), 'checkbox', $filter->clean($field->id), (in_array($field->id, $this->value) ? 'checked="checked"' : ''));
			$html[] = '</label>';
			$html[] = $this->_getButtons();
			$html[] = '</li>';
		}
		// end the available fields element
		$html[] = '</ul>';
		// end the main element
		$html[] = '</div>';
		// load scripts
		JCalPro::loadJsFramework();
		$doc = JFactory::getDocument();
		$doc->addScript(JCalProHelperTheme::getFilePath('field.js', 'js'));
		// load the stylesheet that controls the display of this field
		JCalProHelperTheme::addStyleSheet('field');
		// return the html to the form
		return implode("\n", $html);
	}
	
	private function _getIcon($field) {
		static $icons;
		$relpath = '/images/fields';
		$base    = JCalProHelperUrl::media() . $relpath;
		if (is_null($icons)) {
			// grab the available icons
			$icons = JFolder::files(JPATH_ROOT . '/media/jcalpro' . $relpath, '.png$');
		}
		$icon = "icon-{$field}.png";
		if (in_array($icon, $icons)) {
			return $base . '/' . $icon;
		}
		return $base . '/icon-unknown.png';
	}
	
	private function _getButtons() {
		// build the buttons
		$button = '<input type="button" class="jcalfields_dir jcalfields_%s" value="%s" />';
		$buttons = sprintf($button, 'up', '↑') . ' ' . sprintf($button, 'down', '↓');
		$buttons = '<div class="jcalfields_dirs">' . $buttons . '</div>';
		// return the buttons
		return $buttons;
	}
	
	
	private function _getIconStyle($icon) {
		return 'background-image:url(' . $icon . ');background-repeat:no-repeat;background-position:2px center;';
	}
}
