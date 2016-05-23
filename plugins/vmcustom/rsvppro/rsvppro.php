<?php

defined('_JEXEC') or die('Restricted access');

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: mod.defines.php 1400 2009-03-30 08:45:17Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2012-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 */
// Load the helper functions that are needed by all plugins
if (!class_exists ('VmHTML')) {
    require(VMPATH_ADMIN  . "/helpers/html.php");
}

// Get the plugin library
jimport ('joomla.plugin.plugin');

if (!class_exists ('vmPlugin')) {
    require(VMPATH_PLUGINLIBS . '/vmplugin.php');
}

if (!class_exists('vmCustomPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmcustomplugin.php');

class plgVmCustomRsvppro extends vmCustomPlugin
{

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		$varsToPush = array(	);
		$varsToPush = array(	'transaction_id'=>array(0.0,'int'),
						    		'amount'=>array(0.0,'float')
		);


		$this->setConfigParameterable('custom_params',$varsToPush);

	}

	// get product param for this plugin on edit
	function plgVmOnProductEdit($field, $product, &$row, &$retValue)
	{
		if ($field->custom_element != $this->_name)
			return '';

		$html = '
			<fieldset>
				<legend>' . JText::_('VMCUSTOM_RSVPPRO') . '</legend>
				<table class="admintable">
					' . JText::_('VMCUSTOM_RSVPPRO_INFO') . '
				</table>
			</fieldset>';
		$retValue .= $html;
		$row++;
		return true;

	}

	function plgVmOnDisplayProductVariantFE($field, &$idx, &$group)
	{
		// default return if it's not this plugin
		if ($field->custom_value != $this->_name)
			return '';
		$html = 'TODO - output price and event into here';
		$group->display .= $html;
		return true;

	}

	function plgVmOnViewCartModuleVM3($product, $productCustom, &$html)
	{
		if (empty($productCustom->custom_element) or $productCustom->custom_element != $this->_name) return false;

		if(empty($product->customProductData[$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id])) return false;
		$param = array();
		foreach( $product->customProductData[$productCustom->virtuemart_custom_id] as $k =>$item ) {
			if($productCustom->virtuemart_customfield_id == $k) {
				$param = $item;
				break;
			}
		}
		if (!isset($param["transaction_id"]) && !isset($param["amount"])) {
			return false;
		}

		$html .= JFactory::getApplication()->isAdmin() ? '<div>' : '<div style="display:none">';
		//$html .= 'TODO - output price and event into Module here<br/>';
		$html .= 'Transaction id = '.$param['transaction_id']."<br/>";
		$html .= 'Amount = '.$param['amount'];
		$html .='</div>';
		return true;

	}

	function plgVmOnViewCart($product,$row,&$html) {
		$x = 1;
	}

	function plgVmOnViewCartVM3($product, $productCustom, &$html)
	{
		if (empty($productCustom->custom_element) or $productCustom->custom_element != $this->_name) return false;

		if(empty($product->customProductData[$productCustom->virtuemart_custom_id][$productCustom->virtuemart_customfield_id])) return false;
		$param = array();
		foreach( $product->customProductData[$productCustom->virtuemart_custom_id] as $k =>$item ) {
			if($productCustom->virtuemart_customfield_id == $k) {
				$param = $item;
				break;
			}
		}
		if (!isset($param["transaction_id"]) && !isset($param["amount"])) {
			return false;
		}

		if (!defined("JEV_LIBS")){
			include_once(JPATH_ADMINISTRATOR."/components/com_jevents/jevents.defines.php");
		}
		if (!defined("RSVP_TABLES")){
			include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/rsvppro.defines.php");
		}

		$transaction =new rsvpTransaction( );

		$extrainfo = "";
		if ($this->params->get("showdateincart",0) && $transaction->load( $param['transaction_id'] ) ){

			$db = JFactory::getDBO();
			$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $transaction->attendee_id;
			$db->setQuery($sql);
			$attendee = $db->loadObject();
			if ($attendee)
			{
				$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
				$db->setQuery($sql);
				$rsvpdata = $db->loadObject();

				$rpid = $attendee->rp_id;
				$this->dataModel = new JEventsDataModel();
				$this->queryModel = new JEventsDBModel($this->dataModel);

				// Find the first repeat
				$vevent = $this->dataModel->queryModel->getEventById($rsvpdata->ev_id, false, "icaldb");
				if ($rpid == 0)
				{
					$repeat = $vevent->getFirstRepeat();
				}
				else
				{
					list($year, $month, $day) = JEVHelper::getYMD();
					$repeatdata = $this->dataModel->getEventData(intval($rpid), "icaldb", $year, $month, $day);
					if ($repeatdata && isset($repeatdata["row"]))
						$repeat = $repeatdata["row"];
				}

				$jevparams =  JComponentHelper::getParams(JEV_COM_COMPONENT);
				$registry = JRegistry::getInstance("jevents");
				$tz = $jevparams->get("icaltimezonelive", "");
				if ($tz != "" && is_callable("date_default_timezone_set"))
				{
					$timezone = date_default_timezone_get();
					date_default_timezone_set($tz);
					$registry->set("jevents.timezone", $timezone);
				}

				if ($tz != ""){
					$eventstart = new JevDate($repeat->publish_up(), $tz);
				}
				else {
					$eventstart = new JevDate($repeat->publish_up());
				}

				$extrainfo = "Starting : ".$eventstart->toFormat("%Y-%m-%d %H:%M")."<br/>";

			}
		}

		$html .= '<div>';
		//$html .= 'TODO - output price and event into here<br/>';
		$html .= $extrainfo;
		// Show more information in the backend
		if (JFactory::getApplication()->isAdmin()){
			$html .= 'Transaction id = '.$param['transaction_id']."<br/>";
			$html .= 'Amount = '.$param['amount'];
		}
		$html .='</div>';

		$document = JFactory::getDocument();
		$document->addStyleDeclaration(".quantity-input.js-recalculate, .vm2-add_quantity_cart,  vm3-add_quantity_cart {display:none;}");
		return true;

	}

	/**
	 *
	 * vendor order display BE
	 */
	function plgVmDisplayInOrderBEVM3($item, $row, &$html)
	{
		$this->plgVmOnViewCartVM3($item, $row, $html); //same render as cart

	}

	/**
	 *
	 * shopper order display FE
	 */
	function plgVmDisplayInOrderFEVM3($item, $row, &$html)
	{
		$this->plgVmOnViewCartVM3($item, $row, $html); //same render as cart

	}


	public function plgVmPrepareCartProduct(&$product, &$productCustom,$selected,&$modificatorSum){

		if (empty($productCustom->custom_element) or $productCustom->custom_element != $this->_name) return false;

		if(empty($selected["amount"])) return false;

		$modificatorSum += $selected["amount"];

		return true;
	}


	/**
	 * Declares the Parameters of a plugin
	 * @param $data
	 * @return bool
	 */
	function plgVmDeclarePluginParamsCustomVM3(&$data){

		return $this->declarePluginParams('custom', $data);
	}

}
