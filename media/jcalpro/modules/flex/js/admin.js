/**
 * @package		JCalPro
 * @subpackage	mod_jcalpro_flex
Copyright (c) 2006-2012 Anything-Digital.com
 */

window.jcal_flex_sortables = JCalPro.sortable([], {
	clone: false
,	handle: 'legend'
,	revert: {duration: 500, transition: 'elastic:out'}
,	opacity: 0.7
,	constrain: true
,	onComplete: function(e) {
		JCalPro.each(JCalPro.getElements(JCalPro.getParent(JCalPro.el(e)), '.jcalflexpanel'), function(el, idx) {
			jcl_flex_panel_number(el, jcl_flex_panel_get_number(el), idx + 1);
		});
	}
});

function jcal_flex_panel_add() {
	var type     = JCalPro.getAttribute(JCalPro.id('jcalflexpanel_type_select'), 'value');
	var base     = JCalPro.id('jcalflexpanel_' + type + '_0');
	var parent   = JCalPro.getElement(JCalPro.getParent(base), '.jcalflexpanels');
	var children = JCalPro.getChildren(parent);
	var last     = children ? JCalPro.getLast(children) : false;
	// get the number from the last element
	var next;
	try {
		next = jcl_flex_panel_get_number(last) + 1;
	}
	catch (err) {
		JCalPro.debug(err);
		next = 1;
	}
	// clone & inject the new panel
	var panel = base.clone();
	JCalPro.inject(panel, parent, 'bottom');
	// force the id - for some reason we have issues with cloning in 2.5/mootools
	JCalPro.setAttribute(panel, 'id', JCalPro.getAttribute(base, 'id'));
	// reset the element
	jcl_flex_panel_number(panel, '0', next);
	// scroll to the anchor
	JCalPro.scrollTo(panel);
	// add to sortables
	JCalPro._switch(function(){
		return window.jcal_flex_sortables.addItems(panel);
	}, function(){
		return jQuery(parent).sortable().sortable('refresh');
	});
	// fix chosen selects in 3.x
	JCalPro._switch(function(){
		// do nothing in mootools
		JCalPro.debug('!chosen');
		return;
	}, function(){
		(function($){
			var container = $(panel).find('div.chzn-container'), selects = $(panel).find('select.chzn-done');
			// remove the original chosen element
			container.empty().remove();
			// reset chosen on this element
			JCalPro.each(selects, function(el, idx) {
				$(el).show().removeClass('chzn-done').chosen({
					disable_search_threshold : 10,
					allow_single_deselect : true
				});
			});
		})(jQuery);
	});
}

function jcal_flex_panel_del(what) {
	if (!confirm(Joomla.JText._('MOD_JCALPRO_FLEX_CONFIRM_PANEL_DELETE'))) return;
	// fade this panel out before destroying it
	var kill = function() {
		var panel = JCalPro.getParent(JCalPro.getParent(JCalPro.getParent(what)));
		try {
			var scrollTo = JCalPro.getPrevious(panel);
			// scroll to the previous fieldset
			if (scrollTo) {
				JCalPro.scrollTo(scrollTo);
			}
		}
		catch (err) {
			// most likely scenario - first panel deleted?
			JCalPro.debug(err);
		}
		// renumber the ones after this one
		var step = panel;
		var num  = jcl_flex_panel_get_number(step);
		
		while (step = JCalPro.getNext(step, 'div')) {
			var n = jcl_flex_panel_get_number(step);
			if (!n) break;
			jcl_flex_panel_number(step, n, num++);
		}
		// remove from sortables
		JCalPro._switch(function(){
			return window.jcal_flex_sortables.removeItems(panel);
		}, function(){
			return jQuery(panel.parent()).sortable().sortable('refresh');
		});
		// kill the fieldset
		JCalPro.destroy(panel);
	};
	try {
		var fFx = new Fx.Morph(what.getParent().getParent().getParent(), {
			onComplete: kill
		,	duration: 500
		,	transition: Fx.Transitions.Sine.easeOut
		}).start({
			opacity:0
		,	height:0
		});
	}
	catch (err) {
		kill();
	}
}

function jcl_flex_panel_number(panel, from, to) {
	try {
		var id = (JCalPro.getAttribute(panel, 'id')).replace(/[0-9]+$/, to), reg = new RegExp('\\[' + from + '\\]'), regid = new RegExp('\\_' + from + '\\_');
	}
	catch (err) {
		return false;
	}
	JCalPro.removeAttribute(panel, 'id');
	JCalPro.setAttribute(panel, 'id', id);
	JCalPro.setAttribute(JCalPro.getElement(panel, 'a[name="panel_' + from + '"]'), 'name', 'panel_' + to);
	JCalPro.setText(JCalPro.getElement(panel, 'legend span'), to);
	// fix an attribute
	var fixAttr = function(el, attr, val, regex, open, close) {
		var a = JCalPro.getAttribute(el, attr);
		if (a) {
			JCalPro.setAttribute(el, attr, a.replace(regex, open + '' + val + '' + close));
		}
	};
	// fix common attributes (name & id)
	var fixCommon = function(el, val) {
		fixAttr(el, 'name', val, reg, '[', ']');
		fixAttr(el, 'id', val, regid, '_', '_');
	};
	// selects are likely "chosen"
	JCalPro.each(JCalPro.getElements(panel, 'select'), function(el) {
		if ('function' === typeof el.chosen) {
			el.chosen('destroy');
		}
		fixCommon(el, to);
	});
	// labels have for
	JCalPro.each(JCalPro.getElements(panel, 'label'), function(el) {
		fixAttr(el, 'for', to, regid, '_', '_');
		fixAttr(el, 'id', to, regid, '_', '_');
	});
	// inputs, textareas, buttons, fieldsets
	JCalPro.each(JCalPro.getElements(panel, 'input, button, textarea, fieldset'), function(el) {
		fixCommon(el, to);
	});
	// fix radios if jquery is available
	JCalPro._switch(function(){}, function(){(function($){
		// remove active class from all these
		$(panel).find('.btn-group label.active').removeClass('active');
		// copied from core
		$(panel).find('.btn-group label:not(.active)').off('click').click(function() {
			var label = $(this);
			var input = $('#' + label.attr('for'));
			JCalPro.debug(label, input, input.prop('checked'));

			if (!input.prop('checked')) {
				label.closest('.btn-group').find('label').removeClass('active btn-success btn-danger btn-primary');
				if (input.val() == '') {
					label.addClass('active btn-primary');
				} else if (input.val() == 0) {
					label.addClass('active btn-danger');
				} else {
					label.addClass('active btn-success');
				}
				input.prop('checked', true);
			}
		});
		$(panel).find('.btn-group input[checked=checked]').each(function() {
			if ($(this).val() == '') {
				$('label[for=' + $(this).attr('id') + ']').addClass('active btn-primary');
			} else if ($(this).val() == 0) {
				$('label[for=' + $(this).attr('id') + ']').addClass('active btn-danger');
			} else {
				$('label[for=' + $(this).attr('id') + ']').addClass('active btn-success');
			}
		});
	})(jQuery);});
}

function jcl_flex_panel_get_number(panel) {
	var num = parseInt(JCalPro.getText(JCalPro.getElement(panel, 'legend span')), 10);
	return isNaN(num) ? 0 : num;
}

(function(){
	JCalPro.onLoad(function(){
		JCalPro.each(JCalPro.els('.jcalflexpanels .jcalflexpanel'), function(el) {
			JCalPro._switch(function(){
				return window.jcal_flex_sortables.addItems(el);
			}, function(){
				return jQuery(el).parent().sortable().sortable('refresh');
			});
		});
	});
})();