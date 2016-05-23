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
$products_per_row = $viewData['products_per_row'];
$currency = $viewData['currency'];
$showRating = $viewData['showRating'];
$verticalseparator = " vertical-separator";
echo shopFunctionsF::renderVmSubLayout('askrecomjs');

$ItemidStr = '';
$Itemid = shopFunctionsF::getLastVisitedItemId();
if(!empty($Itemid)){
	$ItemidStr = '&Itemid='.$Itemid;
}

foreach ($viewData['products'] as $type => $products ) {

	$rowsHeight = shopFunctionsF::calculateProductRowsHeights($products,$currency,$products_per_row);
$products_per_row=100;
	if(!empty($type) and count($products)>0){
		$productTitle = vmText::_('COM_VIRTUEMART_'.strtoupper($type).'_PRODUCT'); ?>
<div class="<?php echo $type ?>-view">
  <h4><?php echo $productTitle ?></h4>
		<?php // Start the Output
    }

	// Calculating Products Per Row
	$cellwidth = ' width'.floor ( 100 / $products_per_row );

	$BrowseTotalProducts = count($products);

	$col = 1;
	$nb = 1;
	$row = 1;

	foreach ( $products as $product ) {
$performerName ='';
$SupporterName ='';
		// Show the horizontal seperator
		if ($col == 1 && $nb > $products_per_row) { ?>
	<div class="horizontal-separator"></div>
		<?php }

		// this is an indicator wether a row needs to be opened or not
		if ($col == 1) { ?>
	<div class="row">
		<?php }

		// Show the vertical seperator
		if ($nb == $products_per_row or $nb % $products_per_row == 0) {
			$show_vertical_separator = ' ';
		} else {
			$show_vertical_separator = $verticalseparator;
		}

    // Show Products ?>

<?php 
// get the custum fields for normal view
$fields = $product->customfieldsSorted['normal'];
foreach ($fields  as $field) {
//get the performer name

// get the supporter name 

// get the events dates


// get the supporter name
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
			

}
 ?>
	<ul class="eventBox">
            
                <li class="first">
                    <div class="eventImg">
                   <a href="<?php echo $product->link.$ItemidStr; ?>">
  						<img alt="<?php echo $SupporterName;?>" title="<?php echo $SupporterName;?>" src="<?php  echo $product->images[0]->file_url;?>">
  				</a>
                    </div>
                    <div class="eventTxt">
                    	<div class="eventTxtTop">
                        	              
						<a href="<?php echo $product->link.$ItemidStr;?>">
							<h3><?php echo $performerName;?></h3>
						</a>
                    <h4> <div style="float:left;"><?php echo $start_date;?></div><div> &nbsp;- <?php echo $end_date?> </div></h4>                        	
                    <h4>DINNER FROM <?php echo $dinnertime;?> SHOW STARTS AT <?php echo $showtime ;?>
                            </h4>
                                                    <p class="hide480">
                            </p><p><?php echo $product->product_desc; ?></p>                                                    <p></p>
                        </div>
                        <div class="eventTxtBott hide480">
						                        	<span class="fleft"><span class="bold">SUPPORT:</span> <?php echo $SupporterName;?></span>
						                        	<div class="moreDbtn">
                            	<a class="button" href="<?php echo $product->link.$ItemidStr; ?>">MORE DETAILS</a>
                            </div>
                        </div>
                    </div>
                         
                </li> 
                      
                    
            </ul>
	
	
	<?php
    $nb ++;

      // Do we need to close the current row now?
      if ($col == $products_per_row || $nb>$BrowseTotalProducts) { ?>
    <div class="clear"></div>
  </div>
      <?php
      	$col = 1;
		$row++;
    } else {
      $col ++;
    }
  }

      if(!empty($type)and count($products)>0){
        // Do we need a final closing row tag?
        //if ($col != 1) {
      ?>
    <div class="clear"></div>
  </div>
    <?php
    // }
    }
  }
