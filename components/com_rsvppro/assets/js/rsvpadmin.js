var JevRsvpLanguage = {
	strings: new Object(),
	translate:function(val){
		if (val in JevRsvpLanguage.strings){
			return JevRsvpLanguage.strings[val];
		}
		else {
			return "?? "+val+" ??";
		}
	}
}

var rsvpjsonactive = false;
var cancelSearch = true;
var rsvptimeout=false;
var ignoreSearch=false;
var canchangeuser=false;
var lastrequest = "";
var execcount = 0;

function findUser(e,elem, checkurl){
  	if (rsvpjsonactive) return;
	if (lastrequest == elem.value) return;

	if (ignoreSearch) return;
	if ($('updateattendance').atdee.value==0){
		canchangeuser=true;
	}
        if (canchangeuser==false){
            canchangeuser=confirm(JevRsvpLanguage.translate("JEV_DO_YOU_WANT_TO_CHANGE_USER"));
            if (!canchangeuser) {
                elem.value="";
                return;
            }
        }
	var key = 0;
	if (window.event){
		key = e.keyCode;
	}
	else if (e.which){
		key = e.which;
	}
	if (elem.value.length == 0 || key==8 || key==46){
		// clearing
		rsvpClearMatches();
		currentSearch = "";
		return;
	}

	minlength=2;

	if (elem.value.length>=minlength || elem.value=="*"){
		var requestObject = new Object();
		requestObject.error = false;
		requestObject.token = jsontoken;
		requestObject.client = jsonclient;
		requestObject.task = "checkTitle";
		requestObject.title = elem.value;
		requestObject.ev_id = document.updateattendance?document.updateattendance.ev_id.value : 0;

		currentSearch = elem.value;

		if (rsvptimeout) {
			clearTimeout(rsvptimeout);
		}

		execcount ++;
		
		rsvpjsonactive = true;
		var jSonRequest = jQuery.ajax({
			type : 'GET',
			dataType : 'json',
			url : checkurl,
			data : {'json':JSON.stringify(requestObject)},
			contentType: "application/x-www-form-urlencoded; charset=utf-8",
			scriptCharset: "utf-8"
			})
		.done(function(json){
			cancelSearch = false;
			rsvpjsonactive = false;
			if (json.error){
				try {
					eval(json.error);
				}
				catch (e){
					alert('could not process error handler');
				}
			}
			else {
				// If have started another search already then cancel this one
				if (cancelSearch) {
					return;
				}
				var rsvpmatches = document.getElement("#rsvpmatches");
				//alert(json.timing);
				if (json.titles.length>0){
					lastrequest = elem.value;
					rsvpClearMatches();
					for (var jp=0;jp<json.titles.length;jp++){
						// If have started another search already then cancel this one
						if (cancelSearch) {
							return;
						}
						var option = new Element('div', {id:"rsvp_pot_"+json.titles[jp]["id"], 'style':'margin:0px;padding:2px;cursor:pointer;'});
						//option.appendText(json.titles[jp]["name"]+" ("+json.titles[jp]["username"]+")" + " "+json.timing+ " "+execcount+" " +url);
						option.appendText(json.titles[jp]["name"]+" ("+json.titles[jp]["username"]+")" );
						option.inject(rsvpmatches);
						option.addEvent('mousedown', setAttendee.bind(option));
													}
					rsvpmatches.style.display='block';

				}
				else {
					rsvpClearMatches();
				}

				// If have started another search already then cancel this one
				if (cancelSearch) {
					return;
				}
			}
		})
		.fail( function( jqxhr, textStatus, error){
			if (ignoreSearch) return;
			rsvpjsonactive = false;
			alert('Something went wrong... ')
			rsvpClearMatches();
		});
	}
}

function rsvpClearMatches(){
	if (rsvptimeout) {
		clearTimeout(rsvptimeout);
	}
	var rsvpmatches = document.getElement("#rsvpmatches");
	rsvpmatches.innerHTML = "";
	rsvpmatches.style.display='none';
}

function setAttendee(event){
	var oldid = this.id;
	var newid = this.id.replace("rsvp_pot_","");
	var attendee = $('attendee');
	attendee.innerHTML = this.innerHTML;
	attendee = $('user_id');
	attendee.value = newid;

	var nameattendeenames = $$(".attendeename");
	nameattendeenames.each(function(attendeename){
		var fieldid = attendeename.id.replace('params','');
		if (fieldid.indexOf("_xxx")<0 && fieldid.indexOf("field")==0 && fieldid.indexOf("_")>0){
			var attendee = $('attendee');
			attendeename.value = attendee.innerHTML.replace(/ \((.*)\)/g, '');
		}
	});

	document.updateattendance.jev_name.value = "";
	rsvpClearMatches();
}

