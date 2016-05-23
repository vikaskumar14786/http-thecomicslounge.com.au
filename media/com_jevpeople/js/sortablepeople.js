var trashimages = 0;
var sortablePeople = {
	setup: function() {
		new Sortables('sortablePeople', {"onComplete": sortablePeople.fieldsHaveReordered});
		var uls = $('sortablePeople');
		var lis = uls.getChildren();
		lis.each(function(item, i) {
			sortablePeople.copyTrash(item);
		}, this);
		/*
		 var trashitems = $$('.sortabletrash');
		 trashitems.each(function(item, i){
		 sortablePeople.setupTrashImage(item);
		 },this);
		 */
	},
	copyTrash: function(item) {
		var trashimage = $('trashimage');
		if (!trashimage){
			trashimage = $('trashimage_'+item.className);
		}
		var child = trashimage.clone();
		child.style.display = "inline";
		child.style.marginLeft = "5px";
		child.style.lineHeight = item.style.lineHeight = "16px";
		child.id = "trashimage" + trashimages;
		item.style.backgroundImage = "none";
		item.style.listStyleType = "none";
		item.style.paddingLeft = "0px";
		child.id = child.id + trashimages;
		//item.appendChild(child);
		child.inject(item, "top");
		sortablePeople.setupTrashImage(child);
		trashimages++;
	},
	fieldsHaveReordered: function(targetNode) {
		// Now rebuild the select list items
		var custom_person = $("custom_person");
		if (custom_person) {
			var options = custom_person.getChildren();

			// new dummy selectlist
			var selectList = new Element('select');

			options.each(function(item, i) {
				selectList.appendChild(item);
				//item.remove();
			});

			var uls = $('sortablePeople');
			var lis = uls.getChildren();
			lis.each(function(item, i) {
				selectList.getChildren().each(function(opt, j) {
					if (opt.id == item.id + "option") {
						custom_person.appendChild(opt);
						opt.selected = true;
					}
				});

			});
		}
		else {
			var menuperson = $("menuperson");
			var compatmenuperson = $("compat_menuperson");
			if (menuperson) {
				menuperson.value = "";
				compatmenuperson.value = "";
				var uls = $('sortablePeople');
				var lis = uls.getChildren();
				lis.each(function(item, i) {

					var id = item.id.replace("sortablepers", "");
					menuperson.value += 'jevp:' + id + ",";
					compatmenuperson.value += 'jevp:' + id + ",";
				});

			}

		}

	},
	setupTrashImage: function(item) {
		item.addEvent('mousedown', function(event) {
			if (!event) {
				event = new Event(event);
			}
			try {
				event.stop();
			}
			catch (e) {
				event.stopImmediatePropagation();
			}
			if (!confirm(peopleDeleteWarning))
				return;
			try {
				// mootools
				var id = event.target.parentNode.id;
				var typeid = event.target.parentNode.get('class');
				// remove the item from the li list
				event.target.parentNode.dispose();
			}
			catch (e) {
				var id = event.explicitOriginalTarget.parentNode.id;
				var typeid = event.explicitOriginalTarget.parentNode.get('class');
				// remove the item from the li list
				event.explicitOriginalTarget.parentNode.remove();
			}
			// remove the item from the select list
			var option = $(id + "option");
			if (option) {
				try {
					option.remove();
				}
				catch (e) {
					option.dispose();
				}
			}

			//make the select button appear again for a button per resource config
			if (document.getElement(".jevplugin_people" +  typeid.replace("type_","")))
				document.getElement(".jevplugin_people" +  typeid.replace("type_","")).setStyle('display', 'table-row');

			//getting rid of the filter constrain for the general button config
			if (typeof jevExcludedTypes !== 'undefined') {
				jevExcludedTypes.each(function(resourceTypeId, index) {
					if (resourceTypeId == typeid)
						delete jevExcludedTypes[index];
				});
			}


			var menuperson = $("menuperson");
			id = id.replace("sortablepers", "");
			if (menuperson)
				menuperson.value = menuperson.value.replace('jevp:' + id + ",", "");
		});
	},
	selectThisPerson: function(personid, elem, typename, typeid) {
		var duplicateTest = $("sortablepers" + personid);
		if (duplicateTest) {
			alert(jevpeople.duplicateWarning);
			SqueezeBox.close();
			return false;
		}
		var title = elem.innerHTML;
		var custom_person = $('custom_person');
		var opt = new Element('option', {value: personid, id: "sortablepers" + personid + "option"});
		if (custom_person) {
			custom_person.appendChild(opt);
		}
		opt.text = title + " (" + typename + ")";
		opt.selected = 1;
		// No do the visible list item too
		var uls = $('sortablePeople');
		var li = new Element('li', {id: "sortablepers" + personid, 'class': 'type_'+typeid});
		li.appendText(opt.text);
		if (uls) {
			uls.appendChild(li);
			sortablePeople.copyTrash(li);

			// reset the sortable list
			new Sortables('sortablePeople', {"onComplete": sortablePeople.fieldsHaveReordered});
		}

		// If actually selecting a person for a menu item we do something different:
		var menuperson = $('menuperson');
		if (menuperson) {
			menuperson.value += "jevp:" + personid + ",";
		}

		//We check if only one item of this resource can be added and disable the select button
		if (typeof jevOnlyOnePerType !== 'undefined')
		{
			jevOnlyOnePerType.each(function(resourceTypeId, index) {
				if (typeid == resourceTypeId)
				{
					if (document.getElement(".jevplugin_people" + resourceTypeId))
						document.getElement(".jevplugin_people" + resourceTypeId).setStyle('display', 'none');
					jevExcludedTypes.push(resourceTypeId);
				}
			});
		}
		SqueezeBox.close();
		return false;
	},
	selectPerson: function(url, w, h) {

		SqueezeBox.initialize({});
		SqueezeBox.setOptions(SqueezeBox.presets, {'handler': 'iframe', 'size': {'x': w, 'y': h}, 'closeWithOverlay': 0, 'onOpen' : function(){SqueezeBox.overlay['removeEvent']('click', SqueezeBox.bound.close)}});

		if (typeof jevOnlyOnePerType !== 'undefined')
		{
			jevExcludedTypes.each(function(typeid, index) {

				url = url + "&exclude[]=" + typeid;
			});
		}

		SqueezeBox.url = url;

		SqueezeBox.setContent('iframe', SqueezeBox.url);
		return;// SqueezeBox.call(SqueezeBox, true);

	},
	exists: function() {
		return l
	}
}
