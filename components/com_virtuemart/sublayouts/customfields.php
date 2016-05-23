<?php
/**
* sublayout products
*
* @package	VirtueMart
* @author Max Milbers
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2014 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL2, see LICENSE.php
* @version $Id: cart.php 7682 2014-02-26 17:07:20Z Milbo $
*/

defined('_JEXEC') or die('Restricted access');

$product = $viewData['product'];
$position = $viewData['position'];
$customTitle = isset($viewData['customTitle'])? $viewData['customTitle']: false;;
if(isset($viewData['class'])){
	$class = $viewData['class'];
} else {
	$class = 'product-fields';
}


$cartarray = $product->customfieldsSorted[addtocart];

$cartarraynoral = $product->customfieldsSorted[normal];
//get the produc id 



if (!empty($product->customfieldsSorted[$position])) {

   foreach ($cartarraynoral as $field)
   {
	   if($field->virtuemart_custom_id==75)
		 {    
	         $start = date('Y-m-d',strtotime($field->customfield_value));
             $start_date = date('l j F',strtotime($field->customfield_value));
		 }	 
		  if($field->virtuemart_custom_id==76)
		 {
			 $end1 = date('Y-m-d',strtotime($field->customfield_value));
             $end_date = date('l j F',strtotime($field->customfield_value));
		 }
	   
	   
   }
   
  $begin = new DateTime($start);
  $end = new DateTime($end1);
  $interval = new DateInterval('P1D'); // 1 Day
  $dateRange = new DatePeriod($begin, $interval, $end);
  //$range = [];
  foreach ($dateRange as $date) {
        $range[] = $date->format('Y-m-d');
    }
	$range[] = $end1;
   
   
	?>
<div class="eventD_Head4">
<?php
$events = $product->customfieldsSorted['normal'];

//get the event show with meal and without meal
foreach($events as $event)
{

$product_id  = $field->virtuemart_product_id;
$findstring  = $product_id.'(showonlyprice)';
$findstring2 = $product_id.'(showwithmealprice)';

if (strpos($event->custom_title, $findstring) !== false) {
    $eventsarray[$event->custom_value][showonly][text]= $event->customfield_value;
	$eventsarray[$event->custom_value][showonly][price]= $event->customfield_price;
	$eventsarray[$event->custom_value][showonly][date] = $event->custom_value;
	$eventsarray[$event->custom_value][showonly][display] = $event->custom_value;
	$eventsarray[$event->custom_value][showonly][ids] = $event->virtuemart_custom_id."|".$event->virtuemart_customfield_id."|".$event->custom_value;
}
if (strpos($event->custom_title, $findstring2) !== false) {
    $eventsarray[$event->custom_value][showwithmealprice][text]= $event->customfield_value;
    $eventsarray[$event->custom_value][showwithmealprice][price]= $event->customfield_price;
	$eventsarray[$event->custom_value][showwithmealprice][date] = $event->custom_value;
	$eventsarray[$event->custom_value][showwithmealprice][ids] = $event->virtuemart_custom_id."|".$event->virtuemart_customfield_id."|".$event->custom_value;
}
 

}

 foreach($eventsarray as $neweventdate){
	 $disable='';
	 $current = strtotime(date('Y-m-d'));
	 $datearray = explode('/',$neweventdate['showonly']['date']);
	 $newevent_date =$datearray[2]."-".$datearray[1]."-".$datearray[0];
     $name = $product_id.str_replace('/','_',$neweventdate['showonly']['date']);
     $customid_customfieldid_date1 = $neweventdate['showonly'][ids];
     $customid_customfieldid_date2 = $neweventdate['showwithmealprice'][ids];	 
     $event_date =strtotime($newevent_date);
	 if($current  > $event_date ){
		$disable= "disabled='disabled'";  
	  }else{
		$disable='';
	  }  
	
?>

 <div class="fleft">	
			
<input   <?php echo $disable;?>     onclick="change(this);"  type="radio"  value="<?php echo $name;?>" name="Booking_day<?php echo $product->virtuemart_product_id;?>" id="Booking_day_field1" class="inputboxattrib fleft">
<input     type="hidden"  value="<?php echo $customid_customfieldid_date1;?>" name="<?php echo $name."showonly"; ?>" id="<?php echo $name."showonly"; ?>" class="inputboxattrib fleft">  
<input     type="hidden"  value="<?php echo $customid_customfieldid_date2;?>" name="<?php echo $name."showwithmealprice"; ?>" id="<?php echo $name."showwithmealprice"; ?>" class="inputboxattrib fleft"> 

<label for="Booking_day_field1"><?php echo  date('D j M',strtotime( $newevent_date));?>
<?php if($disable!=''){?>
<span title="This event is closed. You can purchase tickets for this event from the venue." onclick="alert('This event is closed. You can purchase tickets for this event from the venue.');" class="eventClose"><img src="/images/closeBtn.png"></span>            


<?php
}
?>

</label>
</div>

<?php	
}
 ?>
</div>
	<div class="<?php echo $class?>">
		<?php
		if($customTitle and isset($product->customfieldsSorted[$position][0])){
			$field = $product->customfieldsSorted[$position][0]; ?>
		<div class="product-fields-title-wrapper"><span class="product-fields-title"><strong><?php echo vmText::_ ($field->custom_title) ?></strong></span>
			<?php if ($field->custom_tip) {
				echo JHtml::tooltip (vmText::_($field->custom_tip), vmText::_ ($field->custom_title), 'tooltip.png');
			} ?>
		</div> <?php
		}
		$custom_title = null;

		foreach ($product->customfieldsSorted[$position] as $field) {
			
			
			
		if($field->virtuemart_custom_id==73)
		 {
			 if(strlen($performerName)>0)
			 {
			   $performerName = $performerName." & ".$field->customfield_value;
				 
			 }
			 else
			 {
				$performerName = $performerName.$field->customfield_value;	
			 }	 
			  
			 
		 }		
		
         if($field->virtuemart_custom_id==74)
		 {
			 if(strlen($SupporterName)>0)
			 {
			   
 			   $SupporterName = $SupporterName." & ".$field->customfield_value;
				 
			 }
			 else
			 {
				  $SupporterName = $SupporterName.$field->customfield_value;
			 }	 
			  
			 
		 }
          
          if($field->virtuemart_custom_id==75)
		 {    
	         $start = date('Y-m-d',strtotime($field->customfield_value));
             $start_date = date('l j F',strtotime($field->customfield_value));
		 }	 
		  if($field->virtuemart_custom_id==76)
		 {
			 $end1 = date('Y-m-d',strtotime($field->customfield_value));
             $end_date = date('l j F',strtotime($field->customfield_value));
		 }
		 
		 if($field->virtuemart_custom_id==12)
		 {
			 // get the dates
			 $dats =explode(';',$field->custom_value);
           
			  foreach($dats  as $key=>$value)
			  {
				  $dmyats[]['name']	= $value;  
			  }
		}

         if($field->virtuemart_custom_id==67)
		 {
			
			   
			  $showonlyprice = $field->customfield_price;
		 }	
         if($field->virtuemart_custom_id==66)
		 {
			  
			   $dinnertime = $field->customfield_price;
		 }
         	if($field->virtuemart_custom_id==80)
		 {
			  
			   $showwithmealprice = $field->customfield_value;
		 }		

		if($field->virtuemart_custom_id==79)
		 {
			  
			   $showtime = $field->customfield_value;
		 }				 
					 
			
			if ( $field->is_hidden || empty($field->display)) continue; //OSP http://forum.virtuemart.net/index.php?topic=99320.0
			?><div class="product-field product-field-type-<?php echo $field->field_type ?>">
				<?php if (!$customTitle and $field->custom_title != $custom_title and $field->show_title) { ?>
					<span class="product-fields-title-wrapper"><span class="product-fields-title"><strong><?php //echo vmText::_ ($field->custom_title) ?></strong></span>
						<?php if ($field->custom_tip) {
							//echo JHtml::tooltip (vmText::_($field->custom_tip), vmText::_ ($field->custom_title), 'tooltip.png');
						} ?></span>
				<?php }
				if (!empty($field->display)){
					?><div class="product-field-display"><?php if($field->is_cart_attribute==1){ 
					
					//echo "<pre>";
					//print_r($field);
					//echo "</pre>";
					?>
					
					<?php if($field->virtuemart_custom_id == 66 || $field->virtuemart_custom_id == 67 || $field->virtuemart_custom_id == 83 ){?>
					<div class="fleft">
							<div class="eventD_Head2">
                     <?php
					echo str_replace('radio','hidden',$field->display); if($field->virtuemart_custom_id != 83){echo $field->customfield_value;}
					?>
					</div>
					</div>
					<?php
					}
					else
					{
						if($field->virtuemart_custom_id == 84)
						{
							?>
							<input type="hidden" name="customProductData[<?php echo $field->virtuemart_product_id;?>][<?php echo $field->virtuemart_custom_id;?>][<?php echo $field->virtuemart_customfield_id;?>]" id="customProductData_<?php echo $field->virtuemart_product_id;?>_<?php echo $field->virtuemart_customfield_id;?>" value="">
							<input type="hidden" id="datenpricevalue<?php echo $field->virtuemart_product_id;?>" name="datenpricevalue<?php echo $field->virtuemart_product_id;?>" value="<?php echo $field->virtuemart_customfield_id;?>">
	
							
						<?php	
						}
						else{
							echo $field->display;
							
						}
						
						
						
						
					}					?>
					<?php if($field->virtuemart_custom_id == 66 ){ 
						if($product->custom_value=='na' )
						  {
							  $display ="display:none";
						  }
					?>
					<input style="<?php echo $display;?>"  type="text" size="1" autofocus="autofocus" class="inputboxattrib" maxlength="2" id="Ticket_Type_qty_field" name="Ticket_Type2342qty">
					<?php } ?>
					
					<?php if($field->virtuemart_custom_id == 67 ){
                          if($field->customfield_value=='na' )
						  {
							  $display ="display:none";
						  }    
						  ?>
					<input style="<?php echo $display;?>" type="text" size="1" autofocus="autofocus" class="inputboxattrib" maxlength="2" id="Ticket_Type_2_qty_field" name="Ticket_Type_22342qty">
					

					<?php } ?>
					
					<?php
					}?></div><?php
				}
				if (!empty($field->custom_desc)){
					?><div class="product-field-desc"><?php //echo vmText::_($field->custom_desc) ?></div> <?php
				}
				?>
			</div>
		<?php
		//echo	$custom_title = $field->custom_title;
			
			
		} 
		
		?>
      <div class="clear"></div>
	</div>
<?php
} 

  $begin = new DateTime($start);
  $end = new DateTime($end1);
  $interval = new DateInterval('P1D'); // 1 Day
  $dateRange = new DatePeriod($begin, $interval, $end);
  //$range = [];
  foreach ($dateRange as $date) {
        $range[] = $date->format('Y-m-d');
    }
	$range[] = $end1;
	foreach($cartarray as $customfield)
    {
		if($customfield->virtuemart_custom_id ==66 )
		{
				
			$showwithmealprice = $customfield->customfield_price;
			$customfieldidfortickettypeshow	= $customfield->virtuemart_customfield_id;
				
		}
		if($customfield->virtuemart_custom_id ==67 )
		{
			$showonlyprice	= $customfield->customfield_price;
			$customfieldidfortickettypeshowwithmeal	= $customfield->virtuemart_customfield_id;	
				
		}
		if($customfield->virtuemart_custom_id ==84 )
		{
			$customfieldidforbookingdate	= $customfield->virtuemart_customfield_id;
				
				
		}
		if($customfield->virtuemart_custom_id ==83 )
		{
			$customfieldidfort3	= $customfield->virtuemart_customfield_id;
				
				
		}
	}
if (!empty($product->customfieldsSorted['normal'])) {




$events = $product->customfieldsSorted['normal'];

//get the event show with meal and without meal
foreach($events as $event)
{

$product_id  = $field->virtuemart_product_id;
$findstring  = $product_id.'(showonlyprice)';
$findstring2 = $product_id.'(showwithmealprice)';

if (strpos($event->custom_title, $findstring) !== false) {
    $eventsarray[$event->custom_value][showonly][text]= $event->customfield_value;
	$eventsarray[$event->custom_value][showonly][price]= $event->customfield_price;
	$eventsarray[$event->custom_value][showonly][date] = $event->custom_value;
}
if (strpos($event->custom_title, $findstring2) !== false) {
    $eventsarray[$event->custom_value][showwithmealprice][text]= $event->customfield_value;
    $eventsarray[$event->custom_value][showwithmealprice][price]= $event->customfield_price;
	$eventsarray[$event->custom_value][showwithmealprice][date] = $event->custom_value;
}

}

//echo "<pre>";
//print_r($eventsarray);
//echo "</pre>";


?>
  <div class="table-ticketPrices">
<h3><?php echo $performerName;?></h3>
<h4 class="dateNday"><?php echo $start_date;?>-<?php echo $end_date;?></h4><div class="sup">SUPPORT:<br><?php echo $SupporterName; ?></div>
 
				<table>
                      <tbody><tr>
                        <td colspan="3"></br><h4> TICKET PRICES 
						
						</h4></td>
                      </tr>
                      <tr>
                        <td>DATE</td>
                        <td class="text-center">SHOW TICKET</td>
                        <td class="text-center">DINNER &amp; SHOW</td>
                      </tr>
					  <?php foreach($eventsarray as $neweventdate){ ?>
                      <tr>
					 
					  
					  <td>
					  <?php 
					  $datearray = explode('/',$neweventdate['showonly']['date']);
					  $newevent_date =$datearray[2]."-".$datearray[1]."-".$datearray[0];
	                  echo  date('D j M',strtotime( $newevent_date));
					  ?></td>
					   <td align="center"><?php if($neweventdate['showonly']['price'] !=''){echo "$".number_format(($neweventdate['showonly']['price']+ $product->allPrices[0]['product_price']), 2, '.', '');} else { echo "N/A";}?></td>
					  
					  <td align="center"><?php if($neweventdate['showwithmealprice']['price'] !=''){echo "$".number_format(($neweventdate['showwithmealprice']['price']+ $product->allPrices[0]['product_price']), 2, '.', '');}else { echo "$".number_format(($neweventdate['showwithmealprice']['price']+ $product->allPrices[0]['product_price']), 2, '.', '');} ?></td>
					  </tr>
					    
					  <?php }   
					  
					  ?>
					  </tbody></table>
					  
                   </div>  
<?php } ?>
<script type="text/javascript">
function change(el){
 document.getElementById("customProductData_<?php echo $product->virtuemart_product_id."_".$customfieldidforbookingdate;?>").value =el.value;
	
}

function checkInp()
{
	
   var showonlyqty = document.getElementById("Ticket_Type_qty_field").value;
   var showmealqty = document.getElementById("Ticket_Type_2_qty_field").value;
   var bookingday  = document.getElementById("customProductData_<?php echo $product->virtuemart_product_id."_".$customfieldidforbookingdate;?>").value;
  
  
   var showonlyids = jQuery('#'+bookingday+'showonly').val();
  
   var showonlyidsres = showonlyids.split("|"); 
   var show_custom_id = showonlyidsres[0];
   var show_customfield_id = showonlyidsres[1];
   var show_date = showonlyidsres[2];
   
   
	 
   
   var showwithmealids =jQuery('#'+bookingday+'showwithmealprice').val();
   var mealres = showwithmealids.split("|"); 
   var meal_custom_id = mealres[0];
   var meal_customfield_id = mealres[1];
   var meal_date = mealres[2];
   
  if(showonlyqty =='' && showmealqty =='')
  {
	  alert('Please enter ticket quantity');
  } 
  else if(bookingday =='')  
  {
	  alert('Please select event date');
  }	  
  else
  {
	    
	  
	  
	  if( showonlyqty !='' && showmealqty =='')
	  {
		   if (isNaN(showonlyqty)) 
			{
				alert("Must input numbers");
    
			}
			else
			{
				 
				
				var producttype ="customProductData[<?php echo $product->virtuemart_product_id?>]["+showonlyidsres[0]+"]="+show_customfield_id+"&quantity[]="+showonlyqty+"&customProductData[<?php echo $product->virtuemart_product_id?>][84][<?php echo $customfieldidforbookingdate; ?>]="+showonlyidsres[2];
			}	
		    
	  }
      if( showmealqty !='' && showonlyqty=='')
	  {
		   if (isNaN(showmealqty)) 
			{
				alert("Must input numbers");
    
			}
			else
			{
				
    var producttype ="customProductData[<?php echo $product->virtuemart_product_id?>]["+meal_custom_id+"]="+meal_customfield_id+"&quantity[]="+showmealqty+"&customProductData[<?php echo $product->virtuemart_product_id?>][84][<?php echo $customfieldidforbookingdate; ?>]="+meal_date;
				
			}	
		  
	  }
	  if( showmealqty !='' && showonlyqty !='')
	  {
		 if (isNaN(showmealqty) || isNaN(showonlyqty)) 
			{
				alert("Must input numbers");
    
			}
			else
			{
				
   var producttypes =["customProductData[<?php echo $product->virtuemart_product_id?>]["+show_custom_id+"]="+show_customfield_id+"&quantity[]="+showonlyqty+"&customProductData[<?php echo $product->virtuemart_product_id?>][84][<?php echo $customfieldidforbookingdate; ?>]="+show_date,"customProductData[<?php echo $product->virtuemart_product_id?>]["+meal_custom_id+"]="+meal_customfield_id+"&quantity[]="+showmealqty+"&customProductData[<?php echo $product->virtuemart_product_id?>][84][<?php echo $customfieldidforbookingdate; ?>]="+meal_date];
				var i=2;
				
			}
			  
	  }	  
	  
	  if(i == 2)
	  {
		  var index;
		  for(index = 0; index < producttypes.length; index++) 
		  {
            var mydata ="pid=<?php echo $product->virtuemart_product_id."&virtuemart_product_id[]=".$product->virtuemart_product_id."&";?>"+producttypes[index];
	        //alert(mydata);
		    jQuery.ajax({
                type:"post",
				async: false,
				
                url:"<?php echo JURI::base();?>index.php?option=com_virtuemart&nosef=1&view=cart&task=addJS&format=json&lang=&Itemid=106",
            data:mydata,
            success:function(data){
                // alert(data);
				
            }
        }); 
		
         } 
		 window.location="<?php echo JURI::base();?>index.php?option=com_virtuemart&view=cart";
	  }
	  else
	  {
		  
		var mydata ="pid=<?php echo $product->virtuemart_product_id."&virtuemart_product_id[]=".$product->virtuemart_product_id."&";?>"+producttype;
	  
		jQuery.ajax({
                type:"post",
				async: false,
				
                url:"<?php echo JURI::base();?>index.php?option=com_virtuemart&nosef=1&view=cart&task=addJS&format=json&lang=&Itemid=106",
            data:mydata,
            success:function(data){
                // alert(data);
				window.location="<?php echo JURI::base();?>index.php?option=com_virtuemart&view=cart";
            }
        }); 
		  
		  
	  } 
	  
		

 
  }	  


}

</script>