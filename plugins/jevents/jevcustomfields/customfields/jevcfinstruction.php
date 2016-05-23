<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class JFormFieldJevcfinstruction extends JFormField
{
	
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevcfinstruction';

	protected  function getInput()
	{				
		// This field has no data so we use the default atrribute value
		$value = $this->value;
		if (!$value) {
			$value = $this->attribute('default');
		}
		$class = ( $this->element->attributes()->class ? 'class="'.$this->element->attributes()->class.'"' : 'class="text_area"' );
		// convert <br /> tags so they are not visible when editing
		$value = str_replace('<br />', "\n", JText::_($value));
		return '<div '.$class.' id="'.$this->id.'" >'.$value.'</div>';
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