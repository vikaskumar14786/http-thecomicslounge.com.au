<?php
/**
 *
 * loads the add to cart button
 *
 * @package    VirtueMart
 * @subpackage
 * @author Max Milbers, Valerie Isaksen
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2015 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @version $Id: addtocartbtn.php 8024 2014-06-12 15:08:59Z Milbo $
 */
// Check to ensure this file is included in Joomla!
//shopFunctionsF::renderVmSubLayout('addtocartbar',array('product'=>$product));
echo $product->virtuemart_product_id;

defined ('_JEXEC') or die('Restricted access');
if( $_REQUEST['productsublayout'] != 'products_horizon'){
if($viewData['orderable']) {
	echo "<br/><br/>";
	echo '<input onclick="checkInp()" type="button" name="addtocart1" class=" button eventD_Btn" value="'.vmText::_( 'BUY TICKETS' ).'" title="'.vmText::_( 'BUY TICKETS' ).'" />';
} else {
	echo '<span name="addtocart" class="addtocart-button-disabled" title="'.vmText::_( 'COM_VIRTUEMART_ADDTOCART_CHOOSE_VARIANT' ).'" >'.vmText::_( 'COM_VIRTUEMART_ADDTOCART_CHOOSE_VARIANT' ).'</span>';
}
}
else
{ 
	//echo '<input onclick="checkInp()" type="button" name="addtocart1" class=" button" value="'.vmText::_( 'APPLY VOUCHERS' ).'" title="'.vmText::_( 'APPLY VOUCHERS' ).'" />';
	
		echo '<input type="submit" name="addtocart" class=" button" value="'.vmText::_( 'APPLY VOUCHERS' ).'" title="'.vmText::_( 'APPLY VOUCHERS' ).'" />';
	
	  
}	