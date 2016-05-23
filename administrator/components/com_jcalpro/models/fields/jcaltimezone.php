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

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('groupedlist');
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');

/**
 * Form Field class for JCalPro.
 * 
 * We've used our own form field because the core Timezone field is bugged in 1.7.0 - 1.7.3
 *
 */
class JFormFieldJCalTimezone extends JFormFieldGroupedList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 */
	protected $type = 'Jcaltimezone';

	/**
	 * The list of available timezone groups to use.
	 *
	 * @var    array
	 */
	protected static $zones = array(
		'Africa', 'America', 'Antarctica', 'Arctic', 'Asia',
		'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific'
	);

	/**
	 * Method to get the time zone field option groups.
	 *
	 * @return  array  The field option objects as a nested array in groups.
	 */
	protected function getGroups()
	{
		// Initialize variables.
		$groups = array();

		// If the timezone is not set use the server setting.
		if (strlen($this->value) == 0) {
			$this->value = JFactory::getUser()->getParam('timezone', JFactory::getConfig()->get('offset'));
		}

		// Get the list of time zones from the server.
		$zones = DateTimeZone::listIdentifiers();

		// Build the group lists.
		foreach ($zones as $zone) {

			// Time zones not in a group we will ignore.
			if (strpos($zone, '/') === false) {
				continue;
			}

			// Get the group/locale from the timezone.
			list ($group, $locale) = explode('/', $zone, 2);

			// Only use known groups.
			if (in_array($group, self::$zones)) {

				// Initialize the group if necessary.
				if (!isset($groups[$group])) {
					$groups[$group] = array();
				}

				// Only add options where a locale exists.
				if (!empty($locale)) {
					$groups[$group][$zone] = JHtml::_('select.option',
						$zone,
						str_replace('_', ' ', $locale), 'value', 'text', false);
				}
			}
		}

		// Sort the group lists.
		ksort($groups);
		foreach($groups as $zone => & $location) {
			sort($location);
		}

		// Merge any additional groups in the XML definition.
		$groups = array_merge(parent::getGroups(), $groups);

		return $groups;
	}
}