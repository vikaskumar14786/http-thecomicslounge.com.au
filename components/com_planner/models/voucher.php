<?php
/**
 * Joomla! 1.5 component video
 *
 * @version $Id: video.php 2011-12-10 03:24:58 svn $
 * @author vivek
 * @package Joomla
 * @subpackage video
 * @license GNU/GPL
 *
 * display feature video 
 *
 * This component file was created using the Joomla Component Creator by Not Web Design
 * http://www.notwebdesign.com/joomla_component_creator/
 *
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

/**
 * video Component video Model
 *
 * @author      notwebdesign
 * @package		Joomla
 * @subpackage	video
 * @since 1.5
 */
class PlannerModelVoucher extends JModel {
    /**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}
	/* Function add manual booking in Vm tables for order */
	function savebookingbyvoucher($ticketnumber,$bookingname,$email,$mobilenumber,$noofticket,$eventid,$totalticketamount,$showonly,$meals,$noofseat,$voucher_code,$voucher_type,$object_id){
		
	if($voucher_code){
		$voucher_code = implode(',',$voucher_code);
		$voucher_type = implode(',',$voucher_type);
			
	}

	
		$db =& JFactory::getDBO();
		// Get the IP Address
		
			$boookingday = date('Y-m-d')."_"."00";
			
		
	
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];}
		else {
			$ip = 'unknown';}
				
		
			// Insert the main order information
				$timestamp = time();
				$query = "SELECT MIN(user_id)-1 as uid FROM #__vm_user_info";
				$db->setQuery($query);
				$user_id = $db->LoadResult();
			
				$ps_vendor_id = 1;
				$order_total = $totalticketamount; // Total amount of show
				
				$str = session_id();
				$str .= (string)time();
				$order_number = $auth['user_id'] .'_'. md5($str);
				$order_number =  substr($order_number, 0, 32);
				$empty ="''";
				if(!$voucher_code){
				$voucher_code = $empty;
				}
				if(!$totalticketamount){
					
				$totalticketamount = $empty;
					
				}
			
			$sql =  " INSERT INTO #__vm_orders SET ".
					" user_id = '$user_id' ,".
					" vendor_id = '$ps_vendor_id' ,".
					" order_total = '$order_total' ,".
					" order_number  = '$order_number' ,". 
					" user_info_id =  $empty, ". 
					" ship_method_id = $empty,".
					" order_subtotal = $totalticketamount, ".
					" order_tax = $empty,".
					" order_tax_details = $empty, ".
					" order_shipping = $empty, ".
					" order_shipping_tax = $empty, ".
					" order_discount = $empty, ".
					" coupon_discount = $empty, ".
					" coupon_code = '$voucher_code', ".
					" order_currency = 'USD',".
					" order_status = 'P',".
					" cdate = $timestamp,".
					" mdate = $timestamp,".
					" customer_note = 'Booking by planner',".
					" ip_address = '$ip'";	
					
						$db->setQuery($sql);
						if (!$db->query()) {
						echo "<script> alert('".$db->getErrorMsg(true)
						."'); window.history.go(-1); </script>\n";
						}
					$order_id = $db->insertid();
			   // Insert the initial Order History.	    
					$mysqlDatetime = date("Y-m-d G:i:s", $timestamp);
					
					
				
					
					
				$sql =  " INSERT INTO #__vm_order_history SET ".
					" order_id = '$order_id',".
					" order_status_code = 'P',".
					" date_added = '$mysqlDatetime',".
					" customer_notified = '1',".
					" comments = 'Booking added by Planner '";
					
						$db->setQuery($sql);
						if (!$db->query()) {
						echo "<script> alert('".$db->getErrorMsg(true)
						."'); window.history.go(-1); </script>\n";
						}
						
		
				/**
				* Insert the Order payment info 
				*/
				// Payment number is encrypted is removed as not require using mySQL encryption functions.
				$payment_method_id = 2; //cash on delivery
				$order_payment_trans_id = "cltranstionid123456";
				$sql =  " INSERT INTO #__vm_order_payment SET ".
					" order_id = '$order_id',". 
					" payment_method_id = '$payment_method_id',". 
					" order_payment_log = $empty,". 
					" order_payment_trans_id = '$order_payment_trans_id,'";
				  
						$db->setQuery($sql);
						if (!$db->query()) {
						echo "<script> alert('".$db->getErrorMsg(true)
						."'); window.history.go(-1); </script>\n";
						}
						
			/**
			* Insert the User Billto & Shipto Info
			*/
			// First: get all the fields from the user field list to copy them from user_info into the order_user_info	
			// Save current Bill To Address
						
						
						$address_type = "BT";
						$bank_account_type = "Checking";
			
						$sql = " INSERT INTO `#__vm_order_user_info` SET ".
						     " user_id =  '$user_id' ,".
						     " address_type = '$address_type' ,".
						     " order_id = '$order_id' ,".
						     " first_name = '$bookingname',".
						     " phone_1 = '$mobilenumber' ,".
						     " user_email = '$email' ,".
						     " bank_account_type = '$bank_account_type'";
						     
						$db->setQuery($sql);
						if (!$db->query()) {
						echo "<script> alert('".$db->getErrorMsg(true)
						."'); window.history.go(-1); </script>\n";
						}
						$order_item_sku = "P_".time();
						$product_quantity = $noofticket; // QTY of product 
						
						$order_status = 'P';
						$order_item_currency = 'AUD';
						$product_final_price = $totalticketamount;
						$product_item_price = $totalticketamount;
						$product_quantity = $noofticket;
						
					//Multiple entry
					
				if($showonly > 0){
					
				$product_attribute = 'seats: <br/> Booking day: '.$boookingday.': 00<br/>'.
						       ' Ticket Type: Show Only<br/> Ticket Type 2: <br/> object: '.$object_id.'<br/>';
						       
						       
				$sql = " INSERT INTO `jos_vm_order_item`  SET ".
						     " order_id =  '$order_id' ,".
						     " user_info_id = '$address_type' ,".
						     " vendor_id = '$ps_vendor_id' ,".
						     " product_id = '$eventid' ,".
						     " order_item_sku = '$order_item_sku' ,".
						     " order_item_name = 'DOWNLOADABLE' ,".
						     " product_quantity = '$showonly',".
						     " product_item_price = '$product_item_price' ,".
						     " product_final_price = '$product_final_price' ,".
						     " order_item_currency = '$order_item_currency',".
						     " order_status = '$order_status',".
						     " cdate ='$timestamp',".
						     " mdate = '$timestamp',".
						     " product_attribute = '$product_attribute'";
						     
						$db->setQuery($sql);
						if (!$db->query()) {
						echo "<script> alert('".$db->getErrorMsg(true)
						."'); window.history.go(-1); </script>\n";
						}
						$orderitemId  = $db->insertid(); // get Last inserterd id that will  store in ticket table	
						//Insert into ticket table 
						for($i=1; $i<= $showonly; $i++ ){
							
								$ticketean  =  $timestamp+$i+$orderitemId;
								
								$sql = " INSERT INTO `jos_vmeticket_ticket`  SET ".
									   " ticket_ean =  '$ticketean' ,".
								       " order_item_id =  '$orderitemId' ,".
									   " ticket_start_validity =  '$timestamp' ,".
									   " ticket_end_validity = '$timestamp',".
									   " ticket_admission = 1 ";
								$db->setQuery($sql);
								if (!$db->query()) {
								echo "<script> alert('".$db->getErrorMsg(true)
								."'); window.history.go(-1); </script>\n";
								}
						}			
						
						
				}
				if($meals >0 )
				{
						$product_attribute =   'seats: <br/> Booking day:'.$boookingday.': 00<br/>'.
						       ' Ticket Type: <br/> Ticket Type 2: Show with Meal<br/> object: '.$object_id.'<br/>';		       
								$sql = " INSERT INTO `jos_vm_order_item`  SET ".
									 " order_id =  '$order_id' ,".
									 " user_info_id = '$address_type' ,".
									 " vendor_id = '$ps_vendor_id' ,".
									 " product_id = '$eventid' ,".
									 " order_item_sku = '$order_item_sku' ,".
									 " order_item_name = 'DOWNLOADABLE' ,".
									 " product_quantity = '$meals',".
									 " product_item_price = '$product_item_price' ,".
									 " product_final_price = '$product_final_price' ,".
									 " order_item_currency = '$order_item_currency',".
									 " order_status = '$order_status',".
									 " cdate ='$timestamp',".
									 " mdate = '$timestamp',".
									 " product_attribute = '$product_attribute'";
									 
								$db->setQuery($sql);
										if (!$db->query()) {
								echo "<script> alert('".$db->getErrorMsg(true)
								."'); window.history.go(-1); </script>\n";
								}
							//INsert in to VM ticket table also so that validate later
							$orderitemId  = $db->insertid(); // get Last inserterd id that will  store in ticket table
	
							//INsert in to VM ticket table also so that validate later
					
								for($i=1; $i<= $meals; $i++ ){
									
									
								$ticketean  =  $timestamp+$i;

									
								$sql = " INSERT INTO `jos_vmeticket_ticket`  SET ".
									   " ticket_ean =  '$ticketean' ,".
								       " order_item_id =  '$orderitemId' ,".
									   " ticket_start_validity =  '$timestamp' ,".
									   " ticket_end_validity = '$timestamp' ,".
									   " ticket_admission = 1 ";
								$db->setQuery($sql);
									if (!$db->query()) {
									echo "<script> alert('".$db->getErrorMsg(true)
									."'); window.history.go(-1); </script>\n";
									}
								}
					
		
				
						
						
					
				}
				
				// Change the vouser staus chane to rediemed
				
				$voucher_code  = explode(',',$voucher_code);
				foreach($voucher_code as $update){
					$sql = " UPDATE   `#__voucher_with_code`  SET ".
						     " published =  1 ".
						     " WHERE  voucher_code = '".($update) ."'";
							 
								     
						$db->setQuery($sql);
						if (!$db->query()) {
						echo "<script> alert('".$db->getErrorMsg(true)
						."'); window.history.go(-1); </script>\n";
						}
				}
				
				$session = JFactory::getSession(); //Unset session after insertion of record
				$session->clear('noofseat');
				$session->clear('voucher_type');
				$session->clear('voucher_code');
				$session->clear('totalshowonly');
				$session->clear('totalmeals');
				
					return $order_id;		
		
	}
   
    
}
?>
