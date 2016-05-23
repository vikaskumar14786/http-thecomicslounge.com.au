/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */


(function(){
	JCalPro.onLoad(function() {
		// we want this to run for each instance
		// it's doubtful there will be more than one ever,
		// but it's always something to consider
		var fields = JCalPro.getElements(document.body, '.jcalformfields');
		if (!fields) {
			alert(Joomla.JText._('COM_JCALPRO_JCALFORMFIELD_ERROR'));
			return;
		}
		// loop over each of our fields (there should only be one, but hey! who knows?)
		for (var i=0; i<fields.length; i++) {
			var field = fields[i];
			// the input for this field
			var input = JCalPro.getElement(field, '.jcalformfieldsinput');
			// sortable elements
			var sortables = JCalPro.getElements(field, '.jcalformfieldssortable');
			// check our elements to ensure we have them!
			if (!sortables) {
				alert(Joomla.JText._('COM_JCALPRO_JCALFORMFIELD_NOSORTABLE'));
				return;
			}
			// add sortable to our lists
			JCalPro.sortable(sortables, {
				clone: false // using clone breaks the onComplete :(
			,	revert: {duration: 500, transition: 'elastic:out'}
			,	opacity: 0.7
			,	constrain: false
				// this event fires to ensure the actual JForm element gets populated
			,	onComplete: function(e) {
					// start our new value
					var val = [];
					// loop the found inputs & push to our new value
					JCalPro.each(JCalPro.getElements(JCalPro.getElement(field, '.jcalformfieldsassigned'), 'input'), function(el, idx) {
						if (JCalPro.isDisplayed(JCalPro.getParent(el)) && !JCalPro.contains(JCalPro.getValue(el), val)) val.push(JCalPro.getValue(el));
					});
					// reset the main input value
					JCalPro.setValue(input, val.join('|'));
				}
			});
		}
	});
})();
