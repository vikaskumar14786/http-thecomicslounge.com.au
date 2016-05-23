<?php // no direct access
defined('_JEXEC') or die('Restricted access');
vmJsApi::jPrice();

?>
<?php 

$products = array_slice($products, 0, 11,true);

foreach ($products as $product) { ?>

<?php
 if (!empty($product->images[0]) )
 $image = $product->images[0]->displayMediaThumb('class="featuredProductImage" ',false) ;
 else $image = '';
  JHTML::_('link', JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$product->virtuemart_product_id.'&virtuemart_category_id='.$product->virtuemart_category_id),$image,array('title' => $product->product_name) );

 $url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$product->virtuemart_product_id.'&virtuemart_category_id='.
$product->virtuemart_category_id); ?>
<?php


$cartarraynoral = $product->customfieldsSorted[normal];
foreach ($cartarraynoral as $field)
   {
	   if($field->virtuemart_custom_id==75)
		 {    
	        $start = strtotime($field->customfield_value);
			 $current =  strtotime(date('Y-m-d'));
             $start_date = date('l j F',strtotime($field->customfield_value));
		 }	 
		  if($field->virtuemart_custom_id==76)
		 {
			 $end = strtotime($field->customfield_value);
			 $end1 = date('Y-m-d',strtotime($field->customfield_value));
             $end_date = date('D j M',strtotime($field->customfield_value));
		 }
   }
   
       if($end  >= $current ){
	   $valid= 1;  }
?>
<?php if( $valid == 1 ):?>
<li>
    <div class="magBox">
		<a href="<?php echo $url;?>">
        <img width="152" height="200" alt="" src="<?php echo $product->file_url; ?>"></a>
		<div class="liveTxt">
		<span><?php echo $start_date."-".$end_date;	?></span>
         </div>
		</div>
                        <div class="magTicketBox">
						<a href="<?php echo $url;?>">
						<img alt="Get Tickets" src="/templates/thecomicslounge/images/getTickets_Btn.png">
						</a>
                        </div>
                    </li>	                
	<?php endif;?>

	<?php } ?>

