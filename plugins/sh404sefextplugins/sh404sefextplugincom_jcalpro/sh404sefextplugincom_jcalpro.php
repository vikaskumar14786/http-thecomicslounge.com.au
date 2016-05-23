<?php
/**
 * @package		JCalPro
 * @subpackage	plg_sh404sefextplugins_jcalpro

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

class Sh404sefExtpluginCom_jcalpro extends Sh404sefClassBaseextplugin
{
	protected $_extName = 'com_jcalpro';
	
	/**
	 * Constructor
	 * 
	 * @param unknown_type $subject
	 * @param unknown_type $config
	 */
	public function __construct(&$subject, $config) {
		// if something happens & the helper class can't be found, we don't want a fatal error here
		if (class_exists('JCalPro')) {
			JCalPro::language('plg_sh404sefextplugins_sh404sefextplugincom_jcalpro.sys', JPATH_ADMINISTRATOR);
		}
		else {
			$this->loadLanguage();
		}
		parent::__construct($subject, $config);
		$this->_pluginType = Sh404sefClassBaseextplugin::TYPE_SH404SEF_ROUTER;
	}
	
	
	protected function _findSefPluginPath($nonSefVars = array()) {
		$this->_sefPluginPath = dirname(__FILE__) . '/sef_ext/com_jcalpro.php';
	}
	
	protected function _findMetaPluginPath($nonSefVars = array()) {
		$this->_metaPluginPath = dirname(__FILE__) . '/meta_ext/com_jcalpro.php';
	}
}
