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

class JFormFieldJevcfdblist extends JFormFieldList
{

	protected $node;

	//fetchElement($name, $value, &$node, $control_name, $allowmultiple = false)
	function getInput()
	{
		$name = $this->name;
		$value =  $this->value;
		$class = ( $this->element['class'] ? 'class="'.$this->element['class'].'"' : 'class="inputbox"' );

		$db = JFactory::getDbo();
		$tablename = $this->attribute('tablename',0);
		$valuefield = $this->attribute('valuefield',0);
		$labelfield = $this->attribute('labelfield',0);
		if (!$valuefield || !$labelfield || !$tablename) {
			return "<strong>Invalid attributes - please specify tablename, valuefield and labelfield</strong>";
		}
		$db->setQuery("Select $labelfield as text, $valuefield as value FROM $tablename");
		$data = $db->loadObjectList();
		$options = array();
		$options[] = JHTML::_('select.option', 0, " -- ");
		foreach ($data as $option)
		{
			$options[] = JHTML::_('select.option', $option->value, JText::_($option->text));
		}

		if ($this->attribute('multifilter',0)==1){
			if ($value != ""){
				$value = explode(",",$value);
				JArrayHelper::toInteger($value);
			}
			else {
				$value = array();
			}

			$size =  ( $this->attribute('filtersize') ? ' size="'.$this->attribute('filtersize').'"' : '' );
			$multiple = ' multiple="multiple"';
			return JHTML::_('select.genericlist', $options, $this->name, $class.$size.$multiple, 'value', 'text', $value, $this->id);
		}
		else {
			return JHTML::_('select.genericlist', $options, $this->name, $class, 'value', 'text', $value, $this->id);
		}
	}

	function fetchRequiredScript($name, &$node, $control_name)
	{
		$script =  "JevrRequiredFields.fields.push({'name':'".$control_name.$name."', 'default' :'".$this->attribute('default') ."' ,'reqmsg':'".trim(JText::_($this->attribute('requiredmessage'),true))."'}); ";
		return $script ;
	}
/*
	function fetchCategoryRestrictionScript($name, &$node, $control_name, $cats)
	{
		$script = "JevrCategoryFields.fields.push({'name':'".$name."', 'default' :'".$this->attribute('default') ."' ,'catids':".  json_encode($cats)."}); ";
		return $script;
	}
 */
	public function convertValue($value, $node){
		static $values;
		if (!isset($values)){
			$values =  array();
		}
		if (!isset($values[$this->attribute('name')])){
			$values[$this->attribute('name')]=array();
			$db = JFactory::getDbo();
			$tablename = $this->attribute('tablename',0);
			$valuefield = $this->attribute('valuefield',0);
			$labelfield = $this->attribute('labelfield',0);
			if (!$valuefield || !$labelfield || !$tablename) {
				return "<strong>Invalid attributed - please specify tablename, valuefield and labelfield</strong>";
			}
			$db->setQuery("Select $labelfield as text, $valuefield as value FROM $tablename");
			$data = $db->loadObjectList();

			foreach ($data as $option)
			{
				$val	= $option->value;
				$text	= JText::_($option->text);
				$values[$this->attribute('name')][$val] = $text;
			}
		}
		if (array_key_exists($value,$values[$this->attribute('name')])){
			return $values[$this->attribute('name')][$value];
		}
		else {
			return "";
		}
	}

	public function constructFilter($node){
		$this->node = $node;
		$this->filterType = str_replace(" ","",$this->attribute("name"));
		$this->filterLabel = is_null($this->attribute("filterlabel"))?$this->attribute("label"):$this->attribute("filterlabel");
		$this->filterNullValue = is_null($this->attribute("filterdefault"))?(is_null($this->attribute("default"))?"":$this->attribute("default")):$this->attribute("filterdefault");
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

		if ($this->attribute("multifilter")==1){
			$this->filterNullValue = array($this->filterNullValue);
			if (intval(JRequest::getVar('filter_reset',0))){
				JFactory::getApplication()->setUserState( $this->filterType.'_fv_ses', $this->filterNullValue );
			$this->filter_value = $this->filterNullValue;
			}
			// ALSO if this filter is not visible on the page then should not use filter value - does this supersede the previous condition ???
			else if (!$this->visible)
			{
				$this->filter_value =  JRequest::getVar($this->filterType.'_fv', $this->filterNullValue,"request", "array" );
				JArrayHelper::toInteger($this->filter_value);
			}
			else {
				$this->filter_value = JFactory::getApplication()->getUserStateFromRequest( $this->filterType.'_fv_ses', $this->filterType.'_fv', $this->filterNullValue );
				JArrayHelper::toInteger($this->filter_value);
			}
		}
		else {
			if (intval(JRequest::getVar('filter_reset',0))){
				JFactory::getApplication()->setUserState( $this->filterType.'_fv_ses', $this->filterNullValue );
			$this->filter_value = $this->filterNullValue;
			}
			// ALSO if this filter is not visible on the page then should not use filter value - does this supersede the previous condition ???
			else if (!$this->visible)
			{
				$this->filter_value =  JRequest::getVar($this->filterType.'_fv', $this->filterNullValue,"request", "int" );
			}
			else {
				$this->filter_value = JFactory::getApplication()->getUserStateFromRequest( $this->filterType.'_fv_ses', $this->filterType.'_fv', $this->filterNullValue );
			}
			$this->filter_value = intval($this->filter_value );
		}
		
		//$this->filter_value = JRequest::getInt($this->filterType.'_fv', $this->filterNullValue );
		
	}


	public function createJoinFilter(){
		if ($this->attribute("multifilter")==1){
			if ($this->filter_value==$this->filterNullValue) return "";
			if (count($this->filter_value)==0) return "";
		}
		else {
			if (isset($this->filter_value) && trim($this->filter_value)==$this->filterNullValue) return "";
		}
		$join =  " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id";
		
		$db = JFactory::getDBO();
		if ($this->attribute("multifilter")==1){
			$filter =  "$this->map.name=".$db->Quote($this->filterType). " AND ( ";
			$bits = array();
			foreach ($this->filter_value as $fv) {
				$bits[] = " $this->map.value RLIKE ".$db->Quote(",*".$fv.",*");
			}
			$filter .= implode(" OR ",$bits);
			$filter .= ")";
		}
		else {
			$filter =  "$this->map.name=".$db->Quote($this->filterType)." AND $this->map.value=".$db->Quote($this->filter_value);
		}
		return $join . " AND ". $filter;
		
	}

	public function createFilter(){
		if ($this->attribute("multifilter")==1){
			if ($this->filter_value==$this->filterNullValue) return "";
			if (count($this->filter_value)==0) return "";
		}
		else {
			if (isset($this->filter_value) && trim($this->filter_value)==$this->filterNullValue) return "";
		}
		return "$this->map.id IS NOT NULL";		
	}

	public function createFilterHTML(){
		$filterList=array();
		$filterList["title"]="<label class='evdblist_label' for='".$this->filterType."_fv'>".$this->filterLabel."</label>";
		if ($this->attribute("multifilter")==1){
			// allow multiple select!
			$filterList["html"] =  $this->fetchElement($this->filterType."_fv", implode(",",$this->filter_value), $this->node, "", true);
			$script = "try {JeventsFilters.filters.push({id:'".$this->filterType."_fv',value:".$this->filterNullValue[0] ."});} catch (e) {}";
		}
		else {
			$this->name = $this->filterType."_fv";
			$this->value = $this->filter_value;
			$filterList["html"] =  $this->getInput();
			//$filterList["html"] =  $this->fetchElement($this->filterType."_fv", $this->filter_value, $this->node, "");
			$script = "try {JeventsFilters.filters.push({id:'".$this->filterType."_fv',value:".$this->filterNullValue ."});} catch (e) {}";
		}

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