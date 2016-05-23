/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrflatfee= {
    moneyformat: function(amount) {
        // ensure numerical input
        amount = parseFloat(amount);
        decimal = jQuery('#params_CurrencyDecimal').val();
        separator = jQuery('#params_CurrencySeparator').val();
        onLeft = jQuery('#params_CurrencyPlacement').val();
        digits = jQuery('#params_CurrencyDigits').val();
        symbol = jQuery('#params_CurrencySymbol').val();
        
        // format to the correct number of digits
        // @todo prototype needs to implement toFixed() for browsers that don't support this.
        amount = amount.toFixed(digits);

        // split into whole/partial for thousands separator
        var dollars = amount.split('.')[0];
        var cents	= amount.split('.')[1];

        // apply separator between every three digits
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(dollars)) {
            dollars = dollars.replace(rgx, '$1' + separator + '$2');
        }
	if (digits>0){
			if (onLeft)
			{
				return symbol + dollars + decimal + cents;
			}
			else
			{
				return  dollars + decimal + cents + symbol;
			}
	}
	else {
			if (onLeft)
			{
				return symbol + dollars;
			}
			else
			{
				return  dollars + decimal;
			}

	}
    },
    setvalue: function(id){
        jQuery('#pdv'+id).html( jevrflatfee.moneyformat(jQuery('#dv'+id).val()));
    }
}
