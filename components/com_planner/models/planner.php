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
class PlannerModelPlanner extends JModelList {
    /**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();

	}
	/*Function for update voucher status
	 ***/
	function updateVoucherCode($voucherInImploade){

		$db =& JFactory::getDBO();
	 $updatequery = "UPDATE `jos_voucher_with_code` SET ".
						" published = 1 ".
			" WHERE voucher_code IN ('$voucherInImploade') ";
			$db->setQuery($updatequery);
			$db->query();
	return true;

	}


	/***
	 *Function return current date
	 */
	function getCurrentDate(){

		$timeStamp = time();
		//echo "Increased By28800 ".$timeStamp = time()+28800;
		//$timeStamp =strtotime('+1 day', $timeStamp);
		$date = date('Y-m-d',$timeStamp);

		return $date;

	}
	/**
	 Function for set event status
	*/
	function eventDisplayStatus($available){
		if($available <= 0){
		return "Sold Out";
		}else if($available <  25){
		return "Limited Seats Available";
		}else{
		return "Selling Fast";
		}



	}
	/****
	 get Total Sols out Status used in voucher redeem page and user side basket
	 ****/
	function getTotalSoldoutAndEventStatus($event_id,$event_date){
		$db =& JFactory::getDBO();
		
		$parent_id = $this->getParentProductId($event_id);
		if($parent_id!=0){
			$event_id = $parent_id;
		}

		$hall_id = $this->getEventHallId($event_id); // get Hall id from define in model
		$totalcapacity  = $this->getHallCapacity($hall_id); // get total capcity of hall
		$totalmeals = $this->gettotalmeals($event_id);

		$query = "SELECT pp.product_price, p.seling_status FROM jos_vm_product_price AS pp LEFT JOIN jos_vm_product AS p ON pp.product_id=p.product_id WHERE pp.product_id = $event_id";
		$db->setQuery($query);
		$rec = $db->loadObject();
		$ticketBasePrice = $rec->product_price;
		$seling_status = $rec->seling_status;

		if($seling_status != 'Sold Out'){

		/*****************************/
			$event_show_price = 0;
			$event_meal_price = 0;
			$event_attributes = $this->getEventAttribute($event_id);
			$advanced_attribute_list = explode (";", $event_attributes);

										//for base meal price
										$baseindex = strpos($advanced_attribute_list[3], '[')." ";
										$additionalCost = '';
											if ($baseindex != ' ') {
												$additionalCost = substr ($advanced_attribute_list[3], $baseindex+1, -1);
											}

										$mealTicketBasePrice = 0; //event base show with meal price


										$dates_with_price = explode (",", $advanced_attribute_list[1]);
										foreach($dates_with_price as $val){
											if($val=='Booking day')continue;
											$index = strpos($val, '[')." ";
											$mealindex = strpos($val, '|')." ";
											$addshowprice = '';
											$addmealprice = '';
											$showPrice = $ticketBasePrice;
											$showWithMealPrice = $ticketBasePrice;
											if ($index != ' ' && $mealindex != ' ') {
												$addshowprice = substr ($val, $index+2, $mealindex-$index-2);
												if($addshowprice<9){
													$addshowprice = substr ($addshowprice, 0, 1);
													$addmealprice = substr ($val, $mealindex+2, -1);
												}else{
													$addmealprice = substr ($val, $mealindex+2, -1);
												}
												$operand_show = substr ($val, $index+1, 1);
												$operand_meal = substr ($val, $mealindex+1, 1);
												$val = substr ($val, 0, (int)$index);
											}

											$date = strtotime($val);
											$booking_day = strtotime($event_date);

											if (true == is_numeric($addshowprice) || true == is_numeric($addmealprice) ) {
												// Now add or sub the modifier on
												if ($operand_show=="+" ) {
														$showPrice += $addshowprice;
												}else if ($operand_show=="-" ) {
														$showPrice -= $addshowprice;
												}
												else if ($operand_show=="=" ){
														$showPrice = $addshowprice;
												}

												if ($operand_meal=="+") {
														$showWithMealPrice += $addmealprice;
												}else if ($operand_meal=="-" ) {
														$showWithMealPrice -= $addmealprice;
												}
												else if ($operand_meal=="=" ){
														$showWithMealPrice = $addmealprice;
												}


											}
											if($date == $booking_day){
												$event_show_price = $showPrice;
												$event_meal_price = $showWithMealPrice;
											}

										}


		/*******************/
		if($totalcapacity >= $totalmeals){
			$totalshowonly = $totalcapacity - $totalmeals;
		}
		else{
			$totalmeals = $totalcapacity;
			$totalshowonly = 0;
		}

		
		$totalsoldshowonly = $this->gettotalsoldshowonly($event_id,$event_date);
		$totalsoldmeals = $this->gettotalsoldmeals($event_id,$event_date);
		
		if($event_show_price <= 0){		//check if show ticket price is 0 then it should not available for sell
				$totalmeals += $totalshowonly;
				$totalshowonly = 0;
				$totalsoldshowonly = 0;
		}

		if($event_meal_price <= 0){		//check if show+meal ticket price is 0 then it should not available for sell
				$totalshowonly += $totalmeals;
				$totalmeals = 0;
				$totalsoldmeals = 0;
		}

		if($totalshowonly>0){
			$availableshowonly = $totalshowonly - $totalsoldshowonly;
		}
		else{
			$availableshowonly = 0;
			$totalsoldshowonly = 0;
		}

		$availablemeals    = $totalmeals - $totalsoldmeals;

		$totalsoldout = $totalsoldshowonly + $totalsoldmeals;


		//Calcualte total availble seats

		$totalavailableseat = $totalcapacity - $totalsoldout;

			$event_ids = array(175, 178, 184, 187, 190, 193, 196, 199, 202, 205);
			$event_dates = array('2012-12-07', '2012-12-21', '2012-12-22');
				if(in_array($event_id, $event_ids) && in_array($event_date, $event_dates)){
					$totalavailableseat = 0;
			}
		}
		else{
			
			$availableshowonly = 0;
			$availablemeals = 0;
			$totalavailableseat = 0;
			$totalcapacity = 100;
		}

		$availability['date']=$event_date;
		$availability['showonly']=$availableshowonly;
		$availability['showwithmeal']=$availablemeals;
		$availability['total']=$totalavailableseat;
		$availability['totalcapacity']=$totalcapacity;

		return $availability;

	}

	/*In controller line number 56 */
	function getNumberofRowsforReallocation($order_id){
	$now = $this->getCurrentDate();
		$db = &JFactory::getDBO();
		$sql = " SELECT DISTINCT oi.order_item_id FROM jos_vm_order_item as oi LEFT JOIN ".
						" jos_vmeticket_ticket as tkt ON (oi.order_item_id = tkt.order_item_id)".
						" WHERE DATE(FROM_UNIXTIME(tkt.ticket_start_validity)) = DATE('$now') ".
						" AND oi.order_id = ".$order_id;
		$db->setQuery($sql);
		$db->query();
		$num_rows = $db->getNumRows();
		return $num_rows;

	}

	/***
	 Function for Reaalllocation Search  ##  2 March  in controller line number 56
	*/
	function getSearchResultforReallocation($order_id){

	$now = $this->getCurrentDate();
		$db = &JFactory::getDBO();
        $timestamp = strtotime(Date('Y-m-d',time()));
		$sql = " SELECT DISTINCT oi.order_item_id FROM jos_vm_order_item as oi LEFT JOIN ".
						" jos_vmeticket_ticket as tkt ON (oi.order_item_id = tkt.order_item_id)".
						" WHERE tkt.ticket_start_validity = '$timestamp' ".
						" AND oi.order_id = $order_id";
        $db->setQuery($sql);
		$db->query();
		$num_rows = $db->getNumRows();
		return $num_rows;

	$result = $db->loadObjectList();
	return $result;

	}


	/**
	function geting Event Status Summary and total sold out status
	*/
	function getTotalSoldout($event_id,$event_date){

		$db =& JFactory::getDBO();
		$product_parent_id = $this->getDownloadableProductId($event_id);
		/*$query =" SELECT count(*) FROM jos_vmeticket_ticket WHERE  ".
				" DATE(FROM_UNIXTIME(ticket_start_validity)) = '$event_date'".
				" AND  order_item_id IN(SELECT order_item_id FROM jos_vm_order_item WHERE product_id = $event_id OR  product_id =$product_parent_id) ";*/
		$query = "SELECT SUM(product_quantity) FROM jos_vm_order_item WHERE (product_id = $event_id OR product_id=$product_parent_id) AND order_status='c' AND product_attribute like '%$event_date%'";
		$db->setQuery($query);
		$totalsold = $db->loadResult();
		return $totalsold;


	}
	/*
	 Delete temp voucher table
	/*/
	function deleteVoucherTable($user_id){
		  $db =& JFactory::getDBO();
		  $query  = "DELETE  FROM #__temp_vouchers WHERE user_id = ".$user_id;
		  $db->setQuery($query);
		  $db->query();


	}
	/*/*
	*Function ccheck for event is free or not
	 **/
	function getIsFreeEvent($product_id){


		$db =& JFactory::getDBO();
		$result = "";
		if($product_id){

			$query =" SELECT is_free FROM jos_vm_product  WHERE product_parent_id = 0 AND  product_id =".$product_id;
			$db->setQuery($query);
			$result = $db->loadResult();
		}
		return $result;
	}

	/*
	 Function for delete  booking tickets from tickets table
	 **/

	function deacreaseBookingFromTicketTable($ticketean){

		$db =& JFactory::getDBO();
		 $query = "DELETE FROM `jos_vmeticket_ticket` ".
				 "WHERE `ticket_ean` IN ('$ticketean') ";

				$db->setQuery($query);
				if (!$db->query()) {
				echo "<script> alert('".$db->getErrorMsg(true)
				."'); window.history.go(-1); </script>\n";
				}


	}
	/*
	Get user detail from order id
	*/

	function getUserdetail($order_id){
		$db =& JFactory::getDBO();
		$query = " SELECT first_name,phone_1,user_email  FROM jos_vm_order_user_info where ".
				  " order_id = ".$order_id;
					$db->setQuery($query);
				if (!$db->query()) {
				echo "<script> alert('".$db->getErrorMsg(true)
				."'); window.history.go(-1); </script>\n";
				}
			$result = $db->loadobject();
			return $result;

	}


	// Function for deacrease booking

	function deacreasebooking($showonly,$meals){
		$db =& JFactory::getDBO();
			if($showonly){ // Update Show only Row
			$total =  explode('_',$showonly);
			$order_item_id = $total[0];
			$showonly = $total[1];
			$sql = "select product_attribute from jos_vm_order_item where order_item_id=$order_item_id";
			$db->setQuery($sql);
			$product_attribute = $db->loadRow();
			list($product_attribute, $EAN) = explode('<br/> EAN:',$product_attribute['0']);

			$query ="UPDATE jos_vm_order_item  SET ".
				" `product_quantity` = product_quantity - ".$showonly.
				", `product_attribute` = '".$product_attribute."'  WHERE `order_item_id` = ".$order_item_id;
					$db->setQuery($query);
					if (!$db->query()) {
					echo "<script> alert('".$db->getErrorMsg(true)
						."'); window.history.go(-1); </script>\n";
				}
		}
		if($meals){
				// Update Meals only Row
				$total =  explode('_',$meals);
				$order_item_id = $total[0];
				$meals = $total[1];
				$sql = "select product_attribute from jos_vm_order_item where order_item_id=$order_item_id";
				$db->setQuery($sql);
				$product_attribute = $db->loadRow();
				list($product_attribute, $EAN) = explode('<br/> EAN:',$product_attribute['0']);

			 	$query =" UPDATE jos_vm_order_item  SET ".
				" `product_quantity` = product_quantity - ".$meals.
				", `product_attribute` = '".$product_attribute."' WHERE `order_item_id` = ".$order_item_id;
					$db->setQuery($query);
					if (!$db->query()) {
					echo "<script> alert('".$db->getErrorMsg(true)
						."'); window.history.go(-1); </script>\n";
				}
		}
	return true;
	}





	/*Function for return total booking from order id  */

	function totalbookingtype($order_id){

		$db =& JFactory::getDBO();
		$query = " SELECT product_attribute,product_quantity,order_item_id  FROM jos_vm_order_item where ".
				 " order_id = ".$order_id;
		$db->setQuery($query);
		$result = $db->loadobjectlist();
		foreach($result as $r)
		{
				$attribute = explode('<br/>',$r->product_attribute);
				if(trim($attribute[2]) == trim(' Ticket Type: Show Only') OR trim($attribute[2]) == trim(' Ticket Type: Show_Only')){
					$realattribute['show_only']	= $r->product_quantity;
					$realattribute['order_item_id_show_only']	= $r->order_item_id;

				}else {
				    $realattribute['show_meals'] = $r->product_quantity;
				 	$realattribute['order_item_id_show_meals']	= $r->order_item_id;

				}

		}
		return $realattribute;
	}


	/*  Function return total Show
	** Only ticket for a particular event
	**
	** */

	function gettotalsoldshowonly($eventid,$date){

		//$sold_product_id   = $this->getDownloadableProductId($eventid);

		$db =& JFactory::getDBO();
		$query = "SELECT sum(product_quantity) FROM jos_vm_order_item where (product_attribute  like '%$date%Ticket Type: Show_Only%' OR product_attribute  like '%$date%Ticket Type: Show Only%') AND order_status='c' AND product_id=$eventid";
				$db->setQuery($query);
				if (!$db->query()) {
				echo "<script> alert('".$db->getErrorMsg(true)
				."'); window.history.go(-1); </script>\n";
				}
		$result = $db->loadResult();
		return $result;

	}

	/*  Function return total Show + Meals Only
	** Only ticket for a particular event
	*****/
	function gettotalsoldmeals($eventid,$date){

		$db =& JFactory::getDBO();

		//$sold_product_id   = $this->getDownloadableProductId($eventid);

		$query = " SELECT sum(product_quantity) FROM jos_vm_order_item where ".
			 " (product_attribute  like '%$date%Ticket Type 2: Show_with_Meal%' OR product_attribute  like '%$date%Ticket Type 2: Show with Meal%') AND order_status='c' AND product_id = ".$eventid ;
			 	$db->setQuery($query);
				if (!$db->query()) {
				echo "<script> alert('".$db->getErrorMsg(true)
				."'); window.history.go(-1); </script>\n";
				}
		$result = $db->loadResult();
		return $result;

	}
	function gettotalmeals($product_id){
		$db =& JFactory::getDBO();
		if($product_id){
				$query = "SELECT meals FROM jos_vm_product where ".
				 " product_id = ".$product_id ;
			 		$db->setQuery($query);
					if (!$db->query()) {
					echo "<script> alert('".$db->getErrorMsg(true)
					."'); window.history.go(-1); </script>\n";
					}
				$result = $db->loadResult();
		}
		return $result;

	}
/*Function increase non purchec type booking at the time of increase booking **/

function IncreaseBookingByOrderId($order_id,$product_id,$hall_id,$increaseQty,$increaseType,$product_item_price,$product_final_price,$event_date){

			$db =& JFactory::getDBO();
			$parent_id = $this->getDownloadableProductId($product_id);
			$ps_vendor_id = 1;
			$product_quantity = $increaseQty;
			$ticketStartvalidity = $event_date;


			$event_date =date('Y-m-d',$event_date);
			$event_date = $event_date."_00: 00";
			$order_status = 'C';
			$cdate	= time();
			$mdate = time();
				if($increaseType == 1){
					$product_attribute =   'seats: <br/> Booking day: '.$event_date.'<br/>'.
			       ' Ticket Type: Show_Only<br/> Ticket Type 2: <br/> object: '.$hall_id.'<br/>';
				}else {
					$product_attribute =   'seats: <br/> Booking day: '.$event_date.'<br/>'.
								       ' Ticket Type 2: Show with Meal<br/> Ticket Type 2: <br/> object: '.$hall_id.'<br/>';
				}
 	$sql = " INSERT INTO `jos_vm_order_item`  SET ".
						     " order_id =  '$order_id' ,".
						     " vendor_id = '$ps_vendor_id' ,".
						     " product_id = '$parent_id' ,".
							 " order_status	 = '$order_status	' ,".
						     " order_item_sku = '$order_item_sku' ,".
						     " order_item_name = 'DOWNLOADABLE' ,".
						     " product_quantity = '$increaseQty',".
						     " product_item_price = '$product_item_price' ,".
						     " product_final_price = '$product_final_price' ,".
						     " cdate ='$cdate',".
						     " mdate = '$mdate',".
						     " product_attribute = '$product_attribute'";

			$db->setQuery($sql);
			$db->query();
			$orderitemId  = $db->insertid(); // get Last inserterd id that will  store in ticket table
			/*
			for($i=1; $i<= $increaseQty; $i++ ){
			$ticketean = $this->getEanCode();
			 $sql = " INSERT INTO `jos_vmeticket_ticket`  SET ".
					   " ticket_ean =  '$ticketean' ,".
				       " order_item_id =  '$orderitemId' ,".
					   " ticket_start_validity =  '$ticketStartvalidity' ,".
					   " ticket_end_validity = '$ticketStartvalidity'";

					$db->setQuery($sql);
					$db->query();

				}*/

	}

/*/
* Function for update booking Increase booking
*/
function updatebooking($order_id,$productid,$increaseshowonly,$increasemeals,$totalticketamount,$eventdate){
	$db =& JFactory::getDBO();
		$timestamp = time();
		$eventdate = strtotime($eventdate);
		 $query = " UPDATE jos_vm_orders  SET ".
				 " `order_total` = order_total + ".$totalticketamount.
				 " WHERE `order_id` = ".$order_id;
		$db->setQuery($query);
		$db->query();
		$hall_id = $this->getEventHallId($productid);
			if($increasemeals){
					$query = "SELECT order_item_id FROM jos_vm_order_item where ".
						" product_attribute  like '%Ticket Type 2: Show with Meal%' AND order_id = ".$order_id ;
					$db->setQuery($query);
					$db->query();
					$order_item_id = $db->loadResult();
					if($order_item_id){
						$query = " UPDATE `jos_vm_order_item` SET ".
							   	 " `product_quantity` = product_quantity + ".$increasemeals.
								 " WHERE `order_item_id` = ".$order_item_id;
				             	$db->setQuery($query);
								$db->query();
					}else
					{
					$result =	$this->IncreaseBookingByOrderId($order_id,$productid,$hall_id,$increasemeals,$increaseType=2,$totalticketamount,$totalticketamount,$eventdate);
					}
					}
					if($increaseshowonly)
					{

						$query = " SELECT order_item_id FROM jos_vm_order_item where ".
								 " product_attribute  like '%Ticket Type: Show_Only%' AND order_id = ".$order_id ;
						$db->setQuery($query);
						$db->query();
						$order_item_id = $db->loadResult();
						if($order_item_id){
								$query = " UPDATE `jos_vm_order_item` SET ".
  									   	 " `product_quantity` = product_quantity + ".$increaseshowonly.
							   			 " WHERE `order_item_id` = ".$order_item_id;
								$db->setQuery($query);
								$db->query();
						}
						else {
						$this->IncreaseBookingByOrderId($order_id,$productid,$hall_id,$increaseshowonly,$increaseType=1,$totalticketamount,$totalticketamount,$eventdate);
						}
				$totalIncrease = $increaseshowonly+$increasemeals;
				$this->sendIncresbookingemail($order_id,$totalIncrease,$increaseshowonly,$increasemeals);
					}
			return true;

	}

	/*****
	 *Functiom for geting user info
	 **/
	function getUserInfoFromOrderId($order_id){

		$db =& JFactory::getDBO();
	 	$query = "SELECT last_name,first_name,user_email FROM jos_vm_order_user_info WHERE order_id= ".$order_id;
		$db->setQuery($query);
		$userDetail  =  $db->loadobject();
		return $userDetail;
	}

	/****
	 *Function For Send Email notification
	 **/
	function sendIncresbookingemail($order_id,$totalIncrease){


			$ticketType = $this->totalbookingtype($order_id);
			$event_id = $this->getEventIdfromOrderId($order_id);
			$perfermarName = $this->getPerformarName($event_id);
			$showDate = $this->getShowDateFromOrderId($order_id);

			$showDate =  date('jS \of F Y',strtotime($showDate));
			$userInfo = $this->getUserInfoFromOrderId($order_id);

			if($totalIncrease > 1){

			$increaseMsg = $totalIncrease." tickets";

			}else {

			$increaseMsg = $totalIncrease." ticket";
			}

			$ticketDownloadUrl ="";

			$html.=  '<table><tr></td>Hi '.ucwords($userInfo->first_name).',</td></tr>';
			$html.=  '<tr><td></td></tr>';
			$html.=  '<tr><td></td></tr>';
			$html.=  '<tr><td>Thank you for increasing your booking by '.$increaseMsg.' for the performance on '.$showDate.' featuring '.$perfermarName.'</tr></td>';
			$html.=  '<tr><td></td></tr>';
			$html.=  '<tr><td>Please <a href="'.$ticketDownloadUrl.'">click here</a> to view and print your tickets.<tr><td>';
			$html.=  '<tr><td></td></tr>';
			$html.=  '<tr><td>We look forward to seeing you at the show!</td></tr>';
			$html.=  '<tr><td></td></tr>';
			$html.='<tr><td>Best Regards,</td></tr>';
			$html.='<tr><td>The Comic\'s Lounge<tr><td>';
				$html.=  '<tr><td></td></tr>';
				$html.=  '<tr><td></td></tr>';
				$html.=  "</table>";

				$config =& JFactory::getConfig();
				$from 	 	= $config->getValue( 'mailfrom' );
				$fromname 	=  $config->getValue( 'fromname' );
				$recipient 	= $userInfo->user_email;
				$subject 	= JText::_('Booking confirmation - your additional tickets for The Comic\'s Lounge');
				$mode=1;
				JUtility::sendMail($from, $fromname, $recipient, $subject, $html, $mode);
				return true;

	}

		/****
	 *Function For Send Email notification
	 **/
	function sendDecreasebookingemail($order_id, $totalDecrease){


			$ticketType = $this->totalbookingtype($order_id);
			$event_id = $this->getEventIdfromOrderId($order_id);
			$perfermarName = $this->getPerformarName($event_id);
			$showDate = $this->getShowDateFromOrderId($order_id);

			$showDate =  date('jS \of F Y',strtotime($showDate));
			$userInfo = $this->getUserInfoFromOrderId($order_id);

			if($totalDecrease > 1){

			$increaseMsg = $totalDecrease." tickets";

			}else {

			$increaseMsg = $totalDecrease." ticket";
			}

			$ticketDownloadUrl ="";

			$html.=  '<table><tr></td>Hi '.ucwords($userInfo->first_name).',</td></tr>';
			$html.=  '<tr><td></td></tr>';
			$html.=  '<tr><td></td></tr>';
			$html.=  '<tr><td>Your booking by '.$increaseMsg.' for the performance on '.$showDate.' featuring '.$perfermarName.'</tr></td>';
			$html.=  '<tr><td></td></tr>';
			$html.=  '<tr><td>Please <a href="'.$ticketDownloadUrl.'">click here</a> to view and print your tickets.<tr><td>';
			$html.=  '<tr><td></td></tr>';
			$html.=  '<tr><td>We look forward to seeing you at the show!</td></tr>';
			$html.=  '<tr><td></td></tr>';
			$html.='<tr><td>Best Regards,</td></tr>';
			$html.='<tr><td>The Comic\'s Lounge<tr><td>';
			$html.=  '<tr><td></td></tr>';
			$html.=  '<tr><td></td></tr>';
			$html.=  "</table>";

				$config =& JFactory::getConfig();
				$from 	 	= $config->getValue( 'mailfrom' );
				$fromname 	=  $config->getValue( 'fromname' );
				$recipient 	= $userInfo->user_email;
				$subject 	= JText::_('Booking confirmation - your additional tickets for The Comic\'s Lounge');
				$mode=1;
				JUtility::sendMail($from, $fromname, $recipient, $subject, $html, $mode);
				return true;

	}

	/** *
	 * Function geting hall id
	 */
	function getEventHallId($eventid){
		$db =& JFactory::getDBO();
		$query = "SELECT attribute FROM #__vm_product  WHERE product_id = ".$eventid;
		$db->setQuery($query);
		$attribute =  $db->loadResult();
		$object = explode(',',$attribute);
		$object_id = end($object);
		return $object_id;
	}
	/***
	 *Function for get hall capcity
	 */

	function getHallCapacity($objectid){

		$db =& JFactory::getDBO();
		$query = "SELECT capacity FROM #__vmeticket_object  WHERE id = ".$objectid;
		$db->setQuery($query);
		$capcity= $db->loadResult();
		return $capcity;


	}
	/*
	Function for Search result used in both case edit and cancel booking
	*/

	function getsearchresult($refrencenumber,$ticketnumber,$mobilenumber,$email,$bookingname,$eventid,$eventdate){

			$db =& JFactory::getDBO();

			$parent_product_id   = $this->getDownloadableProductId($eventid);

			 $where =" i.product_id= $parent_product_id AND i.product_attribute like '%$eventdate%' AND ";

			//Reset Default value

			if($refrencenumber == "ORDER NUMBER"){
			$refrencenumber = "";
			}
			if($ticketnumber == "TICKET BARCODE"){
			$ticketnumber = "";
			}
			if($mobilenumber == "MOBILE NUMBER"){
			$mobilenumber = "";
			}
			if($email == "EMAIL ADDRESS"){
			$email = "";
			}
			if($bookingname == "TYPE TICKET NO."){
			$refrencenumber = "";
			}


			if($refrencenumber){
			$where .= "i.order_id = ".$refrencenumber." AND ";
			}
			if($ticketnumber){
			$where .= " t.ticket_ean = '".$ticketnumber."' AND ";
			}if($mobilenumber){
			$where .= " u.phone_1 = '".$mobilenumber."' AND ";
			}
			if($email){
			$where .= " u.user_email = '".$email."' AND ";
			}
			//if($bookingname){
			//$where .= " first_name = '".$bookingname."' AND ";
			//}
			$vender_id = 1;
			$pulished = 1;
			$orderby = " ORDER BY u.order_id DESC ";
			$limit = 'LIMIT 1';


   $query  = "SELECT * FROM jos_vm_orders as o LEFT JOIN jos_vm_order_user_info as u ".
				  " ON o.order_id = u.order_id ".
				  " LEFT JOIN jos_vm_order_item as i ".
				  "	ON o.order_id = i.order_id ".
				  "	LEFT JOIN jos_vmeticket_ticket as t ".
				  "	ON i.order_item_id = t.order_item_id ".
				  " WHERE ".$where . " o.vendor_id =".$vender_id." AND  i.order_status != 'X' ".$limit ;
		$db->setQuery($query);
		$result = $db->loadObjectList($query);
		return $result;

	}



	/*Function Return Attributes for a event */

	function getEventAttribute($eventid){
		$db =& JFactory::getDBO();
		$query = "SELECT attribute FROM #__vm_product WHERE product_id =".$eventid;
		$db->setQuery($query);
		$attribute = $db->loadResult();
		return $attribute;

	}

	/***********************************************
	* Function for get product amount and total ****
	************************************************/

	function getbookingamount($eventid, $showonly, $meals, $event_date, $edit){
			require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'virtuemart.cfg.php');
			$db =& JFactory::getDBO();

			$attribute = $this->getEventAttribute($eventid);
			$advanced_attribute_list = explode (";",$attribute);

            $query = "SELECT product_price FROM jos_vm_product_price WHERE product_id =".$eventid;
			$db->setQuery($query);
			$ticketBasePrice = $db->loadResult();

			//for base meal price
			$baseindex = strpos($advanced_attribute_list[3], '[')." ";
			$additionalCost = '';
				if ($baseindex != ' ') {
					$additionalCost = substr ($advanced_attribute_list[3], $baseindex+1, -1);
				}

			$mealTicketBasePrice=$ticketBasePrice+$additionalCost; //event base show with meal price

			$todayShowPrice = $ticketBasePrice;
			$todayShowWithMealPrice = $mealTicketBasePrice;

			$dates_with_price = explode (",", $advanced_attribute_list[1]);
			/********************/
									foreach($dates_with_price as $val){
											if($val=='Booking day')continue;
											$index = strpos($val, '[')." ";
											$mealindex = strpos($val, '|')." ";
											$addshowprice = '';
											$addmealprice = '';
											$showPrice = $ticketBasePrice;
											$showWithMealPrice = $ticketBasePrice;
											if ($index != ' ' && $mealindex != ' ') {
												$addshowprice = substr ($val, $index+2, $mealindex-$index-2);
												if($addshowprice<9){
													$addshowprice = substr ($addshowprice, 0, 1);
													$addmealprice = substr ($val, $mealindex+2, -1);
												}else{
													$addmealprice = substr ($val, $mealindex+2, -1);
												}
												$operand_show = substr ($val, $index+1, 1);
												$operand_meal = substr ($val, $mealindex+1, 1);
												$val = substr ($val, 0, (int)$index);
											}

											$date = strtotime($val);
											$booking_day = strtotime($event_date);

											if (true == is_numeric($addshowprice) || true == is_numeric($addmealprice) ) {
												// Now add or sub the modifier on
												if ($operand_show=="+" ) {
														$showPrice += $addshowprice;
												}else if ($operand_show=="-" ) {
														$showPrice -= $addshowprice;
												}
												else if ($operand_show=="=" ){
														$showPrice = $addshowprice;
												}

												if ($operand_meal=="+") {
														$showWithMealPrice += $addmealprice;
												}else if ($operand_meal=="-" ) {
														$showWithMealPrice -= $addmealprice;
												}
												else if ($operand_meal=="=" ){
														$showWithMealPrice = $addmealprice;
												}

											}

											if($date == $booking_day){
												$event_show_price = $showPrice;
												$event_meal_price = $showWithMealPrice;
											}

										}
				/********************/

			if($showonly>0){
				$totalshowonly = $showonly * $event_show_price;
			}
			if($meals>0){
				$totalamount =   $meals * $event_meal_price;
			}

				$TAmount =array();
				$bookingfees = VM_BOOKING_FEE;

				if($edit){
					$finalAmount = 	$totalamount + $totalshowonly;
					$TAmount['bookingfee'] =0;
				}
				else{
					$finalAmount = 	$totalamount + $totalshowonly + $bookingfees;
					$TAmount['bookingfee'] = $bookingfees;
				}
				// english notation without thousands separator

				$finalAmount = number_format($finalAmount, 2, '.', '');



				$finalAmount  = number_format($finalAmount, 2, '.', '');
				$product_id = $eventid - 1;
				$gst = $this->getGstAmount($product_id,$finalAmount);
				$bookingfees  =  number_format($bookingfees, 2, '.', '');

				$TAmount['amount'] = $finalAmount;
				$TAmount['gst'] = $gst;
				return $TAmount;
	}


	/* Function add manual booking in Vm tables for order Common for both  */
	/* Function add manual booking in Vm tables for order */

	function savebooking($ticketnumber,$bookingname,$email,$mobilenumber,$noofticket,$eventid,$totalticketamount,$showonly,$meals,$noofseat,$voucher_code,$voucher_type,$object_id,$allocated_tablenumber){
			/*if($voucher_code){
				  $voucher_code = implode(',',$voucher_code);
				  $voucher_type = implode(',',$voucher_type);
			}*/

			$db =& JFactory::getDBO();

			// Get the IP Address
			if (!empty($_SERVER['REMOTE_ADDR'])){
					$ip = $_SERVER['REMOTE_ADDR'];}
				else {
				$ip = 'unknown';}
				// Insert the main order information

				$timestamp = strtotime(Date('Y-m-d',time()));


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
					" order_currency = 'AUD',".
					" order_status = 'C',".
					" cdate = $timestamp,".
					" mdate = $timestamp,".
					" customer_note = 'Booking by planner',".
					" ip_address = '$ip'";
				//echo "<br>query1 => ".$sql;
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
					" order_status_code = 'C',".
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
				$order_payment_trans_id = "cltranstionid1234456";
				$sql =  "INSERT INTO #__vm_order_payment SET ".
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

						$order_status = 'C';
						$order_item_currency = 'AUD';
						$product_final_price = $totalticketamount;
						$product_item_price = $totalticketamount;
						$product_quantity = $noofticket;

				//Multiple entry in Jos_vm_order_items


				$event_date = date('Y-m-d');
				$event_date = $event_date."_00: 00";

				if($showonly > 0){



				$product_attribute =   'seats: <br/> Booking day: '.$event_date.'<br/>'.
						       ' Ticket Type: Show_Only<br/> Ticket Type 2: <br/> object: '.$object_id.'<br/>';

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
						$starttime = $timestamp;
						$endtime = $timestamp + 86390;
						for($i=1; $i<= $showonly; $i++ ){

						$ticketean = $this->getEanCode();
						//$ticketean  =  $timestamp+$i+$orderitemId;

								$sql = " INSERT INTO `jos_vmeticket_ticket`  SET ".
									   " ticket_ean =  '$ticketean' ,".
								       " order_item_id =  '$orderitemId' ,".
									   " ticket_start_validity =  '$starttime' ,".
									   " ticket_end_validity = '$endtime'";
								$db->setQuery($sql);
								if (!$db->query()) {
								echo "<script> alert('".$db->getErrorMsg(true)
								."'); window.history.go(-1); </script>\n";
								}
						}

				}
				if($meals >0 ){



				$product_attribute =   'seats: <br/> Booking day: '.$event_date.'<br/>'.
								       ' Ticket Type 2: Show with Meal<br/> Ticket Type 2: <br/> object: '.$object_id.'<br/>';

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

					$orderitemId  = $db->insertid(); // get Last inserterd id that will  store in ticket table

					//INsert in to VM ticket table also so that validate later
                    $starttime = $timestamp;
                    $endtime = $timestamp + 86390;

								for($i=1; $i<= $meals; $i++ ){


								$ticketean = $this->getEanCode();
								//$ticketean  =  $timestamp+$i;


								$sql = " INSERT INTO `jos_vmeticket_ticket`  SET ".
									   " ticket_ean =  '$ticketean' ,".
								       " order_item_id =  '$orderitemId' ,".
									   " ticket_start_validity =  '$starttime' ,".
									   " ticket_end_validity = '$endtime' ,".
									   " ticket_admission = 1 ";
								$db->setQuery($sql);
									if (!$db->query()) {
									echo "<script> alert('".$db->getErrorMsg(true)
									."'); window.history.go(-1); </script>\n";
									}
								}




				}
					return $order_id;

	}

	/****
	 **function that allot a seat number to aparticular ticket
	 ****/
	function seatallotment($ticketnumber)
	{

		$totalSeat = 2;
		$orderNumber = 1234567;
		$showonly = 1;
		$showplusmeals = 1;
		$objectid = 5; // hall id


		$db =& JFactory::getDBO();
		$query = " SELECT table_number FROM #__checkin;";
		$db->setQuery($query);
		$allotedseat = $db->loadRow();
		//print_r($allotedseat); //till date Seat numnber that we booked or checkin

		$query = " SELECT * FROM `jos_vmeticket_section` WHERE object_id = ".$objectid;
		$db->setQuery($query);
		$seats = $db->loadobjectlist();
		foreach($seats as $s)
		{
			 $s->section_name;
		}

		$seatnumber = rand( 0 ,50);
		return $seatnumber;

	}

	/**
	* function for geting ticket detail at the time of admin used in palner menu called by ajax
	*
	***/

	function  admit($ticketnumber)
	{
		$db =& JFactory::getDBO();
		$date = date("Y-m-d H:i:s");
		$evnetid = $this->getEventIdfromOrderId($ticketnumber);

		 $query = " INSERT INTO  #__checkin SET ".
				  " ticketnumber =$ticketnumber ".
				  " ,ordernumber = '$ticketnumber '".
				  " ,date =' $date '".
				  " ,eventid = $evnetid ";


		$db->setQuery($query);
		$db->query();
		$lastId = $db->insertid();
		if($lastId){?>
		<li class="booking_found_headding"><?php echo JText::_('Admit Successfull');?></li>
		<?} else { ?>
		<li class="booking_found_headding"><?php echo JText::_('Error in System');?></li>
		<?php }

		//return $lastId;
	}

	function getEventIdfromOrderId($order_id)
	{
		$db =& JFactory::getDBO();
		$query = " SELECT product_id FROM #__vm_order_item ".
			      " WHERE  order_id = ".$order_id;
		$db->setQuery($query);
		$result = $db->loadResult();
		return $result;

	}

	/***
	 *
	 *Function for checking seat is allready alloted or not
	 *
	 */
	function checkticketdetail($ticketnumber){

		$db =& JFactory::getDBO();
		$query = " SELECT table_number FROM #__checkin".
			      " WHERE  ticketnumber = ".$ticketnumber;
		$db->setQuery($query);
		$result = $db->loadResult();
		if($result){
		return $result;}
		else {return false;}

	}



    /**
     * function for geting ticket detail at the time of admin used in palner menu called by ajax
     *
     ***/

    function  getticketdetail($ticketnumber)
    {
	$db =& JFactory::getDBO();
	$query = " SELECT * FROM #__vm_orders as o ".
		  " LEFT JOIN (#__vm_order_user_info as u,#__vm_order_item as i) ".
		  " ON (o.order_id = u.order_id And o.order_id = i.order_id) WHERE o.order_id = ".$ticketnumber;
	$db->setQuery($query);
	$rows = $db->loadObjectlist();
	return $rows;

    }

    /**
     * Function for display all Events  *
     * */

    function getAllEvent()
    {
			$db =& JFactory::getDBO();
			$now = $this->getCurrentDate();
			$query =  " SELECT DISTINCT performar_title,vp.product_id,vp.product_name,vp.attribute,vp.seling_status  "
				 ." FROM jos_vm_product as vp  RIGHT JOIN (jos_performar as pr ,jos_vm_product_type_1 as pt ) ON "
				 ." (vp.performer_id  = pr.id  And vp.product_id = pt.product_id ) WHERE vp.product_parent_id = 0 AND "
				 ." DATE(FROM_UNIXTIME(pt.vmeticket_end_validity)) >= DATE('$now') "
				 ." AND vp.product_publish = 'Y' ORDER BY pt.vmeticket_end_validity";
			$db->setQuery($query);
			$row = $db->loadObjectlist();
			return $row;

    }

    /**
     * Function for display  Tonight Events only
     * */

    function gettonightEventEvent(){
	$db =& JFactory::getDBO();
	$now = $this->getCurrentDate();
	$query =  "SELECT DISTINCT performar_title,vp.product_id,vp.product_name,vp.attribute,vp.seling_status  "
				 ." FROM jos_vm_product as vp  RIGHT JOIN (jos_performar as pr ,jos_vm_product_type_1 as pt ) ON "
				 ." (vp.performer_id  = pr.id  And vp.product_id = pt.product_id ) WHERE vp.product_parent_id = 0 AND "
				 ." DATE(FROM_UNIXTIME(pt.vmeticket_start_validity)) <= DATE('$now') AND DATE(FROM_UNIXTIME(pt.vmeticket_end_validity)) >= DATE('$now')"
				 ." AND vp.product_publish	= 'Y'  LIMIT 1";
	//echo "query $query";
		$db->setQuery($query);
		$row = $db->loadObject();
		return $row;

    }
	/**
	 *Used in calender page
	*/
	function getTonightEvent(){
	$now = $this->getCurrentDate();

		$db =& JFactory::getDBO();
        $query =  " SELECT DISTINCT performar_title,vp.product_id "
				 ." FROM jos_vm_product as vp  RIGHT JOIN (jos_performar as pr ,jos_vm_product_type_1 as pt ) ON "
				 ." (vp.performer_id  = pr.id  And vp.product_id = pt.product_id ) WHERE vp.product_parent_id = 0 AND "
				 ." DATE(FROM_UNIXTIME(pt.vmeticket_start_validity)) <= DATE('$now') AND DATE(FROM_UNIXTIME(pt.vmeticket_end_validity)) >= DATE('$now') "
				 ." AND published =1  LIMIT 1";
					$db->setQuery($query);
					$tonightEvent = $db->loadObject();
					return $tonightEvent;
	}
	/*
	*Function for geting gst Amount
	**/
	function getGstAmount($eventid,$productamount){

			$db =& JFactory::getDBO();
			$q = 'SELECT tax_rate FROM #__vm_product, #__vm_tax_rate ';
			$q .= 'WHERE product_tax_id=tax_rate_id AND product_id='.(int)$eventid;
			$db->setQuery($q);
			$gst = $db->loadResult();
			$gst = $gst * $productamount;
			$gst = number_format($gst, 2, '.', '');
			return $gst;

	}
	/*
	 Function return total number of availbale table after allocation
	*/
	function getTotalAvilabelTable($noofticket,$object_id){

		$db =& JFactory::getDBO();
		 $query = "SELECT DISTINCT jvs.id, jvs.section_name, jvs.section_code FROM jos_vmeticket_section as jvs LEFT JOIN jos_vmeticket_ticket as jvt ON jvs.id=jvt.table_id  WHERE jvs.object_id= $object_id GROUP BY jvs.id, jvs.seats HAVING (jvs.seats - count(jvt.order_item_id)) >= $noofticket ORDER BY jvs.section_code ASC ";
        $db->setQuery($query);
		$tablelist  = $db->loadObjectList();
		return $tablelist;

	}
	/**
	 *function for Assign table and made check in
	 **/

	function checkin($order_id, $table_id){
		$db =& JFactory::getDBO();
		$updatesql = "UPDATE jos_vmeticket_ticket SET table_id = ".$table_id.", ticket_admission=0 WHERE ";
		$updatesql .= "order_item_id in (SELECT order_item_id FROM jos_vm_order_item where order_id=".$order_id.")";
		$db->setQuery($updatesql);
		$db->query();
	}
	/**
	 *function genrate ean code
	 **/
	function getEanCode(){

		$db =& JFactory::getDBO();
		$length = 7;
		while (true) {
			$ean = '';
			for ($j=0; $j < 7; $j++) {
			$ean .= rand (1, 9);
		}
		$query = " SELECT count(*) as count FROM #__vmeticket_ticket "
				." WHERE ticket_ean='$ean' ";

		$db->setQuery($query);
		$count = $db->loadObject();
		if ($count->count == 0){
				break;
		}
		}
		return $ean;


	}
	/*
	 function get Parent Id for product
	**/

	function getDownloadableProductId($event_id){

			$db =& JFactory::getDBO();
			$query = "SELECT product_id  FROM jos_vm_product WHERE product_parent_id = $event_id  AND product_publish = 'Y' ";
			$db->setQuery($query);
			$parent_id = $db->loadResult();
			return $parent_id;

		}

	function getParentProductId($event_id){

			$db =& JFactory::getDBO();
			$query = "SELECT product_parent_id  FROM jos_vm_product WHERE product_id = $event_id  AND product_publish = 'Y' ";
			$db->setQuery($query);
			$parent_id = $db->loadResult();
			return $parent_id;

		}

	/*
	 Function get Performar name for an event
	*/
	function getPerformarName($event_id){

		$db =& JFactory::getDBO();
		 $sql = "SELECT performar_title FROM jos_performar as per JOIN ".
			   "jos_vm_product as pro ON (per.id = pro.performer_id) Where product_id = ".$event_id;
		$db->setQuery($sql);
		$performerName = $db->loadResult();
		return $performerName;


	}

	/*Functin for get Show date from Order id
	/*/
	function getShowDateFromOrderId($order_id){
		$db =& JFactory::getDBO();
	    $sql = "SELECT product_attribute FROM jos_vm_order_item WHERE order_id = ".$order_id. " LIMIT 1";
		$db->setQuery($sql);
		$product_attribute  = $db->loadResult();
		$attributelist = explode('Booking day: ',$product_attribute);
		$start_time = substr($attributelist[1],0,10);
		return 	$start_time;

	}
	/**
	*Function return date of order for which product is purched
	***/
	function getItemsDate($item_id){

		$db =& JFactory::getDBO();
	    $sql = "SELECT product_attribute FROM jos_vm_order_item WHERE order_item_id IN ($item_id)";
		$db->setQuery($sql);
		$product_attributelist  = $db->loadobjectlist();
		foreach($product_attributelist as  $product_attribute){
			$attributelist = explode('Booking day: ',$product_attribute->product_attribute);
			$order_itemdates[] = substr($attributelist[1],0,10);
		}
		return $order_itemdates;
	}

	/*/*Function Returnn total booking for an order */
	function getTotalBookingForOrder($order_id){

		$db =& JFactory::getDBO();
		$sql = "SELECT sum(product_quantity) FROM jos_vm_order_item WHERE order_id = ".$order_id;
		$db->setQuery($sql);
		$result = $db->loadResult();
		return $result;

	}
	/*/
	Event detail based on event id
	/**/
	function getEventdetail($eventid)
	{
		$db =& JFactory::getDBO();
		$query = "SELECT seling_status,meals,product_name,attribute  FROM #__vm_product as p LEFT JOIN ".
		 " #__jcalpro2_events as e ".
		 " ON p.product_id = e.event_id WHERE product_id = ".$eventid." AND  published = 1 LIMIT 1";
		$db->setQuery($query);
		$row = $db->loadObject();
		return $row;

	}


}
?>
