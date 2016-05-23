/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

(function(){
	var resetButtons, addF, subF, upF, downF, moveF;
	// we have to have a function to reset the buttons
	// this is because mootools doesn't have a live() event handler
	resetButtons = function(elem) {
		var addButtons  = JCalPro.getElements(elem, '.jcalkeyval_add');
		var subButtons  = JCalPro.getElements(elem, '.jcalkeyval_sub');
		var upButtons   = JCalPro.getElements(elem, '.jcalkeyval_up');
		var downButtons = JCalPro.getElements(elem, '.jcalkeyval_down');
		// NOTE: the following code uses setTimeout when adding events so they work in 3.x
		if (addButtons) {
			JCalPro.each(addButtons, function(el) {
				JCalPro.removeEvent('click', el, addF);
				setTimeout(function(){JCalPro.onClick(el, addF);},5);
			});
		}
		if (subButtons) {
			JCalPro.each(subButtons, function(el) {
				JCalPro.removeEvent('click', el, subF);
				setTimeout(function(){JCalPro.onClick(el, subF);},5);
			});
		}
		if (upButtons) {
			JCalPro.each(upButtons, function(el, idx) {
				JCalPro.removeAttribute(el, 'disabled');
				JCalPro.removeEvent('click', el, upF);
				setTimeout(function(){JCalPro.onClick(el, upF);},5);
				// we have to use index 1 and not 0 as the hidden block is 0
				if (1 == idx) JCalPro.setAttribute(el, 'disabled', 'disabled');
			});
		}
		if (downButtons) {
			JCalPro.each(downButtons, function(el, idx) {
				JCalPro.removeAttribute(el, 'disabled');
				JCalPro.removeEvent('click', el, downF);
				setTimeout(function(){JCalPro.onClick(el, downF);},5);
				if (downButtons.length - 1 == idx) JCalPro.setAttribute(el, 'disabled', 'disabled');
			});
		}
	};
	addF = function(ev) {
		JCalPro.debug('addF');
		var hasEmpty = false, p, t, b, inputBlocks, range, documentFragment;
		try {
			// get the main parent block
			p = JCalPro.$(ev.target.parentNode.parentNode.parentNode);
			// the template for adding a new block
			t = JCalPro.getElement(p, '.jcalkeyval_default');
			// the main block
			b = JCalPro.getElement(p, '.jcalkeyval_stage');
			// check our inputs to see if we have empties
			// note that both key AND value must be empty!
			inputBlocks = JCalPro.getElements(b, '.jcalkeyval_inputs');
			if (inputBlocks) {
				JCalPro.each(inputBlocks, function(el) {
					if (hasEmpty) return;
					var i = JCalPro.getElements(el, 'input');
					if ('' === JCalPro.getValue(i[0]) && '' === JCalPro.getValue(i[1])) hasEmpty = true;
				});
			}
			if (hasEmpty) {
				alert(Joomla.JText._('COM_JCALPRO_JCALKEYVAL_EMPTY'));
				return false;
			}
			// create a new range to append
			range = document.createRange();
			// NOTE: cannot use jquery objects here!
			if ('undefined' != typeof t.jquery) t = t[0];
			if ('undefined' != typeof b.jquery) b = b[0];
			range.selectNode(t);
			documentFragment = range.createContextualFragment(t.innerHTML.toString());
			b.appendChild(documentFragment);
			resetButtons(p);
		} catch (err) {
			alert(err);
			return false;
		}
	};
	subF = function(ev) {
		JCalPro.debug('subF');
		var hasEmpty = false, p, t, b, inputBlocks, range, documentFragment;
		try {
			// get the main parent block
			p = JCalPro.el(ev.target.parentNode);
			if (p) {
				var c = JCalPro.getChildren(JCalPro.getParent(p));
				if (1 < c.length) {
					JCalPro.destroy(p);
				}
				else {
					alert(Joomla.JText._('COM_JCALPRO_JCALKEYVAL_EMPTY_REMOVE'));
				}
			}
		} catch (err) {
			alert(err);
			return false;
		}
	};
	moveF = function(ev, dir) {
		JCalPro.debug('moveF ' + dir);
		try {
			// parent block, target sibling
			var p = JCalPro.el(ev.target.parentNode), t;
			switch (dir) {
				case 'before':
					t = JCalPro.getPrevious(p, '.jcalkeyval_block');
					break;
				case 'after':
					t = JCalPro.getNext(p, '.jcalkeyval_block');
					break;
				default: return false;
			}
			if (t) {
				JCalPro.inject(p, t, dir);
				resetButtons(JCalPro.el(ev.target.parentNode.parentNode.parentNode));
				return true;
			}
			return false;
		}
		catch (err) {
			JCalPro.debug(err);
			return false;
		}
	};
	upF = function(ev) {
		return moveF(ev, 'before');
	};
	downF = function(ev) {
		return moveF(ev, 'after');
	};
	// everything starts here
	JCalPro.onLoad(function() {
		JCalPro.debug('onLoad keyval.js');
		// we want this to run for each instance
		var fields = JCalPro.getElements(JCalPro.$(document.body), '.jcalkeyval');
		if (!fields) {
			alert(Joomla.JText._('COM_JCALPRO_JCALKEYVAL_ERROR'));
			return;
		}
		// loop over each of our fields (there should only be one, but hey! who knows?)
		for (var i=0; i<fields.length; i++) {
			resetButtons(fields[i]);
		}
	});
})();
