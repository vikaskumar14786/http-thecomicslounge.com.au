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


//get the produc id 



if (!empty($product->customfieldsSorted[$position])) {
	

	?>
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
			
			//echo "<pre>";
			//print_r($field);
			//echo "</pre>";
			   
			
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
			  
			   $showwithmealprice = $field->customfield_price;
		 }
         	if($field->virtuemart_custom_id==80)
		 {
			  
			   $dinnertime = $field->customfield_value;
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
					
					echo "<pre>";
					print_r($field); 
					echo "</pre>";
					
					echo $field->display; }?></div><?php
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
  $range = [];
  foreach ($dateRange as $date) {
        $range[] = $date->format('Y-m-d');
    }
	$range[] = $end1;
	foreach($cartarray as $customfield)
    {
		if($customfield->virtuemart_custom_id ==66 )
		{
				
			$showwithmealprice = $customfield->customfield_price;
				
		}
		if($customfield->virtuemart_custom_id ==67 )
		{
			$showonlyprice	= $customfield->customfield_price;
				
				
		}
	}
if (!empty($product->customfieldsSorted['normal'])) {	
?>
  <div class="table-ticketPrices">
<h3><?php echo $performerName;?></h3>
<h4 class="dateNday"><?php echo $start_date;?>-<?php echo $end_date;?></h4><div class="sup">SUPPORT:<br><?php echo $SupporterName; ?></div>
 
				<table>
                      <tbody><tr>
                        <td colspan="3"></br><h4> TICKET PRICES </h4></td>
                      </tr>
                      <tr>
                        <td>DATE</td>
                        <td class="text-center">SHOW TICKET</td>
                        <td class="text-center">DINNER &amp; SHOW</td>
                      </tr>
					  <?php foreach($range as $dat){ ?>
                      <tr>
					  <td><?php echo  date('D j M',strtotime($dat));?></td><td align="center"> $<?php echo number_format($showonlyprice, 2, '.', '');?></td><td align="center"> $<?php echo number_format($showwithmealprice, 2, '.', '');?></td>
					  </tr>
					  <?php } ?>
					  </tbody></table>
					  
                   </div>  
<?php } ?>