<?php
/**
 * @package		JCalPro
 * @subpackage	com_jcalpro

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

JLoader::register('JCalProListLocationsModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodellocations.php');

/**
 * This models supports retrieving lists of locations.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelLocations extends JCalProListLocationsModel
{

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState();
		$app  = JFactory::getApplication();
		
		$this->setState('filter.georadius', 30);
	}
	
	/**
	 * Method to generate the database query
	 * 
	 * overriding this so we can do searches by location coordinates
	 * 
	 */
	protected function getListQuery() {
		$db = $this->getDbo();
	
		// get the query from our base model
		$query = parent::getListQuery();
		
		// if we're using geolocation, fetch the locations within the confines of the desired radius
		$geo = $this->getState('filter.geolocate');
		$lat = $this->getState('filter.geolat');
		$lng = $this->getState('filter.geolng');
		$rad = (int) $this->getState('filter.georadius');
		if ($geo && $rad) {
			foreach (array(
				'SET @center = GeomFromText(\'POINT(' . (float) $lat . ' ' . (float) $lng . ')\');'
			,	'SET @radius = ' . ((int) $rad / 69.2) . ';'
			,	'SET @bbox = CONCAT(\'POLYGON((\',
X(@center) - @radius, \' \', Y(@center) - @radius, \',\',
X(@center) + @radius, \' \', Y(@center) - @radius, \',\',
X(@center) + @radius, \' \', Y(@center) + @radius, \',\',
X(@center) - @radius, \' \', Y(@center) + @radius, \',\',
X(@center) - @radius, \' \', Y(@center) - @radius, \'))\'
);'
			) as $setquery) {
				$db->setQuery($setquery);
				$db->query();
			}
			
			$query->where('Intersects(latlng, GeomFromText(@bbox))');
			$query->where('SQRT(POW(ABS(X(latlng) - X(@center)), 2) + POW(ABS(Y(latlng) - Y(@center)), 2)) < @radius');
		}
		
		$query->where('published = 1');
		
		return $query;
	}
}
