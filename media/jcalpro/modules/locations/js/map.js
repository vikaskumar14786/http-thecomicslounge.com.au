/**
 * @package		JCalPro
 * @subpackage	mod_jcalpro_events

**********************************************
JCal Pro
Copyright (c) 2006-2012 Anything-Digital.com
**********************************************
JCalPro is a native Joomla! calendar component for Joomla!

JCal Pro was once a fork of the existing Extcalendar component for Joomla!
(com_extcal_0_9_2_RC4.zip from mamboguru.com).
Extcal (http://sourceforge.net/projects/extcal) was renamed
and adapted to become a Mambo/Joomla! component by
Matthew Friedman, and further modified by David McKinnis
(mamboguru.com) to repair some security holes.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This header must not be removed. Additional contributions/changes
may be added to this header as long as no information is deleted.
**********************************************
Get the latest version of JCal Pro at:
http://anything-digital.com/
**********************************************

 */

/**
 * keep track of the map instances
 */
var jcl_mod_maps = {};

/**
 * sets a map in the instance list
 * 
 * @return Object
 */
var jcl_mod_set_map = function(map) {
	jcl_mod_maps[map.key] = map;
	return map;
};

/**
 * gets a map object from the instance list
 * 
 * @param  mod_id  module id
 * 
 * @return Object
 */
var jcl_mod_get_map = function(mod_id) {
	var key = 'jcalpro_locations_' + mod_id;
	var controls = parseInt(window['mod_locations_controls_' + mod_id], 10);
	var zoom = parseInt(window['mod_locations_zoom_' + mod_id], 10);
	if ('undefined' == typeof jcl_mod_maps[key]) {
		jcl_mod_set_map({
			id: mod_id
		,	key: key
		,	element: document.getElementById(key)
		,	hasMap: false
		,	hasMarkers: false
		,	checking: false
		,	markers: {}
		,	latlng: false
		,	mapOptions: {
				zoom: zoom >= 0 ? zoom : 8
			,	disableDefaultUI: (0 == controls)
			,	mapTypeId: google.maps.MapTypeId.ROADMAP
			}
		});
	}
	return jcl_mod_maps[key];
};

/**
 * creates a new map
 * 
 * @param  id   module id
 * @param  lat  initial latitude
 * @param  lng  initial longitude
 * 
 * @return Object
 */
var jcl_mod_add_map = function(id, lat, lng) {
	var map = jcl_mod_get_map(id);
	if (map.hasMap) {
		return map.element;
	}
	map.lat = lat;
	map.lng = lng;
	map.latlng = new google.maps.LatLng(lat, lng);
	map.mapOptions.center = map.latlng;
	map.map = new google.maps.Map(map.element, map.mapOptions);
	map.hasMap = true;
	return map;
};

/**
 * refreshes locations on map
 * 
 * @param  id   module id
 */
var jcl_mod_refresh_locations = function(id) {
	var map = jcl_mod_get_map(id), center = map.map.getCenter();
	if (!map.hasMarkers || map.checking) {
		return;
	}
	window['mod_locations_zoom_' + id] = map.map.getZoom();
	window['mod_locations_radius_' + id] = google.maps.geometry.spherical.computeDistanceBetween(center, map.map.getBounds().getNorthEast(), 3963.19);
	jcl_mod_fetch_locations(id, center.lat(), center.lng());
};

/**
 * sets map center based on geolocation
 * 
 * @param  id  module id
 */
var jcl_mod_geo = function(id) {
	var map = jcl_mod_get_map(id), lat = false, lng = false;
	try {
		lat = localStorage.getItem('jcl_mod_geo_lat');
		lng = localStorage.getItem('jcl_mod_geo_lng');
	}
	catch (err) {
		lat = false;
		lng = false;
	}
	if (lat && lng) {
		jcl_mod_fetch_locations(id, lat, lng);
	}
	else if (navigator.geolocation) {
		var timeout = setTimeout(function(){
			jcl_mod_fetch_locations(id, map.lat, map.lng);
		}, 5000);
		navigator.geolocation.getCurrentPosition(function(position) {
			clearTimeout(timeout);
			localStorage.setItem('jcl_mod_geo_lat', position.coords.latitude);
			localStorage.setItem('jcl_mod_geo_lng', position.coords.longitude);
			jcl_mod_fetch_locations(id, position.coords.latitude, position.coords.longitude);
		}, function () {
			clearTimeout(timeout);
			jcl_mod_fetch_locations(id, map.lat, map.lng);
		});
	}
	else {
		jcl_mod_fetch_locations(id, map.lat, map.lng);
	}
};

/**
 * fetches locations for map via ajax
 * 
 * @param  id   module id
 * @param  lat  latitude
 * @param  lng  longitude
 */
var jcl_mod_fetch_locations = function(id, lat, lng) {
	var url = window['mod_locations_url_' + id];
	if (!url) {
		return;
	}
	var map = jcl_mod_get_map(id);
	if (map.checking) {
		return;
	}
	map.checking = true;
	var zoom = parseInt(window['mod_locations_zoom_' + id], 10);
	var rad = parseInt(window['mod_locations_radius_' + id], 10);
	var center = new google.maps.LatLng(lat, lng);
	map.map.setCenter(center);
	var req = new Request.JSON({
		url: url
	,	link: 'ignore'
	,	data: {'params[latitude]': lat, 'params[longitude]': lng, 'params[radius]': rad}
	,	onSuccess: function(responseJSON, responseText) {
			map.checking = false;
			var bounds = new google.maps.LatLngBounds(), changeBounds = false, markersReceived = false;
			JCalPro.each(responseJSON, function(el, idx) {
				markersReceived = true;
				changeBounds = true;
				var latlng = new google.maps.LatLng(el.latitude, el.longitude);
				if (!map.markers[el.id]) {
					map.markers[el.id] = new google.maps.Marker({
						map: map.map
					,	position: latlng
					,	title: el.title
					//,	icon: el.pin
					});
					google.maps.event.addListener(map.markers[el.id], 'click', function() {
						parent.window.location.href = el.href;
					});
				}
				if (-1 == zoom) {
					bounds.extend(latlng);
				}
			});
			if (-1 == zoom && changeBounds) {
				map.map.fitBounds(bounds);
				if (16 < map.map.getZoom()) {
					map.map.setZoom(16);
				}
			}
			setTimeout(function() {
				if (!map.hasMarkers) {
					map.hasMarkers = markersReceived;
					if (!markersReceived) {
						window['mod_locations_radius_' + id] = rad * 2;
						if (1000 > window['mod_locations_radius_' + id]) {
							jcl_mod_fetch_locations(id, lat, lng);
						}
					}
				}
			}, 200);
		}
	}).send();
};
