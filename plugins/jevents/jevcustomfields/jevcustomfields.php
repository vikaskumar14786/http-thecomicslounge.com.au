<?php
/**
 * copyright (C) 2009 GWE Systems Ltd - All rights reserved
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
JLoader::register('JevCfForm', dirname(__FILE__) . "/customfields/jevcfform.php");
JLoader::register('JevJoomlaVersion',JPATH_ADMINISTRATOR."/components/com_jevents/libraries/version.php");

class plgJEventsJevcustomfields extends JPlugin
{

	// reduce the number of queries by caching in memory
	static $cachedCfData = array();
	static $cachedParamsData = array();

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		JFactory::getLanguage()->load('plg_jevents_jevcustomfields', JPATH_ADMINISTRATOR);

		include_once(JPATH_SITE . "/components/com_jevents/jevents.defines.php");

		// If JDEBUG is defined, load the profiler instance
		if (defined('JDEBUG') && JDEBUG)
		{
			$this->profiler = JProfiler::getInstance('Application');
		}
	}

	/**
	 * When editing a JEvents menu item can add additional menu constraints dynamically
	 *
	 */
	function onEditMenuItem(&$menudata, $value, $control_name, $name, $id, $param)
	{
		// already done this param
		if (isset($menudata[$id]))
			return;

		$html = "";

		// New parameterised fields - only filter items with attribute filtermenusandmodules = "1"
		$hasparams = false;
		$template = $this->params->get("template", "");
		if ($template != "")
		{
			$xmlfile = plgJEventsJevcustomfields::getXMLFile($template);
			if (file_exists($xmlfile))
			{
				static $params;
				static $offerfilter;
				static $nodes;
				if (!isset($params)){
					$offerfilter = false;
					include_once(JPATH_ADMINISTRATOR . "/components/com_jevents/jevents.defines.php");
					$params = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$params->setEvent(null);

					$nodes = array();
					$groups = $params->getFieldsets();
					foreach ($groups as $group => $element)
					{
						$count = $params->getFieldCountByFieldSet($group);
						$groupparams = $params->getFieldset($group);
						foreach ($groupparams as $p => $node)
						{
							if ($node->attribute("filtermenusandmodules"))
							{
								$offerfilter = true;
								$node->fieldset = $element;
								$node->fieldsetGroup = $group;
								$nodes[] = $node;
							}
						}
					}
				}
				if (!$offerfilter)
					return;

				/*
				$paramsarray = array();
				$groups = $params->getFieldsets();
				foreach ($groups as $group => $element)
				{
					$count = $params->getFieldCountByFieldSet($group);
					$paramsarray = array_merge($paramsarray, $params->renderToBasicArray('params', $group));
				}
				 */

				static $matchingextra = null;
				// find the parameter that matches jevcf: (if any)
				if (!isset($matchingextra))
				{
					$paramsgroup = $param->getGroup('params');
					foreach ($paramsgroup as $key => $element)
					{
						$val = $element->value;
						if (strpos($key, "jform_params_extras") === 0)
						{
							if (strpos($val, "jevcf:") === 0)
							{
								$matchingextra = $key;
								break;
							}
						}
					}
					if (!isset($matchingextra))
					{
						$matchingextra = false;
					}
				}

				// either we found matching extra and this is the correct id or we didn't find matching extra and the value is blank
				if (($matchingextra == $id && strpos($value, "jevcf:") === 0) || (($value == "" || $value == "0") && $matchingextra === false))
				{

					$matchingextra = $id;

					$invalue = str_replace("jevcf:", "", $value);
					if ($invalue != "")
					{
						// assumes the data was stored in json encoded format
						$invalue = json_decode(htmlspecialchars_decode($invalue));
						if (!is_array($invalue))
						{
							$invalue = array();
						}
					}
					else
					{
						$invalue = array();
					}
					$values = array();
					foreach ($invalue as $inv)
					{
						$values[$inv->id] = $inv->val;
					}

					$json = version_compare(JVERSION, '1.6.0', '>=') ? "JSON.encode" : "Json.toString";
					$script = <<<SCRIPT
var JevrCustomFields = {
	fields: new Array(),
	convert:function (){
		var values = new Array();
		//alert('convert '+JevrCustomFields.fields);
		JevrCustomFields.fields.each(function(el){
			var elem = $(el);
			if (elem){
				var id = elem.id;
				var val = elem.value;
				values.push({'id':id, 'val':val});
			}
			else {
				// else could be a radio box!
				document.getElements('input[name='+el+']').each(function(item){
					if (item.checked) {
						var id = el;
						var val = item.value;
						values.push({'id':id, 'val':val});
					}
				});
				// could be a select box!
				document.getElements('select[name='+el+'] option').each(function(item){
					if (item.selected) {
						var id = el;
						var val = item.value;
						values.push({'id':id, 'val':val});
					}
				});
			}
		});
		$('paramsextras$id').value = "jevcf:"+$json(values);
	}
};
SCRIPT;
					$script .= "window.addEvent('load',function(){";
					$html = "<table id='frogswerehere'>";
					$firstpass = true;
					foreach ($nodes as $node)
					{
						$type = $node->attribute("type");
						$type = str_replace("jevr", "jevcf", $type);

						$label = $node->attribute("label");
						$elemname = $name . $node->attribute("name");
						// Needed for Joomla 1.6 (no harm for Joomla 1.5
						$elemname = str_replace(array("[", "]"), "", $elemname);
						if (array_key_exists("$elemname", $values))
						{
							$val = $values["$elemname"];
						}
						else
						{
							$val = $node->attribute("default");
						}
						//$formelement = $node->fetchElement($elemname, $val, $node, "cfparams");

						$params->setValue( $node->attribute("name"), $val);
						$node->setValue($val);
						
						$formelement = $node->input;
						$formelement = str_replace($node->name, $elemname,$formelement);
						
						$html .= "<tr><td class='label'>$label</td><td>$formelement</td></tr>";
						if ($firstpass){
							$script .= "$('frogswerehere').addEvent('mouseout',function(item){JevrCustomFields.convert();});\n";
							$firstpass = false;
						}
						$script .= "JevrCustomFields.fields.push('$elemname');\n";
					}
					$html .= "</table>";
					$script .= "});";

					$style = "#frogswerehere * {float:none;}#frogswerehere label{margin-left:2px;margin-right:10px;min-width:0px;} #frogswerehere  td.label {padding-right:5px;font-weight:bold;}";
					$document = JFactory::getDocument();
					$document->addScriptDeclaration($script);
					$document->addStyleDeclaration($style);

					$html .= "<textarea  name='$name' id='paramsextras$id' style='display:none' rows='5' columns='40' >$value</textarea>";
					$html = '<div style="clear:left"></div>' . $html . '<div style="clear:left"></div>';

					$data = new stdClass();
					$data->name = "jevcf";
					// This is where the form data goes
					// Note that we will need to convert the muliple field data inputs into a single field value - probably using json encoding in mootools Json.toString(...)
					$data->html = $html;
					$data->label = "JEV_CUSTOM_FIELD_FILTER";
					$data->description = "JEV_SPECIFY_CUSTOM_FIELD_VALUES";
					$data->options = array();
					$menudata[$id] = $data;
				}
			}
		}
		return;

	}

	function onEditCustom(&$row, &$customfields)
	{

		$html = "";

		// New parameterised fields
		$hasparams = false;
		$template = $this->params->get("template", "");
		if ($template != "")
		{
			$xmlfile = plgJEventsJevcustomfields::getXMLFile($template);
			if (file_exists($xmlfile))
			{
				if ($row->evdet_id())
				{
					$id = intval($row->evdet_id());
					$data = $this->getCachedCfData($id);

					$params = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$params->bind($data);
					$params->setEvent($row);
				}
				else
				{
					$params = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					//$params->bind(array());
					$params->setEvent($row);
				}
				JHTML::_('behavior.tooltip');
				$groups = $params->getFieldsets();
				/*
				foreach ($groups as $group => $element)
				{
					if ($params->getFieldCountByFieldSet($group))
					{
						$params->setupRender();
						if ($group!="default") {
                                                    $params->render('custom_', $group,  $customfields);
						}
						//break;
					}
				}
				 * 
				 */
				if (isset($groups["default"]) && $params->getFieldCountByFieldSet('default'))
				{
					$params->render('custom_', 'default', $customfields);
				}
			}
		}

	}

	// Extra tabs if required
	function onEventEdit(&$extraTabs, &$row, &$jevparams)
	{
		$html = "";

		// New parameterised fields
		$hasparams = false;
		$template = $this->params->get("template", "");
		if ($template != "")
		{
			$xmlfile = plgJEventsJevcustomfields::getXMLFile($template);
			if (file_exists($xmlfile))
			{
				if ($row->evdet_id())
				{
					$id = intval($row->evdet_id());
					$customdata = $this->getCachedCfData($id);

					$params = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$params->bind($customdata);
					$params->setEvent($row);
				}
				else
				{
					$params = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					//$params->bind(array());
					$params->setEvent($row);
				}

				JHTML::_('behavior.tooltip');


				$groups = $params->getFieldsets();
				foreach ($groups as $group => $element)
				{
					if ($params->getFieldCountByFieldSet($group))
					{
						$params->setupRender();
						break;
					}
				}

				$panecount = 0;
				foreach ($groups as $group => $element)
				{
					$count = $params->getFieldCountByFieldSet($group);
					$customfields = array();
					if ($count > 0 && $group != "default")
					{
						$params->render('custom_', $group, $customfields);
						if (count($customfields) > 0)
						{
							ob_start();
							?> <table cellpadding="5" cellspacing="2" border="0"  class="adminform" > <?php
							foreach ($customfields as $key => $val)
							{
								?>
									<tr class="jevplugin_<?php echo $key; ?>">
										<td style="width:130px;text-align: left;vertical-align: top" ><?php echo $customfields[$key]["label"]; ?></td>
										<td colspan="3"><?php echo $customfields[$key]["input"]; ?></td>
									</tr><?php
							}
							echo "</table>";
							$input = ob_get_clean();
							$label = $group;
							$extraTabs[] = array("title" => $label, "paneid" => 'jev_attend_pane_' . $panecount, "content" => $input);
							$panecount++;
						}
					}
				}
			}
		}

	}

	/**
	 * Clean out custom fields for event details not matching global event detail
	 *
	 * @param unknown_type $idlist
	 */
	function onCleanCustomDetails($idlist)
	{
		$db = JFactory::getDbo();
		$db->setQuery("DELETE FROM #__jev_customfields WHERE evdet_id NOT IN (SELECT det.evdet_id FROM #__jevents_vevdetail as det )");
		$db->query();
		return true;
	}

	/**
	 * Store custom fields
	 *
	 * @param iCalEventDetail $evdetail
	 */
	function onStoreCustomDetails($evdetail)
	{

		// New parameterised fields
		$hasparams = false;
		$template = $this->params->get("template", "");
		if ($template != "")
		{
			$xmlfile = plgJEventsJevcustomfields::getXMLFile($template);
			if (file_exists($xmlfile))
			{

				$eventid = $evdetail->evdet_id;
				$id = $eventid;
				$customdata = $this->getCachedCfData($id);

				$params = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
				$params->bind($customdata);
				$params->setEvent(null);

				$newparams = array();
				$groups = $params->getFieldsets();
				foreach ($groups as $group => $element)
				{
					if ($params->getFieldCountByFieldSet($group))
					{
						$newparams = array_merge($newparams, $params->renderToBasicArray('params', $group));
					}
				}
				$params = $newparams;

				$user = JFactory::getUser();
				foreach ($params as $param)
				{
					if (!empty($param["userid"]) && $param["userid"] != $user->id)
					{
						foreach ($customdata as $cdname => $cdvalue)
						{
							if ($cdname == $param["name"])
							{
								$evdetail->_customFields[$param["name"]] = $cdvalue;
							}
						}
					}
					// fall back to default values
					if (!isset($evdetail->_customFields[$param["name"]]) )
					{
						static $parentEventDetail = false;
						if (!$parentEventDetail)
						{
							$db = JFactory::getDBO();
							$db->setQuery("SELECT evt.detail_id FROM #__jevents_repetition as rpt
								LEFT JOIN #__jevents_vevent as evt on rpt.eventid=evt.ev_id
								WHERE eventdetail_id=" . $id);
							$parentEventDetail = intval($db->loadResult());
							// unless we are editing an existing repeat exception then we will not have a parent Event Details from this query
							if ($parentEventDetail == 0 and JRequest::getInt("evid") > 0)
							{
								$db->setQuery("SELECT evt.detail_id FROM #__jevents_vevent as evt
									WHERE ev_id=" . JRequest::getInt("evid"));
								$parentEventDetail = intval($db->loadResult());
							}
						}
						if ($parentEventDetail > 0)
						{
							$id = $parentEventDetail;
							$parentCustomdata = $this->getCachedCfData($id);

							if (array_key_exists($param["name"], $parentCustomdata) )
							{
								$evdetail->_customFields[$param["name"]] = $parentCustomdata[$param["name"]];
							}
							else
							{
								$evdetail->_customFields[$param["name"]] = isset($param["defaultvalue"]) ? $param["defaultvalue"] : "";
							}
						}
						else
						{
							$evdetail->_customFields[$param["name"]] = isset($param["defaultvalue"]) ? $param["defaultvalue"] : "";
						}
					}
				}

				// clean out the defunct data but leave private data intact!!
				// using the id from the existing data eliminates the need to do this
				//$sql = "DELETE FROM #__jev_customfields WHERE evdet_id=" . intval($eventid) ." and user_id =0";
				//$db->setQuery($sql);
				//$success = $db->query();

				foreach ($params as $param)
				{
					if (!array_key_exists($param["name"], $evdetail->_customFields))
						continue;

					// Do not save data for names with period in them - it won't work in JForm
					if (strpos($param["name"], ".") !== false)
					{
						$param["name"] = str_replace(".", "_", $param["name"]);
					}

					if (isset($evdetail->_customFields[$param["name"]]) && !is_array($evdetail->_customFields[$param["name"]]))
					{
						if ($param["allowraw"])
						{
							$customfield = $evdetail->_customFields[$param["name"]];
						}
						else if ($param["allowhtml"])
						{
							static $safeHtmlFilter;
							if (!isset($safeHtmlFilter))
							{
								$safeHtmlFilter = & JFilterInput::getInstance(null, null, 1, 1);
							}
							$customfield = $safeHtmlFilter->clean($evdetail->_customFields[$param["name"]]);
						}
						else
						{
							static $noHtmlFilter;
							// Since no allow flags were set, we will apply the most strict filter to the variable
							if (is_null($noHtmlFilter))
							{
								$noHtmlFilter = JFilterInput::getInstance(/* $tags, $attr, $tag_method, $attr_method, $xss_auto */);
							}
							$customfield = $noHtmlFilter->clean($evdetail->_customFields[$param["name"]]);
						}
					}
					else if (isset($evdetail->_customFields[$param["name"]]))
					{
						$customfield = implode(",", $evdetail->_customFields[$param["name"]]);
					}
					else {
						$x =1;
					}

					$id = 0;
					// is this an update?
					// this no longer works because we do not pass through the 
					if (isset($customdata["fieldid_".$param["name"]]) &&  $customdata["fieldid_".$param["name"]] > 0)
					{
						$id = $customdata["fieldid_".$param["name"]];
					}

					JTable::addIncludePath(dirname(__FILE__) . "/customfields/");
					$rsvpitem =  JTable::getInstance('jevcustomfields');
					$rsvpitem->value = $customfield;
					$rsvpitem->evdet_id = intval($eventid);
					$rsvpitem->name = $param["name"];
					$rsvpitem->id = $id;
					$rsvpitem->store();

				}
			}
		}

		return true;

	}

	/**
	 * Store custom fields
	 *
	 * @param iCalEventDetail $evdetail
	 */
	// TODO update reminder timestamps when event times have changed
	function onStoreCustomEvent($event)
	{
		return true;

	}

	/*
	 * Any special treatment after saving event?
	 */

	function onAfterSaveEvent($event, $dryrun = false)
	{
		if ($dryrun)
		{
			return true;
		}
		// New parameterised fields
		$hasparams = false;
		$template = $this->params->get("template", "");
		if ($template != "")
		{
			$xmlfile = plgJEventsJevcustomfields::getXMLFile($template);
			if (file_exists($xmlfile))
			{
				$id = intval($event->detail_id);
				$customdata = $this->getCachedCfData($id);

				$params = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
				$params->bind($customdata);
				$params->setEvent(null);

				$user = JFactory::getUser();

				$groups = $params->getFieldsets();
				foreach ($groups as $group => $element)
				{
					$count = $params->getFieldCountByFieldSet($group);
					$groupparams = $params->getFieldset($group);
					foreach ($groupparams as $p => $node)
					{

						$type = $node->attribute("type");
						$name = $node->attribute("name");
						$value = $node->value;
						$type = str_replace("jevr", "jevcf", $type);
						if (method_exists($node, "onAfterSaveEvent"))
						{
							$elem->onAfterSaveEvent($node, $value, $event);
						}
					}
				}
			}
		}

		return true;

	}

	/**
	 * Clean out custom details for deleted event details
	 *
	 * @param comma separated list of event detail ids $idlist
	 */
	function onDeleteEventDetails($idlist)
	{
		// you delete unwanted custom data here - housekeeping

		// clean up the custom fields files first
		if (strlen(trim($idlist)) < 1)
			return false;

		$ids = explode(",", $idlist);
		JArrayHelper::toInteger($ids);
		$idlist = implode(",", $ids);

		// Find the referenced files
		$db = JFactory::getDBO();
		$usedfiles = array();
		// Must also add in images used in custom fields
		if ($cfplugin = JPluginHelper::getPlugin("jevents","jevcustomfields")){
			$this->params = new JRegistry($cfplugin->params);
			$template = $this->params->get("template","");
			if ($template!=""){
				JLoader::register('JevCfForm', JPATH_SITE."/plugins/jevents/jevcustomfields/jevcfform.php");
				$xmlfile = JPATH_SITE."/plugins/jevents/jevcustomfields/customfields/templates/".$template;

				if (file_exists($xmlfile)){
					include_once(JPATH_ADMINISTRATOR . "/components/com_jevents/jevents.defines.php");
					$this->fieldparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$this->fieldparams->setEvent(null);

					$groups = $this->fieldparams->getFieldsets();
					foreach ($groups as $group => $element)
					{
						$count = $this->fieldparams->getFieldCountByFieldSet($group);
						$groupparams = $this->fieldparams->getFieldset($group);
						foreach ($groupparams as $p => $node)
						{
							if ($node->attribute("type")=="jevcfimage" || $node->attribute("type")=="jevcffile")
							{
								$db = JFactory::getDBO();
								$sql = "SELECT DISTINCT value FROM #__jev_customfields where value<>'' AND name=".$db->quote($node->fieldname) ." AND evdet_id IN ($idlist)";
								$db->setQuery($sql);
								$usedfiles2 = $db->loadColumn();
								$usedfiles = array_merge($usedfiles2, $usedfiles);
							}
						}
					}
				}
			}
		}

		if (count($usedfiles)>0){
			// Get the media component configuration settings
			$params = JComponentHelper::getParams('com_media');
			$path = JPATH_ROOT."/".$params->get('image_path', 'images/stories');
			$folder = $this->params->get("folder","jevents");

			jimport('joomla.filesystem.file');
			foreach ($usedfiles as $file) {
				if (trim($file)=="") continue;

				// make sure not used in a copied event or if just deleting a single repeat etc.
				$sql = "SELECT count(evdet_id) FROM #__jev_customfields WHERE value = ".$db->quote($file) . " AND evdet_id NOT IN (" . $idlist . ")";
				$db->setQuery($sql);
				$count = $db->loadResult();
				if ($count>0) continue;

				JFile::delete($path."/".$folder."/".$file);
				if (JFile::exists($path."/".$folder."/".'thumbnails'."/".'thumb_'.$file)){
					JFile::delete($path."/".$folder."/".'thumbnails'."/".'thumb_'.$file);
				}
			}
		}

		$db = JFactory::getDBO();

		$sql = "DELETE FROM #__jev_customfields WHERE evdet_id IN (" . $idlist . ")";
		$db->setQuery($sql);
		$db->query();
		return true;

	}


	function onDeleteCustomEvent($idlist)
	{
		// TODO remove any records
		return true;
		/* A clean up query that we can use at some point */
		/*
delete  th  from #__jev_customfields as th
where th.evdet_id IN (
	select temp.evdet_id FROM (
		select cf.evdet_id from #__jev_customfields as cf
		left join #__jevents_vevdetail as det on det.evdet_id=cf.evdet_id
		where det.evdet_id is null
	) as temp
)
*/

	}

	function onListIcalEvents(& $extrafields, & $extratables, & $extrawhere, & $extrajoin, & $needsgroupdby = false)
	{
		static $usefilter;

		if (!isset($usefilter))
		{
			$mainframe = JFactory::getApplication();
			if (JFactory::getApplication()->isAdmin())
			{
				$usefilter = false;
				return;
			}


			// Have we specified specific people for the menu item
			$compparams = JComponentHelper::getParams("com_jevents");

			// If loading from a module then get the modules params from the registry
			$reg =  JFactory::getConfig();
			$modparams = $reg->get("jev.modparams", false);
			$frommodule = false;
			if ($modparams)
			{
				$compparams = $modparams;
				$frommodule = true;
			}
			// overwrite values if called from missing events plugin
			if ( JRegistry::getInstance("jevents")->get("getnewfilters")){
				$frommodule = true;
			}

			for ($extra = 0; $extra < 20; $extra++)
			{
				$extraval = $compparams->get("extras" . $extra, false);
				if (strpos($extraval, "jevcf:") === 0)
				{
					break;
				}
			}

			$registry = JRegistry::getInstance("jevents");
			$oldRequestValues = array();

			// if we have a conditional vauye then apply filter
			if ($extraval)
			{
				$invalue = str_replace("jevcf:", "", $extraval);
				if ($invalue != "")
				{
					// assumes the data was stored in json encoded format
					$invalue = json_decode(htmlspecialchars_decode($invalue));
				}
				else
				{
					$invalue = array();
				}

				$template = $this->params->get("template", "");
				if ($template != "")
				{
					$xmlfile = plgJEventsJevcustomfields::getXMLFile($template);
					if (file_exists($xmlfile))
					{
						static $cfparams;
						static $cfnodes;
						if (!isset($cfparams))
						{
							$offerfilter = false;
							include_once(JPATH_ADMINISTRATOR . "/components/com_jevents/jevents.defines.php");
							$cfparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
							$cfparams->setEvent(null);

							$cfnodes = array();
							$groups = $cfparams->getFieldsets();
							foreach ($groups as $group => $element)
							{
								$count = $cfparams->getFieldCountByFieldSet($group);
								$groupparams = $cfparams->getFieldset($group);
								foreach ($groupparams as $p => $node)
								{
									if ($node->attribute("filtermenusandmodules"))
									{
										$offerfilter = true;
										$node->fieldset = $element;
										$node->fieldsetGroup = $group;
										$node->filterdefault = $node->attribute('filterdefault');
										if (is_null($node->filterdefault)){
											$node->filterdefault = $node->attribute('default');
										}
										$cfnodes[$node->fieldname] = $node;
									}
								}
							}
						}
					}
				}

				$skipreset = false;
				foreach ($invalue as $inv)
				{
					$skipreset = true;
					// Set the request values to match those from the menu or module parameters
					$fieldname =str_replace("jformparamsextras$extra", "", $inv->id) ;

					//  DO NOT SET if value from menu or module is the filter default value
					if (isset($cfnodes) && isset($cfnodes[$fieldname]) && $inv->val==$cfnodes[$fieldname]->filterdefault){
						continue;
					}

					// do not overwrite if already set unless loading from a module
					$oldRequestValues[$fieldname . "_fv"] = JRequest::getVar($fieldname . "_fv", "not set");
					JRequest::setVar($fieldname . "_fv", $inv->val, "method", $frommodule);
				}

				// Make sure the filter is used even if there is no module!
				$indexedvisiblefilters = $registry->get("indexedvisiblefilters", false);
				if (!$indexedvisiblefilters)
				{
					$indexedvisiblefilters = array();
				}
				$indexedvisiblefilters["Customfield"] = "customfield";
				$registry->set("indexedvisiblefilters", $indexedvisiblefilters);
			}

			// find what is running - used by the filters
			$activeprocess = $registry->get("jevents.activeprocess", "");
			$moduleid = $registry->get("jevents.moduleid", "customfieldsplugin");
			$moduleparams = $registry->get("jevents.moduleparams", false);

			if (isset($skipreset) && $skipreset){
				$filter_reset = JRequest::getVar("filter_reset",0);
				JRequest::setVar("filter_reset",0);
			}
			$filters = jevFilterProcessing::getInstance(array("Customfield"), JPATH_SITE . "/plugins/jevents/filters", false, $moduleid);
			if (isset($skipreset) && $skipreset){
				JRequest::setVar("filter_reset",$filter_reset);
			}

			$filters->setWhereJoin($extrawhere, $extrajoin);
			//$filters->setSearchKeywords($extrawhere, $extrajoin);

			// reset Request where needed
			foreach ($oldRequestValues as $key => $val)
			{
				if ($val == "not set")
				{
					JRequest::setVar($key, "");
					JFactory::getApplication()->setUserState( $key."_ses","");
				}
				else
				{
					JRequest::setVar($key, $val);
					JFactory::getApplication()->setUserState( $key."_ses",$val);
				}
			}
		}
		return true;

	}

	function onSearchEvents(& $extrasearchfields, & $extrajoin, & $needsgroupdby = false)
	{
		static $usefilter;

		if (!isset($usefilter))
		{
			$mainframe = JFactory::getApplication();
			if (JFactory::getApplication()->isAdmin())
			{
				$usefilter = false;
				return;
			}

			if (!isset($extrajoin)){
				$extrajoin = array();
			}
			$filters = jevFilterProcessing::getInstance(array("Customfield"), JPATH_SITE . "/plugins/jevents/filters", false, "customfields");
			$filters->setSearchKeywords($extrasearchfields, $extrajoin);
		}

		return true;

	}

	function onListEventsById(& $extrafields, & $extratables, & $extrawhere, & $extrajoin, & $needsgroupdby = false)
	{
		return $this->onListIcalEvents($extrafields, $extratables, $extrawhere, $extrajoin, $needsgroupdby);

	}

	function onListEventsByIdOLD(& $extrafields, & $extratables, & $extrawhere, & $extrajoin)
	{
		static $usefilter;

		if (!isset($usefilter))
		{
			$mainframe = JFactory::getApplication();
			if (JFactory::getApplication()->isAdmin())
			{
				$usefilter = false;
				return;
			}

			// Have we specified specific people for the menu item
			$compparams = JComponentHelper::getParams("com_jevents");

			// If loading from a module then get the modules params from the registry
			$reg =  JFactory::getConfig();
			$modparams = $reg->get("jev.modparams", false);
			if ($modparams)
			{
				$compparams = $modparams;
			}

			for ($extra = 0; $extra < 20; $extra++)
			{
				$extraval = $compparams->get("extras" . $extra, false);
				if (strpos($extraval, "jevcf:") === 0)
				{
					break;
				}
			}
			// if we have a conditional vauye then apply filter
			if ($extraval)
			{
				$invalue = str_replace("jevcf:", "", $extraval);
				if ($invalue != "")
				{
					// assumes the data was stored in json encoded format
					$invalue = json_decode(htmlspecialchars_decode($invalue));
				}
				else
				{
					$invalue = array();
				}
				foreach ($invalue as $inv)
				{
					JRequest::setVar(str_replace("cfparamsextras$extra", "", $inv->id) . "_fv", $inv->val);
				}
			}

			// find what is running - used by the filters
			$registry = JRegistry::getInstance("jevents");
			$activeprocess = $registry->get("jevents.activeprocess", "");
			$moduleid = $registry->get("jevents.moduleid", 0);
			$moduleparams = $registry->get("jevents.moduleparams", false);

			$filters = jevFilterProcessing::getInstance(array("Customfield"), JPATH_SITE . "/plugins/jevents/filters", false, $moduleid);

			$filters->setWhereJoin($extrawhere, $extrajoin);
		}

		return true;

	}

	function onDisplayCustomFields(&$row)
	{
		// New parameterised fields
		$hasparams = false;
		$template = $this->params->get("template", "");
		$customfields = array();
		if ($template != "")
		{
			$xmlfile = plgJEventsJevcustomfields::getXMLFile($template);
			if (file_exists($xmlfile))
			{

				$id = intval($row->evdet_id());
				$user = JFactory::getUser();
				$customdata = $this->getCachedCfData($id, $user->id);
				$params = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
				$params->bind($customdata);
				$params->setEvent($row);
				$customfields = array();

				// Slimmer method since we don't need the elements
				$groups = $params->getFieldsetsBasic();
				foreach ($groups as $group => $elementNotNeeded)
				{
					$extracustomfields = $params->renderToBasicArray('params', $group);
					if ($extracustomfields) $customfields = array_merge($customfields,$extracustomfields );
				}
			}
		}
		else
		{
			return "";
		}
		$templatetop = $this->params->get("templatetop", "<table border='0'>");
		$templaterow = $this->params->get("templatebody", "<tr><td class='label'>{LABEL}</td><td>{VALUE}</td></tr>");
		$templatebottom = $this->params->get("templatebottom", "</table>");

		$row->customfields = $customfields;
		$html = $templatetop;
		$user = JFactory::getUser();
		foreach (array_keys($customfields) as $id)
		{
			$customfield = $customfields[$id];
			if (isset($customfield["readaccess"]))
			{
				if (!$this->checkAccess($user, $customfield["readaccess"]))
				{
					unset($customfields[$id]);
					continue;
				}
			}
			else if (!$this->checkAccess($user, $customfield["access"]))
			{
				unset($customfields[$id]);
				continue;
			}

			if ((isset($customfield["hiddenvalue"]) && trim($customfield["value"]) == $customfield["hiddenvalue"]))
			{
				unset($customfields[$id]);
				continue;
			}
			$outrow = str_replace("{LABEL}", $customfield["label"], $templaterow);
			$outrow = str_replace("{VALUE}", $customfield["fieldtype"]=="jevcfhtml"?$customfield["value"]:nl2br($customfield["value"]), $outrow);
			$html .= $outrow;
		}
		$html .= $templatebottom;

		$row->customfields = $customfields;
		if (!$this->params->get("outputhtml", 1))
			return "";

		$row->customfieldsummary = $html;

		if (isset($row->hidedetail) && $row->hidedetail)
		{
			return "";
		}

		return $html;

	}

	function onDisplayCustomFieldsMultiRow(&$rows)
	{

		if (!$this->params->get("inlists", 0))
			return;

		if (count($rows) == 0)
			return;

		$ids = array();
		foreach ($rows as $row)
		{
			if (!in_array($row->evdet_id(), $ids)) {
				$ids[] = $row->evdet_id();
			}
		}

		$templatetop = $this->params->get("templatetop", "<table border='0'>");
		$templaterow = $this->params->get("templatebody", "<tr><td class='label'>{LABEL}</td><td>{VALUE}</td>");
		$templatebottom = $this->params->get("templatebottom", "</table>");

		// New parameterised fields
		$customdata = array();
		$hasparams = false;
		$template = $this->params->get("template", "");
		if ($template != "")
		{
			$xmlfile = plgJEventsJevcustomfields::getXMLFile($template);
			if (file_exists($xmlfile))
			{
				$user = JFactory::getUser();				
				$customdata = $this->getCachedCfData($ids, $user->id, $ids);

				if (is_null($customdata) || count($customdata) == 0)
				{
					return;
				}
			}
			else
			{
				return;
			}
		}
		else
		{
			return;
		}

		$user = JFactory::getUser();
		$staticparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
		$staticparams->setEvent(null);

		// Do we match on event detail or repeat id ?
		$needsSeparateRepeats = false;
		$needsSeparateRepeatFields = array();

		$params = clone $staticparams; // building from scratch each time is slow! so use a cloned object!
		$params->setEvent($rows[0]);

		$customfields = array();
		$groups = $params->getFieldsets();
		foreach ($groups as $group => $element)
		{
			$count = $params->getFieldCountByFieldSet($group);
			if ($count) $customfields = array_merge($customfields, $params->renderToBasicArray('params', $group));
		}
		foreach (array_keys($customfields) as $id)
		{
			$customfield = $customfields[$id];
			if (isset($customfield["separaterepeats"]) && $customfield["separaterepeats"]){
				$needsSeparateRepeats = true;
				$needsSeparateRepeatFields[$customfield["name"]]=$customfield;
			}
		}


		foreach ($rows as &$row)
		{
			// No need to process the same event detail multiple times.
			if (isset(self::$cachedParamsData[$needsSeparateRepeats ? "r".$row->rp_id() : $row->evdet_id()]))
			{
				$customfields = self::$cachedParamsData[$needsSeparateRepeats ? "r".$row->rp_id() : $row->evdet_id()];
			}
			else
			{

				$tempdata = array();
				foreach ($customdata as $data)
				{
					if ($needsSeparateRepeats && array_key_exists($data->name,$needsSeparateRepeatFields))
					{
						if ($data->value == $row->rp_id())
						{
							// must eliminate comma separated names
							$tempdata[str_replace(".", "_", $data->name)] = $data->value;
						}
					}
					else if ($data->evdet_id == $row->evdet_id())
					{
						// must eliminate comma separated names
						$tempdata[str_replace(".", "_", $data->name)] = $data->value;
					}
				}
				// building from scratch each time is slow! so use a cloned object!
				//$params = new JevCfForm($tempdata, $xmlfile, $row);
				$params = clone $staticparams;	
				$params->bind($tempdata);
				$params->setEvent($row);

				$customfields = array();
				$groups = $params->getFieldsets();
				foreach ($groups as $group => $element)
				{
					$count = $params->getFieldCountByFieldSet($group);
					if ($count) $customfields = array_merge($customfields, $params->renderToBasicArray('params', $group));
				}

				self::$cachedParamsData[$needsSeparateRepeats ? "r".$row->rp_id() : $row->evdet_id()] = $customfields;
			}

			$row->customfields = $customfields;
			$html = $templatetop;

			foreach (array_keys($customfields) as $id)
			{
				$customfield = $customfields[$id];
				if (isset($customfield["readaccess"]))
				{

					if (!$this->checkAccess($user, $customfield["readaccess"]))
					{
						unset($customfields[$id]);
						continue;
					}
				}
				else if (!$this->checkAccess($user, $customfield["access"]))
				{
					unset($customfields[$id]);
					continue;
				}

				if (!is_null($customfield["hiddenvalue"]) && trim($customfield["value"]) == $customfield["hiddenvalue"])
				{
					unset($customfields[$id]);
					continue;
				}

				if (is_null($customfield["hiddenvalue"]) && trim($customfield["value"]) == "")
					continue;

				$outrow = str_replace("{LABEL}", $customfield["label"], $templaterow);
				$outrow = str_replace("{VALUE}", $customfield["fieldtype"]=="jevcfhtml"?$customfield["value"]:nl2br($customfield["value"]), $outrow);

				$html .= $outrow;
			}
			$html .= $templatebottom;
			$row->customfields = $customfields;

			if ($this->params->get("outputhtml", 1))
				$row->customfieldsummary = $html;
			unset($row);
		}

		return;

	}

	static function fieldNameArray($layout = 'detail')
	{
		static $pluginparams;
		if (!isset($pluginparams))
		{
			$plugin = JPluginHelper::getPlugin("jevents", "jevcustomfields");
			$pluginparams = new JRegistry($plugin ? $plugin->params : null);
		}

		if ($layout == "edit")
		{
			$return = array();
			$return['group'] = JText::_("JEV_CUSTOM_FIELDS", true);

			$labels = array();
			$values = array();

			$template = $pluginparams->get("template", "");
			if ($template != "")
			{
				$xmlfile = plgJEventsJevcustomfields::getXMLFile($template);
				if (file_exists($xmlfile))
				{
					$params = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$params->setEvent(null);

					$customfields = array();
					$groups = $params->getFieldsets();
					$groupcount=0;
					foreach ($groups as $group => $element)
					{
						$groupcount++;
						$count = $params->getFieldCountByFieldSet($group);
						if ($count>0){
							$labels[] = JText::sprintf("JEV_FIELD_CUSTOM_FIELD_TAB_LABEL", $element->name, array("jsSafe"=>true));
							$values[] = "TABSTART#".$element->name;
							$labels[] = JText::sprintf("TAB_CUSTOM_FIELD_BODY", $element->name,  array("jsSafe"=>true));
							$values[] =   strtoupper(str_replace(" ", "_", $element->name));

	                                                        $customfields = array_merge($customfields, $params->renderToBasicArray('params', $group));

						}
					}

					if (count($customfields) > 0)
					{
						foreach ($customfields as $customfield)
						{
							// we only support single custom fields from default fieldset/group
							if ($customfield["group"]!="default") {
								continue;
							}

							// must not have : in the label otherwise regexp witll not work.
							$labels[] = str_replace(":", "", $customfield["label"]);
							$values[] = "customfield_".$customfield["name"];

							$label = JText::_("JEV_CUSTOM_FIELD_LABEL");
							if (strpos($label, '%') === false)
							{
								$label = "%s Label";
							}
							$labels[] = JText::sprintf($label, str_replace(":", "", $customfield["label"]));
							$values[] = "customfield_".$customfield["name"] . "_lbl";
						}
					}

				}
			}


			$return['values'] = $values;
			$return['labels'] = $labels;
			return $return;
		}

		// only offer in detail view unless showing in lists
		if ($layout != "detail" && !$pluginparams->get("inlists", 0))
		{
			return array();
		}

		$return = array();
		$return['group'] = JText::_("JEV_CUSTOM_FIELDS", true);

		$labels = array();
		$labels[] = JText::_("JEV_CUSTOM_FIELD_SUMMARY", true);
		$values = array();
		$values[] = "JEV_CUSTOM_SUMMARY";

		$template = $pluginparams->get("template", "");
		if ($template != "")
		{
			$xmlfile = plgJEventsJevcustomfields::getXMLFile($template);
			if (file_exists($xmlfile))
			{
				$params = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
				$params->setEvent(null);

				$customfields = array();
				$groups = $params->getFieldsets();
				foreach ($groups as $group => $element)
				{
					$count = $params->getFieldCountByFieldSet($group);
					if ($count) $customfields = array_merge($customfields, $params->renderToBasicArray('params', $group));
				}

				if (count($customfields) > 0)
				{
					foreach ($customfields as $customfield)
					{
						// must not have : in the label otherwise regexp witll not work.
						$labels[] = str_replace(":", "", $customfield["label"]);
						$values[] = $customfield["name"];

						$label = JText::_("JEV_CUSTOM_FIELD_LABEL");
						if (strpos($label, '%') === false)
						{
							$label = "%s Label";
						}
						$labels[] = JText::sprintf($label, str_replace(":", "", $customfield["label"]));
						$values[] = $customfield["name"] . "_lbl";
						
						if (isset($customfield["fieldnamearray"]) && count($customfield["fieldnamearray"])>0){
							for ($i=0;$i<count($customfield["fieldnamearray"]["labels"]);$i++ ) {
								$lab = $customfield["fieldnamearray"]["labels"][$i];
								$val = $customfield["fieldnamearray"]["values"][$i];
								
								$labels[] = str_replace(":", "", $customfield["label"]. "($lab)");
								$values[] = $customfield["name"] ."($val)";
								
								$labels[] = JText::sprintf($label, str_replace(":", "", $customfield["label"]. "($lab)"));
								$values[] = $customfield["name"] . "_lbl";
								
							}
						}
					}
				}
			}
		}

		$return['values'] = $values;
		$return['labels'] = $labels;

		return $return;

	}

	static function substitutefield($row, $code)
	{
		$thumbnail = false;
		if (strpos($code, "(THUMBNAIL)")>0){
			$thumbnail = true;
			$code = str_replace("(THUMBNAIL)", "", $code);
		}
		$popup = false;
		if (strpos($code, "(POPUP)")>0){
			$popup = true;
			$code = str_replace("(POPUP)", "", $code);
		}
		if (strlen($code) > 4 && strrpos($code, "_lbl") == strlen($code) - 4)
		{
			$code = substr($code, 0, strlen($code) - 4);
			if (isset($row->customfields) && array_key_exists($code, $row->customfields))
			{
				$user = JFactory::getUser();
				$customfield = $row->customfields[$code];
				// access is handled above

				return nl2br($row->customfields[$code]["label"]);
			}
		}
		if ($code == "JEV_CUSTOM_SUMMARY")
		{
			if (isset($row->customfieldsummary))
				return $row->customfieldsummary;
		}
		else if (isset($row->customfields) && array_key_exists($code, $row->customfields))
		{
			$user = JFactory::getUser();
			$customfield = $row->customfields[$code];
			// access is handled above
			
			if ($thumbnail && isset($row->customfields[$code]["fieldnamearray"]["output"]["THUMBNAIL"])){
				return $row->customfields[$code]["fieldnamearray"]["output"]["THUMBNAIL"];
			}
			if ($popup && isset($row->customfields[$code]["fieldnamearray"]["output"]["POPUP"])){
				return $row->customfields[$code]["fieldnamearray"]["output"]["POPUP"];
			}
			return $row->customfields[$code]["value"];
		}

		return "";

	}

	private static function getXMLFile($template)
	{
		$xmlfile = dirname(__FILE__) . "/customfields/templates/" . $template;
		return $xmlfile;

	}

	private function checkAccess($user, $access)
	{
		if (version_compare(JVERSION, "1.6.0", 'ge'))
		{
			$access = explode(",", $access);
			return count(array_intersect($access, JEVHelper::getAid($user, 'array'))) > 0;
		}
		else
		{
			return $access <= $user->aid;
		}

	}

	private function getCachedCfData($evid, $userid = -1)
	{
		$id = $evid;
		$multievent = false;
		if (is_array($evid)){
			$evid = implode(",", $evid);
			$multievent = true;
			$id = "a".$evid;
			if ($userid==-1){
				$userid=0;
			}
		}
		else if ($userid!=-1){
			$id = "u".$evid;
		}
		if (!array_key_exists($id, self::$cachedCfData))
		{
			$db = JFactory::getDBO();
			if ($userid!=-1){
				// if duplicated values for user specific fields use ordering to trick the system into giving us the one with the user id
				$db->setQuery($sql = "SELECT * FROM #__jev_customfields WHERE evdet_id IN (" . $evid . ") AND (user_id=0 OR user_id=" . $userid . ") ORDER BY user_id ASC ");				
			}
			else {
				$db->setQuery("SELECT * FROM #__jev_customfields WHERE evdet_id=" . $evid);
			}
			if ($multievent){
				self::$cachedCfData[$id] = $db->loadObjectList();
			}
			else {
				self::$cachedCfData[$id] = $db->loadObjectList('name');
			}
			if (is_null(self::$cachedCfData[$id])){
				return array();
			}
		}
		if ($multievent){
			return self::$cachedCfData[$id];
		}
		$data = array();
		foreach (self::$cachedCfData[$id] as $dataelem)
		{
			if (strpos($dataelem->name, ".") !== false)
			{
				$dataelem->name = str_replace(".", "_", $dataelem->name);
			}
			$data[$dataelem->name] = $dataelem->value;
			$data["fieldid_".$dataelem->name] = $dataelem->id;
		}
		return $data;
	}

}

