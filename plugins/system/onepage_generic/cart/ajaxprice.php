<?php
/**
** Parts of this code is written by Joomlaproffs.se (Contact Claes Norin - info@joomlaproffs.se) Copyright (c) 2012 - 2016 All Right Reserved.
** Many part of this code is from VirtueMart Team Copyright (c) 2004 - 2016. All rights reserved.
** Some parts might even be Joomla and is Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved. 
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

** <author>Joomlaproffs.se</author>
** <email>info@joomlaproffs.se</email>
** <date>2016</date>
*/
defined('_JEXEC') or die('Restricted access');
$cart->prepareCartData();
	$i = 0;	
	foreach($cart->pricesUnformatted as $id=>$value) 
	{
		if(!is_array($value) && is_string($id)) 
		{
			continue;
		}
		$price_values["products"][$id]["subtotal_tax_amount"]=!empty($cart->pricesUnformatted[$id]["taxAmount"])?$currency->priceDisplay($cart->pricesUnformatted[$id]["taxAmount"]):"";
		$price_values["products"][$id]["subtotal_discount"]=!empty($cart->pricesUnformatted[$id]["subtotal_discount"])?$currency->priceDisplay($cart->pricesUnformatted[$id]["discountAmount"]):"";
		$price_values["products"][$id]["subtotal_with_tax"] = "";
		if (VmConfig::get('checkout_show_origprice',1) && !empty($cart->pricesUnformatted[$id]['basePriceWithTax']) && $cart->pricesUnformatted[$id]['basePriceWithTax'] != $cart->pricesUnformatted[$id]['salesPrice'] ) 
		{
			$old_baseprice =  !empty($cart->pricesUnformatted[$id]["basePriceWithTax"])?$currency->priceDisplay($cart->pricesUnformatted[$id]["basePriceWithTax"]):"";
			$price_values["products"][$id]["subtotal_with_tax"]='<span class="line-through">'.$old_baseprice.'</span><br />';
		}
		$price_values["products"][$id]["subtotal_with_tax"] .= !empty($cart->pricesUnformatted[$id]["subtotal_with_tax"])?$currency->priceDisplay($cart->pricesUnformatted[$id]["subtotal_with_tax"]):"";
		$i++;
		if(count($cart->products) == $i)
		{
		  break;
		}
		continue;
	}
	
	$priceList = array("salesPrice","salesPriceShipment","paymentTax","shipmentTax","billTaxAmount","discountAmount","salesPriceCoupon","salesPricePayment","couponTax","taxAmount","billTotal","billDiscountAmount");
	
	if(!empty($cart->couponCode)) $price_values["couponCode"]= $cart->couponCode;
	
	foreach ($priceList as $price_name) {
		if(!empty($cart->pricesUnformatted[$price_name])) $price_values[$price_name] = $currency->priceDisplay($cart->pricesUnformatted[$price_name]);
		else $price_values[$price_name] = "";
	}
	$price_values["couponDescr"] = ""; 	   
	$price_values["couponCode"] = ""; 	  
	
	if(!empty($cart->cartData["couponCode"]))
		   $price_values["couponCode"] = $cart->cartData["couponCode"];
		   
	if(!empty($cart->cartData["couponDescr"]))
		   $price_values["couponDescr"] = $cart->cartData["couponDescr"];
	
	$price_values["billTotalunformat"]= $cart->pricesUnformatted["billTotal"];
	
	if(count($this->cart->cartData['taxRulesBill']) > 0)
	{
	    $taxRulesBill = array();
		foreach($this->cart->cartData['taxRulesBill'] as $rule)
		{
		   $virtuemart_calc_id = $rule['virtuemart_calc_id'];
		   $calcname = $rule['calc_name'];
		   $taxRulesBill[$virtuemart_calc_id]["name"] = $calcname;
		   $taxRulesBill[$virtuemart_calc_id]["price"] = $this->currencyDisplay->createPriceDiv($rule['virtuemart_calc_id'].'Diff','', $this->cart->pricesUnformatted[$rule['virtuemart_calc_id'].'Diff'],true);
		}
		$price_values["taxRulesBill"] = $taxRulesBill;
	}
	if(count($this->cart->cartData['DATaxRulesBill']) > 0)
	{
	    $DATaxRulesBill = array();
		foreach($this->cart->cartData['DATaxRulesBill'] as $rule)
		{
		   $virtuemart_calc_id = $rule['virtuemart_calc_id'];
		   $calcname = $rule['calc_name'];
		   $DATaxRulesBill[$virtuemart_calc_id]["name"] = $calcname;
		   $DATaxRulesBill[$virtuemart_calc_id]["price"] = $this->currencyDisplay->createPriceDiv($rule['virtuemart_calc_id'].'Diff','', $this->cart->pricesUnformatted[$rule['virtuemart_calc_id'].'Diff'],true);
		}
		$price_values["DATaxRulesBill"] = $DATaxRulesBill;
	}
	echo json_encode($price_values);
	exit;