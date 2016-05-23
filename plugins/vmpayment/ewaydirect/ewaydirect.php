<?php
/**
 *  Eway Direct Post for virtuemart 2/3
 *  @copyright Copyright (C) 2015 www.virtuemart.com.au - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * 
 *
 * @author viruemart australia 
 */
defined('_JEXEC') or die('Restricted access');
if (!class_exists('Creditcard')) {
	require_once(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'creditcard.php');
}
if (!class_exists('vmPSPlugin'))
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

class plgVMPaymentEwayDirect extends vmPSPlugin {

	private $_cc_name = '';
	private $_cc_number = '';
	private $_cc_cvv = '';
	private $_cc_expire_month = '';
	private $_cc_expire_year = '';

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
		$i =0;
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
		$this->_getEWayFromSession();
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
				'CardDetails' => [
           			 'Name' => $this->_cc_name,
           			 'Number' => $this->_cc_number,
            			 'ExpiryMonth' => $this->_cc_expire_month,
            			'ExpiryYear' => $this->_cc_expire_year,
            			'CVN' => $this->_cc_cvv,
       			 ]
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
			'LogoUrl' => 'https://mysite.com/images/logo4eway.jpg',
			'Language' => 'EN',
		];

		$response = $client->createTransaction(\Eway\Rapid\Enum\ApiMethod::DIRECT, $transaction);

		if ($response->TransactionStatus) {
			echo 'Payment successful!<br> ';
			$order['customer_notified'] = 1;
			$new_status = $method->status_success;
			$this->_clearEwaySession();
			$this->emptyCart($return_context);
			
		} else {
			$new_status = $method->status_cancelled;
			if ($response->getErrors()) {
				foreach ($response->getErrors() as $error) {
					echo "Error: ".\Eway\Rapid::getMessage($error)."<br>";
				}
			} else {
				echo 'Sorry, your payment was declined';
			}
		}
		ob_start();
		print_r($response);
		$dbValues['eway_response_raw'] = ob_get_clean();
//var_dump($dbValues['eway_response_raw']);
        	$this->storePSPluginInternalData($dbValues);
		$order['order_status'] = $new_status;
		$order['comments'] = '';
		$modelOrder = new VirtueMartModelOrders();
		$virtuemart_order_id = $modelOrder->getOrderIdByOrderNumber($order['details']['BT']->order_number);
		$modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
//var_dump($response);
		$html .= '<table>';
		$html .= $this->getHtmlRow('EWAY_PAYMENT_NAME', $this->renderPluginName($method));
		$html .= $this->getHtmlRow('EWAY_ORDER_NUMBER', $order['details']['BT']->order_number );
		$html .= $this->getHtmlRow('Order Status', !$response->TransactionStatus ? 'Cancelled' : 'Confirmed' );
	       $html .= $this->getHtmlRow('eWay Response Code', $response->ResponseCode );
		$html .= $this->getHtmlRow('eWay TransactionID', $response->TransactionID );
		//$html .= $this->getHtmlRow('eWay Response Message', $ra->ResponseMessage );
		
		$html .= $this->getHtmlRow('EWAY_AMOUNT', '$'.$response->Payment->TotalAmount / 100);
		
		$html .= '</table>' . "\n";
        

        $this->logInfo('plgVmOnPaymentNotification return new_status' . $new_status, 'message');

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
		if (!$this->selectedThisByMethodId($cart->virtuemart_paymentmethod_id)) {
			return NULL; // Another method was selected, do nothing
		}
		$this->_getEwayFromSession();

		if( $this->_cc_number and $this->_cc_cvv) return true;
		//$cart->creditcard_id = JRequest::getVar('creditcard', '0');
		$this->_cc_name = JRequest::getVar('cc_name_' . $cart->virtuemart_paymentmethod_id, '');
		$this->_cc_number = str_replace(" ", "", JRequest::getVar('cc_number_' . $cart->virtuemart_paymentmethod_id, ''));
		//var_dump($this->_cc_number);exit;
		$this->_cc_cvv = JRequest::getVar('cc_cvv_' . $cart->virtuemart_paymentmethod_id, '');
		$this->_cc_expire_month = JRequest::getVar('cc_expire_month_' . $cart->virtuemart_paymentmethod_id, '');
		$this->_cc_expire_year = JRequest::getVar('cc_expire_year_' . $cart->virtuemart_paymentmethod_id, '');
		
		$this->_setEwayIntoSession();
		return TRUE;
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
	if ($this->getPluginMethods($cart->vendorId) === 0) {
			if (empty($this->_name)) {
				$app = JFactory::getApplication();
				$app->enqueueMessage(JText::_('COM_VIRTUEMART_CART_NO_' . strtoupper($this->_psType)));
				return FALSE;
			} else {
				return FALSE;
			}
		}
		$html = array();
		$method_name = $this->_psType . '_name';

		JHTML::script('vmcreditcard.js', 'components/com_virtuemart/assets/js/', FALSE);
		JFactory::getLanguage()->load('com_virtuemart');
		vmJsApi::jCreditCard();
		$htmla = '';
		$html = array();
		foreach ($this->methods as $method) {
			if ($this->checkConditions($cart, $method, $cart->pricesUnformatted)) {
				$methodSalesPrice = $this->setCartPrices($cart, $cart->pricesUnformatted, $method);
				$method->$method_name = $this->renderPluginName($method);
				$html = $this->getPluginHtml($method, $selected, $methodSalesPrice);
				if ($selected == $method->virtuemart_paymentmethod_id) {
					$this->_getEwayFromSession();
				} else {
					$this->_cc_number = '';
					$this->_cc_cvv = '';
					$this->_cc_expire_month = '';
					$this->_cc_expire_year = '';
				}
				
				$creditCards = $method->creditcards;

				$sandbox_msg = "";
				if ($method->EWAY_TEST_REQUEST) {
					$sandbox_msg .= '<br />' . JText::_('VMPAYMENT_EWAY_SANDBOX_TEST_NUMBERS');
				}

				$cvv_images = $this->_displayCVVImages($method);
				$html .= '<br /><span class="vmpayment_cardinfo">' . JText::_('VMPAYMENT_EWAY_COMPLETE_FORM') . $sandbox_msg . '
		    <table border="0" cellspacing="0" cellpadding="2" width="100%">
		    <tr valign="top">
					<td nowrap width="10%" align="right">
						<label for="cc_nameoncard">'.JText::_('VMPAYMENT_EWAY_NAMEONCARD').'</label>
					</td>
					<td>
						<input type="text" class="inputbox" id="cc_name' . $method->virtuemart_paymentmethod_id . '" name="cc_name_' . $method->virtuemart_paymentmethod_id . '" size="10" value="' . $this->_cc_name . '" autocomplete="off" />
					</td>
		    </tr>
		    <tr valign="top">
		        <td nowrap width="10%" align="right">
		        	<label for="cc_num">' . JText::_('VMPAYMENT_EWAY_CCNUM') . '</label>
		        </td>
		        <td>
				<script type="text/javascript">
				//<![CDATA[  
				  function checkEway(id, el)
				   {
				     ccError=razCCerror(id);
					CheckCreditCardNumber(el.value, id);
					if (!ccError) {
					el.value=\'\';}
				   }
				//]]> </script>
		        <input type="text" class="inputbox" id="cc_number_' . $method->virtuemart_paymentmethod_id . '" name="cc_number_' . $method->virtuemart_paymentmethod_id . '" value="' . $this->_cc_number . '"    autocomplete="off"   onchange="javascript:checkEway(' . $method->virtuemart_paymentmethod_id . ', this);"  />
		        <div id="cc_cardnumber_errormsg_' . $method->virtuemart_paymentmethod_id . '"></div>
		    </td>
		    </tr>
		    <tr valign="top">
		        <td nowrap width="10%" align="right">
		        	<label for="cc_cvv">' . JText::_('VMPAYMENT_EWAY_CVV2') . '</label>
		        </td>
		        <td>
		            <input type="text" class="inputbox" id="cc_cvv_' . $method->virtuemart_paymentmethod_id . '" name="cc_cvv_' . $method->virtuemart_paymentmethod_id . '" maxlength="4" size="5" value="' . $this->_cc_cvv . '" autocomplete="off" />

			<span class="hasTip" title="' . JText::_('VMPAYMENT_EWAY_WHATISCVV') . '::' . JText::sprintf("VMPAYMENT_EWAY_WHATISCVV_TOOLTIP", $cvv_images) . ' ">' .
					JText::_('VMPAYMENT_EWAY_WHATISCVV') . '
			</span></td>
		    </tr>
		    <tr>
		        <td nowrap width="10%" align="right">' . JText::_('VMPAYMENT_EWAY_EXDATE') . '</td>
		        <td> ';
				$html .= shopfunctions::listMonths('cc_expire_month_' . $method->virtuemart_paymentmethod_id, $this->_cc_expire_month);
				$html .= " / ";
				$html .= '
				<script type="text/javascript">
				//<![CDATA[  
				  function changeDate(id, el)
				   {
				     var month = document.getElementById(\'cc_expire_month_\'+id); if(!CreditCardisExpiryDate(month.value,el.value, id))
					 {el.value=\'\';
					 month.value=\'\';}
				   }
				//]]> 
				</script>';
				$html .= shopfunctions::listYears('cc_expire_year_' . $method->virtuemart_paymentmethod_id, $this->_cc_expire_year, NULL, 2022, " onchange=\"javascript:changeDate(" . $method->virtuemart_paymentmethod_id . ", this);\" ");
				$html .= '<div id="cc_expiredate_errormsg_' . $method->virtuemart_paymentmethod_id . '"></div>';
				$html .= '</td>  </tr>  	</table></span>';

				$htmla[] = $html;
			}
		}
		$htmlIn[] = $htmla;
    }
	function _getEwayFromSession ()
	{

		$session = JFactory::getSession();
		$ewaySession = $session->get('eway', 0, 'vm');
		if (!empty($ewaySession)) {
			$ewayData = unserialize($ewaySession);
			$this->_cc_number = $ewayData->cc_number;
			$this->_cc_name = $ewayData->cc_name;
			$this->_cc_cvv = $ewayData->cc_cvv;
			$this->_cc_expire_month = $ewayData->cc_expire_month;
			$this->_cc_expire_year = $ewayData->cc_expire_year;
			$this->_cc_valid = $ewayData->cc_valid;
		}
	}
	function _clearEwaySession ()
	{
		$session = JFactory::getSession();
		$session->clear('authorizenet', 'vm');
	}
	function _setEwayIntoSession ()
	{

		$session = JFactory::getSession();
		$sessionEway = new stdClass();
		// card information
		$sessionEway->cc_number = $this->_cc_number;
		$sessionEway->cc_cvv = $this->_cc_cvv;
		$sessionEway->cc_expire_month = $this->_cc_expire_month;
		$sessionEway->cc_name = $this->_cc_name;
		$sessionEway->cc_expire_year = $this->_cc_expire_year;
		$sessionEway->cc_valid = $this->_cc_valid;
		$session->set('eway', serialize($sessionEway), 'vm');
	}
	public function _displayCVVImages ($method)
	{

		$cvv_images = $method->cvv_images;
		$img = '';
		if ($cvv_images) {
			$img = $this->displayLogos($cvv_images);
			$img = str_replace('"', "'", $img);
		}
		return $img;
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
