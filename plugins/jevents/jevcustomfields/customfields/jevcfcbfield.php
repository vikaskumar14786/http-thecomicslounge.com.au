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

jimport('joomla.form.formfield');

class JFormFieldJevcfcbfield extends JFormField
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevcfcbfield';

	protected function getInput()
	{
		jimport("joomla.filesystem.folder");
		if (Jfolder::exists(JPATH_ADMINISTRATOR."/components/com_comprofiler")){
			return "";
		}
		$value = $this->value;
		if ($this->event && method_exists($this->event , "created_by") && $this->event->created_by())
		{
			$creator = $this->event->created_by();
			$user =  JEVHelper::getUser($creator);
		}
		else if ($this->event  && isset($this->event->pers_id)  && isset($this->event->linktouser)){
			$creator = intval($this->event->linktouser);
			$user =  JEVHelper::getUser($creator);
		}
		else
		{
			$user =  JFactory::getUser();
			$creator = $user->id;
		}

		$html = "";
		$lang = JFactory::getLanguage();
		$baseurl = JURI::root();

		if ($creator == 0)
		{
			return "'";
		}
		else
		{
			if (!isset($user->cbProfile))
			{
				$db = JFactory::getDBO();
				$user->cbProfile = new stdClass();
				$db->setQuery('SELECT cbprofile.*, user.name, user.username, user.lastvisitDate, user.registerDate ' .
						'FROM #__comprofiler AS cbprofile ' .
						'LEFT JOIN #__users AS user ON ( user.id = cbprofile.user_id ) ' .
						' WHERE ( cbprofile.user_id = \'' . $user->id . '\' ) ');
				$user->cbProfile = $db->loadObject();
				if (is_null($user->cbProfile)) $user->cbProfile = false;
			}
			// this chops off the leading cb_ part 
			//$field = $this->fieldname;
			// so go to the raw data
			$field = $this->element->attributes()->fieldname;
			if ($user->cbProfile && isset($user->cbProfile->$field))
			{
				return $user->cbProfile->$field;
			}
			else if (isset($user->$field))
			{
				return $user->$field;
			}
			else {
				return "";
			}
		}
	}
	
	
	public function convertValue($value, $node)
	{

		if ($this->event && method_exists($this->event , "created_by") && $this->event->created_by())
		{
			$creator = $this->event->created_by();
			$user =  JEVHelper::getUser($creator);
		}
		else if ($this->event  && isset($this->event->pers_id)  && isset($this->event->linktouser)){
			$creator = intval($this->event->linktouser);
			$user =  JEVHelper::getUser($creator);
		}
		else
		{
			$user =  JFactory::getUser();
			$creator = $user->id;
		}

		$html = "";
		$lang = JFactory::getLanguage();
		$baseurl = JURI::root();

		if ($creator == 0)
		{
			return "'";
		}
		else
		{			
			if (!isset($user->cbProfile))
			{
				$db = JFactory::getDBO();
				$user->cbProfile = new stdClass();
				$db->setQuery('SELECT cbprofile.*, user.name, user.username, user.lastvisitDate, user.registerDate ' .
						'FROM #__comprofiler AS cbprofile ' .
						'LEFT JOIN #__users AS user ON ( user.id = cbprofile.user_id ) ' .
						' WHERE ( cbprofile.user_id = \'' . $user->id . '\' ) ');
				$user->cbProfile = $db->loadObject();
				if (is_null($user->cbProfile)) $user->cbProfile = false;
			}
			$field = $node->attribute("fieldname");
			if ($user->cbProfile && isset($user->cbProfile->$field))
			{
				return $user->cbProfile->$field;
			}
			else if (isset($user->$field))
			{
				return $user->$field;
			}
			else
			{
				return "";
			}
		}

	}

	public function constructFilter($node)
	{
		$this->node = $node;
		$this->filterType = "cbf";
		$this->filterLabel = is_null($this->attribute("filterlabel"))?$this->attribute("label"):$this->attribute("filterlabel");
		$this->filterNullValue = "";
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

		//$this->filter_value = JRequest::getString($this->filterType.'_fv', $this->filterNullValue );

	}

	public function createJoinFilter()
	{
		if (is_string($this->filter_value) && trim($this->filter_value) == $this->filterNullValue)
			return "";
		$join = " #__comprofiler AS cbf ON ev.created_by=cbf.user_id";
		$db = JFactory::getDBO();
		$filter =" $this->map.value LIKE (" . $db->Quote($this->filter_value . "%") .")";
		return $join . " AND ". $filter;
	}

	public function createFilter()
	{
		if (is_string($this->filter_value) && trim($this->filter_value) == $this->filterNullValue)
			return "";
		return "$this->map.id IS NOT NULL";
	}

	public function setSearchKeywords(&$extrajoin)
	{
		if ( $this->attribute('searchable'))
		{
			$db = JFactory::getDBO();
			if (strpos($extrajoin, " #__comprofiler AS $this->map ON ev.created_by=$this->map.user_id") === false)
			{
				$extrajoin .= "\nLEFT JOIN #__comprofiler AS $this->map ON ev.created_by=$this->map.user_id";
			}

			$field = $this->element->attributes()->fieldname;
			return " $this->map.$field LIKE (" . $db->Quote('###' . "%") . ")";
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