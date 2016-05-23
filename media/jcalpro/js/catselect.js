/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

(function(){
	JCalPro.onLoad(function(){
		var d = window.parent.document, id, eid, task, catid, form, oldvalue;
		catid = d.getElementById('event-catid');
		if (catid && JCalPro.getValue(catid)) {
			JCalPro.each(JCalPro.getElements(JCalPro.id('catid'), 'option'), function(el, idx) {
				if (JCalPro.getValue(catid) == JCalPro.getValue(el)) {
					JCalPro.setAttribute(el, 'selected', 'selected');
				}
			});
		}
		JCalPro.onClick(JCalPro.id('jclCatidSelectButton'), function(ev){
			var selopt = JCalPro.getValue(JCalPro.id('catid')), d = window.parent.document;
			if (selopt) {
				id = d.getElementById('jform_canonical_id');
				task = d.getElementById('event-task');
				eid = d.getElementById('event-id');
				if (id.value != selopt) {
					oldvalue = id.value;
					id.value = selopt;
					catid.value = selopt;
					task.value = 'event.' + (eid.value ? 'edit' : 'add');
					form = d.getElementById('event-form');
					if (!form) {
						form = d.getElementById('adminForm');
					}
					try {
						form.submit();
					}
					catch (err) {
						JCalPro.debug(err);
						task.value = 'event.save';
						id.value = oldvalue;
						catid.value = oldvalue;
						alert(Joomla.JText._('COM_JCALPRO_CATSELECT_COULD_NOT_CHANGE'));
						window.parent.SqueezeBox.close();
					}
				}
				else {
					window.parent.SqueezeBox.close();
				}
			}
			else {
				alert(Joomla.JText._('COM_JCALPRO_CATSELECT_SELECT_ONE'));
			}
		});
		JCalPro.each(JCalPro.getElements(JCalPro.id('catid'), 'option'), function(el, idx) {
			JCalPro.addEvent('dblclick', el, function(ev) {
				try {
					JCalPro.fireEvent('click', JCalPro.id('jclCatidSelectButton'));
				}
				catch (err) {
					JCalPro.debug(err);
				}
			});
		});
	});
})();