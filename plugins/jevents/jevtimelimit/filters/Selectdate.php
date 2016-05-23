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
class jevSelectdateFilter extends jevFilter
{

	function __construct($tablename, $filterfield, $isstring=true){
		$lang = JFactory::getLanguage();
		$lang->load("plg_jevents_jevtimelimit", JPATH_SITE);
		$lang->load("plg_jevents_jevtimelimit", JPATH_ADMINISTRATOR);

		$this->fieldset=true;

		$this->filterType="selectdate";
		$this->dmap = "rpt";
		parent::__construct($tablename,$filterfield, true);
		
		$plugin = JPluginHelper::getPlugin("jevents","jevtimelimit");	
		$this->params = new JRegistry($plugin ? $plugin->params: null);
		$this->dateformat = $this->params->get("dateformat","Y-m-d");
		$this->inputpopup = $this->params->get("inputpopup",1);
		
	}

	function _createfilterHTML(){

		static $scriptLoaded = false;
		$filterList=array();
		$filterList["title"]=JText::_("PLG_JEVENTS_JEVTIMELIMIT_SELECTDATE_LABEL");
		$filterList["html"]="";

		if (intval(JRequest::getVar('filter_reset', 0))) {
			$datenow = JEVHelper::getNow();
			list($year, $month, $day) = explode('-', $datenow->toFormat('%Y-%m-%d'));
		}
		else {
			list($year,$month,$day) = JEVHelper::getYMD();
		}
		$filterList["html"] .= "<input type='hidden' name='day' id='jevday' value='$day'/>";
		$filterList["html"] .= "<input type='hidden' name='month' id='jevmonth' value='$month'/>";
		$filterList["html"] .= "<input type='hidden' name='year' id='jevyear' value='$year'/>";
		$filterList["html"] .= "<input type='hidden' name='xxxtask' id='selectdatetask' value='day.listevents' />";

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
/*
		if ($this->dateformat=="Y-m-d"){
			$fv	 = "$year-$month-$day";
		}
		else if ($this->dateformat=="d/m/Y"){
			$fv	 = "$day/$month/$year";
		}
		else if ($this->dateformat=="d.m.Y"){
			$fv	 = "$day.$month.$year";
		}
		else {
			$fv	= "$month/$day/$year";
		}
 */
		$fv	 = "$year-$month-$day";

		$script  = "	if ('".$this->dateformat."'=='Y-m-d'){ "
			."	var parts = jQuery('#selectjevdate').val().split('-');"
			."	jQuery('#jevday').val( parts[2]);"
			."	jQuery('#jevmonth').val(parts[1]);"
			."	jQuery('#jevyear').val(parts[0]);"
			."}"
			."else if ('".$this->dateformat."'=='d/m/Y'){"
			."	var parts = jQuery('#selectjevdate').val().split('/');"
			."	jQuery('#jevday').val( parts[0]);"
			."	jQuery('#jevmonth').val( parts[1]);"
			."	jQuery('#jevyear').val( parts[2]);"
			."}"
			."else if ('".$this->dateformat."'=='d.m.Y'){"
			."	var parts = jQuery('#selectjevdate').val().split('.');"
			."	jQuery('#jevday').val(parts[0]);"
			."	jQuery('#jevmonth').val(parts[1]);"
			."	jQuery('#jevyear').val(parts[2]);"
			."}"
			."else 	{"
			."	var parts = jQuery('#selectjevdate').val().split('/');"
			."	jQuery('#jevday').val(parts[1]);"
			."	jQuery('#jevmonth').val(parts[0]);"
			."	jQuery('#jevyear').val(parts[2]);"
			."}"
			."if (jQuery('#selectjevdate').val()!=''){"
			."	jQuery('#selectdatetask').prop('name', 'task');"
			."}"
			."else {"
			."	jQuery('#selectdatetask').prop('name', 'xxxtask');"
			."}";

		ob_start();
		JEVHelper::loadElectricCalendar("selectjevdate", "selectjevdate", $fv, $minyear, $maxyear, '', $script, $this->dateformat,
				array(' readonly'=>"readonly", "maxlength"=>"10", "size"=>"12") );

		$filterList["html"] .= ob_get_clean();

		if ($fv == "" || JRequest::getCmd("task")!="day.listevents"){
			$script = "jQuery(document).on('ready',function() {jQuery('#selectjevdate').val('');})\n";
			$document = JFactory::getDocument();
			$document->addScriptDeclaration($script);
		}

		//$filterList["html"] .= "<input type='text' name='selectjevdate' id='selectjevdate' value='$fv'/>";
		return $filterList;
	}
}
