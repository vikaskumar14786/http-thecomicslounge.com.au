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
JFormHelper::loadFieldClass('checkboxes');

class JFormFieldJevcfcheckbox extends JFormFieldCheckboxes
{
	function getInput()
	{
	
		if (strpos($this->element['class']," checkbox")===false){
			$this->element['class'] .= " checkbox";
		}
		
		$class = ( $this->element['class'] ? 'class="'.$this->element['class'].'"' : '' );
		
		$inputoptions = $this->getOptions();
		
		if ($this->value != ""){
			$this->value = explode(",",$this->value);
			JArrayHelper::toInteger($this->value);
		}
		else {
			if (count ($inputoptions)==0){
				$this->value = array($this->element['default']);
			}
			else {
				$this->value = array();
				foreach ($inputoptions as $opt)
				{
					if ($opt->archive) continue;
					if ($opt->default==1){
						$this->value[] = $opt->value;
					}
				}
			}
		}
		
		$options = array ();
		if (count ($inputoptions)==0){
			$option = new stdClass();
			$option->label = str_replace(" *", "", strip_tags($this->getLabel()));
			$option->value = 1;
			$option->checked = in_array($option->value, $this->value) ? 'checked="checked"':'';
			$option->disabled = "";
			$option->class = "";
			$options[] = $option;
		}
		else {
			foreach ($inputoptions as $opt)
			{
				if ($opt->archive) continue;
				$option = new stdClass();
				$option->label = JText::_((string)$opt->text);
				$option->value = intval($opt->value);
				
				$option->checked = (in_array((string) $opt->value, (array) $this->value) ? ' checked="checked"' : '');
				$option->class = !empty($opt->class) ? ' class="' . $opt->class . '"' : '';
				$option->disabled = !empty($op->disable) ? ' disabled="disabled"' : '';
				
				$options[] = $option;
			}
		}
		
		// Jform auto includes the [] in this element name - but we want to specify an array index!
		$name = str_replace("[]","",$this->name);
		$html = "<input type='hidden'  name='".$name."[-1]' value='-1' />";
		foreach ($options as $option){
			if ($option->label != ""){
				$html .= "<label for='$this->id"."_$option->value' class='checkbox btn '>".$option->label;
			}
			$html .= "<input type='checkbox'  $class $option->checked $option->disabled $option->class  name='".$name."[$option->value]' value='$option->value' id='$this->id"."_$option->value'  />";
			if ($option->label != ""){
				$html .= "</label >";
			}
		}
		
		$html = '<div class="checkbox btn-group ">'.$html.'</div>';
		return $html;
	}
	
	public function getOptions()
	{
		$options = array();

		foreach ($this->element->children() as $option)
		{

			// Only add <option /> elements.
			if ($option->getName() != 'option')
			{
				continue;
			}

			// Create a new option object based on the <option /> element.
			$tmp = JHtml::_(
				'select.option', (string) $option['value'], trim((string) $option), 'value', 'text',
				((string) $option['disabled'] == 'true')
			);

			// Set some option attributes.
			$tmp->class = (string) $option['class'];

			// Set some JavaScript option attributes.
			$tmp->onclick = (string) $option['onclick'];

			// Set some JEvents specific fields
			$tmp->archive = (int) $option['archive'];
			$tmp->default = (int) $option['default'];
			
			// Add the option object to the result set.
			$options[] = $tmp;
		}

		reset($options);

		return $options;
	}	

	public function convertValue($value, $node){
		static $values;
		if (!isset($values)){
			$values =  array();
		}
		if (!isset($values[$this->attribute('name')])){
			$values[$this->attribute('name')]=array();
			if (count($this->element->children())>0){
				foreach ($this->element->children() as $key=>$option)
				{
					$val	= (string) $option["value"];
					$text	= (string) $option;
					$values[$this->attribute('name')][$val] = JText::_($text);
				}
			} 
			else {
				//$val	= -1;	$values[$this->attribute('name')][$val] = JText::_("JEV_NO") ;
				$val	= 0;
				$values[$this->attribute('name')][$val] = JText::_("JEV_NO") ;
				$val	= 1;
				$values[$this->attribute('name')][$val] = JText::_("JEV_YES") ;
			}
		}
		
		$eventvalues = explode(",",$value);
		$html = array();
		foreach ($eventvalues as $val){
			if (array_key_exists($val,$values[$this->attribute('name')])){
				$html[] =  $values[$this->attribute('name')][$val];
			}

		}
		return implode(", ", $html);
	}

	function fetchRequiredScript($name, &$node, $control_name)
	{
		$script =  "JevrRequiredFields.fields.push({'name':'".$control_name.$name."', 'default' :'".$this->attribute('default') ."' ,'reqmsg':'".trim(JText::_($this->attribute('requiredmessage'),true))."'}); ";
		return $script ;
	}


	/*
	public function constructFilter($node){
		$this->node = $node;
		$this->filterType = str_replace(" ","",$this->attribute("name"));
		$this->filterLabel = is_null($this->attribute("filterlabel"))?$this->attribute("label"):$this->attribute("filterlabel");
		// implement filter default value at a later date for checkboxes - its not trivial
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
		
		//$this->filter_value = JRequest::getInt($this->filterType.'_fv', $this->filterNullValue );
}

	public function createJoinFilter(){
		if (trim($this->filter_value)==$this->filterNullValue) return "";
		$join =  " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id";
		$db = JFactory::getDBO();
		$filter =  "$this->map.name=".$db->Quote($this->filterType)." AND $this->map.value=".$db->Quote($this->filter_value);
		return $join . " AND ". $filter;
	}

	public function createFilter(){
		if (trim($this->filter_value)==$this->filterNullValue) return "";
		return "$this->map.id IS NOT NULL";
	}

	public function createFilterHTML(){
		$filterList=array();
		$filterList["title"]="<label class='evdate_label' for='".$this->filterType."_fv'>".JText::_($this->filterLabel)."</label>";
		$name = $this->filterType."_fv";
		$filterList["html"] =  $this->fetchElement($name, $this->filter_value, $this->node, "");

		$name .= $this->filterNullValue;
		$script = "function reset".$this->filterType."_fv(){\$('$name').checked=true;};\n";
		$script .= "try {JeventsFilters.filters.push({action:'reset".$this->filterType."_fv()',id:'".$this->filterType."_fv',value:".$this->filterNullValue."});} catch (e) {}";
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);

		return $filterList;
	}
	 */


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