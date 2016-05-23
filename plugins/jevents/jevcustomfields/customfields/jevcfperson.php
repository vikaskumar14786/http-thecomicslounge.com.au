<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

class JFormFieldJevcfperson extends JFormField
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevcfperson';

	function __construct()
	{
		parent::__construct();

	}

	function getInput()
	{

		return $this->fetchElement($this->name, $this->value);

	}

	function fetchElement($name, $value)
	{
		if ($value == "")
		{
			$value = "-1";
		}
		$values = explode(",", $value);

		$plugin = JPluginHelper::getPlugin('jevents', 'jevpeople');
		if (!$plugin)
		{
			return "<strong>" . JText::_("Please_install_the_JEV_People_Addon") . "</strong>";
		}

		JHTML::script('plugins/jevents/jevcustomfields/customfields/people.js');

		JFactory::getLanguage()->load('plg_jevents_jevpeople', JPATH_ADMINISTRATOR);

		$this->params = new JRegistry($plugin->params);
		$compparams = JComponentHelper::getParams("com_jevpeople");

		$db = JFactory::getDBO();
		$sql = "SELECT * from #__jev_peopletypes as jpt";
		$types = $this->attribute("typeid");
		if (!empty($types))
		{
			$sql .= " WHERE jpt.type_id IN ($types)";
		}
		$db->setQuery($sql);
		$types = @$db->loadObjectList('type_id');

		$sql = "SELECT jp.pers_id, jp.title, jpt.title as typename, jpt.type_id as type_id , jpt.categories ,jpt.calendars  FROM #__jev_people as jp
		LEFT JOIN #__jev_peopletypes as jpt ON jpt.type_id = jp.type_id
		WHERE jp.pers_id IN (" . $value . ")
		ORDER BY jp.type_id, jp.ordering,jp.title";

		$db->setQuery($sql);
		$jpmlist = $db->loadObjectList();
		if (!is_array($jpmlist))
		{
			$jpmlist = array();
		}

		$cleanname = $this->id;

		JHTML::_('behavior.modal');
		JHTML::_('behavior.tooltip');
		$link = JRoute::_("index.php?option=com_jevpeople&task=people.select&tmpl=component");
		// need catid element to stop js error message
		$input = "<div style='margin-bottom:5px;'><span id='catid' ></span><ul id='sortablePeople_$cleanname' style='margin:0px;cursor:move;'>";
		foreach ($jpmlist as $jpm)
		{
			$pname = $jpm->title;
			$pid = $jpm->pers_id;
			$type = $jpm->typename;
			$pname = $pname . " ($type)";
			$input .= "<li class='sortablepers$pid' >$pname</li>";
		}
		$input .= "</ul>";

		$input .= '<select multiple="multiple" name="' . $name . '[]" id="custom_person_' . $cleanname . '" size="4" style="display:none" >';
		foreach ($jpmlist as $jpm)
		{
			$pname = $jpm->title;
			$pid = $jpm->pers_id;
			$type = $jpm->typename;
			$pname = $pname . " ($type)";
			$input .= "<option value='$pid' selected='selected' id='sortablepers" . $pid . "option'>$pname</option>";
		}
		$input .= "</select>";
		$input .= "</div>";


		$firstpass = true;
		$style = "";
		$script = "";
		foreach ($types as $type)
		{

			$showtype = true;

			$typelink = JRoute::_("index.php?option=com_jevpeople&task=people.select&tmpl=component&type_id=" . intval($type->type_id));
			$sp = "sp" . intval($type->type_id);
			$selectPerson = JText::sprintf("Select_by_type", $type->title);
			$selectPersonTip = JText::sprintf("Select_by_type_TIP", $type->title);
			$input .= '<div class="button2-left" style="cursor:move' . $style . '"><div class="blank"><a href="javascript:' . $sp . '.selectPerson(\'' . $typelink . '\');" title="' . $selectPerson . '::' . $selectPersonTip . '"  class="hasTip">' . $selectPerson . '</a></div></div>';
			$input .= "<img src='" . JURI::Root() . "administrator/images/publish_x.png' class='sortabletrash' id='trashimage' style='display:none;padding-right:2px;cursor:pointer;'/>";
			if ($firstpass)
			{
				$input .= "<script type='text/javascript'>
			peopleDeleteWarning='" . JText::_("JEV_REMOVE_PERSON_WARNING", true) . "';
			var jevpeople = {\n";
				$input .= "duplicateWarning : '" . JText::_("Already_Selected", true) . "'\n";
				$input .= "		}
				</script>";
				$firstpass = false;
			}
			$input .= "<script type='text/javascript'>
		    var $sp=new sortablePeopleClass();
			$sp.setup('$cleanname');
			typenamemap = \$merge(typenamemap, {'" . $type->title . "': $sp});
			</script>";
		}
		if ($style != "")
		{
			$document = JFactory::getDocument();
			$document->addStyleDeclaration($style);

			$this->setupCategorySpecificTypes($script);
		}


		return $input;

	}

	public function convertValue($value, $node)
	{
		if (empty($value))
		{
			return $value;
		}
		static $values;
		if (!isset($values))
		{
			$values = array();
		}
		static $typesdone;
		if (!isset($typesdone))
		{
			$typesdone = array();
		}
		$types = $this->attribute("typeid");
		if (!empty($types))
		{
			if (!isset($typesdone[$types]))
			{
				$typesdone[$types] = 1;
				$db = JFactory::getDBO();
				$sql = "SELECT jp.pers_id, jp.title, jpt.title as typename, jpt.type_id as type_id , jpt.categories ,jpt.calendars  FROM #__jev_people as jp
						LEFT JOIN #__jev_peopletypes as jpt ON jpt.type_id = jp.type_id";
				$sql .= " WHERE jpt.type_id IN ($types)";

				$db->setQuery($sql);
				$values = $values + $db->loadObjectList('pers_id');
			}
		}
		else
		{
			static $alldone;
			if (!isset($alldone))
			{
				$alldone = 1;
				$db = JFactory::getDBO();
				$sql = "SELECT jp.pers_id, jp.title, jpt.title as typename, jpt.type_id as type_id , jpt.categories ,jpt.calendars  FROM #__jev_people as jp
								LEFT JOIN #__jev_peopletypes as jpt ON jpt.type_id = jp.type_id";
				$db->setQuery($sql);
				$values = $db->loadObjectList('pers_id');
			}
		}
		$res = array();
		;
		foreach (explode(",", $value) as $val)
		{
			if (array_key_exists($val, $values))
			{
				$Itemid = JRequest::getInt("Itemid");

				$link = JRoute::_("index.php?option=com_jevpeople&task=people.detail&se=1&pers_id=" . $values[$val]->pers_id . "&Itemid=$Itemid");
				$res[] = "<a href='$link'>" . $values[$val]->title . "</a>";
			}
		}
		return implode(", ", $res);

	}

	public function attribute($attr)
	{
		$val = $this->element->attributes()->$attr;
		$val = !is_null($val) ? (string) $val : null;
		return $val;

	}

	/**
	 * Magic setter; allows us to set protected values
	 * @param string $name
	 * @return nothing
	 */
	public function setValue($value)
	{
		$this->value = $value;

	}

}