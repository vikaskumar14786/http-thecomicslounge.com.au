/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

/**
 * Global JCalPro object
 * 
 * used to bridge the gap between 2.5 and 3.0 (and maybe beyond, someday)
 * 
 */
window.JCalPro = window.JCalPro || {
	
	/**
	 * determines if we wish to force usage of jQuery
	 * 
	 * @var boolean
	 */
	useJQuery: ('undefined' != typeof jQuery)
	,
	
	/**
	 * attach a function to the load event
	 * 
	 * @param function
	 */
	onLoad: function(handler) {
		return JCalPro._switch(function(){
			return window.addEvent('load', handler);
		}, function(){
			return jQuery(document).ready(handler);
		});
	}
	,
	/**
	 * attach a click event
	 * 
	 * @param string
	 * @param function
	 */
	onClick: function(el, handler) {
		return JCalPro._switch(function(){
			return $(el).addEvent('click', handler);
		}, function(){
			return jQuery(el).click(handler);
		});
	}
	,
	/**
	 * set a single css style on an element
	 * 
	 * @param string
	 * @param string
	 * @param string
	 * 
	 * @return DOM element
	 */
	setStyle: function(el, style, value) {
		return JCalPro._switch(function(){
			return JCalPro.els(el).setStyle(style, value);
		}, function(){
			return jQuery(el).css(style, value);
		});
	}
	,
	/**
	 * set css styles on an element
	 * 
	 * @param string
	 * @param object
	 * 
	 * @return DOM element
	 */
	setStyles: function(el, styles) {
		return JCalPro._switch(function(){
			return JCalPro.els(el).setStyles(styles);
		}, function(){
			return jQuery(el).css(styles);
		});
	}
	,
	/**
	 * get an object that represents the size of the element
	 * 
	 * @param string
	 * 
	 * @return DOM element
	 */
	getSize: function(el) {
		var jq = function(){
			return {
				x: jQuery(el).width()
			,	y: jQuery(el).height()
			};
		};
		if (window == el) {
			return JCalPro._switch(function(){
				return window.getSize();
			}, jq);
		}
		return JCalPro._switch(function(){
			return JCalPro.el(el).getSize();
		}, jq);
	}
	,
	/**
	 * $ wrapper
	 * 
	 * WARNING: use carefully, this can have different results based on framework!
	 * 
	 * @param mixed
	 * 
	 * @return mixed
	 */
	$: function(sel) {
		return JCalPro._switch(function(){
			return $(sel);
		}, function(){
			// NOTE: not using $ here because noConflict mode may be on
			return jQuery(sel);
		});
	}
	,
	/**
	 * get a DOM element by id
	 * 
	 * @param string
	 * 
	 * @return DOM element
	 */
	id: function(sel) {
		return JCalPro._switch(function(){
			return document.id(sel);
		}, function(){
			var el = jQuery('#' + sel);
			return el.length ? el : null;
		});
	}
	,
	/**
	 * get a collection of DOM elements
	 * 
	 * @param string
	 * 
	 * @return DOM elements
	 */
	els: function(sel) {
		return JCalPro._switch(function(){
			return $$(sel);
		}, function(){
			var els = jQuery(sel);
			return els.length ? els : null;
		});
	}
	,
	/**
	 * get a single (first) DOM element
	 * 
	 * @param string
	 * 
	 * @return DOM element
	 */
	el: function(sel) {
		return JCalPro._switch(function(){
			return $($$(sel)[0]);
		}, function(){
			var els = jQuery(sel);
			if (!els.length) {
				return null;
			}
			return jQuery(els[0]);
		});
	}
	,
	/**
	 * find the first element matching the selector inside the given element
	 * 
	 * @param element
	 * @param selector
	 * 
	 * @return mixed
	 */
	getElement: function(el, sel) {
		try {
			return JCalPro._switch(function(){
				return $(el).getElement(sel);
			}, function(){
				sel = JCalPro.getElements(el, sel);
				if (!sel) {
					return null;
				}
				return sel.first();
			});
		}
		catch (err) {
			return null;
		}
	}
	,
	/**
	 * find the elements matching the selector inside the given element
	 * 
	 * @param elements
	 * @param selector
	 * 
	 * @return mixed
	 */
	getElements: function(el, sel) {
		return JCalPro._switch(function() {
			return $(el).getElements(sel);
		}, function() {
			el = jQuery(el);
			if (!el.length) {
				return null;
			}
			sel = el.find(sel);
			if (!sel.length) {
				return null;
			}
			return sel;
		});
	}
	,
	/**
	 * creates a new element
	 * 
	 * @param string
	 * @param object
	 * 
	 * @return element
	 */
	newElement: function(el, opts) {
		return JCalPro._switch(function() {
			var elem = new Element(el, opts);
			return elem;
		}, function() {
			return jQuery("<" + el + " />", opts);
		});
	}
	,
	/**
	 * find the parent element matching the selector
	 * 
	 * @param element
	 * @param selector
	 * 
	 * @return mixed
	 */
	closest: function(el, sel) {
		return JCalPro._switch(function(){
			return $(el).closest(sel);
		}, function(){
			return jQuery(el).closest(sel);
		});
	}
	,
	/**
	 * get an element's value
	 * 
	 * @param element
	 * 
	 * @return mixed
	 */
	getValue: function(el) {
		return JCalPro._switch(function(){
			return $(el).value;
		}, function(){
			return jQuery(el).val();
		});
	}
	,
	/**
	 * set an element's value
	 * 
	 * @param element
	 * 
	 * @return mixed
	 */
	setValue: function(el, value) {
		return JCalPro._switch(function(){
			return $(el).value = value;
		}, function(){
			return jQuery(el).val(value);
		});
	}
	,
	/**
	 * get an element's attribute
	 * 
	 * @param element
	 * @param attribute
	 * 
	 * @return mixed
	 */
	getAttribute: function(el, attr) {
		return JCalPro._switch(function(){
			return $(el).get(attr);
		}, function(){
			return jQuery(el).attr(attr);
		});
	}
	,
	/**
	 * set an element's attribute
	 * 
	 * @param element
	 * @param attribute
	 * @param value
	 * 
	 * @return mixed
	 */
	setAttribute: function(el, attr, value) {
		// TODO: support jQuery map in mootools?
		return JCalPro._switch(function(){
			return $(el).set(attr, value);
		}, function(){
			return jQuery(el).attr(attr, value);
		});
	}
	,
	/**
	 * remove an element's attribute
	 * 
	 * @param element
	 * @param attribute
	 * 
	 * @return mixed
	 */
	removeAttribute: function(el, attr) {
		return JCalPro._switch(function(){
			return $(el).removeAttribute(attr);
		}, function(){
			return jQuery(el).removeAttr(attr);
		});
	}
	,
	/**
	 * get an element's text
	 * 
	 * @param element
	 * 
	 * @return mixed
	 */
	getText: function(el) {
		return JCalPro._switch(function(){
			return $(el).get('text');
		}, function(){
			return jQuery(el).text();
		});
	}
	,
	/**
	 * set an element's text
	 * 
	 * @param element
	 * @param text
	 * 
	 * @return mixed
	 */
	setText: function(el, text) {
		return JCalPro._switch(function(){
			return $(el).set('text', text);
		}, function(){
			return jQuery(el).text(text);
		});
	}
	,
	/**
	 * get an element's html
	 * 
	 * @param element
	 * 
	 * @return mixed
	 */
	getHtml: function(el) {
		return JCalPro._switch(function(){
			return $(el).get('html');
		}, function(){
			return jQuery(el).html();
		});
	}
	,
	/**
	 * set an element's html
	 * 
	 * @param element
	 * @param html
	 * 
	 * @return mixed
	 */
	setHtml: function(el, html) {
		return JCalPro._switch(function(){
			return $(el).set('html', html);
		}, function(){
			return jQuery(el).html(html);
		});
	}
	,
	/**
	 * get the previous element
	 * 
	 * @param element
	 * @param selector
	 * 
	 * @return mixed
	 */
	getPrevious: function(el, prev) {
		return JCalPro._switch(function(){
			return prev ? $(el).getPrevious(prev) : $(el).getPrevious();
		}, function(){
			return prev ? jQuery(el).prev(prev) : jQuery(el).prev();
		});
	}
	,
	/**
	 * get the next element
	 * 
	 * @param element
	 * @param selector
	 * 
	 * @return mixed
	 */
	getNext: function(el, next) {
		return JCalPro._switch(function(){
			return next ? $(el).getNext(next) : $(el).getNext();
		}, function(){
			var n;
			if (next) {
				n = jQuery(el).next(next);
			}
			else {
				n = jQuery(el).next();
			}
			return jQuery.isEmptyObject(n) ? false : n;
		});
	}
	,
	/**
	 * get the parent element
	 * 
	 * @param element
	 * 
	 * @return mixed
	 */
	getParent: function(el) {
		return JCalPro._switch(function(){
			return $(el).getParent();
		}, function(){
			var p = jQuery(el).parent();
			return jQuery.isEmptyObject(p) ? false : p;
		});
	}
	,
	/**
	 * get the children elements
	 * 
	 * @param element
	 * 
	 * @return mixed
	 */
	getChildren: function(el) {
		return JCalPro._switch(function(){
			return $(el).getChildren();
		}, function(){
			return jQuery(el).children();
		});
	}
	,
	/**
	 * get the children elements
	 * 
	 * @param element
	 * 
	 * @return mixed
	 */
	getLast: function(el) {
		return JCalPro._switch(function(){
			try {
				var r = el.getLast();
				return r;
			}
			catch (err) {
				JCalPro.debug(err);
				return false;
			}
		}, function(){
			return jQuery(el).last();
		});
	}
	,
	/**
	 * inject one element into/around another
	 * 
	 * @param element to be injected
	 * @param element to receive the first element
	 * @param where to inject
	 * 
	 * @return mixed
	 */
	inject: function(el1, el2, dir) {
		return JCalPro._switch(function(){
			return $(el1).inject(el2, dir);
		}, function(){
			switch (dir) {
				case 'before': return jQuery(el1).insertBefore(el2);
				case 'after' : return jQuery(el1).insertAfter(el2);
				case 'top'   : return jQuery(el2).prepend(el1);
				case 'bottom':
				default      : return jQuery(el2).append(el1);
			}
		});
	}
	,
	/**
	 * destroys an element
	 * 
	 * @param selector
	 * 
	 * @return mixed
	 */
	destroy: function(el) {
		return JCalPro._switch(function(){
			return $(el).destroy();
		}, function(){
			return jQuery(el).empty().remove();
		});
	}
	,
	/**
	 * removes an event from an item
	 * 
	 * @param event
	 * @param selector
	 * @param function (optional)
	 * 
	 * @return mixed
	 */
	removeEvent: function(event, el) {
		var func = (3 == arguments.length ? arguments[2] : false);
		return JCalPro._switch(function(){
			if (func) {
				return $(el).removeEvent(event, func);
			}
			else {
				return $(el).removeEvent(event);
			}
		}, function(){
			var jqfunc = ('function' == typeof jQuery().off ? 'off' : 'unbind');
			if (func) {
				return jQuery(el)[jqfunc](event, func);
			}
			else {
				return jQuery(el)[jqfunc](event);
			}
		});
	}
	,
	/**
	 * removes all events from an item
	 * 
	 * @param selector
	 * 
	 * @return mixed
	 */
	removeEvents: function(el) {
		return JCalPro._switch(function(){
			return $(el).removeEvents();
		}, function(){
			return jQuery(el).find("*").andSelf().unbind().undelegate().die();
		});
	}
	,
	/**
	 * stop the current event
	 * 
	 * @param event
	 * 
	 * @return void
	 */
	stopEvent: function(e) {
		try {
			new Event(e).stop();
			return;
		}
		catch (err) {}
		try {
			e.preventDefault();
			return;
		}
		catch (err) {}
		// wtf? shouldn't get here, but oh well :)
		// this is bad because it stops propagation instead of preventing default behavior
		// but oh well, we should never see this part anyways (but never say never!)
		JCalPro.debug('Something went haywire stopping an event!', e);
		e = e||window.event;
		try {
			e.stopPropagation();
		}
		catch (err) {
			e.cancelBubble = true;
		}
	}
	,
	/**
	 * make a request
	 * 
	 * @param object
	 * 
	 * @return ?
	 */
	request: function(opts) {
		var type = opts.requestType || 'html';
		if (opts.requestType) {
			delete opts.requestType;
		}
		return JCalPro._switch(function(){
			opts.format = type;
			var r;
			switch (type) {
				case 'json':
					r = new Request.JSON(opts);
					break;
				case 'raw':
				case 'html':
				default:
					r = new Request.HTML(opts);
					break;
			}
			return r.send();
		}, function(){
			// fix url
			var url = opts.url;
			delete opts.url;
			opts.data = opts.data || {};
			if ('object' == typeof opts.data) {
				opts.data.format = type;
			}
			else if ('string' == typeof opts.data) {
				opts.data = opts.data + '&format=' + type;
			}
			opts.dataType = 'raw' == type ? 'html' : type;
			// fix success function
			opts.success = function(data, resp) {
				if (opts.update) {
					jQuery(opts.update).html(data);
				}
				opts.onSuccess(data, resp);
			};
			// fix failure function
			opts.error = function(jqXHR, textStatus, errorThrown) {
				if ('function' === typeof opts.onFailure) {
					opts.onFailure(textStatus);
				}
			};
			// force to post if not explicit
			if ('undefined' === typeof opts.type) {
				opts.type = 'post';
			}
			return jQuery.ajax(url, opts);
		});
	}
	,
	/**
	 * simple iterator
	 * 
	 * @param array
	 * @param function
	 * @param bool, optional
	 */
	each: function(collection, callback) {
		var isObj = (3 <= arguments.length ? (arguments[2] ? true : false) : false);
		return JCalPro._switch(function(){
			if (isObj) {
				return Object.each(collection, callback);
			}
			else {
				return Array.each(collection, callback);
			}
		}, function(){
			collection = (collection && collection.length) ? collection : [];
			return jQuery.each(collection, (function(idx, el){
				callback(el, idx);
			}));
		});
	}
	,
	contains: function(needle, haystack) {
		return JCalPro._switch(function(){
			return haystack.contains(needle);
		}, function(){
			return jQuery.inArray(needle, haystack);
		});
	}
	,
	/**
	 * add an event to an element
	 * 
	 * @param string
	 * @param DOM element
	 * @param function
	 */
	addEvent: function(event, el, callback) {
		JCalPro.debug('JCalPro.addEvent');
		JCalPro.debug(arguments);
		return JCalPro._switch(function(){
			return $(el).addEvent(event, callback);
		}, function(){
			if (el[event] && 'function' === typeof el[event]) {
				return el[event](callback);
			}
			return jQuery(el).on(event, callback);
		});
	}
	,
	/**
	 * fire an event
	 * 
	 * @param string
	 * @param DOM element
	 */
	fireEvent: function(event, el) {
		return JCalPro._switch(function(){
			return el.fireEvent(event);
		}, function(){
			return jQuery(el).trigger(event);
		});
	}
	,
	/**
	 * determines if an element has a class
	 * 
	 * @param mixed
	 * @param string
	 * 
	 * @return bool
	 */
	hasClass: function(el, className) {
		return JCalPro._fw(el, 'hasClass', className);
	}
	,
	/**
	 * determines if an element has a class
	 * 
	 * @param mixed
	 * @param string
	 * 
	 * @return bool
	 */
	toggleClass: function(el, className) {
		return JCalPro._fw(el, 'toggleClass', className);
	}
	,
	/**
	 * turns a string into html
	 * 
	 * @param string
	 * 
	 * @return array
	 */
	htmlFromString: function(string) {
		return JCalPro._switch(function(){
			return Elements.from(string);
		}, function(){
			return jQuery(string);
		});
	}
	,
	/**
	 * scroll to an element
	 * 
	 * @param mixed
	 * @param string
	 * 
	 * @return bool
	 */
	scrollTo: function(el) {
		return JCalPro._switch(function(){
			var sFx = new Fx.Scroll(window).toElement(el, 'y');
			return sFx;
		}, function(){
			return jQuery('html, body').animate({
				scrollTop: jQuery(el).offset().top
			}, 2000);
		});
	}
	,
	/**
	 * makes an element "sortable"
	 * 
	 */
	sortable: function(el, options) {
		// add sortables
		return JCalPro._switch(function(){
			var s = new Sortables(JCalPro.el(el), options);
			return s;
		}, function(){
			var s = jQuery(el);
			if ('undefined' == typeof s.sortable) {
				JCalPro.debug('jQuery.sortable is undefined');
				return s;
			}
			try {
				if (options.onComplete) {
					options.stop = options.create = options.onComplete;
				}
				s.sortable(options);
				s.disableSelection();
			}
			catch (err) {
				window.JCalPro.debug('Sortable not available!', err);
			}
			return s;
		});
	}
	,
	/**
	 * determines if an element is displayed
	 */
	isDisplayed: function(el) {
		return JCalPro._switch(function(){
			return $(el).isDisplayed();
		}, function(){
			return $(el).is(":visible")
		});
	}
	,
	/**
	 * creates a tooltip on an element
	 * 
	 */
	tips: function(el, options) {
		options = ('object' == typeof options ? options : {});
		var mooFunc = function(){
			Array.each($$(el), function(e, idx) {
				var title = e.get('title');
				if (title) {
					var parts = title.split('::', 2);
					e.store('tip:title', parts[0]);
					e.store('tip:text', parts[1]);
				}
			});
			var JTooltips = new Tips($$(el), options);
		};
		return JCalPro._switch(mooFunc, function(){
			var tipped = false;
			try {
				mooFunc();
				JCalPro.debug('Tips');
				tipped = true;
			}
			catch (err) {
				JCalPro.debug(err);
				tipped = false;
			}
			if (!tipped) {
				try {
					jQuery(el).tooltip(options);
					JCalPro.debug('jQuery.tooltip');
				}
				catch (err) {
					JCalPro.debug(err);
				}
			}
		});
	}
	,
	/**
	 * decodes a JSON string
	 * 
	 * @returns mixed
	 */
	json_decode: function(data) {
		return JCalPro._switch(function(){
			return JSON.decode(data, true);
		}, function(){
			return jQuery.parseJSON(data);
		});
	}
	,
	/**
	 * debug via the console
	 * 
	 * @return void
	 */
	debug: function() {
		if (!(arguments && arguments.length)) {
			return;
		}
		try {
			for (var i = 0, n = arguments.length; i < n; i++) {
				console && console.log && console.log(arguments[i]);
			}
		}
		catch (e) {
			return;
		}
	}
	,
	/**
	 * execute a function from the proper framework, provided both frameworks have the same function
	 * 
	 * @param mixed
	 * @param string
	 * @param mixed
	 * 
	 * @return mixed
	 */
	_fw: function(el, func, arg) {
		var r = false;
		try {
			r = JCalPro._switch(function(){
				return $(el)[func](arg);
			}, function(){
				return jQuery(el)[func](arg);
			});
		}
		catch (err) {
			JCalPro.debug(err, el, func, arg);
			r = false;
		}
		return r;
	}
	,
	/**
	 * fire a callback depending on mootools or jquery
	 * 
	 * @param function
	 * @param function
	 */
	_switch: function(moo, jq) {
		if (JCalPro.useJQuery) {
			return jq();
		}
		else if (document.id) {
			return moo();
		}
		else {
			throw 'No handler';
		}
	}
};

// fix for "closest"
window.JCalPro._switch(function() {
	window.JCalPro.debug('JCalPro - Mootools');
	Element.implement({
		closest: function(selector) {
			var matches = $$(selector);
			var cur = this;
			while (cur && !matches.contains(cur)) {
				cur = cur.getParent();
			}
			return cur;
		}
	});	
}, function(){
	window.JCalPro.debug('JCalPro - jQuery');
});

window.JCalPro.onLoad(function() {
	JCalPro.fireEvent('jcalload', window);
});