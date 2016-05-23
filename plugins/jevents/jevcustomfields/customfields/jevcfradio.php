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
JFormHelper::loadFieldClass('radio');

class JFormFieldJevcfradio extends JFormFieldRadio
{
	function fetchElement($name, $value, &$node, $control_name, $allowmultiple = false)
	{
		$class = ( $this->element['class'] ? 'class="'.$this->element['class'].' btn-group"' : 'class=" btn-group"');
		$options = array ();
		$options = $this->getOptions();

		// remove archive elements if appropriate
		foreach ($options as $index => $option){
			if (isset($option->archive) && $option->archive) {
				unset($options[$index]);
			}
		}
		$options = array_values($options);

		if ($allowmultiple && $this->attribute('multifilter',0)==1){
			if ($value != ""){
				$value = explode(",",$value);
				JArrayHelper::toInteger($value);
			}
			else {
				$value = array();
			}
			
			$html = "<input type='hidden'  name='".$control_name.$name."[-1]' value='-1' />";
			foreach ($options as $option){
				$option->checked = (in_array($option->value, $value)) ? "checked='checked'":"";
				$optiontext = $option->text;
				if ($optiontext != ""){
					$html .= "<label for='$control_name".$name."_$option->value' id='$control_name".$name."$option->value-lbl' >";
				}
				$html .= "<input type='checkbox'  $class $option->checked name='".$control_name.$name."[$option->value]' value='$option->value' id='$control_name".$name."_$option->value'  />";
				if ($optiontext != ""){
					$html .= "<span>".$optiontext. "</span></label >";
				}
			}
			return $html;
			
			$multiple = ' multiple="multiple"';
			$size =  ( $this->attribute('filtersize') ? ' size="'.$this->attribute('filtersize').'"' : '' );
			return JHTML::_('select.genericlist',  $options, ''.$control_name.$name."[]", $class.$size.$multiple, 'value', 'text', $value, $control_name.$name);
		}
		else {
			return JHTML::_('select.radiolist', $options, ''.$control_name.$name, $class, 'value', 'text', $value, $control_name.$name );
		}
	}

	/**
	 * Method to get the radio button field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);

		if ($params->get("bootstrapchosen", 1) && strpos($this->element['class'],"btn-group")===false){
			$this->element['class'] .= " btn-group";
		}
		if (!$params->get("bootstrapchosen", 1) && strpos($this->element['class'],"btn-group")!==false){
			$this->element['class'] = str_replace("btn-group", "",$this->element['class']);
		}

		$html = array();

		// Initialize some field attributes.
		$class = $this->element['class'] ? ' class="radio ' . (string) $this->element['class'] . '"' : ' class="radio"';

		// Start the radio field output.
		$html[] = '<fieldset id="' . $this->id . '"' . $class . '>';

		// Get the field options.
		$options = $this->getOptions();

		// remove archive elements if appropriate
		foreach ($options as $index => $option){
			if (isset($option->archive) && $option->archive && ((string) $option->value != (string) $this->value)) {
				unset($options[$index]);
			}
		}

		// Build the radio field output.
		foreach ($options as $i => $option)
		{

			// Initialize some option attributes.
			$checked = ((string) $option->value == (string) $this->value) ? ' checked="checked"' : '';
			$class = !empty($option->class) ? ' class="' . $option->class . '"' : '';
			$disabled = !empty($option->disable) ? ' disabled="disabled"' : '';
			$required = !empty($option->required) ? ' required="required" aria-required="true"' : '';

			// Set some JEvents specific fields
			//$tmp->archive = (int) $option['archive'];
			//$tmp->default = (int) $option['default'];

			// Initialize some JavaScript option attributes.
			$onclick = !empty($option->onclick) ? ' onclick="' . $option->onclick . '"' : '';

			$temp = '<input type="radio" id="' . $this->id . $i . '" name="' . $this->name . '" value="'
				. htmlspecialchars($option->value, ENT_COMPAT, 'UTF-8') . '"' . $checked . $class . $onclick . $disabled . $required . '/>';

			$html[] = '<label for="' . $this->id . $i . '"' . $class . '>'
				. JText::alt($option->text, preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)) . $temp. '</label>';
		}

		// End the radio field output.
		$html[] = '</fieldset>';

		return implode($html);
	}

	public function getOptions()
	{
		// Initialize variables.
		$options = array();

		foreach ($this->element->children() as $option)
		{

			if ((int) $option['default']) {
				continue;
			}
			
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
			$tmp->class = (string) $option['class']. " btn radio";

			// Set some JavaScript option attributes.
			$tmp->onclick = (string) $option['onclick'];

			// Set some JavaScript option attributes.
			$tmp->archive = @intval ($option['archive']);

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
			foreach ($this->element->children() as $option)
			{
				$val	= (string) $option['value'];
				$text	= (string) $option;
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

	function fetchRequiredScript($name, &$node, $control_name)
	{
		$script =  "JevrRequiredFields.fields.push({'name':'".$control_name.$name."', 'default' :'".$this->attribute('default') ."' ,'reqmsg':'".trim(JText::_($this->attribute('requiredmessage'),true))."'}); ";
		return $script ;
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
				JArrayHelper::toInteger($this->filter_value);
			}
			else {
				$this->filter_value = JFactory::getApplication()->getUserStateFromRequest( $this->filterType.'_fv_ses', $this->filterType.'_fv', $this->filterNullValue );
				if (is_string($this->filter_value)){
					$this->filter_value=  array($this->filter_value);
				}
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
			if ($this->filter_value==array(-1=>-1)) return "";
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
			if ($this->filter_value==array(-1=>-1)) return "";
			if (count($this->filter_value)==0) return "";
		}
		else {
			if (trim($this->filter_value)==$this->filterNullValue) return "";
		}
		return "$this->map.id IS NOT NULL";
	}

	public function createFilterHTML(){
		$filterList=array();
		$filterList["title"]="<label class='evdate_label' for='".$this->filterType."_fv'>".JText::_($this->filterLabel)."</label>";
		if ($this->attribute("multifilter")==1){
			// allow multiple select!
			$name = $this->filterType;
			$filterList["html"] =  $this->fetchElement($name, implode(",",$this->filter_value), $this->node, "", true);
			$script = "function reset".$this->filterType."_fv(){
						for (i=0;i<100;i++){
							if (\$('$name'+'_'+i)){
								\$('$name'+'_'+i).checked=false;
								//alert(\$('$name'+'_'+i).value+' vs'+".$this->filterNullValue.");
								//if (\$('$name'+'_'+i).value == ".$this->filterNullValue."){
								//	\$('$name'+'_'+i).checked=true;
								//}
							}
							else {
								break;
							}
						}
					};\n";
			$script .= "try {JeventsFilters.filters.push({action:'reset".$this->filterType."_fv()',id:'".$this->filterType."_fv',value:".$this->filterNullValue."});} catch (e) {}";			
		}
		else {
			$name = $this->filterType."_fv";
			$filterList["html"] =  $this->fetchElement($name, $this->filter_value, $this->node, "");
			$name .= $this->filterNullValue;
			$script = "function reset".$this->filterType."_fv(){\$('$name').checked=true;};\n";
			$script .= "try {JeventsFilters.filters.push({action:'reset".$this->filterType."_fv()',id:'".$this->filterType."_fv',value:".$this->filterNullValue."});} catch (e) {}";
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