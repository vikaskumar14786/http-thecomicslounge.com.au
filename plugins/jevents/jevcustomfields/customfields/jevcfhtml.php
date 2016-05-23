<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevhtml.php 1569 2009-09-16 06:22:03Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('editor');

class JFormFieldJevcfhtml extends JFormFieldEditor
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevcfhtml';

	function fetchElement($name, $value, &$node, $control_name)
	{

		$rows = $this->element['rows'];
		$cols = $this->element['cols'];
		$class = ( $this->element['class'] ? 'class="'.$this->element['class'].'"' : 'class="text_area"' );
		$value = str_replace('\n', "", $value);

		$editor  = JFactory::getEditor( null);
		$buttons = false;
		
		$height = ((string) $this->attribute('width')) ? (string) $this->attribute('width') : '250';
		$width   = ((string) $this->attribute('height')) ? (string) $this->attribute('height') : '600';
		
		if (JevJoomlaVersion::isCompatible(1.6)){
			$html =  $editor->display($control_name.$name, $value , $width, $height, $cols, $rows, $buttons , $control_name.$name);
		}
		else {
			$html =  $editor->display($control_name.$name, $value , $width, $height, $cols, $rows, $buttons );
		}		
		return $html;
	}
	
	function fetchRequiredScript($name, &$node, $control_name) 
	{
		return "JevrRequiredFields.fields.push({'name':'".$control_name.$name."', 'default' :'".$this->attribute('default') ."' ,'reqmsg':'".trim(JText::_($this->attribute('requiredmessage'),true))."'}); ";
	}

	public function convertValue($value, $node)
	{
		// remove hard coded \n in the text
		return str_replace('\n',"",$value);
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
		if (intval(JRequest::getVar('filter_reset',0))){
			$this->filter_value = JFactory::getApplication()->setUserState( $this->filterType.'_fv_ses', $this->filterNullValue );
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
		if (trim($this->filter_value)==$this->filterNullValue) return "";
		$join =  " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id";
		$db = JFactory::getDBO();
		$filter =  "$this->map.name=".$db->Quote($this->filterType)." AND $this->map.value LIKE (".$db->Quote($this->filter_value."%").")";
		
		return $join . " AND ". $filter;
	}
	
	public function createFilter(){
		if (trim($this->filter_value)==$this->filterNullValue) return "";
		return "$this->map.id IS NOT NULL";
	}
	
	public function setSearchKeywords( &$extrajoin ){
		if ( $this->attribute('searchable')){
			$db = JFactory::getDBO();
			if (strpos($extrajoin, " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id")===false){
				$extrajoin .= "\nLEFT JOIN #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id AND $this->map.name=".$db->Quote($this->filterType);
			}
			if ($this->element->attributes()->fullsearch){			
				return "$this->map.value LIKE (".$db->Quote("%".'###'."%").")";
			}
			else {
				return "$this->map.value LIKE (".$db->Quote('###'."%").")";
			}
		}
	}
	
	function fetchFilterElement($name, $value, &$node, $control_name)
	{
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
