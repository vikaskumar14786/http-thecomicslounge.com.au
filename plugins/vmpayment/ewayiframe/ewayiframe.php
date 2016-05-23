<?php
/**
 *  Eway iframe for virtuemart 2/3
 *  @copyright Copyright (C) 2015 www.virtuemart.com.au - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * 
 *
 * @author viruemart australia 
 */
defined('_JEXEC') or die('Restricted access');

if (!class_exists('vmPSPlugin'))
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

class plgVMPaymentEwayIframe extends vmPSPlugin {

    // instance of class
	public static $_this = FALSE;

    function __construct (& $subject, $config) {

		parent::__construct ($subject, $config);
		$this->_debug = TRUE;
		$this->_loggable = TRUE;
		$this->_tablepKey = 'id';
		$this->_tableId = 'id';
        $this->tableFields = array_keys($this->getTableSQLFields());
        $varsToPush = array(
            'eway_username'    => array('', 'char'),
            'eway_password'    => array('', 'char'),
            'payment_currency' => array(0, 'char'),
            'sandbox' => array(0, 'int'),
            'payment_logos' => array('', 'char'),
            'store_logo' => array('', 'char'),
            'debug' => array(0, 'int'),
            'status_pending' => array(0, 'char'),
            'status_success' => array(0, 'char'),
            'status_canceled' => array(0, 'char'),
            'countries' => array(0, 'char'),
            'min_amount' => array(0, 'int'),
            'max_amount' => array(0, 'int'),
            'cost_per_transaction' => array(0, 'int'),
            'cost_percent_total' => array(0, 'int'),
            'tax_id' => array(0, 'int')
        );

        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
    }

    protected function getVmPluginCreateTableSQL() {
	    return $this->createTableSQL('Payment eWAY iframe Table');
    }

    function getTableSQLFields() {

	    $SQLfields = array(
	        'id'                                         => 'int(1) UNSIGNED NOT NULL AUTO_INCREMENT',
			'virtuemart_order_id'                        => 'int(1) UNSIGNED',
			'order_number'                               => ' char(64)',
			'virtuemart_paymentmethod_id'                => 'mediumint(1) UNSIGNED',
            'payment_name'                               => 'varchar(5000)',
            'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
            'payment_currency' => 'char(3) ',
            'cost_per_transaction'                       => 'decimal(10,2)',
			'cost_percent_total'                         => 'char(10)',
			'tax_id'                                     => 'smallint(1)',
            'eway_response_raw' => ' text DEFAULT NULL'
        );
        return $SQLfields;
    }


	function getUserIP()
        {
            $ip = "";
            
            if (isset($_SERVER))
            {
	            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
	            	$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	            } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
	            	$ip = $_SERVER["HTTP_CLIENT_IP"];
	            } else {
	            	$ip = $_SERVER["REMOTE_ADDR"];
	            }
            }
            else {
	            if ( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
	            	$ip = getenv( 'HTTP_X_FORWARDED_FOR' );
	            } elseif ( getenv( 'HTTP_CLIENT_IP' ) ) {
	           	 $ip = getenv( 'HTTP_CLIENT_IP' );
	            } else {
	            	$ip = getenv( 'REMOTE_ADDR' );
	            }
            }
            return $ip;
          } 
            
      
    function plgVmConfirmedOrder ($cart, $order) {

	if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
		return NULL; // Another method was selected, do nothing
	}
	if (!$this->selectedThisElement($method->payment_element)) {
		return FALSE;
	}

        $this->_debug = $method->debug;
        $this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');

        if (!class_exists('VirtueMartModelOrders'))
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
        if (!class_exists('VirtueMartModelCurrency'))
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');

        $new_status = '';

        $address = $order['details']['BT'];
        //var_dump($order['details']['BT']);exit;
        $shipping_address = $order['details']['ST'];

        if (!class_exists('TableVendors'))
            require(JPATH_VM_ADMINISTRATOR . DS . 'table' . DS . 'vendors.php');
        $vendorModel = new VirtueMartModelVendor();
        $vendorModel->setId(1);
        $vendor = $vendorModel->getVendor();
        $vendorModel->addImages($vendor, 1);
        $this->getPaymentCurrency($method);
        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';
        $db = &JFactory::getDBO();
        $db->setQuery($q);
        $currency_code_3 = $db->loadResult();

        $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
        $totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total,false), 2);
        $cd = CurrencyDisplay::getInstance($cart->pricesCurrency);
        if ($totalInPaymentCurrency <= 0) {
             vmInfo(JText::_('VMPAYMENT_PAYPAL_PAYMENT_AMOUNT_INCORRECT'));
                return false;
        }

        // Prepare data that should be stored in the database
        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['payment_name'] = $this->renderPluginName($method, $order);
        $dbValues['virtuemart_paymentmethod_id'] = $order['details']['BT']->virtuemart_paymentmethod_id;
        $dbValues['cost_per_transaction'] = $method->cost_per_transaction;
        $dbValues['cost_percent_total'] = $method->cost_percent_total;
        $dbValues['payment_currency'] = $method->payment_currency;
        $dbValues['payment_order_total'] = $totalInPaymentCurrency;
        $dbValues['tax_id'] = $method->tax_id;
        $this->storePSPluginInternalData($dbValues);

        $session = JFactory::getSession();
      
        $return_context = $session->getId();
		
		$url = JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&pm=' . $order['details']['BT']->virtuemart_paymentmethod_id. '&task=pluginresponsereceived';
		
		require_once __DIR__.'/include_eway.php';
		
		$apiKey = $method->eway_username;
		$apiPassword = $method->eway_password;
	
		if($method->sandbox)$client = \Eway\Rapid::createClient($apiKey, $apiPassword);
		else $client = \Eway\Rapid::createClient($apiKey, $apiPassword,'https://api.ewaypayments.com/AccessCodesShared');
		
		
		$title_array = array('Mr', 'Ms', 'Mrs', 'Dr', 'Sir', 'Prof');
       	$user_title = (isset($address->title) && in_array($address->title, $title_array)) ? $address->title . '.' : 'Mr.';
		
		$items = array();
		foreach($order['items'] as $_item) {
            $items[] = [
					'SKU' => $_item->order_item_sku,
					'Description' => $_item->order_item_name,
					'Quantity' => $_item->product_quantity,
					'UnitCost' => $_item->product_item_price,
					'Tax' => $_item->product_tax
					];

			}
		//	var_dump($currency_code_3);exit;
		$transaction = [
			'Customer' => [
				'Reference' => 'virtuemart',
				'Title' => $user_title,
				'FirstName' => strval($address->first_name),
				'LastName' => strval($address->last_name),
				'CompanyName' => strval($address->company),
				'JobDescription' => '',
				'Street1' => strval($address->address_1),
				'Street2' => strval($address->address_2),
				'City' => strval($address->city),
				'State' => strval(isset($address->virtuemart_state_id) ? ShopFunctions::getStateByID($address->virtuemart_state_id) : ''),
				'PostalCode' => strval($address->zip),
				'Country' => strtolower(ShopFunctions::getCountryByID($address->virtuemart_country_id, 'country_2_code')),
				'Phone' => $address->phone_1,
				'Mobile' => $address->phone_2,
				'Email' => $address->email,
				"Url" => "",
			],
			
			'ShippingAddress' => [
				'ShippingMethod' => \Eway\Rapid\Enum\ShippingMethod::NEXT_DAY,
				'FirstName' => strval($shipping_address->first_name),
				'LastName' => strval($shipping_address->last_name),
				'Street1' => strval($shipping_address->address_1),
				'Street2' => strval($shipping_address->address_2),
				'City' => strval($shipping_address->city),
				'State' => strval(isset($shipping_address->virtuemart_state_id) ? ShopFunctions::getStateByID($shipping_address->virtuemart_state_id) : ''),
				'Country' => strtolower(ShopFunctions::getCountryByID($shipping_address->virtuemart_country_id, 'country_2_code')),
				'PostalCode' => strval($shipping_address->zip),
				'Phone' => $shipping_address->phone_1,
			],
			
			'Items' => $items,

			'Options' => [
				[
					'Value' =>  $return_context,
				],
				[
					'Value' => $order['details']['BT']->order_number,
				],
			],
			'Payment' => [
				'TotalAmount' => number_format($totalInPaymentCurrency, 2, '.', '') * 100,
				'InvoiceNumber' => $order['details']['BT']->order_number,
				'InvoiceDescription' => $order['details']['BT']->order_number,
				'InvoiceReference' => '',
				'CurrencyCode' => $currency_code_3,
			],
			'RedirectUrl' => $url,
			'CancelUrl' => $_SERVER['HTTP_REFERER'],
			'DeviceID' => '',
			'CustomerIP' => $this->getUserIP(),
			'PartnerID' => '',
			'TransactionType' => \Eway\Rapid\Enum\TransactionType::PURCHASE,
			'Capture' => true,
			'LogoUrl' => 'https://'.$_SERVER['HTTP_HOST'].'/images/stories/virtuemart/payment/'.$method->store_logo,
			'Language' => 'EN',
		];

		$response = $client->createTransaction(\Eway\Rapid\Enum\ApiMethod::RESPONSIVE_SHARED, $transaction);

		// See the JSON tab for all the available $response properties

		if (!$response->getErrors()) {
			$sharedPaymentUrl = $response->SharedPaymentUrl;
		} else {
			foreach ($response->getErrors() as $error) {
				echo "Error: ".\Eway\Rapid::getMessage($error)."<br>";
			}
		}
		$r = parse_url($sharedPaymentUrl);
		parse_str($r['query'], $r2);

	$html .= '
		<script src="https://secure.ewaypayments.com/scripts/eCrypt.js"></script>
<script>

    /**
     * eWAY Rapid IFrame config object. Contains the SharedPaymentUrl
     */
    var eWAYConfig = {
      sharedPaymentUrl: "'. $sharedPaymentUrl .'"
    };

    /**
     * Example eWAY Rapid IFrame callback
     */
    function resultCallback(result, transactionID, errors) {
      if (result == "Complete") {
       parent.location.href = self.location.href = "'.$url.'&result=" + result + "&transactionid=" + transactionID + "&AccessCode='.$r2['AccessCode'].'";
      } else if (result == "Error") {
        alert("There was a problem completing the payment: " + errors);
        parent.location.href = self.location.href = "'.$url.'&result=" + result + "&transactionid=" + transactionID + "&AccessCode='.$r2['AccessCode'].'";
      }
    }

</script>

<button type="button" onClick="eCrypt.showModalPayment(eWAYConfig, resultCallback);">Pay with eWAY</button>
';

        $cart->_confirmDone = false;
        $cart->_dataValidated = false;
        $cart->setCartIntoSession();
        JRequest::setVar('html', $html);
    }

    function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {

	if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
	    return null; // Another method was selected, do nothing
	}
	if (!$this->selectedThisElement($method->payment_element)) {
	    return false;
	}
	 $this->getPaymentCurrency($method);
	$paymentCurrencyId = $method->payment_currency;
    }

    function plgVmOnPaymentResponseReceived(&$html) {

        if (!class_exists('VirtueMartCart'))
            require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
        if (!class_exists('shopFunctionsF'))
            require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
        if (!class_exists('VirtueMartModelOrders'))
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );

        // the payment itself should send the parameter needed.
        $virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);

        $vendorId = 0;
        if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return NULL; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return FALSE;
        }

	    $payment_data = JRequest::get('request');
	    vmdebug('plgVmOnPaymentResponseReceived', $payment_data);
		
		$transactionId = $payment_data['transactionID'];
		$accessCode = $payment_data['AccessCode'];
		$apiKey = $method->eway_username;
		$apiPassword = $method->eway_password;
	
		require_once __DIR__.'/include_eway.php';
		
		if($method->sandbox)$client = \Eway\Rapid::createClient($apiKey, $apiPassword);
		else $client = \Eway\Rapid::createClient($apiKey, $apiPassword,'https://api.ewaypayments.com/AccessCodesShared');
		
		$ra = $client->queryAccessCode($accessCode)->toArray();
		$order_number = ($ra['Options'][1]['Value']);

		$rt = $client->queryTransaction($transactionId)->toArray();
		//var_dump($orderNumber);exit;


        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
        $this->logInfo('plgVmOnPaymentNotification: virtuemart_order_id  found ' . $virtuemart_order_id, 'message');

        //$this->logInfo( print_r($result, true) );
        $response_fields['id'] = 0;
        $response_fields['payment_name'] = $this->renderPluginName($method);
        $response_fields['eway_response_raw'] = print_r($result, true);
        $response_fields['order_number'] = $order_number;
        $response_fields['virtuemart_order_id'] = $virtuemart_order_id;
        $this->storePSPluginInternalData($response_fields);

        // Order not found
		if (!$virtuemart_order_id) {
			// vmdebug('plgVmOnPaymentResponseReceived ' . $this->_name, $data, $resp->get('order_id'));
			$this->logInfo('plgVmOnPaymentResponseReceived -- payment check attempted on non existing order : ' . $virtuemart_order_id, 'error');
			$html = 'Payment Error: Order is not founded';
			if ($isError) {
                $html = 'Payment Error: ' . $lblError;
            }
			return NULL;
		}

		$order = VirtueMartModelOrders::getOrder($virtuemart_order_id);


		$new_status = $method->status_success;

		$order['customer_notified'] = 1;
		$order['order_status'] = $new_status;
		$order['comments'] = '';
		$modelOrder = new VirtueMartModelOrders();
		$modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
		$this->emptyCart($return_context);

		$html = '<table>' . "\n";
		$ra = (object)$ra;
		$html .= $this->getHtmlRow('EWAY_PAYMENT_NAME', $response_fields['payment_name']);
		$html .= $this->getHtmlRow('EWAY_ORDER_NUMBER', $response_fields['order_number'] );
		$html .= $this->getHtmlRow('Order Status', $ra->Errors ? 'Cancelled' : 'Confirmed' );
	  /*  $html .= $this->getHtmlRow('eWay Response Code', $ra->ResponseCode );
		$html .= $this->getHtmlRow('eWay TransactionID', $transactionId );
		$html .= $this->getHtmlRow('eWay Response Message', $ra->ResponseMessage );
		*/ 
		$html .= $this->getHtmlRow('EWAY_AMOUNT', '$'.$ra->TotalAmount / 100);
		
		$html .= '</table>' . "\n";
        

        $this->logInfo('plgVmOnPaymentNotification return new_status' . $new_status, 'message');

	    return true;
    }

	function plgVmOnUserPaymentCancel() {

	if (!class_exists('VirtueMartModelOrders'))
	    require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );

	$order_number = JRequest::getVar('on');
	if (!$order_number)
	    return false;
	$db = JFactory::getDBO();
	$query = 'SELECT ' . $this->_tablename . '.`virtuemart_order_id` FROM ' . $this->_tablename. " WHERE  `order_number`= '" . $order_number . "'";

	$db->setQuery($query);
	$virtuemart_order_id = $db->loadResult();

	if (!$virtuemart_order_id) {
	    return null;
	}
	$this->handlePaymentUserCancel($virtuemart_order_id);

	//JRequest::setVar('paymentResponse', $returnValue);
	return true;
    }

    /*
     *   plgVmOnPaymentNotification() - This event is fired by Offline Payment. It can be used to validate the payment data as entered by the user.
     * Return:
     * Parameters:
     *  None
     *  @author Valerie Isaksen
     */
    function plgVmOnPaymentNotification() {
	    return true;
    }

    /**
     * Display stored payment data for an order
     * @see components/com_virtuemart/helpers/vmPSPlugin::plgVmOnShowOrderBEPayment()
     */

    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id) {

	if (!$this->selectedThisByMethodId($payment_method_id)) {
	    return null; // Another method was selected, do nothing
	}

	$db = JFactory::getDBO();
	$q = 'SELECT * FROM `' . $this->_tablename . '` '
		. 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id .' ORDER BY id DESC';
	$db->setQuery($q);
	if (!($paymentTable = $db->loadObject())) {
	   // JError::raiseWarning(500, $db->getErrorMsg());
	    return '';
	}
	$this->getPaymentCurrency($paymentTable);
	$q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $paymentTable->payment_currency . '" ';
	$db = &JFactory::getDBO();
	$db->setQuery($q);
	$currency_code_3 = $db->loadResult();
	$html = '<table class="adminlist">' . "\n";
	$html .=$this->getHtmlHeaderBE();
	$html .= $this->getHtmlRowBE('EWAY_PAYMENT_NAME', $paymentTable->payment_name);
	foreach ($paymentTable as $key => $value) {
	    if ($key == 'eway_response_raw') {
		$html .= $this->getHtmlRowBE($key, $value);
	    }
	}
	$html .= '</table>' . "\n";
	return $html;
    }

	function getCosts (VirtueMartCart $cart, $method, $cart_prices) {
		if (preg_match ('/%$/', $method->cost_percent_total)) {
			$cost_percent_total = substr ($method->cost_percent_total, 0, -1);
		}
		else {
			$cost_percent_total = $method->cost_percent_total;
		}
		return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
	}

    /**
     * Check if the payment conditions are fulfilled for this payment method
     * @author: Valerie Isaksen
     *
     * @param $cart_prices: cart prices
     * @param $payment
     * @return true: if the conditions are fulfilled, false otherwise
     *
     */
    protected function checkConditions ($cart, $method, $cart_prices) {
		$address = (($cart->ST == 0) ? $cart->BT : $cart->ST);
		$amount = $cart_prices['salesPrice'];
		$amount_cond = ($amount >= $method->min_amount AND $amount <= $method->max_amount
			OR
			($method->min_amount <= $amount AND ($method->max_amount == 0)));
		if (!$amount_cond) {
			return FALSE;
		}
		$countries = array();
		if (!empty($method->countries)) {
			if (!is_array ($method->countries)) {
				$countries[0] = $method->countries;
			}
			else {
				$countries = $method->countries;
			}
		}

		// probably did not gave his BT:ST address
		if (!is_array ($address)) {
			$address = array();
			$address['virtuemart_country_id'] = 0;
		}

		if (!isset($address['virtuemart_country_id'])) {
			$address['virtuemart_country_id'] = 0;
		}
		if (count ($countries) == 0 || in_array ($address['virtuemart_country_id'], $countries) || count ($countries) == 0) {
			return TRUE;
		}
		return FALSE;
	}

    /**
     * We must reimplement this triggers for joomla 1.7
     */

    /**
     * Create the table for this plugin if it does not yet exist.
     * This functions checks if the called plugin is active one.
     * When yes it is calling the standard method to create the tables
     * @author Valérie Isaksen
     *
     */
    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
	    return $this->onStoreInstallPluginTable($jplugin_id);
    }

    /**
     * This event is fired after the payment method has been selected. It can be used to store
     * additional payment info in the cart.
     *
     * @author Max Milbers
     * @author Valérie isaksen
     *
     * @param VirtueMartCart $cart: the actual cart
     * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
     *
     */
    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
		return $this->OnSelectCheck($cart);
    }

    /**
     * plgVmDisplayListFEPayment
     * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
     *
     * @param object $cart Cart object
     * @param integer $selected ID of the method selected
     * @return boolean True on succes, false on failures, null when this plugin was not selected.
     * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
     *
     * @author Valerie Isaksen
     * @author Max Milbers
     */
    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
	return $this->displayListFE($cart, $selected, $htmlIn);
    }

    /*
     * plgVmonSelectedCalculatePricePayment
     * Calculate the price (value, tax_id) of the selected method
     * It is called by the calculator
     * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
     * @author Valerie Isaksen
     * @cart: VirtueMartCart the current cart
     * @cart_prices: array the new cart prices
     * @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
     *
     *
     */

    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
	return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    /**
     * plgVmOnCheckAutomaticSelectedPayment
     * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
     * The plugin must check first if it is the correct type
     * @author Valerie Isaksen
     * @param VirtueMartCart cart: the cart object
     * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
     *
     */
    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array()) {
	return $this->onCheckAutomaticSelected($cart, $cart_prices);
    }

    /**
     * This method is fired when showing the order details in the frontend.
     * It displays the method-specific data.
     *
     * @param integer $order_id The order ID
     * @return mixed Null for methods that aren't active, text (HTML) otherwise
     * @author Max Milbers
     * @author Valerie Isaksen
     */
    protected function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
	  $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    /**
     * This event is fired during the checkout process. It can be used to validate the
     * method data as entered by the user.
     *
     * @return boolean True when the data was valid, false otherwise. If the plugin is not activated, it should return null.
     * @author Max Milbers

      public function plgVmOnCheckoutCheckDataPayment($psType, VirtueMartCart $cart) {
      return null;
      }
     */

    /**
     * This method is fired when showing when priting an Order
     * It displays the the payment method-specific data.
     *
     * @param integer $_virtuemart_order_id The order ID
     * @param integer $method_id  method used for this order
     * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
     * @author Valerie Isaksen
     */
    function plgVmonShowOrderPrintPayment($order_number, $method_id) {
	return $this->onShowOrderPrint($order_number, $method_id);
    }

    /**
     * Save updated order data to the method specific table
     *
     * @param array $_formData Form data
     * @return mixed, True on success, false on failures (the rest of the save-process will be
     * skipped!), or null when this method is not actived.
     * @author Oscar van Eijk

      public function plgVmOnUpdateOrderPayment(  $_formData) {
      return null;
      }
     */
    /**
     * Save updated orderline data to the method specific table
     *
     * @param array $_formData Form data
     * @return mixed, True on success, false on failures (the rest of the save-process will be
     * skipped!), or null when this method is not actived.
     * @author Oscar van Eijk

      public function plgVmOnUpdateOrderLine(  $_formData) {
      return null;
      }
     */
    /**
     * plgVmOnEditOrderLineBE
     * This method is fired when editing the order line details in the backend.
     * It can be used to add line specific package codes
     *
     * @param integer $_orderId The order ID
     * @param integer $_lineId
     * @return mixed Null for method that aren't active, text (HTML) otherwise
     * @author Oscar van Eijk

      public function plgVmOnEditOrderLineBE(  $_orderId, $_lineId) {
      return null;
      }
     */

    /**
     * This method is fired when showing the order details in the frontend, for every orderline.
     * It can be used to display line specific package codes, e.g. with a link to external tracking and
     * tracing systems
     *
     * @param integer $_orderId The order ID
     * @param integer $_lineId
     * @return mixed Null for method that aren't active, text (HTML) otherwise
     * @author Oscar van Eijk

      public function plgVmOnShowOrderLineFE(  $_orderId, $_lineId) {
      return null;
      }
     */
    function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
	return $this->declarePluginParams('payment', $name, $id, $data);
    }

    function plgVmDeclarePluginParamsPaymentVM3(&$data) {
	return $this->declarePluginParams('payment', $data);
    }
    
    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
		return $this->setOnTablePluginParams($name, $id, $table);
    }

}

// No closing tag
