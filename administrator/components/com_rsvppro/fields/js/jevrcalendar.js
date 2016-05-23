/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrcalendar = {
	setvalue: function(id){
		jQuery('#pdv'+id).val( jQuery('#dv'+id).val());
	},
	changeSize:function(id){
		alert(jQuery('#size'+id).val() +"  " + jQuery('#pdv'+id).prop('size'));
		jQuery('#pdv'+id).prop('size', jQuery('#size'+id).val());
		jQuery('#dv'+id).prop('size',  jQuery('#size'+id).val());
		jQuery('#pdv'+id).trigger("chosen:updated");
		jQuery('#dv'+id).trigger("chosen:updated");
		// old style version - still needed!
		jQuery('#pdv'+id).trigger("liszt:updated");
		jQuery('#dv'+id).trigger("liszt:updated");
		
	},
	changeMaxlength:function(id){
		jQuery('#pdv'+id).prop('maxlength',  jQuery('#maxlength'+id).val());
		jQuery('#dv'+id).prop('maxlength',  jQuery('#maxlength'+id).val());
	}
}