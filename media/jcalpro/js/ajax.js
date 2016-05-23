/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

(function() {
	window.jcl_ajax_mode_active = true;
	// store the last page hash
	var oldhash = window.location.hash;
	// store prefetched data
	var prefetchData = {};
	
	/**
	 * takes a href string & parses out any date variable found
	 * 
	 * @param  string  href to change
	 * @return string  changed href
	 */
	var changeHref = function(h) {
		var r = /^([^#]*?)([1-9][0-9]{3}\-[0-9]{2}\-[0-9]{2})(.*?)$/, n, d;
		if (h && h.match(r)) {
			n = h.replace(r, '$1$3').replace(/date\=/, '').replace(/\?\&/, '?').replace(/\&{2,}/, '&');
			d = h.replace(r, '$2');
			h = n.replace(/[\?\&]$/, '') + '#' + d;
		}
		return h;
	};
	
	/**
	 * changes any links found in the component page to use hashes instead of request variables
	 */
	var fixLinks = function () {
		var hasModal = ('undefined' != typeof SqueezeBox);
		JCalPro.each(JCalPro.getElements(JCalPro.id('jcl_component'), 'a'), function(el, idx) {
			if (hasModal && JCalPro.hasClass(el, 'modal')) {
				SqueezeBox.assign(el, {parse: 'rel'});
			}
			if (JCalPro.hasClass(el, 'noajax')) {
				return;
			}
			JCalPro.setAttribute(el, 'href', changeHref(JCalPro.getAttribute(el, 'href')));
		});
	};
	
	/**
	 * interval function to monitor the changing of the hash
	 */
	var hashChange = function() {
		var hash = window.location.hash;
		if (oldhash != hash) {
			requestPage(hash);
			oldhash = hash;
		}
	};
	
	/**
	 * destroys the loading element
	 */
	var destroyLoader = function() {
		try {
			JCalPro.destroy(JCalPro.id('jcl_ajax_loader'));
		}
		catch (err) {
		}
	};
	
	/**
	 * creates the loading element
	 */
	var createLoader = function() {
		var loader = JCalPro.newElement('div', {id:'jcl_ajax_loader'}), dim = JCalPro.getSize(JCalPro.id('jcl_component'));
		JCalPro.setStyles(loader, {opacity: 0.3, width: dim.x + 'px', height: dim.y + 'px'});
		JCalPro.inject(loader, JCalPro.id('jcl_component'), 'bottom');
	};
	
	/**
	 * adds prefetch data for neighboring pages
	 */
	var prefetchNeighbors = function() {
		JCalPro.each(JCalPro.getElements(JCalPro.id('jcl_component'), '.ajaxlayoutlink'), function(el) {
			var hash = JCalPro.getAttribute(el, 'href').replace(/([^#]*?#)(.*)$/, '$2');
			fetchData(hash, false);
		});
	};
	
	/**
	 * gets the page data & appends to the page
	 * 
	 * @param  date hash
	 * @param  completion callback
	 */
	var fetchData = function(hash, complete) {
		if ('undefined' != typeof prefetchData[hash]) {
			if ('function' == typeof complete) {
				complete(prefetchData[hash]);
			}
			return;
		}
		var req = JCalPro.request({
			url: window.location.href
		,	data: {
				format: 'raw'
			,	date: hash
			}
		,	link: 'ignore'
		,	requestType: 'raw'
		,	onSuccess: function(responseHtml) {
				if ('string' == typeof responseHtml) {
					responseHtml = JCalPro.htmlFromString(responseHtml);
				}
				
				JCalPro.each(responseHtml, function(item, key, obj) {
					if ('object' != typeof obj) {
						return;
					}
					if ('undefined' == typeof item || 'TextNode' == typeof item || 'function' != typeof item.clone) {
						try {
							delete obj[key];
						}
						catch (err) {
							JCalPro.debug(err);
						}
					}
				});
				prefetchData[hash] = responseHtml;
				if ('function' == typeof complete) {
					complete(responseHtml);
				}
			}
		});
	};
	
	/**
	 * requests a new page to replace the current one
	 * 
	 * @param  string  date string
	 */
	var requestPage = function(hash) {
		hash = hash.replace(/^\#/, '');
		createLoader();
		fetchData(hash, function(html) {
			JCalPro.id('jcl_layout_body').empty();
			JCalPro.each(html, function(el, idx) {
				var elem = JCalPro.$(el);
				try {
					if ('function' == typeof elem.clone) {
						var copy = elem.clone(true, true);
						JCalPro.inject(copy, JCalPro.id('jcl_layout_body'), 'bottom');
					}
					else throw "Cannot clone ajax response!"
				}
				catch (err) {
					JCalPro.debug(err);
				}
			});
			JCalPro.each(['prev', 'next', 'current', 'header'], function(u, uidx){
				var uel = JCalPro.getElement(JCalPro.id('jcl_component'), '.ajax'+u);
				var tel = JCalPro.id('jcl_layout_value_'+u+'_text');
				var vel = JCalPro.id('jcl_layout_value_'+u+'_href');
				if (uel) {
					if (tel) {
						JCalPro.setText(uel, JCalPro.getValue(tel));
					}
					if (vel) {
						JCalPro.setAttribute(uel, 'href', JCalPro.getValue(vel));
					}
					if ('prev' == u || 'next' == u) {
						var hideNav = !uel.hasClass('nohide');
						if ('' == JCalPro.getText(uel)) {
							if (hideNav) {
								JCalPro.setStyle(uel, 'visibility', 'hidden');
								JCalPro.setStyle(uel, 'display', 'none');
							}
							JCalPro.$(uel).addClass('disabled');
						}
						else {
							if (hideNav) {
								JCalPro.setStyle(uel, 'visibility', 'visible');
								JCalPro.setStyle(uel, 'display', 'inline');
							}
							JCalPro.$(uel).removeClass('disabled');
						}
					}
				}
			});
			// we may not have this element
			try {
				// the values element
				var values = JCalPro.id('jcl_layout_values');
				// try to replace the toolbar buttons
				JCalPro.setHtml(JCalPro.getElement(JCalPro.id('jcl_component'), '.jcl_toolbar'), JCalPro.getHtml(JCalPro.getElement(values, '.jcl_toolbar')));
				
				// destroy the whole thing
				JCalPro.destroy(values);
			}
			catch (err) {
				JCalPro.debug(err);
			};
			JCalPro.tips(JCalPro.getElements(JCalPro.id('jcl_component'), '.hasTip'), {maxTitleChars: 50, fixed: false});
			destroyLoader();
			fixLinks();
			prefetchNeighbors();
			JCalPro.fireEvent('jcalajax', window);
		});
	};
	
	// sets the interval for the hash change monitor
	setInterval(hashChange, 200);
	
	// initialization
	JCalPro.onLoad(function() {
		// existing hashes take precedence
		if (oldhash) {
			requestPage(oldhash);
		}
		// no hash
		else {
			fixLinks();
			prefetchNeighbors();
		}
	});
})();
