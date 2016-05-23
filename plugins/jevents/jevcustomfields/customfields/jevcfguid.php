<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevtext.php 1569 2009-09-16 06:22:03Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('text');

class JFormFieldJevcfguid extends JFormFieldText
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevcfguid';

	function getInput()
	{		
		$this->value = $this->value=="" ? $this->create_guid("bviv") : $this->value;
		return parent::getInput();

	}

	
	public function setSearchKeywords(&$extrajoin)
	{
		if ( $this->attribute('searchable'))
		{
			$db = JFactory::getDBO();
			if (strpos($extrajoin, " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id") === false)
			{
				$extrajoin .= "\nLEFT JOIN #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id";
			}
			return "($this->map.name=" . $db->Quote($this->filterType) . " AND $this->map.value LIKE (" . $db->Quote('###' . "%") . "))";
		}

	}

	public function create_guid($namespace = '')
	{
		static $guid = '';
		$uid = uniqid("", true);
		$data = $namespace;
		$data .= JRequest::getString('REQUEST_TIME', '', 'server');
		$data .= JRequest::getString('HTTP_USER_AGENT', '', 'server');
		$data .= JRequest::getString('LOCAL_ADDR', '', 'server');
		$data .= JRequest::getString('LOCAL_PORT', '', 'server');
		$data .= JRequest::getString('REMOTE_ADDR', '', 'server');
		$data .= JRequest::getString('REMOTE_PORT', '', 'server');
		$hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
		/*
		$guid = '{' .
				substr($hash, 0, 8) .
				'-' .
				substr($hash, 8, 4) .
				'-' .
				substr($hash, 12, 4) .
				'-' .
				substr($hash, 16, 4) .
				'-' .
				substr($hash, 20, 12) .
				'}';
		 */
		$guid = 	substr($hash, 0, 8) .
				'-' .
				substr($hash, 8, 4) .
				'-' .
				substr($hash, 12, 4) .
				'-' .
				substr($hash, 16, 4) .
				'-' .
				substr($hash, 20, 12) 
				;
		return $guid;

	}

	public function attribute($attr){
		$val = $this->element->attributes()->$attr;
		$val = !is_null($val)?(string)$val:null;
		return $val;
	}

	/**
	 * Magic setter; allows us to set protected values
	 * @param string $name
	 * @return nothing
	 */
	public function setValue($value) {
		$this->value = $value;
	}
}