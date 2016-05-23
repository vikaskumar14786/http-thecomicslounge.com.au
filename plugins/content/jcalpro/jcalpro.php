<?php
/**
 * @package		JCalPro
 * @subpackage	plg_content_jcalpro

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

jimport('joomla.plugin.plugin');
// we HAVE to force-load the helper here to prevent fatal errors!
$helper = JPATH_ADMINISTRATOR . '/components/com_jcalpro/helpers/jcalpro.php';
if (JFile::exists($helper))
{
	require_once $helper;
	JCalPro::registerHelper('log');
	JCalPro::registerHelper('mail');
	JCalPro::registerHelper('module');
	JCalPro::registerHelper('path');
	JCalPro::registerHelper('url');
}

class plgContentJCalPro extends JPlugin
{
	private static $_run;
	
	private static $_event_approved = false;
	
	/**
	 * Constructor
	 * 
	 * @param unknown_type $subject
	 * @param unknown_type $config
	 */
	public function __construct(&$subject, $config = null) {
		// if something happens & the helper class can't be found, we don't want a fatal error here
		if (class_exists('JCalPro')) {
			JCalPro::language(JCalPro::COM . '.event', JPATH_ADMINISTRATOR);
			self::$_run = true;
		}
		else {
			self::$_run = false;
		}
		parent::__construct($subject, $config);
	}
	
	/**
	 * Look for content tags
	 * 
	 * @param unknown_type $context
	 * @param unknown_type $row
	 * @param unknown_type $params
	 * @param unknown_type $page
	 */
	public function onContentPrepare($context, &$row, &$params, $page = 0) {
		if (!self::$_run) {
			return;
		}
		// TODO: confine to specific contexts?
		// don't bother if row isn't an object
		if (!is_object($row)) {
			return;
		}
		// parameters to check
		$check = array('description', 'title', 'name', 'alias', 'text');
		// the tag names we're planning on checking for
		// we have multiples because of legacy code
		// it used "jcal_latest" and that's not really representative
		// because what we want is really "jcal_list"
		// however, from now on we're just going to also look for "jcalpro"
		// then we can use THAT as an entryway to pull ANYTHING - events, registrations, etc :)
		$tags = array('jcalpro', 'jcal_latest');
		// default parameters for the module
		$defaults = array(
			'filter_date_range' => ''
			// TODO
		);
		// map legacy parameters to new ones
		// key: new parameter
		// value: old parameter
		// TODO
		$map = array(
			'range'        => 'filter_date_range'
		,	'cat'          => 'filter_category'
		,	'max_upcoming' => 'list_limit'
		,	'max_recent'   => 'list_limit'
		,	'show_date'    => 'display_date'
		,	'show_time'    => 'display_time'
			/*
				cal [comma-seperated list of calendar IDs listed in the calendar manager]
				cat | [comma-seperated list of category IDs listed in the category manager]
				eventid | [comma-seperated list of event IDs listed in the event manager]
				show_description [yes | no]
				show_calendar [yes | no]
				show_category [yes | no]
				readmore [yes | no]
				max_upcoming [number of upcoming events to list]
				max_recent [number of recent events to list]
				sort [date | category]
				direction [asc | desc]
				*/
		);
		
		foreach ($check as $property) {
			if (!property_exists($row, $property)) {
				continue;
			}
			// check this property to see if it matches ANY of our tags
			$hasTags = false;
			foreach ($tags as $tag) {
				$hasTags = $hasTags || (false !== JString::strpos(JString::strtolower($row->{$property}), '{' . $tag));
			}
			if (!$hasTags) {
				continue;
			}
			// we have tags, now comes the fun part - building the regex :)
			$tag = implode('|', $tags);
			$matches = array();
			$search  = '/
# start the regex
	# start a named block for the entire tag so we can replace the whole thing later
	(?P<tag>
		# opening bracket of our tag
		\{
			# named block for the tag name, used for backreference later
			(?P<name>' . $tag . ')
			# allow for a single space
			\s?
			# named block for parameters - everything before the closing bracket
			(?P<params>[^\}]*?)?
		# closing bracket
		\}
		# start ignored block for legacy syntax
		(?:
			# start named block for legacy categories
			(?P<legacy>
				# category ids in legacy block
				[1-9][0-9,]*?
			# end named block for legacy categories
			)
			# end legacy tag
			\{\/(?P=name)\}
		# end of ignored legacy block
		)?
	# end of the tag block
	)
# do not forget the x flag
/mix';
			// find all our matches
			preg_match_all($search, $row->{$property}, $matches);
			// we don't need the numeric keys as the data we want has named keys
			foreach ($matches as $key => $match) {
				if (is_numeric($key)) {
					unset($matches[$key]);
				}
			}
			// loop our found tags
			foreach ($matches['tag'] as $key => $match) {
				// legacy format uses spaces - yuk
				// recommend in NEW docs to use & instead
				$pstring = str_replace(array(' ', '&amp;'), '&', trim("{$matches['params'][$key]}"));
				$parameters = array();
				// parse the params & clean them up
				if (!empty($pstring)) {
					parse_str($pstring, $parameters);
					if (!empty($parameters)) {
						foreach ($parameters as $pname => &$pvalue) {
							// make an array for categories and values delimited by , 
							switch ($pname) {
								case 'cat':
								case 'eventid':
									$pvalue = empty($pvalue) ? array() : explode(',', $pvalue);
									continue;
							}
							// fix this boolean stupidity
							switch (strtolower((string) $pvalue)) {
								case 'yes':
								case 'true':
									$pvalue = true;
									break;
								case 'false':
								case 'no':
									$pvalue = false;
									break;
							}
						}
					}
				}
				// fix for legacy
				$lstring = "{$matches['legacy'][$key]}";
				if (!empty($lstring)) {
					// set parameters, if not set
					$lcats = explode(',', $lstring);
					if (!array_key_exists('cat', $parameters)) {
						$parameters['cat'] = $lcats;
					}
					else {
						$parameters['cat'] = array_unique(array_merge($parameters['cat'], $lcats));
					}
				}
				$matches['params'][$key] = $parameters;
				// TODO: fix these
				$modparams = array_merge($parameters, array(
					'filter_category'     => array_key_exists('cat', $parameters) ? $parameters['cat'] : (array_key_exists('filter_category', $parameters) ? $parameters['filter_category'] : array())
				,	'display_category'    => array_key_exists('show_category', $parameters) ? $parameters['show_category'] : (array_key_exists('display_category', $parameters) ? $parameters['display_category'] : true)
				,	'display_description' => array_key_exists('show_description', $parameters) ? $parameters['show_description'] : (array_key_exists('display_description', $parameters) ? $parameters['display_description'] : true)
				,	'display_date'        => array_key_exists('show_date', $parameters) ? $parameters['show_date'] : (array_key_exists('display_date', $parameters) ? (int) $parameters['display_date'] : true)
				,	'display_time'        => array_key_exists('show_time', $parameters) ? $parameters['show_time'] : (array_key_exists('display_time', $parameters) ? (int) $parameters['display_time'] : true)
				,	'list_limit'          => array_key_exists('max_upcoming', $parameters) ? (int) $parameters['max_upcoming'] : (array_key_exists('list_limit', $parameters) ? (int) $parameters['list_limit'] : JCalPro::config('list_limit', 4))
				,	'display_readmore'    => array_key_exists('readmore', $parameters) ? $parameters['readmore'] : (array_key_exists('display_readmore', $parameters) ? $parameters['display_readmore'] : true)
				// disable debug mode
				,	'debug'               => 0
				// we don't want to display these links yet - do it at the bottom :)
				,	'display_add'         => 0
				,	'display_events_link' => 0
				));
				// fix the search for eventids
				if (array_key_exists('eventid', $modparams) && !empty($modparams['eventid'])) {
					$modparams['filter_search'] = 'ids:' . implode(',', $modparams['eventid']);
					$modparams['filter_date_range'] = JCalPro::RANGE_ALL;
					$module = JCalProHelperModule::render('mod_jcalpro_events', 0, $modparams);
				}
				else if (array_key_exists('filter_date_range', $modparams)) {
					// fix non-numeric values
					if (!is_numeric($modparams['filter_date_range'])) {
						$constant = 'JCalPro::RANGE_' . strtoupper($modparams['filter_date_range']);
						if (defined($constant)) {
							$modparams['filter_date_range'] = constant($constant);
						}
					}
					// render
					$module = JCalProHelperModule::render('mod_jcalpro_events', 0, $modparams);
				}
				else {
					$modparams['filter_date_range'] = JCalPro::RANGE_UPCOMING;
					$upcoming = $recent = '';
					if ($modparams['list_limit']) {
						// render the upcoming events
						$upcoming  = '<h4>' . JText::_('COM_JCALPRO_UPCOMING_EVENTS') . '</h4>';
						$upcoming .= JCalProHelperModule::render('mod_jcalpro_events', 0, $modparams);
					}
					// render the recent events
					$modparams['list_limit'] = array_key_exists('max_recent', $parameters) ? (int) $parameters['max_recent'] : (array_key_exists('list_limit', $parameters) ? (int) $parameters['list_limit'] : JCalPro::config('list_limit', 4));
					$modparams['filter_date_range'] = JCalPro::RANGE_PAST;
					if ($modparams['list_limit']) {
						$recent  = '<h4>' . JText::_('COM_JCALPRO_RECENT_EVENTS') . '</h4>';
						$recent .= JCalProHelperModule::render('mod_jcalpro_events', 0, $modparams);
					}
					
					$module = $upcoming . $recent;
				}
				
				// replace the tag with our modules
				$text = $row->{$property};
				$tag = $matches['tag'][$key];
				$tagstart = JString::strpos($row->{$property}, $tag);
				$taglength = JString::strlen($tag);
				$text = JString::substr($text, 0, $tagstart) . $module . JString::substr($text, ($tagstart + $taglength));
				$row->{$property} = $text;
			}
		}
	}
	
	/**
	 * onContentPrepareForm
	 * 
	 * @param JForm $form
	 */
	public function onContentPrepareForm($form) {
		if (!self::$_run) {
			return;
		}
		JCalProHelperLog::debug(__METHOD__);
		if (!($form instanceof JForm)) {
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}
		if ('com_categories.categorycom_jcalpro' != $form->getName()) {
			return true;
		}
		JCalPro::language(JCalPro::COM, JPATH_ADMINISTRATOR);
		JForm::addFieldPath(JCalProHelperPath::admin("models/fields"));
		JForm::addFormPath(JCalProHelperPath::admin("models/forms"));
		$result = $form->loadFile('jcalprocategory', false);
		$form->setFieldAttribute('jcalpro_color', 'default', JCalPro::config('category_default_color', '#545454'), 'params');
		return $result;
	}
	
	/**
	 * Don't allow categories to be deleted if they contain events or subcategories with events
	 * 
	 * @param       string  The context for the content passed to the plugin.
	 * @param       object  The data relating to the content that was deleted.
	 * @return      boolean
	 */
	public function onContentBeforeDelete($context, $data) {
		if (!self::$_run) {
			return;
		}
		JCalProHelperLog::debug(__METHOD__ . '(' . $context . ')');
		// Skip plugin if we are deleting something other than categories
		if ('com_categories.category' != $context) {
			return true;
		}
		// ensure we're only handling our own
		if (JFactory::getApplication()->input->get('extension', '', 'string') != JCalPro::COM) {
			return true;
		}
		// Default to true
		$result = true;
		// See if this category has any events
		$count = $this->_countEventsInCategory($data->get('id'));
		// Return false if db error
		if (false === $count) {
			$result = false;
		}
		else {
			// Show error if items are found in the category
			if (0 < $count) {
				$msg = JText::sprintf('COM_CATEGORIES_DELETE_NOT_ALLOWED', $data->get('title')) .
				JText::plural('COM_CATEGORIES_N_ITEMS_ASSIGNED', $count);
				JError::raiseWarning(403, $msg);
				$result = false;
			}
			// Check for items in any child categories (if it is a leaf, there are no child categories)
			if (!$data->isLeaf()) {
				$count = $this->_countEventsInChildren($data);
				if (false === $count) {
					$result = false;
				}
				else if (0 < $count) {
					$msg = JText::sprintf('COM_CATEGORIES_DELETE_NOT_ALLOWED', $data->get('title')) .
					JText::plural('COM_CATEGORIES_HAS_SUBCATEGORY_ITEMS', $count);
					JError::raiseWarning(403, $msg);
					$result = false;
				}
			}
		}
		// check our result - if it's true, purge this category from events where it's not canonical
		if ($result) {
			$db = JFactory::getDbo();
			$db->setQuery((string) $db->getQuery(true)
				->delete('#__jcalpro_event_categories')
				->where('category_id = ' . $data->get('id'))
				->where('canonical = 0')
			);
			$db->query();
			if ($error = $db->getErrorMsg()) {
				JError::raiseWarning(500, $error);
				return false;
			}
			return true;
		}
		// if we make it this far, we failed :P
		return false;
	}
	
	/**
	 * Get count of items in a category
	 * 
	 * @param       int     id of the category to check
	 * @return      mixed   count of items found or false if db error
	 */
	private function _countEventsInCategory($catid) {
		$db = JFactory::getDbo();
		$db->setQuery((string) $db->getQuery(true)
			->select('COUNT(event_id)')
			->from('#__jcalpro_event_categories')
			->where('category_id = ' . (int) $catid)
			->where('canonical = 1')
		);
		$count = $db->loadResult();
		// Check for DB error.
		if ($error = $db->getErrorMsg()) {
			JError::raiseWarning(500, $error);
			return false;
		}
		return $count;
	}
	
	/**
	 * Get count of items in a category's child categories
	 * 
	 * @param       object
	 * @return      mixed   count of items found or false if db error
	 */
	private function _countEventsInChildren($data) {
		$db = JFactory::getDbo();
		// Create subquery for list of child categories
		$childCategoryTree = $data->getTree();
		// First element in tree is the current category, so we can skip that one
		unset($childCategoryTree[0]);
		$childCategoryIds = array();
		foreach ($childCategoryTree as $node) {
			$childCategoryIds[] = (int) $node->id;
		}
		// Make sure we only do the query if we have some categories to look in
		if (count($childCategoryIds)) {
			// Count the items in this category
			$db->setQuery((string) $db->getQuery(true)
				->select('COUNT(event_id)')
				->from('#__jcalpro_event_categories')
				->where('category_id IN (' . implode(',', $childCategoryIds) . ')')
				->where('canonical = 1')
			);
			$count = $db->loadResult();
			
			// Check for DB error.
			if ($error = $db->getErrorMsg()) {
				JError::raiseWarning(500, $error);
				return false;
			}
			return $count;
		}
		// If we didn't have any categories to check, return 0
		return 0;
	}
	
	public function onJCalChangeApproval($context, $pks, $value) {
		if (!self::$_run) {
			return;
		}
		JCalProHelperLog::debug(__METHOD__ . '("' . $context . '", "' . implode(',', $pks) . '", "' . (int) $value . '")');
		switch ($this->_getContext($context)) {
			case 'event':
			case 'events':
				// do not trigger emails if we are not approving
				if (!$value) {
					break;
				}
				// loop the ids
				if (!empty($pks)) {
					foreach ($pks as $pk) {
						// load this id
						$table = JTable::getInstance('Event', 'JCalProTable');
						$table->load($pk);
						// not an event? skip
						if (!$table->id) {
							continue;
						}
						self::$_event_approved = true;
						$this->onJCalAfterSave(JCalPro::COM . '.event', $table, false);
						self::$_event_approved = false;
					}
				}
				break;
		}
	}
	
	/**
	 * handle the before save event
	 * 
	 * @param $context
	 * @param $table
	 */
	public function onJCalBeforeSave($context, $table) {
		if (!self::$_run) {
			return;
		}
		JCalProHelperLog::debug(__METHOD__ . '(' . $context . ')');
		$jcontext = $this->_getContext($context);
		switch ($jcontext) {
			case 'event':
				$approved = false;
				// if the event is not new, check to see if it's already been approved
				// the table will have no id if it's new, because it hasn't been saved yet
				if ($table->id && !((bool) $table->approved)) {
					// not approved, probably being approved...
					$approved = true;
				}
				self::$_event_approved = $approved;
				// THIS DOESN'T WORK!!!
				/*
				// is the event approved ?
				$check = (bool) $table->approved;
				// is it an existing event? if so, was it already approved?
				if ($table->id) {
					$checkTable = JTable::getInstance('Event', 'JCalProTable');
					$checkTable->load($table->id);
					$check = $check && ($checkTable->approved != $table->approved);
				}
				// set approval check flag
				self::$_event_approved = $check;// && $table->id;
				*/
				break;
			case 'field':
				$data = JFactory::getApplication()->input->post->get('jform', array(), 'array');
				if (array_key_exists('rules', $data)) {
					$table->setRules($data['rules']);
				}
				break;
		}
	}                
	
	/**
	 * handle the after save event
	 * 
	 * @param $context
	 * @param $table
	 * @param $isNew
	 */
	public function onJCalAfterSave($context, $table, $isNew) {
		if (!self::$_run) {
			return;
		}
		JCalProHelperLog::debug(__METHOD__ . '(' . $context . ')');
		$jcontext = $this->_getContext($context);
		// we use different private methods here depending on context
		switch ($jcontext) {
			
			case 'registration' :
			case 'event'        :
				// for now this ONLY handles new items, either registrations or events
				// TODO: handle existing items if needed
				//if (!$isNew) return;
				$method = '_send' . ucwords($jcontext) . 'Emails';
				JCalProHelperLog::debug("Running " . __CLASS__ . "::$method()...");
				return self::$method($table, $isNew);
				
			case 'field'        :
				// we have to manually update the rules for a field
				// because the core JAccessRule forces the rules into integers
				// this means that "Inherit" gets changed to "Denied" and is wrong
				// TODO: check if this is still applicable, as there was a serious bug in asset control
				$data  = JFactory::getApplication()->input->post->get('jform', array(), 'array');
				$rules = array();
				if (!empty($data) && array_key_exists('rules', $data)) {
					foreach ($data['rules'] as $action => $identities) {
						if (!empty($identities)) {
							foreach ($identities as $group => $permission) {
								if ('' == $permission) {
									continue;
								}
								$rules[$action][$group] = (int) ((bool) $permission);
							}
						}
					}
				}
				// update the asset
				$db = JFactory::getDbo();
				$db->setQuery($db->getQuery(true)
					->update('#__assets')
					->set('rules = ' . $db->Quote(json_encode($rules)))
					->where('id = ' . (int) $table->asset_id)
				);
				$db->query();
				break;
		}
	}
	
	public function onJCalEmailContextList(&$list) {
		if (!self::$_run) {
			return;
		}
		JCalProHelperLog::debug(__METHOD__);
		$list = array_merge($list, array(
			JHtml::_('select.option', 'event.admin.approve', JText::_('COM_JCALPRO_EMAIL_CONTEXT_EVENT_ADMIN_APPROVE'))
		,	JHtml::_('select.option', 'event.user.added',    JText::_('COM_JCALPRO_EMAIL_CONTEXT_EVENT_USER_ADDED'))
		,	JHtml::_('select.option', 'event.user.approve',  JText::_('COM_JCALPRO_EMAIL_CONTEXT_EVENT_USER_APPROVE'))
		));
	}
	
	private function _getContext($context) {
		return str_replace(JCalPro::COM . '.', '', $context);
	}
	
	/**
	 * sends out notification emails to moderators when a new event is added
	 * also send out a generic confirmation to the user that submitted the event, if not a guest
	 * 
	 * @param $table
	 */
	private function _sendEventEmails(&$table, &$isNew) {
		// if this event is a child event and not detached, skip it
		if (((bool) (int) $table->rec_id) && !((bool) (int) $table->detached_from_rec)) {
			JCalProHelperLog::debug("Child event of {$table->rec_id} skipped in " . __CLASS__ . '::' . __METHOD__);
			return;
		}
		// get the user from the event & send them an email (if allowed by their settings)
		$user = JFactory::getUser($table->created_by);
		// load the event model and get the fully parsed event
		$model = JCalPro::getModelInstance('Event', 'JCalProModel');
		$event = $model->getItem($table->id);
		// if this event is approved already, then it's either been created by a moderator
		// or it's a private event. regardless of which one, we don't need to notify the mods
		if ($table->approved && (self::$_event_approved || $isNew)) {
			// ensure we have a user :)
			if ($user->id) {
				// UPDATE: use mail helper to send user-defined emails
				JCalProHelperMail::send('event.user.added', $event, $user);
				// start building the submission courtesy email
				return;
			}
			else {
				JCalProHelperLog::debug("Approved event with no user, not sending event.user.added ...");
			}
		}
		// we only want to send out the moderator email if the event has approved = 0 and it's new
		else if ($isNew && 0 === (int) $table->approved) {
			// we need to get the email addresses for the moderators
			$mods = JCalProHelperMail::getModerators($event->categories->canonical->id);
			if (!empty($mods)) {
				// UPDATE: use mail helper to send user-defined emails
				foreach ($mods as $mod) {
					JCalProHelperMail::send('event.admin.approve', $event, $mod);
				}
			}
			// now send the user an email telling them that their event is awaiting approval
			if ($user->id) {
				// UPDATE: use mail helper to send user-defined emails
				JCalProHelperMail::send('event.user.approve', $event, $user);
				return;
			}
		}
		else {
			JCalProHelperLog::debug("No emails to send, details:\n" . print_r(array('table.approved' => (int) $table->approved, 'table.isnew' => ($isNew ? 'true' : 'false'), 'table.isapproved' => (self::$_event_approved ? 'true' : 'false')), 1));
		}
	}
	
	/**
	 * sends out a registration notification email
	 * 
	 * @param unknown_type $table
	 */
	private function _sendRegistrationEmails(&$table, &$isNew) {
		// for now, fix this
		if (!$isNew) {
			return;
		}
		$model = JCalPro::getModelInstance('Event', 'JCalProModel');
		$event = $model->getItem($table->event_id);
		if (!$event) {
			return;
		}
		
		// ensure we have an unpublisged registration
		if (!property_exists($event, 'registration_data') || $table->published) {
			return;
		}
		
		// set some extra data
		$table->confirmhref = JCalProHelperUrl::toFull(JCalProHelperUrl::task('registration.confirm', true, array('token' => $table->confirmation)));
		$table->details = JCalProHelperMail::buildEventData($event);
		
		if (property_exists($table, 'params') && is_string($table->params)) {
			$reg = new JRegistry;
			$reg->loadString($table->params);
			$table->params = $reg;
		}
		
		// now set registration in the event & pass to the helper
		$event->registration_data->current_entry = $table;
		
		$user = JFactory::getUser($table->user_id);
		if (!$user->id) {
			$user = new stdClass;
			$user->email    = $table->user_email;
			$user->name     = $table->user_name;
			$user->username = $table->user_name;
		}
		
		// UPDATE: use mail helper to send user-defined emails
		JCalProHelperMail::send('registration.confirm', $event, $user);
		
		// unset the things we added
		unset($event->registration_data->current_entry);
		unset($table->confirmhref);
		unset($table->details);
		
		/*
		$details   = JCalProHelperMail::getSiteDetails();
		$url       = JCalProHelperUrl::toFull(JCalProHelperUrl::task('registration.confirm', true, array('token' => $table->confirmation)));
		$name      = $table->user_name;
		$email     = $table->user_email;
		$eventdata = JCalProHelperMail::buildEventData($event);
		$subject   = JText::sprintf('COM_JCALPRO_CONFIRMATION_SUBJECT', $details['sitename']);
		$body      = JText::sprintf('COM_JCALPRO_CONFIRMATION_BODY', $event->title, $details['sitename'], JUri::root(), $url, $eventdata);
		// build the mail
		JCalProHelperMail::mail($email, $subject, $body);
		*/
	}
}

jimport('joomla.event.dispatcher');
if (JCalPro::version()->isCompatible('3.0.0')) {
	$dispatcher = JEventDispatcher::getInstance();
}
else {
	$dispatcher = JDispatcher::getInstance();
}
$dispatcher->register('onJCalChangeApproval', 'plgContentJCalPro');
