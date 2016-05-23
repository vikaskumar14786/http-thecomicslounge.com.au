/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrradio = {
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
	updatePreview:function(id, op){
		var countel = 0;
		// remove exising options
		jQuery('#pdv'+id).empty();
		// create the new options
		jQuery("#options"+id+ " input.inputlabel").each(function(idx, el){
			$el = jQuery(el);
			if ($el.val()!="" && $el.val()!="xxx"){
				var label = jQuery('<label>');
				label.text($el.val());
				var radio = jQuery('<input>', {type:'radio'});
				label.append(radio);
				jQuery('#pdv'+id).append(label);

				var rid = el.id.replace("_t_","_r_");
				rid = rid.replace("optionsfield","defaultfield");

				radio.prop('checked', jQuery("#"+rid).prop('checked'));
				//var br = new Element('br');
				//jQuery('#pdv'+id).appendChild(br);
			}
		});
	},
	defaultOption:function(el, id, op){
		jQuery('#'+id+"preview label").each(function(pidx, lab){
			jQuery("#options"+id+ " input.inputlabel").each(function(idx, el){
				$el = jQuery(el);
				$lab = jQuery(lab);
				text = $lab.text();
				if (text == $el.val()){
					var rid = el.id.replace("_t_","_r_");
					rid = rid.replace("optionsfield","defaultfield");
					var radio = $lab.find('input');
					radio.prop('checked',  jQuery('#'+rid).prop('checked'));
					var vid = el.id.replace("_t_","_v_");
					jQuery('#'+rid).value = jQuery('#'+vid).val();
				}
			});
		})
	}



}