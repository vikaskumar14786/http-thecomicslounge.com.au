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

class JCalProViewForms extends JCalProListView
{
	/**
	 * Default sorting column
	 * 
	 * @var string
	 */
	protected $_sortColumn = 'Form.title';
	
	function display($tpl = null, $safeparams = false) {
		try {
			$this->form = JCalProHelperForm::getForm(JCalPro::COM . '.forms', 'forms', JCalPro::COM . '.forms');
		}
		catch (Exception $e) {
			JError::raiseError(500, $e->getMessage());
			jexit();
		}
		parent::display($tpl, $safeparams);
	}
	
	/**
	 * Returns an array of fields the table can be sorted by
	 * 
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields() {
		return array(
			'Form.title'      => JText::_('COM_JCALPRO_TITLE')
		,	'Form.type'       => JText::_('COM_JCALPRO_FORM_TYPE_LABEL')
		,	'FormFieldCount'  => JText::_('COM_JCALPRO_FIELD_COUNT')
		,	'Form.created_by' => JText::_('COM_JCALPRO_CREATED_BY')
		,	'Form.published'  => JText::_('COM_JCALPRO_PUBLISHED')
		,	'Form.default'    => JText::_('COM_JCALPRO_DEFAULT')
		);
	}
}