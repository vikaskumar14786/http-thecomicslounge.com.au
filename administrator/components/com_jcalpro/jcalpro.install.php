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

class com_JCalProInstallerScript
{
	public $parent = null;
	
	protected $_jc_extension = 'com_jcalpro';
	
	protected $_jc_uninstall;
	
	protected $_jc_categories;
	
	protected $_jc_assets;
	
	protected $fixupdateserver = false;
	
	protected $db;
	
	protected $app;
	
	function __construct() {
		$this->db = JFactory::getDbo();
		$this->app = JFactory::getApplication();
	}
	
	/**
	 * Joomla! pre-flight event
	 * 
	 * @param string $type Installation type (install, update, discover_install)
	 * @param JInstaller $parent Parent object
	 */
	public function preflight($type, $parent) {
		// ensure update urls are set
		if (in_array($type, array('install', 'discover_install', 'update'))) {
			$this->_forceUpdateUrls();
		}
		// Joomla! 1.6/1.7 bugfix for "Can not build admin menus"
		if (in_array($type, array('install', 'discover_install'))) {
			$this->_bugfixDBFunctionReturnedNoError();
		}
		else {
			$this->_bugfixCantBuildAdminMenus();
		}
	}
	
	public function postflight($type, $parent) {
		$version = new JVersion;
		// enable the plugins
		try {
			$this->db->setQuery('UPDATE `#__extensions` SET `enabled`=1 WHERE (`element`="jcalpro" OR `element`="sh404sefextplugincom_jcalpro") AND `folder` IN ("system", "content", "extension", "sh404sefextplugins", "user") AND `type`="plugin"')->query();
		}
		catch (Exception $e) {
			$this->app->enqueueMessage($e->getMessage(), 'error');
		}
		// try to fix frontend menus, if any are available
		$this->_fixFrontendMenus();
		switch ($type) {
			case 'install':
			case 'discover_install':
				// fix component config
				$this->_saveDefaults($parent);
				// NOTE: no break here!!!!!!!!!!!!
			case 'update':
				// set the acls
				$this->_setACLs();
				// try to add default emails to database
				$this->_installEmailTemplates($parent);
				// add tags
				if ($version->isCompatible('3.1.0')) {
					$this->_installContentTypes();
				}
				// fix xref
				$this->_addEventChildrenToXref();
				// fix any events with bad language
				try {
					$this->db->setQuery($this->db->getQuery(true)
						->update('#__jcalpro_events')
						->set($this->db->quoteName('language') . ' = ' . $this->db->quote('*'))
						->where($this->db->quoteName('language') . ' = ' . $this->db->quote(''))
					)->query();
				}
				catch (Exception $e) {
					$this->app->enqueueMessage($e->getMessage(), 'error');
				}
				
				break;
		}
	}
	
	public function install($parent) {
		$this->parent = $parent->getParent();
	}

	public function update($parent) {
		$this->parent = $parent->getParent();
	}
	
	public function uninstall($parent) {
		$this->parent = $parent->getParent();
		
		// remove content types and ucm entries for 3.1.x+
		$version = new JVersion;
		if ($version->isCompatible('3.1.0')) {
			// we need the content type ids to remove the rest of the data
			try {
				$ids = $this->db->setQuery($this->db->getQuery(true)
					->select('type_id')
					->from('#__content_types')
					->where($this->db->quoteName('type_alias') . ' LIKE ' . $this->db->quote('com_jcalpro.%'))
				)->loadColumn();
				if (!is_array($ids)) {
					$ids = array();
				}
			}
			catch (Exception $e) {
				$this->app->enqueueMessage($e->getMessage(), 'error');
				$ids = array();
			}
			
			// only continue if we have ids
			if (!empty($ids)) {
				// delete entries in the content types and tags mapping tables
				foreach (array(
					'#__content_types'       => 'type_alias'
				,	'#__contentitem_tag_map' => 'type_alias'
				,	'#__ucm_content'         => 'core_type_alias'
				) as $table => $column) {
					try {
						$this->db->setQuery($this->db->getQuery(true)
							->delete($table)
							->where($this->db->quoteName($column) . ' LIKE ' . $this->db->quote('com_jcalpro.%'))
						)->query();
					}
					catch (Exception $e) {
						$this->app->enqueueMessage($e->getMessage(), 'error');
					}
				}
				// now delete from the base table
				try {
					$this->db->setQuery($this->db->getQuery(true)
						->delete('#__ucm_base')
						->where($this->db->quoteName('ucm_type_id') . ' IN(' . implode(',', $ids) . ')')
					)->query();
				}
				catch (Exception $e) {
					$this->app->enqueueMessage($e->getMessage(), 'error');
				}
			}
		}
	}
	
	// checks if the orphan db table is empty and populates it if necessary
	private function _addEventChildrenToXref() {
		try {
			$xref = $this->db->setQuery($this->db->getQuery(true)
				->select('*')
				->from('#__jcalpro_event_xref')
			)->loadObjectList();
		}
		catch (Exception $e) {
			// we don't want to keep going if there was an error
			$xref = true;
		}
		if (empty($xref)) {
			try {
				$xrefs = $this->db->setQuery($this->db->getQuery(true)
					->select('id')
					->select('rec_id')
					->from('#__jcalpro_events')
					->where('rec_id <> 0')
				)->loadObjectList();
			}
			catch (Exception $e) {
				$xrefs = false;
			}
			if (!empty($xrefs)) {
				$query = $this->db->getQuery(true)
					->insert('#__jcalpro_event_xref')
					->columns('parent_id, child_id')
				;
				foreach ($xrefs as $xref) {
					$query->values(((int) $xref->rec_id) . ', ' . ((int) $xref->id));
				}
				$this->db->setQuery($query);
				try {
					$this->db->query();
				}
				catch (Exception $e) {
					$this->app->enqueueMessage($e->getMessage(), 'error');
				}
			}
		}
	}
	
	private function _saveDefaults(&$parent) {
		jimport('joomla.filesystem.file');
		jimport('joomla.form.form');
		
		if (method_exists($parent, 'extension_root')) {
			$configfile = $parent->getPath('extension_root') . '/config.xml';
		}
		else {
			$configfile = $parent->getParent()->getPath('extension_root') . '/config.xml';
		}
		
		if (!JFile::exists($configfile)) {
			return;
		}
		
		$xml       = JFile::read($configfile);
		$form      = JForm::getInstance('installer', $xml, array(), false, '/config');
		$params    = array();
		$fieldsets = $form->getFieldsets();
		
		if (!empty($fieldsets)) {
			foreach ($fieldsets as $fieldset) {
				$fields = $form->getFieldset($fieldset->name);
				if (!empty($fields)) {
					foreach ($fields as $name => $field) {
						$params[$field->__get('name')] = $field->__get('value');
					}
				}
			}
		}
		
		// set default theme if above 2.5
		$version = new JVersion();
		if ($version->isCompatible('3.0.0')) {
			$params['default_theme'] = 'inspired';
		}
		
		$this->db->setQuery($this->db->getQuery(true)
			->update('#__extensions')
			->set('params = ' . $this->db->quote(json_encode($params)))
			->where('element = ' . $this->db->quote($parent->get('element')))
		);
		try {
			$this->db->query();
		}
		catch (Exception $e) {
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
		
	}
	
	private function _installEmailTemplates(&$parent) {
		$this->db->setQuery('SHOW TABLES LIKE "%jcalpro_emails"');
		try {
			$table = $this->db->loadResult();
		}
		catch (Exception $e) {
			// uh oh, not good!
			$this->app->enqueueMessage(JText::sprintf('COM_JCALPRO_INSTALLER_SQL_ERROR', $e->getMessage()), 'error');
			return;
		}
		
		if ($table) {
			// get the installed langauges for this site
			$langs = JLanguage::getKnownLanguages(JPATH_ROOT);
			$default = JLanguageHelper::detectLanguage();
			
			if (!is_array($langs)) {
				// uh oh, not good!
				$this->app->enqueueMessage(JText::_('COM_JCALPRO_INSTALLER_ERROR_NO_LANGS'));
				return;
			}
			$langs = array_keys($langs);
			
			if (is_null($default)) $default = '*';
			
			// the emails we're going to need
			$contexts = array(
				// events
				'event.admin.approve', 'event.user.added', 'event.user.approve',
				// registrations
				'registration.confirm', 'registration.confirmed', 'registration.notify'
			);
			// loop the languages and load the language file needed
			$base  = 'COM_JCALPRO_EMAIL_INSTALL';
			$check = "$base";
			foreach ($langs as $lang) {
				//if (JDEBUG) $this->app->enqueueMessage("Installing emails for language $lang (default $default)");
				$cxs = array_merge($contexts, array());
				// load this language
				JFactory::getLanguage()->load('com_jcalpro.emails', JPATH_ADMINISTRATOR, $lang, true);
				// check if this language loaded
				if (JText::_($base) == $check || '' == JText::_($base)) {
					//if (JDEBUG) $this->app->enqueueMessage("Language $lang not supported, found '$check' == JText::_('$base')");
					continue;
				}
				// if this is loaded, reset the check
				$check = JText::_($base);
				// load any existing emails for this language from the database
				$this->db->setQuery($this->db->getQuery(true)
					->select($this->db->quoteName('context'))
					->from('#__jcalpro_emails')
					->where($this->db->quoteName('language') . ' = ' . $this->db->quote($lang))
				);
				// try to get the emails
				try {
					$emails = $this->db->loadObjectList();
					// we have emails - see if we need to add this one
					if (!empty($emails)) {
						foreach ($emails as $email) {
							$key = array_search($email->context, $cxs);
							if (array_key_exists($key, $cxs) && $email->context == $cxs[$key]) {
								unset($cxs[$key]);
								$cxs = array_values($cxs);
							}
						}
					}
				}
				catch (Exception $e) {
					$this->app->enqueueMessage(JText::sprintf('COM_JCALPRO_INSTALLER_SQL_ERROR', $e->getMessage()), 'error');
					continue;
				}
				
				// let's see, we need to go ahead & try to insert each context, but only if
				// no other emails exist for this context - loop the contexts and check before
				// going further
				$values = array();
				foreach ($cxs as $i => $context) {
					$key      = strtoupper(str_replace('.', '_', $context));
					$body     = JText::_('COM_JCALPRO_EMAIL_DEFAULT_BODY_' . $key);
					$subject  = JText::_('COM_JCALPRO_EMAIL_DEFAULT_SUBJECT_' . $key);
					if ('' == $body || '' == $subject) {
						continue;
					}
					$values[] = $this->db->quote($context) . ','
					. $this->db->quote($body) . ','
					. $this->db->quote($subject) . ','
					. $this->db->quote($lang) . ','
					. ($default == $lang ? '1' : '0')
					;
				}
				if (!empty($values)) {
					$this->db->setQuery('INSERT IGNORE INTO #__jcalpro_emails (' . $this->db->quoteName('context') . ', ' . $this->db->quoteName('body') . ', ' . $this->db->quoteName('subject') . ', ' . $this->db->quoteName('language') . ', ' . $this->db->quoteName('default') . ') VALUES (' . implode('), (', $values) . ')');
					try {
						$this->db->query();
					}
					catch (Exception $e) {
						$this->app->enqueueMessage(JText::sprintf('COM_JCALPRO_INSTALLER_SQL_ERROR', $e->getMessage()), 'error');
						continue;
					}
				}
			}
		}
	}
	
	/**
	 * As of 3.2.7 JCalPro no longer uses its own captcha plugin
	 * 
	 */
	private function _uninstallCaptchaPlugin() {
		// check if the plugin is installed
		$this->db->setQuery($this->db->getQuery(true)
			->select('extension_id')
			->select('enabled')
			->select('params')
			->from('#__extensions')
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('jclcaptcha'))
		);
		try {
			$captcha = $this->db->loadObject();
		}
		catch (Exception $e) {
			return;
		}
		if (empty($captcha)) {
			return;
		}
		// if the plugin was enabled, enable it in config
		if ($captcha->enabled) {
			// TODO
		}
		// remove the plugin
		JInstaller::getInstance()->uninstall('plugin', $captcha->extension_id);
	}
	
	/**
	 * Installs/updates the core Contenttype entries for JCalPro
	 * 
	 * @since 3.2.5
	 */
	private function _installContentTypes() {
		$data = array(
			array(
				'type_title'     => 'JCALPRO_CATEGORY'
			,	'type_alias'     => 'com_jcalpro.category'
			,	'table'          => json_encode(array(
					"special" => array(
						"dbtable" => "#__categories"
					,	"key"     => "id"
					,	"type"    => "Category"
					,	"prefix"  => "JTable"
					,	"config"  => "array()"
					)
				,	"common" => array(
						"dbtable" => "#__core_content"
					,	"key"     => "ucm_id"
					,	"type"    => "Corecontent"
					,	"prefix"  => "JTable"
					,	"config"  => "array()"
					)
				))
			,	'field_mappings' => json_encode(array(
					"common" => array(
						array(
							"core_content_item_id" => "id"
						,	"core_title"           => "title"
						,	"core_state"           => "published"
						,	"core_alias"           => "alias"
						,	"core_created_time"    => "created_time"
						,	"core_modified_time"   => "modified_time"
						,	"core_body"            => "description"
						,	"core_hits"            => "hits"
						,	"core_publish_up"      => "null"
						,	"core_publish_down"    => "null"
						,	"core_access"          => "access"
						,	"core_params"          => "params"
						,	"core_featured"        => "null"
						,	"core_metadata"        => "metadata"
						,	"core_language"        => "language"
						,	"core_images"          => "null"
						,	"core_urls"            => "null"
						,	"core_version"         => "version"
						,	"core_ordering"        => "null"
						,	"core_metakey"         => "metakey"
						,	"core_metadesc"        => "metadesc"
						,	"core_catid"           => "parent_id"
						,	"core_xreference"      => "null"
						,	"asset_id"             => "asset_id"
						)
					)
				,	"special" => array(
						array(
							"parent_id" => "parent_id"
						,	"lft"       => "lft"
						,	"rgt"       => "rgt"
						,	"level"     => "level"
						,	"path"      => "path"
						,	"extension" => "extension"
						,	"note"      => "note"
						)
					)
				))
			,	'router' => 'JCalProHelperRoute::getCategoryRoute'
			)
		,	array(
				'type_title' => 'JCALPRO_EVENT'
			,	'type_alias' => 'com_jcalpro.event'
			,	'table'          => json_encode(array(
					"special" => array(
						"dbtable" => "#__jcalpro_events"
					,	"key"     => "id"
					,	"type"    => "Event"
					,	"prefix"  => "JCalProTable"
					,	"config"  => "array()"
					)
				,	"common" => array(
						"dbtable" => "#__ucm_content"
					,	"key"     => "ucm_id"
					,	"type"    => "Corecontent"
					,	"prefix"  => "JTable"
					,	"config"  => "array()"
					)
				))
			,	'field_mappings' => json_encode(array(
					"common" => array(
						array(
							"core_content_item_id" => "id"
						,	"core_title"           => "title"
						,	"core_state"           => "published"
						,	"core_alias"           => "alias"
						,	"core_created_time"    => "created"
						,	"core_modified_time"   => "modified"
						,	"core_body"            => "description"
						,	"core_hits"            => "null"
						,	"core_publish_up"      => "null"
						,	"core_publish_down"    => "null"
						,	"core_access"          => "null"
						,	"core_params"          => "params"
						,	"core_featured"        => "null"
						,	"core_metadata"        => "null"
						,	"core_language"        => "language"
						,	"core_images"          => "null"
						,	"core_urls"            => "null"
						,	"core_version"         => "null"
						,	"core_ordering"        => "null"
						,	"core_metakey"         => "null"
						,	"core_metadesc"        => "null"
						,	"core_catid"           => "canonical"
						,	"core_xreference"      => "null"
						,	"asset_id"             => "asset_id"
						)
					)
				))
			,	'router' => 'JCalProHelperRoute::getEventRoute'
			)
		);
		
		foreach ($data as $d) {
			$this->db->setQuery($this->db->getQuery(true)
				->select('id')
				->from('#__content_types')
				->where($this->db->quoteName('type_alias') . ' = ' . $this->db->quote($d['type_alias']))
			);
			
			try {
				$id = $this->db->loadResult();
			}
			catch (Exception $e) {
				$id = 0;
			}
			
			$table = JTable::getInstance('Contenttype');
			if ($id) {
				$table->load($id);
				$d['id'] = $id;
			}
			$status  = true;
			$message = false;
			
			try {
				$status = $status && $table->bind($d);
			}
			catch (Exception $e) {
				$status  = false;
				$message = $e->getMessage();
			}
			
			if ($status) try {
				$status = $status && $table->check();
			}
			catch (Exception $e) {
				$status = false;
				$message = $e->getMessage();
			}
			
			if ($status) try {
				$status = $status && $table->store();
			}
			catch (Exception $e) {
				$status = false;
				$message = $e->getMessage();
			}
			
			if ($status && $message) {
				JFactory::getApplication()->enqueueMessage($message, 'error');
			}
		}
	}
	
	private function _setACLs() {
		// get the rules for both the site AND the component, because we only want to reset
		// the moderate rules for the component IF they are empty
		$this->db->setQuery((string) $this->db->getQuery(true)
			->select('a.rules AS root_rules')
			->select('b.rules AS com_rules')
			->from('#__assets AS a')
			->leftJoin('#__assets AS b ON b.parent_id = a.id')
			->where('b.name = ' . $this->db->Quote('com_jcalpro'))
		);
		$rules = $this->db->loadObject();
		// parse the site rules
		$registry = new JRegistry();
		$registry->loadString($rules->root_rules);
		$root_rules = $registry->toArray();
		// parse the component rules
		$registry = new JRegistry();
		$registry->loadString($rules->com_rules);
		$com_rules = $registry->toArray();
		// check the component rules for moderation
		if (!array_key_exists("core.moderate", $com_rules) || empty($com_rules["core.moderate"])) {
			$com_rules["core.moderate"] = $root_rules["core.edit.state"];
		}
		// check the component rules for create private
		if (!array_key_exists("core.create.private", $com_rules) || empty($com_rules["core.create.private"])) {
			$com_rules["core.create.private"] = $root_rules["core.create"];
		}
		// check the component rules for field.create and field.edit
		foreach (array('create', 'edit') as $rule) {
			if (!array_key_exists("field.$rule", $com_rules)) {
				$com_rules["field.$rule"] = 1;
			}
		}
		// update the rules for the component
		$this->db->setQuery((string) $this->db->getQuery(true)
			->update('#__assets')
			->set($this->db->quoteName('rules') . ' = ' . $this->db->Quote(json_encode($com_rules)))
			->where($this->db->quoteName('name') . ' = ' . $this->db->Quote('com_jcalpro'))
		);
		$this->db->query();
		// ugh, fix context bug in events assets
		$this->_fixAssetNames();
		// fix the assets table for orphaned entries
		$this->_fixAssets();
		// fix the assets table for events
		$this->_fixEventAssets();
		// fix the assets table for locations
		$this->_fixLocationAssets();
		// fix the assets table for registrations
		$this->_fixRegistrationAssets();
		// fix the assets table for emails
		$this->_fixEmailAssets();
		// fix the assets table for forms
		$this->_fixFormAssets();
		// fix the assets table for fields
		$this->_fixFieldAssets();
	}
	
	/**
	 * Fix assets for locations
	 * 
	 * @return void
	 * 
	 */
	private function _fixLocationAssets() {
		$this->_fixGenericAsset('#__jcalpro_locations', 'Location');
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_jcalpro.locations');
		if (empty($asset->id))
		{
			JCalPro::registerHelper('access');
			JCalProHelperAccess::saveRules('locations', array('core.dummy' => array()), false);
			$asset->loadByName('com_jcalpro.locations');
		}
		// now fix any field assets that already exist
		$ids = $this->db->setQuery($this->db->getQuery(true)
			->select('id')
			->from('#__assets')
			->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_jcalpro.location.%'))
		)->loadColumn();
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				$field = JTable::getInstance('Asset');
				$field->load($id);
				$field->moveByReference($asset->id, 'last-child');
			}
		}
	}
	
	/**
	 * Fix assets for registrations
	 * 
	 * @return void
	 * 
	 */
	private function _fixRegistrationAssets() {
		$this->_fixGenericAsset('#__jcalpro_registration', 'Registration');
		
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_jcalpro');
		// now fix any assets that already exist
		$ids = $this->db->setQuery($this->db->getQuery(true)
			->select('id')
			->from('#__assets')
			->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_jcalpro.registration.%'))
		)->loadColumn();
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				$field = JTable::getInstance('Asset');
				$field->load($id);
				$field->moveByReference($asset->id, 'last-child');
			}
		}
	}
	
	/**
	 * Fix assets for emails
	 * 
	 * @return void
	 * 
	 */
	private function _fixEmailAssets() {
		$this->_fixGenericAsset('#__jcalpro_emails', 'Email');
		try {
			$this->db->setQuery('UPDATE #__assets SET name = REPLACE(name, ' . $this->db->quote('#__jcalpro_emails.') . ', ' . $this->db->quote('com_jcalpro.emails.') . ') WHERE name LIKE ' . $this->db->quote('#__jcalpro_emails.%', false) . ' AND id IN ((SELECT asset_id FROM #__jcalpro_emails))');
			$this->db->query();
			$this->db->setQuery('DELETE FROM #__assets WHERE name LIKE ' . $this->db->quote('#__jcalpro_emails.%', false) . ' AND id NOT IN ((SELECT asset_id FROM #__jcalpro_emails))');
			$this->db->query();
		}
		catch (Exception $e) {
			// nothing
		}
		
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_jcalpro');
		// now fix any assets that already exist
		$ids = $this->db->setQuery($this->db->getQuery(true)
			->select('id')
			->from('#__assets')
			->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_jcalpro.emails.%'))
		)->loadColumn();
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				$field = JTable::getInstance('Asset');
				$field->load($id);
				$field->moveByReference($asset->id, 'last-child');
			}
		}
	}
	
	/**
	 * Fix assets for forms
	 * 
	 * @return void
	 * 
	 */
	private function _fixFormAssets() {
		$this->_fixGenericAsset('#__jcalpro_forms', 'Form');
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_jcalpro.forms');
		if (empty($asset->id))
		{
			JCalPro::registerHelper('access');
			JCalProHelperAccess::saveRules('forms', array('core.dummy' => array()), false);
			$asset->loadByName('com_jcalpro.forms');
		}
		// now fix any field assets that already exist
		$ids = $this->db->setQuery($this->db->getQuery(true)
			->select('id')
			->from('#__assets')
			->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_jcalpro.form.%'))
		)->loadColumn();
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				$field = JTable::getInstance('Asset');
				$field->load($id);
				$field->moveByReference($asset->id, 'last-child');
			}
		}
	}
	
	/**
	 * Fix assets for fields
	 * 
	 * @return void
	 * 
	 */
	private function _fixFieldAssets() {
		// fields already have assets, so we need to create a generic one first
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_jcalpro.fields');
		if (empty($asset->id))
		{
			JCalPro::registerHelper('access');
			JCalProHelperAccess::saveRules('fields', array('core.dummy' => array()), false);
			$asset->loadByName('com_jcalpro.fields');
		}
		// now fix any field assets that already exist
		$ids = $this->db->setQuery($this->db->getQuery(true)
			->select('id')
			->from('#__assets')
			->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('com_jcalpro.field.%'))
		)->loadColumn();
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				$field = JTable::getInstance('Asset');
				$field->load($id);
				$field->moveByReference($asset->id, 'last-child');
			}
		}
	}
	
	/**
	 * Fix generic assets
	 * 
	 * @param type $table
	 * @param type $type
	 * @return type
	 */
	private function _fixGenericAsset($table, $type) {
		try {
			$records = $this->db->setQuery($this->db->getQuery(true)
				->select('t.id')
				->from($table . ' AS t')
				->leftJoin('#__assets AS a ON a.id = t.asset_id')
				->where('t.asset_id = 0 OR a.id IS NULL OR (a.parent_id = 0 AND a.id <> 1)')
			)->loadColumn();
		}
		catch (Exception $ex) {
			return;
		}
		
		if (empty($records)) {
			return;
		}
		
		try {
			$this->db->setQuery($this->db->getQuery(true)
				->update($table)
				->set('asset_id = 0')
				->where('id IN(' . implode(',', $records) . ')')
			)->query();
		}
		catch (Exception $ex) {
			// do nothing
		}
		
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jcalpro/tables/');
		
		foreach ($records as $record) {
			try {
				$row = JTable::getInstance($type, 'JCalProTable');
				if (!is_object($row)) {
					continue;
				}
				if ($row->load($record)) {
					$row->store();
				}
			}
			catch (Exception $ex) {
				return;
			}
		}
	}
	
	private function _fixAssetNames() {
		$this->db->setQuery('UPDATE #__assets SET name = REPLACE(name, ' . $this->db->quote('.') . ', ' . $this->db->quote('com_jcalpro.event.') . ') WHERE name LIKE ' . $this->db->quote('.%', false) . ' AND id IN ((SELECT asset_id FROM #__jcalpro_events))');
		try {
			$this->db->query();
		}
		catch (Exception $e) {
			return;
		}
	}
	
	private function _fixAssets() {
		$this->db->setQuery($this->db->getQuery(true)
			->select('a.id')
			->from('#__assets AS a')
			->leftJoin('#__jcalpro_events AS e ON CONCAT(' . $this->db->quote('com_jcalpro.event.') . ',e.id) = a.name')
			->where('a.name LIKE ' . $this->db->quote('com_jcalpro.event.%', false))
			->where('e.id IS NULL')
			->group('a.id')
		);
		
		try {
			$assets = $this->db->loadColumn();
		}
		catch (Exception $e) {
			return;
		}
		
		if (!empty($assets)) {
			JArrayHelper::toInteger($assets);
			$this->db->setQuery($this->db->getQuery(true)
				->delete('#__assets')
				->where('id IN (' . implode(',', $assets) . ')')
			);
			try {
				$this->db->query();
			}
			catch (Exception $e) {
				return;
			}
		}
		
		// try to fix old entries with incorrect parents
		
		// get the broken assets
		$this->db->setQuery($this->db->getQuery(true)
			->select('*')
			->from('#__assets')
			->where('name LIKE ' . $this->db->quote('com_jcalpro.event.%', false))
			->where('level = 2')
		);
		try {
			$assets = $this->db->loadObjectList();
		}
		catch (Exception $e) {
			return;
		}
		
		// are there no broken assets? we're done here
		if (empty($assets)) return;
		
		// pull our event ids out
		$ids = array();
		foreach ($assets as $asset) {
			$ids[] = (int) str_replace('com_jcalpro.event.', '', $asset->name);
		}
		$ids = array_unique($ids);
		
		// now we need to know which category assets to assign to
		// get the category info from our xref table
		$this->db->setQuery($this->db->getQuery(true)
			->select('*')
			->from('#__jcalpro_event_categories')
			->where('canonical = 1')
			->where('event_id IN (' . implode(',', $ids) . ')')
		);
		
		try {
			$xrefs = $this->db->loadObjectList();
		}
		catch (Exception $e) {
			return;
		}
		
		// go through the assets and assign them
		foreach ($assets as $asset) {
			// find this asset's parent
			$id = (int) str_replace('com_jcalpro.event.', '', $asset->name);
			$catid = false;
			foreach ($xrefs as $xref) {
				if ($id == $xref->event_id) {
					$catid = (int) $xref->category_id;
					break;
				}
			}
			if (!$catid) continue;
			// load this category asset and update
			$this->db->setQuery($this->db->getQuery(true)
				->select('id')
				->from('#__assets')
				->where('name = ' . $this->db->quote('com_jcalpro.category.' . $catid))
			);
			
			try {
				$catasset = $this->db->loadResult();
			}
			catch (Exception $e) {
				continue;
			}
			
			// get a table for this asset and move it
			$table = JTable::getInstance('Asset');
			if ($table->load($asset->id)) {
				$table->moveByReference($catasset, 'last-child');
			}
			
		}
		
	}
	
	private function _fixEventAssets() {
		$this->db->setQuery($this->db->getQuery(true)
			->select('a.id AS asset_id')
			->select('p.id AS parent_id')
			->from('#__assets AS a')
			->leftJoin('#__jcalpro_events AS e ON CONCAT(' . $this->db->quote('com_jcalpro.event.') . ',e.id) = a.name')
			->leftJoin('#__jcalpro_event_categories AS c ON c.event_id = e.id AND c.canonical = 1')
			->leftJoin('#__assets AS p ON CONCAT(' . $this->db->quote('com_jcalpro.category.') . ',c.category_id) = p.name')
			->where('a.name LIKE ' . $this->db->quote('com_jcalpro.event.%', false))
			->where('a.level = 2')
			->group('a.id')
		);
		try {
			$broken = $this->db->loadObjectList();
		}
		catch (Exception $ex) {
			return;
		}
		if (empty($broken)) {
			return;
		}
		foreach ($broken as $record) {
			$table = JTable::getInstance('Asset');
			if ($table->load($record->asset_id)) {
				$table->moveByReference($record->parent_id, 'last-child');
			}
		}
	}
	
	private function _fixFrontendMenus() {
		// get the current id of this component
		$this->db->setQuery((string) $this->db->getQuery(true)
			->select('extension_id')
			->from('#__extensions')
			->where($this->db->quoteName('name') . '=' . $this->db->Quote('com_jcalpro'))
			->where($this->db->quoteName('type') . '=' . $this->db->Quote('component'))
		);
		$id = $this->db->loadResult();
		if ($id) {
			$this->db->setQuery((string) $this->db->getQuery(true)
				->update('#__menu')
				->set($this->db->quoteName('component_id') . '=' . intval($id))
				->where($this->db->quoteName('client_id') . '=0')
				->where($this->db->quoteName('link') . 'LIKE "index.php?option=com_jcalpro%"')
			);
			$this->db->query();
		}
	}
	
	/**
	 * Joomla! 1.6+ bugfix for "DB function returned no error"
	 */
	private function _bugfixDBFunctionReturnedNoError()
	{
		// Fix broken #__assets records
		$query = $this->db->getQuery(true);
		$query->select('id')
			->from('#__assets')
			->where($this->db->quoteName('name').' = '.$this->db->Quote($this->_jc_extension));
		$this->db->setQuery($query);
		$ids = $this->db->loadColumn();
		if(!empty($ids)) foreach($ids as $id) {
			$query = $this->db->getQuery(true);
			$query->delete('#__assets')
				->where($this->db->quoteName('id').' = '.$this->db->Quote($id));
			$this->db->setQuery($query);
			$this->db->query();
		}

		// Fix broken #__extensions records
		$query = $this->db->getQuery(true);
		$query->select('extension_id')
			->from('#__extensions')
			->where($this->db->quoteName('element').' = '.$this->db->Quote($this->_jc_extension));
		$this->db->setQuery($query);
		$ids = $this->db->loadColumn();
		if(!empty($ids)) foreach($ids as $id) {
			$query = $this->db->getQuery(true);
			$query->delete('#__extensions')
				->where($this->db->quoteName('extension_id').' = '.$this->db->Quote($id));
			$this->db->setQuery($query);
			$this->db->query();
		}

		// Fix broken #__menu records
		$query = $this->db->getQuery(true);
		$query->select('id')
			->from('#__menu')
			->where($this->db->quoteName('type').' = '.$this->db->Quote('component'))
			->where($this->db->quoteName('menutype').' = '.$this->db->Quote('main'))
			->where($this->db->quoteName('link').' LIKE '.$this->db->Quote('index.php?option='.$this->_jc_extension.'%'));
		$this->db->setQuery($query);
		$ids = $this->db->loadColumn();
		if(!empty($ids)) foreach($ids as $id) {
			$query = $this->db->getQuery(true);
			$query->delete('#__menu')
				->where($this->db->quoteName('id').' = '.$this->db->Quote($id));
			$this->db->setQuery($query);
			$this->db->query();
		}
	}
	
	/**
	 * Joomla! 1.6+ bugfix for "Can not build admin menus"
	 */
	private function _bugfixCantBuildAdminMenus()
	{
		// If there are multiple #__extensions record, keep one of them
		$query = $this->db->getQuery(true);
		$query->select('extension_id')
			->from('#__extensions')
			->where($this->db->quoteName('element').' = '.$this->db->Quote($this->_jc_extension));
		$this->db->setQuery($query);
		$ids = $this->db->loadColumn();
		if(count($ids) > 1) {
			asort($ids);
			$extension_id = array_shift($ids); // Keep the oldest id
			
			foreach($ids as $id) {
				$query = $this->db->getQuery(true);
				$query->delete('#__extensions')
					->where($this->db->quoteName('extension_id').' = '.$this->db->Quote($id));
				$this->db->setQuery($query);
				$this->db->query();
			}
		}
		
		// If there are multiple assets records, delete all except the oldest one
		$query = $this->db->getQuery(true);
		$query->select('id')
			->from('#__assets')
			->where($this->db->quoteName('name').' = '.$this->db->Quote($this->_jc_extension));
		$this->db->setQuery($query);
		$ids = $this->db->loadObjectList();
		if(count($ids) > 1) {
			asort($ids);
			$asset_id = array_shift($ids); // Keep the oldest id
			
			foreach($ids as $id) {
				$query = $this->db->getQuery(true);
				$query->delete('#__assets')
					->where($this->db->quoteName('id').' = '.$this->db->Quote($id));
				$this->db->setQuery($query);
				$this->db->query();
			}
		}

		// Remove #__menu records for good measure!
		$query = $this->db->getQuery(true);
		$query->select('id')
			->from('#__menu')
			->where($this->db->quoteName('type').' = '.$this->db->Quote('component'))
			->where($this->db->quoteName('menutype').' = '.$this->db->Quote('main'))
			->where($this->db->quoteName('link').' LIKE '.$this->db->Quote('index.php?option='.$this->_jc_extension.'%'));
		$this->db->setQuery($query);
		$ids = $this->db->loadColumn();
		if(!empty($ids)) foreach($ids as $id) {
			$query = $this->db->getQuery(true);
			$query->delete('#__menu')
				->where($this->db->quoteName('id').' = '.$this->db->Quote($id));
			$this->db->setQuery($query);
			$this->db->query();
		}
	}
	
	private function _forceUpdateUrls()
	{
		// no matter what, we should tell the plugin it needs to fix the update url
		JFactory::getSession()->set('jcalpro.update.updateservers', 1);
		try
		{
			$pkg = $this->db->setQuery($this->db->getQuery(true)
				->select('extension_id AS id')
				->from('#__extensions')
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('pkg_jcalpro'))
			)->loadResult();
			$params = $this->db->setQuery($this->db->getQuery(true)
				->select('params')
				->from('#__extensions')
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_jcalpro'))
			)->loadResult();
			if (empty($pkg))
			{
				return;
			}
			// purge any "existing" updates
			$this->db->setQuery($this->db->getQuery(true)
				->delete('#__updates')
				->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($pkg))
			)->query();
			// might be more than one!
			$siteids = $this->db->setQuery($this->db->getQuery(true)
				->select('update_site_id')
				->from('#__update_sites_extensions')
				->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($pkg))
			)->loadColumn();
			// purge records
			if (!empty($siteids))
			{
				$this->db->setQuery($this->db->getQuery(true)
					->delete('#__update_sites_extensions')
					->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($pkg))
				)->query();
				$this->db->setQuery($this->db->getQuery(true)
					->delete('#__update_sites')
					->where($this->db->quoteName('update_site_id') . ' IN (' . implode(',', $siteids) . ')')
				)->query();
			}
			// if the component has no params, there's nothing else to do
			if (empty($params))
			{
				return;
			}
			// find the update server in params
			$params = json_decode($params);
			if (!(is_object($params) && property_exists($params, 'update_server')))
			{
				return;
			}
			$this->db->setQuery($this->db->getQuery(true)
				->insert('#__update_sites')
				->columns(array('name', 'type', 'location', 'enabled', 'last_check_timestamp'))
				->values($this->db->quote('jcalpro') . ',' . $this->db->quote('collection') . ',' . $this->db->quote($params->update_server) . ', 1, 0')
			)->query();
			$newid = $this->db->insertid();
			$this->db->setQuery($this->db->getQuery(true)
				->insert('#__update_sites_extensions')
				->columns(array('update_site_id', 'extension_id'))
				->values($this->db->quote($newid) . ',' . $this->db->quote($pkg))
			)->query();
		}
		catch (Exception $e)
		{
			$this->app->enqueueMessage($e->getMessage(), 'error');
			return;
		}
	}
	
}