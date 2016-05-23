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

JLoader::register('JevCfForm', JPATH_SITE. "/plugins/jevents/jevcustomfields/customfields/jevcfform.php");

// searches location of event
class jevCustomfieldfilter extends jevFilter
{
	private $params;
	private $fieldparams;
	static $xmlparams = array();
	const filterType = "customfield";
	
	function __construct($tablename, $filterfield, $isstring=true){
		$plugin = JPluginHelper::getPlugin("jevents","jevcustomfields");
		if (!$plugin) return "";
		$this->params = new JRegistry($plugin->params);
		
		$this->filterType=self::filterType;
		$this->fieldparams = false;

		// If JDEBUG is defined, load the profiler instance
		if (defined('JDEBUG') && JDEBUG)
		{
			$this->profiler = JProfiler::getInstance('Application');
		}

		// Should these be ignored?
		$reg = JFactory::getConfig();
		$modparams = $reg->get("jev.modparams",false);
		if ($modparams && $modparams->get("ignorefiltermodule",false)){
			return "";
		}		

		$template = $this->params->get("template","");
		if ($template!=""){			
			$xmlfile = JPATH_SITE."/plugins/jevents/jevcustomfields/customfields/templates/".$template;

			if (isset(self::$xmlparams[$xmlfile]) && self::$xmlparams[$xmlfile]!="" && file_exists($xmlfile)){
				$this->fieldparams = clone (self::$xmlparams[$xmlfile]);
			}
			else {
				if (file_exists($xmlfile)){
					$this->fieldparams = JevCfForm::getInstance("com_jevent.customfields", $xmlfile, array('control' => 'jform', 'load_data' => true), true, "/form");
					$this->fieldparams->setEvent(null);
					self::$xmlparams[$xmlfile] = $this->fieldparams ;
				}
				else {
					self::$xmlparams[$xmlfile] = "";
					return "";
				}
			}
		}
		else {
			return;
		}

		$this->fieldparams->constructFilters();
	}		

	function _createFilter($prefix=""){
		if (!$this->fieldparams) return "";
		return $this->fieldparams->createFilters();

	}

	function _createJoinFilter($prefix=""){
		if (!$this->fieldparams) return "";
		// Always do the join
		$this->needsgroupby = true;
		return $this->fieldparams->createJoinFilters();
	}

	public function setSearchKeywords(& $extrajoin ){
		if (!$this->fieldparams) return array();
		// Always do the join
		$this->needsgroupby = true;
		return $this->fieldparams->setSearchKeywords($extrajoin);		
	}

	function _createfilterHTML(){
		if (!$this->fieldparams) return array();
		return $this->fieldparams->createFiltersHTML();
	}
}