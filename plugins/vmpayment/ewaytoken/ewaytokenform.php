<?php
/**
 * Description of RapidAPI
 * @copyright Copyright (C) 2013 www.virtuemart.com.au - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * 
 *
 * @author eWAY
 */
defined('_JEXEC') or die('Restricted Access');
?>

<br /><script language="JavaScript" type="text/javascript" >
//<!--
  var submitcount = 0;
  function avoidDuplicationSubmit(){
    if (submitcount == 0) {
      // sumbit form
      submitcount++;
      return true;
    } else {
      alert("Transaction is in progress.");
      return false;
    }
  }
//-->
</script><form action="<?php echo  $gateway_url; ?>" method="POST" onsubmit="return avoidDuplicationSubmit()"><input type="hidden" name="EWAY_ACCESSCODE" value="<?php echo 
            $AccessCode; ?>" /><span class="vmpayment_cardinfo"><?php echo JText::_ ('VMPAYMENT_EWAY_COMPLETE_FORM') ;?>
		    <table border="0" cellspacing="0" cellpadding="2" width="100%">
		    <tr valign="top">
		        <td nowrap width="10%" align="right">
		        	<label for="EWAY_CARDNAME"><?php echo  JText::_ ('VMPAYMENT_EWAY_HOLDER') ?></label>
		        </td>
		        <td>
		        <input type="text" class="inputbox" id="EWAY_CARDNAME" name="EWAY_CARDNAME" autocomplete="off" value="<?php echo $result->Customer->CardName?>"/>
		    </td>
		    </tr>
		     <tr valign="top">
		        <td nowrap width="10%" align="right">
		        	<label for="EWAY_CARDNUMBER"><?php echo  JText::_ ('VMPAYMENT_EWAY_CCNUM') ?></label>
		        </td>
		        <td>
		        <input type="text" class="inputbox" id="EWAY_CARDNUMBER" name="EWAY_CARDNUMBER" autocomplete="off"  value="<?php echo $result->Customer->CardNumber?>"/>
		    </td>
		    </tr>
		    <tr valign="top">
		        <td nowrap width="10%" align="right">
		        	<label for="EWAY_CARDCVN"><?php echo  JText::_ ('VMPAYMENT_EWAY_CVV2') ?></label>
		        </td>
		        <td>
		            <input type="text" class="inputbox" id="EWAY_CARDCVN" name="EWAY_CARDCVN" maxlength="4" size="5" autocomplete="off" />
			    </td>
		    </tr>
		    <tr>
		        <td nowrap width="10%" align="right"><?php echo  JText::_ ('VMPAYMENT_EWAY_EXDATE') ?></td>
		        <td> 
