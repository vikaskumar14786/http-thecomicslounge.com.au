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

JLoader::register('JCalProBaseModel', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/models/basemodel.php');

JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('date');
JCalPro::registerHelper('filter');


// ensure our language files are properly loaded
JCalPro::language('com_jcalpro', JPATH_ADMINISTRATOR);
JCalPro::language('com_jcalpro.event', JPATH_ADMINISTRATOR);

/**
 * This model is for the install options
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProModelInstall extends JCalProBaseModel
{
	public function getData($layout) {
		switch ($layout) {
			case 'sample'        : return $this->_installSampleData();
			case 'migrate'       : return $this->_installMigrationData();
			case 'migratecollect': return $this->_collectMigrationData();
			case 'migrateitem'   : return $this->_handleMigrationItem();
			case 'fixcategories' : return $this->_fixBrokenCategories();
			case 'fixassets'     : return $this->_fixBrokenAssets();
			default       :
				$this->setError(JText::_('COM_JCALPRO_INSTALLER_LAYOUT_NOT_FOUND'));
				return false;
		}
	}
	
	public function checkCategories() {
		static $hasCategories;
		if (is_null($hasCategories)) {
			// our database object
			$db = JFactory::getDbo();
			// check if there are any categories - if not, offer to install sample data
			$db->setQuery((string) $db->getQuery(true)
				->select('COUNT(id) AS c')
				->from('#__categories')
				->where('extension = "com_jcalpro"')
			);
			try {
				$hasCategories = (bool) $db->loadResult();
			}
			catch (Exception $e) {
				$hasCategories = false;
			}
		}
		return $hasCategories;
	}
	
	public function checkMigration() {
		static $canMigrate;
		if (is_null($canMigrate)) {
			$canMigrate = !$this->checkCategories();
			if ($canMigrate) {
				// our database object
				$db = JFactory::getDbo();
				// check for old data from JCal Pro 2
				foreach (array('calendars', 'categories', 'events', 'config') as $table) {
					$db->setQuery('SHOW TABLES LIKE "%_jcalpro2_' . $table . '"');
					try {
						$tableCanMigrate = $db->loadResult();
					}
					catch (Exception $e) {
						$tableCanMigrate = false;
					}
					$canMigrate = $canMigrate && (bool) $tableCanMigrate;
				}
			}
		}
		return $canMigrate;
	}
	
	public function checkBrokenCategories() {
		static $isBroken;
		if (is_null($isBroken)) {
			$db = JFactory::getDbo();
			// find out if we need to even bother with this insanity
			$db->setQuery($db->getQuery(true)
				->select('*')
				->from('#__categories')
				->where($db->quoteName('level') . '=' . $db->quote('0'))
				->where($db->quoteName('alias') . '<>' . $db->quote('root'))
			);
			try {
				$broken = $db->loadObjectList();
				$isBroken = !empty($broken);
			}
			catch (Exception $e) {
				$isBroken = false;
			}
		}
		return $isBroken;
	}
	
	public function checkBrokenAssets() {
		static $isBroken;
		if (is_null($isBroken)) {
			$db = JFactory::getDbo();
			$db->setQuery($db->getQuery(true)
				->select('a.id')
				->from('#__jcalpro_events AS a')
				->leftJoin('#__assets AS b ON a.asset_id = b.id')
				->where('b.id IS NULL')
			);
			try {
				$broken = $db->loadColumn();
				$isBroken = !empty($broken);
			}
			catch (Exception $e) {
				$isBroken = false;
			}
		}
		return $isBroken;
	}
	
	/**
	 * sends migration data to the client for further processing
	 * 
	 */
	private function _collectMigrationData() {
		$db   = JFactory::getDbo();
		$data = array('numRecords' => array(), 'errors' => array());
		// ensure we have a db table to store ids in
		if (!$this->_prepareMigrationEnvironment()) {
			$data['errors'] = array_merge($data['errors'], $this->getErrors());
		}
		// we need to start with calendars, then categories, then events
		foreach (array('calendars' => 'cal_id', 'categories' => 'cat_id', 'events' => 'extid') as $table => $pk) {
			$query = $db->getQuery(true)
				->select($pk . ' AS pk')
				->from('#__jcalpro2_' . $table)
			;
			// only count the parent events //and detached children
			if ('events' == $table) {
				$query->where('rec_id = 0');
				//$query->where('(rec_id = 0 OR detached_from_rec = 1)');
				// ensure we process all parents first
				$query->order('rec_id ASC');
			}
			$db->setQuery($query);
			try {
				$data['pks'][$table] = $db->loadColumn();
				$data['numRecords'][$table] = count($data['pks'][$table]);
			}
			catch (Exception $e) {
				$data['pks'][$table] = array();
				$data['numRecords'][$table] = 0;
				$data['errors'][] = $e->getMessage();
			}
		}
		if (!empty($data['errors'])) {
			$data['errorText'] = JText::_('COM_JCALPRO_MIGRATION_ERROR_COLLECTING_DATA');
		}
		else {
			$data['updateText'] = JText::sprintf('COM_JCALPRO_MIGRATION_FOUND_XYZ_ITEMS', $data['numRecords']['calendars'], $data['numRecords']['categories'], $data['numRecords']['events']);
		}
		return $data;
	}
	
	private function _getMigrationCustomCategoryForm() {
		$db = JFactory::getDbo();
		$db->setQuery('SELECT id FROM #__jcalpro_forms WHERE title = ' . $db->quote(JText::_('COM_JCALPRO_MIGRATION_FORM_EVENT_TITLE')));
		try {
			$formid = $db->loadResult();
			return empty($formid) ? false : $formid;
		}
		catch (Exception $e) {
			return false;
		}
	}
	
	private function _prepareMigrationEnvironment() {
		$db = JFactory::getDbo();
		
		// check if our custom form is in the database already
		// if not, create fields/forms
		$formid = $this->_getMigrationCustomCategoryForm();
		if (!$formid) {
			// create a form and assign the id to the session
			// create our custom form & our fields to mimic v2 contact info
			// need "email", "url", and "contact" fields
			$fields = array(
				array(
					'name'          => 'email'
				,	'title'         => JText::_('COM_JCALPRO_MIGRATION_FIELD_EMAIL_TITLE')
				,	'type'          => 'email'
				,	'description'   => JText::_('COM_JCALPRO_MIGRATION_FIELD_EMAIL_DESCRIPTION')
				,	'published'     => 1
				,	'formtype'      => 0
				,	'event_display' => 1
				,	'params'        => '{"classname":"inputbox input-block-level"}'
				)
			,	array(
					'name'          => 'url'
				,	'title'         => JText::_('COM_JCALPRO_MIGRATION_FIELD_URL_TITLE')
				,	'type'          => 'url'
				,	'description'   => JText::_('COM_JCALPRO_MIGRATION_FIELD_URL_DESCRIPTION')
				,	'published'     => 1
				,	'formtype'      => 0
				,	'event_display' => 1
				,	'params'        => '{"classname":"inputbox input-block-level"}'
				)
			,	array(
					'name'          => 'contact'
				,	'title'         => JText::_('COM_JCALPRO_MIGRATION_FIELD_CONTACT_TITLE')
				,	'type'          => 'textarea'
				,	'description'   => JText::_('COM_JCALPRO_MIGRATION_FIELD_CONTACT_DESCRIPTION')
				,	'published'     => 1
				,	'formtype'      => 0
				,	'event_display' => 1
				,	'params'        => '{"classname":"inputbox input-block-level","attrs":{"cols":"50","rows":"5"}}'
				)
			);
			$fieldids = array();
			foreach ($fields as $field) {
				$table = JTable::getInstance('Field', 'JCalProTable');
				if (!$table->bind($field)) {
					$this->setError(JText::sprintf('COM_JCALPRO_MIGRATION_BIND_ERROR', JText::_('COM_JCALPRO_FIELD'), $field['title'], $table->getError()));
					return false;
				}
				if (!$table->check()) {
					$this->setError(JText::sprintf('COM_JCALPRO_MIGRATION_CHECK_ERROR', JText::_('COM_JCALPRO_FIELD'), $field['title'], $table->getError()));
					return false;
				}
				if (!$table->store()) {
					$this->setError(JText::sprintf('COM_JCALPRO_MIGRATION_STORE_ERROR', JText::_('COM_JCALPRO_FIELD'), $field['title'], $table->getError()));
					return false;
				}
				$fieldids[] = $table->id;
			}
			// now create the form
			$form = array(
				'title'      => JText::_('COM_JCALPRO_MIGRATION_FORM_EVENT_TITLE')
			,	'type'       => 0
			,	'published'  => 1
			,	'default'    => 1
			,	'formfields' => implode('|', $fieldids)
			);
			$formid = 0;
			$table = JTable::getInstance('Form', 'JCalProTable');
			if (!$table->bind($form)) {
				$this->setError(JText::sprintf('COM_JCALPRO_MIGRATION_BIND_ERROR', JText::_('COM_JCALPRO_FORM'), $form['title'], $table->getError()));
				return false;
			}
			if (!$table->check()) {
				$this->setError(JText::sprintf('COM_JCALPRO_MIGRATION_CHECK_ERROR', JText::_('COM_JCALPRO_FORM'), $form['title'], $table->getError()));
				return false;
			}
			if (!$table->store()) {
				$this->setError(JText::sprintf('COM_JCALPRO_MIGRATION_STORE_ERROR', JText::_('COM_JCALPRO_FORM'), $form['title'], $table->getError()));
				return false;
			}
			$formid = $table->id;
		}
		
		// create a "temporary" table to hold our data
		$db->setQuery('CREATE TABLE IF NOT EXISTS #__jcalpro_migration_xref ('
		. 'old_id INT(11) NOT NULL,'
		. 'new_id INT(11) NOT NULL,'
		. 'type ENUM("calendars","categories","events") NOT NULL'
		. ')'
		);
		try {
			$db->query();
		}
		catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}
		return true;
	}
	
	private function _handleMigrationItem() {
		$data  = array('errors' => array());
		$types = array(
			'categories' => array('pk' => 'cat_id', 'title' => 'cat_name')
		,	'calendars'  => array('pk' => 'cal_id', 'title' => 'cal_name')
		,	'events'     => array('pk' => 'extid',  'title' => 'title')
		);
		$db    = JFactory::getDbo();
		$input = JFactory::getApplication()->input;
		$type  = $input->get('type', '', 'word');
		$pk    = $input->get('pk', 0, 'int');
		
		// only handle our known types
		if (!array_key_exists($type, $types)) {
			$data['errors'][] = $data['errorText'] = JText::sprintf('COM_JCALPRO_MIGRATION_INVALID_DATA_TYPE', htmlspecialchars($type));
			return $data;
		}
		
		// load this item
		$db->setQuery($db->getQuery(true)
			->select('*')
			->from('#__jcalpro2_' . $type)
			->where($types[$type]['pk'] . ' = ' . (int) $pk)
		);
		
		try {
			$item = $db->loadObject();
		}
		catch (Exception $e) {
			$data['errors'][]  = $e->getMessage();
			$data['errorText'] = JText::sprintf('COM_JCALPRO_MIGRATION_ITEM_NOT_FOUND', $pk, $type);
			return $data;
		}
		
		// TODO actually import ;)
		try {
			$this->_importV2Item($item, $type);
		}
		catch (Exception $e) {
			$data['errors'][]  = $e->getMessage();
			$data['errorText'] = $e->getMessage();
			return $data;
		}
		
		$data['item'] = $item;
		$data['updateText'] = JText::sprintf('COM_JCALPRO_MIGRATION_IMPORTED_ITEM', $type, htmlspecialchars($item->{$types[$type]['title']}));
		
		return $data;
	}
	
	private function _importV2Item($item, $type) {
		$db    = JFactory::getDbo();
		switch ($type) {
			case 'calendars':
				// create our data for binding
				$data = array(
					'title'       => $item->cal_name
				,	'description' => $item->description
				,	'extension'   => 'com_jcalpro'
				,	'parent_id'   => 1
				,	'published'   => $item->published
				,	'access'      => 1
				,	'language'    => '*'
				,	'rules'       => $this->_getRules()
				,	'params' => array(
						'jcalpro_color'     => 'FFFFFF'
					,	'jcalpro_eventform' => $this->_getMigrationCustomCategoryForm()
					)
				,	'metadata' => array(
						'page_title' => ''
					,	'author'     => ''
					,	'robots'     => ''
					,	'tags'       => ''
					)
				);
				$table = JTable::getInstance('Category');
				if (!$table->bind($data)) {
					throw new Exception(JText::sprintf('COM_JCALPRO_MIGRATION_BIND_ERROR', JText::_('COM_JCALPRO_CATEGORY'), $data['title'], $table->getError()));
				}
				if (!$table->check()) {
					throw new Exception(JText::sprintf('COM_JCALPRO_MIGRATION_CHECK_ERROR', JText::_('COM_JCALPRO_CATEGORY'), $data['title'], $table->getError()));
				}
				if (!$table->store()) {
					throw new Exception(JText::sprintf('COM_JCALPRO_MIGRATION_STORE_ERROR', JText::_('COM_JCALPRO_CATEGORY'), $data['title'], $table->getError()));
				}
				$table->moveByReference(0, 'last-child', $table->id);
				
				// update the temp xref table (so we can assign categories later)
				$db->setQuery('INSERT INTO #__jcalpro_migration_xref (old_id,new_id,type) VALUES (' . ((int) $item->cal_id) . ',' . ((int) $table->id) . ',' . $db->quote($type) . ')');
				$db->query();
				break;
			case 'categories':
				// unfortunately categories on v2 are rrelated to calendars via events
				// so we have to fetch what events have this category
				// and create a category for each calendar (yuk)
				$db->setQuery('SELECT DISTINCT cal_id FROM #__jcalpro2_events WHERE cat = ' . (int) $item->cat_id);
				$cals = $db->loadColumn();
				if (empty($cals)) {
					// we have no idea what calendar this applies to, so it's probably empty - ignore
					break;
					//throw new Exception(JText::_('COM_JCALPRO_MIGRATION_ERROR_CAL_IDS_NOT_FOUND') . ' ' . $db->getQuery(false));
				}
				foreach ($cals as $cal) {
					// before starting, fetch the parent from the xref table
					$db->setQuery('SELECT new_id FROM #__jcalpro_migration_xref WHERE old_id = ' . ((int) $cal) . ' AND type = ' . $db->quote('calendars'));
					$cal_id = $db->loadResult();
					if (empty($cal_id)) {
						throw new Exception(JText::_('COM_JCALPRO_MIGRATION_ERROR_CAL_ID_NOT_FOUND') . ' ' . $db->getQuery(false));
					}
					// create our data for binding
					$data = array(
						'title'       => $item->cat_name
					,	'description' => $item->description
					,	'extension'   => 'com_jcalpro'
					,	'parent_id'   => $cal_id
					,	'published'   => $item->published
					,	'access'      => 1
					,	'language'    => '*'
					,	'rules'       => $this->_getRules()
					,	'params' => array(
							'jcalpro_color' => trim($item->color, '#')
						)
					);
					// now save the category
					$table = JTable::getInstance('Category');
					if (!$table->bind($data)) {
						throw new Exception(JText::sprintf('COM_JCALPRO_MIGRATION_BIND_ERROR', JText::_('COM_JCALPRO_CATEGORY'), $data['title'], $table->getError()));
					}
					if (!$table->check()) {
						throw new Exception(JText::sprintf('COM_JCALPRO_MIGRATION_CHECK_ERROR', JText::_('COM_JCALPRO_CATEGORY'), $data['title'], $table->getError()));
					}
					if (!$table->store()) {
						throw new Exception(JText::sprintf('COM_JCALPRO_MIGRATION_STORE_ERROR', JText::_('COM_JCALPRO_CATEGORY'), $data['title'], $table->getError()));
					}
					$table->moveByReference($cal_id, 'last-child', $table->id);
					
					// update the temp xref table (so we can assign categories later)
					$db->setQuery('INSERT INTO #__jcalpro_migration_xref (old_id,new_id,type) VALUES (' . ((int) $item->cat_id) . ',' . ((int) $table->id) . ',' . $db->quote($type) . ')');
					$db->query();
				}
				break;
			case 'events':
				jimport('jcaldate.date');
				jimport('jcaldate.timezone');
				// get the rec_id, if there is one
				$rec_id = 0;
				if ($item->rec_id) {
					$db->setQuery('SELECT new_id FROM #__jcalpro_migration_xref WHERE old_id = ' . $item->rec_id . ' AND type = ' . $db->quote('events'));
					$rec_id = $db->loadResult();
				}
				// get the timezone
				$timezone = JCalTimeZone::joomla();
				$db->setQuery('SELECT value FROM #__jcalpro2_config WHERE name = ' . $db->quote('site_timezone'));
				$tz = $db->loadResult();
				if ($tz) $timezone = $tz;
				// get the end
				list($recur_end_until, $tmp) = explode(' ', $item->recur_until);
				// ensure we have a category before continuing
				$db->setQuery('SELECT new_id FROM #__jcalpro_migration_xref WHERE old_id = ' . $item->cat . ' AND type = ' . $db->quote('categories'));
				$category = $db->loadResult();
				if (empty($category)) {
					throw new Exception('COM_JCALPRO_MIGRATION_ERROR_CANNOT_FIND_EVENT_CATEGORY');
				}
				// create the base DateTime object for this event
				$date = JCalDate::createFromMySQLFormat($item->start_date, JCalTimeZone::utc())->toTimezone($timezone);
				// before creating the event data, we have to calculate the end time back out
				// "all day" and "no end" are easy - they will have dates set as constants
				$duration_type = 0;
				$end_days      = 0;
				$end_hours     = 0;
				$end_minutes   = 0;
				switch ($item->end_date) {
					case JCalPro::JCL_ALL_DAY_EVENT_END_DATE:
					case JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY:
					case JCalPro::JCL_ALL_DAY_EVENT_END_DATE_LEGACY_2:
						$duration_type = JCalPro::JCL_EVENT_DURATION_ALL;
						break;
					case JCalPro::JCL_EVENT_NO_END_DATE:
						$duration_type = JCalPro::JCL_EVENT_DURATION_NONE;
						break;
					default:
						$duration_type = JCalPro::JCL_EVENT_DURATION_DATE;
						// this is nasty - we have to determine the values to use here
						// we COULD use DateTime::diff if we're on PHP 5.3, or if our internal date handler
						// had a complete PHP 5.2 backwards-compatible implementation
						// oh well - do it the hard way :'(
						$end = JCalDate::createFromMySQLFormat($item->end_date, JCalTimeZone::utc())->toTimezone($timezone);
						// days first - just check if the month & day are the same
						while (!($date->day() == $end->day() && $date->month() == $end->month() && $date->year() == $end->year())) {
							$end->subDay();
							$end_days++;
						}
						// handle hours
						while ($date->hour() != $end->hour()) {
							$end->subHour();
							$end_hours++;
						}
						// handle minutes
						while ($date->minute() != $end->minute()) {
							$end->subMin();
							$end_minutes++;
						}
						break;
				}
				// build a "params" array for things we ditched (email, etc)
				$contactinfo = array(
					'contact' => $item->contact
				,	'url'     => $item->url
				,	'email'   => $item->email
				);
				// now create the event data
				$data = array(
					'title'                    => $item->title
				,	'description'              => $item->description
				,	'common_event_id'          => $item->common_event_id
				,	'rec_id'                   => $rec_id
				,	'detached_from_rec'        => $item->detached_from_rec
				,	'day'                      => $date->day()
				,	'month'                    => $date->month()
				,	'year'                     => $date->year()
				,	'hour'                     => $date->hour()
				,	'minute'                   => $date->minute()
				,	'timezone'                 => $date->timezone()
				,	'recur_type'               => $item->rec_type_select
				,	'recur_end_type'           => $item->recur_end_type
				,	'recur_end_count'          => $item->recur_count
				,	'recur_end_until'          => $recur_end_until
				,	'approved'                 => $item->approved
				,	'private'                  => $item->private
				,	'published'                => $item->published
				,	'rec_daily_period'         => $item->rec_daily_period
				,	'rec_weekly_period'        => $item->rec_weekly_period
				,	'rec_weekly_on_monday'     => $item->rec_weekly_on_monday
				,	'rec_weekly_on_tuesday'    => $item->rec_weekly_on_tuesday
				,	'rec_weekly_on_wednesday'  => $item->rec_weekly_on_wednesday
				,	'rec_weekly_on_thursday'   => $item->rec_weekly_on_thursday
				,	'rec_weekly_on_friday'     => $item->rec_weekly_on_friday
				,	'rec_weekly_on_saturday'   => $item->rec_weekly_on_saturday
				,	'rec_weekly_on_sunday'     => $item->rec_weekly_on_sunday
				,	'rec_monthly_period'       => $item->rec_monthly_period
				,	'rec_monthly_type'         => $item->rec_monthly_type
				,	'rec_monthly_day_number'   => $item->rec_monthly_day_number
				,	'rec_monthly_day_list'     => $item->rec_monthly_day_list
				,	'rec_monthly_day_order'    => $item->rec_monthly_day_order
				,	'rec_monthly_day_type'     => $item->rec_monthly_day_type
				,	'rec_yearly_period'        => $item->rec_yearly_period
				,	'rec_yearly_type'          => $item->rec_yearly_type
				// BUGFIX: rec_yearly_on_month in v2 uses a 0-11 month list
				// v3 uses 1-12 - we have to adjust this
				,	'rec_yearly_on_month'      => (((int) $item->rec_yearly_on_month) + 1)
				,	'rec_yearly_on_month_list' => $item->rec_yearly_on_month_list
				,	'rec_yearly_day_number'    => $item->rec_yearly_day_number
				,	'rec_yearly_day_order'     => $item->rec_yearly_day_order
				,	'rec_yearly_day_type'      => $item->rec_yearly_day_type
				,	'duration_type'            => $duration_type
				,	'end_days'                 => $end_days
				,	'end_hours'                => $end_hours
				,	'end_minutes'              => $end_minutes
				,	'params'                   => $contactinfo
				,	'canonical'                => $category
				,	'registration'             => 0
				);
				$table = JTable::getInstance('Event', 'JCalProTable');
				if (!$table->bind($data)) {
					throw new Exception(JText::sprintf('COM_JCALPRO_MIGRATION_BIND_ERROR', JText::_('COM_JCALPRO_EVENT'), $data['title'], $table->getError()));
				}
				if (!$table->check()) {
					throw new Exception(JText::sprintf('COM_JCALPRO_MIGRATION_CHECK_ERROR', JText::_('COM_JCALPRO_EVENT'), $data['title'], $table->getError()));
				}
				if (!$table->store()) {
					throw new Exception(JText::sprintf('COM_JCALPRO_MIGRATION_STORE_ERROR', JText::_('COM_JCALPRO_EVENT'), $data['title'], $table->getError()));
				}
				// update the temp xref table (so we can assign categories later)
				$db->setQuery('INSERT INTO #__jcalpro_migration_xref (old_id,new_id,type) VALUES (' . ((int) $item->extid) . ',' . ((int) $table->id) . ',' . $db->quote($type) . ')');
				$db->query();
				break;
			default:
				throw new Exception('TODO');
		}
	}
	
	/**
	 * Begins migration of data
	 * 
	 */
	private function _installMigrationData() {
		// ensure migration can be run
		if (!$this->checkMigration()) {
			$this->setError(JText::_('COM_JCALPRO_MIGRATION_NO_DATA'));
			return false;
		}
		
		// instead of directly running the migration
		// send back enough just enough js
		// to add in the migration js script
		$file     = '../media/jcalpro/js/migration.js';
		$script   = array();
		$script[] = '(function(){';
		$script[] = "var s = document.createElement('script');";
		$script[] = "s.setAttribute('src', '$file');";
		$script[] = "document.body.appendChild(s);";
		$script[] = '})();';
		
		// add in migration texts manually (JText::script won't work here)
		$script[] = 'window.jcl_migration_text = {';
		$script[] = "	contacting_host: '" . JCalProHelperFilter::escape_js(JText::_('COM_JCALPRO_MIGRATION_CONTACTING_HOST')) . "',";
		$script[] = "	bad_request: '" . JCalProHelperFilter::escape_js(JText::_('COM_JCALPRO_MIGRATION_BAD_REQUEST')) . "',";
		$script[] = "	not_finished: '" . JCalProHelperFilter::escape_js(JText::_('COM_JCALPRO_MIGRATION_NOT_FINISHED')) . "',";
		$script[] = "	finished: '" . JCalProHelperFilter::escape_js(JText::_('COM_JCALPRO_MIGRATION_FINISHED')) . "'";
		$script[] = '};';
		
		return array(
			'ok' => JText::_('COM_JCALPRO_MIGRATION_STARTED')
		,	'eval' => implode("", $script)
		);
		
		// return an ok :)
		return array('ok' => JText::_('COM_JCALPRO_MIGRATION_COMPLETE'));
	}
	
	private function _installSampleData() {
		if ($this->checkCategories()) {
			$this->setError(JText::_('COM_JCALPRO_SAMPLEDATA_ALREADY_HAS_DATA'));
			return false;
		}
		$db = JFactory::getDbo();
		// start with fields
		$fields = array(
			array(
				'name'          => 'email'
			,	'title'         => JText::_('COM_JCALPRO_SAMPLEDATA_FIELD_EMAIL_TITLE')
			,	'type'          => 'email'
			,	'description'   => JText::_('COM_JCALPRO_SAMPLEDATA_FIELD_EMAIL_DESCRIPTION')
			,	'published'     => 1
			,	'formtype'      => 0
			,	'event_display' => 1
			,	'params'        => '{"classname":"inputbox input-block-level","attrs":{"required":"true"}}'
			)
		,	array(
				'name'          => 'url'
			,	'title'         => JText::_('COM_JCALPRO_SAMPLEDATA_FIELD_URL_TITLE')
			,	'type'          => 'url'
			,	'description'   => JText::_('COM_JCALPRO_SAMPLEDATA_FIELD_URL_DESCRIPTION')
			,	'published'     => 1
			,	'formtype'      => 0
			,	'event_display' => 1
			,	'params'        => '{"classname":"inputbox input-block-level"}'
			)
		,	array(
				'name'          => 'list'
			,	'title'         => JText::_('COM_JCALPRO_SAMPLEDATA_FIELD_LIST_TITLE')
			,	'type'          => 'list'
			,	'description'   => JText::_('COM_JCALPRO_SAMPLEDATA_FIELD_LIST_DESCRIPTION')
			,	'published'     => 1
			,	'formtype'      => 0
			,	'event_display' => 1
			,	'params'        => '{"classname":"inputbox input-block-level","opts":{"Option A":"A","Option B":"B","Option C":"C","Option D":"D","Option E":"E"},"attrs":{"multiple":"true","required":"true","size":"3"}}'
			)
		,	array(
				'name'          => 'integer'
			,	'title'         => JText::_('COM_JCALPRO_SAMPLEDATA_FIELD_INTEGER_TITLE')
			,	'type'          => 'integer'
			,	'description'   => JText::_('COM_JCALPRO_SAMPLEDATA_FIELD_INTEGER_DESCRIPTION')
			,	'published'     => 1
			,	'formtype'      => 0
			,	'event_display' => 1
			,	'params'        => '{"classname":"inputbox input-block-level","attrs":{"first":"1","last":"10","step":"1"}}'
			)
		,	array(
				'name'          => 'tel'
			,	'title'         => JText::_('COM_JCALPRO_SAMPLEDATA_FIELD_TEL_TITLE')
			,	'type'          => 'tel'
			,	'description'   => JText::_('COM_JCALPRO_SAMPLEDATA_FIELD_TEL_DESCRIPTION')
			,	'published'     => 1
			,	'formtype'      => 1
			,	'event_display' => 1
			,	'params'        => '{"classname":"inputbox input-block-level"}'
			)
		);
		
		$efieldids = array();
		$rfieldids = array();
		foreach ($fields as $field) {
			$table = JTable::getInstance('Field', 'JCalProTable');
			if (!$table->bind($field)) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_BIND_ERROR', JText::_('COM_JCALPRO_FIELD'), $field['title'], $table->getError()));
				return false;
			}
			if (!$table->check()) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_CHECK_ERROR', JText::_('COM_JCALPRO_FIELD'), $field['title'], $table->getError()));
				return false;
			}
			if (!$table->store()) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_STORE_ERROR', JText::_('COM_JCALPRO_FIELD'), $field['title'], $table->getError()));
				return false;
			}
			switch ($field['formtype']) {
				case 0 : $efieldids[] = $table->id; break;
				case 1 : $rfieldids[] = $table->id; break;
				default:
					$efieldids[] = $table->id;
					$rfieldids[] = $table->id;
					break;
			}
		}
		// follow with forms
		$forms = array(
			array(
				'title'      => JText::_('COM_JCALPRO_SAMPLEDATA_FORM_EVENT_TITLE')
			,	'type'       => 0
			,	'published'  => 1
			,	'default'    => 1
			, 'formfields' => implode('|', $efieldids)
			)
		,	array(
				'title'      => JText::_('COM_JCALPRO_SAMPLEDATA_FORM_REGISTRATION_TITLE')
			,	'type'       => 1
			,	'published'  => 1
			,	'default'    => 1
			, 'formfields' => implode('|', $rfieldids)
			)
		);
		$formids = array(
			'event'        => ''
		,	'registration' => ''
		);
		foreach ($forms as $form) {
			$table = JTable::getInstance('Form', 'JCalProTable');
			if (!$table->bind($form)) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_BIND_ERROR', JText::_('COM_JCALPRO_FORM'), $form['title'], $table->getError()));
				return false;
			}
			if (!$table->check()) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_CHECK_ERROR', JText::_('COM_JCALPRO_FORM'), $form['title'], $table->getError()));
				return false;
			}
			if (!$table->store()) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_STORE_ERROR', JText::_('COM_JCALPRO_FORM'), $form['title'], $table->getError()));
				return false;
			}
			$formids[0 == $form['type'] ? 'event' : 'registration'] = $table->id;
		}
		// before we save the categories, we need to pull the asset data for the main component
		$rules = $this->_getRules();
		// create new categories using JTable
		$category = array(
			array(
				'title'       => JText::_('COM_JCALPRO_SAMPLEDATA_CATEGORY_TITLE')
			,	'description' => JText::_('COM_JCALPRO_SAMPLEDATA_CATEGORY_DESCRIPTION')
			,	'extension'   => 'com_jcalpro'
			,	'parent_id'   => 1
			,	'published'   => 1
			,	'access'      => 1
			,	'language'    => '*'
			,	'rules'       => $rules
			,	'params'      => array(
					'jcalpro_color'            => 'FF0000'
				,	'jcalpro_eventform'        => $formids['event']
				,	'jcalpro_registrationform' => $formids['registration']
				)
			,	'metadata' => array(
					'page_title' => ''
				,	'author'     => ''
				,	'robots'     => ''
				,	'tags'       => ''
				)
			)
		,	array(
				'title'       => JText::_('COM_JCALPRO_SAMPLEDATA_CATEGORY_TITLE2')
			,	'description' => JText::_('COM_JCALPRO_SAMPLEDATA_CATEGORY_DESCRIPTION2')
			,	'extension'   => 'com_jcalpro'
			,	'parent_id'   => 1
			,	'published'   => 1
			,	'access'      => 1
			,	'language'    => '*'
			,	'rules'       => $rules
			,	'params' => array(
					'jcalpro_color'            => 'FFFFFF'
				,	'jcalpro_eventform'        => $formids['event']
				,	'jcalpro_registrationform' => $formids['registration']
				)
			,	'metadata' => array(
					'page_title' => ''
				,	'author'     => ''
				,	'robots'     => ''
				,	'tags'       => ''
				)
			)
		);
		$catids = array();
		foreach ($category as $cat) {
			$table = JTable::getInstance('Category');
			if (!$table->bind($cat)) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_BIND_ERROR', JText::_('COM_JCALPRO_CATEGORY'), $cat['title'], $table->getError()));
				return false;
			}
			if (!$table->check()) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_CHECK_ERROR', JText::_('COM_JCALPRO_CATEGORY'), $cat['title'], $table->getError()));
				return false;
			}
			if (!$table->store()) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_STORE_ERROR', JText::_('COM_JCALPRO_CATEGORY'), $cat['title'], $table->getError()));
				return false;
			}
			$table->moveByReference(0, 'last-child', $table->id);
			$catids[] = $table->id;
		}
		
		// end with events with dates based on "today"
		$today  = JCalProHelperDate::getToday()->toJoomla()->toHourStart();
		$base   = clone $today;
		$email  = JFactory::getConfig()->get('mailfrom');
		$url    = JUri::root();
		// set base
		// start with 2 days after today at 2pm
		$base->addDay(2)->toHour(14);
		
		$events = array(
			array(
				'title'              => JText::_('COM_JCALPRO_SAMPLEDATA_EVENT_NORMAL_TITLE')
			,	'description'        => JText::_('COM_JCALPRO_SAMPLEDATA_EVENT_NORMAL_DESCRIPTION')
			,	'rec_id'             => 0
			,	'detached_from_rec'  => 0
			,	'day'                => $base->day()
			,	'month'              => $base->month()
			,	'year'               => $base->year()
			,	'hour'               => $base->hour()
			,	'minute'             => $base->minute()
			,	'timezone'           => $base->timezone()
			,	'start_date'         => $base->toSql()
			// this event lasts 2 hours
			,	'end_date'           => $base->addHour(2)->toSql()
			,	'recur_type'         => 0
			,	'recur_end_type'     => 1
			,	'recur_end_count'    => 2
			,	'recur_end_until'    => ''
			,	'approved'           => 1
			,	'published'          => 1
			,	'duration_type'      => 1
			,	'end_days'           => 0
			,	'end_hours'          => 2
			,	'end_minutes'        => 0
			,	'params'             => array('email' => $email, 'url' => $url, 'list' => array('C', 'D'))
			,	'canonical'          => $catids[0]
			,	'registration'       => 1
			,	'registration_capacity'     => 200
			,	'registration_start_day'    => $today->day()
			,	'registration_start_month'  => $today->month()
			,	'registration_start_year'   => $today->year()
			,	'registration_start_hour'   => $today->hour()
			,	'registration_start_minute' => $today->minute()
			,	'registration_until_event'  => 1
			)
		,	array(
				'title'              => JText::_('COM_JCALPRO_SAMPLEDATA_EVENT_REPEAT_TITLE')
			,	'description'        => JText::_('COM_JCALPRO_SAMPLEDATA_EVENT_REPEAT_DESCRIPTION')
			,	'rec_id'             => 0
			,	'detached_from_rec'  => 0
			// here we increase the event by 3 days and set it to noon
			,	'day'                => $base->addDay(3)->toHour(12)->day()
			,	'month'              => $base->month()
			,	'year'               => $base->year()
			,	'hour'               => $base->hour()
			,	'minute'             => $base->minute()
			,	'timezone'           => $base->timezone()
			,	'start_date'         => $base->toSql()
			// this event lasts 2 hours
			,	'end_date'           => $base->addHour(2)->toSql()
			// repeating daily event
			,	'recur_type'         => 1
			,	'rec_daily_period'   => 1
			,	'recur_end_type'     => 1
			,	'recur_end_count'    => 3
			,	'recur_end_until'    => ''
			,	'approved'           => 1
			,	'published'          => 1
			,	'duration_type'      => 2
			,	'end_days'           => 0
			,	'end_hours'          => 1
			,	'end_minutes'        => 0
			,	'params'             => array('email' => $email, 'url' => $url, 'list' => array('A', 'B'))
			,	'canonical'          => $catids[1]
			,	'registration'       => 0
			)
		);
		$eventids = array();
		foreach ($events as $event) {
			$table = JTable::getInstance('Event', 'JCalProTable');
			if (!$table->bind($event)) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_BIND_ERROR', JText::_('COM_JCALPRO_EVENT'), $event['title'], $table->getError()));
				return false;
			}
			if (!$table->check()) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_CHECK_ERROR', JText::_('COM_JCALPRO_EVENT'), $event['title'], $table->getError()));
				return false;
			}
			if (!$table->store()) {
				$this->setError(JText::sprintf('COM_JCALPRO_SAMPLEDATA_STORE_ERROR', JText::_('COM_JCALPRO_EVENT'), $event['title'], $table->getError()));
				return false;
			}
			$eventids[] = $table->id;
		}
		// extra fix for approval
		$db->setQuery((string) $db->getQuery(true)
			->update('#__jcalpro_events')
			->set($db->quoteName('approved') . ' = 1')
			->where('(' . $db->quoteName('id') . ' IN (' . implode(',', $eventids) . ') OR ' . $db->quoteName('rec_id') . ' IN (' . implode(',', $eventids) . '))')
		);
		$db->query();
		// return an ok :)
		return array('ok' => JText::_('COM_JCALPRO_SAMPLEDATA_INSTALLED'));
	}
	
	private function _getRules() {
		static $rules;
		if (is_null($rules)) {
			$db = JFactory::getDbo();
			$db->setQuery((string) $db->getQuery(true)
					->select('rules')
					->from('#__assets')
					->where($db->quoteName('name') . ' = ' . $db->Quote('com_jcalpro'))
			);
			$rules = $db->loadResult();
			$registry = new JRegistry();
			$registry->loadString($rules);
			$rules = $registry->toArray();
			$keys = array("core.create","core.delete","core.edit","core.edit.state","core.edit.own");
			foreach (array_keys($rules) as $key) {
				if (!in_array($key, $keys)) {
					unset($rules[$key]);
					continue;
				}
				if ("core.edit.state" == $key) {
					$rules["core.moderate"] = $rules[$key];
				}
				if ("core.create" == $key) {
					$rules["core.create.private"] = $rules[$key];
				}
			}
		}
		return $rules;
	}
	
	
	/**
	 * Apparently there is at least one jacked up quickstart package
	 * that is inserting categories without nesting them properly,
	 * which causes JCalPro to flip its shit in sef mode
	 *
	 * This truly sucks because the sample data installed with JCalPro
	 * ends up with an incorrect parent, no path, and makes sef break
	 *
	 */
	private function _fixBrokenCategories() {
		$debug = defined('JDEBUG') && JDEBUG;
		$app   = JFactory::getApplication();
		$db    = JFactory::getDbo();
		// find out if we need to even bother with this insanity
		$db->setQuery($db->getQuery(true)
				->select('*')
				->from('#__categories')
				->where($db->quoteName('level') . '=' . $db->quote('0'))
				->where($db->quoteName('alias') . '<>' . $db->quote('root'))
		);
		try {
			$broken = $db->loadObjectList();
			if (empty($broken)) {
				throw new Exception("Hurrah! This site isn't screwed!");
			}
		}
		catch (Exception $e) {
			return array('ok' => JText::sprintf('COM_JCALPRO_FIXED_N_CATEGORIES', 0));
		}
		// fix each one by loading it as a table and moving it nowhere
		$fixed = 0;
		foreach ($broken as $cat) {
			$cat->parent_id = 1;
			$table = JTable::getInstance('Category');
			$table->load($cat->id);
			$bind = JArrayHelper::fromObject($cat);
			if (!$table->bind($bind)) {
				continue;
			}
			if (!$table->check()) {
				continue;
			}
			if (!$table->store()) {
				continue;
			}
			$table->moveByReference(0, 'last-child', $table->id);
			$fixed++;
		}
		return array('ok' => JText::sprintf('COM_JCALPRO_FIXED_N_CATEGORIES', $fixed));
	}
	
	private function _fixBrokenAssets() {
		$return  = array('fixed' => 0, 'found' => 0, 'errors' => array());
		$db = JFactory::getDbo();
		$db->setQuery($db->getQuery(true)
			->select('a.id, a.title, c.category_id')
			->from('#__jcalpro_events AS a')
			->leftJoin('#__assets AS b ON a.asset_id = b.id')
			->leftJoin('#__jcalpro_event_categories AS c ON a.id = c.event_id AND c.canonical = 1')
			->where('b.id IS NULL')
		);
		
		try {
			$broken = $db->loadObjectList();
		}
		catch (Exception $e) {
			return array('errors' => $e->getMessage());
		}
		
		$return['found'] = count($broken);
		$parents = array();
		
		foreach ($broken as $record) {
			$parent = 'com_jcalpro.category.' . $record->category_id;
			if (!array_key_exists($parent, $parents)) {
				$parents[$parent] = JTable::getInstance('Asset', 'JTable', array('dbo' => $db));
				$parents[$parent]->loadByName('com_jcalpro.category.' . $record->category_id);
			}
			$parent_id = $parents[$parent]->id;
			$asset = JTable::getInstance('Asset', 'JTable', array('dbo' => $db));
			$data  = array(
				'parent_id' => $parent_id
			,	'name'      => 'com_jcalpro.event.' . $record->id
			,	'title'     => $record->title
			,	'rules'     => '{}'
			);
			if (!$asset->bind($data)) {
				$return['errors'][] = $asset->getError();
				continue;
			}
			if (!$asset->check()) {
				$return['errors'][] = $asset->getError();
				continue;
			}
			if (!$asset->store()) {
				$return['errors'][] = $asset->getError();
				continue;
			}
			$asset->moveByReference($parent_id, 'last-child', $asset->id);
			$db->setQuery($db->getQuery(true)
				->update('#__jcalpro_events')
				->set('asset_id = ' . intval($asset->id))
				->where('id = ' . intval($record->id))
			);
			try {
				$db->query();
			}
			catch (Exception $e) {
				$return['errors'][] = $e->getMessage();
				continue;
			}
			$return['fixed']++;
		}
		if (empty($return['errors'])) {
			$return = array('ok' => JText::sprintf('COM_JCALPRO_FIXED_N_ASSETS', $return['fixed']));
		}
		else {
			$return['error'] = implode('<br/>', $errors);
		}
		unset($return['errors']);
		unset($return['fixed']);
		return $return;
	}
}
