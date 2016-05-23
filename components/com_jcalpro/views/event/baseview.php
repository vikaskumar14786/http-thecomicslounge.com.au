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

jimport('joomla.application.component.view');

JLoader::register('JCalProView', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/views/baseview.php');
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/libraries/helpers/jcalpro.php');
JCalPro::registerHelper('url');

/**
 * JCalPro event view.
 *
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
class JCalProViewEvent extends JCalProView
{
	function display($tpl = null, $safeparams = false) {
		
		$app = JFactory::getApplication();
		// we need to set the format in the model's state in case of ical
		$format = $app->input->get('format', 'html', 'cmd');
		$this->extmode = 'event';
		
		// get data from the model
		$form = $this->get('Form');
		$item = $this->get('Item');
		$categories = $this->get('Categories');
		$user = JFactory::getUser();
		
		// check this item to see if it's an orphan
		$item_id = $app->input->get('id', 0);
		if ($item_id && $item && !$item->id) {
			// this is either an orphan or a deleted/missing event
			// check the xref table to see if it's an orphan
			// if it is, redirect to the parent
			$parent = $this->getModel()->getOrphanParent($item_id);
			if ($parent) {
				$app->redirect(JCalProHelperUrl::page(array('id' => $parent)));
				jexit();
			}
		}
		
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		
		// check this event
		if ($item && $item->id && (1 != $item->published || 1 != $item->approved)) {
			// check permissions here
			if (!JCalPro::canModerateEvents($item->categories->canonical->id)) {
				if ($user->guest) {
					$app->enqueueMessage(JText::_('JGLOBAL_YOU_MUST_LOGIN_FIRST'), 'error');
					$app->redirect(JRoute::_('index.php?option=com_users&view=login'));
				}
				else if ($user->id != $item->created_by) {
					JError::raiseError(404, JText::_('COM_JCALPRO_ERROR_PAGE_NOT_FOUND'));
				}
				jexit();
			}
		}
		
		// Assign the Data
		$this->form       = $form;
		$this->item       = $item;
		$this->user       = $user;
		$this->categories = $categories;
		$this->category   = false;
		
		JCalPro::debugger('Item', $this->item);
		JCalPro::debugger('Form', $this->form);
		JCalPro::debugger('Categories', $this->categories);
		
		$catid = $app->getUserStateFromRequest('com_jcalpro.events.jcal.catid', 'catid', 0);
		foreach ($categories as $cat) {
			if ($cat->id == $catid) {
				$this->category = $cat;
				break;
			}
		}
		
		// switch the different view modes depending on format
		switch ($format) {
			// ical format, register the ical helper, process the ical and exit
			case 'ical':
				JLoader::register('JCalProHelperIcal', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/ical.php');
				echo JCalProHelperIcal::toIcal(array($this->item));
				jexit();
				break;
			// all other formats we just display normally
			default:
				if ('edit' != $app->input->get('layout', '', 'cmd')) {
					if (!$item->id) {
						JError::raiseError(404, JText::_('COM_JCALPRO_ERROR_PAGE_NOT_FOUND'));
					}
					// fix the theme for this item
					$this->template = $this->item->categories->canonical->params->get('jcalpro_theme');
					// add the registration button if this event allows it
					if ($this->item->allow_registration && $this->item->registration
					&& isset($this->item->registration_data)
					&& is_object($this->item->registration_data)
					&& $this->item->registration_data->can_register
					) {
						JCalPro::registerHelper('toolbar');
						JCalProHelperToolbar::addButton('register', array(
							'href' => JCalProHelperUrl::task('registration.add', true, array('event_id' => $this->item->id))
						));
					}
					// go ahead and build the form field layouts
					$displaytypes = array('hidden', 'header', 'top', 'bottom', 'side');
					$fields = new stdClass;
					foreach ($displaytypes as $dt) $fields->{$dt} = array();
					// now grab the fields for this event
					$formid = (int) $this->item->categories->canonical->params->get('jcalpro_eventform');
					JLoader::register('JCalProHelperForm', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/form.php');
					$formfields = JCalProHelperForm::getFields($formid);
					// load any found fields and add them to the appropriate places
					if (!empty($formfields)) {
						foreach ($formfields as $field) {
							if (array_key_exists($field->name, $this->item->params)) {
								$fields->{$displaytypes[$field->event_display]}[] = $field;
							}
						}
					}
					$this->item->formid = $formid;
					$this->item->formfields = $formfields;
					
					// assign the form field layouts to the item
					$this->item->custom_fields = $fields;
				}
				
				// display
				parent::display($tpl, $safeparams);
		}
	}
	
	/**
	 * Prepares the document
	 */
	protected function _prepareDocument() {
		parent::_prepareDocument();
		if ('edit' == $this->_layout) {
			JHtml::_('behavior.framework', true);
			$this->document->addScriptDeclaration('window.jclDateTimeCheckUrl = \'' . JCalProHelperFilter::escape_js(JCalProHelperUrl::task('event.checkdate')) . '\';');
			$this->document->addScript(JCalProHelperUrl::media() . '/js/event.js');
			$this->document->addStyleSheet(JCalProHelperUrl::media() . '/css/event.css');
		}
		else {
			if (!empty($this->item->description)) {
				$this->document->setDescription(JCalProHelperFilter::truncate(str_replace("\n", "  ", trim(strip_tags($this->item->description)))), 160);
			}
			$this->document->setTitle(str_replace("\n", "  ", trim(strip_tags($this->item->title))));
		}
	}
	
	/**
	 * Handle breadcrumbs
	 * 
	 * @param array
	 * 
	 * @return array
	 */
	public function getBreadcrumbs(&$menu) {
		$app    = JFactory::getApplication();
		$crumbs = array();
		// make sure we have a menu!
		if (!$menu) {
			return $crumbs;
		}
		// some variables
		$option = @$menu->query['option'];
		$view   = @$menu->query['view'];
		$id     = @$menu->query['id'];
		
		if (JCalPro::COM == $option && 'event' == $view && $id == @$this->item->id) {
			// this is ours - leave it
			return $crumbs;
		}
		$crumbs[] = $this->getCrumb($this->item->title, '');
		
		return $crumbs;
	}
}
