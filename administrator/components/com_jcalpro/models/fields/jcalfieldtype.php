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
JLoader::register('JCalProHelperPath', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/path.php');

class JFormFieldJCalFieldType extends JFormField
{
	public $type = 'Jcalfieldtype';

	protected function getInput() {
		// initialize our array for the field types
		// we're holding them here because we want to be able to sort them later
		$fieldtypes = array();
		// get class for this element
		$class = $this->element['class'] ? ' class="'.(string) $this->element['class'].'"' : '';
		// we may want to ignore certain field types
		// rather than hard-code those here, we'll read them from the xml element itself
		$ignored = array();
		if ($this->element['ignoredfields']) {
			$ignored = explode(',', (string) $this->element['ignoredfields']);
		}
		// our field files
		$fieldfiles = array();
		// go ahead and base this off the files available in the core lib folder
		$corelibs = JPATH_LIBRARIES . '/joomla/form/fields';
		// read the files availableJCal Media
		$corefieldfiles = JFolder::files($corelibs, '.php$');
		// put the core files on the stack
		if (is_array($corefieldfiles)) $fieldfiles = array_merge($fieldfiles, $corefieldfiles);
		// add in the jcal fields
		$jcallibs = JCalProHelperPath::library() . '/fields';
		$jcalfieldfiles = JFolder::files($jcallibs, '.php$');
		if (is_array($jcalfieldfiles)) $fieldfiles = array_merge($fieldfiles, $jcalfieldfiles);
		// go ahead & loop through our found fields, and add them to the stack if they're not being ignored
		if (!empty($fieldfiles)) {
			foreach ($fieldfiles as $filename) {
				$name = preg_replace('/^(.*?)\.(.*)$/', '\1', $filename);
				if (in_array($name, $ignored)) continue;
				$fieldtypes[] = $name;
			}
		}
		// TODO: fire a plugin trigger or something to get other available field types
		
		// sort field types alphabetically
		asort($fieldtypes);
		$fieldtypes = array_values($fieldtypes);
		// loop these types & create options
    for ($i = 0; $i < count($fieldtypes); $i++) {
      $list[] = JHtml::_('select.option', $fieldtypes[$i], $fieldtypes[$i], 'field_id', 'field_name');
    }
    // send back our select list
    return JHtml::_('select.genericlist', $list, $this->name, $class . ' size="1"', 'field_id', 'field_name', $this->value);
	}
}
