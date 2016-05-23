/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrpaymentoptionlist = {
	setvalue: function(id){
		jQuery('#pdv'+id).val( jQuery('#dv'+id).val());
	},
	changeMultiple:function(id){
		jQuery('#pdv'+id).prop('size',  jQuery('#size'+id).val());
		jQuery('#dv'+id).prop('size',  jQuery('#size'+id).val());
	},
	showNext:function(el, id, op){
		jevrlist.showNext(el, id, op);
	},
	newOption:function(id){
		jevrlist.newOption( id);
	},
	deleteOption: function(el){
		jevrlist.deleteOption( el);
	},
	updatePreview:function(id){
		jevrlist.updatePreview( id);
	},
	defaultOption:function(el, id, op){
		jevrlist.defaultOption(el, id, op);
	}
}