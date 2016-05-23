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

class JFormFieldJevcfupdatable extends JFormField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevcfupdatable';
	var $_validuser = 0;

	function getInput()
	{

		$rows =$this->element['rows'];
		$cols = $this->element['cols'];
		$class = ( $this->element['class'] ? 'class="'.$this->element['class'].'"' : 'class="text_area"' );
		// convert <br /> tags so they are not visible when editing
		$this->value = str_replace(array("\\r\\n", "\\r", "\\n"), "\n",$this->value);
		$this->value = str_replace('<br />', "\n", $this->value);

		return '<textarea name="'.$this->name.'" cols="'.$cols.'" rows="'.$rows.'" '.$class.' id="'.$this->id.'" >'.$this->value.'</textarea>';
	}

	/*
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
	*/

	public function convertValue($value, &$node){
		$isEditor = JEVHelper::isEventEditor();

		if (!$isEditor) {
			$node->element->attributes()->label = "";
			// set access to something impossible too!
			$node->element->attributes()->access = -999;
			return "";
		}

		if (is_null($this->event) || !is_a($this->event, "jIcalEventRepeat" ) ) return;
		
		static $scriptloaded;
		if (!isset($scriptloaded)) {
			$scriptloaded = true;
			if (version_compare(JVERSION, "1.6.0", 'ge')) {
				$url = JURI::root()."plugins/jevents/jevcustomfields/customfields/updatefield.php";
			}
			else {
				$url = JURI::root()."plugins/jevents/customfields/updatefield.php";
			}
			$updatedmessage = $this->attribute("updatedmessage");
			$doc = JFactory::getDocument();
			$rpid = $this->event->rp_id();
			$script = <<<SCRIPT
function updateUpdatable(fieldname, userid, append){
	//alert($('jecfupdatable_'+fieldname).value);

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.field = fieldname;
	requestObject.task = "updateUpdatable";
	requestObject.value = $('jecfupdatable_'+fieldname).value;
	requestObject.userid = userid;
	requestObject.append = append;
	requestObject.rpid = $rpid;

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
				if (append){
					$('jecfupdatable_'+fieldname).value = '';
					$('jecfupdatablediv_'+fieldname).innerHTML = json.newvalue;
				}
				else {
					$('jecfupdatable_'+fieldname).value = json.newvalue;
				}
				alert('$updatedmessage');
			}
		},
		onFailure: function(x){
			alert('Something went wrong... '+x )
		}
	}).get({'json':JSON.encode(requestObject)});
	
}
SCRIPT;
			$doc->addScriptDeclaration($script);

}
		?>
		<?php
		$fieldname = $this->attribute("name");
		$rows = $this->element['rows'];
		$cols = $this->element['cols'];
		$class = ( $this->element['class'] ? 'class="'.$this->element['class'].'"' : 'class="text_area"' );
		$buttonlabel = $this->attribute("buttonlabel");
		$append = intval($this->attribute("append"));

		$user = JFactory::getUser();

		if ($append){
			$html = '<textarea name="'.$fieldname.'" cols="'.$cols.'" rows="'.$rows.'" '.$class.' id="jecfupdatable_'.$fieldname.'"  style="float:left"></textarea>';
			$html .= '<div style="float:left;margin-left:10px;"><div id="jecfupdatablediv_'.$fieldname.'" >'.str_replace(array("\\r\\n", "\\r", "\\n"), "<br />",$value);
			$html .= '</div><input type="button" onclick="updateUpdatable(\''.$fieldname.'\', '.$user->id.', '.$append.');return false;" value="'.JText::_($buttonlabel).'"/>';
			$html .= '</div>';
		}
		else {
			$html = '<textarea name="'.$fieldname.'" cols="'.$cols.'" rows="'.$rows.'" '.$class.' id="jecfupdatable_'.$fieldname.'" >'.$value.'</textarea>';
			$html .= '<input type="button" onclick="updateUpdatable(\''.$fieldname.'\', '.$user->id.', '.$append.');return false;" value="'.JText::_($buttonlabel).'"/>';
		}
		$html = str_replace("\n","",$html);

		return $html;
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
		$join =  " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id";
		$db = JFactory::getDBO();
		$filter = "$this->map.name=".$db->Quote($this->filterType)." AND $this->map.value LIKE (".$db->Quote($this->filter_value."%").")";
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
		if ($this->attribute("multifilter")==1){
			// allow multiple select!
			$filterList["html"] =  $this->fetchElement($this->filterType."_fv", implode(",",$this->filter_value), $this->node, "", true);
			$script = "try {JeventsFilters.filters.push({id:'".$this->filterType."_fv',value:".$this->filterNullValue[0] ."});}";
		}
		else {
			$filterList["html"] =  $this->fetchFilterElement($this->filterType."_fv", $this->filter_value, $this->node, "");
			$script = "try {JeventsFilters.filters.push({id:'".$this->filterType."_fv',value:'".addslashes($this->filterNullValue)."'});} catch (e) {}";
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