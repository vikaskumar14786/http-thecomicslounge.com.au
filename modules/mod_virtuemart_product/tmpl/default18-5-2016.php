<?php // no direct access
defined ('_JEXEC') or die('Restricted access');
// add javascript for price and cart, need even for quantity buttons, so we need it almost anywhere
vmJsApi::jPrice();


$col = 1;
$pwidth = ' width' . floor (100 / $products_per_row);
if ($products_per_row > 1) {
	$float = "floatleft";
} else {
	$float = "center";
}

?>                           

<?php if($Product_group =='featured') {
$products = array_slice($products, 0, 3); 	
	
	
	?>                
<?php foreach ($products as $product) { 


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
			 $end1 = date('Y-m-d',strtotime($field->customfield_value));
             $end_date = date('l j F',strtotime($field->customfield_value));
		 }
		   if($field->virtuemart_custom_id==85)
		 {
			 
			  $imageid = $field->customfield_value;
			 if($imageid !='')
			 { 
			
			$db = JFactory::getDbo();		
			$query = $db->getQuery(true);
			$query
			->select($db->quoteName(array('file_url')))
			->from($db->quoteName('#__virtuemart_medias'))
			->where($db->quoteName('virtuemart_media_id') .'= '.$imageid );
           $db->setQuery($query);
           $results = $db->loadObjectList();
		   $image =$results[0]->file_url; 

           }
			 
		
		 }
	   
	    if($start  >= $current ){
	   $valid= 1;  
	   
  } 
   }
   

?>				<?php if( $valid == 1 ):?>			
			<div class="slide">
							<img src="<?php echo $image;?>"  width="576" height="270" alt="Slide 1">
							<div class="caption" style="bottom:0">
							<div class="hero_TxtBott">
							<?php echo $start_date	?> To <?php echo $end_date	?> 	</div>					</div>
			</div>
	<?php endif;?>

							
	<?php }?>						
<?php }?>							
							
						
												

							
