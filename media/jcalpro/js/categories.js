/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

(function() {
	JCalPro.onLoad(function() {
		var url = 'index.php?option=com_jcalpro&task=event.catcounts', form = JCalPro.id('adminForm'), submenu = JCalPro.id('submenu'), sidebar = JCalPro.id('j-sidebar-container');
		if (!form) {
			return;
		}
		if (submenu && sidebar) {
			try {
				var p = JCalPro.getParent(JCalPro.getParent(JCalPro.getParent(submenu))), s = JCalPro.getElement(sidebar, '.sidebar-nav'), m = JCalPro.getElement(JCalPro.getParent(p), '.span10');
				JCalPro.inject(JCalPro.newElement('hr'), s, 'top');
				JCalPro.inject(submenu, s, 'top');
				JCalPro.destroy(p);
				m.removeClass('span10');
				m.addClass('span12');
			}
			catch (err) {
				JCalPro.debug(err);
			}
		}
		var th1 = JCalPro.newElement('th', {
			text: Joomla.JText._('COM_JCALPRO_TOTAL_EVENTS')
		,	width: '5%'
		});
		var th2 = JCalPro.newElement('th', {
			text: Joomla.JText._('COM_JCALPRO_UPCOMING_EVENTS')
		,	width: '5%'
		});
		var old = true;
		var categoryList = JCalPro.id('categoryList');
		var parent, colspan;
		if (categoryList) {
			old = false;
			parent = JCalPro.getNext(JCalPro.getNext(JCalPro.getNext(JCalPro.getElement(categoryList, 'th'), false), false), false);
			colspan = JCalPro.getElement(JCalPro.getElement(categoryList, 'tfoot'), 'td');
		}
		else {
			parent = JCalPro.getNext(JCalPro.getElement(JCalPro.getElement(form, '.adminlist'), 'th'), false);
			colspan = JCalPro.getElement(JCalPro.getElement(JCalPro.getElement(form, '.adminlist'), 'tfoot'), 'td');
		}
		JCalPro.inject(th1, parent, 'after');
		JCalPro.inject(th2, th1, 'after');
		JCalPro.setAttribute(colspan, 'colspan', 17);
		JCalPro.each(JCalPro.getElements(form, 'input[type=checkbox]'), function(el, idx) {
			var elid = el.id;
			if (!elid.match(/^cb[0-9]+/)) {
				return;
			}
			url += '&catids[]=' + el.value;
			var td1 = JCalPro.newElement('td', {id: 'jcal_category_total_' + el.value, align: 'center'});
			var td2 = JCalPro.newElement('td', {id: 'jcal_category_upcoming_' + el.value, align: 'center'});
			JCalPro.inject(td1, old ? JCalPro.getNext(JCalPro.getParent(el), '') : JCalPro.getNext(JCalPro.getNext(JCalPro.getParent(el), false), false), 'after');
			JCalPro.inject(td2, td1, 'after');
		});
		var r = JCalPro.request({
			url: url
		,	requestType: 'json'
		,	onSuccess: function(responseJSON, responseText) {
				if (!responseJSON || !responseJSON.categories || !responseJSON.categories.length) {
					return;
				}
				var i = 0, cid, total, upcoming;
				for (; i<responseJSON.categories.length; i++) {
					cid = responseJSON.categories[i].id;
					total = JCalPro.id('jcal_category_total_' + cid);
					upcoming = JCalPro.id('jcal_category_upcoming_' + cid);
					if (total && upcoming) {
						JCalPro.setText(total, parseInt(responseJSON.categories[i].total_events, 10));
						JCalPro.setText(upcoming, parseInt(responseJSON.categories[i].upcoming_events, 10));
						JCalPro.setStyle(JCalPro.getElement(JCalPro.getParent(total), 'td'), 'background-color', responseJSON.categories[i].color);
					}
				}
			}
		});
	});
})();