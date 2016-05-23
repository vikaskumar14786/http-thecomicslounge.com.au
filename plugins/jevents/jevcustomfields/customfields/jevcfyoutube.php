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

class JFormFieldJevcfyoutube extends JFormFieldText
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevcfyoutube';

	function fetchRequiredScript($name, &$node, $control_name)
	{
		return "JevrRequiredFields.fields.push({'name':'" . $control_name . $name . "', 'default' :'" . $this->attribute('default') . "' ,'reqmsg':'" . trim(JText::_($this->attribute('requiredmessage'), true)) . "'}); ";

	}

	public function convertValue($value, $node)
	{
		if ($value == "" ||  $value == $this->attribute('hiddenvalue'))
			return $value;
		else if (strpos(strtolower($value), "vimeo=")===0){
			$value = str_ireplace("vimeo=","", $value);
			return '<iframe width="420" height="315" src="http://player.vimeo.com/video/' . $value . '?color=#339900" frameborder="0" allowfullscreen class="jevvimeo"></iframe>';
		}
		else {
			$value = str_ireplace("http://www.youtube.com/watch?v=","", $value);
			return '<iframe width="420" height="315" src="http://www.youtube.com/embed/' . $value . '" frameborder="0" allowfullscreen class="jevyoutube"></iframe>';
		}

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