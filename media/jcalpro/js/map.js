/**
 * @package		JCalPro
 * @subpackage	com_jcalpro
Copyright (c) 2006-2012 Anything-Digital.com
 */

window.jcl_map_default_zoom_level = window.jcl_map_default_zoom_level || 8;
var jcl_lat = 0.0, jcl_lng = 0.0, jcl_map, jcl_geocoder, jcl_marker, jcl_dir, jcl_dir_service;
jcl_dir_service = new google.maps.DirectionsService();

var jcl_get_directions = function() {
	var btn = JCalPro.id('jcl_location_directions_search'), geo = JCalPro.id('jcl_location_directions_geo'), address = JCalPro.id('jcl_location_directions_address'), link = JCalPro.id('jcl_location_directions_link');
	if (!btn) {
		JCalPro.debug(Joomla.JText._('COM_JCALPRO_LOCATION_GET_DIRECTIONS_FORM_NOT_FOUND'));
		return;
	}
	JCalPro.addEvent('change', address, function() {
		address.removeClass('invalid');
		var printButton = JCalPro.el('.jcl_toolbar_button_print');
		if (printButton) {
			window.jcl_location_print_button_url = JCalPro.getAttribute(link, 'data-linkpattern') + encodeURIComponent(JCalPro.getValue(address)) + '&tmpl=component&print=1';
			JCalPro.setAttribute(printButton, 'href', window.jcl_location_print_button_url);
			JCalPro.removeAttribute(printButton, 'onclick');
			if ('undefined' == typeof window.jcl_location_print_button_fix) {
				JCalPro.onClick(printButton, function(e) {
					jclPrintWindow = window.open(window.jcl_location_print_button_url,'jclPrintWindow','toolbar=no,location=no,directories=no,status=no,menubar=yes,scrollbars=yes,resizable=yes,width=800,height=600');
					return false;
				});
				window.jcl_location_print_button_fix = true;
			};
		}
	});
	JCalPro.onClick(link, function(){
		if (arguments.length) {
			JCalPro.stopEvent(arguments[0]);
		}
		var url = JCalPro.getAttribute(link, 'data-linkpattern') + encodeURIComponent(JCalPro.getValue(address));
		var input = JCalPro.newElement('input', {id: 'jcl_location_directions_url_field', type: 'text', value: url});
		var container = JCalPro.newElement('div', {style: 'padding:8px;text-align:center'});
		input.onclick = function() {
			this.select();
		};
		JCalPro.inject(input, container);
		try {
			var s = JCalPro.getSize(document);
			s.x = 600 > s.x ? s.x - 65 : 600;
			s.y = 100 > s.y ? s.y - 65 : 100;
			JCalPro.setStyle(input, 'width', Math.max(30, s.x - 30) + 'px');
			SqueezeBox.resize(s);
		}
		catch (err) {
			JCalPro.debug(err);
		}
		SqueezeBox.setContent('adopt', container);
		return false;
	});
	JCalPro.onClick(geo, function(){
		if (arguments.length) {
			JCalPro.stopEvent(arguments[0]);
		}
		navigator.geolocation.getCurrentPosition(function(position) {
			var loc = jcl_get_latlng(), orig = new google.maps.LatLng(position.coords.latitude, position.coords.longitude), request = {
				origin: orig
			,	destination: new google.maps.LatLng(loc.lat, loc.lng)
			,	travelMode: google.maps.DirectionsTravelMode.DRIVING
			};
			jcl_geocoder.geocode({'latLng': orig}, function(results, status) {
				if (google.maps.GeocoderStatus.OK == status) {
					JCalPro.setValue(address, results[0].formatted_address);
					JCalPro.fireEvent('change', address);
				}
			});
			jcl_dir_service.route(request, function(response, status) {
				if (status == google.maps.DirectionsStatus.OK) {
					jcl_dir.setDirections(response);
				}
			});
		}, function () {
			alert(Joomla.JText._('COM_JCALPRO_LOCATION_GET_DIRECTIONS_CANNOT_GEOLOCATE'));
		});
		return false;
	});
	JCalPro.onClick(btn, function(){
		if (arguments.length) {
			JCalPro.stopEvent(arguments[0]);
		}
		if (!address) {
			JCalPro.debug(Joomla.JText._('COM_JCALPRO_LOCATION_GET_DIRECTIONS_FORM_NOT_FOUND'));
			return false;
		}
		var addressValue = JCalPro.getValue(address);
		if ('' == addressValue) {
			address.addClass('invalid');
			return false;
		}
		var loc = jcl_get_latlng(), request = {
			origin: addressValue
		,	destination: new google.maps.LatLng(loc.lat, loc.lng)
		,	travelMode: google.maps.DirectionsTravelMode.DRIVING
		};
		jcl_dir_service.route(request, function(response, status) {
			if (status == google.maps.DirectionsStatus.OK) {
				jcl_dir.setDirections(response);
			}
		});
		return false;
	});
	if ('' != JCalPro.getValue(address)) {
		JCalPro.fireEvent('click', btn);
	}
};

/**
 * map initialization function
 */
var jcl_map_init = function() {
	JCalPro.debug(Joomla.JText._('COM_JCALPRO_INITIALIZING_MAP'));
	jcl_dir = new google.maps.DirectionsRenderer();
	var map = document.getElementById('map_canvas'), loc;
	if (!map) {
		JCalPro.debug(Joomla.JText._('COM_JCALPRO_ERROR_NO_MAP_ELEMENT'));
		return;
	}
	loc = jcl_get_latlng();
	jcl_geocoder = new google.maps.Geocoder();
	var latlng = new google.maps.LatLng(loc.lat, loc.lng);
	var mapOptions = {
		zoom: window.jcl_map_default_zoom_level
	,	center: latlng
	,	mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	jcl_map = new google.maps.Map(map, mapOptions);
	if (!(0 == loc.lat && 0 == loc.lng)) {
		jcl_marker = new google.maps.Marker({map: jcl_map, position: latlng});
	}
	jcl_dir.setMap(jcl_map);
	jcl_dir.setPanel(document.getElementById('jcl_location_directions_panel'));
	jcl_get_directions();
};

var jcl_nonewlines = function(str) {
	return str.toString().replace(/[\t\n\r]/, ' ').replace(/^\s+/, '').replace(/\s+$/, '');
}

/**
 * refresh the map from the given element
 */
var jcl_map_refresh = function(addr_id, city_id, state_id, country_id, postal_code_id) {
	var addr = JCalPro.id(addr_id), city = JCalPro.id(city_id), state = JCalPro.id(state_id), country = JCalPro.id(country_id), postal_code = JCalPro.id(postal_code_id), address, loc;
	if (addr && city && state && country && postal_code) {
		address = jcl_nonewlines(JCalPro.getValue(addr) + ' ' + JCalPro.getValue(city) + ' ' + JCalPro.getValue(state) + ' ' + JCalPro.getValue(postal_code) + ' ' + JCalPro.getValue(country));
		if ('' == address) {
			return;
		}
		jcl_geocoder.geocode({'address': address}, function(results, status) {
			switch (status) {
				case google.maps.GeocoderStatus.OK :
					loc = results[0].geometry.location;
					jcl_map.setCenter(loc);
					if ('undefined' != typeof jcl_marker) {
						try {
							jcl_marker.setMap(null);
						}
						catch (err) {
							JCalPro.debug(err);
						}
					}
					jcl_marker = new google.maps.Marker({map: jcl_map, position: loc});
					jcl_update_latlng(loc.lat(), loc.lng());
					jcl_update_address_fields(results[0]);
					break;
				default :
					alert(Joomla.JText._('COM_JCALPRO_GEOCODER_STATUS_' + status));
					break;
			}
		});
	}
};

/**
 * gets the latitude & longitude from the hidden inputs
 */
var jcl_get_latlng = function() {
	var latitude = JCalPro.id('jform_latitude'), longitude = JCalPro.id('jform_longitude'), ll = {
		lat: jcl_lat
	,	lng: jcl_lng
	};
	if (latitude && longitude) {
		ll.lat = JCalPro.getValue(latitude);
		ll.lng = JCalPro.getValue(longitude);
	}
	JCalPro.debug(latitude, longitude, ll);
	return ll;
};

/**
 * update the address fields
 */
var jcl_update_address_fields = function(result) {
	if ('undefined' == typeof result.address_components) {
		return;
	}
	JCalPro.each(result.address_components, function(el) {
		if ('undefined' == typeof el.types) {
			return;
		}
		for (var i=0; i<el.types.length; i++) {
			if ('locality' == el.types[i]) {
				JCalPro.setValue(JCalPro.id('jform_city'), el.long_name);
				break;
			}
			if ('administrative_area_level_1' == el.types[i]) {
				JCalPro.setValue(JCalPro.id('jform_state'), el.long_name);
				break;
			}
			if ('country' == el.types[i]) {
				JCalPro.setValue(JCalPro.id('jform_country'), el.long_name);
				break;
			}
			if ('postal_code' == el.types[i]) {
				JCalPro.setValue(JCalPro.id('jform_postal_code'), el.long_name);
				break;
			}
		}
	});
};

/**
 * update the latitude and longitude fields
 */
var jcl_update_latlng = function(lat, lng) {
	var latitude = JCalPro.id('jform_latitude'), longitude = JCalPro.id('jform_longitude');

	JCalPro.debug(latitude);
	JCalPro.debug(longitude);
	
	if (!latitude || !longitude) {
		JCalPro.debug(Joomla.JText._('COM_JCALPRO_ERROR_NO_LATLNG_ELEMENTS'));
		return;
	}
	
	JCalPro.setValue(latitude, lat);
	JCalPro.setValue(longitude, lng);
	
	JCalPro.debug(latitude);
	JCalPro.debug(longitude);
	JCalPro.debug({lat: lat, lng: lng});
};

if ('undefined' != typeof google && 'undefined' != google.maps) {
	google.maps.event.addDomListener(window, 'load', jcl_map_init);
}
