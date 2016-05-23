<?php // no direct access
defined('_JEXEC') or die('Restricted access');
vmJsApi::jPrice();

?>
<?php 

//find the wekly event
foreach($products as $product)
{
   

   $weekly = '';
   
   $productCustomFields = $product->customfieldsSorted['normal'];
   foreach($productCustomFields as $productCustomField)
   {
	   if($productCustomField->custom_title =='Repeats' &&  $productCustomField->customfield_value =='Weekly' )
	   {
		   $weekly = 1;
	   }
       		   
	   
   }
   
   
   
    if($weekly ==1){
	  $weeklyproducts[] = $product;	
	}
	else{
	   $otherproducts[] = $product;	
	}
		
   
 } 
 
$newproducts = $weeklyproducts + $otherproducts;

$newproducts = array_slice($newproducts, 0, 50,true);

foreach ($newproducts as $newproduct) { 
$repeatedTrue ='';

?>

<?php
 if (!empty($newproduct->images[0]) )
 $image = $newproduct->images[0]->displayMediaThumb('class="featuredProductImage" ',false) ;
 else $image = '';
  JHTML::_('link', JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$newproduct->virtuemart_product_id.'&virtuemart_category_id='.$product->virtuemart_category_id),$image,array('title' => $product->product_name) );

 $url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$newproduct->virtuemart_product_id.'&virtuemart_category_id='.
$newproduct->virtuemart_category_id); ?>
<?php


$cartarraynoral = $newproduct->customfieldsSorted[normal];
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
		 if($field->customfield_value=='Weekly' )
		 {
			 
			 $repeatedTrue = '1';
			 
		 }
			 
		 
   }
   
       if($end  >= $current ){
	   $valid= 1;  }
?>
<?php if( $valid == 1 ){?>
<li>
    <div class="magBox">
		<a href="<?php echo $url;?>">
        <img width="152" height="200" alt="" src="<?php echo $newproduct->file_url; ?>"></a>
		<div class="liveTxt">
		<span>
		<?php if($repeatedTrue==1){
	            echo date('l',strtotime($start_date));
			  }
	          else{
	            echo  $start_date."-".$end_date;
			  } 
	   ?>
		</span>
         </div>
		</div>
                        <div class="magTicketBox">
						<a href="<?php echo $url;?>">
						<img alt="Get Tickets" src="/templates/thecomicslounge/images/getTickets_Btn.png">
						</a>
                        </div>
                    </li>	                
<?php } ?>

	<?php } ?>

