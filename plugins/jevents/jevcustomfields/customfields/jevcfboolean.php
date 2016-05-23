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

include_once("jevcfradio.php");
class JFormFieldJevcfboolean extends JFormFieldJevcfradio //JFormFieldRadio
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevcfboolean';

	public function getInput() {
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		if ($params->get("bootstrapchosen", 1) && strpos($this->element['class'],"btn-group")===false){
			$this->element['class'] .= " btn-group";
		}
		if (!$params->get("bootstrapchosen", 1) && strpos($this->element['class'],"btn-group")!==false){
			$this->element['class'] = str_replace("btn-group", "",$this->element['class']);
		}
		return parent::getInput();
	}
		
	public function getOptions()
	{		
		// Must load admin language files
		$lang = JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);
		
		$options = array ();
		$options[] = JHTML::_('select.option', 0, JText::_("Jev_No"));
		$options[] = JHTML::_('select.option', 1, JText::_("jev_Yes"));

		for ($i=0;$i<count($options);$i++){
			$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
			if ($params->get("bootstrapchosen", 1)){
				//$options[$i]->class="btn radio ".($options[$i]->value?"btn-success":"btn-danger");
				$options[$i]->class="btn radio";
			}
		}
		return $options;

		
	}	
	function fetchRequiredScript($name, &$node, $control_name)
	{
		return "JevrRequiredFields.fields.push({'name':'" . $control_name . $name . "', 'default' :'" . $this->attribute('default') . "' ,'reqmsg':'" . trim(JText::_($this->attribute('requiredmessage'), true)) . "'}); ";

	}

	public function convertValue($value, $node)
	{
		if (!is_null($this->attribute('hiddenvalue')) && $value==$this->attribute('hiddenvalue')) return "";
		static $values;
		if (!isset($values))
		{
			$values = array();
		}
		if (!isset($values[$this->attribute('name')]))
		{
			$values[$this->attribute('name')] = array();
			$values[$this->attribute('name')][0] = JText::_("JEV_NO");
			$values[$this->attribute('name')][1] = JText::_("JEV_YES");
		}
		return $values[$this->attribute('name')][intval($value)>0?1:0];

	}

	public function constructFilter($node)
	{
		$this->node = $node;
		$this->filterType = str_replace(" ", "", $this->attribute("name"));
		$this->filterLabel = is_null($this->attribute("filterlabel"))?$this->attribute("label"):$this->attribute("filterlabel");
		$this->filterNullValue = is_null($this->attribute("filterdefault"))?(is_null($this->attribute("default"))?"":$this->attribute("default")):$this->attribute("filterdefault");
		$this->filter_value = $this->filterNullValue;
		$this->map = "csf" . $this->filterType;

		$registry = JRegistry::getInstance("jevents");
		$this->indexedvisiblefilters = $registry->get("indexedvisiblefilters", false);
		if ($this->indexedvisiblefilters === false)
			return;

		// This is our best guess as to whether this filter is visible on this page.
		$this->visible = in_array("customfield", $this->indexedvisiblefilters);

		// If using caching should disable session filtering if not logged in
		$cfg = JEVConfig::getInstance();
		$useCache = intval($cfg->get('com_cache', 0));
		$user = JFactory::getUser();
		$mainframe = JFactory::getApplication();
		if (intval(JRequest::getVar('filter_reset', 0)))
		{
			JFactory::getApplication()->setUserState( $this->filterType.'_fv_ses', $this->filterNullValue ); 			
			$this->filter_value = $this->filterNullValue;
		}
		// ALSO if this filter is not visible on the page then should not use filter value - does this supersede the previous condition ???
		else if (!$this->visible)
		{
			$this->filter_value = JRequest::getVar($this->filterType . '_fv', $this->filterNullValue, "request", "int");
		}
		else
		{
			$this->filter_value = JFactory::getApplication()->getUserStateFromRequest($this->filterType . '_fv_ses', $this->filterType . '_fv', $this->filterNullValue);
		}
		$this->filter_value = intval($this->filter_value);

		//$this->filter_value = JRequest::getInt($this->filterType.'_fv', $this->filterNullValue );

	}

	public function createJoinFilter()
	{
		if (trim($this->filter_value) == $this->filterNullValue)
			return "";
		$join =  " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id";
		$db = JFactory::getDBO();
		$filter =  "$this->map.name=" . $db->Quote($this->filterType) . " AND $this->map.value=" . $db->Quote($this->filter_value);
		return $join . " AND ". $filter;
	}

	public function createFilter()
	{
		if (trim($this->filter_value) == $this->filterNullValue)
			return "";
		return "$this->map.id IS NOT NULL";		
	}

	public function createFilterHTML()
	{
		return parent::createFilterHTML();
		
		$filterList = array();
		$filterList["title"] = "<label class='evdate_label' for='" . $this->filterType . "_fv'>" . $this->filterLabel . "</label>";
		$name = $this->filterType ;
		
		$name = $this->node->name;
		$id = $this->node->id;
		$value = $this->node->value;
		$this->node->name = $this->filterType . "_fv";
		$this->node->id = $this->filterType . "_fv";
		$this->node->value = $this->filter_value;
		$filterList["html"] = $this->node->getInput();
		$this->node->name = $name;
		$this->node->id = $id;
		$this->node->value = $value;

		$name .= $this->filterNullValue;
		$script = "function reset" . $this->filterType . "_fv(){\$('jform_$name').checked=true;};\n";
		$script .= "try {JeventsFilters.filters.push({action:'reset" . $this->filterType . "_fv()',id:'" . $this->filterType . "_fv',value:" . $this->filterNullValue . "});} catch (e) {}";
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