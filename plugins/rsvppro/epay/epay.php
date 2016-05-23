<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

class plgRsvpproEPay extends JPlugin
{

	function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
	}

	private function _soapcall()
	{
		$client = new SoapClient('https://ssl.ditonlinebetalingssystem.dk/remote/payment.asmx?WSDL');

		return $client;
	}
	public function onRegistrationStatusChange($attendee, $eventrepeat, $rsvpdata) {



			
			$db = JFactory::getDbo();
			$db->setQuery("SELECT logdata FROM #__jev_rsvp_transactions WHERE attendee_id = ".intval($attendee->id));
		 	$logdatafield = $db->loadObjectList();
	
			
		if($logdatafield[0]->logdata <> '')
		{
		
			
			$logdata = json_decode($logdatafield[0]->logdata);			
			$request = $logdata->request;

				
				// Get Merchant number and EPay API Password
				$plugin = JPluginHelper::getPlugin("rsvppro","epay");
				$params = new JRegistry($plugin->params);
				$payee = $params->get("DefaultEPayAccount");
				$apisecret = $params->get("APISecret");
				$epay_params = array();
				$epay_params['merchantnumber'] = $payee;
				$epay_params['transactionid'] = $request->txnid;
				$epay_params['amount'] = $request->amount;
				$epay_params['pwd'] = $apisecret;
				$epay_params['epayresponse'] = "-1";
				$epay_params['pbsresponse'] = "-1";
				$result = $this->_soapcall()->credit($epay_params);
				return $result;
		
		

		}
		

	}
	
	
	public static function NotifyPayment($templateParams) {
      return $templateParams->get("notifyepay", 1);
   }

   public static function PaymentMessageType() {
      return "epay";
   }
	
	
	
	 public function generatePaymentPage(&$html, $attendee, $rsvpdata, $event, &$transaction){

		$plugin = JPluginHelper::getPlugin("rsvppro","epay");
		$params = new JRegistry($plugin->params);

		// TODO transaction based invoiceid
		$invoice = JRequest::getString("invoiceid","").'xx'.$transaction->transaction_id;;
		$rand = uniqid("inv_");
		$html .= "Invoice id is $invoice<br/>";
		$amount = RsvpHelper::ceil_dec(JRequest::getFloat("amount",0), 2, ".");
		//Multiply amount with 100 to remove decimals
		$amount = $amount *100;
		$mainframe = JFactory::getApplication();
		$Itemid=JRequest::getInt("Itemid");
		$detaillink = JRoute::_( JUri::root() . $event->viewDetailLink($event->yup(),$event->mup(),$event->dup(),false),false);;

		// TODO pick the payee etc from the session!
		$payee = $params->get("DefaultEPayAccount");
		$currency = $params->get("Defaultcurrency","GBP");
		$code = "";
		$em = JRequest::getString("em","");
		if ($em != ""){
			$code = "&em=$em";
		}

		if (isset($rsvpdata->template) &&  is_numeric($rsvpdata->template)){
			$db = JFactory::getDBO();
			$db->setQuery("Select params from #__jev_rsvp_templates where id=".intval($rsvpdata->template));
			$templateParams = $db->loadObject();
			if ($templateParams){
				$templateParams =  json_decode($templateParams->params);
			}
			else {
				$templateParams = $params;
			}
		}
		else {
			$templateParams = $params;
		}

		$html =   $params->get("template","Invoiceid = {INVOICEID}<br/>Total Fees = {TOTALFEES}<br/>Fees Already Paid= {FEESPAID}<br/>Outstanding Balance = {BALANCE}<br/><br/>Please proceed to epay ...<br/><br/>{FORM}");
		if (isset($templateParams->template)){
			//$templateParams->template = nl2br($templateParams->template );
			$html =   $templateParams->template;
		}
		if ($html == ""){
			$html =   "Invoiceid = {INVOICEID}<br/>Total Fees = {TOTALFEES}<br/>Fees Already Paid= {FEESPAID}<br/>Outstanding Balance = {BALANCE}<br/><br/>Please proceed to epay ...<br/><br/>{FORM}";
		}
		if (isset($attendee->outstandingBalances)){
			$html = str_replace("{INVOICEID}",$invoice, $html);
			$html = str_replace("{TOTALFEES}",$this->phpMoneyFormat($attendee->outstandingBalances['totalfee'], $templateParams), $html);
			$html = str_replace("{FEESPAID}",$this->phpMoneyFormat($attendee->outstandingBalances['feepaid'], $templateParams), $html);
			$html = str_replace("{BALANCE}",$this->phpMoneyFormat($attendee->outstandingBalances['feebalance'], $templateParams), $html);
			$html = str_replace("{DEPOSIT}",$this->phpMoneyFormat($attendee->outstandingBalances['deposit'], $templateParams), $html);
		}
		

		$comparams = JComponentHelper::getParams("com_rsvppro");
		$user = JEVHelper::getUser($attendee->user_id);
		if ($user->id == 0 && $comparams->get("attendemails", 0))
		{
			$name = $attendee->email_address;
			$username = $attendee->email_address;
		}
		else
		{
			$name = $user->name;
			$username = $user->username;
		}
		
		include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/attendeehelper.php");
		$this->helper = new RsvpAttendeeHelper($comparams);
		
		$html = $this->helper->parseMessage($html, $rsvpdata, $event, $name, $username, $attendee, false, false, $transaction);

		 $currency = isset($templateParams->Currency)? $templateParams->Currency:$currency;
		 $payee = isset($templateParams->DefaultEPayAccount)? $templateParams->DefaultEPayAccount:$payee;
		// $mode =  isset($templateParams->EPayMode)?$templateParams->EPayMode : $params->get("EPayMode","www.sandbox.epay.com");
		 $start_date = JEventsHTML::getDateFormat( $event->yup(), $event->mup(), $event->dup(), 0 );
		 
		 $autoredirect = isset($templateParams->autoredirect)? $templateParams->autoredirect:$params->get("autoredirect",1);
		ob_start();
        $noshipping = isset($templateParams->request_shipping)? $templateParams->request_shipping:$params->get("request_shipping",1);;
		$shipping = $noshipping? 0 : 1;
		$md5params = array('merchantnumber' => $payee, 'amount' => $amount, 'currency' => $currency, 'windowstate' => '3', 'instantcapture' => '1', 'callbackurl' => JURI::root()."index.php?option=com_rsvppro&task=accounts.notify&gateway=epay", 'accepturl' => $detaillink.$code, 'cancelurl' => $detaillink.$code, 'orderid' => $invoice);



		?>
		
		<form name="redirectToEPay" action="https://ssl.ditonlinebetalingssystem.dk/integration/ewindow/Default.aspx" method="post">
			<input type="hidden" name="merchantnumber" value="<?php echo $payee;?>">
			<input type="hidden" name="amount" value="<?php echo $amount;?>"> 
			<input type="hidden" name="currency" value="<?php echo $currency; ?>">				
			<input type="hidden" name="windowstate" value="3">
			<input type="hidden" name="instantcapture" value="1">
			<input type="hidden" name="callbackurl" value="<?php echo JURI::root()."index.php?option=com_rsvppro&task=accounts.notify&gateway=epay"; ?>" />
			<input type="hidden" name="accepturl" value="<?php echo $detaillink.$code; ?>" />
			<input type="hidden" name="cancelurl" value="<?php echo $detaillink.$code; ?>" />			
			<input type="hidden" name="orderid" value="<?php echo $invoice;?>" />
			<input type="hidden" name="hash" value="<?php echo md5(implode("", array_values($md5params)) . $params->get("MD5Secret")); ?>" />
			<input type="submit" value="<?php echo JText::_("RSVP_PROCEED_WITH_REDIRECT"); ?>">

		</form>
		
		

		<?php
		$form = ob_get_clean();
		if ($autoredirect){
			$form .= "<script type='text/javascript'>function sendToEPay(){document.redirectToEPay.submit()}; setTimeout('sendToEPay()',3000);</script>";
		}
		// Make sure HTML includes the form (this is often forgotton!)
		if (strpos($html,"{FORM}")===false){
			$html .= "<br/>{FORM}";
		}
		
		$html = str_replace("{FORM}",$form, $html);

		// setup transaction data
		$transaction->amount = $amount/100;
		$transaction->currency = $currency;
		$transaction->attendee_id = $attendee->id;
		$transaction->gateway = "epay";

		$transaction->params = new stdClass();
		$transaction->params->payee = $payee;
		$transaction->params = json_encode($transaction->params);
		
		$transaction->store();
		
		$this->notifyEpayPayment($transaction, $attendee, $rsvpdata);
		
	}

	static public function transactionDetailLink($transaction){


		$plugin = JPluginHelper::getPlugin("rsvppro","epay");
		$params = new JRegistry($plugin->params);
		$transactionLogdata = json_decode($transaction->logdata);
		if (!is_object($transactionLogdata) || !isset($transactionLogdata->request)) return "";
		return  '<a href="https://ssl.ditonlinebetalingssystem.dk/admin/transactions_info.asp?tid=' . $transactionLogdata->request->txnid . '" target="_blank">' . $transactionLogdata->request->txnid . '</a>' ;
	}

	public function activeGatewayClass(&$activeGatewayClass, $action="notify"){
		$gateway = JRequest::getString("gateway");

		if ($gateway == "epay" || $gateway == "1" || strpos($gateway,"epay_")===0){
			$activeGatewayClass = __CLASS__;
		}
	}

	public function activeGateways(&$activeGatewayClasses){
			$activeGatewayClasses[] = __CLASS__;
	}

	static public function notifyRsvpGateway(&$args){

		$gateway = JRequest::getString("gateway");

		if ($gateway == "epay" || $gateway == "1"){

			$plugin = JPluginHelper::getPlugin("rsvppro","epay");
			$dispatcher = JDispatcher::getInstance();
			$epayPlugin = new plgRsvpproEPay($dispatcher,  (array)($plugin));

			$epayPlugin->EPay_Response();
		}

	}


	protected /**
 *  EPay_Response
 *  Verifies IPN transaction with EPay server
 */
	function EPay_Response()
	{

		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		$logText  = "IPN Response - " . gmstrftime ("%b %d %Y %H:%M:%S", time()) . "\n";

		// get reveiver email for log messages
		$receiver_email  =   strval( JRequest::getVar( 'receiver_email', '' ) );
		
		// get the invoice ID - will be needed for to get the transaction and rsvpdata
		$invoice = JRequest::getString("orderid","0_0");

		$parts = explode("xx",$invoice);
		
		if (count($parts)!=2){
			$logText .= "Bad invoice id!\n";
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->EPay_Log( $logText );
			$this->EPay_Error( $logText  , $receiver_email);
			return;
		}
		JArrayHelper::toInteger($parts);
		list($attendee_id,$transaction_id) = $parts;
		
		// Load the transaction instance		
		$transaction =new rsvpTransaction( );

		if ( !$transaction->load( $transaction_id ) )
		{
			$logText .= "Error loading rsvpTransaction instance $transaction_id\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->EPay_Log( $logText );
			$this->EPay_Error( $logText  ,$receiver_email );
			return;
		}
		$transactionData = json_decode($transaction->params);
		
				
		
		// fetch the session and the attendee
		
		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__jev_attendees WHERE id=" . $attendee_id;
		$db->setQuery($sql);
		$attendee = $db->loadObject();
		if (!$attendee)
		{
			$logText .= "Error loading attendee instance $attendee_id\n";
			$logText .= $db->getErrorMsg();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->EPay_Log( $logText );
			$this->EPay_Error( $logText  ,$receiver_email );
			return;
		}
		
		
		
		
		
		
		$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
		$db->setQuery($sql);
		$rsvpdata = $db->loadObject();
		if (!$rsvpdata)
		{
			$logText .= "Error loading rsvpdata instance $attendee->at_id\n";
			$logText .= $db->getErrorMsg();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->EPay_Log( $logText );
			$this->EPay_Error( $logText  ,$receiver_email );
			return;
		}
		
		if (isset($rsvpdata->template) &&  is_numeric($rsvpdata->template)){
			$db = JFactory::getDBO();
			$db->setQuery("Select params from #__jev_rsvp_templates where id=".intval($rsvpdata->template));
			$templateParams = $db->loadObject();
			if ($templateParams){
				$templateParams =  json_decode($templateParams->params);
			}
			else {
				$templateParams = $params;
			}
		}
		else {
			$templateParams = $params;
		}
		
		// read the post from EPay system and add 'cmd'
		$req = 'cmd=_notify-validate';

		foreach ( $_GET as $key => $value )
		{
			 if (get_magic_quotes_gpc()){
			         // Fix issue with magic quotes
				$value = stripslashes ($value);
			 }

			$value = urlencode(  $value  );
			$req .= "&$key=$value";
		}
		
		
		
		// Check the MD5 Key
		
		$GETparams = $_GET;
		$plugin = JPluginHelper::getPlugin("rsvppro","epay");
		$MD5params = new JRegistry($plugin->params);
		$var = "";
  
		foreach ($GETparams as $key => $value)
		{
			if($key != "hash")
			{
				$var .= $value;
			}
		}

		$genstamp = md5($var . $MD5params->get("MD5Secret"));
  
		if($genstamp != $_GET["hash"])
		{
			$logText .= "Error: Wrong MD5 key\n";
			$this->EPay_Log( $logText );
			$this->EPay_Error( $logText  ,$receiver_email );
			return;
		}
		else
		{
     //Hash is OK    
		}
		
		
		
		

			
			
		 //$mode =  isset($templateParams->EPayMode)?$templateParams->EPayMode : $this->params->get("EPayMode","www.sandbox.epay.com");
		 $currency = isset($templateParams->Currency)? $templateParams->Currency:$this->params->get("Defaultcurrency","GBP");
		 $payee = isset($templateParams->DefaultEPayAccount)? $templateParams->DefaultEPayAccount:$this->params->get("DefaultEPayAccount");;

				
		

		// So far so good.  Now, we need to check the specifics of what went on.
		// To do that, safely extract some information for POST to use in the queries.
		$txnid 		 =   strval( JRequest::getVar(         'txnid', '' ) );
		$receiver_email  =   strval( JRequest::getVar( 'receiver_email', '' ) );
		$currency        =   strval( JRequest::getVar(    'currency', '' ) );
		$amount          = floatval( JRequest::getVar(    'amount',  0 ) );
		$amount = $amount/100;
		$status          =   strval( JRequest::getVar( 'payment_status', 'Completed' ) );
		

		
		// check the payment_status is Completed
		if ( strcmp( $status, 'Completed' ) != 0  &&  strcmp( $status, 'Refunded' ) != 0)
		{
			$logText .= "Incomplete payment transaction!\n";
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->EPay_Log( $logText );
			$this->EPay_Error( $logText, $receiver_email);
			return;
		}
		$refunded = false;
		// if a refund then set the transasction id back to zero
		if (strcmp( $status, 'Refunded' ) == 0) {

			// see https://cms.epay.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_RefundTransaction for more ideas
			$logText .= "Payment refunded!\n";
			$this->EPay_Log( $logText );

			// must ensure the registration is set to cancelled state too!
			$refunded = true;
		}



		// Check #2 - Ensure amount is correct
		if ( $amount != $transaction->amount &&  strcmp( $status, 'Refunded' ) != 0) {
			$logText .= "Amount Error!\n";
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->EPay_Log( $logText );
			$this->EPay_Error( $logText, $receiver_email );
			return;
		}



		if ( !$transaction->bind( $_POST ) )
		{
			$logText .= "Error binding rsvpTransaction instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->EPay_Log( $logText );
			$this->EPay_Error( $logText , $receiver_email);
			return;
		}

		$transaction->logdata = array();
		$transaction->logdata["request"] = JRequest::get();
		$transaction->logdata["epayPostBackResponse"] = $epayPostBackResponse;
		$transaction->logdata = json_encode($transaction->logdata);


		
		if ($refunded){
			$transaction->paymentstate = -1;
		}
		else {
			$transaction->paymentstate = 1;
		}
		


		if ( !$transaction->check() )
		{
			$logText .= "Error checking rsvpTransaction instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->EPay_Log( $logText );
			$this->EPay_Error( $logText , $receiver_email);
			return;
		}
	

		if ( !$transaction->store() )
		{
			$logText .= "Error saving rsvpTransaction instance\n";
			$logText .= $transaction->getError();
			$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";
			$this->EPay_Log( $logText );
			$this->EPay_Error( $logText , $receiver_email);
			return;
		}
		

		$this->notifyEpayPayment($transaction, $attendee, $rsvpdata, true);
		
		/*
		if ($refunded){
			// load the registration
			$registration = new comAERegistration( $db );
			$registration->load( $transaction->registration_id );

			$registration->updateStatus();
		}
		*/
		
		$logText .= "Success\n";
		// for debugging only
		//$logText .= '$_REQUEST: ' . print_r( $_REQUEST, true ) . "\n";

		$this->EPay_Message( $logText , $receiver_email);
	}

	protected function EPay_Message( $text , $email)
	{
		$this->FixEmail($email);
		if ($email == ""){
			return;
		}
		$mail = JFactory::getMailer();
		return $mail->sendMail($email, 'epay log', $email,'EPay IPN Message', $text,0 );

	}

	protected function EPay_Error( $text, $email)
	{
		$this->FixEmail($email);
		if ($email == ""){
			return;
		}
		$mail = JFactory::getMailer();
		return $mail->sendMail($email, 'epay log', $email,'EPay IPN Error', $text,0 );
		
	}

	protected function FixEmail(& $email){
		if ($email==""){
			// get Jevents params and send messages to admin if necessary
			$params = JComponentHelper::getParams("com_jevents");
			$jevadmin = $params->get("jevadmin",-1);
			if ($jevadmin==-1) {
				return;
			}
			$jevadmin = JEVHelper::getUser($jevadmin);
			$email = $jevadmin->email;
		}

	}
	protected function EPay_Log( $text )
	{

		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		$logfile = JPATH_SITE. str_replace("//","/",$this->params->get('EPayLogFileLocation','/administrator/components/com_rsvppro/logs/') . '/epay_log.txt');

		$fp = @fopen($logfile, 'a' );
		if ($fp){
			@fwrite( $fp, $text . "\n\n" );
			@fclose( $fp );
		}
	}


	

	function phpMoneyFormat($amount, $template) {
		if (isset($template->Currency)){
			$digits 	= $template->CurrencyDigits;
			$symbol 	= $template->CurrencySymbol;
			$onLeft 	= (strcmp($template->CurrencyPlacement,'left') == 0);
			$separator 	= $template->CurrencySeparator;
			$decimal	= $template->CurrencyDecimal;
		}
		else {
			$eSessParams = JComponentHelper::getParams('com_rsvppro');

			$digits 	= $eSessParams->get("CurrencyDigits");
			$symbol 	= $eSessParams->get("CurrencySymbol");
			$onLeft 	= (strcmp($eSessParams->get("CurrencyPlacement"),'left') == 0);
			$separator 	= $eSessParams->get("CurrencySeparator");
			$decimal	= $eSessParams->get("CurrencyDecimal");
		}
		$formattedText  = '';

		// negative amount
		if ( $amount < 0 ) $formattedText .= '-';

		// currency symbol on the left
		if ( $onLeft )     $formattedText .= $symbol;

		// format with correct number of digits and separator
		if ( $digits > 0 ) {
			$amount = RsvpHelper::ceil_dec(abs($amount), $digits, ".");			
			$formattedText .= number_format( abs($amount), $digits, $decimal, $separator );
		} else {
			$formattedText = abs( $amount );
		}

		// currency symbols on the right
		if ( !$onLeft )    $formattedText .= $symbol;


		return $formattedText;
	}


	private function notifyEpayPayment($transaction, $attendee, $rsvpdata, $aftertransation=false){
		$templateParams  = RsvpHelper::getTemplateParams($rsvpdata);
		// immediate notification
		
		if (((!$aftertransation && $templateParams->get("notifypplpay", 1)==2) || ($aftertransation && $templateParams->get("notifypplpay", 1)==1))  && $transaction->gateway == "epay")
		{
				
			$comparams = JComponentHelper::getParams("com_rsvppro");
			include_once(JPATH_ADMINISTRATOR . "/components/com_rsvppro/libraries/attendeehelper.php");
			$this->helper = new RsvpAttendeeHelper($comparams);

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

			$user = JEVHelper::getUser($attendee->user_id);
			if ($user->id == 0 && $comparams->get("attendemails", 0))
			{
				$name = $attendee->email_address;
				$username = $attendee->email_address;
			}
			else
			{
				$name = $user->name;
				$username = $user->username;
			}

			$class = "plgRsvppro" . ucfirst($transaction->gateway);
			$pluginpath = version_compare(JVERSION, "1.6.0", 'ge')?JPATH_SITE."/plugins/rsvppro/$transaction->gateway/" : JPATH_SITE."/plugins/rsvppro/";
			JLoader::register($class, $pluginpath . $transaction->gateway . ".php");
			
			$this->helper->notifyUser($rsvpdata, $repeat, $user, $name, $username, $attendee, 'pplpay', false, $transaction);
		}		
	}
	
}
