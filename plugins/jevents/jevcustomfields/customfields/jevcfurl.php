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

class JFormFieldJevcfurl extends JFormFieldText
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevcfurl';

	function getInput() {

		// Initialize some field attributes.
		$size = $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
		$maxLength = $this->element['maxlength'] ? ' maxlength="' . (int) $this->element['maxlength'] . '"' : '';
		$class = $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$readonly = ((string) $this->element['readonly'] == 'true') ? ' readonly="readonly"' : '';
		$disabled = ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';

		$placeholder = ( $this->attribute('placeholder') ? ' placeholder="'.$this->attribute('placeholder').'"' : '' );


		// Initialize JavaScript field attributes.
		$onchange = $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';

		return '<input type="text" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
			. htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' . $class . $size . $disabled . $readonly . $onchange . $maxLength . $placeholder. '/>';

	}

	function fetchRequiredScript($name, &$node, $control_name)
	{
		return "JevrRequiredFields.fields.push({'name':'" . $control_name . $name . "', 'default' :'" . $this->attribute('default') . "' ,'reqmsg':'" . trim(JText::_($this->attribute('requiredmessage'), true)) . "'}); ";

	}

	public function constructFilter($node)
	{
		$this->node = $node;
		$this->filterType = str_replace(" ", "", $this->attribute("name"));
		$this->filterLabel = is_null($this->attribute("filterlabel")) ? $this->attribute("label") : $this->attribute("filterlabel");
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
			$this->filter_value = JRequest::getVar($this->filterType . '_fv', $this->filterNullValue, "request", "string");
		}
		else
		{
			$this->filter_value = JFactory::getApplication()->getUserStateFromRequest($this->filterType . '_fv_ses', $this->filterType . '_fv', $this->filterNullValue, "string");
		}

	}

	public function createJoinFilter()
	{
		if (is_string($this->filter_value) && trim($this->filter_value) == $this->filterNullValue)
			return "";
		$join = " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id";
		$db = JFactory::getDBO();
		$filter =  "$this->map.name=" . $db->Quote($this->filterType) . " AND $this->map.value LIKE (" . $db->Quote($this->filter_value . "%") . ")";
		return $join . " AND ". $filter;
	}

	public function createFilter()
	{
		if (is_string($this->filter_value) && trim($this->filter_value) == $this->filterNullValue)
			return "";
		return "$this->map.id IS NOT NULL";
	}

	public function createFilterHTML()
	{
		$filterList = array();
		$filterList["title"] = "<label class='evdate_label' for='" . $this->filterType . "_fv'>" . $this->filterLabel . "</label>";
		$filterList["html"] = $this->fetchElement($this->filterType . "_fv", $this->filter_value, $this->node, "");

		$script = "try {JeventsFilters.filters.push({id:'" . $this->filterType . "_fv',value:'" . addslashes($this->filterNullValue) . "'});} catch (e) {}";
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);

		return $filterList;

	}

	public function convertValue($value, &$node)
	{
		$target = $this->attribute('target') ? " target='" . $this->attribute('target') . "' " : "";
		$linktext = $this->attribute('linktext') ? $this->attribute('linktext') : $value;

		if ($value != "")
		{
			$hv = $this->attribute("hiddenvalue");
			if (strpos($hv, "<a href=") !== 0 && strpos($hv, "http") === 0)
			{
				$node->element->attributes()->hiddenvalue = "<a href='$hv' $target >$hv</a>";
			}
			if (!is_string($value)){
				$x = 1;
			}
			if (strpos($value, "http://") === false && strpos($value, "https://") === false && strpos($value, "ftp://") === false && strpos($value, "mailto:") === false)
			{
				$value = "http://" . $value;
			}

			// redirect ONLY if viewing event detail 
			if ($this->attribute("redirect")==1)
			{
				if (JRequest::getString("jevtask") == "icalrepeat.detail" || JRequest::getString("jevtask") == "icalevent.detail")
				{
					JFactory::getApplication()->redirect($value);
				}
			}

			return "<a href='$value' $target>$linktext</a>";
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