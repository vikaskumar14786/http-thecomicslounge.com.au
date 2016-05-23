<?php
/**
 *
 * Controller for the front end Orderviews
 *
 * @package	VirtueMart
 * @subpackage User
 * @author Oscar van Eijk
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: orders.php 7821 2014-04-08 11:07:57Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Load the controller framework
jimport('joomla.application.component.controller');

/**
 * VirtueMart Component Controller
 *
 * @package		VirtueMart
 */
class VirtueMartControllerDownloads extends JControllerLegacy
{

	/**
	 * Todo do we need that anylonger? that way.
	 * @see JController::display()
	 */
	public function display($cachable = false, $urlparams = false)  {

		$format = vRequest::getCmd('format','html');
		if  ($format == 'pdf') $viewName= 'pdf';
		else $viewName='downloads';
		VmConfig::loadJLang('com_virtuemart_orders',TRUE);
		VmConfig::loadJLang('com_virtuemart_shoppers',TRUE);
		$view = $this->getView($viewName, $format);

		// Display it all
	  $view->display();
	}
	public function getOrderIdByOrderPass($orderNumber,$orderPass){

		$db = JFactory::getDBO();
		$q = 'SELECT `virtuemart_order_id` FROM `#__virtuemart_orders` WHERE `order_pass`="'.$db->escape($orderPass).'" AND `order_number`="'.$db->escape($orderNumber).'"';
		$db->setQuery($q);
		$orderId = $db->loadResult();
		if(empty($orderId)) vmdebug('getOrderIdByOrderPass no Order found $orderNumber = '.$orderNumber.' $orderPass = '.$orderPass.' $q = '.$q);
		return $orderId;

	}public function getOrder($virtuemart_order_id){

		//sanitize id
		$virtuemart_order_id = (int)$virtuemart_order_id;
		$db = JFactory::getDBO();
		$order = array();

		// Get the order details
		$q = "SELECT  o.*,u.*,
				s.order_status_name
			FROM #__virtuemart_orders o
			LEFT JOIN #__virtuemart_orderstates s
			ON s.order_status_code = o.order_status
			LEFT JOIN #__virtuemart_order_userinfos u
			ON u.virtuemart_order_id = o.virtuemart_order_id
			WHERE o.virtuemart_order_id=".$virtuemart_order_id;
		$db->setQuery($q);
		$order['details'] = $db->loadObjectList('address_type');
		if($order['details']){
			$concat = array();
			if(isset($order['details']['BT']->company))  $concat[]= $order['details']['BT']->company;
			if(isset($order['details']['BT']->first_name))  $concat[]= $order['details']['BT']->first_name;
			if(isset($order['details']['BT']->middle_name))  $concat[]= $order['details']['BT']->middle_name;
			if(isset($order['details']['BT']->last_name))  $concat[]= $order['details']['BT']->last_name;
			$order['details']['BT']->order_name = '';
			foreach($concat as $c){
				$order['details']['BT']->order_name .= $c;
			}
			$order['details']['BT']->order_name = htmlspecialchars(strip_tags(htmlspecialchars_decode($order['details']['BT']->order_name)));
		}

		// Get the order history
		$q = "SELECT *
			FROM #__virtuemart_order_histories
			WHERE virtuemart_order_id=".$virtuemart_order_id."
			ORDER BY virtuemart_order_history_id ASC";
		$db->setQuery($q);
		$order['history'] = $db->loadObjectList();

		// Get the order items
$q = 'SELECT virtuemart_order_item_id, product_quantity, order_item_name,
    order_item_sku, i.virtuemart_product_id, product_item_price,
    product_final_price, product_basePriceWithTax, product_discountedPriceWithoutTax, product_priceWithoutTax, product_subtotal_with_tax, product_subtotal_discount, product_tax, product_attribute, order_status, p.product_available_date, p.product_availability,
    intnotes, virtuemart_category_id, p.product_mpn
   FROM (#__virtuemart_order_items i
   		LEFT JOIN #__virtuemart_products p
   		ON p.virtuemart_product_id = i.virtuemart_product_id)
   LEFT JOIN #__virtuemart_product_categories c
   ON p.virtuemart_product_id = c.virtuemart_product_id
   WHERE `virtuemart_order_id`="'.$virtuemart_order_id.'" group by `virtuemart_order_item_id`';
//group by `virtuemart_order_id`'; Why ever we added this, it makes trouble, only one order item is shown then.
// without group by we get the product 3 times, when it is in 3 categories and similar, so we need a group by
//lets try group by `virtuemart_order_item_id`
		$db->setQuery($q);
		$order['items'] = $db->loadObjectList();

		$customfieldModel = VmModel::getModel('customfields');
		$pModel = VmModel::getModel('product');
		foreach($order['items'] as &$item){
			$item->customfields = array();
			$ids = array();
			$product = $pModel->getProduct($item->virtuemart_product_id);
			if(!empty($item->product_attribute)){
				//Format now {"9":7,"20":{"126":{"comment":"test1"},"127":{"comment":"t2"},"128":{"comment":"topic 3"},"129":{"comment":"4 44 4 4 44 "}}}
				//$old = '{"46":" <span class=\"costumTitle\">Cap Size<\/span><span class=\"costumValue\" >S<\/span>","109":{"textinput":{"comment":"test"}}}';
				//$myCustomsOld = @json_decode($old,true);

				$myCustoms = @json_decode($item->product_attribute,true);
				$myCustoms = (array) $myCustoms;

				foreach($myCustoms as $custom){
					if(!is_array($custom)){
						$custom = array( $custom =>false);
					}
					foreach($custom as $id=>$field){
						$item->customfields[] = $customfieldModel-> getCustomEmbeddedProductCustomField($id);
						$ids[] = $id;
					}
				}
			}

			if(!empty($product->customfields)){
				foreach($product->customfields as $customfield){
					if(!in_array($customfield->virtuemart_customfield_id,$ids) and $customfield->field_type=='E' and ($customfield->is_input or $customfield->is_cart_attribute)){
						$item->customfields[] = $customfield;
					}
				}
			}
		}

// Get the order items
		$q = "SELECT  *
			FROM #__virtuemart_order_calc_rules AS z
			WHERE  virtuemart_order_id=".$virtuemart_order_id;
		$db->setQuery($q);
		$order['calc_rules'] = $db->loadObjectList();
		return $order;
	}
	public function getcountry( $countryId)
	{
			$db = JFactory::getDBO();
		$q = "SELECT  country_name
			FROM #__virtuemart_countries
			WHERE  virtuemart_country_id=".$countryId;
		$db->setQuery($q);
		$order = $db->loadObjectList();
		return $order[0]->country_name;
		
	}
	
	
		public function getstarttime( $virtuemart_product_id)
	{
			$db = JFactory::getDBO();
		$q = "SELECT `customfield_value`
FROM `jos_virtuemart_product_customfields`
WHERE `virtuemart_custom_id` =80
AND `virtuemart_product_id` =$virtuemart_product_id";
		$db->setQuery($q);
		$order = $db->loadObjectList();
		return $order[0]->customfield_value;
		
	}
		public function getendtime( $virtuemart_product_id)
	{
			$db = JFactory::getDBO();
		$q = "SELECT `customfield_value`
FROM `jos_virtuemart_product_customfields`
WHERE `virtuemart_custom_id` =79
AND `virtuemart_product_id` =$virtuemart_product_id";
		$db->setQuery($q);
		$order = $db->loadObjectList();
		return $order[0]->customfield_value;
		
	}
	
	
	public function getstatename ( $stateid )
	{
		 	$db = JFactory::getDBO();
		$q = "SELECT `state_name` FROM `#__virtuemart_states` WHERE `virtuemart_state_id`=".$stateid;
		$db->setQuery($q);
		$order = $db->loadObjectList();
		return $order[0]->state_name;
		
	}
		public function getstatusname ( $statusid )
	{
		 	$db = JFactory::getDBO();
		$q = "SELECT order_status_code FROM `#__vm_order_status`
WHERE order_status_id=".$statusid;
		$db->setQuery($q);
		$order_status_code = $db->loadObjectList();
		return $order_status_code[0]->order_status_code;
		
	}

	public function download()
	{
		
		$orderNumber = $_REQUEST['order_numbe'];
		$orderPass  = $_REQUEST['order_pass'];
		
	$orderId = $this->getOrderIdByOrderPass($orderNumber,$orderPass);
	
	require_once(JPATH_ROOT.'/components/com_virtuemart/views/downloads/tmpl/examples/config/tcpdf_config_alt.php');
	
	require_once(JPATH_ROOT.'/components/com_virtuemart/views/downloads/tmpl/tcpdf.php');
	
	
	
	 if(empty($orderId)){
                    echo vmText::_('COM_VIRTUEMART_RESTRICTED_ACCESS');
					vmdebug('getMyOrderDetails COM_VIRTUEMART_RESTRICTED_ACCESS',$orderNumber, $orderPass, $tries);
					$tries++;
					
                    return false;
                }
    $orderDetails = $this->getOrder($orderId);
	$details =      $orderDetails['details']['BT'];
	$items   =      $orderDetails['items'];
	$Ordername  = $details->order_number;
	$first_name = $details->first_name;
	$last_name  = $details->last_name;
	$address1   = $details->address_1;
	$address2   = $details->address_2;
	$city       = $details->city;
	$email      = $details->email; 
	$orderdate  = date('d-m-Y',strtotime($details->created_on));
	$virtuemart_state_id   = $details->virtuemart_state_id;
	$virtuemart_country_id = $details->virtuemart_country_id;
	$country_name   =  $this->getcountry( $virtuemart_country_id );
	$state_name     =  $this->getstatename( $virtuemart_state_id );
	
	
	// add a page
	$zip       = $details->zip; 
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	$pdf->SetAuthor('Comic`s');
	$pdf->SetTitle('http://thecomicslounge.com.au/');
	$pdf->SetSubject('Tickets');
	$pdf->SetKeywords('TCPDF, PDF, Event , Event tickets , guide');
	$pdf->SetHeaderData('', '', ''.' Event Ticket','http://www.thecomicslounge.com.au/');
	$pdf->SetPrintHeader(false);
	// set header and footer fonts
	$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
	$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
	$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
	// set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	$pdf->SetLineStyle( array( 'width' => 15, 'color' => array(0,0,0)));
	// set image scale factor
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
	require_once(dirname(__FILE__).'/lang/eng.php');
	$pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 10);



foreach($items as $item ){



$product_quantity = $item->product_quantity;
for($i=1;$i<=$product_quantity;$i++){	
$customfields = $item->customfields;
$attributes  = json_decode ($item->product_attribute, true);
$filelds ='';

foreach($customfields as $customfield)
{

if($customfield->customfield_value =='showonlyprice')
{
$eventtype ="Show Only";
}
else if($customfield->customfield_value =='showwithmealprice')
{
$eventtype ="Show With Meal";
}

if($customfield->virtuemart_custom_id==84)
	{
	}
   else
   {
	 $filelds = $filelds."(".$customfield->custom_value.')'."";   
   }	   
}

if($filelds=='')
{
	$filelds ='Show Only';
	
}




if($item->order_status == 'P')
{
	$status = 'Pending';
}
else if($item->order_status == 'U')
{
	$status = 'Confirmed by Shopper';
}
else if($item->order_status == 'C')
{
	$status = 'Confirmed';
}
else if($item->order_status == 'X')
{
	$status = 'Cancelled';
}
else if($item->order_status == 'R')
{
	$status = 'Refunded';
}
else if($item->order_status == 'U')
{
	$status = 'Shipped';
}	


foreach($attributes[84]  as $startdate)
{
	 $eventdate = date('j F Y',strtotime($startdate));
	
}


$prduct_name = $item->order_item_name;
$product_price = $item->product_item_price;

$start_time = $this->getstarttime($item->virtuemart_product_id);
$end_time   = $this->getendtime($item->virtuemart_product_id);

$pdf->AddPage();
// draw some reference lines
$image =JPATH_ROOT.'/components/com_virtuemart/views/downloads/tmpl/examples/images/venueHere.png';
$pdf->Image($image, 0, 5, 0, '', '', 'http://thecomicslounge.com.au/', '', true, 100);
$linestyle = array('width' => 0.0, 'cap' => 'butt', 'join' => 'miter', 'dash' => '', 'phase' => 0, 'color' => array(255, 255, 255));
$pdf->Line(0, 40, 500, 40, $linestyle);
$pdf->Ln(12);
$pdf->SetFontSize(10);
$pdf->SetFillColor(255,255,255);
//Event Date: $eventdate
$html = "PLEASE MAKE SURE YOU PRINT OUT ALL OF THE FOLLOWING PAGES
YOU MUST BRING ALL TICKETS FOR YOUR BOOKING TO THE VENUE SO THEY CAN BE SCANNED FOR EACH PERSON TO
ENTER THE EVENT.
Please do not forward this email. Each barcode is unique and can only be scanned once.
Comics Lounge Pty Ltd
YOU HAVE PURCHASED TICKES FOR THE FOLLOWING EVENT:

Event Name: $prduct_name $filelds
BILL TO: $first_name  $last_name
$email
$address1
$address2  $city $zip  $state_name $country_name  
PAYMENT: $status
Date: $orderdate 
Order Number:$orderNumber
Order Amount:".number_format($product_price, 2, '.', '') ."AUD
Ticket Type: $eventtype  $filelds This order was placed subject to Terms and Conditions.";

$pdf->SetTextColor(0,0,0);
 
//$pdf->writeHTML($html, true, 0, true, 0);
$pdf->MultiCell(180, 60, $html, 1, 'L', 1, 1, '' ,'', false);
$pdf->Ln(5);
//Date: $eventdate
$txt = "
THE COMIC`S LOUNGE, 26 ERROL STREET,NORTH MELBOURNE, AUS
www.thecomicslounge.com.au
Ticket price ".number_format($product_price, 2, '.', '') ."AUD + B/F | Show starts at $start_time
Ticket Type: $eventtype  $filelds | Order Number: $orderNumber.\n";
$pdf->SetFillColor(236,0,140);
$pdf->SetTextColor(255,255,255);
$pdf->SetFontSize(12);
$pdf->MultiCell(180, 50, $txt, 1, 'C', 1, 0, '', '', true);
$pdf->Ln(35);
$id = md5($item->virtuemart_product_id.$orderNumber.$i);
$pdf->write1DBarcode($id, 'C39E+', '', '', '', 18, 0.4, array('position'=>'S', 'border'=>false, 'padding'=>4, 'fgcolor'=>array(255,255,255), 'bgcolor'=>array(236,0,140), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N');



$pdf->Ln();
}
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -




// reset pointer to the last page
$pdf->lastPage();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_049.pdf', 'I');
    

die();  
		
	}
	
public function  checkvouchervalidity()
{
	$product_id  = $_REQUEST['pid'];
	$vouchername = trim($_REQUEST['vouchername']);  
		$db = JFactory::getDBO();
		$q = "SELECT coupons.coupon_code
FROM jos_awocoupon_vm AS coupons
LEFT JOIN jos_awocoupon_vm_product AS products ON coupons.id = products.coupon_id
WHERE products.product_id =$product_id
AND coupons.coupon_code = '$vouchername'";
		$db->setQuery($q);
		$order = $db->loadObjectList();
		if(count($order)>0)
		{
			echo "1";
		}
        else
        {
			echo "0";
		}			
			die();
		//return $order[0]->coupon_code;
	
} 

}

// No closing tag
