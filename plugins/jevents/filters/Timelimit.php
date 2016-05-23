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

defined('_JEXEC') or die( 'No Direct Access' );

// Event repeat timelimit fitler
class jevTimelimitFilter extends jevFilter
{

	function __construct($tablename, $filterfield, $isstring=true){
		$this->fieldset=true;

		$this->filterType="timelimit";
		$this->dmap = "rpt";
		parent::__construct($tablename,$filterfield, true);
	}

	function _createFilter($prefix=""){
		if (!$this->filterField ) return "";

		$reg = JFactory::getConfig();
		$modparams = $reg->get("jev.modparams",false);
		if ($modparams && $modparams->get("ignorefiltermodule",false)){
			return "";
		}
		
		// get plugin params
		$plugin = JPluginHelper::getPlugin('jevents', 'jevtimelimit');
		if (!$plugin) return "";
		$params = new JRegistry($plugin->params);

		if (intval($params->get("override",0)) && $this->filter_value==1){
			return "";
		}

		$cats = $params->get("cats",false);		
		$filter = "";
		if (!is_numeric($params->get("past",-1))){
			$past = $params->get("past","");
			$future = $params->get("future","");
			if ($past!="" && $future!=""){
				$pastdate = new JevDate($past);
				$pastdate = $pastdate->toFormat("%Y-%m-%d %H:%M:%S");
				$futuredate = new JevDate($future);
				$futuredate = $futuredate->toFormat("%Y-%m-%d %H:%M:%S");
				$extrawhere[] = "(".$this->dmap.".endrepeat>='$pastdate' AND ".$this->dmap.".startrepeat<='$futuredate')";
			}
			else if ($past!=""){
				$pastdate = new JevDate($past);
				$pastdate = $pastdate->toFormat("%Y-%m-%d %H:%M:%S");
				$extrawhere[] = $this->dmap.".endrepeat>='$pastdate'";
			}
			else if ($future!="") {
				$futuredate = new JevDate($future);
				$futuredate = $futuredate->toFormat("%Y-%m-%d %H:%M:%S");
				$extrawhere[] = $this->dmap.".startrepeat<='$futuredate'";
			}				
		}
		else {
			$past = intval($params->get("past",-1));
			$future = intval($params->get("future",-1));
			if ($past>=0 && $future>=0){
				$pastdate = new JevDate("-$past days");
				$pastdate = $pastdate->toFormat("%Y-%m-%d 00:00:00");
				$futuredate = new JevDate("$future days");
				$futuredate = $futuredate->toFormat("%Y-%m-%d 23:59:59");
				$filter = "(".$this->dmap.".endrepeat>='$pastdate' AND ".$this->dmap.".startrepeat<='$futuredate')";
			}
			else if ($past>=0){
				$pastdate = new JevDate("-$past days");
				$pastdate = $pastdate->toFormat("%Y-%m-%d 00:00:00");
				$filter = $this->dmap.".endrepeat>='$pastdate'";
			}
			else if ($future>=0) {
				$futuredate = new JevDate("$future days");
				$futuredate = $futuredate->toFormat("%Y-%m-%d 23:59:59");
				$filter = $this->dmap.".startrepeat<='$futuredate'";
			}
		}

		// restricted to specific categories only?
		if ($cats && count($cats)>0){
			$compparams = JComponentHelper::getParams("com_jevents");
                                                      if(!empty($filter))
                                                      {
                                                          $filter = " AND ". $filter;
                                                      }
				// if not in the category OR in the category AND within the date range
				if ($compparams->get("multicategory",0)){						
					$filter  = "catmap.catid NOT IN (".implode(",",$cats).") OR (catmap.catid IN (".implode(",",$cats).") $filter)";
				}
				else {
					$filter  = "ev.catid NOT IN (".implode(",",$cats).") OR (ev.catid IN (".implode(",",$cats).") $filter)";
				}
			$filter  = "(".$filter .")";
		}
		
		return $filter;
	}

	function _createfilterHTML(){

		$filterList=array();
		$filterList["title"]="";
		$filterList["html"]="";

		// get plugin params
		$plugin = JPluginHelper::getPlugin('jevents', 'jevtimelimit');
		if(!$plugin) {
			// Filter not active
			return $filterList;
		}
		$params = new JRegistry($plugin->params);
		if (!intval($params->get("override",0))){

			// A hidden filter
			return $filterList;
		}
		else {

			$lang = JFactory::getLanguage();
			$lang->load("plg_jevents_timelimit");

			$this->filterLabel=JText::_("Show Past Events?");
			$this->yesLabel = JText::_("Yes");
			$this->noLabel =  JText::_("No");

			$filterList=array();
			$filterList["title"]="<label class='evtimelimit_label' for='".$this->filterType."_fv'>".$this->filterLabel."</label>";

			$options = array();
			$options[] = JHTML::_('select.option', "0", $this->noLabel,"value","yesno");
			$options[] = JHTML::_('select.option',  "1", $this->yesLabel,"value","yesno");
			$filterList["html"] = JHTML::_('select.genericlist',$options, $this->filterType.'_fv', 'class="inputbox" size="1" onchange="form.submit();"', 'value', 'yesno', $this->filter_value );

		}
		return $filterList;


	}
}
