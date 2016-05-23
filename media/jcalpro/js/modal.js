/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

(function() {
	var modal = function() {
		JCalPro.debug('Running modal...');
		if (window.top != window.self) {
			JCalPro.debug('Inside frame');
			JCalPro.each(JCalPro.els('a'), function(el, idx){
				var target = JCalPro.getAttribute(el, 'target');
				if (target) {
					return;
				}
				var href = JCalPro.getAttribute(el, 'href');
				if (href && (href.match(/\#/) || href.match(/^javascript/))) {
					return;
				}
				JCalPro.setAttribute(el, 'target', '_top');
			});
			return;
		}
		var links = JCalPro.els('.eventtitle, .jcalpro_events_link a');
		if (!(links && links.length)) {
			JCalPro.debug('No jcal links found');
			return;
		}
		JCalPro.each(links, function(el, idx) {
			var t = JCalPro.el(el).closest('#jcl_component');
			if (t && JCalPro.hasClass(t, 'jcl_view_categories')) {
				return;
			}
			JCalPro.onClick(el, function(e){
				var url = JCalPro.getAttribute(el, 'href').toString(), orig = url;
				var size = JCalPro.getSize(window);
				size.x = Math.min(size.x * .85, 640);
				size.y = Math.min(size.y * .85, 480);
				if (!url.match(/tmpl=component/)) {
					JCalPro.setAttribute(el, 'href', url + (url.match(/\?/) ? '&' : '?') + 'tmpl=component');
				}
				SqueezeBox.fromElement(el, {
					handler: 'iframe'
				,	size: size
				});
				JCalPro.setAttribute(el, 'href', orig);
				JCalPro.stopEvent(e);
				return false;
			});
		});
	};
	JCalPro.addEvent('jcalload', window, modal);
	JCalPro.addEvent('jcalajax', window, modal);
})();
