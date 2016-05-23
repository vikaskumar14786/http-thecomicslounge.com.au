/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */


(function(){
	var fixButtons = function(parent) {
		var els = JCalPro.getElements(parent, '.jcalfields_dir'), i = 0;
		JCalPro.each(els, function (el, idx) {
			JCalPro.removeAttribute(el, 'disabled');
			if (0 == i || (els.length - 1) == i) {
				JCalPro.setAttribute(el, 'disabled', 'disabled');
			}
			i++;
		});
	};
	var moveF = function(ev, dir) {
		try {
			// parent block, target sibling
			var p = JCalPro.el(ev.target.parentNode.parentNode), t;
			switch (dir) {
				case 'before':
					t = JCalPro.getPrevious(p, '.jcalfieldsavailableitem');
					break;
				case 'after':
					t = JCalPro.getNext(p, '.jcalfieldsavailableitem');
					break;
				default:
					throw Joomla.JText._('COM_JCALPRO_JCALFIELD_ERROR_BAD_DIRECTION');
					return false;
			}
			if (t) {
				JCalPro.inject(p, t, dir);
				return true;
			}
			throw Joomla.JText._('COM_JCALPRO_JCALFIELD_ERROR_CANNOT_FIND_AVAILABLE_ITEM');
		}
		catch (err) {
			JCalPro.debug(err);
			return false;
		}
	};
	var moveUp = function(ev) {
		return moveF(ev, 'before');
	};
	var moveDown = function(ev) {
		return moveF(ev, 'after');
	};
	
	JCalPro.onLoad(function() {
		if ('undefined' == typeof JCalPro) {
			return;
		}
		// we want this to run for each instance
		var fields = JCalPro.els('.jcalfields');
		if (!fields) {
			alert(Joomla.JText._('COM_JCALPRO_JCALFORMFIELD_ERROR'));
			return;
		}
		// loop over each of our fields (there should only be one, but hey! who knows?)
		for (var i=0; i<fields.length; i++) {
			var field = fields[i];
			fixButtons(field);
			JCalPro.each(JCalPro.getElements(field, '.jcalfields_up'), function(el, idx) {
				JCalPro.onClick(el, function (ev) {
					moveUp(ev);
					fixButtons(field);
				});
			});
			JCalPro.each(JCalPro.getElements(field, '.jcalfields_down'), function(el, idx) {
				JCalPro.onClick(el, function (ev) {
					moveDown(ev);
					fixButtons(field);
				});
			});
			JCalPro.sortable(JCalPro.getElement(field, 'ul'), {
				clone: false
			,	revert: {duration: 500, transition: 'elastic:out'}
			,	opacity: 0.7
			,	constrain: false
			,	onComplete: function(e) {
					fixButtons(field);
				}
			});
		}
	});
})();
