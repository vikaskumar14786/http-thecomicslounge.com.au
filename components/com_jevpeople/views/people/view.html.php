<?php
/**
 * copyright (C) 2008 JEV Systems Ltd - All rights reserved
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

include_once(JPATH_COMPONENT_ADMINISTRATOR . "/views/" . basename(dirname(__FILE__)) . "/" . basename(__FILE__));

/**
 * HTML View class for the component
 *
 * @static
 */
class FrontPeopleViewPeople extends AdminPeopleViewPeople
{

	function __construct($config = array())
	{
		include_once(JPATH_ADMINISTRATOR .  "/includes/toolbar.php");
		parent::__construct($config);

		// TODO find the active admin template
		JHtml::stylesheet('com_jevpeople/pagination.css', array(), true);
		JHTML::stylesheet("admin.css", JURI::root() . "components/com_jevpeople/assets/adminsim/css/");

	}

	function people($tpl = null)
	{
		$document = JFactory::getDocument();
		//JHTML::stylesheet("general.css",JURI::root()."administrator/templates/khepri/css/");
		JHtml::stylesheet('com_jevpeople/pagination.css', array(), true);
		//Check for JEvents Custom CSS file
		if (JFile::exists(JPATH_SITE . "/components/com_jevents/assets/css/jevcustom.css"))
		{
			JEVHelper::stylesheet('jevcustom.css', 'components/' . JEV_COM_COMPONENT . '/assets/css/');
		}
		// make sure sorting JS is loaded
		$user =  JFactory::getUser();
		if (!$user->get('id') && !version_compare(JVERSION, "1.7.0", 'ge'))
		{
			JHTML::script("joomla.javascript.js", JURI::base() . 'includes/js/');
		}

		JLoader::register('JEventsHTML', JPATH_SITE . "/components/com_jevents/libraries/jeventshtml.php");

		$mainframe = JFactory::getApplication();
		$option = JRequest::getCmd("option");
        JHtml::stylesheet('com_jevpeople/jevpeople.css', array(), true);

		$db = JFactory::getDBO();
		$uri =  JFactory::getURI();

		$filter_state = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_state', 'filter_state', '', 'word');
		$filter_order = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_order', 'filter_order', 'pers.ordering', 'cmd');
		$filter_order_Dir = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_order_Dir', 'filter_order_Dir', '', 'word');
		$filter_catid = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_filter_catid', 'filter_catid', 0, 'int');
		$search = JFactory::getApplication()->getUserStateFromRequest($option . 'pers_search', 'search', '', 'string');
		$search = JString::strtolower($search);

		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;

		$compparams = JComponentHelper::getParams("com_jevpeople");

		$model =  $this->getModel();
		$model->setState("limitstart", JRequest::getInt("limitstart", 0));
		$total = $model->getPublicTotal();
		$items = $model->getPublicData();
		$pagination = $model->getPublicPagination();

		$lists['typefilter'] = $this->typeFilter(false);

		// state filter
		$lists['state'] = JHTML::_('grid.state', $filter_state);

		// if we filtered the menu then we should filter the cats!
		$types = $compparams->get("type", "");
		if ($types != "")
		{
			if (!is_array($types))
			{
				$types = array($types);
			}
			$javascript = 'onchange="document.adminForm.submit();"';
			$lists['catid'] = $this->buildCategorySelect(intval($filter_catid), $javascript, "", true, false, 0, 'filter_catid', "com_jevpeople", $types);
			$lists['catid'] = str_replace(JText::_('JEV_EVENT_ALLCAT'), JText::_('ALL_CATEGORIES'), $lists['catid']);
		}
		else
		{
			$firsttype = array($this->getFirstType());
			$typefilter = intval(JFactory::getApplication()->getUserStateFromRequest("type_type_id", 'type_id', $firsttype));

			$javascript = 'onchange="document.adminForm.submit();"';
			$lists['catid'] = $this->buildCategorySelect(intval($filter_catid), $javascript, "", true, false, 0, 'filter_catid', "com_jevpeople", $firsttype);
			$lists['catid'] = str_replace(JText::_('JEV_EVENT_ALLCAT'), JText::_('ALL_CATEGORIES'), $lists['catid']);
		}


		// search filter
		$lists['search'] = $search;

		// check if person has any events	- a very crude test
		jimport("joomla.utilities.date");
		$startdate = new JDate("-" . $compparams->get("checkeventbefore", 30) . " days");
		$enddate = new JDate("+" . $compparams->get("checkeventafter", 30) . " days");

		foreach ($items as &$item)
		{
			if ($compparams->get("checkevents",1)){			
				$item->hasEvents = $model->hasEvents($item->pers_id, $startdate->toSql(), $enddate->toSql());
			}
			else {
				$item->hasEvents = 1;
			}
			unset($item);
		}

		static $typedata;
		if (!isset($typedata)){
			$db = JFactory::getDBO();
			$db->setQuery("SELECT * FROM #__jev_peopletypes");
			$typedata  = $db->loadObjectList('type_id');
		}

		// New custom fields
		// first pass through to check if we offer any filters
		$activefilters = array();
		$compparams = JComponentHelper::getParams("com_jevpeople");
		$cftemplate = $compparams->get("template", "");
		if ($cftemplate != "" )
		{
			$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $cftemplate;
			if (file_exists($xmlfile))
			{
				$jcfparams = JevCfForm::getInstance("com_jevent.customfields.common",$xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
				$fieldsets= $jcfparams->getFieldsets();
				foreach ($fieldsets as $fieldset => $element)
				{
					$fields = $jcfparams->getFieldset($fieldset);
					foreach ($fields as $field){
						if ($field->attribute("filter")){
							$activefilters[$field->fieldname]=$field;
						}
					}
				}
			}
		}
		// Now make sure all the types with their own custom fields match the same filters
		$typesChecked = array();
		foreach ($items as $item)
		{
			if (count($activefilters)==0){
				continue;
			}
			if (in_array($item->type_id, $typesChecked)) {
				continue;
			}
			$typesChecked[] = $item->type_id;
			$compparams = JComponentHelper::getParams("com_jevpeople");
			$typeCfTemplate = false;
			if (isset($typedata[$item->type_id]->typetemplate) && $typedata[$item->type_id]->typetemplate!=""){
				$typeCfTemplate =$typedata[$item->type_id]->typetemplate;
			}
			if ($typeCfTemplate){
				$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $typeCfTemplate;
				if (file_exists($xmlfile))
				{
					$activeTypeFilters = array();
					$jcfparams = JevCfForm::getInstance("com_jevent.customfields.".$item->type_id, $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$fieldsets= $jcfparams->getFieldsets();
					foreach ($fieldsets as $fieldset => $element)
					{
						$fields = $jcfparams->getFieldset($fieldset);
						foreach ($fields as $field){
							if ($field->attribute("filter")){
								$activeTypeFilters[$field->fieldname]=$field;
							}
						}
					}
					$activefilters = array_intersect_key( $activefilters, $activeTypeFilters);
				}
			}
		}
		// get the filter HTML
		if (count($activefilters)>0){
			JEVHelper::script("mod_jevents_filter.js", "modules/mod_jevents_filter/", true);
			// Fool the custom field filter to thinking the custom field filter is visible (which it is in reality!)
			$registry	= JRegistry::getInstance("jevents");
			$indexedvisiblefilters = $registry->get("indexedvisiblefilters",false);
			$registry->set("indexedvisiblefilters",array("customfield"));

			$filterElements = array();
			foreach ($activefilters as $fieldname => $field){
				if (method_exists($field, "constructFilter"))
					$field->constructFilter($field);
				if (!$field->attribute("filter") || !method_exists($field, "createFilterHTML")){
					continue;
				}
				$filterElements[] = $field->createFilterHTML();
			}
			// reset the visible filter value in tjhe registry
			$registry->set("indexedvisiblefilters",$indexedvisiblefilters);
			if (count($filterElements)>0){
				$lists["customfield"]=$filterElements;
			}

		}

		$db = JFactory::getDBO();
		foreach ($items as &$item)
		{
			$compparams = JComponentHelper::getParams("com_jevpeople");
			$cftemplate = $compparams->get("template", "");
			if (isset($typedata[$item->type_id]->typetemplate) && $typedata[$item->type_id]->typetemplate!=""){
				$cftemplate =$typedata[$item->type_id]->typetemplate;
			}

			if ($cftemplate != "" && $compparams->get("custinlist"))
			{
				$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $cftemplate;
				if (file_exists($xmlfile))
				{
					$db->setQuery("SELECT * FROM #__jev_customfields2 WHERE target_id=" . intval($item->pers_id) . " AND targettype='com_jevpeople'");
					$cfdata  = $db->loadObjectList('name');
					$customdata = array();
					foreach ($cfdata as $dataelem)
					{
						if (strpos($dataelem->name, ".") !== false)
						{
							$dataelem->name = str_replace(".", "_", $dataelem->name);
						}
						$customdata[$dataelem->name] = $dataelem->value;
					}

					$jcfparams = JevCfForm::getInstance("com_jevent.customfields.".$item->type_id, $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$jcfparams->bind($customdata);
					$jcfparams->setEvent($item);

					$customfields = array();
					$groups = $jcfparams->getFieldsets();
					foreach ($groups as $group => $element)
					{
						if ($jcfparams->getFieldCountByFieldSet($group))
						{
							$customfields = array_merge($customfields, $jcfparams->renderToBasicArray('params', $group));
						}
					}
					$item->customfields = $customfields;
					unset($item);
				}
			}
		}

		$user = JFactory::getUser();
		$this->assignRef('user', $user);
		$this->assignRef('items', $items);
		$this->assignRef('lists', $lists);
		$this->assignRef('pagination', $pagination);

		if ($compparams->get('menu-meta_description'))
		{
			$document->setDescription($compparams->get('menu-meta_description'));
		}

		if ($compparams->get('menu-meta_keywords'))
		{
			$document->setMetadata('keywords', $compparams->get('menu-meta_keywords'));
		}

		if ($compparams->get('robots'))
		{
			$document->setMetadata('robots', $compparams->get('robots'));
		}

		parent::display($tpl);

	}

	function select($tpl = null)
	{
		// Make sure form stuff is loaded
		$user =  JFactory::getUser();
		if (!$user->get('id') && !version_compare(JVERSION, "1.7.0", 'ge'))
		{
			JHtml::script(JURI::base() . "includes/js/joomla.javascript.js");
		}

		JLoader::register('JToolbarHelper', JPATH_ADMINISTRATOR . "/" . 'includes' . "/" . 'toolbar.php');
		JHtml::stylesheet(JURI::root() . "components/com_jevpeople/assets/adminsim/css/admin.css");
		JHtml::stylesheet('com_jevpeople/pagination.css', array(), true);

		$model =  $this->getModel();
		$model->setState("limitstart", JRequest::getInt("limitstart", 0));

		parent::select($tpl);

	}

	/**
	 * Build HTML selection list of categories
	 *
	 * @param int $catid				Selected catid
	 * @param string $args				Additional HTML attributes for the <select> tag
	 * @param string $catidList			Restriction list of categories
	 * @param boolean $with_unpublished	Set true to build list with unpublished categories
	 * @param boolean $require_sel		First entry: true = Choose one category, false = All categories
	 * @param int $catidtop				Top level category ancestor
	 */
	function buildCategorySelect($catid, $args, $catidList = null, $with_unpublished = false, $require_sel = false, $catidtop = 0, $fieldname = "catid", $section, $types = array())
	{

		// need to declare this because of bug in Joomla JHtml::_('select.options', on content pages - it loade the WRONG CLASS!
		if (version_compare(JVERSION, "3.0.0", 'ge'))
		{
			include_once(JPATH_SITE . "/libraries/cms/html/category.php");
		}
		else
		{
			include_once(JPATH_SITE . "/libraries/joomla/html/html/category.php");
		}
		$t_first_entry = ($require_sel) ? JText::_('JEV_EVENT_CHOOSE_CATEG') : JText::_('JEV_EVENT_ALLCAT');
		$options = $this->categoryOptions($section, $types);
		if (count($options) == 0)
			return "";
		ob_start();
		if ($catidList != null)
		{
			$cats = explode(',', $catidList);
			$count = count($options);
			for ($o = 0; $o < $count; $o++)
			{
				if (!in_array($options[$o]->value, $cats))
				{
					unset($options[$o]);
				}
			}
			$options = array_values($options);
		}
		?>
		<select name="<?php echo $fieldname; ?>" id="<?php echo $fieldname; ?>" <?php echo $args; ?> >
			<option value=""><?php echo $t_first_entry; ?></option>
			<?php echo JHtml::_('select.options', $options, 'value', 'text', $catid); ?>
		</select>
		<?php
		return ob_get_clean();
				
	}

	function loadedFromTemplate($template_name, $person, $mask = false, $layout = "detail")
	{

		$db = JFactory::getDBO();
		// find published template
		static $templates;
		if (!isset($templates))
		{
			$templates = array();
		}
		if (!array_key_exists($template_name, $templates))
		{
			$db->setQuery("SELECT * FROM #__jev_defaults WHERE state=1 AND name= " . $db->Quote($template_name) . " AND ".'language in ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
			$templates[$template_name] = $db->loadObjectList("language");
			if (isset($templates[$template_name][JFactory::getLanguage()->getTag()])){
				$templates[$template_name] = $templates[$template_name][JFactory::getLanguage()->getTag()];
			}
			else if (isset($templates[$template_name]["*"])){
				$templates[$template_name] =$templates[$template_name]["*"];
			}
			else if (is_array($templates[$template_name]) && count($templates[$template_name])>0)
			{
				$templates[$template_name] = current($templates[$template_name]);
			}
			else {
				$templates[$template_name] = null;
			}
		}

		if (is_null($templates[$template_name]) || !$templates[$template_name] || $templates[$template_name]->value == "")
			return false;

		$template = $templates[$template_name];

		static $typedata;
		if (!isset($typedata)){
			$db = JFactory::getDBO();
			$db->setQuery("SELECT * FROM #__jev_peopletypes");
			$typedata  = $db->loadObjectList('type_id');
		}

		$templateParams = new JRegistry($template->params);
		$showAddress = $templateParams->get("showaddress", "0");

		$jevparams = JComponentHelper::getParams(JEV_COM_COMPONENT);
		$peopleList = array();
		$peopleList [] = $person;

		// People fields		
		$jevplugin = JPluginHelper::getPlugin("jevents", "jevpeople");
		JPluginHelper::importPlugin("jevents", $jevplugin->name);
		$jevpluginparams = new JRegistry($jevplugin->params);
		// fill in any missing fields
		$detailpopup = $jevpluginparams->get("detailpopup", 1);
		$map = '<div id="gmapppl" style="width:' . $jevpluginparams->get("gwidth", 200) . 'px; height:' . $jevpluginparams->get("gheight", 150) . 'px;overflow:hidden;"></div>';

		$Itemid = JRequest::getInt("Itemid");
		$document = JFactory::getDocument();
		$compparams = JComponentHelper::getParams("com_jevpeople");
		if ($compparams->get('menu-meta_description'))
		{
			$document->setDescription($compparams->get('menu-meta_description'));
		}

		if ($compparams->get('menu-meta_keywords'))
		{
			$document->setMetadata('keywords', $compparams->get('menu-meta_keywords'));
		}

		if ($compparams->get('robots'))
		{
			$document->setMetadata('robots', $compparams->get('robots'));
		}

		$showMap = $compparams->get("showmap", "1");

		$classname = "plgJevents" . ucfirst($jevplugin->name);

		$peopleCount = sizeof($peopleList);


		foreach ($peopleList as $person)
		{
			$template_value = $template->value;
			// strip carriage returns other wise the preg replace doesn;y work - needed because wysiwyg editor may add the carriage return in the template field
			$template_value = str_replace("\r", '', $template_value);
			$template_value = str_replace("\n", '', $template_value);
			// non greedy replacement - because of the ?
			$template_value = preg_replace_callback('|{{.*?}}|', array($this, 'cleanLabels'), $template_value);

			$person->map = $map;
			$pers_id = $person->pers_id;

			if ($detailpopup)
			{
				$locurl = JRoute::_("index.php?option=com_jevpeople&task=people.detail&tmpl=component&pers_id=$pers_id&title=" . JApplication::stringURLSafe($person->title));
			}
			else
			{
				$locurl = JRoute::_("index.php?option=com_jevpeople&task=people.detail&se=1&pers_id=$pers_id&title=" . JApplication::stringURLSafe($person->title));
			}

			$pwidth = $jevpluginparams->get("pwidth", "750");
			$pheight = $jevpluginparams->get("pheight", "500");
			if ($detailpopup)
			{
				$person->linkstart = "<a href='$locurl' class='modal' rel='{handler:\"iframe\",\"size\": {\"x\": $pwidth, \"y\": $pheight}}'>";
			}
			else
			{
				$person->linkstart = "<a href='$locurl'>";
			}

			// New custom fields
			if (!isset($person->customfields))
			{
				$compparams = JComponentHelper::getParams("com_jevpeople");
				$cftemplate = $compparams->get("template", "");
				if (isset($typedata[$person->type_id]->typetemplate) && $typedata[$person->type_id]->typetemplate!=""){
					$cftemplate =$typedata[$person->type_id]->typetemplate;
				}
				if ($cftemplate != "")
				{
					$html = "";
					$xmlfile = JPATH_SITE . "/plugins/jevents/jevcustomfields/customfields/templates/" . $cftemplate;
					$customfields = array();
					$allfields = array();
					if (file_exists($xmlfile))
					{
						$db = JFactory::getDBO();
						$db->setQuery("SELECT * FROM #__jev_customfields2 WHERE target_id=" . intval($person->pers_id) . " AND targettype='com_jevpeople'");
						$cfdata  = $db->loadObjectList('name');
						$customdata = array();
						foreach ($cfdata as $dataelem)
						{
							if (strpos($dataelem->name, ".") !== false)
							{
								$dataelem->name = str_replace(".", "_", $dataelem->name);
							}
							$customdata[$dataelem->name] = $dataelem->value;
						}

						$jcfparams = JevCfForm::getInstance("com_jevent.customfields.".$person->type_id, $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
						$jcfparams->bind($customdata);
						$jcfparams->setEvent($person);
						
						$allfields = array();
						$groups = $jcfparams->getFieldsets();
						foreach ($groups as $group => $element)
						{
							if ($jcfparams->getFieldCountByFieldSet($group))
							{
								$allfields = array_merge($allfields, $jcfparams->renderToBasicArray('params', $group));
							}
						}

					}

					$user = JFactory::getUser();
					foreach ($allfields as $key=>$customfield)
					{
						$cfaccess = $customfield["access"];
						if (version_compare(JVERSION, "1.6.0", 'ge'))
						{
							$cfaccess = explode(",", $cfaccess);
							if (count(array_intersect($cfaccess, JEVHelper::getAid($user, 'array'))) == 0)
								continue;
						}
						else
						{
							if (intval($cfaccess) > $user->aid)
								continue;
						}
						$customfields[$key] = $customfield;

					}
					$person->customfields = $customfields;

				}
			}

			$event = new stdClass();
			$event->_jevperson = $person;

			// now replace the fields
			$search = array();
			$replace = array();
			$blank = array();

			if (is_callable(array($classname, "substitutefield")))
			{
				$fieldNameArray = call_user_func(array($classname, "fieldNameArray"), $layout, $showAddress, $person->type_id);
				$fieldNameArray2 = call_user_func(array($classname, "fieldNameArray"), 'detail', $showAddress, $person->type_id);
				if (isset($fieldNameArray["values"]) && isset($fieldNameArray2["values"]))
				{
					$fieldNameArray["values"] = array_merge($fieldNameArray["values"], $fieldNameArray2["values"]);
					$fieldNameArray["values"] = array_unique($fieldNameArray["values"]);
					foreach ($fieldNameArray["values"] as $fieldname)
					{
						$search[] = "{{" . $fieldname . "}}";
						$replace[] = call_user_func(array($classname, "substitutefield"), $event, $fieldname);
						if (is_callable(array($classname, "blankfield")))
						{
							$blank[] = call_user_func(array($classname, "blankfield"), $event, $fieldname);
						}
						else
						{
							$blank[] = "";
						}
					}
				}
			}

			// word counts etc.
			for ($s = 0; $s < count($search); $s++)
			{
				if (strpos($search[$s], "TRUNCATED_DESC:") > 0)
				{
					global $tempreplace, $tempevent, $tempsearch;
					$tempreplace = $replace[$s];
					$tempsearch = $search[$s];
					$tempevent = $event;
					$template_value = preg_replace_callback("|$tempsearch|", array($this, 'jevSpecialHandling'), $template_value);
				}
			}

			for ($s = 0; $s < count($search); $s++)
			{
				global $tempreplace, $tempevent, $tempsearch, $tempblank;
				$tempreplace = $replace[$s];
				$tempblank = $blank[$s];
				$tempsearch = str_replace("}}", "#", $search[$s]);
				$tempevent = $event;
				$template_value = preg_replace_callback("|$tempsearch(.+?)}}|", array($this, 'jevSpecialHandling2'), $template_value);
			}

			$template_value = str_replace($search, $replace, $template_value);

			// non greedy replacement - because of the ?
			$template_value = preg_replace_callback('|{{.*?}}|', array($this, 'cleanUnpublished'), $template_value);

			$peopleTemplate [] = $template_value;
		}

		//$final_template = "<form action=\"" . JRoute::_("index.php?option=com_jevpeople&task=people.people&layout=people_blog&Itemid=$Itemid") . "\" method=\"post\" name=\"adminForm\">";
		$final_template = $template_value;
		//$final_template .="</form>";

		// call content plugins
		JPluginHelper::importPlugin('content');
		$tmprow = new stdClass();
		$tmprow->text = $final_template;
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('onContentPrepare', array('com_jevents', &$tmprow, &$params, 0));
		$final_template = $tmprow->text;

		echo $final_template;

		return true;

	}

	function cleanLabels($matches)
	{
		if (count($matches) == 1)
		{
			$parts = explode(":", $matches[0]);
			if (count($parts) > 0)
			{
				if (strpos($matches[0], "://") > 0)
				{
					return "{{" . $parts[count($parts) - 1];
				}
				array_shift($parts);
				return "{{" . implode(":", $parts);
			}
			return "";
		}
		return "";

	}

	function cleanUnpublished($matches)
	{
		if (count($matches) == 1)
		{
			return "";
		}
		return $matches;

	}

	function jevSpecialHandling($matches)
	{
		if (count($matches) == 1 && strpos($matches[0], ":") > 0)
		{
			global $tempreplace, $tempevent, $tempsearch;
			$parts = explode(":", $matches[0]);
			if (count($parts) == 2)
			{
				$wordcount = intval(str_replace("}}", "", $parts[1]));
				$value = strip_tags($tempreplace);

				$value = str_replace("  ", " ", $value);
				$words = explode(" ", $value);
				if (count($words) > $wordcount)
				{
					$words = array_slice($words, 0, $wordcount);
					$words[] = " ...";
				}
				return implode(" ", $words);
			}
			else
			{
				return $matches[0];
			}
		}
		else if (count($matches) == 1)
			return $matches[0];

	}

	function jevSpecialHandling2($matches)
	{
		if (count($matches) == 2 && strpos($matches[0], "#") > 0)
		{
			global $tempreplace, $tempevent, $tempsearch, $tempblank;
			$parts = explode("#", $matches[1]);
			if ($tempreplace == $tempblank)
			{
				if (count($parts) == 2)
				{
					return $parts[1];
				}
				else
					return "";
			}
			else if (count($parts) >= 1)
			{
				return sprintf($parts[0], $tempreplace);
			}
		}
		else
			return "";

	}

}
