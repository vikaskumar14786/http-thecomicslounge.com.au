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

class JFormFieldJevcflist extends JFormFieldList
{

	protected $node;

	public function getInput() {
		// make sure we have a helpful class set to get the width
		if (!$this->element['class'] ){
			$this->element['class'] =" jevminwidth";
		}
		return parent::getInput();
	}
	
	function fetchElement($name, $value, &$node, $control_name, $allowmultiple = false)
	{
		$class = ( $this->element['class'] ? 'class="'.$this->element['class'].'"' : 'class="inputbox"' );

		$options = array ();
		foreach ($this->element->children() as $option)
		{
			if ((int) $option['archive']) continue;
			$val	= (string) $option["value"];
			$text	= (string) $option;
			// Joomla translation splits strings with commas in them
			if (strpos($text, ",")===false){
				$options[] = JHTML::_('select.option', $val, JText::_($text));
			}
			else {
				$options[] = JHTML::_('select.option', $val, $text);
			}

		}

		if ($allowmultiple && $this->attribute('multifilter',0)==1){
			if ($value != ""){
				$value = explode(",",$value);
				// these do not have to be integer values ??
				JArrayHelper::toInteger($value);
			}
			else {
				$value = array();
			}

			$size =  ( $this->attribute('filtersize') ? ' size="'.$this->attribute('filtersize').'"' : '' );
			$multiple = ' multiple="multiple"';
			return JHTML::_('select.genericlist',  $options, ''.$control_name.$name."[]", $class.$size.$multiple, 'value', 'text', $value, $control_name.$name);
		}
		else {
			return JHTML::_('select.genericlist',  $options, ''.$control_name.$name, $class, 'value', 'text', $value, $control_name.$name);
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
			foreach ($this->element->children() as $option)
			{
				// Only add <option /> and <optgroup> elements.
				if ($option->getName() == 'optgroup')
				{
					foreach ($option->children() as $ogoption)
					{
						$val	= (string) $ogoption['value'];
						$text	= (string) $ogoption;
						if (JText::_($text)!=$text){
							$text = JText::_($text);
						}
						$values[$this->attribute('name')][$val] = $text;
					}

				}
				else {
					$val	= (string) $option['value'];
					$text	= (string) $option;
					if (JText::_($text)!=$text){
						$text = JText::_($text);
					}
					$values[$this->attribute('name')][$val] = $text;
				}
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
			$this->filterNullValue = array($this->filterNullValue );
			if (intval(JRequest::getVar('filter_reset',0))){
				JFactory::getApplication()->setUserState( $this->filterType.'_fv_ses', $this->filterNullValue );
				$this->filter_value = $this->filterNullValue;
				if (is_string($this->filter_value)){
					$this->filter_value=  array($this->filter_value);
				}
			}
			// ALSO if this filter is not visible on the page then should not use filter value - does this supersede the previous condition ???
			else if (!$this->visible) 
			{
				$this->filter_value =  JRequest::getVar($this->filterType.'_fv', $this->filterNullValue,"request", "array" );
				if (is_string($this->filter_value)){
					$this->filter_value=  array($this->filter_value);
				}
				// These do not have to be integer values ??
				JArrayHelper::toInteger($this->filter_value);
			}
			else {
				$this->filter_value = JFactory::getApplication()->getUserStateFromRequest( $this->filterType.'_fv_ses', $this->filterType.'_fv', $this->filterNullValue );
				if (is_string($this->filter_value)){
					$this->filter_value=  array($this->filter_value);
				}
				// These do not have to be integer values ??
				JArrayHelper::toInteger($this->filter_value);
			}
		}
		else {
			if (intval(JRequest::getVar('filter_reset',0))){
				JFactory::getApplication()->setUserState( $this->filterType.'_fv_ses', $this->filterNullValue );
				$this->filter_value = $this->filterNullValue;
			}
			// ALSO if this filter is not visible on the page then should not use filter value - does this supersede the previous condition ???
			else if (!$this->visible )
			{
				// These do not have to be integer values ??
				$this->filter_value =  JRequest::getVar($this->filterType.'_fv', $this->filterNullValue,"request", "int" );
				//$this->filter_value =  JRequest::getVar($this->filterType.'_fv', $this->filterNullValue,"request", "string" );
			}
			else {
				$this->filter_value = JFactory::getApplication()->getUserStateFromRequest( $this->filterType.'_fv_ses', $this->filterType.'_fv', $this->filterNullValue );
			}
			// These do not have to be integer values ??
			$this->filter_value = intval($this->filter_value );
			//$this->filter_value = trim($this->filter_value );
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
		$filterList["title"]="<label class='evdate_label' for='".$this->filterType."_fv'>".JText::_($this->filterLabel)."</label>";
		if ($this->attribute("multifilter")==1){
			// allow multiple select!
			$filterList["html"] =  $this->fetchElement($this->filterType."_fv", implode(",",$this->filter_value), $this->node, "", true);
			$script = "try {JeventsFilters.filters.push({id:'".$this->filterType."_fv',value:".$this->filterNullValue[0] ."});} catch (e) {}";
		}
		else {
			$filterList["html"] =  $this->fetchElement($this->filterType."_fv", $this->filter_value, $this->node, "");
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
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   11.1
	 */
	function getOptions()
	{
		// Initialize variables.
		$options = array();

		foreach ($this->element->children() as $option)
		{
			if ($option['archived'] || $option['archive']) {
				continue;
			}

			// Only add <option /> and <optgroup> elements.
			if ($option->getName() == 'optgroup')
			{

				if (strpos((string)$option['label'], ",")===false){
					$label = JText::alt(trim((string) $option['label']), preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname));
				}
				else {
					$label = trim((string) $option['label']);
				}
				$tmp = JHtml::_(	'select.optgroup', $label, 'value', 'text');

				// Set some option attributes.
				$tmp->class = (string) $option['class'];

				// Set some JavaScript option attributes.
				$tmp->onclick = (string) $option['onclick'];

				// Add the option object to the result set.
				$options[] = $tmp;

				foreach ($option->children() as $ogoption)
				{
					if ($ogoption['archived'] || $ogoption['archive'] || $ogoption->getName() != 'option') {
						continue;
					}
					// Create a new option object based on the <option /> element.
					// Joomla translation splits strings with commas in them
					if (strpos((string)$ogoption, ",")===false){
						$tmp = JHtml::_(
							'select.option', (string) $ogoption['value'],
							JText::alt(trim((string) $ogoption), preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)), 'value', 'text',
							((string) $ogoption['disabled'] == 'true')
						);
					}
					else {
						$tmp = JHtml::_(
							'select.option', (string) $ogoption['value'],
							trim((string) $ogoption), 'value', 'text',
							((string) $ogoption['disabled'] == 'true')
						);
					}
					// Set some option attributes.
					$tmp->class = (string) $ogoption['class'];

					// Set some JavaScript option attributes.
					$tmp->onclick = (string) $ogoption['onclick'];

					// Add the option object to the result set.
					$options[] = $tmp;
				}
				// close the optgroup
				$tmp = JHtml::_(	'select.optgroup', $label, 'value', 'text');
				// Add the option object to the result set.
				$options[] = $tmp;
				continue;
			}
			else if ($option->getName() != 'option')
			{
				continue;
			}
			else {

				// Create a new option object based on the <option /> element.
				// Joomla translation splits strings with commas in them
				if (strpos((string)$option, ",")===false){
					$tmp = JHtml::_(
						'select.option', (string) $option['value'],
						JText::alt(trim((string) $option), preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)), 'value', 'text',
						((string) $option['disabled'] == 'true')
					);
				}
				else {
					$tmp = JHtml::_(
						'select.option', (string) $option['value'],
						trim((string) $option), 'value', 'text',
						((string) $option['disabled'] == 'true')
					);
				}
			}
			// Set some option attributes.
			$tmp->class = (string) $option['class'];

			// Set some JavaScript option attributes.
			$tmp->onclick = (string) $option['onclick'];

			// Add the option object to the result set.
			$options[] = $tmp;
		}

		reset($options);

		return $options;
	}

	public function publicGetOptions(){
		return $this->getOptions();
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