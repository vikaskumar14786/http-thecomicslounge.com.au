
var regTabs = new Object();
regTabs.needtoSetupAttendeeNames = false;

regTabs.initialise = function (element, options) {
	this.options = options;

	this.el = jQuery("#" + element);
	this.elid = element;

	this.titles = jQuery('#' + this.elid + ' ul.nav-tabs li a');

	this.activeTitle = false;

	this.titles.each(function (index, title) {
		jQuery(title).on('click', function (e) {
			e.preventDefault();
			regTabs.activate(jQuery(this));
		});
	});


	this.panelHeight = this.el.height() - (jQuery(this.titles[0]).height() + 4);
	if (this.panelHeight < 0) {
		// This causes problems in MSIE7
		//this.panelHeight = "50";
	}

	// We may need to set up the initial attendee names but could not do so until the tabs were initialised
	if (this.needtoSetupAttendeeNames) {
		this.setInitialAttendeeNames();
	}

	if (this.options.activateOnLoad != 'none')
	{
		if (this.options.activateOnLoad == 'tab0')
		{
			this.activate(this.titles[0], 0);
		}
		else
		{
			this.activate(this.options.activateOnLoad, 0);
		}
	}
}

regTabs.addTab = function (title, label, count) {
	//the new title
	var newTitle = jQuery('<li>', {
		title: title
	});
	var newTitleLink = jQuery('<a href="#attendeetab' + (count-1)  + '" data-toggle="tab">' + label + '</a>');
	newTitle.append(newTitleLink);

	jQuery('#' + this.elid + ' ul.nav-tabs').append(newTitle);
	this.titles = jQuery('#' + this.elid + ' ul.nav-tabs li a');

	newTitleLink.on('click', function (e) {
		e.preventDefault();
		regTabs.activate(jQuery(this));
	});

	//Now for the form content!
	var elems = jQuery(".paramtmpl");
	elems.each(function (index, item) {
		item = jQuery(item);
		// do not clone elements whose parents are also to be cloned - otherwise we may get duplicate elements
		if (item.parent().hasClass("paramtmpl") || item.parent().parent().hasClass("paramtmpl")) {
			return;
		}
		var clone = item.clone();
		// radio boxes etc. have their own ids set and cloning will drop these to avoid double ids with the same values so we replace the innerHTML
		if ((!clone.attr('name') || !clone.attr('id')) && item.html() != "") {
			var html = item.html();
			// For the radio lists etc only
			html = html.replace(/\'xxxyyyzzz\'/g, (count - 1));
			html = html.replace(/xxxyyyzzz/g, (count - 1));
			html = html.replace(/paramtmpl_/g, "");
			html = html.replace(/paramtmpl/g, "");
			// fix the ID for radio fields!
			clone.html(html.replace(/_xxx/g, "_" + (count - 1)));
		}
		clone.removeClass("paramtmpl");
		clone.addClass("rsvpparam");
		clone.addClass("rsvpparam" + (count - 1));
		// replace id and names
		if (item.attr('id')) {
			var cloneid = item.attr('id').replace("xxxyyyzzz", (count - 1));
			clone.attr('id', cloneid.replace("xxx", (count - 1)));
		}
		if (item.attr('name')) {
			clone.attr('name', item.attr('name').replace("paramtmpl_", ""));
		}
		// insert the clone in the DOM
		clone.insertAfter(item);

		// Clear up Chosen Mess
		if (jQuery(clone).find('.chzn-done').length){
			jQuery(clone).find('.chzn-container').remove();
			var chosenElements = jQuery(clone).find('.chzn-done');
			jQuery(clone).find('.chzn-done').css("display", "initial");
			jQuery(clone).find('.chzn-done').removeClass('chzn-done');
			chosenElements.chosen({
				disable_search_threshold : 10,
				allow_single_deselect : true
			});
		}

	});

	// scroll into view - see
	newTitleLink[0].scrollIntoView();

	// activate the new tab
	this.activate(newTitleLink);
}

regTabs.activate = function (activetab) {
	if (jQuery.type(activetab) == 'string')
	{
		myTab = jQuery('#' + this.elid + ' ul a[href=#' + activetab + ']');
		activetab = myTab;
		this.activeTitle = myTab;
	}

	if (jQuery.type(activetab) == 'object')
	{
		activetab.tab('show');
		this.activeTitle = activetab;
		var activeTabId = 0;
		this.titles.each(function (index, item) {
			if (jQuery(item).parent().attr('title') == jQuery(activetab).parent().attr('title')) {
				activeTabId = parseInt(activetab.attr('href').replace("#attendeetab",""));
			}
		});
		//this.titles.removeClass('active');
		activetab.parent().addClass('active');
		this.activeTab = activetab;

		var pane = jQuery("#registration-tab-pane");
		if (activeTabId == 0) {
			// first find the rows that only apply to the first attendee and display these
			var elems = pane.find(".type0param");
			elems.each(function (index, elem) {
				jQuery(elem).removeClass("type0paramHidden")
			});

			// next find the rows that apply to the second and over and display there
			elems = pane.find(".type2param");
			elems.each(function (index, elem) {
				jQuery(elem).addClass("type2paramHidden")
			});

		}
		else {
			// first find the rows that only apply to the first attendee and hide these
			elems = pane.find(".type0param");
			elems.each(function (index, elem) {
				jQuery(elem).addClass("type0paramHidden")
			});

			// next find the rows that apply to the second and over and display there
			elems = pane.find(".type2param");
			elems.each(function (index, elem) {
				jQuery(elem).removeClass("type2paramHidden")
			});

		}

		// next find the rows that apply to the second and over and display there
		elems = pane.find(".type1param");
		if (pane.find(".type2param").length > 0) {
			// this doesn't work in Chrome - must be a Mootools bug!'
			//  elems.extend(pane.getElements(".type2param"));
			pane.find(".type2param").each(function (index, elem) {
				elems.push(elem);
			});
		}
		elems.each(function (index, elem) {
			var paramelements = jQuery(elem).find('.rsvpparam');
			var e = 0;
			paramelements.each(function (pindex, pelem) {
				if (jQuery(pelem).hasClass("rsvpparam" + activeTabId)) {
					jQuery(pelem).removeClass("hideparam");
				}
				else {
					jQuery(pelem).addClass("hideparam");
				}
				e++;
			})
		});

		if (jQuery("#killguest").length) {
			if (activeTabId > 0)
				jQuery("#killguest").css('display', 'block');
			else
				jQuery("#killguest").css('display', 'none');
		}
		else if (pane.find(".nav-tabs").length) {
			pane.find(".nav-tabs").style.display = "none";
		}
	}
	if (JevrConditionalFields) {
		JevrConditionalFields.setup(false);
	}

}

regTabs.removeActiveTab = function () {
	this.removeTab(this.activeTab);
}

regTabs.removeTab = function (tabToGo) {
	if (this.activeTab == tabToGo)
	{
		this.activate(jQuery(this.titles[0]));
		//this.activate(this.titles[0].href.substr(this.titles[0].href.indexOf("#")+1));
		//alert(this.titles[1].href.substr(this.titles[1].href.indexOf("#")+1));
		//this.activate(this.titles[1].href.substr(this.titles[1].href.indexOf("#")+1));
	}
	tabToGo.parent().remove();
	this.titles = jQuery('#' + this.elid + ' ul.nav-tabs li a');

	// Now remove the form content!
	// find the relevant form entries
	var tabcounter = parseInt(tabToGo.attr('href').replace("#attendeetab",""));
	var paramelements = jQuery("#registration-tab-pane .rsvpparam" +tabcounter);
	
	var e = 0;
	paramelements.each(function (pindex, pelem) {
		//jQuery(pelem).parent().removeChild(pelem);
		jQuery(pelem).remove();
		e++;
	});

	return;

	this.activate(this.titles[0], 0);

}

regTabs.setInitialAttendeeNames = function () {
	if (!regTabs.titles){
		this.needtoSetupAttendeeNames = true;
		return;
	}
	var nameattendeenames = jQuery(".attendeename");
	var attendeecount = 1;
	nameattendeenames.each(function(atidx, attendeename){
		attendeename = jQuery(attendeename);
		var fieldid = attendeename.attr('id').replace('params', '').replace('xmlfile_', '');
		if (fieldid.indexOf("_xxx") < 0 && fieldid.indexOf("field") == 0 && fieldid.indexOf("_") > 0 && !attendeename.hasClass('disabledfirstparam')){
			var fieldid = fieldid.split("_");
			if (regTabs.titles[fieldid[1]]){
				if (attendeename.val() == ""){
					jQuery(regTabs.titles[fieldid[1]]).html(jQuery("#jevnexttabtitle").val().replace('xxx', attendeecount));
				}
				else {
					jQuery(regTabs.titles[fieldid[1]]).html(attendeename.val());
				}
			}
		}
		attendeecount++;
	});
}

function setAttendeeName(field) {
	try {
		if (regTabs.activeTitle && field.value != "") {
			jQuery(regTabs.activeTitle).html(field.value);
		}
	}
	catch (e) {
	}
}

		/*

		 mootabs.prototype.removeTab =  function(title){
		 if(this.activeTitle.title == title)
		 {
		 this.activate(this.titles[0]);
		 }
		 try {
		 this.activeTitle.remove();
		 }
		 catch (e){
		 this.activeTitle.dispose();
		 }

		 // Now remove the form content!
		 // find the relevant form entries
		 var pane = $("registration-tab-pane");
		 var paramelements = pane.getElements('.rsvpparam'+this.activeTab);
		 var e = 0;
		 paramelements.each(function(pelem){
		 pelem.parentNode.removeChild(pelem);
		 e++;
		 });

		 this.activate(this.titles[0], 0);

		 }

		 mootabs.prototype.next = function(){
		 var nextTab = this.activeTitle.getNext();
		 if(!nextTab) {
		 nextTab = this.titles[0];
		 }
		 this.activate(nextTab);
		 }

		 mootabs.prototype.setTitle =  function (title, label) {
		 $$('#' + this.elid + ' ul li').filterByAttribute('title', '=', title)[0].innerHTML = label;
		 }

		 mootabs.prototype.previous =  function(){
		 var previousTab = this.activeTitle.getPrevious();
		 if(!previousTab) {
		 previousTab = this.titles[this.titles.length - 1];
		 }
		 this.activate(previousTab);
		 }

		 });
		 }



		 */
