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
 
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

JLoader::register('jevFilterProcessing',JPATH_SITE."/components/com_jevents/libraries/filters.php");
JLoader::register('JevJoomlaVersion',JPATH_ADMINISTRATOR."/components/com_jevents/libraries/version.php");
$mainframe = JFactory::getApplication();  // RSH 11/10/10 - Make J!1.6 compatible
if($mainframe->isAdmin()) {
	return;
}

jimport( 'joomla.plugin.plugin' );


class plgJEventsJevtimelimit extends JPlugin
{

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$lang = JFactory::getLanguage();
		$lang->load("plg_jevents_jevtimelimit", JPATH_SITE);
		$lang->load("plg_jevents_jevtimelimit", JPATH_ADMINISTRATOR);
	}

	/**
	 * When editing a JEvents menu item/module can add additional menu constraints dynamically
	 *
	 */
	function onEditMenuItem(&$menudata, $value,$control_name,$name, $id, $param)
	{
		// already done this param
		if (isset($menudata[$id])) return;

		static $matchingextra = null;
		// find the parameter that matches jevtl: (if any)
		if (!isset($matchingextra)){
			$params = $param->getGroup('params');
			foreach ($params as $key => $element){
				$val = $element->value;
				if (strpos($key,"jform_params_extras")===0 ){
					if (strpos($val,"jevtl:")===0){
						$matchingextra = $key;
						break;
					}
				}
			}
			if (!isset($matchingextra)){
				$matchingextra = false;
			}
		}

		// either we found matching extra and this is the correct id or we didn't find matching extra and the value is blank
		if (strpos($value,"jevtl:")===0 || (($value==""||$value=="0") && $matchingextra===false)){
			$matchingextra = true;
			$invalue = trim($value);
			if ($invalue =="") $invalue =  'jevtl:-1,-1';
			$value = str_replace("jevtl:","",$invalue);
			
			$input = '<input type="text"  name="jevtl"  value="'.$value.'" onchange="$(\'jevtl\').value=\'jevtl:\'+this.value;" />';
			$input .= '<input type="hidden"  name="'.$name.'"  id="jevtl" value="'.$invalue.'" />';			

			$data = new stdClass();
			$data->name = "jevuser";
			$data->html = $input;
			$data->label = "JEV_DATE_RESTRICTIONS";
			$data->description = "JEV_DATE_RESTRICTIONS_DESC";
			$data->options = array();
			$menudata[$id] = $data;
		}
	}
	
	function onListIcalEvents( & $extrafields, & $extratables, & $extrawhere, & $extrajoin, & $needsgroupdby=false)
	{
		$mainframe = JFactory::getApplication();  // RSH 11/10/10 - Make J!1.6 compatible
		// do not do this since it will stop the plugin from working with newsletter components etc.
		if($mainframe->isAdmin()) {
			// return;
		}
		
		// Have we specified specific people for the menu item
		$compparams = JComponentHelper::getParams(JRequest::getCmd("option","com_jevents"));

		// If loading from a module then get the modules params from the registry
		$reg = JFactory::getConfig();
		$modparams = $reg->get("jev.modparams",false);
		
		if ($modparams){
			$compparams = $modparams;
		}

		for ($extra = 0;$extra<20;$extra++){
			$extraval = $compparams->get("extras".$extra, false);
			if (strpos($extraval,"jevtl:")===0){
				$extraval = str_replace("jevtl:", "", $extraval);
				break;
			}
			$extraval == false;
		}
		
		$pastdate = false;
		$futuredate = false;
		
		if ($extraval && strpos($extraval, ",")) {
			list($past, $future) = explode(",",$extraval);
			$this->dmap = "rpt";
			
			JLoader::register('JevDate', JPATH_SITE . "/components/com_jevents/libraries/jevdate.php");
			
			if (!is_numeric($past)){
				if ($past!="" && $future!=""){
					$pastdate = new JevDate($past);
					$futuredate = new JevDate($future);
					if ($compparams->get("startnow",1)){
						$pastdate = $pastdate->toFormat("%Y-%m-%d %H:%M:%S");
						$futuredate = $futuredate->toFormat("%Y-%m-%d %H:%M:%S");
					}
					else {
						$pastdate = $pastdate->toFormat("%Y-%m-%d  00:00:00");
						$futuredate = $futuredate->toFormat("%Y-%m-%d 23:59:59");
					}
					$extrawhere[] = "(".$this->dmap.".endrepeat>='$pastdate' AND ".$this->dmap.".startrepeat<='$futuredate')";
				}
				else if ($past!=""){
					$pastdate = new JevDate($past);
					if ($compparams->get("startnow",1)){
						$pastdate = $pastdate->toFormat("%Y-%m-%d %H:%M:%S");
					}
					else {
						$pastdate = $pastdate->toFormat("%Y-%m-%d  00:00:00");
					}
					$extrawhere[] = $this->dmap.".endrepeat>='$pastdate'";
				}
				else if ($future!="") {
					$futuredate = new JevDate($future);
					if ($compparams->get("startnow",1)){
						$futuredate = $futuredate->toFormat("%Y-%m-%d %H:%M:%S");
					}
					else {
						$futuredate = $futuredate->toFormat("%Y-%m-%d 23:59:59");
					}
					$extrawhere[] = $this->dmap.".startrepeat<='$futuredate'";
				}
				//echo array_slice($extrawhere, -1)[0];
			}
			else if ($past>=0 && $future>=0){
				$pastdate = new JevDate("-$past days");
				$pastdate = $pastdate->toFormat("%Y-%m-%d 00:00:00");
				$futuredate = new JevDate("+$future days");
				$futuredate = $futuredate->toFormat("%Y-%m-%d 23:59:59");
				$extrawhere[] = "(".$this->dmap.".endrepeat>='$pastdate' AND ".$this->dmap.".startrepeat<='$futuredate')";
			}
			else if ($past>=0){
				$pastdate = new JevDate("-$past days");
				$pastdate = $pastdate->toFormat("%Y-%m-%d 00:00:00");
				$extrawhere[] = $this->dmap.".endrepeat>='$pastdate'";
			}
			else if ($future>=0) {
				$futuredate = new JevDate("+$future days");
				$futuredate = $futuredate->toFormat("%Y-%m-%d 23:59:59");
				$extrawhere[] = $this->dmap.".startrepeat<='$futuredate'";
			}
		}
		$reg->set("jev.timelimit.past",$pastdate);
		$reg->set("jev.timelimit.future",$futuredate);

		// And the normal filters too!				
		$pluginsDir = JPATH_ROOT . '/plugins/jevents';
		$filters = jevFilterProcessing::getInstance(array("timelimit","beforedate","afterdate"),$pluginsDir."/filters/");

		$filters->setWhereJoin($extrawhere,$extrajoin);

		return true;
	}

	function onListEventsById( & $extrafields, & $extratables, & $extrawhere, & $extrajoin)
	{
		$mainframe = JFactory::getApplication();  // RSH 11/10/10 - Make J!1.6 compatible
		if($mainframe->isAdmin()) {
			return;
		}
		$pluginsDir = JPATH_ROOT.'/plugins/jevents';
		$filters = jevFilterProcessing::getInstance(array("timelimit","beforedate","afterdate"),$pluginsDir."/filters/");

		$filters->setWhereJoin($extrawhere,$extrajoin);

		return true;
	}

}
