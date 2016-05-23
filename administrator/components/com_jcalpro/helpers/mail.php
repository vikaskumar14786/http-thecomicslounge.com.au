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

JLoader::register('JCalProHelperLog', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/log.php');

abstract class JCalProHelperMail
{
	/**
	 * returns the "namespace" in a . delimited string, e.g. event.user.added
	 * 
	 * @param string $context
	 * @return string
	 */
	static public function getContextNamespace($context) {
		return array_shift(explode('.', $context));
	}
	
	/**
	 * send an email for the given context
	 * 
	 * @param unknown_type $context
	 * @param unknown_type $event
	 * @param unknown_type $user
	 * @param unknown_type $subject
	 * @param unknown_type $body
	 * @throws Exception
	 * @return void|Ambigous <mixed, object, boolean, reference>
	 */
	static public function send($context, $event, $user, $subject = false, $body = false) {
		
		JCalProHelperLog::debug('Sending mail for context ' . $context . ' ...');
		
		// if there's no user, set to the current one
		if (!is_object($user)) {
			$user = JFactory::getUser();
		}
		
		// don't send if we're not supposed to
		if (!JCalProHelperMail::canSendTo($user->email)) {
			JCalProHelperLog::debug('Cannot send email for context ' . $context . ' to email ' . $user->email . ' ...');
			return false;
		}
		
		// get the language
		$lang = '';
		if (method_exists($user, 'getParam')) {
			$lang = $user->getParam('language', $user->getParam('admin_language'));
		}
		else if (property_exists($user, 'language')) {
			$lang = $user->language;
		}
		else {
			$u    = JFactory::getUser();
			$lang = $u->getParam('language', $u->getParam('admin_language'));
		}
		// do we have a language? if not use site default
		if (empty($lang)) {
			$lang = JFactory::getLanguage()->getTag();
		}
		
		// figure out what our context namespace is now
		$namespace = self::getContextNamespace($context);
		// get the mail based on context
		$db = JFactory::getDbo();
		
		$query = $db->getQuery(true)
			->select('*')
			->from('#__jcalpro_emails')
			->where($db->quoteName('context') . ' = ' . $db->quote($context))
			->order($db->quoteName('language') . ' DESC')
			->order($db->quoteName('default') . ' DESC')
		;
		
		// try to filter by language, if any :)
		if (!empty($lang)) {
			$query->where($db->quoteName('language') . ' IN (' . $db->quote('*') . ', ' . $db->quote($lang) . ')');
		}
		
		$db->setQuery($query);
		
		try {
			$email = $db->loadObject();
			if (empty($email)) {
				throw new Exception('No email!');
			}
			JCalProHelperLog::debug('Found the following email for context ' . $context . ":\n" . print_r($email, 1));
		}
		catch (Exception $e) {
			JCalProHelperLog::error('No email found for context ' . $context);
			return false;
		}
		
		$email->body    = JCalProHelperMail::replaceTags((!empty($body) ? $body : $email->body), $event, $user);
		$email->subject = JCalProHelperMail::replaceTags((!empty($subject) ? $subject : $email->subject), $event, $user);
		
		return (bool) JCalProHelperMail::mail($user->email, $email->subject, $email->body);
	}
	
	/**
	 * static method to send a mail using JMail
	 * 
	 * @param mixed  $email
	 * @param string $subject
	 * @param string $body
	 * @param mixed  $from
	 */
	static public function mail($email, $subject, $body, $from = null) {
		// get our mailer object
		$mail = JFactory::getMailer();
		// get our details
		$details = self::getSiteDetails();
		// if email is empty, we'll go ahead and send it to the site admin (yuk)
		if (empty($email)) {
			$email = array($details['mailfrom']);
		}
		// if email is a string, we only have one recipient
		// if it's an array, we send to all
		if (!is_array($email)) {
			$email = array($email);
		}
		// BUGFIX: possible issue with empty TO field
		if (1 < count($email)) {
			$mail->addRecipient($details['mailfrom']);
			foreach ($email as $e) {
				$mail->addBCC($e);
			}
		}
		else {
			$mail->addRecipient($email[0]);
		}
		// set the subject
		$mail->setSubject($subject);
		// set the body, both in html and text
		$textbody = "$body";
		$htmlbody = "$body";
		if (false !== strpos($body, '<')) {
			$textbody = str_ireplace('</p>', "\n\n</p>", $textbody);
			$textbody = preg_replace('/<br\s*\/?>/i', "\n", $textbody);
			$textbody = strip_tags($textbody);
		}
		else {
			$htmlbody = nl2br($htmlbody);
		}
		$mail->IsHTML(true);
		$mail->setBody($htmlbody);
		$mail->AltBody = $textbody;
		// if we have a from, use that - otherwise load the site details
		if (is_null($from) || empty($from)) {
			$details = self::getSiteDetails();
			$mail->setSender(array($details['mailfrom'], $details['fromname']));
		}
		else {
			$mail->setSender($from);
		}
		return $mail->Send();
	}
	
	/**
	 * static method to get the details about the site for sending emails
	 * 
	 * @return array
	 */
	static public function getSiteDetails() {
		static $details;
		if (!is_array($details)) {
			jimport('joomla.environment.uri');
			$details = array();
			foreach (array('mailfrom', 'fromname', 'sitename') as $var) {
				$details[$var] = JFactory::getApplication()->getCfg($var);
			}
			$details['url'] = JUri::root();
		}
		return $details;
	}
	
	/**
	 * get moderators (by category or otherwise)
	 * 
	 * @param unknown_type $catid
	 * @return multitype:|Ambigous <multitype:, mixed, void, NULL, multitype:unknown mixed >
	 */
	static public function getModerators($catid = null) {
		/**/
		// go directly to the database and load the available groups
		// this query is based on the UserHelper but converted to use JDatabaseQuery
		$db = JFactory::getDbo();
		$db->setQuery((string) $db->getQuery(true)
			// first level
			->select('a.id')
			->from('#__usergroups AS a')
			// join over the other levels
			->leftJoin('#__usergroups AS b ON a.lft > b.lft AND a.rgt < b.rgt')
			// we have to join over on the xref table
			//->leftJoin('#__user_usergroup_map AS m ON m.group_id = a.id')
			//->where('m.user_id IS NOT NULL')
			// grouping & ordering
			->group('a.id')
			->order('a.lft ASC')
		);
		$groups = $db->loadColumn();
		// build the asset identifier
		$asset = 'com_jcalpro' . (is_null($catid) ? '' : '.category.' . (int) $catid);
		// loop each group & check it against our asset so we can get the associated users
		$users = array();
		foreach ($groups as $group) {
			// bah, check moderate AND Super Admin (wtf Joomla)
			if (JAccess::checkGroup($group, 'core.moderate', $asset) || JAccess::checkGroup($group, 'core.admin')) {
				$gusers = JAccess::getUsersByGroup($group, true);
				if (!empty($gusers)) {
					$users = array_merge($users, $gusers);
				}
			}
		}
		$users = array_unique($users);
		// don't let this get to the next query...
		if (empty($users)) {
			return $users;
		}
		$db->setQuery((string) $db->getQuery(true)
			->select('User.*')
			->from('#__users AS User')
			// join over the profile so we can ascertain if the user is to be sent emails
			->leftJoin('#__user_profiles AS Profile ON Profile.user_id = User.id AND Profile.profile_key = "jcalpro.jcalpro_send_mail"')
			// ensure we're getting the correct values
			->where('User.block = 0')
			->where('User.id IN (' . implode(', ', $users) . ')')
			->where('(Profile.profile_value = 1 OR Profile.profile_value IS NULL)')
			->group('User.id')
		);
		return $db->loadObjectList();
	}
	
	/**
	 * can we send mail to this user?
	 * 
	 * @param unknown_type $user
	 * @return bool
	 */
	static public function canSendTo($email) {
		static $emails;
		if (is_null($emails)) $emails = array();
		// check if we have this one already
		if (array_key_exists($email, $emails)) return $emails[$email];
		// do a lookup
		$db = JFactory::getDbo();
		$db->setQuery((string) $db->getQuery(true)
			->select('User.email')
			->from('#__users AS User')
			// join over the profile so we can ascertain if the user is to be sent emails
			->leftJoin('#__user_profiles AS Profile ON Profile.user_id = User.id AND Profile.profile_key = "jcalpro.jcalpro_send_mail"')
			// ensure we're getting the correct values
			->where('User.block = 0')
			->where('User.email = ' . $db->quote($email))
			->where('(Profile.profile_value = 1 OR Profile.profile_value IS NULL)')
			->group('User.id')
		);
		// get the value
		try {
			$canSendTo = ($db->loadResult() == $email);
		}
		catch (Exception $e) {
			JCalProHelperLog::debug($e->getMessage());
			return ($emails[$email] = false);
		}
		// if we can't send to this user, do a quick lookup to see if they are a guest
		// if so, send anyways
		if (!$canSendTo) {
			$db->setQuery((string) $db->getQuery(true)
				->select('User.email')
				->from('#__users AS User')
				->where('User.email = ' . $db->quote($email))
			);
			try {
				$canSendTo = !($db->loadResult() == $email);
			}
			catch (Exception $e) {
				$canSendTo = false;
			}
		}
		
		return ($emails[$email] = $canSendTo);
	}
	
	/**
	 * get a list of moderator emails
	 * 
	 * @param unknown_type $catid
	 * @return multitype:
	 */
	static public function getModeratorEmails($catid = null) {
		$users = self::getModerators($catid);
		$emails = array();
		if (!empty($users)) foreach ($users as $user) {
			$emails[] = $user->email;
		}
		$emails = array_unique($emails);
		return $emails;
	}
	
	/**
	 * static method to build event data in text format for emails
	 * 
	 * @param $event
	 * @return string
	 */
	static public function buildEventData(&$event) {
		JLoader::register('JCalProHelperUrl', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/url.php');
		// our string info
		$string = array();
		
		// start with the name of the event
		$string[] = JText::sprintf('COM_JCALPRO_CONFIRMATION_DATA_TITLE', $event->title);
		// start date
		$string[] = JText::sprintf('COM_JCALPRO_CONFIRMATION_DATA_START_DATE_TIME', $event->user_datedisplay, (property_exists($event, 'user_start_timedisplay') ? $event->user_start_timedisplay : $event->user_timedisplay));
		// end date, if necessary
		if (property_exists($event, 'user_end_datedisplay')) {
			$string[] = JText::sprintf('COM_JCALPRO_CONFIRMATION_DATA_END_DATE_TIME', $event->user_end_datedisplay, $event->user_end_timedisplay);
		}
		// custom fields
		if (!empty($event->params)) {
			// load the html helper so we can render the fields properly
			jimport('joomla.html.html');
			JHtml::addIncludePath(JPATH_ROOT . '/components/com_jcalpro/helpers/html');
			// load our form fields
			$formid = (int) @$event->categories->canonical->params->get('jcalpro_eventform');
			JLoader::register('JCalProHelperForm', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/form.php');
			$formfields = JCalProHelperForm::getFields($formid);
			// loop our params
			foreach ($event->params as $key => $val) {
				// find our field
				$field = false;
				if (!empty($formfields)) {
					foreach ($formfields as $formfield) {
						if ($key == $formfield->name) {
							$field = $formfield;
							break;
						}
					}
				}
				if ($field) {
					$val = JHtml::_('jcalpro.formfieldvalue', $field, $val, false);
					$string[] = JText::sprintf('COM_JCALPRO_CONFIRMATION_DATA_CUSTOM', $field->title, $val);
				}
			}
		}
		// the event url (wrapped in newlines)
		$string[] = '';
		$string[] = JCalProHelperUrl::toFull(JCalProHelperUrl::event($event->id));
		$string[] = '';
		
		// return the string
		return implode("<br />\n", $string);
		
	}
	
	/**
	 * gets a list of available email tags
	 * 
	 * NOTE FOR ADVANCED USERS:
	 * 
	 * There is a configuration option called "custom tags" that allows the admin to open up
	 * all kinds of information from an event - basically anything you can see when you look
	 * at an event in debug mode under the "Item" slider. For example, if you wanted to allow
	 * the common event id, you would add the tag "event.common_event_id" in the custom tags
	 * textarea on its own line, and use the tag "{%event.common_event_id%}" in the email itself.
	 * Each tag is derived from the object referenced, though be warned! Events are likely the
	 * only objects that will ALWAYS have extra information, and even then some events have
	 * certain properties that others do not, depending on the event in question.
	 * 
	 * EXAMPLES:
	 * 
	 * In the "Item" slider on an event, you may see the following:
	 * 
	 * JObject Object
	 * (
	 * 
	 *      ...
	 * 
	 *      [id] => 20
	 *      [title] => Aethan's Birthday
	 *      [alias] => aethans-birthday
	 *      [language] => *
	 *      [description] => <p>My little boy's special day!</p>
	 *      [common_event_id] => 20120221T000000UTC-7b86f4@http://my.fake.site/
	 * 
	 *      ...
	 * 
	 * )
	 * 
	 * These elements can be accessed using the respective tags (if enabled via the component,
	 * a plugin or the custom tags configuration field):
	 * 
	 *      event.id
	 *      event.title
	 *      event.alias
	 *      event.language
	 *      event.description
	 *      event.common_event_id
	 * 
	 * 
	 * 
	 * @param mixed $filter
	 * @param bool $keys
	 * 
	 * @return array
	 */
	public static function getEmailTags($filter = null, $keys = true, $refresh = false) {
		static $tags;
		if (!is_array($tags) || $refresh) {
			$tags = array(
				// site variables
				'site.name'     => 'COM_JCALPRO_EMAIL_TAG_SITE_NAME',
				'site.url'      => 'COM_JCALPRO_EMAIL_TAG_SITE_URL',
				'site.mailfrom' => 'COM_JCALPRO_EMAIL_TAG_SITE_MAILFROM',
				'site.fromname' => 'COM_JCALPRO_EMAIL_TAG_SITE_FROMNAME',
				// event variables
				'event.title'           => 'COM_JCALPRO_EMAIL_TAG_EVENT_TITLE',
				'event.description'     => 'COM_JCALPRO_EMAIL_TAG_EVENT_DESCRIPTION',
				'event.start'           => 'COM_JCALPRO_EMAIL_TAG_EVENT_START',
				'event.end'             => 'COM_JCALPRO_EMAIL_TAG_EVENT_END',
				'event.fullhref'        => 'COM_JCALPRO_EMAIL_TAG_EVENT_FULLHREF',
				'event.adminhref'       => 'COM_JCALPRO_EMAIL_TAG_EVENT_ADMINHREF',
				'event.edithref'        => 'COM_JCALPRO_EMAIL_TAG_EVENT_EDITHREF',
				'event.adminedithref'   => 'COM_JCALPRO_EMAIL_TAG_EVENT_ADMINEDITHREF',
				'event.canonical.title' => 'COM_JCALPRO_EMAIL_TAG_EVENT_CANONICAL_TITLE',
				'event.qrcode'          => 'COM_JCALPRO_EMAIL_TAG_EVENT_QRCODE',
				// registration
				'registration.confirmhref' => 'COM_JCALPRO_EMAIL_TAG_REGISTRATION_CONFIRMHREF',
				'registration.details'     => 'COM_JCALPRO_EMAIL_TAG_REGISTRATION_DETAILS',
				// user variables
				'user.name'     => 'COM_JCALPRO_EMAIL_TAG_USER_NAME',
				'user.username' => 'COM_JCALPRO_EMAIL_TAG_USER_USERNAME',
				'user.email'    => 'COM_JCALPRO_EMAIL_TAG_USER_EMAIL'
			);
			// get custom tags from config
			$custom = JCalPro::config('email_tags');
			if (!empty($custom)) {
				$custom = explode("\n", $custom);
				foreach ($custom as $c) {
					$c = trim($c);
					$lang = 'COM_JCALPRO_EMAIL_TAG_' . strtoupper(str_replace('.', '_', $c));
					if (JText::_($lang) == $lang) {
						$lang = 'COM_JCALPRO_EMAIL_TAG_USER_DEFINED';
					}
					$tags[$c] = $lang;
				}
			}
			// TODO: fire an event plugin to extend these?
		}
		ksort($tags);
		// filter
		if (!empty($filter)) {
			$filtered = array();
			foreach ($tags as $key => $value) {
				if (0 === stripos($key, $filter)) {
					$filtered[$key] = $value;
				}
			}
			return $keys ? array_keys($filtered) : $filtered;
		}
		return $keys ? array_keys($tags) : $tags;
	}
	
	public static function replaceEventTags($string, $event) {
		// extra tags
		$tags = array();
		// populate extra tags array
		if (property_exists($event, 'params') && is_array($event->params) && !empty($event->params)) {
			foreach ($event->params as $key => $param) {
				$tags[] = "event.params.$key";
			}
		}
		// replace all the tags
		return self::_replaceTags($string, 'event', $event, $tags);
	}
	
	public static function replaceRegistrationTags($string, $event) {
		// check that we have what we need!!!
		if (!property_exists($event, 'registration_data') || !property_exists($event->registration_data, 'current_entry')) {
			return $string;
		}
		return self::_replaceTags($string, 'registration', $event->registration_data->current_entry);
	}
	
	public static function replaceSiteTags($string) {
		static $site;
		if (is_null($site)) {
			jimport('joomla.environment.uri');
			$app            = JFactory::getApplication();
			$site           = new stdClass;
			$site->url      = JUri::root();
			$site->fromname = $app->getCfg('fromname');
			$site->mailfrom = $app->getCfg('mailfrom');
			$site->name     = $app->getCfg('sitename');
		}
		return self::_replaceTags($string, 'site', $site);
	}
	
	public static function replaceCustomTags($string, &$event, &$user) {
		// fire an event to allow altering of string
		JDispatcher::getInstance()->trigger('onJCalReplaceEmailTags', array(&$string, &$event, &$user));
		return $string;
	}
	
	public static function replaceUserTags($string, $user) {
		return self::_replaceTags($string, 'user', $user);
	}
	
	public static function deleteAllTags($string) {
		return preg_replace('/\{\%.*?\%\}/', '', $string);
	}
	
	public static function replaceTags($string, $event, $user) {
		$string = JCalProHelperMail::replaceEventTags($string, $event);
		$string = JCalProHelperMail::replaceRegistrationTags($string, $event);
		$string = JCalProHelperMail::replaceUserTags($string, $user);
		$string = JCalProHelperMail::replaceSiteTags($string);
		$string = JCalProHelperMail::replaceCustomTags($string, $event, $user);
		$string = JCalProHelperMail::deleteAllTags($string);
		return $string;
	}
	
	private static function _replaceTags($string, $namespace, $object, $extra = false) {
		$tags = JCalProHelperMail::getEmailTags($namespace);
		
		if ($extra && is_array($extra)) {
			$tags = array_merge($tags, $extra);
		}
		
		if (!empty($tags)) foreach ($tags as $tag) {
			if (false === stripos($string, $tag)) {
				continue;
			}
			if (false !== strpos($tag, 'event.canonical')) {
				$parts = explode('.', str_replace('event.canonical', 'event.categories.canonical', $tag));
			}
			else if ('event.start' === $tag) {
				$parts = array('event', 'user_date_display', 'event_start');
			}
			else if ('event.end' === $tag) {
				$parts = array('event', 'user_date_display', 'event_end');
			}
			else {
				$parts = explode('.', $tag);
			}
			$context = array_shift($parts);
			$params  = false;
			$value   = false;
			while (!empty($parts)) {
				$part = array_shift($parts);
				// handle the value differently based on it's type
				if ($value) {
					// arrays should have the key available
					if (is_array($value) && array_key_exists($part, $value)) {
						$value = $value[$part];
					}
					// JRegistry uses get() for values
					else if (is_object($value) && $value instanceof JRegistry) {
						$value = $value->get($part);
					}
					// normal object
					else if (is_object($value) && property_exists($value, $part)) {
						$value = $value->{$part};
					}
					// object with this method
					else if (is_object($value) && method_exists($value, $part)) {
						$value = call_user_func(array($value, $part));
					}
					// don't know what to do here...
					else {
						JCalProHelperLog::error("Key or property '$part' not found in tag '$tag'");
						$value = '';
						break;
					}
				}
				else {
					$value = $object->{$part};
				}
			}
			if (is_array($value)) {
				if (1 == count($value)) {
					$value = array_shift($value);
				}
				else {
					$value = '<ul><li>' . implode('</li><li>', $value) . '</li>';
				}
			}
			$string = str_ireplace("{%$tag%}", $value, $string);
		}
		return $string;
	}
}
