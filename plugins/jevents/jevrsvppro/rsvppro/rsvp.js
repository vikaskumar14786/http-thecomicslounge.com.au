var JevRsvpLanguage = {
	strings: new Object(),
	translate: function (val) {
		if (val in JevRsvpLanguage.strings) {
			return JevRsvpLanguage.strings[val];
		}
		else {
			return "?? " + val + " ??";
		}
	}
}
function showRSVP() {
	jQuery("#jevsimplereg").css("display","block");
}

function enableattendance() {
	jQuery("#jevattendance").css("display","block");

}
function disableattendance() {
	jQuery("#jevattendance").css("display","none");
	jQuery("#custom_rsvp_showattendees0").prop("checked",true);
}

function enableinvites() {
	jQuery("#jev_invites").css("display","block");
	jQuery("#jev_allinvites").css("display","block");
	jQuery("#jevmessage").css("display","block");
}
function disableinvites() {
	jQuery("#jev_invites").css("display","none");
	jQuery("#jev_allinvites").css("display","none");
	jQuery("#jevmessage").css("display","none");
}
function enablereminders() {
	jQuery("#jevreminder").css("display","block");
}
function disablereminders() {
	jQuery("#jevreminder").css("display","none");
}

function addInvitees() {
	if (window.ie6 || window.ie7)
		jQuery(".jevcol1").each(function (elindex, el) {
			$el = jQuery(el);
			$el.css("display", "");
		});
	else
		jQuery(".jevcol1").each(function (elindex, el) {
			$el = jQuery(el);
			$el.css("display", "table-cell");
		});
	jQuery("#jev_name").css("display","block");
	if (jQuery("#jev_email").length){
		jQuery("#jev_email").css("display","block");
	}

	jQuery('#jugroups').css("display","none");
	jQuery('#jsgroups').css("display","none");
	jQuery('#cbgroups').css("display","none");
}

var rsvpjsonactive = false;
var cancelSearch = true;
var ignoreSearch = false;

function showMatchingInvitees(responsedata){
	rsvpClearMatches();

	var rsvpmatches = jQuery("#rsvpmatches");
	//alert(json.timing);
	if (responsedata.length > 0) {
		rsvpClearMatches();
		var shownotes = false;
		for (var jp = 0; jp < responsedata.length; jp++) {
			// Do not add if already in list of invitees
			if (jQuery("#rsvp_inv_" + responsedata[jp]["id"]).length)
				continue;
			shownotes = true;
			
			var option = jQuery('<div>', {
				id: "rsvp_pot_" + responsedata[jp]["id"],
				'style': 'margin:0px;padding:2px;cursor:pointer;'
			});
			option.html(responsedata[jp]["name"] + " (" + responsedata[jp]["username"] + ")");
			rsvpmatches.append(option);
			
			//see http://api.jquery.com/bind/
			option.bind('mousedown', {'invitee':option} , function (event) { rsvpaddInvitee(event.data.invitee)});
		}
		if (shownotes)
			jQuery("#rsvpclicktoinvite").css('display', "block");
	}
	else {
		rsvpClearMatches();
	}
	responsedata = [];
	return responsedata;
}

var checkingUser = false;

function checkUserExists(e, elem, checkurl, client) {

	var key = 0;
	if (window.event) {
		key = e.keyCode;
	}
	else if (e.which) {
		key = e.which;
	}
	if (elem.value.length == 0 || key == 8 || key == 46) {
		return;
	}

	if (checkingUser) {
		return;
	}

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "checkEmail";
	requestObject.address = elem.value;
	requestObject.client = client;
	requestObject.ev_id = -1;

	minlength = 6;

	if (elem.value.length >= minlength || elem.value == "*") {

		//url += '?start_debug=1&debug_host=127.0.0.1&debug_port=10000&debug_stop=1';
		checkingUser = true;

		var jSonRequest = jQuery.ajax({
			type : 'POST',
			dataType : 'json',
			url : checkurl,
			data : {'json':JSON.stringify(requestObject)},
			contentType: "application/x-www-form-urlencoded; charset=utf-8",
			scriptCharset: "utf-8"
			})
		.done(function(json){
			checkingUser = false;
			if (json.error) {
				try {
					eval(json.error);
				}
				catch (e) {
				}
			}
			else {
				if (json.length > 0) {
					jQuery("#rsvpMustBeLoggedIn").css("display","block");
					if (jQuery("#jevattendsubmit").length) {
						jQuery("#jevattendsubmit").prop("disabled", true);
					}
				}
				else {
					jQuery("#rsvpMustBeLoggedIn").css("display","none");
					if (jQuery("#jevattendsubmit").length) {
						jQuery("#jevattendsubmit").prop("disabled", false);
					}
				}
			}
		})
		.fail( function( jqxhr, textStatus, error){
			checkingUser = false;
		});
	}
}

function rsvpClearMatches() {
	jQuery("#rsvpclicktoinvite").css('display',  "none");
	jQuery("#rsvpmatches").html("");
}

function updateInvitees(button) {
	/// make sure we take focus from the input box
	ignoreSearch = true;
	document.updateinvitees.submit();
}

function emailInvitees(button) {
	/// make sure we take focus from the input box
	ignoreSearch = true;
	jQuery("#rsvp_email").val( "email");
	document.updateinvitees.submit();
}

function saveInvitees(button) {
	/// make sure we take focus from the input box
	ignoreSearch = true;
	jQuery("#rsvp_email").val( "savelist");

	if (jQuery("#inviteelistname").val() == "") {
		alert("please provide a value");
	}
	else {
		jQuery("#jevrsvp_listid").val( jQuery("#inviteelistname").val());
		document.updateinvitees.submit();
	}
}

function reemailInvitees(button) {
	/// make sure we take focus from the input box
	ignoreSearch = true;
	jQuery("#rsvp_email").val( "reemail");

	document.updateinvitees.submit();
}
function resendFailed(button) {
	/// make sure we take focus from the input box
	ignoreSearch = true;
	jQuery("#rsvp_email").val( "failed");
	document.updateinvitees.submit();
}

function rsvpaddInvitee(invitee) {
	var oldid = invitee.attr('id');
	var newid = invitee.attr('id').replace("rsvp_pot", "rsvp_inv");
	var invitetable = jQuery("#invitetable").find('tbody');
	var tr = jQuery('<tr>');
	var td = jQuery('<td>');
	td.html(invitee.html());
	var input = jQuery('<input>', {
		id: newid,
		type: 'hidden',
		name: 'jevinvitee[]',
		value: newid
	});
	td.append(input);
	tr.append(td);

	td = jQuery('<td>', {
		align: 'center'
	});
	var imgpath = urlroot + 'plugins/jevents/jevrsvppro/rsvppro/assets/Trash.png';

	var img = jQuery('<img>', {
		src: imgpath,
		style: "height:16px;cursor:pointer",
	});
	//see http://api.jquery.com/bind/
	img.bind('click', {'invitee':img} , function (event) { cancelInvite(event.data.invitee)});

	td.append(img);
	tr.append(td);

	// email sent?
	td = jQuery('<td>');
	tr.append(td);

	invitetable.append(tr);
	jQuery(invitee).remove();

	jQuery("#rsvpupdateinvites").css("display","inline");
	jQuery("#rsvpemailinvites").css("display","inline");
	jQuery("#rsvpreemailinvites").css("display","inline");
	jQuery("#saveinvitees").css("display","block");
	jQuery("#invitetable").css("display","block");
}

function addEmailInvitee() {

	var emailname = jQuery("#jev_emailname").val();
	jQuery("#jev_emailname").val( "");
	emailaddress = jQuery("#jev_emailaddress").val();
	jQuery("#jev_emailaddress").val( "");

	if (emailaddress == "")
		return;

	var newid = "rsvp_inv_" + emailname + "{" + emailaddress + "}";
	var invitetable = jQuery("#invitetable tbody");
	var tr = jQuery('<tr>');
	var td = jQuery('<td>');
	td.text(emailname + "{" + emailaddress + "}");
	var input = jQuery('<input>', {
		id: newid,
		type: 'hidden',
		name: 'jevinvitee[]',
		value: newid
	});
	td.append(input);
	tr.append(td);

	td = jQuery('<td>', {
		align: 'center'
	});
	var imgpath = urlroot + 'plugins/jevents/jevrsvppro/rsvppro/assets/Trash.png';
	var img = jQuery('<img>', {
		src: imgpath,
		style: "height:16px;cursor:pointer",
	});
	//see http://api.jquery.com/bind/
	img.bind('click', {'invitee':img} , function (event) { cancelInvite(event.data.invitee)});

	td.append(img);
	tr.append(td);

	// email sent?
	td = jQuery('<td>');
	tr.append(td);

	invitetable.append(tr);

	jQuery("#rsvpupdateinvites").css("display","inline");
	jQuery("#rsvpemailinvites").css("display","inline");
	jQuery("#rsvpreemailinvites").css("display","inline");
	jQuery("#invitetable").css("display","block");
}

function cancelInvite(img) {
	tr = jQuery(img).parent().parent();
	table = tr.parent();
	if (table.prop("tagName").toUpperCase() != "TABLE") {
		table = tr.parent().parent();
	}
	tr.remove();
	if (table.find("tr").length == 1) {
		jQuery("#rsvpemailinvites").css("display","none");
		jQuery("#rsvpreemailinvites").css("display","none");
		jQuery("#rsvpsendfailed").css("display","none");
	}
	jQuery("#rsvpupdateinvites").css("display","inline");
}

function cancelAttendance(attendee) {
	jQuery("#jevattendlist_id").val( attendee);
	document.attendeelist.submit();
}

function approveAttendance(attendee) {
	jQuery("#jevattendlist_id_approve").val( attendee);
	document.attendeelist.submit();
}

function inviteAll() {
	var options = jQuery("#rsvpmatches div")
	options.each(function (itemindex, item) {
		// auto add
		rsvpaddInvitee(item);
	});
}
function inviteFriends(checkurl, client) {
	addInvitees();

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "inviteFriends";
	requestObject.ev_id = jQuery('#rsvp_evid').val();
	requestObject.client = client || "site";

	var jSonRequest = jQuery.ajax({
		type : 'POST',
		dataType : 'json',
		url : checkurl,
		data : {'json':JSON.stringify(requestObject)},
		contentType: "application/x-www-form-urlencoded; charset=utf-8",
		scriptCharset: "utf-8"
		})
	.done(function(json){
		if (!json){
			alert('could not fetch friends');
			return;
		}
		if (json.error) {
			try {
				eval(json.error);
			}
			catch (e) {
				alert('could not process error handler');
			}
		}
		else {
			var rsvpmatches = jQuery("#rsvpmatches");
			if (json.titles.length > 0) {
				rsvpClearMatches();
				var shownotes = false;
				for (var jp = 0; jp < json.titles.length; jp++) {
					// Do not add if already in list of invitees
					if (jQuery("#rsvp_inv_" + json.titles[jp]["id"]).length)
						continue;
					shownotes = true;
					var opt = jQuery('<option>', {
						id: "rsvp_pot_" + json.titles[jp]["id"]
					});
					//option.addEvent('mousedown', rsvpaddInvitee.bindWithEvent(option));
					opt.text(json.titles[jp]["name"] + " (" + json.titles[jp]["username"] + ")");
					rsvpmatches.append(opt);
					// auto add
					rsvpaddInvitee(opt);
				}
				if (shownotes)
					jQuery("#rsvpclicktoinvite").css("display","block");
			}
			else {
				rsvpClearMatches();
			}

		}
	})
	.fail( function( jqxhr, textStatus, error){
			rsvpjsonactive = false;
			rsvpClearMatches();
			alert(textStatus + ", " + error);
	});
}

function inviteJSGroup(checkurl, groupid, client) {
	if (groupid == "NONE")
		return false;
	addInvitees();

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "inviteJSGroup";
	requestObject.groupid = groupid;
	requestObject.ev_id = jQuery('#rsvp_evid').val();
	requestObject.client = client || "site";

	var jSonRequest = jQuery.ajax({
		type : 'POST',
		dataType : 'json',
		url : checkurl,
		data : {'json':JSON.stringify(requestObject)},
		contentType: "application/x-www-form-urlencoded; charset=utf-8",
		scriptCharset: "utf-8"
		})
	.done(function(json){
		if (!json){
			alert('could not fetch JomSocial Group');
			return;
		}
		if (json.error) {
			try {
				eval(json.error);
			}
			catch (e) {
				alert('could not process error handler');
			}
		}
		else {
			var rsvpmatches = jQuery("#rsvpmatches");
			if (json.titles.length > 0) {
				rsvpClearMatches();
				var shownotes = false;
				for (var jp = 0; jp < json.titles.length; jp++) {
					// Do not add if already in list of invitees
					if (jQuery("#rsvp_inv_" + json.titles[jp]["id"]).length)
						continue;
					shownotes = true;
					var opt = jQuery('<option>', {
						id: "rsvp_pot_" + json.titles[jp]["id"]
					});
					//option.addEvent('mousedown', rsvpaddInvitee.bindWithEvent(option));
					opt.text(json.titles[jp]["name"] + " (" + json.titles[jp]["username"] + ")");
					rsvpmatches.append(opt);
					// auto add
					rsvpaddInvitee(opt);
				}
				if (shownotes)
					jQuery("#rsvpclicktoinvite").css("display","block");
			}
			else {
				rsvpClearMatches();
			}

		}
	})
	.fail( function( jqxhr, textStatus, error){
			rsvpjsonactive = false;
			rsvpClearMatches();
			alert(textStatus + ", " + error);
	});
}

function inviteJoomlaGroup(checkurl, groupid, client) {
	if (groupid == "NONE")
		return false;
	addInvitees();

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "inviteJUGroup";
	requestObject.groupid = groupid;
	requestObject.ev_id = jQuery('#rsvp_evid').val();
	requestObject.client = client || "site";

	var jSonRequest = jQuery.ajax({
		type : 'POST',
		dataType : 'json',
		url : checkurl,
		data : {'json':JSON.stringify(requestObject)},
		contentType: "application/x-www-form-urlencoded; charset=utf-8",
		scriptCharset: "utf-8"
		})
	.done(function(json){
		if (!json){
			alert('could not fetch guests');
			return;
		}
		if (json.error) {
			try {
				eval(json.error);
			}
			catch (e) {
				alert('could not process error handler');
			}
		}
		else {
			var rsvpmatches = jQuery("#rsvpmatches");
			if (json.titles.length > 0) {
				rsvpClearMatches();
				var shownotes = false;
				for (var jp = 0; jp < json.titles.length; jp++) {
					// Do not add if already in list of invitees
					if (jQuery("#rsvp_inv_" + json.titles[jp]["id"]).length)
						continue;
					shownotes = true;
					var opt = jQuery('<option>', {
						id: "rsvp_pot_" + json.titles[jp]["id"]
					});
					//option.addEvent('mousedown', rsvpaddInvitee.bindWithEvent(option));
					opt.text(json.titles[jp]["name"] + " (" + json.titles[jp]["username"] + ")");
					rsvpmatches.append(opt);
					// auto add
					rsvpaddInvitee(opt);
				}
				if (shownotes)
					jQuery("#rsvpclicktoinvite").css("display", "block");
			}
			else {
				rsvpClearMatches();
			}
		}
	})
	.fail( function( jqxhr, textStatus, error){
			rsvpjsonactive = false;
			rsvpClearMatches();
			alert(textStatus + ", " + error);		
	});
}

function inviteCBGroup(url, groupid, client) {
	if (groupid == "NONE")
		return false;
	addInvitees();

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "inviteCBGroup";
	requestObject.groupid = groupid;
	requestObject.ev_id = jQuery('#rsvp_evid').val();
	requestObject.client = client || "site";

	var jSonRequest = jQuery.ajax({
		type : 'POST',
		dataType : 'json',
		url : checkurl,
		data : {'json':JSON.stringify(requestObject)},
		contentType: "application/x-www-form-urlencoded; charset=utf-8",
		scriptCharset: "utf-8"
		})
	.done(function(json){
		if (!json){
			alert('could not fetch CB Group');
			return;
		}
		if (json.error) {
			try {
				eval(json.error);
			}
			catch (e) {
				alert('could not process error handler');
			}
		}
		else {
			var rsvpmatches = jQuery("#rsvpmatches");
			if (json.titles.length > 0) {
				rsvpClearMatches();
				var shownotes = false;
				for (var jp = 0; jp < json.titles.length; jp++) {
					// Do not add if already in list of invitees
					if (jQuery("#rsvp_inv_" + json.titles[jp]["id"]).length)
						continue;
					shownotes = true;
					var opt = jQuery('<option>', {
						id: "rsvp_pot_" + json.titles[jp]["id"]
					});
					//option.addEvent('mousedown', rsvpaddInvitee.bindWithEvent(option));
					opt.text(json.titles[jp]["name"] + " (" + json.titles[jp]["username"] + ")");
					rsvpmatches.append(opt);
					// auto add
					rsvpaddInvitee(opt);
				}
				if (shownotes)
					jQuery("#rsvpclicktoinvite").css("display","block");
			}
			else {
				rsvpClearMatches();
			}

		}
	})
	.fail( function( jqxhr, textStatus, error){
			rsvpjsonactive = false;
			rsvpClearMatches();
			alert(textStatus + ", " + error);
	});

}


function inviteList(checkurl, listid, client) {
	if (listid == "NONE")
		return false;
	addInvitees();

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "inviteList";
	requestObject.listid = listid;
	requestObject.ev_id = jQuery('#rsvp_evid').val();
	requestObject.client = client || "site";

	var jSonRequest = jQuery.ajax({
		type : 'POST',
		dataType : 'json',
		url : checkurl,
		data : {'json':JSON.stringify(requestObject)},
		contentType: "application/x-www-form-urlencoded; charset=utf-8",
		scriptCharset: "utf-8"
		})
	.done(function(json){
		if (!json){
			alert('could not fetch list');
			return;
		}
		if (json.error) {
			try {
				eval(json.error);
			}
			catch (e) {
				alert('could not process error handler');
			}
		}
		else {
			var rsvpmatches = jQuery("#rsvpmatches");
			if (json.titles.length > 0) {
				rsvpClearMatches();
				var shownotes = false;
				for (var jp = 0; jp < json.titles.length; jp++) {
					// Do not add if already in list of invitees
					if (jQuery("#rsvp_inv_" + json.titles[jp]["id"]).length)
						continue;
					shownotes = true;
					if (json.titles[jp]["id"] == 0) {
						var opt = jQuery('<option>', {
							id: "rsvp_pot_" + json.titles[jp]["name"] + "(" + json.titles[jp]["username"] + ")"
						});
					}
					else {
						var opt = jQuery('<option>', {
							id: "rsvp_pot_" + json.titles[jp]["id"]
						});
					}
					opt.text(json.titles[jp]["name"] + " (" + json.titles[jp]["username"] + ")");
					rsvpmatches.append(opt);
					// auto add
					rsvpaddInvitee(opt);
				}
				if (shownotes)
					jQuery("#rsvpclicktoinvite").css("display","block");
			}
			else {
				rsvpClearMatches();
			}
		}
	})
	.fail( function( jqxhr, textStatus, error){
			rsvpjsonactive = false;
			rsvpClearMatches();
			alert(textStatus + ", " + error);
	});
}

function confirmUpdate(confirmmsg) {
	var newname = jQuery('#inviteelistname').val();
	if (newname == "")
		return false;
	// no lists in existence
	if (!jQuery('#custom_jevuser_inviteelist').length)
		return true;
	var inviteelist = jQuery('#custom_jevuser_inviteelist option');
	if (inviteelist.length <= 1)
		return true;
	var matched = false;
	inviteelist.each(function (lindex, list) {
		$list = jQuery(list);
		if ($list.text() == newname) {
			matched = true;
		}
	});
	if (matched) {
		return confirm(confirmmsg);
	}
	return true;
}
function showJevStatus() {
	jQuery("#jevstatus").css("display","block");
	jQuery("#jevstatusbutton").css("display","none");

}
function showSubmitButton() {
	jQuery("#jevattendsubmit").css("display","");
	jQuery("#rsvppro_admintable").css("display","table");
	if (jQuery("#addguest")) {
		jQuery("#addguest").css("display","block");
	}
}

function convertTime(item) {
	var regtime = jQuery("#"+item);
	var hiddenregtime = jQuery("#hidden"+item);
	var time = regtime.val();
	var pm = false;
	if (time.indexOf('PM')>0) {
		pm = true;
	}
	time = time.replace(/[^0-9\:]/g, '');
	timeArray = time.split(':');
	hour = timeArray[0] ? timeArray[0].toString() : timeArray.toString();
	minute = timeArray[1] ? timeArray[1].toString() : '';
	if (pm && hour!=12){
		hour = parseInt(hour) + 12;
	}
	if (parseInt(hour)<10){
		hour = "0"+hour;
	}
	hiddenregtime.val(hour + ":" + minute);
	checkRegDates(item);
}

function checkRegDates(item) {
	if (item == 'regopentime') {
		var reg = jQuery('#custom_rsvp_regopen');
		var regdate = jQuery('#regopen');
		var regtime = jQuery('#hiddenregopentime');
	}
	else if (item == 'regclosetime') {
		var reg = jQuery('#custom_rsvp_regclose');
		var regdate = jQuery('#regclose');
		var regtime = jQuery('#hiddenregclosetime');
	}
	else {
		var reg = jQuery('#custom_rsvp_cancelclose');
		var regdate = jQuery('#cancelclose');
		var regtime = jQuery('#hiddencancelclosetime');
	}
	var tempDate = new Date();
	tempDate = tempDate.dateFromYMD(regdate.val());

	reg.val( tempDate.getFullYear()+"-"+(tempDate.getMonth()+1)+"-"+tempDate.getDate() + " " + regtime.val());
}

function updateCancelClose(val) {
	val = jQuery("#custom_rsvp_allowcancellation1").prop("checked") || jQuery("#custom_rsvp_allowchanges1").prop("checked");
	var jevendcancel = jQuery("#jevendcancel");
	jevendcancel.css("display",  val ? "block" : "none");
}

//See https://github.com/jdewit/bootstrap-timepicker/blob/gh-pages/js/bootstrap-timepicker.js
/*!
 * Timepicker Component for Twitter Bootstrap
 *
 * Copyright 2013 Joris de Wit
 *
 * Contributors https://github.com/jdewit/bootstrap-timepicker/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
(function ($, window, document, undefined) {
	'use strict';

	// TIMEPICKER PUBLIC CLASS DEFINITION
	var Timepicker = function (element, options) {
		this.widget = '';
		this.$element = $(element);
		this.defaultTime = options.defaultTime;
		this.disableFocus = options.disableFocus;
		this.disableMousewheel = options.disableMousewheel;
		this.isOpen = options.isOpen;
		this.minuteStep = options.minuteStep;
		this.modalBackdrop = options.modalBackdrop;
		this.orientation = options.orientation;
		this.secondStep = options.secondStep;
		this.showInputs = options.showInputs;
		this.showMeridian = options.showMeridian;
		this.showSeconds = options.showSeconds;
		this.template = options.template;
		this.appendWidgetTo = options.appendWidgetTo;
		this.showWidgetOnAddonClick = options.showWidgetOnAddonClick;

		this._init();
	};

	Timepicker.prototype = {
		constructor: Timepicker,
		_init: function () {
			var self = this;

			if (this.showWidgetOnAddonClick && (this.$element.parent().hasClass('input-append') || this.$element.parent().hasClass('input-prepend'))) {
				this.$element.parent('.input-append, .input-prepend').find('.add-on').on({
					'click.timepicker': $.proxy(this.showWidget, this)
				});
				this.$element.on({
					'focus.timepicker': $.proxy(this.highlightUnit, this),
					'click.timepicker': $.proxy(this.highlightUnit, this),
					'keydown.timepicker': $.proxy(this.elementKeydown, this),
					'blur.timepicker': $.proxy(this.blurElement, this),
					'mousewheel.timepicker DOMMouseScroll.timepicker': $.proxy(this.mousewheel, this)
				});
			} else {
				if (this.template) {
					this.$element.on({
						'focus.timepicker': $.proxy(this.showWidget, this),
						'click.timepicker': $.proxy(this.showWidget, this),
						'blur.timepicker': $.proxy(this.blurElement, this),
						'mousewheel.timepicker DOMMouseScroll.timepicker': $.proxy(this.mousewheel, this)
					});
				} else {
					this.$element.on({
						'focus.timepicker': $.proxy(this.highlightUnit, this),
						'click.timepicker': $.proxy(this.highlightUnit, this),
						'keydown.timepicker': $.proxy(this.elementKeydown, this),
						'blur.timepicker': $.proxy(this.blurElement, this),
						'mousewheel.timepicker DOMMouseScroll.timepicker': $.proxy(this.mousewheel, this)
					});
				}
			}

			if (this.template !== false) {
				this.$widget = $(this.getTemplate()).on('click', $.proxy(this.widgetClick, this));
			} else {
				this.$widget = false;
			}

			if (this.showInputs && this.$widget !== false) {
				this.$widget.find('input').each(function () {
					$(this).on({
						'click.timepicker': function () {
							$(this).select();
						},
						'keydown.timepicker': $.proxy(self.widgetKeydown, self),
						'keyup.timepicker': $.proxy(self.widgetKeyup, self)
					});
				});
			}

			this.setDefaultTime(this.defaultTime);
		},
		blurElement: function () {
			this.highlightedUnit = null;
			this.updateFromElementVal();
		},
		clear: function () {
			this.hour = '';
			this.minute = '';
			this.second = '';
			this.meridian = '';

			this.$element.val('');
		},
		decrementHour: function () {
			if (this.showMeridian) {
				if (this.hour === 1) {
					this.hour = 12;
				} else if (this.hour === 12) {
					this.hour--;

					return this.toggleMeridian();
				} else if (this.hour === 0) {
					this.hour = 11;

					return this.toggleMeridian();
				} else {
					this.hour--;
				}
			} else {
				if (this.hour <= 0) {
					this.hour = 23;
				} else {
					this.hour--;
				}
			}
		},
		decrementMinute: function (step) {
			var newVal;

			if (step) {
				newVal = this.minute - step;
			} else {
				newVal = this.minute - this.minuteStep;
			}

			if (newVal < 0) {
				this.decrementHour();
				this.minute = newVal + 60;
			} else {
				this.minute = newVal;
			}
		},
		decrementSecond: function () {
			var newVal = this.second - this.secondStep;

			if (newVal < 0) {
				this.decrementMinute(true);
				this.second = newVal + 60;
			} else {
				this.second = newVal;
			}
		},
		elementKeydown: function (e) {
			switch (e.keyCode) {
				case 9: //tab
				case 27: // escape
					this.updateFromElementVal();
					break;
				case 37: // left arrow
					e.preventDefault();
					this.highlightPrevUnit();
					break;
				case 38: // up arrow
					e.preventDefault();
					switch (this.highlightedUnit) {
						case 'hour':
							this.incrementHour();
							this.highlightHour();
							break;
						case 'minute':
							this.incrementMinute();
							this.highlightMinute();
							break;
						case 'second':
							this.incrementSecond();
							this.highlightSecond();
							break;
						case 'meridian':
							this.toggleMeridian();
							this.highlightMeridian();
							break;
					}
					this.update();
					break;
				case 39: // right arrow
					e.preventDefault();
					this.highlightNextUnit();
					break;
				case 40: // down arrow
					e.preventDefault();
					switch (this.highlightedUnit) {
						case 'hour':
							this.decrementHour();
							this.highlightHour();
							break;
						case 'minute':
							this.decrementMinute();
							this.highlightMinute();
							break;
						case 'second':
							this.decrementSecond();
							this.highlightSecond();
							break;
						case 'meridian':
							this.toggleMeridian();
							this.highlightMeridian();
							break;
					}

					this.update();
					break;
			}
		},
		getCursorPosition: function () {
			var input = this.$element.get(0);

			if ('selectionStart' in input) {// Standard-compliant browsers

				return input.selectionStart;
			} else if (document.selection) {// IE fix
				input.focus();
				var sel = document.selection.createRange(),
						selLen = document.selection.createRange().text.length;

				sel.moveStart('character', -input.value.length);

				return sel.text.length - selLen;
			}
		},
		getTemplate: function () {
			var template,
					hourTemplate,
					minuteTemplate,
					secondTemplate,
					meridianTemplate,
					templateContent;

			if (this.showInputs) {
				hourTemplate = '<input type="text" class="bootstrap-timepicker-hour" maxlength="2"/>';
				minuteTemplate = '<input type="text" class="bootstrap-timepicker-minute" maxlength="2"/>';
				secondTemplate = '<input type="text" class="bootstrap-timepicker-second" maxlength="2"/>';
				meridianTemplate = '<input type="text" class="bootstrap-timepicker-meridian" maxlength="2"/>';
			} else {
				hourTemplate = '<span class="bootstrap-timepicker-hour"></span>';
				minuteTemplate = '<span class="bootstrap-timepicker-minute"></span>';
				secondTemplate = '<span class="bootstrap-timepicker-second"></span>';
				meridianTemplate = '<span class="bootstrap-timepicker-meridian"></span>';
			}

			templateContent = '<table>' +
					'<tr>' +
					'<td><a href="#" data-action="incrementHour"><i class="icon-chevron-up"></i></a></td>' +
					'<td class="separator">&nbsp;</td>' +
					'<td><a href="#" data-action="incrementMinute"><i class="icon-chevron-up"></i></a></td>' +
					(this.showSeconds ?
							'<td class="separator">&nbsp;</td>' +
							'<td><a href="#" data-action="incrementSecond"><i class="icon-chevron-up"></i></a></td>'
							: '') +
					(this.showMeridian ?
							'<td class="separator">&nbsp;</td>' +
							'<td class="meridian-column"><a href="#" data-action="toggleMeridian"><i class="icon-chevron-up"></i></a></td>'
							: '') +
					'</tr>' +
					'<tr>' +
					'<td>' + hourTemplate + '</td> ' +
					'<td class="separator">:</td>' +
					'<td>' + minuteTemplate + '</td> ' +
					(this.showSeconds ?
							'<td class="separator">:</td>' +
							'<td>' + secondTemplate + '</td>'
							: '') +
					(this.showMeridian ?
							'<td class="separator">&nbsp;</td>' +
							'<td>' + meridianTemplate + '</td>'
							: '') +
					'</tr>' +
					'<tr>' +
					'<td><a href="#" data-action="decrementHour"><i class="icon-chevron-down"></i></a></td>' +
					'<td class="separator"></td>' +
					'<td><a href="#" data-action="decrementMinute"><i class="icon-chevron-down"></i></a></td>' +
					(this.showSeconds ?
							'<td class="separator">&nbsp;</td>' +
							'<td><a href="#" data-action="decrementSecond"><i class="icon-chevron-down"></i></a></td>'
							: '') +
					(this.showMeridian ?
							'<td class="separator">&nbsp;</td>' +
							'<td><a href="#" data-action="toggleMeridian"><i class="icon-chevron-down"></i></a></td>'
							: '') +
					'</tr>' +
					'</table>';

			switch (this.template) {
				case 'modal':
					template = '<div class="bootstrap-timepicker-widget modal hide fade in" data-backdrop="' + (this.modalBackdrop ? 'true' : 'false') + '">' +
							'<div class="modal-header">' +
							'<a href="#" class="close" data-dismiss="modal">×</a>' +
							'<h3>Pick a Time</h3>' +
							'</div>' +
							'<div class="modal-content">' +
							templateContent +
							'</div>' +
							'<div class="modal-footer">' +
							'<a href="#" class="btn btn-primary" data-dismiss="modal">OK</a>' +
							'</div>' +
							'</div>';
					break;
				case 'dropdown':
					template = '<div class="bootstrap-timepicker-widget dropdown-menu">' + templateContent + '</div>';
					break;
			}

			return template;
		},
		getTime: function () {
			if (this.hour === '') {
				return '';
			}

			return this.hour + ':' + (this.minute.toString().length === 1 ? '0' + this.minute : this.minute) + (this.showSeconds ? ':' + (this.second.toString().length === 1 ? '0' + this.second : this.second) : '') + (this.showMeridian ? ' ' + this.meridian : '');
		},
		hideWidget: function () {
			if (this.isOpen === false) {
				return;
			}

			this.$element.trigger({
				'type': 'hide.timepicker',
				'time': {
					'value': this.getTime(),
					'hours': this.hour,
					'minutes': this.minute,
					'seconds': this.second,
					'meridian': this.meridian
				}
			});

			if (this.template === 'modal' && this.$widget.modal) {
				this.$widget.modal('hide');
			} else {
				this.$widget.removeClass('open');
			}

			$(document).off('mousedown.timepicker, touchend.timepicker');

			this.isOpen = false;
			// show/hide approach taken by datepicker
			this.$widget.detach();
		},
		highlightUnit: function () {
			this.position = this.getCursorPosition();
			if (this.position >= 0 && this.position <= 2) {
				this.highlightHour();
			} else if (this.position >= 3 && this.position <= 5) {
				this.highlightMinute();
			} else if (this.position >= 6 && this.position <= 8) {
				if (this.showSeconds) {
					this.highlightSecond();
				} else {
					this.highlightMeridian();
				}
			} else if (this.position >= 9 && this.position <= 11) {
				this.highlightMeridian();
			}
		},
		highlightNextUnit: function () {
			switch (this.highlightedUnit) {
				case 'hour':
					this.highlightMinute();
					break;
				case 'minute':
					if (this.showSeconds) {
						this.highlightSecond();
					} else if (this.showMeridian) {
						this.highlightMeridian();
					} else {
						this.highlightHour();
					}
					break;
				case 'second':
					if (this.showMeridian) {
						this.highlightMeridian();
					} else {
						this.highlightHour();
					}
					break;
				case 'meridian':
					this.highlightHour();
					break;
			}
		},
		highlightPrevUnit: function () {
			switch (this.highlightedUnit) {
				case 'hour':
					if (this.showMeridian) {
						this.highlightMeridian();
					} else if (this.showSeconds) {
						this.highlightSecond();
					} else {
						this.highlightMinute();
					}
					break;
				case 'minute':
					this.highlightHour();
					break;
				case 'second':
					this.highlightMinute();
					break;
				case 'meridian':
					if (this.showSeconds) {
						this.highlightSecond();
					} else {
						this.highlightMinute();
					}
					break;
			}
		},
		highlightHour: function () {
			var $element = this.$element.get(0),
					self = this;

			this.highlightedUnit = 'hour';

			if ($element.setSelectionRange) {
				setTimeout(function () {
					if (self.hour < 10) {
						$element.setSelectionRange(0, 1);
					} else {
						$element.setSelectionRange(0, 2);
					}
				}, 0);
			}
		},
		highlightMinute: function () {
			var $element = this.$element.get(0),
					self = this;

			this.highlightedUnit = 'minute';

			if ($element.setSelectionRange) {
				setTimeout(function () {
					if (self.hour < 10) {
						$element.setSelectionRange(2, 4);
					} else {
						$element.setSelectionRange(3, 5);
					}
				}, 0);
			}
		},
		highlightSecond: function () {
			var $element = this.$element.get(0),
					self = this;

			this.highlightedUnit = 'second';

			if ($element.setSelectionRange) {
				setTimeout(function () {
					if (self.hour < 10) {
						$element.setSelectionRange(5, 7);
					} else {
						$element.setSelectionRange(6, 8);
					}
				}, 0);
			}
		},
		highlightMeridian: function () {
			var $element = this.$element.get(0),
					self = this;

			this.highlightedUnit = 'meridian';

			if ($element.setSelectionRange) {
				if (this.showSeconds) {
					setTimeout(function () {
						if (self.hour < 10) {
							$element.setSelectionRange(8, 10);
						} else {
							$element.setSelectionRange(9, 11);
						}
					}, 0);
				} else {
					setTimeout(function () {
						if (self.hour < 10) {
							$element.setSelectionRange(5, 7);
						} else {
							$element.setSelectionRange(6, 8);
						}
					}, 0);
				}
			}
		},
		incrementHour: function () {
			if (this.showMeridian) {
				if (this.hour === 11) {
					this.hour++;
					return this.toggleMeridian();
				} else if (this.hour === 12) {
					this.hour = 0;
				}
			}
			if (this.hour === 23) {
				this.hour = 0;

				return;
			}
			this.hour++;
		},
		incrementMinute: function (step) {
			var newVal;

			if (step) {
				newVal = this.minute + step;
			} else {
				newVal = this.minute + this.minuteStep - (this.minute % this.minuteStep);
			}

			if (newVal > 59) {
				this.incrementHour();
				this.minute = newVal - 60;
			} else {
				this.minute = newVal;
			}
		},
		incrementSecond: function () {
			var newVal = this.second + this.secondStep - (this.second % this.secondStep);

			if (newVal > 59) {
				this.incrementMinute(true);
				this.second = newVal - 60;
			} else {
				this.second = newVal;
			}
		},
		mousewheel: function (e) {
			if (this.disableMousewheel) {
				return;
			}

			e.preventDefault();
			e.stopPropagation();

			var delta = e.originalEvent.wheelDelta || -e.originalEvent.detail,
					scrollTo = null;

			if (e.type === 'mousewheel') {
				scrollTo = (e.originalEvent.wheelDelta * -1);
			}
			else if (e.type === 'DOMMouseScroll') {
				scrollTo = 40 * e.originalEvent.detail;
			}

			if (scrollTo) {
				e.preventDefault();
				$(this).scrollTop(scrollTo + $(this).scrollTop());
			}

			switch (this.highlightedUnit) {
				case 'minute':
					if (delta > 0) {
						this.incrementMinute();
					} else {
						this.decrementMinute();
					}
					this.highlightMinute();
					break;
				case 'second':
					if (delta > 0) {
						this.incrementSecond();
					} else {
						this.decrementSecond();
					}
					this.highlightSecond();
					break;
				case 'meridian':
					this.toggleMeridian();
					this.highlightMeridian();
					break;
				default:
					if (delta > 0) {
						this.incrementHour();
					} else {
						this.decrementHour();
					}
					this.highlightHour();
					break;
			}

			return false;
		},
		// This method was adapted from bootstrap-datepicker.
		place: function () {
			if (this.isInline) {
				return;
			}
			var widgetWidth = this.$widget.outerWidth(), widgetHeight = this.$widget.outerHeight(), visualPadding = 10, windowWidth =
					$(window).width(), windowHeight = $(window).height(), scrollTop = $(window).scrollTop();

			var zIndex = parseInt(this.$element.parents().filter(function () {
			}).first().css('z-index'), 10) + 10;
			var offset = this.component ? this.component.parent().offset() : this.$element.offset();
			var height = this.component ? this.component.outerHeight(true) : this.$element.outerHeight(false);
			var width = this.component ? this.component.outerWidth(true) : this.$element.outerWidth(false);
			var left = offset.left, top = offset.top;

			this.$widget.removeClass('timepicker-orient-top timepicker-orient-bottom timepicker-orient-right timepicker-orient-left');

			if (this.orientation.x !== 'auto') {
				this.picker.addClass('datepicker-orient-' + this.orientation.x);
				if (this.orientation.x === 'right') {
					left -= widgetWidth - width;
				}
			} else {
				// auto x orientation is best-placement: if it crosses a window edge, fudge it sideways
				// Default to left
				this.$widget.addClass('timepicker-orient-left');
				if (offset.left < 0) {
					left -= offset.left - visualPadding;
				} else if (offset.left + widgetWidth > windowWidth) {
					left = windowWidth - widgetWidth - visualPadding;
				}
			}
			// auto y orientation is best-situation: top or bottom, no fudging, decision based on which shows more of the widget
			var yorient = this.orientation.y, topOverflow, bottomOverflow;
			if (yorient === 'auto') {
				topOverflow = -scrollTop + offset.top - widgetHeight;
				bottomOverflow = scrollTop + windowHeight - (offset.top + height + widgetHeight);
				if (Math.max(topOverflow, bottomOverflow) === bottomOverflow) {
					yorient = 'top';
				} else {
					yorient = 'bottom';
				}
			}
			this.$widget.addClass('timepicker-orient-' + yorient);
			if (yorient === 'top') {
				top += height;
			} else {
				top -= widgetHeight + parseInt(this.$widget.css('padding-top'), 10);
			}

			this.$widget.css({
				top: top,
				left: left,
				zIndex: zIndex
			});
		},
		remove: function () {
			$('document').off('.timepicker');
			if (this.$widget) {
				this.$widget.remove();
			}
			delete this.$element.data().timepicker;
		},
		setDefaultTime: function (defaultTime) {
			if (!this.$element.val()) {
				if (defaultTime === 'current') {
					var dTime = new Date(),
							hours = dTime.getHours(),
							minutes = dTime.getMinutes(),
							seconds = dTime.getSeconds(),
							meridian = 'AM';

					if (seconds !== 0) {
						seconds = Math.ceil(dTime.getSeconds() / this.secondStep) * this.secondStep;
						if (seconds === 60) {
							minutes += 1;
							seconds = 0;
						}
					}

					if (minutes !== 0) {
						minutes = Math.ceil(dTime.getMinutes() / this.minuteStep) * this.minuteStep;
						if (minutes === 60) {
							hours += 1;
							minutes = 0;
						}
					}

					if (this.showMeridian) {
						if (hours === 0) {
							hours = 12;
						} else if (hours >= 12) {
							if (hours > 12) {
								hours = hours - 12;
							}
							meridian = 'PM';
						} else {
							meridian = 'AM';
						}
					}

					this.hour = hours;
					this.minute = minutes;
					this.second = seconds;
					this.meridian = meridian;

					this.update();

				} else if (defaultTime === false) {
					this.hour = 0;
					this.minute = 0;
					this.second = 0;
					this.meridian = 'AM';
				} else {
					this.setTime(defaultTime);
				}
			} else {
				this.updateFromElementVal();
			}
		},
		setTime: function (time, ignoreWidget) {
			if (!time) {
				this.clear();
				return;
			}

			var timeArray,
					hour,
					minute,
					second,
					meridian;

			if (typeof time === 'object' && time.getMonth) {
				// this is a date object
				hour = time.getHours();
				minute = time.getMinutes();
				second = time.getSeconds();

				if (this.showMeridian) {
					meridian = 'AM';
					if (hour > 12) {
						meridian = 'PM';
						hour = hour % 12;
					}

					if (hour === 12) {
						meridian = 'PM';
					}
				}
			} else {
				if (time.match(/p/i) !== null) {
					meridian = 'PM';
				} else {
					meridian = 'AM';
				}

				time = time.replace(/[^0-9\:]/g, '');

				timeArray = time.split(':');

				hour = timeArray[0] ? timeArray[0].toString() : timeArray.toString();
				minute = timeArray[1] ? timeArray[1].toString() : '';
				second = timeArray[2] ? timeArray[2].toString() : '';

				// idiot proofing
				if (hour.length > 4) {
					second = hour.substr(4, 2);
				}
				if (hour.length > 2) {
					minute = hour.substr(2, 2);
					hour = hour.substr(0, 2);
				}
				if (minute.length > 2) {
					second = minute.substr(2, 2);
					minute = minute.substr(0, 2);
				}
				if (second.length > 2) {
					second = second.substr(2, 2);
				}

				hour = parseInt(hour, 10);
				minute = parseInt(minute, 10);
				second = parseInt(second, 10);

				if (isNaN(hour)) {
					hour = 0;
				}
				if (isNaN(minute)) {
					minute = 0;
				}
				if (isNaN(second)) {
					second = 0;
				}

				if (this.showMeridian) {
					if (hour < 1) {
						hour = 1;
					} else if (hour > 12) {
						hour = 12;
					}
				} else {
					if (hour >= 24) {
						hour = 23;
					} else if (hour < 0) {
						hour = 0;
					}
					if (hour < 13 && meridian === 'PM') {
						hour = hour + 12;
					}
				}

				if (minute < 0) {
					minute = 0;
				} else if (minute >= 60) {
					minute = 59;
				}

				if (this.showSeconds) {
					if (isNaN(second)) {
						second = 0;
					} else if (second < 0) {
						second = 0;
					} else if (second >= 60) {
						second = 59;
					}
				}
			}

			this.hour = hour;
			this.minute = minute;
			this.second = second;
			this.meridian = meridian;

			this.update(ignoreWidget);
		},
		showWidget: function () {
			if (this.isOpen) {
				return;
			}

			if (this.$element.is(':disabled')) {
				return;
			}

			// show/hide approach taken by datepicker
			this.$widget.appendTo(this.appendWidgetTo);
			var self = this;
			$(document).on('mousedown.timepicker, touchend.timepicker', function (e) {
				// This condition was inspired by bootstrap-datepicker.
				// The element the timepicker is invoked on is the input but it has a sibling for addon/button.
				if (!(self.$element.parent().find(e.target).length ||
						self.$widget.is(e.target) ||
						self.$widget.find(e.target).length)) {
					self.hideWidget();
				}
			});

			this.$element.trigger({
				'type': 'show.timepicker',
				'time': {
					'value': this.getTime(),
					'hours': this.hour,
					'minutes': this.minute,
					'seconds': this.second,
					'meridian': this.meridian
				}
			});

			this.place();
			if (this.disableFocus) {
				this.$element.blur();
			}

			// widget shouldn't be empty on open
			if (this.hour === '') {
				if (this.defaultTime) {
					this.setDefaultTime(this.defaultTime);
				} else {
					this.setTime('0:0:0');
				}
			}

			if (this.template === 'modal' && this.$widget.modal) {
				this.$widget.modal('show').on('hidden', $.proxy(this.hideWidget, this));
			} else {
				if (this.isOpen === false) {
					this.$widget.addClass('open');
				}
			}

			this.isOpen = true;
		},
		toggleMeridian: function () {
			this.meridian = this.meridian === 'AM' ? 'PM' : 'AM';
		},
		update: function (ignoreWidget) {
			this.updateElement();
			if (!ignoreWidget) {
				this.updateWidget();
			}

			this.$element.trigger({
				'type': 'changeTime.timepicker',
				'time': {
					'value': this.getTime(),
					'hours': this.hour,
					'minutes': this.minute,
					'seconds': this.second,
					'meridian': this.meridian
				}
			});
		},
		updateElement: function () {
			this.$element.val(this.getTime()).change();
		},
		updateFromElementVal: function () {
			this.setTime(this.$element.val());
		},
		updateWidget: function () {
			if (this.$widget === false) {
				return;
			}

			var hour = this.hour,
					minute = this.minute.toString().length === 1 ? '0' + this.minute : this.minute,
					second = this.second.toString().length === 1 ? '0' + this.second : this.second;

			if (this.showInputs) {
				this.$widget.find('input.bootstrap-timepicker-hour').val(hour);
				this.$widget.find('input.bootstrap-timepicker-minute').val(minute);

				if (this.showSeconds) {
					this.$widget.find('input.bootstrap-timepicker-second').val(second);
				}
				if (this.showMeridian) {
					this.$widget.find('input.bootstrap-timepicker-meridian').val(this.meridian);
				}
			} else {
				this.$widget.find('span.bootstrap-timepicker-hour').text(hour);
				this.$widget.find('span.bootstrap-timepicker-minute').text(minute);

				if (this.showSeconds) {
					this.$widget.find('span.bootstrap-timepicker-second').text(second);
				}
				if (this.showMeridian) {
					this.$widget.find('span.bootstrap-timepicker-meridian').text(this.meridian);
				}
			}
		},
		updateFromWidgetInputs: function () {
			if (this.$widget === false) {
				return;
			}

			var t = this.$widget.find('input.bootstrap-timepicker-hour').val() + ':' +
					this.$widget.find('input.bootstrap-timepicker-minute').val() +
					(this.showSeconds ? ':' + this.$widget.find('input.bootstrap-timepicker-second').val() : '') +
					(this.showMeridian ? this.$widget.find('input.bootstrap-timepicker-meridian').val() : '')
					;

			this.setTime(t, true);
		},
		widgetClick: function (e) {
			e.stopPropagation();
			e.preventDefault();

			var $input = $(e.target),
					action = $input.closest('a').data('action');

			if (action) {
				this[action]();
			}
			this.update();

			if ($input.is('input')) {
				$input.get(0).setSelectionRange(0, 2);
			}
		},
		widgetKeydown: function (e) {
			var $input = $(e.target),
					name = $input.attr('class').replace('bootstrap-timepicker-', '');

			switch (e.keyCode) {
				case 9: //tab
					if ((this.showMeridian && name === 'meridian') || (this.showSeconds && name === 'second') || (!this.showMeridian && !this.showSeconds && name === 'minute')) {
						return this.hideWidget();
					}
					break;
				case 27: // escape
					this.hideWidget();
					break;
				case 38: // up arrow
					e.preventDefault();
					switch (name) {
						case 'hour':
							this.incrementHour();
							break;
						case 'minute':
							this.incrementMinute();
							break;
						case 'second':
							this.incrementSecond();
							break;
						case 'meridian':
							this.toggleMeridian();
							break;
					}
					this.setTime(this.getTime());
					$input.get(0).setSelectionRange(0, 2);
					break;
				case 40: // down arrow
					e.preventDefault();
					switch (name) {
						case 'hour':
							this.decrementHour();
							break;
						case 'minute':
							this.decrementMinute();
							break;
						case 'second':
							this.decrementSecond();
							break;
						case 'meridian':
							this.toggleMeridian();
							break;
					}
					this.setTime(this.getTime());
					$input.get(0).setSelectionRange(0, 2);
					break;
			}
		},
		widgetKeyup: function (e) {
			if ((e.keyCode === 65) || (e.keyCode === 77) || (e.keyCode === 80) || (e.keyCode === 46) || (e.keyCode === 8) || (e.keyCode >= 46 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105)) {
				this.updateFromWidgetInputs();
			}
		}
	};

	//TIMEPICKER PLUGIN DEFINITION
	$.fn.timepicker = function (option) {
		var args = Array.apply(null, arguments);
		args.shift();
		return this.each(function () {
			var $this = $(this),
					data = $this.data('timepicker'),
					options = typeof option === 'object' && option;

			if (!data) {
				$this.data('timepicker', (data = new Timepicker(this, $.extend({}, $.fn.timepicker.defaults, options, $(this).data()))));
			}

			if (typeof option === 'string') {
				data[option].apply(data, args);
			}
		});
	};

	$.fn.timepicker.defaults = {
		defaultTime: 'current',
		disableFocus: false,
		disableMousewheel: false,
		isOpen: false,
		minuteStep: 15,
		modalBackdrop: false,
		orientation: {x: 'auto', y: 'auto'},
		secondStep: 15,
		showSeconds: false,
		showInputs: true,
		showMeridian: true,
		template: 'dropdown',
		appendWidgetTo: 'body',
		showWidgetOnAddonClick: true
	};

	$.fn.timepicker.Constructor = Timepicker;

})(jQuery, window, document);

// GWE MOD to stop timepicker disappearing after usage
jQuery(document).ready (function() {

	if(window.MooTools ) {

		var mHide = Element.prototype.hide;

		Element.implement({
			hide: function () {
				if (this.is("[rel=tooltip]")) {
					return this;
				}
				mHide.apply(this, arguments);
			}
		});
	}
});

function priceJevrList(name, prices) {
	var elems = jQuery(".rsvp_" + name);
	if (!elems.length)
		return 0;
	var result = 0;
	if (name.replace("xmlfile_", "") != name) {
		name = name.replace("xmlfile_", "");
	}
	elems.each(function (elindex, elem) {
		//if (elem.tagName.toLowerCase()!="select") return;
		// find parent span which has the elemtn id and individual count in it
		$elem = jQuery(elem);
		var indivcount = 0;
		if ($elem.parent().attr('id'))
		{
			indivcount = $elem.parent().attr('id').replace("rsvp_" + name + "_span_", "");
			if (name.replace("xmlfile_", "") != name) {
				indivcount = $elem.parent().attr('id').replace("rsvp_" + name.replace("xmlfile_", "") + "_span_", "");
			}
		}
		else {

			// is this what I should be doing ???
			// check radio list too!
			return;
		}
		// NB this is the name not the element!
		if (JevrConditionalFields.isVisible(name, indivcount)) {
			// is it a disabled first parameter
			if ($elem.hasClass("disabledfirstparam"))
				return;
			if ($elem.hasClass("paramtmpl"))
				return;
			var option = $elem.val();
			if (prices[option]) {
				result += parseFloat(prices[option]);
			}
		}
	});
	return result;
}
function priceJevrRadio(name, prices) {
	var elems = jQuery(".rsvp_" + name + " input");
	if (!elems.length)
		return 0;
	var result = 0;
	if (name.replace("xmlfile_", "") != name) {
		name = name.replace("xmlfile_", "");
	}
	elems.each(function (elindex, elem) {
		$elem = jQuery(elem);
		var indivcount = 0;

		// find parent span which has the elemtn id and individual count in it
		indivcount = $elem.parent().attr('id') ? $elem.parent().attr('id').replace("rsvp_" + name + "_span_", "") : "";
		if (name.replace("xmlfile_", "") != name) {
			indivcount = $elem.parent().attr('id') ? $elem.parent().attr('id').replace("rsvp_" + name.replace("xmlfile_", "") + "_span_", "") : "";
		}
		if (indivcount == "" && $elem.parent().prop('tagName').toLowerCase() == "label") {
			indivcount = $elem.parent().parent().attr('id') ? $elem.parent().parent().attr('id').replace("rsvp_" + name + "_span_", "") : "";
			if (name.replace("xmlfile_", "") != name) {
				indivcount = $elem.parent().parent().attr('id') ? $elem.parent().parent().attr('id').replace("rsvp_" + name.replace("xmlfile_", "") + "_span_", "") : "";
			}
		}

		// NB this is the name not the element!
		if (JevrConditionalFields.isVisible(name, indivcount)) {
			// is it a disabled first parameter
			if ($elem.parent().hasClass("disabledfirstparam") || $elem.parent().parent().hasClass("disabledfirstparam"))
				return;
			if ($elem.parent().hasClass("paramtmpl") || $elem.parent().parent().hasClass("paramtmpl"))
				return;
			if (!$elem.prop('checked'))
				return;
			var option = $elem.val();
			if (prices[option]) {
				//alert(prices[option]);
				result += parseFloat(prices[option]);
			}
		}
	});
	return result;
}

function surchargeList(name, surcharges) {
	var elems = jQuery(".rsvp_" + name);
	if (!elems.length)
		return 0;
	var result = 0;
	elems.each(function (elindex, elem) {
		if (elem.tagName.toLowerCase() != "select")
			return;
		var option = elem.value;
		if (surcharges[option]) {
			result += parseFloat(surcharges[option]);
		}
	});
	return result;
}

function surchargeCoupon(name, surcharges) {
	var elems = jQuery(".rsvp_" + name);
	if (!elems.length)
		return 0;
	var result = 0;
	elems.each(function (elindex, elem) {
		if (elem.tagName.toLowerCase() != "input")
			return;
		var option = elem.value;
		if (surcharges[option]) {
			result += parseFloat(surcharges[option]);
		}
	});
	return result;
}

var jevrsvpRequiredFields = {
	fields: new Array(),
	verify:function (form){
		if (jQuery("#jevattend_no").length && jQuery("#jevattend_no").prop("checked")){
			return true;
		}
		var messages =  new Array();
		var validattendee = true;
		if (!jevrsvpRequiredFields.emailcheck()){
			messages.push(rsvpInvalidEmail);
			validattendee = false;
		}
		jQuery(jevrsvpRequiredFields.fields).each(function (i, item) {
			if (item.requiredCheckScript && window[item.requiredCheckScript]) {
				// does it fail the specific check script
				if (!window[item.requiredCheckScript](form, item)){
					messages.push(item.reqmsg);
				}
			}
			else if (item.type && item.type=='radio'){
				messages = jevrsvpRequiredFields.verifyRadio(item, form, messages);
			}
			else if (item.type && item.type=='checkbox'){
				messages = jevrsvpRequiredFields.verifyCheckbox(item, form, messages);
			}
			else {
				var name = item.name;
				var matches = new Array();
				jQuery(form.elements).each (function (testi, testitem) {
					if (item.name && testitem.name == item.name && testitem.id.indexOf("_xxx")<0){
                                            matches.push(testitem);
					}
					else if(testitem.id == item.id){
                                            matches.push(testitem);
					}
				});

				// extract field name for conditionality check
				var conditionalfieldname = item.id;
				// remove the xmlfile_ prefix
				conditionalfieldname = conditionalfieldname.replace("xmlfile_","");
				// remove the count
				if (conditionalfieldname.indexOf("_")>0){
					conditionalfieldname = conditionalfieldname.substring(0,conditionalfieldname.indexOf("_"));
				}
				var value = "";
				jQuery(matches).each (function (index, match){
                                        // NB match is NOT a jQuery Object
					if (jQuery(match).hasClass("disabledfirstparam") || jQuery(match).parent().hasClass("disabledfirstparam")  || jQuery(match).parent().parent().hasClass("disabledfirstparam")) {
						return ;
					}

					value = match.value;
					// chosen replacements!
					if (document.getElementById(item.id+'_chzn'))
					{
						match = document.getElementById(item.id+'_chzn');
					}

					//if (JevrConditionalFields.isVisible(conditionalfieldname, index) && (value == item['default'] || value == "" || value.length < 3)){
					if (JevrConditionalFields.isVisible(conditionalfieldname, index) && (value == item['default'] || value == "" )){

						//highlight the bad element values
						match.style.backgroundColor="red";
						if(item.reqmsg!=""){
							messages.push(item.reqmsg);
						}
					}
					else {
						try {
							match.style.backgroundColor="inherit";
						}
						catch (e){
							match.style.backgroundColor="transparent";
						}
					}
				});

			}
		});
		if (messages.length>0){
			var message = "";
			jQuery(messages).each (function (index, msg){
				message += msg+'\n';
			});
			if(validattendee && JevRsvpLanguage.strings['JEV_CONTINUE_EVEN_THOUGH_NOT_ALL_REQUIRED_FIELDS_FILLED']){
				return confirm(JevRsvpLanguage.strings['JEV_CONTINUE_EVEN_THOUGH_NOT_ALL_REQUIRED_FIELDS_FILLED']+'\n\n'+message);
			}
			else {
				alert(message);
			}
		}
		return (messages.length==0);
	},
	verifyRadio : function(item, form, messages){
		var name = item.name;
		var matches = new Array();
		var testname = item.name.replace("[xxxyyyzzz]","");
		jQuery.makeArray(form.elements).slice().each (function (testitem,testi) {
			if(testitem.name.substring(0,testname.length) == testname && testitem.id.indexOf("_xxx")<0 && testitem.checked){
				matches.push(testitem);
			};
		});

		// extract field name for conditionality check
		var conditionalfieldname = item.id;
		// remove the xmlfile_ prefix
		conditionalfieldname = conditionalfieldname.replace("xmlfile_","");
		// remove the count
		if (conditionalfieldname.indexOf("_")>0){
			conditionalfieldname = conditionalfieldname.substring(0,conditionalfieldname.indexOf("_"));
		}

		var value = "";
		matches.each (function (match, index){
			value = match.value;

			if (jQuery(match).hasClass("disabledfirstparam") || jQuery(match).parent().hasClass("disabledfirstparam")  || jQuery(match).parent().parent().hasClass("disabledfirstparam")) {
				return ;
			}

			if (JevrConditionalFields.isVisible(conditionalfieldname, index) && (value == item['default'] || value == "")){

				//highlight the bad element values
				match.parentNode.parentNode.style.backgroundColor="red";
				if(item.reqmsg!=""){
					messages.push(item.reqmsg);
				}
				else {
					messages.push(" ");
				}
			}
			else {
				try {
					match.parentNode.parentNode.style.backgroundColor="inherit";
				}
				catch (e){
					match.parentNode.parentNode.style.backgroundColor="transparent";
				}
			}
		});
		return messages;
	},
	verifyCheckbox : function(item, form, messages){
		var name = item.name;
		var matches = new Array();
		var failures = new Array();
		var testname = item.name.replace("[xxxyyyzzz]","");
		jQuery.makeArray(form.elements).slice().each (function (testitem,testi) {
			if (testitem.type!="checkbox") return;
			if(testitem.name.substring(0,testname.length) == testname && testitem.id.indexOf("_xxx")<0 && testitem.checked){
				matches.push(testitem);
			}
			else if (testitem.name.substring(0,testname.length) == testname && testitem.id.indexOf("_xxx")<0 && !testitem.checked){
				failures.push(testitem);
			}
		});

		// extract field name for conditionality check
		var conditionalfieldname = item.id;
		// remove the xmlfile_ prefix
		conditionalfieldname = conditionalfieldname.replace("xmlfile_","");
		// remove the count
		if (conditionalfieldname.indexOf("_")>0){
			conditionalfieldname = conditionalfieldname.substring(0,conditionalfieldname.indexOf("_"));
		}

		if (matches.length>0){
			matches.each (function (match, index){
				if (jQuery(match).hasClass("disabledfirstparam") || jQuery(match).parent().hasClass("disabledfirstparam")  || jQuery(match).parent().parent().hasClass("disabledfirstparam")) {
					return ;
				}

				try {
					match.parentNode.style.backgroundColor="inherit";
				}
				catch (e){
					match.parentNode.style.backgroundColor="transparent";
				}
			});
		}
		if (failures.length>0){
			failures.each (function (failure, index){
				if (jQuery(failure).hasClass("disabledfirstparam") || jQuery(failure).parent().hasClass("disabledfirstparam")  || jQuery(failure).parent().parent().hasClass("disabledfirstparam")) {
					return ;
				}

				// index value here is for each checkbox NOT the overall 'element' so extract the real index'
				var checkboxindex = failure.name.replace("xmlfile["+conditionalfieldname+"][", "").replace("][]","");
				//alert(checkboxindex+" vs "+failure.name+" from "+conditionalfieldname);
				if (!JevrConditionalFields.isVisible(conditionalfieldname, checkboxindex)) {
					return;
				}

				//highlight the bad element values
				failure.parentNode.style.backgroundColor="red";
				if(item.reqmsg!=""){
					// Don't output the message more than once!'
					if (messages.indexOf(item.reqmsg) <0) {
						messages.push(item.reqmsg);
					}
				}
				else {
					messages.push(" ");
				}
			});
		}
		return messages;
	},
	emailcheck : function () {
		if (!jQuery("#jevattend_email").length || ( jQuery('#user_id').length && jQuery('#user_id').val() >0) ) {
			if (!jQuery(".profile_email").length){
				return true;
			}
		}
		var str = jQuery("#jevattend_email").val();
		// Core Joomla profile email field if present!
		if ( jQuery('.profile_email').length ) {
			str = jQuery(".profile_email").val();
		}
		
		var at="@";
		var dot=".";
		var lat=str.indexOf(at);
		var lstr=str.length;
		var ldot=str.indexOf(dot);
		var valid = true;

		// must have an @ and must not start or end with @
		if (str.indexOf(at)==-1 || str.indexOf(at)==0 || str.indexOf(at)==lstr-1){
			valid=false;
		}
		// Must have a . and must not start or end with a .
		if (str.indexOf(dot)==-1 || str.indexOf(dot)==0 || str.indexOf(dot)==lstr-1){
			valid=false;
		}
		// must not have more than one @
		if (str.indexOf(at,(lat+1))!=-1){
			valid=false;
		}
		// must not have a . straight before or after a ?
		if (str.substring(lat-1,lat)==dot || str.substring(lat+1,lat+2)==dot){
			valid=false;
		}
		// there must be a . after the @
		if (str.indexOf(dot,(lat+2))==-1){
			valid=false;
		}
		// no spaces
		if (str.indexOf(" ")!=-1){
			valid=false;
		}

		if (!valid){
			jQuery("#jevattend_email").css('background-color',"red");
			return false;
		}
		else {
			try {
				jQuery("#jevattend_email").css('background-color',"inherit");
			}
			catch (e){
				jQuery("#jevattend_email").css('background-color',"transparent");
			}

			return true;
		}
	}

}


// fix the attendance repeat options based on whether the event is repeating or not!
function setupRepeatListener() {
	if (!document.adminForm)
		return;
	var rrfreq = document.adminForm.freq;
	if (rrfreq) {
		checkRepeatBox();
		window.setTimeout("checkRepeatBox()", 800);
		jQuery(rrfreq).each(function (rindex, rbox) {
			jQuery(rbox).on('click', function () {
				checkRepeatBox();
			});
		});
	}
}
function checkRepeatBox() {
	var rrfreq = document.adminForm.freq;
	if (rrfreq) {
		jQuery(rrfreq).each(function (rindex, rbox) {
			if (rbox.checked) {
				var allrepeats = jQuery('div.rsvp_allrepeats');
				var allinvites = jQuery('div.rsvp_allinvites');
				var allreminders = jQuery('div.rsvp_allreminders');

				if (rbox.value.toUpperCase() == "NONE") {
					if (allrepeats.length) {
						allrepeats.css("display","none");
						if (document.adminForm.evid.value == 0)
							jQuery('custom_rsvp_allrepeats1').prop("checked",true);
					}
					if (allinvites.length) {
						allinvites.css("display","none");
						if (document.adminForm.evid.value == 0)
							jQuery('custom_rsvp_allinvites1').prop("checked",true);
					}
					if (allreminders.length) {
						allreminders.css("display","none");
						if (document.adminForm.evid.value == 0)
							jQuery('custom_rsvp_remindallrepeats1').prop("checked",true);
					}
				}
				else {
					if (allrepeats.length) {
						allrepeats.css("display","block");
						// uncomment if you want new events to be tracked by each specific repeat by default
						//if (document.adminForm.evid.value==0) Query('custom_rsvp_allrepeats0').prop("checked",true);
					}
					if (allinvites.length) {
						allinvites.css("display","block");
						// uncomment if you want new events to be tracked by each specific repeat by default
						//if (document.adminForm.evid.value==0) Query('custom_rsvp_allinvites0').prop("checked",true);
					}
					if (allreminders.length) {
						allreminders.css("display","block");
						// uncomment if you want new events to be tracked by each specific repeat by default
						//if (document.adminForm.evid.value==0) Query('custom_rsvp_remindallrepeats0').prop("checked",true);
					}
				}
				// Update Chosen element display
				jQuery(allrepeats).trigger("liszt:updated");
				jQuery(allinvites).trigger("liszt:updated");
				jQuery(allreminders).trigger("liszt:updated");
			}

		});
	}
}

var JevrTotalFee = 0;
var JevrFees = {
	fields: new Array(),
	calculate: function (form) {
		if (jQuery('#jevrtotalfee').length && jQuery('#guestcount').length) {
			JevrTotalFee = 0;
			jQuery(JevrFees.fields).each(function (i, item) {
				var multiplier = 1;
				if (item.byguest && item.byguest > 0) {
					if (JevrConditionalFields) {
						// is this field visible - count how many times
						multiplier = JevrConditionalFields.countVisible(item);
					}
					else if (item.byguest == 1) {
						multiplier = jQuery('#guestcount').val();
					}
					else {
						multiplier = jQuery('#guestcount').val() - 1;
					}
				}
				else {
					if (JevrConditionalFields) {
						// is this field visible - count how many times
						multiplier = JevrConditionalFields.countVisible(item);
					}
				}
				var linefee = 0;
				if (item.price) {
					linefee = parseFloat(item.price(item.name));
				}
				else {
					linefee = parseFloat(item.amount);
				}
				linefee *= multiplier;
				JevrTotalFee += linefee;
			});

			// now the surcharges
			var additiveSurcharge = 0;
			jQuery(JevrFees.fields).each(function (i, item) {
				var linemultiplier = 0;
				if (item.surchargefunction) {
					linemultiplier = parseFloat(item.surchargefunction(item.name));
				}
				else if (!isNaN(parseFloat(item.surcharge))) {
					linemultiplier = parseFloat(item.amount);
				}
				if (item.additivesurcharge){
					additiveSurcharge += linemultiplier;
					if (jQuery('#rsvpst_'+item.name)){
						jQuery('#rsvpst_'+item.name).html(rsvpMoneyFormat(JevrTotalFee *  linemultiplier/100));
					}
				}
				else {
					JevrTotalFee *= 1 + linemultiplier/100;
				}
			});
			if (additiveSurcharge!=0){
				JevrTotalFee *= 1 + additiveSurcharge/100;
			}
			
			// set the total
			jQuery('#jevrtotalfee').html(rsvpMoneyFormat(JevrTotalFee));
			jQuery('#xmlfile_totalfee').val(JevrTotalFee);

			// update the balance
			if (jQuery('#xmlfile_feepaid').length) {
				var feepaid = jQuery('#xmlfile_feepaid').val();
			}
			else {
				var feepaid = 0;
			}
			if (jQuery('#jevrfeebalance').length && jQuery('#xmlfile_totalfee').length) {
				jQuery('#jevrfeebalance').html(rsvpMoneyFormat(jQuery('#xmlfile_totalfee').val() - feepaid));
			}
			;
			if (jQuery('#xmlfile_feebalance').length && jQuery('#xmlfile_totalfee').length) {
				jQuery('#xmlfile_feebalance').val(jQuery('#xmlfile_totalfee').val() - feepaid);
			}

			if (jQuery('#jevrdeposit').length && jQuery('#xmlfile_totalfee').length && jQuery('#jevrdepositpercentage').length) {
				jQuery('#jevrdeposit').html(" ( " + rsvpMoneyFormat(jQuery('#xmlfile_totalfee').val() * parseFloat(jQuery('#jevrdepositpercentage').html()) / 100) + " ) ");
			}
		}
	}
}
jQuery(document).ready(function () {
	setupRepeatListener();
	JevrFees.calculate(document.updateattendance);
})

function addGuest(limit) {
	// update the guest count
	// Do not change this to remove +1 without changing call to addStates
	jQuery('#guestcount').val(parseInt(jQuery('#guestcount').val()) + 1);
	jQuery('#lastguest').val(parseInt(jQuery('#lastguest').val()) + 1);
	var title = jQuery("#jevnexttabtitle").val();

	title = title.replace('xxx', jQuery('#lastguest').val());
	regTabs.addTab(title, title, jQuery('#lastguest').val());

	// Watch this count is 1 less than the label on the tab!
	// call before the fees since the visibility affect the calculation
	JevrConditionFieldState.addStates(jQuery('#lastguest').val() - 1);

	// recalculate the fees!
	JevrFees.calculate(document.updateattendance);

	if (limit > 0 && jQuery('#guestcount').val() >= limit) {
		if (jQuery('#addguest').length)
			jQuery('#addguest').css("display","none");
	}
	// scroll tabs into view	
	var myElem = jQuery('#registration-tab-pane');
	if (myElem.length) {
		jQuery('html, body').animate({
			scrollTop: myElem.offset().top
		}, 1000);
		//var myFx = new Fx.Scroll(window).toElement(myElem, 'y');
	}
}

function removeGuest(limit) {
	// update the guest count
	jQuery('#guestcount').val(parseInt(jQuery('#guestcount').val()) - 1);

	regTabs.removeActiveTab();

	// recalculate the fees!
	JevrFees.calculate(document.updateattendance);

	if (limit > 0 && jQuery('#guestcount').val() < limit) {
		if (jQuery('#addguest').length)
			jQuery('#addguest').css('display', "block");
	}

	// scroll tabs into view
	var myElem = jQuery('#registration-tab-pane');
	if (myElem.length) {
		jQuery('html, body').animate({
			scrollTop: myElem.offset().top
		}, 1000);
	}
}

var resizeTimer;
var SqueezeBox;
function customiseTemplate(url, title) {

	id = jQuery	('#custom_rsvp_template').val();
	url = url.replace("xxGGxx", id);

	var evid = document.adminForm.evid.value;
	if (url.indexOf("?") > 0) {
		url += "&evid=" + evid;
	}
	else {
		url += "?evid=" + evid;
	}
	jevModalPopup("customisetemplate", url, title)

	return;
	}

function setTemplate(id, title)
{
	var template_select = jQuery("#custom_rsvp_template");
	var option = jQuery('<option>');
	template_select.append(option);
	option.val( id);
	option.text( title);
	template_select.val( id);
}
function setTemplateTitle(id, title) {

	var selectedNode = jQuery("#custom_rsvp_template option[value="+id+"]");
	alert(id+" " +title+" "+selectedNode .length);
	if (selectedNode .length){
		selectedNode.text(title);
	}
}

function changeTemplateSelection() {
	var selectedNode = jQuery("#custom_rsvp_template option:selected");
	if (selectedNode.val().indexOf(".xml") > 0 || selectedNode.val() == 0 || selectedNode.attr('locked')) {
		jQuery("#custom_rsvp_template_link").css('display', 'none');
	}
	else {
		jQuery("#custom_rsvp_template_link").css('display', 'inline');
		if (jQuery(".rsvp_overrideprice").length) {
			if (hasFlatFees.indexOf(parseInt(selectedNode.val())) != -1) {
				jQuery(".rsvp_overrideprice").css("display","block");
			}
			else {
				jQuery(".rsvp_overrideprice").css("display","none");
			}
		}
	}
}

function checkCoupon(e, elem, checkurl, client, fieldid, rpid, atd_id, atdee_id) {

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.token = jsontoken;
	requestObject.task = "checkCoupon";
	requestObject.title = elem.val();
	requestObject.client = client;
	requestObject.rp_id = rpid;
	requestObject.atd_id = atd_id;
	requestObject.atdee_id = atdee_id;
	requestObject.fieldid = fieldid;

	minlength = 1;

	if (elem.val().length >= minlength) {

		//url += '?start_debug=1&debug_host=127.0.0.1&debug_port=10000&debug_stop=1';
		var jSonRequest = jQuery.ajax({
			type : 'POST',
			dataType : 'json',
			url : checkurl,
			data : {'json':JSON.stringify(requestObject)},
			contentType: "application/x-www-form-urlencoded; charset=utf-8",
			scriptCharset: "utf-8"
			})
		.done(function(json){
			if (json.error) {
				try {
					elem.val( "");
					eval(json.error);
				}
				catch (e) {
					alert('could not process error handler');
				}
			}
			else {
				setDiscount(json.discount, fieldid);
				setSurcharge(json.surcharge, fieldid);
			}
		})
		.fail( function( jqxhr, textStatus, error){

				alert('Something went wrong...')
				elem.val( "");
				setDiscount(0, fieldid);
				setSurcharge(0, fieldid);
		});
	}
	else {
		setDiscount(0, fieldid);
	}
}

function setDiscount(amount, fieldid) {
	eval('field' + fieldid + 'discount=' + amount);
	if (JevrFees && document.updateattendance) {
		JevrFees.calculate(document.updateattendance);
	}
}

function setSurcharge(amount, fieldid) {
	eval('field' + fieldid + 'surcharge=' + amount);
	if (JevrFees && document.updateattendance) {
		JevrFees.calculate(document.updateattendance);
	}
}

var JevrConditionFieldState = {
	fields: new Object(),
	append: function (toAppend) {
		for (var key in toAppend)
			this.fields[key] = toAppend[key];
	},
	changeState: function (elem, name) {
		jQuery(elem).find("input[type=radio]").each(function (index, boolel) {
			var $boolel = jQuery(boolel);
			var indivcount = "";
			if ($boolel.parent().prop("tagName").toLowerCase() =="span" ){
				indivcount = $boolel.parent().attr('id').replace("rsvp_" + name + "_span_", "");
			}
			else {
				indivcount = $boolel.parent().parent().attr('id').replace("rsvp_" + name + "_span_", "");
			}
			var fieldname = (indivcount != "") ? name + "_" + indivcount : name;
			if (boolel.checked && JevrConditionFieldState.fields[fieldname]) {
				JevrConditionFieldState.fields[fieldname].value = boolel.value;
				//alert(boolel.value + " \n"+name+"  \n"+fieldname+"\n "+elem.innerHTML+"\n"+JevrConditionFieldState.fields.get(fieldname)+"\n"+JevrConditionFieldState.fields.get(fieldname).value);
			}
		});
		// Change State may not be called in the correct sequence of events so we call it again to be sure
		if (JevrFees && document.updateattendance) {
			JevrFees.calculate(document.updateattendance);
		}
	},
	// new guest field has been added so we need a new condition state field
	addStates: function (guestid) {
		// NOT a jQuery array
		for (var index in JevrConditionFieldState.fields) {
			var field =  JevrConditionFieldState.fields[index];
			// is it a indiv/guest field
			// is this the template state field
			// and does this guest field not exist ?
			//alert(field.peruser + " "+field.guestcount);
			if (field.peruser > 0 && field.guestcount == 'xxxyyyzzz' && !JevrConditionFieldState.fields[field.name + "_" + guestid]) {
				// must clone to not change the original
				// see http://stackoverflow.com/questions/122102/what-is-the-most-efficient-way-to-clone-an-object
				// http://api.jquery.com/jQuery.extend/
				var newfield = jQuery.extend(true, {}, field);
				newfield.guestcount = guestid;
				var newfieldname = newfield.name + "_" + guestid;
				// this uses the text newfieldname instead of the value of the variable!
				//JevrConditionFieldState.fields.extend( {newfieldname : newfield} );
				//Hash.set(JevrConditionFieldState.fields, newfieldname, newfield);
				JevrConditionFieldState.fields[newfieldname] =  newfield;
			}
		}
	},
	isVisible: function (name, guestid, requiredstate) {
		// TODO : This could bubble up to parent fields!
		if (JevrConditionFieldState.fields[name + "_" + guestid] || JevrConditionFieldState.fields[name]) {
			var conditionstatefield = JevrConditionFieldState.fields[name + "_" + guestid] ? JevrConditionFieldState.fields[name + "_" + guestid] : JevrConditionFieldState.fields[name];
			if (guestid == 0 && conditionstatefield.peruser == 2) {
				return 0;
			}
			if (conditionstatefield.value == requiredstate) {
				return 1;
			}
		}
		return 0;
	}
}

var JevrConditionalFields = {
	fields: new Array(),
	setup: function (firstpass) {
		jQuery(JevrConditionalFields.fields).each(function (cfindex, cf) {
			var $cfels = jQuery('.rsvp_' + cf.cf);
			if ($cfels && $cfels.length >= 1) {
				if ($cfels.length == 1) {
					// no guests
					// TR version - doesn't support guests'
					var $cfel = jQuery('.param' + cf.cf);
					if ($cfel.length) {
						$cfel.find("input[type=radio]").each(function (rindex, radel) {
							$radel = jQuery(radel);
							$radel.off('click', JevrConditionalFields.setup);
							$radel.on('click', JevrConditionalFields.setup);

							// reveal initially where valid - hidden by default in PHP
							if ($radel.val() == cf.cfvfv) {
								if ($radel.prop("checked")) {
									jQuery('.param' + cf.name).removeClass("conditionalhidden");
								}
								else {
									jQuery('.param' + cf.name).addClass("conditionalhidden");
								}
							}
						});
					}
				}
				else {
					$cfels.each(function (cfelindex, cfel) {
						jQuery(cfel).find("input[type=radio]").each(function (rindex, radel) {
							$radel = jQuery(radel);
							$radel.off('click', JevrConditionalFields.setup);
							$radel.on('click', JevrConditionalFields.setup);

							// temp fields - we don't process'
							if (jQuery(cfel).hasClass("paramtmpl")) return;
							
							var radelid = $radel.prop("id").replace("xmlfile_"+cf.cf+"_", "");
							radelid = radelid.substring(0,radelid.length-1);
							
							if (jQuery(cfel).hasClass("rsvpparam"+radelid)){
								var dependentFields = jQuery(".rsvp_"+cf.name);
								if ($radel.val() == cf.cfvfv){
									if ($radel.prop("checked")) {
										dependentFields.each (function (depindex, depfield){
											$depfield = jQuery(depfield);
											if ($depfield.hasClass("rsvpparam"+radelid)){
												$depfield.removeClass("conditionalhidden");
												// and the label
												if (!$depfield.hasClass("hideparam")) {
													jQuery('.param' + cf.name).removeClass("conditionalhidden");
												}
											}
										});
									}
									else {
										jQuery(dependentFields).each(function (dindex, depfield) {
											$depfield = jQuery(depfield);
											if ($depfield.hasClass("rsvpparam" + radelid)) {
												$depfield.addClass("conditionalhidden");
												// and the label
												if (!$depfield.hasClass("hideparam")) {
													jQuery('.param' + cf.name).addClass("conditionalhidden");
												}
											}
										});
									}
								}
							}
						});
					});

				}
			}

		});

		// recalculate the fees if appropriate
		if (JevrFees) {
			JevrFees.calculate(document.updateattendance);
		}

	},
	countVisible: function (field) {
		// if field has price function then this takes care of visible fields
		if (field.price) {
			return 1;
		}
		// make sure field id doesn't have xmlfile_ at the start of its name
		fieldid = field.name.replace("xmlfile_", "");
		var visiblefieldcount = 0;

		var isFieldConditional = false;

		// scan through the condition fields 
		jQuery(JevrConditionalFields.fields).each(function (cindex, cf) {
			// We matched our field in the conditional triggers
			if (cf.name == fieldid) {
				isFieldConditional = true;
				// Find the conditional field triggers in the DOM
				var $cfels = jQuery('.rsvp_' + cf.cf);
				if ($cfels && $cfels.length >= 1) {
					if (field.peruser == 0) {
						// no guests
						var $cfel = jQuery('.param' + cf.cf);
						if ($cfel.length) {
							// some might be spans etc so find the embedded radio buttons
							$cfel.find("input[type=radio]").each(function (rindex, radel) {
								// this is the one we are looking for with the value that matches the condition value and its checked
								if (radel.value == cf.cfvfv && radel.checked) {
									visiblefieldcount += 1;
								}

							});
						}
					}
					else {
						$cfels.each(function (cfelindex, cfel) {
							var $cfel = jQuery(cfel);
							// temp fields - we don't process'
							if ($cfel.hasClass("paramtmpl"))
								return;
							// we don't process the disabled first element
							if ($cfel.hasClass("disabledfirstparam"))
								return;

							// some might be spans etc so find the embedded radio buttons
							$cfel.find("input[type=radio]").each(function (rindex, radel) {
								// this is the one we are looking for with the value that matches the condition value and its checked
								if (radel.value == cf.cfvfv && radel.checked){
								
									var radelid = radel.id.replace("xmlfile_"+cf.cf+"_", "");
									radelid = radelid.substring(0,radelid.length-1);

									if (radelid == "p"){
										// in this case we have a radio button trigger that is a group field
										// This has a count of 0 of course since its the first entry and we will therefore match all dependents
										radelid = "";
									}

									//if ($cfel.hasClass("rsvpparam"+radelid)){
										var dependentFields = jQuery(".rsvp_"+cf.name );
										//countfields = 0;
										dependentFields.each (function (depidx, depfield){
											if (JevrConditionFieldState.isVisible(cf.cf, radelid, cf.cfvfv)) {
												// we don't process the disabled first element
												if (jQuery(depfield).hasClass("disabledfirstparam")) return;
												// temp fields - we don't process'
												if (jQuery(depfield).hasClass("paramtmpl")) return;
												// Must be on the correct 'guest tab'
												if (!jQuery(depfield).hasClass("rsvpparam"+radelid)) return;
												visiblefieldcount +=1;
											}
											//countfields++;
											return;
											// This was WRONG!
											if (jQuery(depfield).hasClass("rsvpparam"+radelid)){
												visiblefieldcount +=1;
											}
										});										
									//}

								}
							});
						});
					}
				}
			}
		});
		if (!isFieldConditional) {
			var dependentFields = jQuery(".rsvp_" + fieldid);
			dependentFields.each(function (dindex, depfield) {
				depfield = jQuery(depfield);
				// we don't process the disabled first element
				if (depfield.hasClass("disabledfirstparam"))
					return;
				// temp fields - we don't process'
				if (depfield.hasClass("paramtmpl"))
					return;
				//	if (depfield.hasClass("rsvpparam"+radelid)){
				visiblefieldcount += 1;
			});
		}
		return visiblefieldcount;
	},
	isVisible: function (fieldname, individual) {
		// make sure field id doesn't have xmlfile_ at the start of its name
		fieldid = fieldname.replace("xmlfile_", "");
		var visiblefieldcount = 0;

		var isFieldConditional = false;

		// scan through the condition fields 
		var isVisible = false;
		var matchedConditionalFields = false;
		jQuery(JevrConditionalFields.fields).each(function (cfindex, cf) {
			// We matched our field in the conditional triggers
			if (cf.name == fieldid) {
				matchedConditionalFields = true;
				isVisible = JevrConditionFieldState.isVisible(cf.cf, individual, cf.cfvfv);
			}
		});
		if (matchedConditionalFields) {
			return isVisible;
		}
		else {
			// in this case no conditions!
			return true;
		}
	}
}

jQuery(document).ready(function () {
	if (JevrConditionalFields) {
		JevrConditionalFields.setup(true);
	}
	//moveEmailFieldDown();
})

function updateReminder() {
	var remurl = jQuery(document.jevreminderform).prop('action');

	var requestObject = new Object();
	requestObject.error = false;
	requestObject.formdata = jQuery(document.jevreminderform).formToJson();

	var jSonRequest = jQuery.ajax({
		type : 'POST',
		dataType : 'json',
		url : remurl,
		data : {'json':JSON.stringify(requestObject)},
		contentType: "application/x-www-form-urlencoded; charset=utf-8",
		scriptCharset: "utf-8"
		})
	.done(function(json){
		if (!json){
			// TODO make these strings translateable
			alert(JevRsvpLanguage.translate("JEV_COULD_NOT_RECORD_REMINDER"));
		}
		if (json.error) {
			try {
				eval(json.error);
			}
			catch (e) {
				alert('could not process error handler');
			}
		}
		else {
			if (json.message) {
				alert(json.message);
			}
		}
	})
	.fail( function( jqxhr, textStatus, error){
		alert('Something went wrong...' + textStatus + ", " + error);
	});
}

jQuery.fn.formToJson =  function(){
		var json = {};
		jQuery(this).find('input, textarea, select').each(function(index,el){
			var name = el.name;
			var value = el.value;
			if (value === false || !name || el.disabled) return;
			// multi selects
			if (name.indexOf('[]')>=0 && (el.tagName.toLowerCase() =='select' ) && el.multiple==true){
				name = name.substr(0,name.length-2);
				if (!json[name]) json[name] = [];
				jQuery(el).find('option').each(function(eldx, opt){
					if (opt.selected ==true) json[name].push(opt.value);
				});
			}
			else if (name.indexOf('[]')>=0 && (el.type=='radio' || el.type=='checkbox') ){
				if (!json[name]) json[name] = [];
				if (el.checked==true) json[name].push(value);
			}
			else if (el.type=='radio' || el.type=='checkbox'){
				//alert(el+" "+el.name+ " "+el.checked+ " "+value);
				if (el.checked==true) {
					json[name] = value;
				}
			}
			else json[name] = value;
		});
		return json;
	}
	
function toggleSessionAccessMessage() {
	if (document.getElement("[name=custom_rsvp_sessionaccess]").value > 0) {
		jQuery("rsvp_sessionaccessmessage").css("display","block");
	}
	else {
		jQuery("rsvp_sessionaccessmessage").css("display","none");
	}
}

function moveEmailFieldDown() {
	if (jQuery('#jevattend_email').length) {
		// get the TR grand parent
		var elem = jQuery('#jevattend_email').parent().parent();
		var target = jQuery('#rsvppro_admintable').find('tr');
		if (target.length) {
			elem.inject(target[0], 'after');
		}
	}
}

/**
   * Decimal adjustment of a number.
   *
   * @param {String}  type  The type of adjustment.
   * @param {Number}  value The number.
   * @param {Integer} exp   The exponent (the 10 logarithm of the adjustment base).
   * @returns {Number} The adjusted value.
   *
   * See https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Math/round
   */
  function decimalAdjust(type, value, exp) {
    // If the exp is undefined or zero...
    if (typeof exp === 'undefined' || +exp === 0) {
      return Math[type](value);
    }
    value = +value;
    exp = +exp;
    // If the value is not a number or the exp is not an integer...
    if (isNaN(value) || !(typeof exp === 'number' && exp % 1 === 0)) {
      return NaN;
    }
    // Shift
    value = value.toString().split('e');
    value = Math[type](+(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp)));
    // Shift back
    value = value.toString().split('e');
    return +(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp));
  }
