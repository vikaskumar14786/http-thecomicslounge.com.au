<?php
/**
 * @package		JCalPro
 * @subpackage	mod_jcalpro_flex

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

jimport('joomla.filesystem.file');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
jimport('joomla.html.html');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('path');
JCalPro::registerHelper('theme');

JHtml::addIncludePath(JCalProHelperPath::site() . '/helpers/html');

class JFormFieldJCalFlexPanel extends JFormField
{
	public $type = 'Jcalflexpanel';
	
	protected function addScripts() {
		if (JCalPro::version()->isCompatible('3.0.0')) {
			JHtml::_('jquery.ui', array('core', 'sortable'));
		}
		// add the scripts
		JText::script('MOD_JCALPRO_FLEX_CONFIRM_PANEL_DELETE');
		$doc = JFactory::getDocument();
		$doc->addScript(JCalProHelperUrl::media() . '/js/jcalpro.js');
		$doc->addScript(JCalProHelperUrl::media() . '/modules/flex/js/admin.js');
		// load the css
		JCalProHelperTheme::addStyleSheet('config');
		JCalProHelperTheme::addStyleSheet('jcalflexpanel', 'modules/flex/css');
	}
	
	/**
	 * This method displays an input that loads the "basic" form fields for the events module
	 * for each iteration of the given values. Then, when the flex module displays event lists,
	 * we don't have a bunch of duplicated code (yay).
	 * 
	 * (non-PHPdoc)
	 * @see JFormField::getInput()
	 */
	protected function getInput() {
		// we need to know if this is 2.5 or not
		$legacy = !JCalPro::version()->isCompatible('3.0.0');
		// load scripts
		$this->addScripts();
		// we need a default name
		$name = 0;
		// print hidden fieldsets (#jcalflexpanel_events_0 and #jcalflexpanel_calendar_0 are hidden via css)
		$this->_printPanelForm('events', $name);
		$this->_printPanelForm('calendar', $name);
		// "add panel" controls
		echo '<div class="toolbar btn-group input-append">';
		$opts = array(
			JHtml::_('select.option', 'events', JText::_('MOD_JCALPRO_FLEX_PANEL_TYPE_EVENTS'))
		,	JHtml::_('select.option', 'calendar', JText::_('MOD_JCALPRO_FLEX_PANEL_TYPE_CALENDAR'))
		);
		
		// select box
		$select = JHtml::_('select.genericlist', $opts, '', 'size="1"', 'value', 'text', 'events', 'jcalflexpanel_type_select');
		// Joomla! 2.5 button
		if ($legacy) {
			echo '<a href="#" class="icon-32-new" onclick="jcal_flex_panel_add();return false;"> </a>' . $select;
		}
		// Joomla! 3.0+ uses bootstrap
		else {
			echo $select . '<a href="#" class="btn btn-small btn-primary" onclick="jcal_flex_panel_add();return false;"><i class="icon-save-new"> </i></a>';
		}
		echo '</div>';
		// start official "panels" element
		echo '<div class="jcalflexpanels">';
		// start our slides
		//echo JHtml::_('jcalpro.startSlider', 'jcalflexpanels_slider_' . $this->id);
		// loop our value (if it's not empty) and create a new form for each panel
		if (!empty($this->value) && is_array($this->value)) {
			foreach ($this->value as $values) {
				if (is_object($values)) {
					$values = JArrayHelper::fromObject($values);
				}
				if (empty($values['panel_title'])) {
					continue;
				}
				switch ($values['panel_type']) {
					case 'events':
					case 'calendar':
						++$name;
						//echo JHtml::_('jcalpro.addSlide', JText::_('MOD_JCALPRO_FLEX_PANEL_TYPE_' . strtoupper($values['panel_type'])), 'jcalflexpanels_slider_slide_' . $name);
						$this->_printPanelForm($values['panel_type'], $name, $values);
						//echo JHtml::_('jcalpro.endSlide');
						break;
				}
			}
		}
		//echo JHtml::_('jcalpro.endSlider');
		echo '</div>';
	}
	
	private function _printPanelForm($type, $name, $values = array()) {
		switch ($type) {
			case 'events':
			case 'calendar':
				$values['panel_type'] = $type;
				break;
			default:
				echo JText::_('MOD_JCALPRO_FLEX_ERROR_TYPE_NOT_FOUND');
				return;
		}
		$xmlfile = JPATH_ROOT . "/modules/mod_jcalpro_{$type}/mod_jcalpro_{$type}.xml";
		if (!JFile::exists($xmlfile)) {
			echo JText::_('MOD_JCALPRO_FLEX_ERROR_' . $type . '_MODULE_NOT_FOUND');
			return;
		}
		// load our language
		JFactory::getLanguage()->load('mod_jcalpro_' . $type, JPATH_ROOT);
		// we need to know if this is 2.5 or not
		$legacy = !JCalPro::version()->isCompatible('3.0');
		// create a new JForm and populate it with the event module's params
		$form = new JForm($name, array('control' => $this->name . "[{$name}]"));
		$form->loadFile($xmlfile, true, 'config');
		$form->loadFile(JPATH_ROOT . '/modules/mod_jcalpro_flex/forms/panel.xml');
		$form->bind($values);
		// now get the "params" group
		$params = $form->getGroup('params');
		// the id for our fieldset
		$id = 'jcalflexpanel_' . $type . '_' . $name;
		// our html
		$html = array();
		// our panel
		$html[] = '<div class="jcalflexpanel" id="' . $id . '">';
		// leave an anchor to each panel
		$html[] = '<a name="panel_' . $name . '"></a>';
		// start our panel fieldset
		$html[] = '<fieldset class="jcalflexpanel_fieldset">';
		// add a legend
		$html[] = "<legend>" . JText::sprintf('MOD_JCALPRO_FLEX_PANEL_LEGEND', "<span>$name</span>", JText::_('MOD_JCALPRO_FLEX_PANEL_TYPE_' . strtoupper($type))) . "</legend>";
		
		// add our delete button
		$html[] = '<div class="toolbar">';
		if ($legacy) {
			$html[] = '	<a href="#" class="icon-32-cancel" onclick="jcal_flex_panel_del(this);return false;"> </a>';
		}
		else {
			$html[] = '	<a href="#" class="btn btn-small pull-right" onclick="jcal_flex_panel_del(this);return false;"><i class="icon-cancel"> </i></a>';
		}
		$html[] = '</div>';
		
		// start a new list for legacy mode
		if ($legacy) {
			$html[] = '<ul class="adminformlist">';
		}
		
		// render the panel-specific stuff
		foreach ($form->getFieldset('jcalpro') as $param) {
			// render the item
			$html[] = $this->_printPanelField($param);
		}
		
		// render each module param
		foreach ($params as $param) {
			// ensure we're not showing any of the "advanced" fieldset group
			if (in_array($param->__get('fieldname'), array('moduleclass_sfx', 'cache', 'cache_time', 'cachemode'))) {
				continue;
			}
			// render the item
			$html[] = $this->_printPanelField($param);
		}
		
		// end the list for legacy mode
		if ($legacy) {
			$html[] = '</ul>';
		}
		
		// end the fieldset
		$html[] = "</fieldset>";
		// end the panel
		$html[] = '</div>';
		// render
		echo implode("\n", $html);
	}
	
	private function _printPanelField($field) {
		$label = $field->getLabel();
		$input = $field->getInput();
		$empty = empty($label) || empty($input);
		$html = array();
		if (JCalPro::version()->isCompatible('3.0')) {
			if ($empty) {
				return '<div class="hidden">' . $input . '</div>';
			}
			$html[] = '<div class="control-group">';
			$html[] =   '<div class="control-label">' . $label . '</div>';
			$html[] =   '<div class="controls">' . $input . '</div>';
			$html[] = '</div>';
			return implode("\n", $html);
		}
		if ($empty) {
			return '<li style="display:none">' . $input . '</li>';
		}
		$html[] = '<li>' . $label . $input . '</li>';
		return implode("\n", $html);
	}
}
