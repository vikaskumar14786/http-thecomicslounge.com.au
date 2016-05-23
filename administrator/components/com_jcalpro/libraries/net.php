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

class JcalNet
{
	var $host;
	var $timer = 20;
	
	var $agent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; JCalPro for Joomla!)';
	
	/**
	 * method to request a remote asset
	 * 
	 * @param mixed $url
	 */
	public function request($url = false) {
		if (empty($url)) {
			$url = $this->host;
		}
		if (empty($url)) {
			return false;
		}
		$data = false;
		try {
			if (function_exists('curl_init')) {
				$data = $this->_requestCurl($url);
			}
			else {
				$data = $this->_requestFsock($url);
			}
		}
		catch (Exception $e) {
			JFactory::getApplication()->enqueuemessage($e->getMessage(), 'error');
			return false;
		}
		return $data;
	}
	
	/**
	 * method to request a remote asset
	 * 
	 * @param string $url
	 */
	private function _requestFsock($url) {
		return file_get_contents($url);
	}
	
	/**
	 * private method to request a remote asset via curl
	 * 
	 * @param string $url
	 */
	private function _requestCurl($url) {
		$C = curl_init();
		if (!(@ini_get('open_basedir') || @ini_get('safe_mode'))) {
			@curl_setopt($C, CURLOPT_FOLLOWLOCATION, 1);
		}
		curl_setopt($C, CURLOPT_TIMEOUT, $this->timer);
		curl_setopt($C, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($C, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($C, CURLOPT_URL, $url);
		curl_setopt($C, CURLOPT_USERAGENT, $this->agent);
		$D = curl_exec($C);
		curl_close($C);
		return $D;
	}
}
