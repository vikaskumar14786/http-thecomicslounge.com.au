<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
jimport("joomla.html.parameter");

class JevCfParameter extends JRegistry
{

	private $event;
	private $filterelements;
	// for attendees only fields
	private $hasrsvp = false;
	private $hasaje = false;

	/**
	 * Backward compatability changes
	 */
	protected $_raw = null;
	protected $_xml = null;
	
	function __construct($data, $path = '', $event)
	{
		$this->event = $event;
		$params = "";
		$paramsarray = array();
		foreach ($data as $field)
		{
			// must make sure we preserver the carriage returns in text areas
			if (is_object($field)){
				$params.=$field->name . "=" . str_replace("\n", '\n', $field->value) . "\n";
				$paramsarray [$field->name] = str_replace("\n", '\n', $field->value);
			}
			else if (is_array($field))
			{
				$params.=$field["name"] . "=" . str_replace("\n", '\n', $field["value"]) . "\n";
				$paramsarray [$field["name"] ] = str_replace("\n", '\n',  $field["value"]);
			}
		}
		$filterelements = array();

		// for attendees only fields
		$rsvpplugin = JPluginHelper::getPlugin("jevents", "jevrsvppro");
		if (is_array($rsvpplugin) && count($rsvpplugin) == 0)
		{
			$this->hasrsvp = false;
		}
		else
		{
			$this->hasrsvp = true;
		}
		$ajeplugin = JPluginHelper::getPlugin("jevents", "jevsessions");
		if (is_array($ajeplugin) && count($ajeplugin) == 0)
		{
			$this->hasaje = false;
		}
		else
		{
			$this->hasaje = true;
		}

		if (version_compare(JVERSION, "1.6.0", 'ge') && count($paramsarray)>0){
			$params = json_encode($paramsarray);
		}
		
		parent::__construct($params, $path);
		
		if ($path)
		{
			$this->loadSetupFile($path);
		}

		$this->_raw = $data;
		

	}

	public function loadData($data, $event){
		$this->event = $event;
		$params = "";
		$paramsarray = array();
		foreach ($data as $field)
		{
			// must make sure we preserver the carriage returns in text areas
			if (is_object($field)){
				$params.=$field->name . "=" . str_replace("\n", '\n', $field->value) . "\n";
				$paramsarray [$field->name] = str_replace("\n", '\n', $field->value);
			}
			else if (is_array($field))
			{
				$params.=$field["name"] . "=" . str_replace("\n", '\n', $field["value"]) . "\n";
				$paramsarray [$field["name"] ] = str_replace("\n", '\n',  $field["value"]);
			}
		}
		if (version_compare(JVERSION, "1.6.0", 'ge') && count($paramsarray)>0){
			$params = json_encode($paramsarray);
		}
		return parent::bind($params);			
	}
	
	public $jevparams = array();

	/**
	 * Render
	 *
	 * @access	public
	 * @param	string	The name of the control, or the default text area if a setup file is not found
	 * @return	string	HTML
	 * @since	1.5
	 */
	public function setupRender()
	{

		// Get all the categories and their parentage
		$db = JFactory::getDBO();
		if (version_compare(JVERSION, "1.6.0", 'ge'))
		{
			$db->setQuery("SELECT id, parent_id from #__categories where extension='com_jevents' and published=1");
		}
		else
		{
			$db->setQuery("SELECT id, parent_id from #__categories where section='com_jevents' and published=1");
		}
		$catlist = $db->loadObjectList("id");

		$cats = array();
		foreach ($catlist as $cat)
		{
			// extract the complete ancestry
			if (!array_key_exists($cat->id, $cats))
			{
				$cats[$cat->id] = array();
				$cats[$cat->id][] = $cat->id;
				$parent = ($cat->parent_id > 0 && array_key_exists($cat->parent_id, $catlist)) ? $catlist[$cat->parent_id] : false;
				while ($parent)
				{
					$cats[$cat->id][] = $parent->id;
					$parent = ($parent->parent_id > 0 && array_key_exists($parent->parent_id, $catlist)) ? $catlist[$parent->parent_id] : false;
				}
			}
		}
		// Must set this up for empty category too
		$cats[0] = array();
		$cats[][] = 0;

		$cats = json_encode($cats);

		// setup required fields script
		$doc = JFactory::getDocument();
		$script = <<<SCRIPT
var JevrRequiredFields = {
	fields: new Array(),
	verify:function (form){
		var messages =  new Array();
		valid = true;
		JevrRequiredFields.fields.each(function (item,i) {
			name = item.name;

		    // should we skip this test because of category restrictions?
			if (JevrCategoryFields.skipVerify(name))  return;
		
			var matches = new Array();
                           Array.from(form.elements).slice().each (function (testitem,testi) {
				if(testitem.name == name || "custom_"+testitem.name == name || testitem.id == name  || testitem.id.indexOf(name+"_")==0  || ("#"+testitem.id) == name  || $(testitem).hasClass(name.substr(1))){
					matches.push(testitem);					
				};
			});
			var value = "";
			if(matches.length==1){
				value = matches[0].value;
			}
			// A set of radio checkboxes
			else if (matches.length>1){
				matches.each (function (match, index){
					if (match.checked) value = match.value; 
				});				
			}
			//if (elem) elem.value = item.value;
			if (value == item['default'] || value == ""){
				valid = false;
				// TODO add message together 
				if(item.reqmsg!=""){
					messages.push(item.reqmsg);
				}
			}
		});
		if (!valid){
			message = "";
			messages.each (function (msg, index){message += msg+"\\n";});
			alert(message); 
		}
		return valid;
	}
}
// Disabled for now
/*
window.addEvent("domready",function(){
	var form =document.adminForm;
	if (form){		
		$(form).addEvent('submit',function(event){if (!JevrRequiredFields.verify(form)) {event = new Event(event); event.stop();}});
		//JevrRequiredFields
	};
});
*/


// category conditional fields
var JevrCategoryFields = {
	fields: [],
	cats: $cats,
	setup:function (){
		if (!$('catid')) return;
		var catidel = $('catid');
		var catid = catidel.value;

		// These are the ancestors of this cat
		this.fields.each(function (item,i) {
			var elem = $(document).getElement(".jevplugin_customfield_"+item.name);
			if (item.name.indexOf(".")==0 || item.name.indexOf("#")==0) {
				elem = $(document).getElement(item.name);
			}
			if (!elem) return;
			// This is the version that ignores parent category selections
			/*
			// only show it if the selected category is in the list
			if (\$A(item.catids).contains(catid)){
				if (window.ie) {
					elem.style.display="";
				}
				else {
					elem.style.display="table-row";
				}
			}
			else {				
				elem.style.display="none";
			}
			*/
			// hide the item by default
			elem.style.display="none";
			
			if (catidel.multiple){
				\$A(catidel.options).each(function(opt) {
					if (opt.selected) {
						catid = opt.value;
						var cats = this.cats[catid];
						\$A(cats).each (function(cat,i){
							if (\$A(item.catids).contains(cat)){
								//alert("matched "+cat + " cf "+item.catids);
								if (window.ie) {
									elem.style.display="";
								}
								else {
									elem.style.display="table-row";
								}
							}
						});
					}
				}.bind(this));
			}
			else {
				var cats = this.cats[catid];
				\$A(cats).each (function(cat,i){
					if (\$A(item.catids).contains(cat)){
						//alert("matched "+cat + " cf "+item.catids);
						if (window.ie) {
							elem.style.display="";
						}
						else {
							elem.style.display="table-row";
						}
					}
				});
			}

		}.bind(this));
	},
	skipVerify:function (fieldname){
		if (!$('catid')) return true;
		var catid = $('catid').value;
		var cats = JevrCategoryFields.cats[catid];
		var skip = false;
		this.fields.each(function (item,i) {
			if ('custom_'+item.name !== fieldname && ('custom_jform['+item.name+']') !== fieldname) return;
			skip = true;
			\$A(cats).each (function(cat,i){
				if (\$A(item.catids).contains(cat)){
					skip = false;
					return;
				}
			});
		});
		return skip;
	}	
};
window.addEvent("load",function(){
	if (JevrCategoryFields){
		JevrCategoryFields.setup();
		if (!$('catid')) return;
		$('catid').addEvent('change',function(){
			JevrCategoryFields.setup();
		});
		if (!$('ics_id')) return;
		$('ics_id').addEvent('change',function(){
			setTimeout("JevrCategoryFields.setup()",500);
		});
	}
});
SCRIPT;
		$doc->addScriptDeclaration($script);

	}

	/**
	 * Render
	 *
	 * @access	public
	 * @param	string	The name of the control, or the default text area if a setup file is not found
	 * @return	string	HTML
	 * @since	1.5
	 */
	public function render($name = 'custom_', $group = '_default', &$customfields)
	{

		if (!isset($this->_xml [$group]))
		{
			return false;
		}

		$doc = JFactory::getDocument();

		$params = $this->getParams($name, $group);

		if ($description = $this->_xml [$group]->attributes()->description)
		{
			// add the params description to the display
			$desc = JText::_($description);
			$customfield = array("label" => "", "input" => $desc);
			$customfields["customfield_" . $this->attribute()->name] = $customfield;
		}

		$nodes = $this->_xml [$group]->children();
		for ($p = 0; $p < count($params); $p++)
		{
			$param = $params [$p];

			$node = $nodes [$p];

			$task = JRequest::getCmd('task', 'cpanel.show');
			// default state of allow override is TRUE
			$allowoverride = $this->attribute()->allowoverride;
			if (!is_null($allowoverride) && $allowoverride != 0)
				$allowoverride = 1;
			if ($task == "icalrepeat.edit" && !$allowoverride)
				continue;

			// check access
			$user = JFactory::getUser();
			if (version_compare(JVERSION, "1.6.0", 'ge'))
			{
				if ($this->attribute()->access && !in_array($this->attribute()->access, JEVHelper::getAid($user, 'array')))
					continue;
			}
			else
			{
				if ($this->attribute()->access && $user->aid < $this->attribute()->access)
					continue;
			}

			// Disabled for now
			$required = $this->attribute()->required ? JText::_("JEV_REQUIRED") : "";
			//$required = "";
			$customfield = array();
			$customfield["label"] = "";
			if ($param [0])
			{
				if (isset($param [2]))
				{
					$customfield["label"] = '<span class="editlinktip">' . $param [0] . $required . '</span>';
				}
				else
				{
					$customfield["label"] = JText::_($param [3]) . $required;
				}
			}
			$customfield["input"] = $param [1];
			if (strpos($this->attribute()->name, ".")!==0 && strpos($this->attribute()->name, "#")!==0) {
				$customfields["customfield_" . $this->attribute()->name] = $customfield;
			}

			if ($required)
			{
				//get the type of the parameter
				$type = $this->attribute()->type;
				if (strpos($type, "jevr") === 0)
				{
					$type = "jevcf" . substr($type, 4);
				}

				$element = $this->loadElement($type);
				if (method_exists($element, "fetchRequiredScript"))
				{
					$script = $element->fetchRequiredScript($this->attribute()->name, $node, $name);
					$doc->addScriptDeclaration($script);
				}
				else
				{
					if (strpos($this->attribute()->name,".")!==false || strpos($this->attribute()->name,"#")!==false ){
						$script = "JevrRequiredFields.fields.push({'name':'" . $this->attribute()->name . "', 'default' :'" . $this->attribute()->default . "' ,'reqmsg':'" . trim(JText::_($this->attribute()->requiredmessage, true)) . "'}); ";
					}
					else {
						$script = "JevrRequiredFields.fields.push({'name':'" . $name . "[" . $this->attribute()->name . "]', 'default' :'" . $this->attribute()->default . "' ,'reqmsg':'" . trim(JText::_($this->attribute()->requiredmessage, true)) . "'}); ";
					}
					$doc->addScriptDeclaration($script);
				}
			}

			$catrestrictions = $this->attribute()->categoryrestrictions;
			if ($catrestrictions)
			{
				static $done;
				if (!isset($done))
				{
					$done = array();
				}
				if (!in_array($this->attribute()->name . $name, $done))
				{
					$done[] = $this->attribute()->name . $name;
					;

					$cats = explode(",", $this->attribute()->categoryrestrictions);

					//get the type of the parameter
					$type = $this->attribute()->type;
					if (strpos($type, "jevr") === 0)
					{
						$type = "jevcf" . substr($type, 4);
					}
					$element = $this->loadElement($type);
					if (method_exists($element, "fetchCategoryRestrictionScript"))
					{
						$script = $element->fetchCategoryRestrictionScript($this->attribute()->name, $node, $name, $cats);
						$doc->addScriptDeclaration($script);
					}
					else
					{
						$script = "JevrCategoryFields.fields.push({'name':'" . $this->attribute()->name . "', 'default' :'" . $this->attribute()->default . "' ,'catids':" . json_encode($cats) . "}); ";
						$doc->addScriptDeclaration($script);
					}
				}
			}
		}


		return true;

	}

	/**
	 * Render a parameter type
	 *
	 * @param	object	A param tag node
	 * @param	string	The control name
	 * @return	array	Any array of the label, the form element and the tooltip
	 * @since	1.5
	 */
	public function getParam(&$node, $control_name = 'custom_', $group = '_default')
	{
		//get the type of the parameter
		$type = $this->attribute()->type;

		//remove any occurance of a mos_ prefix
		$type = str_replace('mos_', '', $type);

		if (strpos($type, "jevr") === 0)
		{
			$type = "jevcf" . substr($type, 4);
		}
		$element = $this->loadElement($type);

		// error happened
		if ($element === false)
		{
			$result = array();
			$result[0] = $this->attribute()->name;
			$result[1] = JText::_('Element not defined for type') . ' = ' . $type;
			$result[3] = $this->attribute()->label;
			$result[5] = $result[0];
			return $result;
		}

		// set the rsvpdata for reference
		$element->event = $this->event;

		//get value
		$value = $this->get($this->attribute()->name, $this->attribute()->default, $group);

		return $element->render($node, $value, $control_name);

	}

	public function renderToBasicArray($name = 'params', $group = '_default')
	{
		if (!isset($this->_xml[$group]))
		{
			return false;
		}
		$results = array();
		foreach ($this->_xml[$group]->children() as $node)
		{

			if ($this->attribute()->categoryrestrictions)
			{
				$cats = explode(",", $this->attribute()->categoryrestrictions);
				JArrayHelper::toInteger($cats);

				if (isset($this->event) && !in_array($this->event->catid(), $cats))
					continue;
			}
			$result = array();
			$result['type'] = $this->attribute()->type;
			if (strpos($result['type'], "jevr") === 0)
			{
				$result['type'] = "jevcf" . substr($result['type'], 4);
			}

			$result['value'] = $this->get($this->attribute()->name, $this->attribute()->default, $group);
			$result['defaultvalue'] = $this->attribute()->default;

			$element = $this->loadElement($result['type']);

			// Add the event row into the $element so that it is available is necessary
			$element->event = $this->event;

			if (method_exists($element, "convertValue"))
				$result['value'] = $element->convertValue($result['value'], $node);

			// reset the type - just in case a special type has changed the node attributes
			$result['type'] = $this->attribute()->type;
			if (strpos($result['type'], "jevr") === 0)
			{
				$result['type'] = "jevcf" . substr($result['type'], 4);
			}

			$result['name'] = $this->attribute()->name;
			$result['label'] = $this->attribute()->label;
			$result['access'] = ($this->attribute()->access != null) ? $this->attribute()->access : (version_compare(JVERSION, "1.6.0", 'ge') ? 1 : 0);
			$result['readaccess'] = $this->attribute()->readaccess;
			//$result['readaccess'] = ($this->attribute()->readaccess != null) ? $this->attribute()->readaccess : $result['access'] ;
			$result['hiddenvalue'] = $this->attribute()->hiddenvalue;
			$result['userid'] = $this->attribute()->userid;
			$result['allowhtml'] = $this->attribute()->allowhtml;
			$result['allowraw'] = $this->attribute()->allowraw;

			// if field is for attendees only then hide if necessary
			if ($this->attribute()->attendeesonly == 1)
			{
				$this->hideFromNonAttendees($result, $element);
			}

			$results[$result['name']] = $result;
		}
		return $results;

	}

	private function hideFromNonAttendees(&$result, $element)
	{
		// TODO find a more efficient way to do this - it currently generates  LOT of queries for list views!
		$user = JFactory::getUser();
		if ($this->hasrsvp && isset($this->event) && $user->id > 0)
		{			
			$db = JFactory::getDbo();
			$eventid = $this->event->ev_id();
			/*
			$sql = "SELECT atd.* FROM #__jev_attendance as atd WHERE atd.ev_id=" . $eventid;

			$db->setQuery($sql);
			$rsvpdata = $db->loadObject();

			if ($rsvpdata)
			{				
				$sql = "SELECT atdees.* FROM #__jev_attendees as atdees WHERE atdees.at_id=" . $rsvpdata->id . " and atdees.user_id=" . $user->id;
				if (!$rsvpdata->allrepeats)
				{
					$sql .= " AND atdees.rp_id=" . $this->event->rp_id();
				}
				$db->setQuery($sql);
				$attendee = $db->loadObject();

 				if ($attendee && $attendee->attendstate == 1)
				{
					return true;
				}
			} 
			 */				
			// combined query
			$sql = "SELECT atdees.* FROM #__jev_attendees as atdees ";
			$sql .= " LEFT JOIN #__jev_attendance as atd ON atd.id = atdees.at_id AND atd.ev_id=" . $eventid;
			$sql .= " WHERE atdees.user_id=" . $user->id;
			$sql .= " AND ( (atd.allrepeats=0 AND atdees.rp_id=" . $this->event->rp_id(). ") OR atd.allrepeats=1)";
			$db->setQuery($sql);
			$attendee = $db->loadObject();

			if ($attendee && $attendee->attendstate == 1)
			{
				return true;
			}
			
		}
		// don't hide if editing layout in backend
		if ( !JFactory::getApplication()->isAdmin() || JRequest::getCmd("task")!="defaults.edit"){
			$result["value"] = $result['hiddenvalue'];
			$result["label"]="";
		}
	}

	public function constructFilters()
	{
		$this->filterElements = array();
		$groups = $this->getGroups();
		foreach ($groups as $group => $count)
		{
			if (!isset($this->_xml[$group]))
			{
				continue;
			}
			foreach ($this->_xml[$group]->children() as $node)
			{
				if (!$this->attribute()->filter)
					continue;
				$type = $this->attribute()->type;
				if (strpos($type, "jevr") === 0)
				{
					$type = "jevcf" . substr($type, 4);
				}
				// Must be a new one
				$element = $this->loadElement($type, true);
				if (method_exists($element, "constructFilter"))
					$element->constructFilter($node);
				$this->filterElements[] = $element;
			}
		}
		return;

	}

	public function createFilters()
	{
		$results = array();
		foreach ($this->filterElements as $element)
		{
			if (method_exists($element, "createFilter"))
			{
				$result = $element->createFilter();
				if ($result)
					$results[] = $result;
			}
		}
		return implode(" AND ", $results);

	}

	public function createJoinFilters()
	{
		$results = array();
		foreach ($this->filterElements as $element)
		{
			if (method_exists($element, "createJoinFilter"))
			{
				$result = $element->createJoinFilter();
				if ($result)
					$results[] = $result;
			}
		}
		return implode(" LEFT JOIN ", $results);

	}

	public function setSearchKeywords(& $extrajoin)
	{
		$results = array();
		foreach ($this->filterElements as $element)
		{
			if (method_exists($element, "setSearchKeywords"))
			{
				$result = $element->setSearchKeywords($extrajoin);
				if ($result)
					$results[] = $result;
			}
		}
		return $results;

	}

	public function createFiltersHTML()
	{
		$results = array();
		$results["merge"] = array();
		foreach ($this->filterElements as $element)
		{
			if (method_exists($element, "createFilterHTML"))
				$results["merge"][] = $element->createFilterHTML();
		}
		return $results;

	}

	public function getXML($group="_default")
	{
		return $this->_xml[$group];

	}

	
	
	// Backward compatability changes
	public function getGroups()
	{
		if (!is_array($this->_xml))
		{

			return false;
		}

		$results = array();
		foreach ($this->_xml as $name => $group)
		{
			$results[$name] = $this->getNumParams($name);
		}
		return $results;
	}

	public function loadSetupFile($path)
	{
		$result = false;

		if ($path)
		{
			jimport('joomla.utilities.xmlelement');
			$xml = simplexml_load_file($path, 'JXMLElement');

			if ($xml)
			{
				if ($params = $xml->params)
				{
					foreach ($params as $param)
					{
						$this->setXML($param);
						$result = true;
					}
				}
			}
		}
		else
		{
			$result = true;
		}

		return $result;
	}
	
	public function setXML(&$xml)
	{

		if (is_object($xml))
		{
			if ($group = $xml->attributes('group'))
			{
				$this->_xml[$group] = $xml;
			}
			else
			{
				$this->_xml['_default'] = $xml;
			}

			if ($dir = $xml->attributes('addpath'))
			{
				$this->addElementPath(JPATH_ROOT . str_replace('/', "/", $dir));
			}
		}
	}
	
	public function getNumParams($group = '_default')
	{
		if (!isset($this->_xml[$group]) || !count($this->_xml[$group]->children()))
		{
			return false;
		}
		else
		{
			return count($this->_xml[$group]->children());
		}
	}
	
	public function getParams($name = 'params', $group = '_default')
	{

		if (!isset($this->_xml[$group]))
		{

			return false;
		}

		$results = array();
		foreach ($this->_xml[$group]->children() as $param)
		{
			$results[] = $this->getParam($param, $name, $group);
		}
		return $results;
	}
	
	public function loadElement($type, $new = false)
	{
		$signature = md5($type);

		if ((isset($this->_elements[$signature]) && !($this->_elements[$signature] instanceof __PHP_Incomplete_Class)) && $new === false)
		{
			return $this->_elements[$signature];
		}

		$elementClass = 'JFormField' . $type;
		if (!class_exists($elementClass))
		{
			if (isset($this->_elementPath))
			{
				$dirs = $this->_elementPath;
			}
			else
			{
				$dirs = array();
			}

			$file = JFilterInput::getInstance()->clean(str_replace('_', "/", $type) . '.php', 'path');

			jimport('joomla.filesystem.path');
			if ($elementFile = JPath::find($dirs, $file))
			{
				include_once $elementFile;
			}
			else
			{
				$false = false;
				return $false;
			}
		}

		if (!class_exists($elementClass))
		{
			$false = false;
			return $false;
		}

		$this->_elements[$signature] = new $elementClass($this);

		return $this->_elements[$signature];
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