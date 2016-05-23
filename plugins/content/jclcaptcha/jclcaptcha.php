<?php
/**
 * @package		JCalPro
 * @subpackage	plg_content_jclcaptcha

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

jimport('joomla.plugin.plugin');
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');

class plgContentJclCaptcha extends JPlugin
{
	/**
	 * Constructor
	 * 
	 * @param unknown_type $subject
	 * @param unknown_type $config
	 */
	public function __construct(&$subject, $config) {
		$app = JFactory::getApplication();
		if ($app->isAdmin() && JFactory::getUser()->id) {
			JCalPro::language('plg_content_jclcaptcha.sys', JPATH_ADMINISTRATOR);
			$app->enqueueMessage(JText::_('PLG_CONTENT_JCLCAPTCHA_WARNING'), 'warning');
		}
		parent::__construct($subject, $config);
	}	
}
