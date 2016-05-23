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

JLoader::register('JCalProBaseController', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/controllers/basecontroller.php');
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('url');

class JCalProController extends JCalProBaseController
{
	function display($cachable = false, $urlparams = false) {
		$app        = JFactory::getApplication();
		$view       = $app->input->get('view', 'Dashboard', 'cmd');
		$authorurl  = JCalPro::config('jcalpro_author_url');
		$configured = preg_match('/^https?\:\/{2}/', $authorurl);
		if (!$configured) {
			$app->enqueuemessage(JText::_('COM_JCALPRO_SAVE_CONFIG_WARNING'), 'warning');
		}
		// the help view acts as a redirect to the REAL help page
		// we only really use this in the main component submenu,
		// as any link we can handle via code will just use the config option
		if ('help' == strtolower($view)) {
			$app->redirect(JCalProHelperUrl::help());
			// tear down the application
			jexit();
		}
		$app->input->set('view', $view);
		parent::display($cachable);
	}
}
