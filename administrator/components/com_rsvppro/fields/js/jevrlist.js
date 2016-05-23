/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrlist = {
	setvalue: function(id){
		jQuery('#pdv'+id).val( jQuery('#dv'+id).val());
	},
	changeMultiple:function(id){
		jQuery('#pdv'+id).prop('size',  jQuery('#size'+id).val());
		jQuery('#dv'+id).prop('size',  jQuery('#size'+id).val());
	},
	showNext:function(el, id, op){
		if (jQuery("#options"+id+"_t_"+op).val()!=""){
			var sib =$(el).parentNode.parentNode.getNext();
			if (sib) sib.style.display='';
		}
	},
	newOption:function(id){
		var newdone = false;
		jQuery("#options"+id + " tr").each(function(idx, el){
			$el = jQuery(el);
			if (!newdone && $el.css("display")=="none"){
				newdone = true;
				$el.css("display", "")
			}
		});
	},
	deleteOption: function(el){
		$el = jQuery(el);
		$el.parent().parent().remove();
	},
	updatePreview:function(id){
		var countel = 0;
		jQuery('#pdv'+id).empty();
		// create the new options
		jQuery("#options"+id+ " input.inputlabel").each(function(idx, el){
			$el = jQuery(el);
			if ($el.val()!="" && $el.val()!="xxx"){
				var opt = jQuery('<option>');
				opt.text (jevrlist.htmlspecialchars_decode($el.val()));
				opt.val("cal"+countel);
				jQuery('#pdv'+id).append(opt);
				//jQuery('#pdv'+id).options[countel].text = el.val();
				countel++;
			}
		});
		jQuery('#pdv'+id).trigger("chosen:updated");
		// old style version - still needed!
		jQuery('#pdv'+id).trigger("liszt:updated");
	},
	defaultOption:function(el, id, op){
		var value = jQuery("#options"+id+"_v_"+op).val();
		jQuery("#dv"+id).val( value);
		var text =jQuery("#options"+id+"_t_"+op).val();
		jQuery('#pdv'+id + " option").each(function(idx, opt){
			opt.selected = false;
			if (opt.text== text){
				opt.selected = true;
			};
		});
		jQuery('#pdv'+id).trigger("chosen:updated");
		// old style version - still needed!
		jQuery('#pdv'+id).trigger("liszt:updated");
	},
	htmlspecialchars_decode:function (text)
	{
		var stub_object = jQuery('<span>');
		stub_object.html(text);
		var ret_val = stub_object.text();
		stub_object.remove();
		return ret_val;
	}
}