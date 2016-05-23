<?php
/**
 * Payment plugin for EWay
 *
 * @version 1.0
 * @subpackage Plugins - payment
 * @copyright Copyright (C) 2013 virtuemart.com.au - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 * http://www.virtuemart.com.au
 * @author virtuemart.com.au
 *
 */


defined('_JEXEC') or die('Restricted access');

if (!class_exists('Creditcard')) {
	require_once(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'creditcard.php');
}
if (!class_exists('vmPSPlugin')) {
	require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

define( 'EWAY_DEFAULT_GATEWAY_URL', 'https://www.eway.com.au/gateway_cvn/xmlpayment.asp' );
define( 'EWAY_DEFAULT_CUSTOMER_ID', '91882228' );

define( 'EWAY_CURL_ERROR_OFFSET', 1000 );
define( 'EWAY_XML_ERROR_OFFSET',  2000 );

define( 'EWAY_TRANSACTION_OK',       0 );
define( 'EWAY_TRANSACTION_FAILED',   1 );
define( 'EWAY_TRANSACTION_UNKNOWN',  2 );

class EwayPayment {
	var $paymentPlugin;
    var $parser;
    var $xmlData;
    var $currentTag;
    
    var $myGatewayURL;
    var $myCustomerID;
    
    var $myTotalAmount;
    var $myCustomerFirstname;
    var $myCustomerLastname;
    var $myCustomerEmail;
    var $myCustomerAddress;
    var $myCustomerPostcode;
    var $myCustomerInvoiceDescription;
    var $myCustomerInvoiceRef;
    var $myCardHoldersName;
    var $myCardNumber;
    var $myCardExpiryMonth;
    var $myCardExpiryYear;
    var $myCardCVN;
    var $myTrxnNumber;
    var $myOption1;
    var $myOption2;
    var $myOption3;
    
    var $myResultTrxnStatus;
    var $myResultTrxnNumber;
    var $myResultTrxnOption1;
    var $myResultTrxnOption2;
    var $myResultTrxnOption3;
    var $myResultTrxnReference;
    var $myResultTrxnError;
    var $myResultAuthCode;
    var $myResultReturnAmount;
    
    var $myError;
    var $myErrorMessage;

    /***********************************************************************
     *** Class Constructor                                               ***
     ***********************************************************************/
    function EwayPayment( $customerID = EWAY_DEFAULT_CUSTOMER_ID, $gatewayURL = EWAY_DEFAULT_GATEWAY_URL, $plugin ) {
        $this->myCustomerID = $customerID;
        $this->myGatewayURL = $gatewayURL;
        $this->paymentPlugin = $plugin;
    }


    /***********************************************************************
     *** XML Parser - Callback functions                                 ***
     ***********************************************************************/
    function epXmlElementStart ($parser, $tag, $attributes) {
        $this->currentTag = $tag;
    }
    
    function epXmlElementEnd ($parser, $tag) {
        $this->currentTag = "";
    }
    
    function epXmlData ($parser, $cdata) {
        $this->xmlData[$this->currentTag] = $cdata;
    }
    
    /***********************************************************************
     *** SET values to send to eWAY                                      ***
     ***********************************************************************/
    function setCustomerID( $customerID ) {
        $this->myCustomerID = $customerID;
    }
    
    function setTotalAmount( $totalAmount ) {
        $this->myTotalAmount = $totalAmount;
    }
    
    function setCustomerFirstname( $customerFirstname ) {
        $this->myCustomerFirstname = $customerFirstname;
    }
    
    function setCustomerLastname( $customerLastname ) {
        $this->myCustomerLastname = $customerLastname;
    }
    
    function setCustomerEmail( $customerEmail ) {
        $this->myCustomerEmail = $customerEmail;
    }
    
    function setCustomerAddress( $customerAddress ) {
        $this->myCustomerAddress = $customerAddress;
    }
    
    function setCustomerPostcode( $customerPostcode ) {
        $this->myCustomerPostcode = $customerPostcode;
    }
    
    function setCustomerInvoiceDescription( $customerInvoiceDescription ) {
        $this->myCustomerInvoiceDescription = $customerInvoiceDescription;
    }
    
    function setCustomerInvoiceRef( $customerInvoiceRef ) {
        $this->myCustomerInvoiceRef = $customerInvoiceRef;
    }
    
    function setCardHoldersName( $cardHoldersName ) {
        $this->myCardHoldersName = $cardHoldersName;
    }
    
    function setCardNumber( $cardNumber ) {
        $this->myCardNumber = $cardNumber;
    }
    
    function setCardExpiryMonth( $cardExpiryMonth ) {
        $this->myCardExpiryMonth = $cardExpiryMonth;
    }
    
    function setCardExpiryYear( $cardExpiryYear ) {
        $this->myCardExpiryYear = $cardExpiryYear;
    }
    
    function setCardCVN( $cardCVN ) {
        $this->myCardCVN = $cardCVN;
    }
    
    function setTrxnNumber( $trxnNumber ) {
        $this->myTrxnNumber = $trxnNumber;
    }
    
    function setOption1( $option1 ) {
        $this->myOption1 = $option1;
    }
    
    function setOption2( $option2 ) {
        $this->myOption2 = $option2;
    }
    
    function setOption3( $option3 ) {
        $this->myOption3 = $option3;
    }

    /***********************************************************************
     *** GET values returned by eWAY                                     ***
     ***********************************************************************/
    function getTrxnStatus() {
        return $this->myResultTrxnStatus;
    }
    
    function getTrxnNumber() {
        return $this->myResultTrxnNumber;
    }
    
    function getTrxnOption1() {
        return $this->myResultTrxnOption1;
    }
    
    function getTrxnOption2() {
        return $this->myResultTrxnOption2;
    }
    
    function getTrxnOption3() {
        return $this->myResultTrxnOption3;
    }
    
    function getTrxnReference() {
        return $this->myResultTrxnReference;
    }
    
    function getTrxnError() {
        return $this->myResultTrxnError;
    }
    
    function getAuthCode() {
        return $this->myResultAuthCode;
    }
    
    function getReturnAmount() { 
        return $this->myResultReturnAmount;
    }

    function getError()
    {
        if( $this->myError != 0 ) {
            // Internal Error
            return $this->myError;
        } else {
            // eWAY Error
            if( $this->getTrxnStatus() == 'True' ) {
                return EWAY_TRANSACTION_OK;
            } elseif( $this->getTrxnStatus() == 'False' ) {
                return EWAY_TRANSACTION_FAILED;
            } else {
                return EWAY_TRANSACTION_UNKNOWN;
            }
        }
    }

    function getErrorMessage()
    {
        if( $this->myError != 0 ) {
            // Internal Error
            return $this->myErrorMessage;
        } else {
            // eWAY Error
            return $this->getTrxnError();
        }
    }

    /***********************************************************************
     *** Business Logic                                                  ***
     ***********************************************************************/
    function doPayment() {
        $xmlRequest = "<ewaygateway>".
                "<ewayCustomerID>".htmlentities( $this->myCustomerID )."</ewayCustomerID>".
                "<ewayTotalAmount>".htmlentities( $this->myTotalAmount)."</ewayTotalAmount>".
                "<ewayCustomerFirstName>".htmlentities( $this->myCustomerFirstname )."</ewayCustomerFirstName>".
                "<ewayCustomerLastName>".htmlentities( $this->myCustomerLastname )."</ewayCustomerLastName>".
                "<ewayCustomerEmail>".htmlentities( $this->myCustomerEmail )."</ewayCustomerEmail>".
                "<ewayCustomerAddress>".htmlentities( $this->myCustomerAddress )."</ewayCustomerAddress>".
                "<ewayCustomerPostcode>".htmlentities( $this->myCustomerPostcode )."</ewayCustomerPostcode>".
                "<ewayCustomerInvoiceDescription>".htmlentities( $this->myCustomerInvoiceDescription )."</ewayCustomerInvoiceDescription>".
                "<ewayCustomerInvoiceRef>".htmlentities( $this->myCustomerInvoiceRef )."</ewayCustomerInvoiceRef>".
                "<ewayCardHoldersName>".htmlentities( $this->myCardHoldersName )."</ewayCardHoldersName>".
                "<ewayCardNumber>".htmlentities( $this->myCardNumber )."</ewayCardNumber>".
                "<ewayCardExpiryMonth>".htmlentities( $this->myCardExpiryMonth )."</ewayCardExpiryMonth>".
                "<ewayCardExpiryYear>".htmlentities( $this->myCardExpiryYear )."</ewayCardExpiryYear>".
                "<ewayTrxnNumber>".htmlentities( $this->myTrxnNumber )."</ewayTrxnNumber>".
                "<ewayOption1>".htmlentities( $this->myOption1 )."</ewayOption1>".
                "<ewayOption2>".htmlentities( $this->myOption2 )."</ewayOption2>".
                "<ewayOption3>".htmlentities( $this->myOption3 )."</ewayOption3>".
                "<ewayCVN>".htmlentities( $this->myCardCVN )."</ewayCVN>".
                "</ewaygateway>";
//exit($xmlRequest);
        /* Use CURL to execute XML POST and write output into a string */
        $ch = curl_init( $this->myGatewayURL );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $xmlRequest );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 240 );
        $xmlResponse = curl_exec( $ch );
        $this->paymentPlugin->logInfo($xmlResponse);
        /*
var_dump($xmlResponse);        
var_dump(curl_error($ch));
var_dump(curl_getinfo($ch));
exit;
*/
        // Check whether the curl_exec worked.
        if( curl_errno( $ch ) == CURLE_OK ) {
            // It worked, so setup an XML parser for the result.
            $this->parser = xml_parser_create();
            
            // Disable XML tag capitalisation (Case Folding)
            xml_parser_set_option ($this->parser, XML_OPTION_CASE_FOLDING, FALSE);
            
            // Define Callback functions for XML Parsing
            xml_set_object($this->parser, $this);
            xml_set_element_handler ($this->parser, "epXmlElementStart", "epXmlElementEnd");
            xml_set_character_data_handler ($this->parser, "epXmlData");
            
            // Parse the XML response
            xml_parse($this->parser, $xmlResponse, TRUE);
            
            if( xml_get_error_code( $this->parser ) == XML_ERROR_NONE ) {
                // Get the result into local variables.
                $this->myResultTrxnStatus = $this->xmlData['ewayTrxnStatus'];
                $this->myResultTrxnNumber = $this->xmlData['ewayTrxnNumber'];
                $this->myResultTrxnOption1 = $this->xmlData['ewayTrxnOption1'];
                $this->myResultTrxnOption2 = $this->xmlData['ewayTrxnOption2'];
                $this->myResultTrxnOption3 = $this->xmlData['ewayTrxnOption3'];
                $this->myResultTrxnReference = $this->xmlData['ewayTrxnReference'];
                $this->myResultAuthCode = $this->xmlData['ewayAuthCode'];
                $this->myResultReturnAmount = $this->xmlData['ewayReturnAmount'];
                $this->myResultTrxnError = $this->xmlData['ewayTrxnError'];
                $this->myError = 0;
                $this->myErrorMessage = '';
            } else {
                // An XML error occured. Return the error message and number.
                $this->myError = xml_get_error_code( $this->parser ) + EWAY_XML_ERROR_OFFSET;
                $this->myErrorMessage = xml_error_string( $myError );
            }
            // Clean up our XML parser
            xml_parser_free( $this->parser );
        } else {
            // A CURL Error occured. Return the error message and number. (offset so we can pick the error apart)
            $this->myError = curl_errno( $ch ) + EWAY_CURL_ERROR_OFFSET;
            $this->myErrorMessage = curl_error( $ch );
        }
        // Clean up CURL, and return any error.
        curl_close( $ch );
        return $this->getError();
    }
}

class plgVmpaymentEway extends vmPSPlugin
{

	public function logInfo($msg){
		parent::logInfo($msg);
	}
	
	private $_cc_name = '';
	private $_cc_type = '';
	private $_cc_number = '';
	private $_cc_cvv = '';
	private $_cc_expire_month = '';
	private $_cc_expire_year = '';
	private $_cc_valid = FALSE;
	private $_errormessage = array();

	public $approved;
	public $declined;
	public $error;
	public $held;

	const APPROVED = 1;
	const DECLINED = 2;
	const ERROR = 3;
	const HELD = 4;
	const EWAY_PAYMENT_CURRENCY = "AUD";
	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */
	// instance of class
	function __construct (& $subject, $config)
	{

		parent::__construct($subject, $config);

		$this->_loggable = TRUE;
		$this->_tablepKey = 'id';
		$this->_tableId = 'id';
		$this->tableFields = array_keys($this->getTableSQLFields());
		$varsToPush = $this->getVarsToPush();

		$this->_debug = true;
		$this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
	}

	protected function getVmPluginCreateTableSQL ()
	{

		return $this->createTableSQL('Payment Eway Table');
	}

	function getTableSQLFields ()
	{

		$SQLfields = array(
			'id' => 'int(1) UNSIGNED NOT NULL AUTO_INCREMENT',
			'virtuemart_order_id' => 'int(1) UNSIGNED',
			'order_number' => 'char(64)',
			'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED',
			'payment_name' => 'varchar(5000)',
			'payment_order_total' => 'decimal(15,5) NOT NULL',
			'payment_currency' => 'smallint(1)',
			'return_context' => 'char(255)',
			'cost_per_transaction' => 'decimal(10,2)',
			'cost_percent_total' => 'char(10)',
			'tax_id' => 'smallint(1)',
			'ewayTrxnNumber' => 'varchar(128)',
			'ewayTrxnStatus' => 'varchar(128)',
			'ewayTrxnOption1' => 'varchar(128)',
			'ewayTrxnOption2' => 'varchar(128)',
			'ewayTrxnOption3' => 'varchar(128)',
			'ewayTrxnReference' => 'varchar(128)',
			'ewayAuthCode' => 'varchar(128)',
			'ewayReturnAmount' => 'varchar(128)',
			'ewayTrxnError' => 'varchar(128)',
			'ewayResponseRaw'=>'text',
		);
		return $SQLfields;
	}

	/**
	 * This shows the plugin for choosing in the payment list of the checkout process.
	 *
	 */
	function plgVmDisplayListFEPayment (VirtueMartCart $cart, $selected = 0, &$htmlIn)
	{
		
		//JHTML::_ ('behavior.tooltip');

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
					$this->_cc_type = '';
					$this->_cc_number = '';
					$this->_cc_cvv = '';
					$this->_cc_expire_month = '';
					$this->_cc_expire_year = '';
				}
				
				$creditCards = $method->creditcards;

				$creditCardList = '';
				if ($creditCards) {
					$creditCardList = ($this->_renderCreditCardList($creditCards, $this->_cc_type, $method->virtuemart_paymentmethod_id, FALSE));
				}
				$sandbox_msg = "";
				if ($method->EWAY_TEST_REQUEST) {
					$sandbox_msg .= '<br />' . JText::_('VMPAYMENT_EWAY_SANDBOX_TEST_NUMBERS');
				}

				$cvv_images = $this->_displayCVVImages($method);
				$html .= '<br /><span class="vmpayment_cardinfo">' . JText::_('VMPAYMENT_EWAY_COMPLETE_FORM') . $sandbox_msg . '
		    <table border="0" cellspacing="0" cellpadding="2" width="100%">
		    <tr valign="top">
		        <td nowrap width="10%" align="right">
		        	<label for="creditcardtype">' . JText::_('VMPAYMENT_EWAY_CCTYPE') . '</label>
		        </td>
		        <td>' . $creditCardList .
					'</td>
		    </tr>
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
		        	<label for="cc_type">' . JText::_('VMPAYMENT_EWAY_CCNUM') . '</label>
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

		return TRUE;
	}


	protected function checkConditions ($cart, $method, $cart_prices)
	{

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
			if (!is_array($method->countries)) {
				$countries[0] = $method->countries;
			} else {
				$countries = $method->countries;
			}
		}

		// probably did not gave his BT:ST address
		if (!is_array($address)) {
			$address = array();
			$address['virtuemart_country_id'] = 0;
		}

		if (!isset($address['virtuemart_country_id'])) {
			$address['virtuemart_country_id'] = 0;
		}
		if (count($countries) == 0 || in_array($address['virtuemart_country_id'], $countries) ) {
			return TRUE;
		}

		return FALSE;
	}

	function getCosts (VirtueMartCart $cart, $method, $cart_prices)
	{

		if (preg_match('/%$/', $method->cost_percent_total)) {
			$cost_percent_total = substr($method->cost_percent_total, 0, -1);
		} else {
			$cost_percent_total = $method->cost_percent_total;
		}
		return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
	}

	function _setEwayIntoSession ()
	{

		$session = JFactory::getSession();
		$sessionEway = new stdClass();
		// card information
		$sessionEway->cc_type = $this->_cc_type;
		$sessionEway->cc_number = $this->_cc_number;
		$sessionEway->cc_cvv = $this->_cc_cvv;
		$sessionEway->cc_expire_month = $this->_cc_expire_month;
		$sessionEway->cc_name = $this->_cc_name;
		$sessionEway->cc_expire_year = $this->_cc_expire_year;
		$sessionEway->cc_valid = $this->_cc_valid;
		$session->set('eway', serialize($sessionEway), 'vm');
	}

	function _getEwayFromSession ()
	{

		$session = JFactory::getSession();
		$ewaySession = $session->get('eway', 0, 'vm');
		if (!empty($ewaySession)) {
			$ewayData = unserialize($ewaySession);
			$this->_cc_type = $ewayData->cc_type;
			$this->_cc_number = $ewayData->cc_number;
			$this->_cc_name = $ewayData->cc_name;
			$this->_cc_cvv = $ewayData->cc_cvv;
			$this->_cc_expire_month = $ewayData->cc_expire_month;
			$this->_cc_expire_year = $ewayData->cc_expire_year;
			$this->_cc_valid = $ewayData->cc_valid;
		}
	}


	function plgVmOnCheckoutCheckDataPayment (VirtueMartCart $cart)
	{

		if (!$this->selectedThisByMethodId($cart->virtuemart_paymentmethod_id)) {
			return NULL; // Another method was selected, do nothing
		}
		$this->_getEwayFromSession();
        return $this->_validate_creditcard_data(TRUE);

	}


	function plgVmOnStoreInstallPaymentPluginTable ($jplugin_id)
	{

		return parent::onStoreInstallPluginTable($jplugin_id);
	}


	public function plgVmOnSelectCheckPayment (VirtueMartCart $cart, &$msg)
	{

		if (!$this->selectedThisByMethodId($cart->virtuemart_paymentmethod_id)) {
			return NULL; // Another method was selected, do nothing
		}
		$this->_getEwayFromSession();

		if( $this->_cc_number and $this->_cc_cvv) return true;
		//$cart->creditcard_id = JRequest::getVar('creditcard', '0');
		$this->_cc_type = JRequest::getVar('cc_type_' . $cart->virtuemart_paymentmethod_id, '');
		$this->_cc_name = JRequest::getVar('cc_name_' . $cart->virtuemart_paymentmethod_id, '');
		$this->_cc_number = str_replace(" ", "", JRequest::getVar('cc_number_' . $cart->virtuemart_paymentmethod_id, ''));
		//var_dump($this->_cc_number);exit;
		$this->_cc_cvv = JRequest::getVar('cc_cvv_' . $cart->virtuemart_paymentmethod_id, '');
		$this->_cc_expire_month = JRequest::getVar('cc_expire_month_' . $cart->virtuemart_paymentmethod_id, '');
		$this->_cc_expire_year = JRequest::getVar('cc_expire_year_' . $cart->virtuemart_paymentmethod_id, '');
		if (!$this->_validate_creditcard_data(TRUE)) {
			return FALSE; // returns string containing errors
		}
		$this->_setEwayIntoSession();
		return TRUE;
	}

	public function plgVmOnSelectedCalculatePricePayment (VirtueMartCart $cart, array &$cart_prices, &$payment_name)
	{

		if (!($method = $this->getVmPluginMethod($cart->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return FALSE;
		}

		$this->_getEwayFromSession();
		$cart_prices['payment_tax_id'] = 0;
		$cart_prices['payment_value'] = 0;

		if (!$this->checkConditions($cart, $method, $cart_prices)) {
			return FALSE;
		}
		$payment_name = $this->renderPluginName($method);

		$this->setCartPrices($cart, $cart_prices, $method);

		return TRUE;
	}
	/*
		 * @param $plugin plugin
		 */

	protected function renderPluginName ($plugin)
	{

		$return = '';
		$plugin_name = $this->_psType . '_name';
		$plugin_desc = $this->_psType . '_desc';
		$description = '';
		// 		$params = new JParameter($plugin->$plugin_params);
		// 		$logo = $params->get($this->_psType . '_logos');
		$logosFieldName = $this->_psType . '_logos';
		$logos = $plugin->$logosFieldName;
		if (!empty($logos)) {
			$return = $this->displayLogos($logos) . ' ';
		}
		if (!empty($plugin->$plugin_desc)) {
			$description = '<span class="' . $this->_type . '_description">' . $plugin->$plugin_desc . '</span>';
		}
		$this->_getEwayFromSession();
		$extrainfo = $this->getExtraPluginNameInfo();
		$pluginName = $return . '<span class="' . $this->_type . '_name">' . $plugin->$plugin_name . '</span>' . $description;
		$pluginName .= $extrainfo;
		return $pluginName;
	}

	/**
	 * Display stored payment data for an order
	 *
	 * @see components/com_virtuemart/helpers/vmPaymentPlugin::plgVmOnShowOrderPaymentBE()
	 */
	function plgVmOnShowOrderBEPayment ($virtuemart_order_id, $virtuemart_payment_id)
	{

		if (!$this->selectedThisByMethodId($virtuemart_payment_id)) {
			return NULL; // Another method was selected, do nothing
		}
		if (!($paymentTable = $this->getDataByOrderId($virtuemart_order_id))) {
			return NULL;
		}
		$html = '<table class="adminlist">' . "\n";
		$html .= $this->getHtmlHeaderBE();
		$html .= $this->getHtmlRowBE('EWAY_PAYMENT_NAME', $paymentTable->payment_name);
		$html .= $this->getHtmlRowBE('EWAY_PAYMENT_ORDER_TOTAL', $paymentTable->payment_order_total . " " . self::EWAY_PAYMENT_CURRENCY);
		$html .= $this->getHtmlRowBE('EWAY_COST_PER_TRANSACTION', $paymentTable->cost_per_transaction);
		$html .= $this->getHtmlRowBE('EWAY_COST_PERCENT_TOTAL', $paymentTable->cost_percent_total);
		$code = "eway_response_";
		foreach ($paymentTable as $key => $value) {
			if (substr($key, 0, strlen($code)) == $code) {
				$html .= $this->getHtmlRowBE($key, $value);
			}
		}
		$html .= '</table>' . "\n";
		return $html;
	}

	function plgVmOnPaymentResponseReceived(&$html) {
		$out = '';
		foreach($_REQUEST as $k => $v){
			$out .= $k. '='. $v."\n";
		}
		$this->logInfo('paymentReceived'."\n".$out);
	}
	
	/**
	 * Reimplementation of vmPaymentPlugin::plgVmOnConfirmedOrder()
	 *
	 * Credit Cards Test Numbers
	 * Visa Test Account           4007000000027
	 * Amex Test Account           370000000000002
	 * Master Card Test Account    6011000000000012
	 * Discover Test Account       5424000000000015
	 */
	function plgVmConfirmedOrder (VirtueMartCart $cart, $order)
	{

		if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return FALSE;
		}
		$usrBT = $order['details']['BT'];
		$usrST = ((isset($order['details']['ST'])) ? $order['details']['ST'] : $order['details']['BT']);
		$session = JFactory::getSession();
		$return_context = $session->getId();
		
		define( "GATEWAY_URL", "https://www.eway.com.au/gateway/xmlpayment.asp");
            
        // WE need the $order_total in cents!
        
        
        // We need to show the year with two digits only
        $year = substr( $this->_cc_expire_year, 2, 2 );
        
		$my_trxn_number = uniqid( "eway_" );
		
        $payment_currency_id = shopFunctions::getCurrencyIDByName(self::EWAY_PAYMENT_CURRENCY);
		$paymentCurrency = CurrencyDisplay::getInstance($payment_currency_id);
		$totalInPaymentCurrency = intval($paymentCurrency->convertCurrencyTo(self::EWAY_PAYMENT_CURRENCY, $order['details']['BT']->order_total, FALSE) * 100);
		
        if( $method->EWAY_TEST_REQUEST == "0" ) {
			$eway = new EwayPayment( $method->EWAY_CUSTID, GATEWAY_URL, $this );
		} else {
			$eway = new EwayPayment( '91882228', "https://www.eway.com.au/gateway_cvn/xmltest/testpage.asp",  $this );
		}
		$this->_getEwayFromSession();
		$eway->setCustomerFirstname( $usrBT->first_name );
		$eway->setCustomerLastname( $usrBT->last_name );
		$eway->setCustomerEmail( $usrBT->email );
		$eway->setCustomerAddress( $usrBT->address_1);
		$eway->setCustomerPostcode( $usrBT->zip );
		$eway->setCustomerInvoiceDescription( $usrBT->vendor_name." Order" );
		$eway->setCustomerInvoiceRef( $order->order_number );
		$eway->setCardHoldersName( $this->_cc_name );
		//var_dump($this->_cc_number);exit;
		$eway->setCardNumber( $this->_cc_number );
		$eway->setCardExpiryMonth( $this->_cc_expire_month );
		$eway->setCardExpiryYear( $year + '' );
		$eway->setCardCVN( $this->_cc_cvv );
		$eway->setTrxnNumber( $my_trxn_number );
		$eway->setTotalAmount( $totalInPaymentCurrency );
		$session = JFactory::getSession();
		$return_context = $session->getId();
		
		//var_dump($usrBT);exit;
		$cd = CurrencyDisplay::getInstance($cart->pricesCurrency);
		$dbValues['order_number'] = $order['details']['BT']->order_number;
		$dbValues['virtuemart_order_id'] = $order['details']['BT']->virtuemart_order_id;
		$dbValues['payment_method_id'] = $order['details']['BT']->virtuemart_paymentmethod_id;
		$dbValues['return_context'] = $return_context;
		$dbValues['payment_name'] = parent::renderPluginName($method);
		$dbValues['cost_per_transaction'] = $method->cost_per_transaction;
		$dbValues['cost_percent_total'] = $method->cost_percent_total;
		$dbValues['payment_order_total'] = $totalInPaymentCurrency / 100;
		$dbValues['payment_currency'] = $payment_currency_id;
//var_dump($order);exit;

		if( $eway->doPayment() == EWAY_TRANSACTION_OK ) {
            //Catch Transaction ID
            $new_status = $method->payment_approved_status;
            $this->_clearEwaySession();
		} 
        else {
			vmError( JText::_('PHPSHOP_PAYMENT_ERROR',false).": "
                            .$eway->getErrorMessage() );
             $new_status = $method->payment_declined_status;
          // return false;
		}
		$dbValues["ewayTrxnNumber"] = $eway->getTrxnNumber();
		$dbValues["ewayTrxnStatus"] = $eway->getTrxnStatus();
		$dbValues["ewayTrxnError"] = $eway->getTrxnError();
		$dbValues["ewayReturnAmount"] = $eway->getReturnAmount();
		$dbValues["ewayTrxnOption1"] = $eway->getTrxnOption1();
		$dbValues["ewayTrxnOption2"] = $eway->getTrxnOption2();
		$dbValues["ewayTrxnOption3"] = $eway->getTrxnOption3();
		
		$this->storePSPluginInternalData($dbValues);
		
		$modelOrder = VmModel::getModel('orders');
		$order['order_status'] = $new_status;
		$order['customer_notified'] = 1;
		$order['comments'] = '';
		$modelOrder->updateStatusForOneOrder($order['details']['BT']->virtuemart_order_id, $order, TRUE);

		//We delete the old stuff
		$cart->emptyCart();
		$html = '<table>
			<tr><td>Order Number</td><td>'.$dbValues["order_number"].'</td></tr>
			<tr><td>Order Status</td><td>'.($new_status == $method->payment_approved_status ? 'Confirmed' : 'Cancelled').'</td></tr>
			<tr><td>Eway Transaction Number</td><td>'.$dbValues["ewayTrxnNumber"].'</td></tr>
			<tr><td>Eway Transaction Status</td><td>'.$dbValues["ewayTrxnStatus"].'</td></tr>
			<tr><td>Eway Transaction Error</td><td>'.$dbValues["ewayTrxnError"].'</td></tr>
			<tr><td></td><td></td></tr>
		</table>';
		JRequest::setVar('html', $html);
		return true;
	}


    
	function _handlePaymentCancel ($virtuemart_order_id, $html)
	{

		if (!class_exists('VirtueMartModelOrders')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}
		$modelOrder = VmModel::getModel('orders');
		$modelOrder->remove(array('virtuemart_order_id' => $virtuemart_order_id));
		// error while processing the payment
		$mainframe = JFactory::getApplication();
		$mainframe->enqueueMessage($html);
		$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart&task=editpayment'), JText::_('COM_VIRTUEMART_CART_ORDERDONE_DATA_NOT_VALID'));
	}

	function plgVmGetPaymentCurrency ($virtuemart_paymentmethod_id, &$paymentCurrencyId)
	{

		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return FALSE;
		}

		if (!class_exists('VirtueMartModelVendor')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'vendor.php');
		}
		$vendorId = 1; //VirtueMartModelVendor::getLoggedVendor();
		$db = JFactory::getDBO();
/*
		$q = 'SELECT   `virtuemart_currency_id` FROM `#__virtuemart_currencies` WHERE `currency_code_3`= "'.self::AUTHORIZE_PAYMENT_CURRENCY.'"';
		$db->setQuery($q);
		$paymentCurrencyId = $db->loadResult();
*/
	}

	function _clearEwaySession ()
	{
		$session = JFactory::getSession();
		$session->clear('authorizenet', 'vm');
	}

	/**
	 * renderPluginName
	 * Get the name of the payment method
	 *
	 * @param  $payment
	 * @return string Payment method name
	 */
	function getExtraPluginNameInfo ()
	{
		return '';
		$creditCardInfos = '';
		if ($this->_validate_creditcard_data(FALSE)) {
			$cc_number = "**** **** **** " . substr($this->_cc_number, -4);
			$creditCardInfos .= '<br /><span class="vmpayment_cardinfo">' . JText::_('VMPAYMENT_EWAY_CCTYPE') . $this->_cc_type . '<br />';
			$creditCardInfos .= JText::_('VMPAYMENT_EWAY_CCNUM') . $cc_number . '<br />';
			$creditCardInfos .= JText::_('VMPAYMENT_EWAY_CVV2') . '****' . '<br />';
			$creditCardInfos .= JText::_('VMPAYMENT_EWAY_EXDATE') . $this->_cc_expire_month . '/' . $this->_cc_expire_year;
			$creditCardInfos .= "</span>";
		}
		return $creditCardInfos;
	}

	/**
	 * Creates a Drop Down list of available Creditcards
	 *
	 */
	function _renderCreditCardList ($creditCards, $selected_cc_type, $paymentmethod_id, $multiple = FALSE, $attrs = '')
	{

		$idA = $id = 'cc_type_' . $paymentmethod_id;
		//$options[] = JHTML::_('select.option', '', JText::_('VMPAYMENT_EWAY_SELECT_CC_TYPE'), 'creditcard_type', $name);
		if (!is_array($creditCards)) {
			$creditCards = (array)$creditCards;
		}
		foreach ($creditCards as $creditCard) {
			$options[] = JHTML::_('select.option', $creditCard, JText::_('VMPAYMENT_EWAY_' . strtoupper($creditCard)));
		}
		if ($multiple) {
			$attrs = 'multiple="multiple"';
			$idA .= '[]';
		}
		return JHTML::_('select.genericlist', $options, $idA, $attrs, 'value', 'text', $selected_cc_type);
	}


	function _validate_creditcard_data ($enqueueMessage = TRUE)
	{
		//return true;
		$html = '';
		$this->_cc_valid = TRUE;

		if (!Creditcard::validate_credit_card_number($this->_cc_type, $this->_cc_number)) {
			//$this->_errormessage[] = 'VMPAYMENT_EWAY_CARD_NUMBER_INVALID';
			$this->_cc_valid = FALSE;
		}

		if (!Creditcard::validate_credit_card_cvv($this->_cc_type, $this->_cc_cvv)) {
			//$this->_errormessage[] = 'VMPAYMENT_EWAY_CARD_CVV_INVALID';
			$this->_cc_valid = FALSE;
		}
		if (!Creditcard::validate_credit_card_date($this->_cc_type, $this->_cc_expire_month, $this->_cc_expire_year)) {
			//$this->_errormessage[] = 'VMPAYMENT_EWAY_CARD_CVV_INVALID';
			$this->_cc_valid = FALSE;
		}
		if (!$this->_cc_valid) {
			//$html.= "<ul>";
			foreach ($this->_errormessage as $msg) {
				//$html .= "<li>" . Jtext::_($msg) . "</li>";
				$html .= Jtext::_($msg) . "<br/>";
			}
			//$html.= "</ul>";
		}
		if (!$this->_cc_valid && $enqueueMessage) {
			$app = JFactory::getApplication();
			$app->enqueueMessage($html);
		}

		return $this->_cc_valid;
	}

	function _getLoginId ($method)
	{

		return $method->sandbox ? $method->sandbox_login_id : $method->login_id;
	}

	function _getTransactionKey ($method)
	{

		return $method->get('sandbox') ? $method->sandbox_transaction_key : $method->transaction_key;
	}

	function _recurringPayment ($method)
	{

		return ''; //$params->get('recurring_payment', '0');
	}

	/**
	 * _getFormattedDate
	 *
	 *
	 */
	function _getFormattedDate ($month, $year)
	{

		return sprintf('%02d-%04d', $month, $year);
	}

	function _setHeader ()
	{

		return $this->_eway_params;
	}
	/**
	 * Proceeds the simple payment
	 *
	 * @param string $resp
	 * @param array  $submitted_values
	 * @return object Message object
	 *
	 */
	function _handleResponse ($response, $submitted_values, $order, $payment_name)
	{

		
	}

	/**
	 * displays the CVV images of for CVV tooltip plugin
	 *
	 * @param array $logo_list
	 * @return html with logos
	 */
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


	/**
	 * plgVmOnCheckAutomaticSelectedPayment
	 * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
	 * The plugin must check first if it is the correct type
	 *
	 * @param VirtueMartCart cart: the cart object
	 * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
	 *
	 */

	function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {

		$return = $this->onCheckAutomaticSelected($cart, $cart_prices);
		if (isset($return)) {
			return 0;
		} else {
			return NULL;
		}
	}

	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the method-specific data.
	 *
	 * @param integer $order_id The order ID
	 * @return mixed Null for methods that aren't active, text (HTML) otherwise
	 */
	protected function plgVmOnShowOrderFEPayment ($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name)
	{

		$this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
		return TRUE;
	}

	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id The order ID
	 * @param integer $method_id  method used for this order
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 */
	function plgVmOnShowOrderPrintPayment ($order_number, $method_id)
	{

		return parent::onShowOrderPrint($order_number, $method_id);
	}
	/**
	 * This method is fired when showing the order details in the frontend, for every orderline.
	 * It can be used to display line specific package codes, e.g. with a link to external tracking and
	 * tracing systems
	 *
	 * @param integer $_orderId The order ID
	 * @param integer $_lineId
	 * @return mixed Null for method that aren't active, text (HTML) otherwise

	public function plgVmOnShowOrderLineFE(  $_orderId, $_lineId) {
	return null;
	}
	 */
	function plgVmDeclarePluginParamsPayment ($name, $id, &$data)
	{

		return $this->declarePluginParams('payment', $name, $id, $data);
	}

    function plgVmDeclarePluginParamsPaymentVM3(&$data) {
		return $this->declarePluginParams('payment', $data);
    }
    
	
	function plgVmSetOnTablePluginParamsPayment ($name, $id, &$table)
	{

		return $this->setOnTablePluginParams($name, $id, $table);
	}

}

// No closing tag
