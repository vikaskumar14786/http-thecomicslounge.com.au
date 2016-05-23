/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

(function(){
	JCalPro.onLoad(function() {
		var switchers = JCalPro.getElements(document.body, '.jcalformtype');
		if (switchers) {
			JCalPro.each(switchers, function(switcher, idx) {
				JCalPro.addEvent('change', switcher, function(ev) {
					var hidden, shown;
					switch (JCalPro.getValue(JCalPro.getElement(this, ':selected'))) {
						case '0':
							hidden = '.jcalformfieldformtyperegistration';
							shown  = '.jcalformfieldformtypeevent';
							break;
						case '1':
							hidden = '.jcalformfieldformtypeevent';
							shown  = '.jcalformfieldformtyperegistration';
							break;
						default:
							shown = '.jcalformfieldformtypeevent, .jcalformfieldformtyperegistration';
					}
					if (hidden) {
						JCalPro.each(JCalPro.getElements(document.body, hidden), function(el, i) {
							JCalPro.setStyle(el, 'display', 'none');
						});
					}
					if (shown) {
						JCalPro.each(JCalPro.getElements(document.body, shown), function(el, i) {
							JCalPro.setStyle(el, 'display', 'block');
						});
					}
				});
				JCalPro.fireEvent('change', switcher);
			});
		}
	});
})();
