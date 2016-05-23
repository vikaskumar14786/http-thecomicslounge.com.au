<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevboolean.php 1569 2009-09-16 06:22:03Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldJevcfmultilist extends JFormFieldList
{

	function fetchElement($name, $value, &$node, $control_name)
	{
		if ($value != ""){
			$value = explode(",",$value);
			JArrayHelper::toInteger($value);
		}
		else {
			$value = array();
		}
		$class = ( $this->element['class'] ? 'class="'.$this->element['class'].'"' : 'class="inputbox"' );
		$size =  ( $this->attribute('size') ? ' size="'.$this->attribute('size').'"' : '' );

		if ($size=="" && isset($this->filtersize)){
			$size = ' size="'.$this->filtersize.'"';
		}

		$multiple = "";
		if ($this->attribute('size')>0 || ($this->attribute('size')=="" && isset($this->filtersize) && $this->filtersize>1) ){
			$multiple = ' multiple="multiple"';
		}
		if ($this->attribute("multifilter")==-1){
			$multiple =  "";
		}

		$options = array ();
		foreach ($this->element->children() as $option)
		{
			if ((int) $option['archive']) continue;
			$val	= (string) $option["value"];
			$text	= (string) $option;
			$options[] = JHTML::_('select.option', $val, JText::_($text));
		}

		if ($multiple) {
			return JHTML::_('select.genericlist',  $options, ''.$control_name.$name."[]", $class.$size.$multiple, 'value', 'text', $value, $control_name.$name);
		}
		else {
			return JHTML::_('select.genericlist',  $options, ''.$control_name.$name, $class.$size.$multiple, 'value', 'text', $value, $control_name.$name);
		}
	}

	function fetchRequiredScript($name, &$node, $control_name)
	{
		return "JevrRequiredFields.fields.push({'name':'".$control_name.$name."', 'default' :'".$this->attribute('default') ."' ,'reqmsg':'".trim(JText::_($this->attribute('requiredmessage'),true))."'}); ";
	}

	protected function getInput() {
		// make sure we have a helpful class set to get the width
		if (!$this->element['class'] ){
			$this->element['class'] =" jevminwidth";
		}
		$this->multiple=true;
		$this->name .= "[]";
		$this->value = is_string($this->value)? explode(",",$this->value ) : $this->value ;
		return parent::getInput();
	}

	public function convertValue($value, $node){
		if ($value =="") return;
		$value = explode(",",$value);
		JArrayHelper::toInteger($value);

		static $values;
		if (!isset($values)){
			$values =  array();
		}
		if (!isset($values[$this->attribute('name')])){
			$values[$this->attribute('name')]=array();
			foreach ($this->element->children() as $option)
			{
				$val	= (string) $option['value'];
				$text	= (string) $option;
				$values[$this->attribute('name')][$val] = $text;
			}
		}
		$output = array();
		foreach ($value as $v) {
			if (array_key_exists($v,$values[$this->attribute('name')])) $output[] = $values[$this->attribute('name')][$v];
		}

		return implode(", ",$output);
	}

	public function constructFilter($node){
		$this->node = $node;
		$this->filterType = str_replace(" ","",$this->attribute("name"));
		$this->filterLabel = is_null($this->attribute("filterlabel"))?$this->attribute("label"):$this->attribute("filterlabel");
		$this->filterNullValue = is_null($this->attribute("filterdefault"))?(is_null($this->attribute("default"))?"":$this->attribute("default")):$this->attribute("filterdefault");
		$this->filterNullValue = array($this->filterNullValue);
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
			$this->filter_value =  JRequest::getVar($this->filterType.'_fv', $this->filterNullValue,"request", "array" );
			if (!is_array($this->filter_value)){
				$this->filter_value = array($this->filter_value);
			}
			JArrayHelper::toInteger($this->filter_value);
		}
		else {
			$this->filter_value = JFactory::getApplication()->getUserStateFromRequest( $this->filterType.'_fv_ses', $this->filterType.'_fv', $this->filterNullValue );
			if (!is_array($this->filter_value)){
				$this->filter_value = array($this->filter_value);
			}
			JArrayHelper::toInteger($this->filter_value);
		}

		/*
		$this->filter_value = JRequest::getVar($this->filterType.'_fv', $this->filterNullValue ,"request", "array");
		JArrayHelper::toInteger($this->filter_value);
		*/
	}

	public function createJoinFilter(){
		if ($this->filter_value==$this->filterNullValue) return "";
		if (count($this->filter_value)==0) return "";
		$join =  " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id";
		$db = JFactory::getDBO();
		$filter =  "$this->map.name=".$db->Quote($this->filterType). " AND ( ";
		$bits = array();
		foreach ($this->filter_value as $fv) {
			$bits[] = " $this->map.value RLIKE ".$db->Quote(",*".$fv.",*");
		}
		$filter .= implode(" OR ",$bits);
		$filter .= ")";
		$join .= " AND " . $filter;
		return $join;
	}

	public function createFilter(){
		if ($this->filter_value==$this->filterNullValue) return "";
		if (count($this->filter_value)==0) return "";
		return "$this->map.id IS NOT NULL";
	}

	public function createFilterHTML(){
		if ($this->element->attributes()->filtersize){
			$this->element->attributes()->size = (string) ( $this->element->attributes()->filtersize ? $this->element->attributes()->filtersize  : $this->element->attributes()->size);
			$this->filtersize = (string) ( $this->element->attributes()->filtersize ? $this->element->attributes()->filtersize  : $this->element->attributes()->size);
		}
		$filterList=array();
		$filterList["title"]="<label class='evdate_label' for='".$this->filterType."_fv'>".JText::_($this->filterLabel)."</label>";
		$filterList["html"] =  $this->fetchElement($this->filterType."_fv", implode(",",$this->filter_value), $this->node, "");

		$script = "try{JeventsFilters.filters.push({id:'".$this->filterType."_fv',value:".$this->filterNullValue[0] ."});} catch (e) {}";
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);

		return $filterList;
	}

	public function attribute($attr){
		$val = $this->element->attributes()->$attr;
		$val = !is_null($val)?(string)$val:null;
		return $val;
	}

	public function getOptions(){
		return parent::getOptions();
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