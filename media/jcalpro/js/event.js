/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

/**
 * hides or shows options for recurrence type
 * 
 * @param obj
 * @return
 */

window.jcl_map_default_zoom_level = window.jcl_map_default_zoom_level || 8;

function jclShowRecOptions(obj) {
	var opts = [
		'jcl_rec_none_options'
	,	'jcl_rec_daily_options'
	,	'jcl_rec_weekly_options'
	,	'jcl_rec_monthly_options'
	,	'jcl_rec_yearly_options'
	]
	, repeatend = JCalPro.getElements(JCalPro.id('jcl_component'), '.jcalrepeatend')
	, i = 0, elem, disp;
	for (; i<opts.length; i++) {
		elem = $(opts[i]);
		if (!elem) {
			continue;
		}
		disp = (i == JCalPro.getValue(obj) ? 'block' : 'none');
		JCalPro.setStyle(elem, 'display', disp);
	}
	JCalPro.each(repeatend, function(el, idx) {
		JCalPro.setStyle(el, 'display', (0 == JCalPro.getValue(obj) ? 'none' : ('table' == el.tagName.toLowerCase() ? 'table' : 'block')));
	});
}

/**
 * hides or shows options for registration
 * 
 * @param obj
 * @return
 */
function jclShowRegOptions(obj) {
	var opts = [
		'jcl_registration_off_options'
	,	'jcl_registration_on_options'
	]
	, i = 0, elem, disp;
	for (; i<opts.length; i++) {
		elem = $(opts[i]);
		JCalPro.debug(elem);
		if (!elem) {
			continue;
		}
		disp = (i == JCalPro.getValue(obj) ? 'block' : 'none');
		JCalPro.setStyle(elem, 'display', disp);
	}
}

/**
 * initializes fields with sub options
 * 
 * @param count
 * @param optname
 * @param togglename
 * @param callback
 * @return
 */
function jclInitializeOptions(count, optname, togglename, callback) {
	var i = 0, toggle = 'minus', elem, id, checked;
	for (; i<count; i++) {
		id = 'jform_' + optname + i;
		elem = JCalPro.id(id);
		if (!elem) {
			JCalPro.debug('No element in jclInitializeOptions for ' + i);
			return;
		}
		checked = JCalPro.getAttribute(elem, 'checked');
		if (checked) {
			callback(elem);
			if (0 > i) {
				toggle = 'plus';
			}
			// this only works on frontend
			try {
				JCalPro.fireEvent('click', JCalPro.getElement(JCalPro.id('jcl_component'), '.jcal_' + togglename + '_' + toggle));
			}
			catch (err) {
				JCalPro.debug(err);
			}
			return;
		}
	}
}

/**
 * initializes the form toggles
 * 
 * @return
 */
function jclInitializeToggle(bType) {
	var buttons = JCalPro.getElements(JCalPro.id('jcl_component'), '.jcal_' + bType + '_button');
	if (!buttons) {
		JCalPro.debug('No buttons found for type ' + bType);
		return;
	}
	JCalPro.each(buttons, function(el, idx) {
		JCalPro.onClick(el, function(ev) {
			if (ev) {
				JCalPro.stopEvent(ev);
			}
			var opening, closing;
			if (JCalPro.hasClass(el, 'jcal_' + bType + '_plus')) {
				opening = 'close';
				closing = 'open';
			}
			else if (JCalPro.hasClass(el, 'jcal_' + bType + '_minus')) {
				opening = 'open';
				closing = 'close';
			}
			else {
				JCalPro.debug('Not a toggle!', el);
				return;
			}
			JCalPro.debug('Opening #' + bType + '_' + opening);
			JCalPro.debug('Closing #' + bType + '_' + closing);
			JCalPro.setStyle(JCalPro.id(bType + '_' + opening), 'display', 'block');
			JCalPro.setStyle(JCalPro.id(bType + '_' + closing), 'display', 'none');
		});
	});
}

/**
 * initializes the day select
 * 
 * @deprecated
 */
function jclInitializeDaySelect() {
	return;
}

/**
 * toggles recurrence
 * 
 * @param t
 */
function jclToggleRegEnd(t) {
	var d = JCalPro.id('jformregistration_end_date_arrayday');
	if (d) {
		var li = JCalPro.getParent(d);
		JCalPro.each(JCalPro.getElements(li, 'select'), function(el, idx) {
			if (t) {
				JCalPro.removeAttribute(el, 'disabled');
			}
			else {
				JCalPro.setAttribute(el, 'disabled', 'disabled');
			}
			// special fix for Chosen plugin in 3.x+
			if (JCalPro.useJQuery && JCalPro.hasClass(el, 'chzn-done')) {
				jQuery(el).trigger("liszt:updated");
			}
		});
	}
}

/**
 * initializes registration end
 */
function jclInitializeRegEnd() {
	var fieldset = JCalPro.id('jform_registration_until_event');
	if (fieldset.length) {
		jclToggleRegEnd(0 == parseInt(JCalPro.getValue(JCalPro.getElement(fieldset, ':checked')), 10));
	}
}

/**
 * adds listeners to various form elements so that if they gain focus,
 * the corresponding radio options get selected
 * 
 * @return
 */
function jclInitializeRadioFocus() {
	// duration
	JCalPro.each(['jform_end_days', 'jform_end_hours', 'jform_end_minutes'], function(el, idx) {
		el = JCalPro.id(el);
		if (!el) {
			return;
		}
		JCalPro.addEvent('focus', el, function(ev) {
			JCalPro.setAttribute(JCalPro.id('jform_duration_type1'), 'checked', 'checked');
		});
	});
	// month/year repeat
	JCalPro.each(['monthly', 'yearly'], function(el, idx) {
		var dayNum = JCalPro.id('jform_rec_' + el + '_day_number');
		if (!dayNum) {
			return;
		}
		JCalPro.addEvent('focus', dayNum, function(ev) {
			JCalPro.setValue(JCalPro.id('jform_rec_' + el + '_type1'), 'checked', 'checked');
		});
		JCalPro.each(['order', 'type'], function(sel, sidx) {
			JCalPro.addEvent('focus', JCalPro.id('jform_rec_' + el + '_day_' + sel), function (ev) {
				JCalPro.setValue(JCalPro.id('jform_rec_' + el + '_type2'), 'checked', 'checked');
			});
		});
	});
	// end times
	try {
		JCalPro.addEvent('focus', JCalPro.id('jform_recur_end_count'), function(el, idx) {
			JCalPro.setValue(JCalPro.id('jform_recur_end_type1'), 'checked', 'checked');
		});
		JCalPro.addEvent('focus', JCalPro.id('jform_recur_end_until'), function(el, sidx) {
			JCalPro.setValue(JCalPro.id('jform_recur_end_type2'), 'checked', 'checked');
		});
		JCalPro.addEvent('click', JCalPro.id('jform_recur_end_until_img'), function(el, sidx) {
			JCalPro.setValue(JCalPro.id('jform_recur_end_type3'), 'checked', 'checked');
		});
	}
	catch (err) {
		// must be in a display view
	}
}

/**
 * adds listeners to the date parts of the start date field to update the recurrence fields
 * this is used primarily to enforce rules already in place during event save
 * and to prevent users from having to figure out certain elements of the recurrence
 * 
 * for example, when repeating weekly, the user has to ensure that the day of the week
 * that the start date falls on is selected - rather than have them calculate that themselves,
 * we should calculate it for them and check that box for them
 * 
 * @return
 */
function jclInitializeRecUpdateFromStartDate() {
	if ('undefined' == typeof window.jclDateTimeCheckUrl) {
		return;
	}
	var dateparts = ['jformstart_date_arrayday', 'jformstart_date_arraymonth', 'jformstart_date_arrayyear', 'jformstart_date_arrayhour', 'jformstart_date_arrayminute'];
	var days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
	JCalPro.each(dateparts, function(el, idx) {
		el = JCalPro.id(el);
		if (!el) {
			return;
		}
		JCalPro.setAttribute(el, 'idx', idx);
		JCalPro.addEvent('change', el, function(ev) {
			if (1 == JCalPro.getAttribute(el, 'idx')) {
				JCalPro.each(JCalPro.getElements(JCalPro.id('jform_rec_yearly_on_month'), 'option'), function(oel, oidx) {
					if (JCalPro.getValue(oel) == JCalPro.getValue(JCalPro.getElement(el, ':selected'))) {
						JCalPro.setAttribute(oel, 'selected', 'selected');
					}
				});
			}
			var data = {
				day     : JCalPro.getValue(JCalPro.getElement(JCalPro.id(dateparts[0]), ':selected'))
			,	month   : JCalPro.getValue(JCalPro.getElement(JCalPro.id(dateparts[1]), ':selected'))
			,	year    : JCalPro.getValue(JCalPro.getElement(JCalPro.id(dateparts[2]), ':selected'))
			,	hour    : JCalPro.getValue(JCalPro.getElement(JCalPro.id(dateparts[3]), ':selected'))
			,	minute  : JCalPro.getValue(JCalPro.getElement(JCalPro.id(dateparts[4]), ':selected'))
			,	timezone: JCalPro.getValue(JCalPro.getElement(JCalPro.id('jform_timezone'), ':selected'))
			}
			, req = new Request.JSON({
				url: window.jclDateTimeCheckUrl
			,	data: data
			,	format: 'json'
			,	onLoadstart: function() {
					JCalPro.debug('onComplete');
					window.jclDateTimeCheckActive = true;
				}
			,	onSuccess: function(responseJSON, responseText) {
					JCalPro.debug('onSuccess');
					if (!responseJSON.valid) {
						alert(responseJSON.error ? responseJSON.error : Joomla.JText._('COM_JCALPRO_INVALID_DATE'));
						Array.each(dateparts, function(pel, pidx) {
							$(pel).addClass('invalid');
						});
						delete window.jclDateTimeCheckActive;
						return;
					}
					Array.each(dateparts, function(pel, pidx) {
						$(pel).removeClass('invalid');
					});
					try {
						$('jform_rec_weekly_on_' + days[responseJSON.weekday]).checked = 'checked';
					}
					catch (err) {
						$('jform_rec_weekly_on_' + days[responseJSON.weekday]).set('checked', 'checked');
					}
					delete window.jclDateTimeCheckActive;
				}
			,	onError: function(text, error) {
					JCalPro.debug('onError');
					delete window.jclDateTimeCheckActive;
					alert(error);
				}
			,	onComplete: function() {
					JCalPro.debug('onComplete');
					delete window.jclDateTimeCheckActive;
			}
			,	onCancel: function() {
					JCalPro.debug('onCancel');
					delete window.jclDateTimeCheckActive;
			}
			,	onException: function() {
					JCalPro.debug('onException');
					delete window.jclDateTimeCheckActive;
				}
			,	onTimeout: function() {
					JCalPro.debug('onTimeout');
					delete window.jclDateTimeCheckActive;
				}
			}).send();
		});
	});
}

/**
 * Initializes a change event on privacy selection to toggle published status
 * 
 * @return
 */
function jclInitializePrivacy() {
	var priv = JCalPro.id('jform_private'), publish = JCalPro.id('jformpublished'), approve = JCalPro.id('jform_approved'), reg = JCalPro.id('jcl_registration');
	if (!priv || !publish) {
		return;
	}
	JCalPro.addEvent('change', priv, function(ev) {
		if (1 == JCalPro.getValue(JCalPro.getElement(priv, ':selected'))) {
			if (0 == window.jclAcl.editState) {
				JCalPro.removeAttribute(publish, 'disabled');
			}
			if (approve) {
				JCalPro.setAttribute(approve, 'disabled', 'disabled');
			}
			if (reg) {
				JCalPro.setStyles(reg, {display: 'none'});
			}
		}
		else {
			if (0 == window.jclAcl.editState) {
				JCalPro.setAttribute(publish, 'disabled', 'disabled');
			}
			if (approve) {
				JCalPro.removeAttribute(approve, 'disabled');
			}
			if (reg) {
				JCalPro.setStyles(reg, {display: 'block'});
			}
		}
	});
	try {
		JCalPro.fireEvent('change', priv);
	}
	catch (err) {
		JCalPro.debug(err);
	}
}

/**
 * initializes a map for this event
 * 
 * @param lat
 * @param lon
 * @param url
 */
function jclEventMapInit(lat, lng, url) {
	var map = document.getElementById('jcl_event_map');
	if (map) {
		window.jcl_event_latlng = new google.maps.LatLng(lat, lng);
		var mapOptions = {
			zoom: window.jcl_map_default_zoom_level
		,	center: window.jcl_event_latlng
		,	mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		window.jcl_event_map = new google.maps.Map(map, mapOptions);
		setTimeout(function() {
			var click = false, mopts = {
				map: window.jcl_event_map
			,	position: window.jcl_event_latlng
			};
			if ('undefined' != typeof url) {
				click = true;
				mopts.url = url;
			}
			jcl_marker = new google.maps.Marker(mopts);
			if (click) {
				google.maps.event.addListener(jcl_marker, 'click', function() {
					window.parent.location.href = jcl_marker.url;
				});
			}
		}, 200);
	}
}

/**
 * handler for load event
 */
JCalPro.onLoad(function() {
	jclInitializePrivacy();
	// set recurrence buttons
	jclInitializeToggle('recurrence');
	jclInitializeToggle('registration');
	// set options
	jclInitializeOptions(5, 'recur_type', 'recurrence', jclShowRecOptions);
	// set day select to reset recurrence options
	jclInitializeDaySelect();
	// set state of registration - this may not be accessible so dump errors
	try {
		jclInitializeOptions(2, 'registration', 'registration', jclShowRegOptions);
		jclInitializeRegEnd();
	}
	catch (err) {
		JCalPro.debug(err);
	}
	// help our user with recurrence parts
	jclInitializeRecUpdateFromStartDate();
	// assist in focusing the correct inputs
	jclInitializeRadioFocus();
});
