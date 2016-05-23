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
JCalPro::registerHelper('filter');
jimport('jcaldate.date');

/**
 * Date format form field
 * 
 */
class JFormFieldJCalDateFormat extends JFormFieldList
{
	public $type = 'Jcaldateformat';
	
	protected function getOptions() {
		// options list
		$options = array();
		// Feb 1 is being used because the month and day differ AND are less than 10 for leading 0s
		$date = JCalDate::_()->toMonth(2)->toMonthStart();
		// known available formats
		$formats = array(
			'l, F j, Y' // Saturday, February 1, 2014
		,	'l, F j'    // Saturday, February 1
		,	'l, F d, Y' // Saturday, February 01, 2014
		,	'l, F d'    // Saturday, February 01
		,	'l, M j, Y' // Saturday, Feb 1, 2014
		,	'l, M j'    // Saturday, Feb 1
		,	'l, M d, Y' // Saturday, Feb 01, 2014
		,	'l, M d'    // Saturday, Feb 01
		,	'F d, Y'    // February 01, 2014
		,	'F d'       // February 01
		,	'F j, Y'    // February 1, 2014
		,	'F j'       // February 1
		,	'd F, Y'    // 01 February, 2014
		,	'd F'       // 01 February
		,	'j F, Y'    // 1 February, 2014
		,	'j F'       // 1 February
		,	'F Y'       // February 2014
		,	'F y'       // February 14
		,	'M Y'       // Feb 2014
		,	'M y'       // Feb 14
		,	'D. M j, Y' // Sat. Feb 1, 2014
		,	'D. M j'    // Sat. Feb 1
		,	'D. j M, Y' // Sat. 1 Feb, 2014
		,	'D. j M'    // Sat. 1 Feb
		,	'd-M-Y'     // 01-Feb-2014
		,	'd/M/Y'     // 01/Feb/2014
		,	'd M Y'     // 01 Feb 2014
		,	'd-M-y'     // 01-Feb-14
		,	'd/M/y'     // 01/Feb/14
		,	'd M y'     // 01 Feb 14
		,	'd-m-Y'     // 01-02-2014
		,	'd/m/Y'     // 01/02/2014
		,	'd m Y'     // 01 02 2014
		,	'd-m-y'     // 01-02-14
		,	'd/m/y'     // 01/02/14
		,	'd m y'     // 01 02 14
		,	'M-d-Y'     // Feb-01-2014
		,	'M/d/Y'     // Feb/01/2014
		,	'M d Y'     // Feb 01 2014
		,	'M-d-y'     // Feb-01-14
		,	'M/d/y'     // Feb/01/14
		,	'M d y'     // Feb 01 14
		,	'm-d-Y'     // 02-01-2014
		,	'm/d/Y'     // 02/01/2014
		,	'm d Y'     // 02 01 2014
		,	'm-d-y'     // 02-01-14
		,	'm/d/y'     // 02/01/14
		,	'm d y'     // 02 01 14
		,	'l'         // Saturday
		,	'D.'        // Sat.
		,	'F'         // February
		,	'M'         // Feb
		);
		// start with the custom value, if any
		if (!empty($this->value) && !in_array($this->value, $formats)) {
			$value = $this->value;
			try {
				$label = $date->format($this->value);
			}
			catch (Exception $e) {
				$label = $this->value;
			}
			$label = JCalProHelperFilter::escape($label);
			$value = JCalProHelperFilter::escape($value);
			$options[] = JHtml::_('select.option', $value, $label);
		}
		// add any xml-defined options
		$options = array_merge($options, parent::getOptions());
		// add the defaults
		foreach ($formats as $label => $format) {
			$options[] = JHtml::_('select.option', $format, $date->format($format));
		}
		
		return $options;
	}
}
