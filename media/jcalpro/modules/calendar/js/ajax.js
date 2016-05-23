/**
 * @package		JCalPro
 * @subpackage	mod_jcalpro_calendar
Copyright (c) 2006-2012 Anything-Digital.com
 */

function mod_jcalpro_calendar_ajax(cal) {
	var id = parseInt(JCalPro.getAttribute(cal, 'id').replace(/jcalpro_calendar_/, ''), 10)
	,   buttons = JCalPro.getElements(cal, '.jcalpro_calendar_nav_button')
	,   data = window.mod_jcalpro_calendar['mod' + id]
	,   loader = JCalPro.getElement(cal, '.jcalpro_calendar_loader')
	;
	if (!buttons || 'object' != typeof data) return;
	if (loader) JCalPro.setStyle(loader, 'display', 'none');
	try {
		var tips = $$('.jcalpro_calendar_tip_' + id);
		JCalPro.each(tips, function(el, idx) {
			var title = JCalPro.getAttribute(el, 'title');
			if (title) {
				var parts = title.split('::', 2);
				el.store('tip:title', parts[0]);
				el.store('tip:text', parts[1]);
			}
		});
		if (!window.mod_jcalpro_calendar.tips) {
			window.mod_jcalpro_calendar.tips = new Tips(tips);
		}
		else {
			window.mod_jcalpro_calendar.tips.attach(tips)
		}
	}
	catch (err) {
		if (console && console.log) console.log(err);
	}
	JCalPro.each(buttons, function(button, bidx) {
		var url = false;
		if (JCalPro.hasClass(button, 'jcalpro_calendar_nav_prev')) url = data.prev;
		else if (JCalPro.hasClass(button, 'jcalpro_calendar_nav_next')) url = data.next;
		else return;
		JCalPro.onClick(button, function(ev) {
			if (loader) JCalPro.setStyle(loader, 'display', 'block');
			var req = new Request.HTML({
				url: url
			,	link: 'ignore'
			,	update: cal
			,	evalScripts: true
			,	filter: '.jcalpro_calendar>*'
			,	onSuccess: function() {
					mod_jcalpro_calendar_ajax(cal);
				}
			}).send();
		});
	});
}

(function() {
	JCalPro.onLoad(function() {
		if ('object' != typeof window.mod_jcalpro_calendar) return;
		var cals = JCalPro.els('.jcalpro_calendar');
		if (!cals) return;
		JCalPro.each(cals, function(cal, cidx) {
			mod_jcalpro_calendar_ajax(cal);
		});
	});
})();