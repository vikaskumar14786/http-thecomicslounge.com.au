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

	if(!empty($type) and count($products)>0){
		$productTitle = vmText::_('COM_VIRTUEMART_'.strtoupper($type).'_PRODUCT'); ?>
		<?php // Start the Output
    }

	// Calculating Products Per Row
	$cellwidth = ' width'.floor ( 100 / $products_per_row );

	$BrowseTotalProducts = count($products);

?>
<h1>VOUCHER REDEMPTION</h1>
<div class="headBott">REDEEMING VOUCHERS FOR UPCOMING COMIC'S LOUNGE EVENTS IS A QUICK AND EASY 3 STEP PROCESS!</div>

<div class="voucher_Ytxt">
			<span>1. ENTER VOUCHER CODES</span>
			<span>2. SELECT EVENT</span>
			<span>3. PROCEED TO CHECKOUT</span>
</div>
<div class="headBott shopBorderBott pBott">NOTE: YOU CAN PURCHASE ADDITIONAL TICKETS WHEN SELECTING YOUR EVENT!</div>
<ul class="pinkHeadingBox">
<li>1. enter voucher code</li>                
</ul>
<div class="voucherBox">
			<div class="voucherLftBox pNone">
	
					<form action="" name="addVouchers" id="addVouchers">
					<div id="newDiv">
				
				<div class="voucherLftTop">
				<input type="text" class="inputbox" name="voucher_code[]"  id='voucher_code_0'><img src='<?php echo JURI::root(); ?>images/delete_voc.png' alt='Delete' title='Delete' onclick='javascript:resetCurrentBox(this);' >						
				<div class="voucText"></div>
	
			    </div>
				
					</div>
			    <div class="voucherLftTop mTop">
			
				<input type="button" class="button" value="ADD VOUCHER" onclick="javascript:generateInputBox();">
				
				<input type="hidden" name="userid" id="userid" value="<?php echo $user->id?>" />
			    </div>
						</form>
			</div>              
		    </div>
		    

<ul class="pinkHeadingBox">
<li>2. SELECT EVENT</li>                
</ul>			
<ul class="voucherList">
<?php
	foreach ( $products as $product ) {
	    //echo "<pre>";
		//echo "<pre>";
		//print_r($product); 
		//echo "</pre>";
		$cartarraynoral = $product->customfieldsSorted['normal'];
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
		 
	   
	    
   }
	?>
<li>
				<a onclick="javascript:voucherBoxToggle(<?php echo $product->virtuemart_product_id;?>);" href="javascript:void(0);" class="pLft" id="pLft<?php echo $product->virtuemart_product_id;?>"> <?php echo $product->product_name;?>-<?php echo $start_date;?> 
				<span class="yCol">
								</span>
							
							
							</a>
			    <div style="display:none;" class="voucherList_Box" id="voucherList_Box<?php echo $product->virtuemart_product_id;?>">
				<div class="voucherList_InTopBox">
				    <div class="voucherLftBox">                               
					<p class="voucherMft"></p>
					<?php echo $product->product_desc;?>
					<?php echo shopFunctionsF::renderVmSubLayout('addtocart',array('product'=>$product,'rowHeights'=>$rowsHeight[$row])); 
					
					
					
					?>
				    </div>
				   
				</div>
				<div class="voucherList_InBottBox">
				    <div class="voucherLftBox voucherTxtA">
					<img src="/images/spacer.png">
				  </div>
				    <div class="voucherRgtBox">
								
									
	
				    </div>
				</div>
					</div>
			    
			</li>
   <?php
   }
   ?>
</ul>  



<script type="text/javascript">

function checkavaialablecoupon ( id )
{
	
	
	var i =0;
	vochersarray = new Array();
	var valid;
	var product_id = id;
	var div = document.getElementById('addVouchers');
jQuery(div).find('input:text')
        .each(function() {
			
            var element = jQuery(this).val();
			if(element == '')
			{
				alert('Please enter the voucher');
			}
            else
            {				
			
				var mydata ="pid=" + product_id +"&vouchername="+element;
	  
		jQuery.ajax({
                type:"post",
				async: false,
                url:"/index.php?option=com_virtuemart&view=downloads&task=checkvouchervalidity",
            data:mydata,
            success:function(data){
		  
				if(data == 0)
			    {
					alert('Voucher '+ element+' is not valid for this product');
					 
					 valid =false;
					 return false;
				}
				else
				{
					vochersarray[i] = element;
					i++;
				}	
				
               
				//window.location="/index.php?option=com_virtuemart&view=cart";
            }
        });   
		  
			
			}
			
			
			
        });
		
		var index;
		if(valid !=false)
		{	
			for	(index = 0; index < vochersarray.length; index++) {
			var str = vochersarray[index];
			
			var res = str.charAt(0);
		    var bookingday = jQuery("input[name=Booking_day"+product_id+"]").val();
			var bookindaypricevalue = jQuery("input[name=datenpricevalue"+product_id+"]").val();
			var showithmeal   = jQuery("input[name='customProductData["+product_id+"][67]'").val();
			var  showonly = jQuery("input[name='customProductData["+product_id+"][66]'").val();
			
			var showmealqty =1;
			
			if(res =='M')
			{
				var producttype ="customProductData["+product_id+"][67]="+showithmeal+"&quantity[]="+showmealqty+"&customProductData["+product_id+"][84]["+bookindaypricevalue+"]="+bookingday;
			}
			else
            {
				 var producttype ="customProductData["+product_id+"][66]="+showonly+"&quantity[]="+showmealqty+"&customProductData["+product_id+"][84]["+bookindaypricevalue+"]="+bookingday;
				
			}				
			
			
			
			
			
			
			
			
			       
			
            var mydata ="pid="+product_id+"&virtuemart_product_id[]="+product_id+"&"+producttype;
	        
		    jQuery.ajax({
                type:"post",
				async: false,
                url:"/index.php?option=com_virtuemart&nosef=1&view=cart&task=addJS&format=json&lang=&Itemid=106",
            data:mydata,
            success:function(data){
				
				
				var mydata2 ="couponcode="+str;
	       
		    jQuery.ajax({
                type:"post",
				async: false,
                url:"/index.php?&option=com_virtuemart&view=cart&vmtask=applycoupon",
            data:mydata2,
            success:function(data2){
               
				
            }
        }); 
				
               
				
            }
        }); 
		
         
			
			
			
			    
			} 

window.location="/index.php?option=com_virtuemart&view=cart";
			
	    }   
	   
}
function voucherBoxToggle(id) {
	  jQuery('#voucherList_Box'+id).slideToggle('slow');
	  return false;
	}
function generateInputBox() 
{
	var inputCount = jQuery("#newDiv").find(".voucherLftTop").length;
	if(inputCount > 4 )
	{
			alert('Only 5 vouchers can be redeemed');
			return;
	}
	  jQuery('#newDiv').append("<div class='voucherLftTop'><input type='text' class='inputbox' name='voucher_code[]'  id='vid_"+inputCount+"'><img src='<?php echo JURI::root(); ?>images/delete_voc.png' alt='Delete' title='Delete' onclick='javascript:removeCurrentBox(this);' ><div class='voucText'></div></div>");
	  return false;
}
function removeCurrentBox(obj) {
	  vid = jQuery(obj).closest('div').find('input').attr('id');
	  if(vid)
		{
			jQuery(obj).closest('div').remove(); 
		}
	  
	  return false;
	}
	function resetCurrentBox(obj) {
	  vid = jQuery(obj).closest('div').find('input').attr('id');
	  if(vid)
		{
		    jQuery(obj).closest('div').find('input').val(''); 
			jQuery(obj).closest('div').find('input').removeAttr('readonly');
			jQuery(obj).closest('div').find(".voucText").html(' ');					
			jQuery(obj).closest('div').find(".voucText").append('Reset voucher');
			
		}
	  
	  return false;
	}	
</script>
<?php   
if(!empty($type)and count($products)>0){?>

    <?php
    }
  }
