<?php 
/**
** Parts of this code is written by Joomlaproffs.se Copyright (c) 2012, 2015 All Right Reserved.
** Many part of this code is from VirtueMart Team Copyright (c) 2004 - 2015. All rights reserved.
** Some parts might even be Joomla and is Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved. 
** http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
** This source is free software. This version may have been modified pursuant
** to the GNU General Public License, and as distributed it includes or
** is derivative of works licensed under the GNU General Public License or
** other free or open source software licenses.
**
** THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY 
** KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
** IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
** PARTICULAR PURPOSE.

** <author>Joomlaproffs / Virtuemart team</author>
** <email>info@joomlaproffs.se</email>
** <date>2015</date>
*/

defined('_JEXEC') or die('Restricted access');
 
$plugin=JPluginHelper::getPlugin('system','onepage_generic');
$params=new JRegistry($plugin->params);

$button_primary_class  = $params->get("button_primary","opg-button-primary");


?>
<div class="opg-width-1-1 opg-margin-bottom">
   <h3 class="opg-h3"><?php echo JText::_('COM_VIRTUEMART_CART_TITLE'); ?></h3>
</div>
<div id="allproducts" class="opg-width-1-1">
	
	 <?php
    $modules = JModuleHelper::getModules("onepage_promo_top");
	$document  = JFactory::getDocument();
	$renderer  = $document->loadRenderer('module');
	$attribs   = array();
	$attribs['style'] = 'xhtml';
	
	if(count($modules) > 0)
	{ 
	    
	    echo '<div class="opg-panel opg-panel-box opg-width-1-1 opg-margin-top">';
	 	foreach($modules as $mod)
	    {
		  echo JModuleHelper::renderModule($mod, $attribs);
	    }
	    echo '</div>';
		echo '<hr class="opg-margin" />';
	}
	?>
	<div class="opg-width-1-1" id="customerror" style="display:none;"> </div>
    <?php
		$i=1;
		foreach( $this->cart->products as $pkey =>$prow ) {
		
		if (isset($prow->step_order_level))
		    $step=$prow->step_order_level;
		else
		    $step=1;
		if($step==0)
		    $step=1;
		$vmproduct_id = $prow->virtuemart_product_id;
		$cartitemid = $prow->cart_item_id;
		$pModel = VmModel::getModel('product');
		$tmpProduct = $pModel->getProduct($vmproduct_id, true, false,true,1);
		$pModel->addImages($tmpProduct,1);
		?>
 		<div class="product opg-width-1-1 opg-margin" id="product_row_<?php echo $cartitemid; ?>">
          <div class="spacer">
		    <div class="opg-width-1-5 opg-hidden-small opg-float-left">
				<?php // Output Product Image
				if ($tmpProduct->virtuemart_media_id) { ?>
                    <div class="opg-margin-right opg-text-center ">
						<?php
						 echo JHTML::_ ( 'link', JRoute::_ ( 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $tmpProduct->virtuemart_product_id . '&virtuemart_category_id=' . $tmpProduct->virtuemart_category_id ), $tmpProduct->images[0]->displayMediaThumb( 'class="opg-thumbnail opg-thumbnail-mini" border="0"',false,'' ) ); ?>
                    </div>
		    	<?php } ?>
            </div>
         <div class="opg-width-large-4-5 opg-width-small-1-1 opg-float-left">
            <div class="top-row">
			  <div class="opg-text-large opg-text-bold opg-float-left opg-width-large-2-5 opg-width-small-1-1 opg-width-1-1">
                    <div class="spacer">
						<?php echo JHTML::link($prow->url, $prow->product_name, 'class="opg-link"') ?>
                    </div>
               </div>
			   <div class="opg-text-primary opg-text-bold opg-float-right opg-width-large-1-6 opg-width-small-3-6 opg-width-3-6 opg-text-right">
                    <div class="spacer" id="subtotal_with_tax_<?php echo $pkey; ?>">
						<?php echo $this->currencyDisplay->createPriceDiv('salesPrice','', $this->cart->pricesUnformatted[$pkey],true,false,$prow->quantity); //No quantity or you must use product_final_price ?>
                    </div>
               </div>
			       <div class="quantity opg-float-right opg-width-large-1-4 opg-width-small-3-6 opg-width-3-6 opg-text-left-small">
                    <div class="spacer" >
					 <?php
					   if (isset($prow->step_order_level))
							$step=$prow->step_order_level;
					   else
						    $step=1;
						if($step==0)
							$step=1;
						$alert=JText::sprintf ('COM_VIRTUEMART_WRONG_AMOUNT_ADDED', $step);

					   ?>
					   <script type="text/javascript">
                        function check_<?php echo $pkey?>(obj) {
                            // use the modulus operator '%' to see if there is a remainder
                            remainder=obj.value % <?php echo $step?>;
                            quantity=obj.value;
                            if (remainder  != 0) {
                                alert('<?php echo $alert?>!');
                                obj.value = quantity-remainder;
                                return false;
                            }
                            return true;
                        }
                        </script>
					         <input name="quantityv" type="hidden" value="<?php echo $step ?>" />
							 
                             <input type="text" title="<?php echo  JText::_('COM_VIRTUEMART_CART_UPDATE') ?>" class="quantity-input js-recalculate opg-form-small opg-text-center" onblur="check_<?php echo $pkey; ?>(this);" size="2" maxlength="4" value="<?php echo $prow->quantity ?>" id='quantity_<?php echo $cartitemid; ?>' name="quantityval[<?php echo $pkey; ?>]"  style="color:inherit !important;" />
									  
				            <input type="hidden" name="stock[<?php echo $pkey; ?>]" value="<?php echo $prow->product_in_stock; ?>" />  
                            <input type="hidden" name="view" value="cart" /> 
                            <input type="hidden" name="virtuemart_product_id[]" value="<?php echo $vmproduct_id;  ?>" />
                            <div class="opg-button-group">
                             <a href="javascript:void(0);" class="uk-button <?php echo $button_primary_class; ?> quantity-minus opg-button-mini"><i class="opg-icon-minus"></i></a>
							 <a href="javascript:void(0);" class="uk-button <?php echo $button_primary_class; ?> quantity-plus  opg-button-mini"><i class="opg-icon-plus"></i></a>
							 <a id="refreshbutton" data-itemid= "<?php echo $cartitemid;  ?>" href="javascript:void(0);" name="update" title="<?php echo  JText::_('COM_VIRTUEMART_CART_UPDATE') ?>" class="refreshbutton  opg-margin-small-left <?php echo $button_primary_class; ?>  opg-button-mini"><?php echo JText::_('COM_VIRTUEMART_UPDATE'); ?></a>	
                         	</div>
                    </div>
                </div>
				
      <div class="opg-text-primary opg-hidden-small opg-text-bold opg-float-right opg-width-large-1-6 opg-width-small-2-6 opg-width-2-6 opg-text-left-small">

                    <div class="spacer" >
						<?php echo $this->currencyDisplay->createPriceDiv('salesPrice','', $this->cart->pricesUnformatted[$pkey],true,false,1); //No quantity or you must use product_final_price ?>
						<?php //echo $this->currencyDisplay->createPriceDiv('basePriceVariant','', $this->cart->pricesUnformatted[$pkey],false); ?>
                    </div>
               </div>
       	<div class="clear"></div>
        </div>
		<hr class="opg-margin-remove" />
            <div class="bottom-row opg-grid">
                <div class="opg-width-large-1-3 opg-width-small-1-2 opg-width-1-2 opg-text-left-small opg-hidden-small">
                    <div class="spacer">
                       <?php if($prow->product_sku != "")
						 {
						 ?>
                        <div class="opg-text-small">
							<?php echo JText::_('COM_VIRTUEMART_CART_SKU').': '.$prow->product_sku; ?>
                        </div>
						 <?php
						 }
						 if (!empty($prow->customfields)) 
					 	 {
						   $customfiledstext = $this->customfieldsModel->CustomsFieldCartDisplay($prow);
							$customfiledstext = str_replace("<br />", "", $customfiledstext);
							echo str_replace('<span', '<span class="opg-text-small" ', $customfiledstext);
						 } ?>
                        <div class="cart-product-details">
							<?php echo JHTML::link($prow->url, JText::_('COM_VIRTUEMART_PRODUCT_DETAILS')) ?>
                        </div>

                    </div>
                </div>
                

                <div class="status opg-width-large-1-3 opg-width-small-1-2 opg-width-1-2">
                    <div class="spacer">
						<?php // Output The Tax For The Product
						$taxtAmount = $this->currencyDisplay->createPriceDiv('taxAmount','', $this->cart->pricesUnformatted[$pkey],true,false,$prow->quantity);
						if ( VmConfig::get('show_tax') && !empty($taxtAmount)) { 
						echo '<div><span class="opg-margin-small-right opg-float-left">'.JText::_('COM_VIRTUEMART_CART_SUBTOTAL_TAX_AMOUNT')." :</span>";
						?>
                            <span class="tax opg-text-left" id="subtotal_tax_amount_<?php echo $pkey; ?>"><?php $this->currencyDisplay->createPriceDiv('taxAmount','', $this->cart->pricesUnformatted[$pkey],true,false,$prow->quantity) ?></span></div>
							<?php } ?>

						<?php // Output The Discount For The Product
						$discountAmount = $this->currencyDisplay->createPriceDiv('discountAmount','', $this->cart->pricesUnformatted[$pkey],true,false,$prow->quantity);
						if(!empty($discountAmount)) {
						echo '<div><span class="opg-margin-small-right opg-float-left">'.JText::_('COM_VIRTUEMART_CART_SUBTOTAL_DISCOUNT_AMOUNT')." :</span>";
						?>
                            <span class="discount opg-float-left" id="subtotal_discount_<?php echo $pkey; ?>"><?php echo $this->currencyDisplay->createPriceDiv('discountAmount','', $this->cart->pricesUnformatted[$pkey],true,false,$prow->quantity);  //No quantity is already stored with it ?></span></div>
							<?php } ?>
                    </div>
                </div>
				<div class="status opg-width-large-1-3 opg-width-small-1-2 opg-width-1-2 opg-text-right">
                    <div class="spacer">
					        <a id="removeproduct" class="removeproduct" title="<?php echo JText::_('COM_VIRTUEMART_CART_DELETE') ?>" align="middle" href="javascript:void(0)" data-itemid="<?php echo $cartitemid; ?>" ><?php echo JText::_('COM_VIRTUEMART_CART_DELETE') ?> </a></td> 

                    </div>
                </div>
                <div class="clear"></div>
             </div>
        </div>
        <div class="clear"></div>
        <hr class="opg-margin-bottom-remove" />
   </div></div>
    <?php
			$i = 1 ? 2 : 1;
	} ?>
   </div>
   <?php
   $hidecoupondiv = "opg-hidden";
   if(VmConfig::get('coupons_enable', 0))
   {
     $hidecoupondiv = "";
   }
   
   ?>
   <div class="opg-clear"></div>
   <div class="opg-width-1-1 opg-margin-small-top <?php echo $hidecoupondiv; ?>">
	   <div class="opg-width-1-1 opg-text-center opg-panel-box">
	   			<input type="text" name="coupon_code" id="coupon_code" size="30" maxlength="50" class="" alt="<?php echo $this->coupon_text ?>" placeholder="<?php echo $this->coupon_text; ?>" value=""/>
			<span class="details-button">
				<input class="opg-button" type="button" title="<?php echo JText::_('COM_VIRTUEMART_SAVE'); ?>" value="<?php echo JText::_('COM_VIRTUEMART_SAVE'); ?>" onclick="applycoupon();"/>
		</span>
        <?php
				echo "<div id='coupon_code_txt' class='opg-width-1-2 opg-container-center'>".@$this->cart->cartData['couponCode'];
				echo @$this->cart->cartData['couponDescr'] ? (' (' . $this->cart->cartData['couponDescr'] . ')' ): '';
				echo "</div>";
				?>
		   
	  </div>
  </div>
  <?php
   $customernote = 0;
  foreach($this->cart->BTaddress["fields"] as $field) 
  {
     if($field['name']=='customer_note') 
 	 {
	   $customernote = true;
	   $singlefield = $field;
	   break;
	 }
  } 
  foreach($this->cart->STaddress["fields"] as $field) 
  {
     if($field['name']=='customer_note') 
 	 {
	   $customernote = true;
	   $singlefield = $field;
	   break;
	 }
  } 
  foreach($this->userFieldsCart["fields"] as $field) 
  {
     if($field['name']=='customer_note') 
 	 {
	   $customernote = true;
	   $singlefield = $field;
	   break;
	 }
  } 
     if($customernote) 
	 {
	 ?>
	   <div id="extracommentss" class="opg-panel opg-panel-box opg-margin-small-top" style="display:none;">
	   <h3 class="opg-panel-title"><?php echo JText::_('COM_VIRTUEMART_COMMENT_CART'); ?></h3>
		   <div class="opg-text-center">
		   <?php
			   echo str_replace("<textarea", '<textarea onblur="javascript:updatecustomernote(this);" ', $singlefield['formcode']);
		   ?>
		   </div>
	   </div>
	 <?php
	 }

    echo $this->loadTemplate('prices');
	
    $modules = JModuleHelper::getModules("onepage_promo");
	$document  = JFactory::getDocument();
	$renderer  = $document->loadRenderer('module');
	$attribs   = array();
	$attribs['style'] = 'xhtml';
	
	if(count($modules) > 0)
	{ 
	    echo '<hr class="opg-margin" />';
	    echo '<div class="opg-width-1-1 opg-margin-top opg-panel opg-panel-box ">';
	 	foreach($modules as $mod)
	    {
		  echo JModuleHelper::renderModule($mod, $attribs);
	    }
	    echo '</div>';
	}
?>