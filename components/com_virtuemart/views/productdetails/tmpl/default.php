<?php
/**
 *
 * Show the product details page
 *
 * @package	VirtueMart
 * @subpackage
 * @author Max Milbers, Eugen Stranz, Max Galt
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2014 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default.php 9058 2015-11-10 18:30:54Z Milbo $
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/* Let's see if we found the product */
if (empty($this->product)) {
	echo vmText::_('COM_VIRTUEMART_PRODUCT_NOT_FOUND');
	echo '<br /><br />  ' . $this->continue_link_html;
	return;
}

echo shopFunctionsF::renderVmSubLayout('askrecomjs',array('product'=>$this->product));



if(vRequest::getInt('print',false)){ ?>
<body onload="javascript:print();">
<?php } ?>

<div class="productdetails-view productdetails">

    <?php
    // Product Navigation
	
	if($this->product->virtuemart_category_id !='20'):
    if (VmConfig::get('product_navigation', 1)) {
	?>
        <div class="product-neighbours">
	    <?php
	    if (!empty($this->product->neighbours ['previous'][0])) {
		$prev_link = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->neighbours ['previous'][0] ['virtuemart_product_id'] . '&virtuemart_category_id=' . $this->product->virtuemart_category_id, FALSE);
		echo JHtml::_('link', $prev_link, $this->product->neighbours ['previous'][0]
			['product_name'], array('rel'=>'prev', 'class' => 'previous-page','data-dynamic-update' => '1'));
	    }
	    if (!empty($this->product->neighbours ['next'][0])) {
		$next_link = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->neighbours ['next'][0] ['virtuemart_product_id'] . '&virtuemart_category_id=' . $this->product->virtuemart_category_id, FALSE);
		//echo JHtml::_('link', $next_link, $this->product->neighbours ['next'][0] ['product_name'], array('rel'=>'next','class' => 'next-page','data-dynamic-update' => '1'));
	    }
	    ?>
    	<div class="clear"></div>
        </div>
    <?php } // Product Navigation END
	endif;
    ?>

	<?php // Back To Category Button
	if ($this->product->virtuemart_category_id) {
		$catURL =  JRoute::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$this->product->virtuemart_category_id, FALSE);
		$categoryName = vmText::_($this->product->category_name) ;
	} else {
		$catURL =  JRoute::_('index.php?option=com_virtuemart');
		$categoryName = vmText::_('COM_VIRTUEMART_SHOP_HOME') ;
	}
	?>
	<div class="back-to-category">
    	<a href="<?php echo $catURL ?>" class="product-details" title="<?php echo $categoryName ?>"><?php echo vmText::sprintf('COM_VIRTUEMART_CATEGORY_BACK_TO',$categoryName) ?></a>
	</div>

    <?php // Product Title   ?>
    <!--<h1 itemprop="name"><?php //echo $this->product->product_name ?></h1>-->
    <?php // Product Title END   ?>

    <?php // afterDisplayTitle Event
   // echo $this->product->event->afterDisplayTitle ?>

    <?php
    // Product Edit Link
    //echo $this->edit_link;
    // Product Edit Link END
    ?>

    <?php
    // PDF - Print - Email Icon
    if (VmConfig::get('show_emailfriend') || VmConfig::get('show_printicon') || VmConfig::get('pdf_icon')) {
	?>
        <div class="icons">
	    <?php

	    $link = 'index.php?tmpl=component&option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->virtuemart_product_id;

		//echo $this->linkIcon($link . '&format=pdf', 'COM_VIRTUEMART_PDF', 'pdf_button', 'pdf_icon', false);
	    //echo $this->linkIcon($link . '&print=1', 'COM_VIRTUEMART_PRINT', 'printButton', 'show_printicon');
		//echo $this->linkIcon($link . '&print=1', 'COM_VIRTUEMART_PRINT', 'printButton', 'show_printicon',false,true,false,'class="printModal"');
		$MailLink = 'index.php?option=com_virtuemart&view=productdetails&task=recommend&virtuemart_product_id=' . $this->product->virtuemart_product_id . '&virtuemart_category_id=' . $this->product->virtuemart_category_id . '&tmpl=component';
	   // echo $this->linkIcon($MailLink, 'COM_VIRTUEMART_EMAIL', 'emailButton', 'show_emailfriend', false,true,false,'class="recommened-to-friend"');
	    ?>
    	<div class="clear"></div>
        </div>
    <?php } // PDF - Print - Email Icon END
    ?>

    <?php
    // Product Short Description
    if (!empty($this->product->product_s_desc)) {
	?>
        <div class="product-short-description">
	    <?php
	    /** @todo Test if content plugins modify the product description */
	    //echo nl2br($this->product->product_s_desc);
	    ?>
        </div>
	<?php
    } // Product Short Description END

	//echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'ontop'));
    ?>

    <div class="vm-product-container">
	<div class="vm-product-media-container">
<?php
//echo $this->loadTemplate('images');
?>
	</div>

	<div class="vm-product-details-container">
	    <div class="spacer-buy-area">

		<?php
		// TODO in Multi-Vendor not needed at the moment and just would lead to confusion
		/* $link = JRoute::_('index2.php?option=com_virtuemart&view=virtuemart&task=vendorinfo&virtuemart_vendor_id='.$this->product->virtuemart_vendor_id);
		  $text = vmText::_('COM_VIRTUEMART_VENDOR_FORM_INFO_LBL');
		  echo '<span class="bold">'. vmText::_('COM_VIRTUEMART_PRODUCT_DETAILS_VENDOR_LBL'). '</span>'; ?><a class="modal" href="<?php echo $link ?>"><?php echo $text ?></a><br />
		 */
		?>

		<?php
		//echo shopFunctionsF::renderVmSubLayout('rating',array('showRating'=>$this->showRating,'product'=>$this->product));

		if (is_array($this->productDisplayShipments)) {
		    foreach ($this->productDisplayShipments as $productDisplayShipment) {
			echo $productDisplayShipment . '<br />';
		    }
		}
		if (is_array($this->productDisplayPayments)) {
		    foreach ($this->productDisplayPayments as $productDisplayPayment) {
			//echo $productDisplayPayment . '<br />';
		    }
		}

		//In case you are not happy using everywhere the same price display fromat, just create your own layout
		//in override /html/fields and use as first parameter the name of your file
		//echo shopFunctionsF::renderVmSubLayout('prices',array('product'=>$this->product,'currency'=>$this->currency));
		?> <div class="clear"></div><?php
		//echo shopFunctionsF::renderVmSubLayout('addtocart',array('product'=>$this->product));

		//echo shopFunctionsF::renderVmSubLayout('stockhandle',array('product'=>$this->product));

		// Ask a question about this product
		if (VmConfig::get('ask_question', 0) == 1) {
			$askquestion_url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&task=askquestion&virtuemart_product_id=' . $this->product->virtuemart_product_id . '&virtuemart_category_id=' . $this->product->virtuemart_category_id . '&tmpl=component', FALSE);
			?>
			<div class="ask-a-question">
				<a class="ask-a-question" href="<?php echo $askquestion_url ?>" rel="nofollow" ><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_ENQUIRY_LBL') ?></a>
			</div>
		<?php
		}
		?>

		<?php
		// Manufacturer of the Product
		if (VmConfig::get('show_manufacturers', 1) && !empty($this->product->virtuemart_manufacturer_id)) {
		   // echo $this->loadTemplate('manufacturer');
		}
		?>

	    </div>
	</div>
	<div class="clear"></div>


    </div>
<?php
	$count_images = count ($this->product->images);
	if ($count_images > 1) {
		//echo $this->loadTemplate('images_additional');
	}

	// event onContentBeforeDisplay
	//echo $this->product->event->beforeDisplayContent; ?>

	<?php
	//echo ($this->product->product_in_stock - $this->product->product_ordered);
	// Product Description
	if (!empty($this->product->product_desc)) {
	    ?>
        <div class="product-description">
	<?php /** @todo Test if content plugins modify the product description */ ?>
    	<!--<span class="title"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_DESC_TITLE') ?></span>-->
	<?php //echo $this->product->product_desc; ?>
        </div>
	<?php
    } // Product Description END

	//echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'normal'));

    // Product Packaging
    $product_packaging = '';
    if ($this->product->product_box) {
	?>
        <div class="product-box">
	    <?php
	        //echo vmText::_('COM_VIRTUEMART_PRODUCT_UNITS_IN_BOX') .$this->product->product_box;
	    ?>
        </div>
    <?php } // Product Packaging END ?>

    <?php 

	?>

<?php // onContentAfterDisplay event
echo $this->product->event->afterDisplayContent;

//echo $this->loadTemplate('reviews');

// Show child categories
if (VmConfig::get('showCategory', 1)) {
	echo $this->loadTemplate('showcategory');
}

$j = 'jQuery(document).ready(function($) {
	Virtuemart.product(jQuery("form.product"));

	$("form.js-recalculate").each(function(){
		if ($(this).find(".product-fields").length && !$(this).find(".no-vm-bind").length) {
			var id= $(this).find(\'input[name="virtuemart_product_id[]"]\').val();
			Virtuemart.setproducttype($(this),id);

		}
	});
});';
//vmJsApi::addJScript('recalcReady',$j);

/** GALT
 * Notice for Template Developers!
 * Templates must set a Virtuemart.container variable as it takes part in
 * dynamic content update.
 * This variable points to a topmost element that holds other content.
 */
$j = "Virtuemart.container = jQuery('.productdetails-view');
Virtuemart.containerSelector = '.productdetails-view';";

vmJsApi::addJScript('ajaxContent',$j);

if(VmConfig::get ('jdynupdate', TRUE)){
	$j = "jQuery(document).ready(function($) {
	Virtuemart.stopVmLoading();
	var msg = '';
	jQuery('a[data-dynamic-update=\"1\"]').off('click', Virtuemart.startVmLoading).on('click', {msg:msg}, Virtuemart.startVmLoading);
	jQuery('[data-dynamic-update=\"1\"]').off('change', Virtuemart.startVmLoading).on('change', {msg:msg}, Virtuemart.startVmLoading);
});";

	vmJsApi::addJScript('vmPreloader',$j);
}

echo vmJsApi::writeJS();

if ($this->product->prices['salesPrice'] > 0) {
  echo shopFunctionsF::renderVmSubLayout('snippets',array('product'=>$this->product, 'currency'=>$this->currency, 'showRating'=>$this->showRating));
}

?>


<div id="vmMainPage">

<h1 class="eventD_Head">THE BIGGEST NIGHT OF COMEDY IN MELBOURNE!</h1>
<?php
//echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'ontop'));
//echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'normal'));
//echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'onbot'));
//echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'related_products','class'=> 'product-related-products','customTitle' => true ));
///echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'related_categories','class'=> 'product-related-categories'));?>
<div class="eventD_Box">
<table border="0" style="width: 100%;">
  <tbody>
        <tr rowspan="2">
      <td valign="top" class="columnBiggestL">
	  <table cellspacing="0" cellpadding="0" border="0" class="table-ticketprices">
          <tbody><tr>
            <td class="eventImg_Mrgt imgMBott">	
<?php echo $this->loadTemplate('images'); ?>	<br>
              <br>
              </td>
            <td valign="top" class="eventImg_Txt">
			<table cellspacing="0" cellpadding="2" border="0">
                <tbody><tr>
                  <td>                
				<?php	echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'normal'));?>
					
					</td>
                </tr>
              </tbody></table>
              
             
                    </td>
                    
          </tr>
        </tbody></table>
                    	 
<?php
//echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'ontop'));
//echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'normal'));
//echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'onbot'));
//echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'related_products','class'=> 'product-related-products','customTitle' => true ));
//echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'related_categories','class'=> 'product-related-categories'));?>


        </td>
      <td valign="top" class="columnBiggestR" colspan="2"><div class="eventD_RgtBox">
          <ul>
            <li class="eventD_Head1">SELECT DATE</li>
            <li class="eventD_Head2">TICKET TYPE</li>
            <li class="eventD_Head3">QTY</li>
          </ul>
          <div class="eventD_RgtBox_Bott">
            <div class="vmCartContainerNew">
    
    
<div class="vmCartDetailsNew">
     <div class="vmCartAttributesNew">
  	 <?php echo shopFunctionsF::renderVmSubLayout('addtocart',array('product'=>$this->product));?>	
	</div>
</div>
 
    </div>
</div>          </div>
        </div>
        <div class="clear"></div>
        <br></td>
    </tr>
            <tr>
      <td valign="top" align="left">&nbsp;</td>
      <td valign="top" colspan="2"><br></td>
    </tr>
</tbody></table>
<table width="100%" cellspacing="0" cellpadding="0" class="table-social">
    <tbody><tr>
  
    <td width="56%" valign="top" colspan="3">
  
  <table width="90%" cellspacing="0" cellpadding="0" class="evt_dtl">
    <tbody><tr>
      <td valign="top" align="left" style="text-align: justify;">
	  <?php  echo $this->product->product_desc; //echo "<pre>"; //print_r($this->product);
	  
	  $fields = $this->product->customfieldsSorted['normal'];
	  foreach ($fields  as $field) {
		  
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
            <div class="eventBott_head eventBott_head2">
             DINNER FROM <?php echo $dinnertime;?> SHOW STARTS AT <?php echo $showtime;?>
            </div>
      </td>
    
      </tr>  
    
  </tbody></table>
  <p style="padding:0px;"><span style="font-style: italic;"></span></p>
    </td>
  
    
    <td><div>
        <div class="fb_eventD_RgtBox">

<div id="fb_root_second"><script type="text/javascript" src="http://connect.facebook.net/en_US/all.js" async=""></script></div>

<!--<script type="text/javascript" src="/cl/libraries/js/jquery.js"></script>
<script type="text/javascript" src="/cl/libraries/js/thickbox.js"></script>
<link type="text/css" rel="stylesheet" href="/cl/libraries/js/thickbox.css"></link>
-->
<link href="http://localhost/cl/modules/mod_event_attendees_detailpage/assets/css/style.css" rel="stylesheet" type="text/css">
<script type="text/javascript">
	window.fbAsyncInit = function() {
		FB.init({appId: '', status: true, cookie: true, xfbml: true});
	};


	(function() {
	    var e = document.createElement('script');
	    e.type = 'text/javascript';
	    e.src = document.location.protocol +
		'//connect.facebook.net/en_US/all.js';
	    e.async = true;
	    document.getElementById('fb_root_second').appendChild(e);
	}());

	function toggleContent(element){
		var target = jQuery(element).attr('id');
		if(target=='friends_content'){
			FB.getLoginStatus(function(response1){
				if(response1.authResponse){
					getFriendsRSVP(response1.authResponse.userID, target);
				}
				else{
					FB.login(
						function(response5){
							if(response5.authResponse){
								FB.api('/me', function(response6) {
									getFriendsRSVP(response6.id, target);
								});
							}
						}
					);
				}
			});
		}
		else{
			jQuery(".rsvp_toggler").parent().removeClass('active');
			jQuery(".rsvp_content").hide();
			jQuery("#"+target).parent().addClass('active');
			jQuery('#'+target+'_d').show();
		}
	}
	
	function getFriendsRSVP(uid, target){
		jQuery(".rsvp_toggler").parent().removeClass('active');
		jQuery(".rsvp_content").hide();
		jQuery("#"+target).parent().addClass('active');
		jQuery('#'+target+'_d').show();

		jQuery.ajax({
			url: "http://localhost/cl/index.php?option=com_user&amp;view=rsvp&amp;layout=friends_rsvp",
			type: 'get',
			data: {'fid':uid,'event_id':2342,'rows_to_display':5},
			success: function(response){
				if(response){
					jQuery("#friends_content_d").html(response);
				}
			}
		});
	}

</script>
<div class="fb_rsvp_detail" id="rsvp_tabs">
	<div class="evt_title">
		<h3>FRIENDS ON COMICS LOUNGE</h3>
	</div>
	<div class="event-lst">
		<ul>
			<li class="active"><a onclick="toggleContent(this)" class="rsvp_toggler" id="everyone_content">Everyone</a></li>
			<li class=""><a onclick="toggleContent(this)" class="rsvp_toggler" id="friends_content">Friends</a></li>
		</ul>
		<div style="display: block" class="rsvp_content" id="everyone_content_d">
				</div>
		<div style="display:none" class="rsvp_content" id="friends_content_d"></div>
	</div>
</div>
</div>      </div></td>
  </tr>
        </tbody>
  
</table>

  <div class="eventBott_head mNone box-fbtw">
    <?php 
	JPluginHelper::importPlugin('content');
	$share = plgContentBt_socialshare::socialButtons();
	echo $share['script']; // Required
	echo $share['buttons']; // Social button
	echo $share['recommend']; // Recommendation bar
	echo $share['comment']; // facebook comment box
  ?>
  
  </div>
      </div>

<div style="text-align:center;display:none;visibility:hidden;" id="statusBox"></div><div style="text-align:left;color:#ff0000;font-size:15px" id="email_exists"></div></div>


</div>



