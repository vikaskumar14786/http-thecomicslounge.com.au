<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: Search.php 1410 2009-04-09 08:13:54Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined('_JEXEC') or die( 'No Direct Access' );

// searches location of event
class jevPeoplemultilookupFilter extends jevFilter
{
	// needed to stop lazy language loading from throwing a php notice
	public $_name = "plg_jevents_jevpeople";
	public $_type = "jevents";
	
	function __construct($tablename, $filterfield, $isstring=true){
		JFactory::getLanguage()->load( 'plg_jevents_jevpeople',JPATH_ADMINISTRATOR );

		$this->filterType="pmlkup";
		$this->filterLabel=JText::_( 'SEARCH_BY_PERSON' );
		$this->filterNullValue=array(0);
		parent::__construct($tablename,$filterfield, true);

		// Should these be ignored?
		$reg = JFactory::getConfig();
		$modparams = $reg->get("jev.modparams",false);
		if ($modparams && $modparams->get("ignorefiltermodule",false)){
			$this->filter_value = $this->filterNullValue;
		}
		
		JArrayHelper::toInteger($this->filter_value);
		
	}

	function _createFilter($prefix=""){
		if (!$this->filterField ) return "";
		if ($this->filter_value==$this->filterNullValue) return "";

		$this->needsgroupby = true;
		return " pers.pers_id IN (". implode(",",$this->filter_value).")";
	}

	// No need join  the people is always joined
	// function _createJoinFilter($prefix=""){}

	function _createfilterHTML(){

		if (!$this->filterField) return "";

		// Find the accessible locations
		$user = JFactory::getUser();
		$db = JFactory::getDBO();
				
		$query = "SELECT p.pers_id as value, p.title as text, t.title as ptype FROM #__jev_people as p ";
		$query .= "LEFT JOIN #__jev_peopletypes as t ON t.type_id=p.type_id ";
		$query .= "WHERE p.published=1 AND p.access  ". (version_compare(JVERSION, '1.6.0', '>=') ?  ' IN (' . JEVHelper::getAid($user) . ')'  :  ' <=  ' . JEVHelper::getAid($user)) ." ORDER BY t.title ASC, p.title ASC";
		$db->setQuery( $query );
		$people = $db->loadObjectList();

   		$list[] = JHTML::_( 'select.option', 0, JText::_( 'SEARCH_BY_PERSON' ));
   		// count the types first
   		$types=array();
   		foreach ($people as $person) {
   			if (!in_array($person->ptype,$types)) $types[]=$person->ptype;
   		}
   		if (count($types)==1){
   			$list = array_merge($list, $people);
   		}
   		else {
   			$type = "";
   			$typecount = 1;
   			foreach ($people as $person){
   				if ($person->ptype!=$type){   					
   					$list[] = JHTML::_( 'select.option', -$typecount, $person->ptype,"value","text", true);
   					$type = $person->ptype;
   					$typecount++;
   				}
   				$list[] = JHTML::_( 'select.option', $person->value, " - ".$person->text);
   			}
   		}
  
		$filterList=array();
		$filterList["title"]="<label class='evppllkup_label' for='".$this->filterType."_fv'>".$this->filterLabel."</label>";
		$size = min(array(count($list),5));
  		$filterList["html"] = JHTML::_( 'select.genericlist', $list, $this->filterType."_fv[]", "id='".$this->filterType."_fv' class='evppllkup' multiple='multiple' size='$size'", 'value', 'text', $this->filter_value);

		$script = "try {JeventsFilters.filters.push({id:'".$this->filterType."_fv',value:0});} catch (e) {}";
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);
		
		return $filterList;

	}
}