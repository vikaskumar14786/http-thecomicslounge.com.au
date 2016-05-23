/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrdeposit = {
	setvalue: function(id){
		var value = jQuery('#dv'+id).val();
		if (jQuery('#deposittype1'+id).prop("checked")){
			value  += "%";
		}
		else {
			value = jevrflatfee.moneyformat(value);
		}
		jQuery('#pdv'+id).html( value);
	}
}