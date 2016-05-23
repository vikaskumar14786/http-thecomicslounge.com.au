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

JLoader::register('JCalProHelperPath', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/path.php');
JLoader::register('JCalPro', JCalProHelperPath::helper() . '/jcalpro.php');

jimport(JCalPro::version()->isCompatible('3.0') ? 'legacy.model.admin' : 'joomla.application.component.modeladmin');
jimport('joomla.form.form');
jimport('joomla.form.helper');

JForm::addFormPath(JCalProHelperPath::admin() . '/models/forms');
JForm::addFieldPath(JCalProHelperPath::admin() . '/models/fields');
// Augh, J1.7 JFormHelper::loadClass is busted ;(
// https://github.com/joomla/joomla-platform/commit/b61a91d88df390687e57fd9b6d2dbdc6a5e3cf1d#libraries/joomla/form/helper.php
// TODO: since we're not supporting 1.7 anymore, do we really need this? it doesn't hurt to leave it...
JForm::addFieldPath(JCalProHelperPath::admin() . '/models/fields/modal');
JForm::addFieldPath(JCalProHelperPath::library() . '/fields');

// load the event-specific language file
JCalPro::language('com_jcalpro.event', JPATH_ADMINISTRATOR);

class JCalProAdminModel extends JModelAdmin
{
	protected $context  = 'com_jcalpro';
	
	public $option = 'com_jcalpro';
	
	public function getForm($data = array(), $loadData = true) {
		// Get the form.
		$form = $this->loadForm($this->option.'.'.$this->name, $this->name, array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			return false;
		}
		return $form;
	}

	public function getTable($type=null, $prefix = 'JCalProTable', $config = array()) {
		if (empty($type))
		{
			$type = $this->name;
		}
		return parent::getTable($type, $prefix, $config);
	}

	public function getItem($id = null) {
		return parent::getItem($id);
	}

	protected function loadFormData() {
		$data = JFactory::getApplication()->getUserState($this->option.'.edit.'.strtolower($this->name).'.data', array());
		if (empty($data)) {
			$data = $this->getItem();
		}
		return $data;
	}
	
	function cleanCache($group = null, $client_id = 0) {
		parent::cleanCache($this->option);
		parent::cleanCache('_system');
		parent::cleanCache($group, $client_id);
	}
	
	/**
	 * give public read access to the model's context
	 * 
	 */
	public function getContext() {
		return (string) $this->context;
	}
	
	/**
	 * override to handle deletion of xref entries
	 * 
	 * @param unknown_type $pks
	 */
	public function delete(&$pks) {
		$delete = parent::delete($pks);
		$app    = JFactory::getApplication();
		$app->input->set('filter_published', '');
		$app->setUserState($this->context . '.filter.published', '');
		return $delete;
	}
}
