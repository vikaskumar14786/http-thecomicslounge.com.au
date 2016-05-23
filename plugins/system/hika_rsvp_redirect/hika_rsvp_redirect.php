<?php
/**
 * @package	RSVP Pro for Joomla!
 * @version	3.0.0
 * @author	jevents.net
 * @copyright	(C) 2010-2015 GWE Systems Ltd. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');


class plgSystemHika_Rsvp_redirect extends JPlugin {

	function onAfterRoute() {
		$app = JFactory::getApplication();

		if( JRequest::getString('option') != 'com_hikashop' || $app->isAdmin() )
			return true;
		if (!(
			(JRequest::getString('ctrl') == 'product' && JRequest::getString('task') == 'show') ||
			(JRequest::getString('ctrl') == 'product' && JRequest::getString('task') == 'updatecart')  
			)) 
			return true;

		// if adding a product to the cart then check which product and redirect if needed!
		if ((JRequest::getString('ctrl') == 'product' && JRequest::getString('task') == 'updatecart')) {
			$hikaProdId = (int)JRequest::getVar('product_id');
			$cart_type = JRequest::getCmd("cart_type");
			// this redirect doesn't work when called in AJAX mode!!!
			return true;
			if ($hikaProdId==0 || $cart_type!="cart") {
				return true;
			}
		}
		else {
			$hikaProdId = (int)JRequest::getVar('cid');
			if ($hikaProdId==0)
				return true;
		}

		$db = JFactory::getDBO();

		if(!include_once(rtrim(JPATH_ADMINISTRATOR,DS).'/components/com_hikashop/helpers/helper.php'))
			return true;

		$productclass = hikashop_get('class.product');
		$productclass->getProducts($hikaProdId);

		if (!isset( $productclass->products) ||  count( $productclass->products)==0)
			return true;
		
		$product =  current( $productclass->products);

		$sku = $product->product_code;

		$plugin = JPluginHelper::getPlugin("rsvppro", "hikashop");
		$params = new JRegistry($plugin->params);
		$rsvp_sku = $params->get('skuprefix', 'RSVP');

		$parts = explode("_", $sku);

		if (count($parts)!=3)
			return true;

		if ($parts[0]!=$rsvp_sku){
			return true;
		}

		list($rsvpprefix, $eventid, $repeatid) = $parts;

		// setup the Joomla autoloader
		include_once(JPATH_SITE . "/components/com_jevents/jevents.defines.php");
		// get the data and query models
		$dataModel = new JEventsDataModel("JEventsAdminDBModel");
		$queryModel = new JEventsDBModel($dataModel);
		//method viewDetailLink is in the following class
		$jEventModel = new jEventCal($dataModel);

		if ($repeatid==0){
			// single event
			$nextrepeat = true;
			// get the event by event id
			$jevent = $queryModel->getEventById(intval($eventid), 1, "icaldb");
		}
		else {
			// repeating event
			$nextrepeat = false;
			// get the event by repeat id
			$jevent = $queryModel->listEventsById(intval($repeatid), 1, "icaldb");

		}

		if (!$jevent)
			return "";
		if ($nextrepeat)
		{
			$jevent = $jevent->getNextRepeat();
		}
// get the event detail link (aleady SEFed)
		$Itemid = JEVHelper::getItemid($jevent);
		$detailSefLink = $jevent->viewDetailLink($jevent->yup(), $jevent->mup(), $jevent->dup(), true, $Itemid);
		$link = JRoute::_($detailSefLink);

		JFactory::getApplication()->redirect($link);

		return true;
	}
}
