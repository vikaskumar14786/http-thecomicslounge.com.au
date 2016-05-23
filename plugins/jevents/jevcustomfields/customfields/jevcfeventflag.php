<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevtextarea.php 1569 2009-09-16 06:22:03Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * This class handles users adding events to lists e.g. list of favourite events or to a planner
 */
class JFormFieldJevcfeventflag extends JFormField
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevcfeventflag';

	function getInput()
	{
		$name = $this->name;
		$value = $this->value;
		if (JRequest::getCmd("option") == "com_menus" || JRequest::getCmd("option") == "com_modules")
		{
			$class = ( $this->element['class'] ? 'class="' . $this->element['class'] . '"' : '' );

			// Must load admin language files
			$lang = JFactory::getLanguage();
			$lang->load("com_jevents", JPATH_ADMINISTRATOR);

			$options = array();
			//$options[] = JHTML::_('select.option', -1, JText::_("JEV_IGNORE"));
			$options[] = JHTML::_('select.option', 0, JText::_("JEV_No"));
			$options[] = JHTML::_('select.option', 1, JText::_("JEV_Yes"));

			return JHTML::_('select.genericlist', $options, $this->name, $class, 'value', 'text', $value, $this->id);
		}
		return "";
	}

	protected function getLabel()
	{
		if (JRequest::getCmd("option") == "com_menus" || JRequest::getCmd("option") == "com_modules")
		{
			return parent::getLabel();
		}
		else {
			return "";
		}
	}

	public function convertValue($value, &$node)
	{
		$user = JFactory::getUser();
		if ($user->get('id') == 0)
		{
			$node->element->attributes()->label = "";
			// set access to something impossible too!
			$node->element->attributes()->access = -999;
			return "";
		}

		$separaterepeats=intval($this->attribute("separaterepeats"));
		
		if (is_null($this->event) || !is_a($this->event, "jIcalEventRepeat" )  || JRequest::getCmd('task') == 'defaults.edit')
		{
			return "";
		}
				
		// For lists we would have a separator or 2 fields for this
		$value = intval($value);

		$root = JURI::root(false);

		$fieldname = $this->attribute("name");
		$activeimage = $this->attribute('activeimage');
		$inactiveimage = $this->attribute('inactiveimage');
		$addimage = $this->attribute('addimage');
		$removeimage = $this->attribute('removeimage');
		if (strpos($activeimage, 'http:') === false)
		{
			$activeimage = $root . $activeimage;
		}

		if (strpos($inactiveimage, "http:") === false)
		{
			$inactiveimage = $root . $inactiveimage;
		}
		if (strpos($addimage, "http:") === false)
		{
			$addimage = $root . $addimage;
		}
		if (strpos($removeimage, "http:") === false)
		{
			$removeimage = $root . $removeimage;
		}

		$hovermessage = $this->attribute('hovermessage');

		$class = ( $this->element['class'] ? 'class="' . $this->element['class'] . '"' : '' );
		$buttonlabel = $this->attribute("buttonlabel");
		$append = intval($this->attribute("append"));
		$separaterepeats=intval($this->attribute("separaterepeats"));

		JHtml::_('behavior.tooltip');

		$user = JFactory::getUser();
		if ($separaterepeats) {
			$value = ($value == $node->event->rp_id()) ? 1 : 0;
		}
		$currentimage = $value > 0 ? $activeimage : $inactiveimage;
		$overimage = $value > 0 ? $removeimage : $addimage;
		$rpid = $this->event->rp_id();
		
		static $scriptloaded;
		if (!isset($scriptloaded))
		{
			$scriptloaded = true;
			if (version_compare(JVERSION, "1.6.0", 'ge'))
			{
				$url = JURI::root() . "plugins/jevents/jevcustomfields/customfields/updateeventflag.php";
			}
			else
			{
				$url = JURI::root() . "plugins/jevents/customfields/updateeventflag.php";
			}
			$updatedmessage = $this->attribute("updatedmessage");
					
			$doc = JFactory::getDocument();
			$script = <<<SCRIPT

var updatingPlannerImage = false;

function updatePlannerImage(fieldname, rpid){
	if (updatingPlannerImage) {
		return true;
	}
	var planner = \$('jecfeventflag_'+fieldname+'_'+rpid);
	if (planner.src=='$activeimage'){
		planner.src='$removeimage';
	}
	else if (planner.src=='$inactiveimage'){
		planner.src='$addimage';
	}
	else if (planner.src=='$removeimage' ){
		planner.src='$activeimage';
	}
	else if (planner.src=='$addimage'){
		planner.src='$inactiveimage';
	}
	else if (planner.src=='$addimage'){
		planner.src='$inactiveimage';
	}
}
			
function updateEventFlag(fieldname, userid, rpid){
	updatingPlannerImage = true;
	action = \$('jecfeventflag_'+fieldname+'_value'+'_'+$rpid).value;
	var requestObject = new Object();
	requestObject.error = false;
	requestObject.field = fieldname;
	requestObject.task = "updateEventFlag";
	requestObject.value = action;
	requestObject.userid = userid;
	requestObject.rpid = rpid;
	requestObject.separaterepeats= $separaterepeats;

	var jSonRequest = new Request.JSON({
		'url':'$url',
		onSuccess: function(json, responsetext){
			if (!json){
				alert('Update Failed');
			}
			if (json.error){
				try {
					eval(json.error);
				}
				catch (e){
					alert('could not process error handler');
				}
			}
			else {
				if ( json.newvalue){
					$('jecfeventflag_'+fieldname+'_'+rpid).src = '$activeimage';
				}
				else {
					$('jecfeventflag_'+fieldname+'_'+rpid).src = '$inactiveimage';
				}
				$('jecfeventflag_'+fieldname+'_value'+'_'+rpid).value = json.newvalue;
				if ('$updatedmessage'!=''){
					alert('$updatedmessage');
				}
			}
			updatingPlannerImage = false;
		},
		onFailure: function(x){
			alert('Something went wrong... '+x )
			updatingPlannerImage = false;
		}
	}).get({'json':JSON.encode(requestObject)});
	
}
SCRIPT;
			$doc->addScriptDeclaration($script);
		}

		$html = "";
		$img = '<img  ' . $class . ' alt="' . htmlspecialchars($hovermessage, ENT_COMPAT, 'UTF-8') . '"  src="' . $currentimage . '"  id="jecfeventflag_' . $fieldname . '_' . $rpid . '" onclick="updateEventFlag(\'' . $fieldname . '\', ' . $user->id . ', ' . $rpid . ');return false;" onmouseover="updatePlannerImage(\'' . $fieldname . '\', ' . $rpid . ')" onmouseout="updatePlannerImage(\'' . $fieldname . '\', ' . $rpid . ')" style="pointer:cursor" />';
		if ($hovermessage)
		{
			$html .= '<span class="hasTip" title="' . htmlspecialchars($hovermessage, ENT_COMPAT, 'UTF-8') . '" >' . $img . '</span>';
		}
		else
		{
			$html .= $img;
		}

		$html .= '<input type="hidden" id="jecfeventflag_' . $fieldname . '_value_' . $rpid . '" value="' . $value . '"/>';

		return $html;

	}

	function fetchRequiredScript($name, &$node, $control_name)
	{
		return "";

	}

	public function constructFilter($node)
	{

		$user = JFactory::getUser();
		if ($user->id == 0)
			return "";

		$this->node = $node;
		$this->filterType = str_replace(" ", "", $this->attribute("name"));
		$this->filterLabel = is_null($this->attribute("filterlabel")) ? $this->attribute("label") : $this->attribute("filterlabel");
		$this->filterNullValue = is_null($this->attribute("filterdefault"))?(is_null($this->attribute("default"))?"":$this->attribute("default")):$this->attribute("filterdefault");
		$this->filter_value = $this->filterNullValue;
		$this->map = "csf" . $this->filterType;

		$this->separaterepeats = intval($this->attribute("separaterepeats"));

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

		$user = JFactory::getUser();
		if ($user->id == 0)
			return "";

		if (trim($this->filter_value) == $this->filterNullValue)
			return "";
		$join = " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id";

		$db = JFactory::getDBO();
		if ($this->separaterepeats) {
			$filter = "$this->map.name=" . $db->Quote($this->filterType) . " AND $this->map.value = rpt.rp_id AND $this->map.user_id =" . $user->id;
		}
		else {
			$filter = "$this->map.name=" . $db->Quote($this->filterType) . " AND $this->map.value =1 AND $this->map.user_id =" . $user->id;
		}
		return $join . " AND " . $filter;

	}

	public function createFilter()
	{

		$user = JFactory::getUser();
		if ($user->id == 0)
			return "";

		if (trim($this->filter_value) == $this->filterNullValue)
			return "";
		return "$this->map.id IS NOT NULL";

	}

	function fetchFilterElement($name, $value, &$node, $control_name)
	{

		$user = JFactory::getUser();
		if ($user->id == 0)
			return "";

		$size = ( $this->attribute('size') ? 'size="' . $this->attribute('size') . '"' : '' );
		$class = ( $this->element['class'] ? 'class="' . $this->element['class'] . '"' : 'class="text_area"' );
		/*
		 * Required to avoid a cycle of encoding &
		 * html_entity_decode was used in place of htmlspecialchars_decode because
		 * htmlspecialchars_decode is not compatible with PHP 4
		 */
		$value = intval($value);

		$checked = $value ? " checked='checked' " : "";

		if ($value == 0)
		{
			$res = '<input type="checkbox" value="1" ' . $class . ' ' . $size . ' ' . $checked . ' onclick="$(\'hiddenevflag\').value=this.checked?1:0;submit(this.form)" />';
			$res .= '<input type="hidden" name="' . $control_name . $name . '" id="hiddenevflag" value="' . $value . '"/>';
		}
		else
		{
			$res = '<input type="hidden" name="' . $control_name . $name . '" id="hiddenevflag" value="' . $value . '"/>';
			$res.= "<span class='filtervalue' onmousedown='\$(\"hiddenevflag\").value=0;document.jeventspost.submit();'>Yes</span>";
		}

		return $res;

	}

	public function createFilterHTML()
	{

		$user = JFactory::getUser();
		if ($user->id == 0)
			return "";

		$filterList = array();
		$filterList["title"] = "<label class='evdate_label' for='" . $this->filterType . "_fv'>" . $this->filterLabel . "</label>";
		$filterList["html"] = $this->fetchFilterElement($this->filterType . "_fv", $this->filter_value, $this->node, "");

		$script = "try {JeventsFilters.filters.push({id:'" . $this->filterType . "_fv',value:'" . addslashes($this->filterNullValue) . "'});} catch (e) {}";
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