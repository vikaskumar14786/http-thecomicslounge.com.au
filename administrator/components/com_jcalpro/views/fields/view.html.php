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

JLoader::register('JCalProListView', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/views/baseviewlist.php');
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('form');
JCalPro::registerHelper('url');

class JCalProViewFields extends JCalProListView
{
	/**
	 * Default sorting column
	 * 
	 * @var string
	 */
	protected $_sortColumn = 'Field.title';
	
	function display($tpl = null, $safeparams = false) {
		try {
			$this->form = JCalProHelperForm::getForm(JCalPro::COM . '.fields', 'fields', JCalPro::COM . '.fields');
		}
		catch (Exception $e) {
			JError::raiseError(500, $e->getMessage());
			jexit();
		}
		$state = $this->get('State');
		$opts = array(
			JHtml::_('select.option', '', JText::_('COM_JCALPRO_FILTER_FORMTYPE'))
		,	JHtml::_('select.option', 0, JText::_('COM_JCALPRO_FORM_TYPE_OPTION_EVENT'))
		,	JHtml::_('select.option', 1, JText::_('COM_JCALPRO_FORM_TYPE_OPTION_REGISTRATION'))
		);
		$this->addFilter(JText::_('COM_JCALPRO_FILTER_FORMTYPE'), 'filter_formtype', $opts, $state->get('filter.formtype'));
		
		parent::display($tpl, $safeparams);
	}
	
	/**
	 * Returns an array of fields the table can be sorted by
	 * 
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields() {
		return array(
			'Field.title'      => JText::_('COM_JCALPRO_TITLE')
		,	'Field.type'       => JText::_('COM_JCALPRO_FIELD_TYPE_LABEL')
		,	'Field.formtype'   => JText::_('COM_JCALPRO_FIELD_FORMTYPE_LABEL')
		,	'Field.created_by' => JText::_('COM_JCALPRO_CREATED_BY')
		,	'Field.published'  => JText::_('COM_JCALPRO_PUBLISHED')
		);
	}
}