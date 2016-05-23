/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

(function() {
	JCalPro.onLoad(function() {
		if ('undefined' == typeof window.jclChildrenEvents) {
			return;
		}
		// confirm detaching child events
		JCalPro.each(window.jclChildrenEvents.list, function(id, idx) {
			var row = JCalPro.getElement(JCalPro.id('jcl_component'), '.event-row-' + id);
			if (!row) {
				return;
			}
			JCalPro.each(JCalPro.getElements(row, '.event-row-confirm a'), function(el, eidx) {
				var p = JCalPro.getParent(el), o = el.onclick;
				JCalPro.setAttribute(el, 'onclick', null);
				JCalPro.onClick(el, function(ev) {
					if (!confirm(Joomla.JText._('COM_JCALPRO_CONFIRM_DETACH'))) {
						JCalPro.stopEvent(ev);
						return false;
					}
					return o(ev);
				});
			});
		});
		// determine what toolbar buttons need tweaked
		var toolbarButtons = JCalPro.els('a.toolbar'), iconElement = 'span', iconPrefix = 'icon-32';
		if (!toolbarButtons) {
			toolbarButtons = JCalPro.getElements(JCalPro.id('toolbar'), 'button');
			iconElement    = 'i';
			iconPrefix     = 'icon-';
		}
		// start changing default actions on toolbar buttons
		JCalPro.each(toolbarButtons, function(el, idx) {
			var icon = JCalPro.getElement(el, iconElement);
			var o = el.onclick;
			if (JCalPro.hasClass(icon, iconPrefix + 'delete')) {
				JCalPro.setAttribute(el, 'onclick', null);
				JCalPro.onClick(el, function(ev) {
					var toggle = JCalPro.id('event-toggle');
					JCalPro.setAttribute(toggle, 'checked', 'checked');
					Joomla.checkAll(toggle);
					return o(ev);
				});
			}
			// handle all detach actions
			
			if (!JCalPro.hasClass(icon, iconPrefix + 'publish') && !JCalPro.hasClass(icon, iconPrefix + 'unpublish') && !JCalPro.hasClass(icon, iconPrefix + 'trash')) {
				return;
			}
			JCalPro.setAttribute(el, 'onclick', null);
			JCalPro.onClick(el, function(ev) {
				if (0 == document.adminForm.boxchecked.value) {
					return o(ev);
				}
				var children = false, checked = [];
				JCalPro.each(JCalPro.getElements(JCalPro.id('jcl_component'), '.adminlist tbody input[type=checkbox]:checked'), function(input, iidx) {
					var value = parseInt(JCalPro.getValue(input), 10);
					checked.push(value);
					if (children) {
						return;
					}
					if ('cid[]' != JCalPro.getAttribute(input, 'name')) {
						return;
					}
					if (!window.jclChildrenEvents.list.contains(value)) {
						return;
					}
					children = true;
				});
				if (children) {
					var warned = false, confirmed = false;
					JCalPro.each(window.jclChildrenEvents.data, function(data, iidx) {
						if (checked.contains(data.id) || warned) {
							return;
						}
						warned = true;
						confirmed = confirm(Joomla.JText._('COM_JCALPRO_CONFIRM_DETACH_MULTI'));
					});
					if (warned && !confirmed) {
						JCalPro.stopEvent(ev);
						return false;
					}
				}
				return o(ev);
			});
		});
	});
})();
