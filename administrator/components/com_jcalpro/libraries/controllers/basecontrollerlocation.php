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

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');

JCalPro::registerHelper('path');

JLoader::register('JCalProBaseControllerForm', JCalProHelperPath::library() . '/controllers/basecontrollerform.php');

class JCalProLocationController extends JCalProBaseControllerForm
{
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id') {
		// get the results of the parent method
		$append = parent::getRedirectToItemAppend($recordId, $urlVar);
		// check if "function" is defined & if so, add it
		$function = JFactory::getApplication()->input->get('function', '', 'cmd');
		if (!empty($function)) $append .= '&function=' . $function;
		// return final append string
		return $append;
	}
	
	protected function getRedirectToListAppend() {
		$input    = JFactory::getApplication()->input;
		// get the results of the parent method
		$append   = parent::getRedirectToListAppend();
		// check if "mlayout" is defined & if so, add it
		$mlayout  = $input->get('mlayout', '', 'cmd');
		if (!empty($mlayout)) $append .= '&layout=' . $mlayout;
		// check if "function" is defined & if so, add it
		$function = $input->get('function', '', 'cmd');
		if (!empty($function)) $append .= '&function=' . $function;
		// return final append string
		return $append;
	}
}
