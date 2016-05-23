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

defined('_VALID_MOS') or defined('_JEXEC') or die( 'No Direct Access' );

// Event repeat startdate fitler
class jevAfterdateFilter extends jevFilter
{

	var $dmap="";
	var $_date = "";

	function __construct($tablename, $filterfield, $isstring=true){
		$lang = JFactory::getLanguage();
		$lang->load("plg_jevents_jevtimelimit", JPATH_SITE);
		$lang->load("plg_jevents_jevtimelimit", JPATH_ADMINISTRATOR);

		$this->fieldset=true;

		$this->valueNum=3;
		$this->filterNullValue=0;
		$this->filterNullValues[0]=1; // n/a, before, after
		$this->filterNullValues[1]=""; // the date
		$this->filterNullValues[2]=0; // true means the form is submitted

		$this->filterType="afterdate";
		$this->filterLabel="";
		$this->dmap = "rpt";
		parent::__construct($tablename,$filterfield, true);

		$plugin = JPluginHelper::getPlugin("jevents","jevtimelimit");
		$this->params = new JRegistry($plugin ? $plugin->params: null);
		$this->dateformat = $this->params->get("dateformat","Y-m-d");
		$this->inputpopup = $this->params->get("inputpopup",1);

		/*
		// filter processing class takes care of memory w.r.t. filter module now.
		$this->filter_values[1] = JRequest::getString($this->filterType.'_fvs1','');
		$this->filter_values[2] = JRequest::getInt($this->filterType.'_fvs2',0);
		*/

		// Should these be ignored?
		$reg = JFactory::getConfig();
		$modparams = $reg->get("jev.modparams",false);
		if ($modparams && $modparams->get("ignorefiltermodule",false)){
			$this->filter_values[1] = $this->filterNullValues[1];
			$this->filter_values[2] = $this->filterNullValues[2];
			$this->_date = $this->filter_values[1];
			return;

		}

		$this->_date = $this->filter_values[1];

	}

	function _createFilter($prefix=""){
		if (!$this->filterField ) return "";
		// first time visit
		if (isset($this->filter_values[2]) && $this->filter_values[2]==0) {
			$this->filter_values = array();
			$this->filter_values[0]=1;
			// default scenario is inactive
			$this->filter_values[1]="";
			return "";
		}
		else if ($this->filter_values[0]==1 && $this->filter_values[1]==""){
			return "";
		}
		$filter="";

		if ($this->_date!="" ){
			if ($this->dateformat=="Y-m-d"){
				$parts = explode("-",($this->_date));
			}
			else if ($this->dateformat=="d.m.Y"){
				$parts = explode(".",($this->_date));
			}
			else {
				$parts = explode("/",($this->_date));
			}
			if (count($parts)<3){
				$parts = explode("-",($this->_date));
			}
			JArrayHelper::toInteger($parts);
			if (count($parts)<3) {
				$fulldate = date( 'Y-m-d H:i:s',strtotime("-5 seconds"));
				$this->filter_values[1]=substr($fulldate,0,10);
				$this->filter_values[2]=1;
				return  $this->dmap.".endrepeat>='$fulldate'";
			}
			else {
				if ($this->dateformat=="Y-m-d"){
					$y=$parts[0];
					$m=$parts[1];
					$d=$parts[2];
				}
				else if ($this->dateformat=="d/m/Y" || $this->dateformat=="d.m.Y"){
					$d=$parts[0];
					$m=$parts[1];
					$y=$parts[2];
				}
				else {
					$m=$parts[0];
					$d=$parts[1];
					$y=$parts[2];
				}
				$fulldate = date( 'Y-m-d H:i:s',strtotime($y."-".$m."-".$d));
				$this->filter_values[1] = $fulldate;
			}
			$date = $this->dmap.".endrepeat>='$fulldate'";
		}
		else {
			$date = "";
		}
		$filter = $date;

		return $filter;
	}

	function _createfilterHTML(){

		if (!$this->filterField) return "";

		$filterList=array();
		$filterList["title"]=JText::_("PLG_JEVENTS_JEVTIMELIMIT_STARTING_AFTER_LABEL");

		$filterList["html"] = "";

		$filterList["html"] .= "<input type='hidden' name='".$this->filterType."_fvs0' value='1'/>";

		$params = JComponentHelper::getParams( JEV_COM_COMPONENT );
		if(method_exists("JEVHelper", "getMinYear"))
		{
			$minyear =  JEVHelper::getMinYear();
			$maxyear = JEVHelper::getMaxYear();
		}
		else
		{
			$minyear = $params->get("com_earliestyear", 1970);
			$maxyear = $params->get("com_latestyear", 2150);
		}

		$parts = explode("-",($this->filter_values[1]));
		JArrayHelper::toInteger($parts);
		if (count($parts)<3){
			$this->filter_values[1] = "";
		}
		else {
			/*
			if ($this->dateformat=="Y-m-d"){
				$this->filter_values[1] = strftime("%Y-%m-%d",strtotime($this->filter_values[1]));
			}
			else if ($this->dateformat=="d/m/Y"){
				$this->filter_values[1] = strftime("%d/%m/%Y",strtotime($this->filter_values[1]));
			}
			else {
				$this->filter_values[1] = strftime("%m/%d/%Y",strtotime($this->filter_values[1]));
			}
			 */
			$this->filter_values[1] = strftime("%Y-%m-%d",strtotime($this->filter_values[1]));
		}

		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		$minyear = JEVHelper::getMinYear();
		$maxyear = JEVHelper::getMaxYear();
		$script  = "var dateval = jQuery('#". $this->filterType."_fvs1').val();" .
				"if (dateval!=''){ " .
				"	jQuery('#afterdatetask').prop('name', 'task'); "  .
				"	if ('".$this->dateformat."'=='Y-m-d'){ ".
				"		var parts = dateval.split('-');".
				"		jQuery('#startdate').val( parts[0]+'-'+parts[1]+'-'+parts[2]); " .
				"	}".
				"	else if ('".$this->dateformat."'=='d/m/Y'){".
				"		var parts = dateval.split('/');".
				"		jQuery('#startdate').val( parts[2]+'-'+parts[1]+'-'+parts[0]); " .
				"	}".
				"	else if ('".$this->dateformat."'=='d.m.Y'){".
				"		var parts = dateval.split('.');".
				"		jQuery('#startdate').val( parts[2]+'-'+parts[1]+'-'+parts[0]); " .
				"	}".
				"	else 	{".
				"		var parts = dateval.split('/');".
				"		jQuery('#startdate').val( parts[0]+'-'+parts[1]+'-'+parts[2]); " .
				"	}".
				"	jQuery('#afterdatetask').prop('name', 'task'); "  .
				"} " .
				"else { " .
				"	jQuery('#afterdatetask').prop('name', 'xxxtask'); " .
				"	jQuery('#startdate').val( ''); " .
				"}";
		ob_start();

		JEVHelper::loadElectricCalendar($this->filterType."_fvs1", $this->filterType."_fvs1", $this->filter_values[1], $minyear, $maxyear, '', $script, $this->dateformat,
				array(' readonly'=>"readonly", "maxlength"=>"10", "size"=>"12") );

		$filterList["html"] .= ob_get_clean();

		//$filterList["html"] .=  '<input type="text" name="'.$this->filterType.'_fvs1" id="'.$this->filterType.'_fvs1" value="'.$this->filter_values[1].'"  readonly="readonly" maxlength="10" size="12"  />'."\n";

		$filterList["html"] .= "<input type='hidden' name='".$this->filterType."_fvs2' value='1'/>\n";
		$filterList["html"] .= "<input type='hidden' name='xxxtask' id='afterdatetask' value='range.listevents' />\n";
		$filterList["html"] .= "<input type='hidden' name='startdate' id='startdate' value='".$this->filter_values[1]."' />\n";

		$script = "try {JeventsFilters.filters.push({id:'".$this->filterType."_fvs1',value:''});} catch (e) {}\n";
		$script .= "try {JeventsFilters.filters.push({id:'".$this->filterType."_fvs2',value:1});} catch (e) {}\n";
		if ($this->filter_values[1] == ""){
			$script .= "jQuery(document).on('ready',function() {jQuery('#".$this->filterType."_fvs1').val('');})\n";
		}
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);

		return $filterList;


	}
}
