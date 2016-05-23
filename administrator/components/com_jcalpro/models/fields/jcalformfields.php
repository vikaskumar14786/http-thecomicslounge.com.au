<?php
/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
 * @deprecated

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
JLoader::register('JCalProHelperUrl', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/url.php');
JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');

class JFormFieldJCalFormFields extends JFormField
{
	public $type = 'Jcalformfields';

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
		// load the published fields - we'll sort them into two groups later
		JCalProBaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/models', 'JCalProModel');
		$model  = JCalPro::getModelInstance('Fields', 'JCalProModel');
		// TODO: why does setState not work?!
		$model->setState('filter.published', '1');
		$fields = $model->getItems();
		// make sure we actually HAVE fields to add :)
		if (empty($fields)) {
			return '<div>' . JText::_('COM_JCALPRO_FORMFIELDS_NO_FIELDS') . '</div>';
		}
		// load up the core filter, to save keystrokes later
		$filter = JFilterInput::getInstance();
		// start our arrays for sorting between the two
		$available = '';
		$assigned  = '';
		// loop the value first, so we can maintain ordering
		if (!empty($this->value)) {
			foreach ($this->value as $fid) {
				// now loop the fields and assign them as they're found
				foreach ($fields as $field) {
					if ($field->id == $fid) $assigned[] = $field;
				}
			}
		}
		// now get the available array
		foreach ($fields as $field) {
			// since we're looping here, go ahead and set an extra variable for the formtype class
			$field->_formtypeclass = 'jcalformfieldformtype';
			switch ($field->formtype) {
				case 0:  $field->_formtypeclass .= 'event'; break;
				case 1:  $field->_formtypeclass .= 'registration'; break;
				default: $field->_formtypeclass .= 'all'; break;
			}
			// go ahead and just add this to available if our value is empty
			if (empty($this->value)) {
				$available[] = $field;
				continue;
			}
			// check this field against our array of values
			if (!in_array($field->id, $this->value)) $available[] = $field;
		}
		// ok, we have each sorted - start constructing the html
		$html = array();
		// start by opening our element div
		$html[] = '<div class="jcalformfields jcalformfieldsclear">';
		// we need the field lists inside other containers so we can add text labels (#329)
		$html[] = '<div class="jcalformfieldslist">';
		// add the header to this list
		$html[] = '<h4>' . JText::_('COM_JCALPRO_FORMFIELDS_AVAILABLE') . '</h4>';
		// loop through the available fields and create new tags for each
		// take note we're adding the ul tag regardless so we maintain the 2 lists
		$html[] = '<ul class="jcalformfieldsavailable jcalformfieldssortable">';
		if (!empty($available)) {
			foreach ($available as $field) {
				// start this field element
				$html[] = '<li class="jcalformfield ' . $field->_formtypeclass . '" style="' . $this->_getIconStyle($this->_getIcon($field->type)) . '">';
				// add the text
				$html[] = $filter->clean($field->title, 'string');
				// also add a hidden input element so we can keep track of this element's id
				$html[] = '<input type="hidden" value="' . $field->id . '" />';
				// end this field element
				$html[] = '</li>';
			}
		}
		// end the available fields element
		$html[] = '</ul>';
		// add some extra text
		$html[] = '<p class="jcalformfieldsdesc">' . JText::_('COM_JCALPRO_FORMFIELDS_AVAILABLE_DESC') . '</p>';
		// end the container
		$html[] = '</div>';
		// open another div for the available fields
		$html[] = '<div class="jcalformfieldslist">';
		// add the header to this list
		$html[] = '<h4>' . JText::_('COM_JCALPRO_FORMFIELDS_ASSIGNED') . '</h4>';
		// start the list element
		$html[] = '<ul class="jcalformfieldsassigned jcalformfieldssortable">';
		// loop through the assigned fields and create new tags for each
		if (!empty($assigned)) {
			foreach ($assigned as $field) {
				// start this field element
				$html[] = '<li class="jcalformfield ' . $field->_formtypeclass . '" style="' . $this->_getIconStyle($this->_getIcon($field->type)) . '">';
				// for now just add the text
				$html[] = $filter->clean($field->title, 'string');
				// also add a hidden input element so we can keep track of this element's id
				$html[] = '<input type="hidden" value="' . intval($field->id) . '" />';
				// end this field element
				$html[] = '</li>';
			}
		}
		// end the assigned fields element
		$html[] = '</ul>';
		// add some extra text
		$html[] = '<p class="jcalformfieldsdesc">' . JText::_('COM_JCALPRO_FORMFIELDS_ASSIGNED_DESC') . '</p>';
		// end the container
		$html[] = '</div>';
		// it's not good to have this here, but in the interest of keeping things from breaking add a clearin element
		$html[] = '<div class="jcalformfieldsclear"><!-- --></div>';
		// go ahead and append a hidden input that will act as our main field
		$html[] = '<input type="' . (JDEBUG ? 'text' : 'hidden') . '" class="jcalformfieldsinput" name="' . $filter->clean($this->name) . '" value="' . $filter->clean('' . implode('|', $this->value)) . '" />';
		// end the main element
		$html[] = '</div>';
		// some debug info :P
		/*
		if (JDEBUG) {
			$html[] = "<pre>Fields = " . print_r($fields, 1) . "</pre><br />";
			$html[] = "<pre>Value = " . print_r($this->value, 1) . "</pre><br />";
		}
		*/
		if (JCalPro::version()->isCompatible('3.0.0')) {
			JHtml::_('jquery.ui', array('core', 'sortable'));
		}
		// load the javascript that controls the drag & drop
		JFactory::getDocument()->addScript(rtrim(JUri::root(), '/') . '/media/jcalpro/js/formfield.js');
		// load the stylesheet that controls the display of this field
		JFactory::getDocument()->addStyleSheet(rtrim(JUri::root(), '/') . '/media/jcalpro/css/formfield.css');
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
	
	private function _getIconStyle($icon) {
		return 'background-image:url(' . $icon . ');background-repeat:no-repeat;background-position:2px center;';
	}
}
