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

JFormHelper::loadFieldClass('list');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');

/**
 * Editor Buttons form field
 * 
 */
class JFormFieldJCalEditorButtons extends JFormFieldList
{
	public $type = 'Jcaleditorbuttons';
	
	protected function getOptions() {
		
		$options = array();
		
		$exclude = (string) $this->element['exclude'];
		
		// get all the editor buttons
		$db = JFactory::getDbo();
		
		$db->setQuery($db->getQuery(true)
			->select('element, name')
			->from('#__extensions')
			->where('type="plugin"')
			->where('folder="editors-xtd"')
			->where('enabled="1"')
		);
		
		$buttons = $db->loadObjectList();
		if (!empty($buttons)) foreach ($buttons as $button) {
			if ($exclude) {
				if (preg_match(chr(1) . $exclude . chr(1), $button->element)) {
					continue;
				}
			}
			JFactory::getLanguage()->load($button->name, JPATH_ADMINISTRATOR);
			JFactory::getLanguage()->load($button->name . '.sys', JPATH_ADMINISTRATOR);
			$options[] = JHtml::_('select.option', $button->element, JText::_($button->name));
		}
		
		return $options;
	}
}
