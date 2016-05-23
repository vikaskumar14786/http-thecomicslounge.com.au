<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('textarea');

class JFormFieldJevcfnotes extends JFormFieldTextarea 
{
	public function getInput(){
		
		$this->_validuser = explode(",",$this->attribute('userid'));
		
		$user = JFactory::getUser();
		if (!in_array($user->id, $this->_validuser)) {
			$this->element->attributes()->label = "";
			// set access to something impossible too!
			$this->element->attributes()->access = -999;
			return "";
		}
		
		$this->value = str_replace('<br />', "\n", $this->value);
		return parent::getInput();		
	}
	
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevcfnotes';
	var $_validuser = 0;
	
	
	function render(&$xmlElement, $value, $control_name = 'params')
	{
		$name	= $xmlElement->attributes('name');
		$label	= $xmlElement->attributes('label');
		$descr	= $xmlElement->attributes('description');
		//make sure we have a valid label
		$label = $label ? $label : $name;
		$this->_validuser = $xmlElement->attributes('userid');
		$user = JFactory::getUser();
		if ($user->id != $this->_validuser) {
			$result[0] = "";
			$result[1] = "";
		}
		else {
			$result[0] = $this->fetchTooltip($label, $descr, $xmlElement, $control_name, $name);
			$result[1] = $this->fetchElement($name, $value, $xmlElement, $control_name);
		}
		$result[2] = $descr;
		$result[3] = $label;
		$result[4] = $value;
		$result[5] = $name;

		return $result;
	}
	
	public function convertValue($value, &$node){
		$this->_validuser = explode(",",$this->attribute('userid'));
		
		$user = JFactory::getUser();
		if (!in_array($user->id, $this->_validuser)) {
			$this->element->attributes()->label = "";
			// set access to something impossible too!
			$this->element->attributes()->access = -999;
			return "";
		}

		return $value;
	}
	
	function fetchRequiredScript($name, &$node, $control_name) 
	{

		$user = JFactory::getUser();
		if ($user->id != $this->_validuser) return "";
				
		return "JevrRequiredFields.fields.push({'name':'".$control_name.$name."', 'default' :'".$this->attribute('default') ."' ,'reqmsg':'".trim(JText::_($this->attribute('requiredmessage'),true))."'}); ";
	}

	public function constructFilter($node){

		$user = JFactory::getUser();
		if ($user->id != $this->_validuser) return "";
				
		$this->node = $node;
		$this->filterType = str_replace(" ","",$this->attribute("name"));
		$this->filterLabel = is_null($this->attribute("filterlabel"))?$this->attribute("label"):$this->attribute("filterlabel");
		$this->filterNullValue = is_null($this->attribute("filterdefault"))?$this->attribute("default"):$this->attribute("filterdefault");
		$this->filter_value = $this->filterNullValue;
		$this->map = "csf".$this->filterType;
		
		$registry	= JRegistry::getInstance("jevents");
		$this->indexedvisiblefilters = $registry->get("indexedvisiblefilters",false);
		if ($this->indexedvisiblefilters === false) return;
		
		// This is our best guess as to whether this filter is visible on this page.
		$this->visible = in_array("customfield",$this->indexedvisiblefilters);
		
		// If using caching should disable session filtering if not logged in
		$cfg	 = JEVConfig::getInstance();
		$useCache = intval($cfg->get('com_cache', 0));
		$user = JFactory::getUser();
		$mainframe = JFactory::getApplication();
		if (intval(JRequest::getVar('filter_reset',0))){
			JFactory::getApplication()->setUserState( $this->filterType.'_fv_ses', $this->filterNullValue );
			$this->filter_value = $this->filterNullValue;
		}
		// ALSO if this filter is not visible on the page then should not use filter value - does this supersede the previous condition ???
		else if (!$this->visible)
		{
			$this->filter_value =  JRequest::getVar($this->filterType.'_fv', $this->filterNullValue,"request", "string" );
		}
		else {
			$this->filter_value = JFactory::getApplication()->getUserStateFromRequest( $this->filterType.'_fv_ses', $this->filterType.'_fv', $this->filterNullValue , "string");
		}
		
	}
		
	public function createJoinFilter(){

		$user = JFactory::getUser();
		if ($user->id != $this->_validuser) return "";

		if (trim($this->filter_value)==$this->filterNullValue) return "";
		$join = " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id";
		$db = JFactory::getDBO();
		$filter =  "$this->map.name=".$db->Quote($this->filterType)." AND $this->map.value LIKE (".$db->Quote($this->filter_value."%").")";
		return $join . " AND ". $filter;
	}
	
	public function createFilter(){

		$user = JFactory::getUser();
		if ($user->id != $this->_validuser) return "";

		if (trim($this->filter_value)==$this->filterNullValue) return "";
		return "$this->map.id IS NOT NULL";
		
	}
	
	function fetchFilterElement($name, $value, &$node, $control_name)
	{

		$user = JFactory::getUser();
		if ($user->id != $this->_validuser) return "";
						
		$size = ( $this->attribute('size') ? 'size="'.$this->attribute('size').'"' : '' );
		$class = ( $this->element['class'] ? 'class="'.$this->element['class'].'"' : 'class="text_area"' );
		/*
		* Required to avoid a cycle of encoding &
		* html_entity_decode was used in place of htmlspecialchars_decode because
		* htmlspecialchars_decode is not compatible with PHP 4
		*/
		$value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES), ENT_QUOTES);

		return '<input type="text" name="'.$control_name.$name.'" id="'.$control_name.$name.'" value="'.$value.'" '.$class.' '.$size.' />';

	}
	

	public function createFilterHTML(){

		$user = JFactory::getUser();
		if ($user->id != $this->_validuser) return "";
						
		$filterList=array();
		$filterList["title"]="<label class='evdate_label' for='".$this->filterType."_fv'>".JText::_($this->filterLabel)."</label>";
		$filterList["html"] =  $this->fetchFilterElement($this->filterType."_fv", $this->filter_value, $this->node, "");
		
		$script = "try {JeventsFilters.filters.push({id:'".$this->filterType."_fv',value:'".addslashes($this->filterNullValue)."'});} catch (e) {}";
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);
		
		return $filterList;
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