var typenamemap = {};

sortablePeopleClass = new Class({
	Implements: Options,

	options: {
		cleanname: 'false'
	},

	initialize: function(options){
		this.setOptions(options);
	},

	setup:function (cleanname){
		this.cleanname = cleanname;
		this.ulname = 'sortablePeople_'+this.cleanname;
		this.cpname = "custom_person_"+this.cleanname;
		new Sortables(this.ulname,{"onComplete":function(el) { this.fieldsHaveReordered(el, this)}.bind(this)});
		var uls = $(this.ulname);
		var lis = uls.getChildren();
		lis.each(function(item, i){
			this.copyTrash(item);
		},this);
	},
	copyTrash:function(item){
		var trashimage = $('trashimage');
		var child = trashimage.clone();
		child.style.display="inline";
		child.style.marginLeft="5px";
		child.style.lineHeight = item.style.lineHeight = "16px";
		item.style.backgroundImage="none";
		item.style.listStyleType="none";
		item.style.paddingLeft="0px";
		//item.appendChild(child);
		child.inject(item,"top");
		this.setupTrashImage(child);
	},
	fieldsHaveReordered:function(targetNode, sortable){
		// Now rebuild the select list items
		var custom_person = $(sortable.cpname);
		if (custom_person){
			var options = custom_person.getChildren();

			// new dummy selectlist
			var selectList = new Element('select');

			options.each(function (item,i){
				selectList.appendChild(item);
			});

			var uls = $(sortable.ulname);
			var lis = uls.getChildren();
			lis.each(function(item, i){
				selectList.getChildren().each(function(opt,j){
					if (opt.id==item.className+"option"){
						custom_person.appendChild(opt);
						opt.selected = true;
					}
				});

			});
		}		

	}.bind(this),
	setupTrashImage:function(item){
		item.addEvent('mousedown',function(event){
			event = new Event(event);
			try {
				event.stop();
			}
			catch (e) {
				event.stopImmediatePropagation();
			}
			if (!confirm(peopleDeleteWarning)) return;
			var id = event.target.parentNode.className;
			// remove the item from the li list
			Element.dispose(event.target.parentNode);
			// remove the item from the select list
			var option = $(id+"option");
			if (option) Element.dispose(option);

			var custom_person = $(this.cpname);
			if (custom_person){
				var options = custom_person.getChildren();
			}


		}.bind(this));
	},
	selectPerson:function (url){

		SqueezeBox.initialize({});
		SqueezeBox.setOptions(SqueezeBox.presets,{'handler': 'iframe','size': {'x': 750, 'y': 500},'closeWithOverlay': 0, 'onOpen' : function(){SqueezeBox.overlay['removeEvent']('click', SqueezeBox.bound.close)}});
		SqueezeBox.url = url;

		SqueezeBox.setContent('iframe', SqueezeBox.url );
		return;// SqueezeBox.call(SqueezeBox, true);

	}
});

// Use ONLY part of this class from managed people - it should NOT be used for events!
var sortablePeople = {
	selectThisPerson:function (personid, elem, typename){
		var sp = typenamemap[typename];
		var duplicateTest = document.getElement("#"+sp.ulname+" .sortablepers"+personid);
		if (duplicateTest) {
			alert(jevpeople.duplicateWarning);
			SqueezeBox.close();
			return false;
		}
		var title = elem.innerHTML;
		var custom_person = $(sp.cpname);
		var opt = new Element('option',{value:personid,id:"sortablepers"+personid+"option"});
		if (custom_person){
			custom_person.appendChild(opt);
		}
		opt.text = title + " ("+typename+")";
		opt.selected = 1;
		// No do the visible list item too
		var uls = $(sp.ulname);
		var li = new Element('li',{'class':"sortablepers"+personid});
		li.appendText(opt.text);
		if (uls){
			uls.appendChild(li);
			sp.copyTrash(li);

			// reset the sortable list
			new Sortables('sortablePeople',{"onComplete":sp.fieldsHaveReordered});
		}

		// If actually selecting a person for a menu item we do something different:
		var menuperson = $('menuperson');
		if (menuperson){
			menuperson.value += "jevp:"+personid+",";
		}

		SqueezeBox.close();
		return false;
	}
};	
