<?php
/**
 * @package		JCalPro
 * @subpackage	mod_jcalpro_locations

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

defined('JPATH_PLATFORM') or die;

jimport('joomla.application.component.model');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JLoader::register('JCalProHelperTheme', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/theme.php');
JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');
JCalProBaseModel::addIncludePath(JPATH_SITE.'/components/com_jcalpro/models', 'JCalProModel');

abstract class modJCalProLocationsHelper
{
	
	public static function getList(&$params, &$module) {
		
		$profiler = JProfiler::getInstance('Application');
		$profiler->mark('onJCalProCalendarModuleGetListStart');
		
		// Get the dbo
		$db = JFactory::getDbo();
		
		// Get an instance of the events model
		$locationsModel = JCalPro::getModelInstance('Locations', 'JCalProModel', array('ignore_request' => true));
		
		// set the state based on the module params
		$geo = $locationsModel->getState('filter.geolocate');
		$lat = $locationsModel->getState('filter.geolat');
		$lng = $locationsModel->getState('filter.geolng');
		$rad = $locationsModel->getState('filter.georadius');
		
		$locationsModel->setState('filter.geolocate', (int) $params->get('geolocation'));
		$locationsModel->setState('filter.geolat', (float) $params->get('latitude'));
		$locationsModel->setState('filter.geolng', (float) $params->get('longitude'));
		$locationsModel->setState('filter.georadius', (int) $params->get('radius'));
		
		// get the items from the model
		$items = $locationsModel->getItems();
		
		// reset state
		$locationsModel->setState('filter.geolocate', $geo);
		$locationsModel->setState('filter.geolat', $lat);
		$locationsModel->setState('filter.geolng', $lng);
		$locationsModel->setState('filter.georadius', $rad);
		
		// we only want to send back locations with events
		$locations = array();
		
		// loop items & fetch events for each one
		if (!empty($items)) {
			// set the module state to eliminate interference
			JFactory::getApplication()->setUserState('com_jcalpro.events.jcalpro.module', true);
			// get our events model
			$eventsModel = JCalProBaseModel::getInstance('Events', 'JCalProModel', array('ignore_request' => true));
			$location = $eventsModel->getState('filter.location');
			$layout = $eventsModel->getState('filter.layout');
			// assign events to each location
			foreach ($items as &$item) {
				$eventsModel->setState('filter.location', $item->id);
				$events = $eventsModel->getItems();
				if (empty($events)) continue;
				$loc = array();
				$loc['id'] = $item->id;
				$loc['title'] = $item->title;
				$loc['latitude'] = $item->latitude;
				$loc['longitude'] = $item->longitude;
				$loc['href'] = JCalProHelperUrl::location($item->id);
				$locations[] = $loc;
			}
			$eventsModel->getState('filter.location', $location);
			$eventsModel->getState('filter.layout', $layout);
			
			// reset user state
			JFactory::getApplication()->setUserState('com_jcalpro.events.jcalpro.module', false);
		}
		
		$profiler->mark('onJCalProCalendarModuleGetListEnd');
		
		return $locations;
	}
	
	public static function prepareDocument(&$params, &$module) {
		$document = JFactory::getDocument();
		if (method_exists($document, 'addScript')) {
			$id      = (int) $module->id;
			$width   = (int) $params->def('width', 250);
			$height  = (int) $params->def('height', 200);
			$measure = ((int) $params->def('width_type', 0)) ? '%' : 'px';
			
			// javascripts
			JCalPro::mapScript(false, array('geometry'));
			$document->addScript(JCalProHelperUrl::media() . '/modules/locations/js/map.js');
			
			$script = array();
			$script[] = 'window.mod_locations_controls_' . $id . ' = ' . (int) $params->def('controls', 0) . ';';
			$script[] = 'window.mod_locations_radius_' . $id . ' = ' . (int) $params->def('radius', 0) . ';';
			$script[] = 'window.mod_locations_url_' . $id . ' = "' . JCalProHelperFilter::escape_js(JCalProHelperUrl::toFull(JCalProHelperUrl::task('module', false, array('format' => 'raw', 'id' => $id, 'module' => 'mod_jcalpro_locations', 'action' => 'json')))) . '";';
			$script[] = 'window.mod_locations_zoom_' . $id . ' = ' . (int) $params->def('zoom', -1) . ';';
			$script[] = 'JCalPro.onLoad(function() {';
			$script[] = '	var map = jcl_mod_add_map(' . $id . ', ' . (float) $params->def('latitude', 0.0) . ', ' . (float) $params->def('longitude', 0.0) . ');';
			if ((int) $params->def('geolocation', 1)) {
				$script[] = '	jcl_mod_geo(' . $id . ');';
			}
			else {
				$script[] = '	jcl_mod_fetch_locations(' . $id . ', ' . (float) $params->def('latitude', 0.0) . ', ' . (float) $params->def('longitude', 0.0) . ');';
			}
			foreach (array('dragend', 'zoom_changed') as $event) {
				$script[] = '	google.maps.event.addListener(map.map, \'' . $event . '\', function() {';
				$script[] = '		jcl_mod_refresh_locations(' . $id . ')';
				$script[] = '	});';
			}
			$script[] = '});';
			$document->addScriptDeclaration(implode("\n", $script));
			
			// css
			JCalProHelperTheme::addStyleSheet('default', 'modules/locations/css');
			$style = array();
			$style[] = '#jcalpro_locations_' . $id . ' {';
			$style[] = '	width: ' . $width . $measure . ';';
			$style[] = '	height: ' . $height . 'px;';
			$style[] = '}';
			$document->addStyleDeclaration(implode("\n", $style));
		}
	}
}
