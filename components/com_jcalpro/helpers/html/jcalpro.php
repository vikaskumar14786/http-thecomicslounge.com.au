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

JCalPro::registerHelper('array');
JCalPro::registerHelper('date');
JCalPro::registerHelper('filter');
JCalPro::registerHelper('toolbar');
JCalPro::registerHelper('url');

/**
 * Utility class for JCalPro
 *
 * @static
 * @package		JCalPro
 * @subpackage	com_jcalpro
 */
abstract class JHtmlJCalPro
{
	/**
	 * Starts a tab set
	 * 
	 * @param string $selector
	 * @param array  $options
	 */
	public static function startTabSet($selector, $options = array()) {
		if (JCalPro::version()->isCompatible('3.1.0')) {
			JHtml::_('bootstrap.framework');
			return JHtml::_('bootstrap.startTabSet', $selector, $options);
		}
		else {
			return JHtml::_('tabs.start', $selector, $options);
		}
	}
	
	/**
	 * Starts a slider set
	 * 
	 * @param string $tabSetName
	 * @param array  $options
	 */
	public static function startSlider($selector = 'myAccordian', $options = array()) {
		if (JCalPro::version()->isCompatible('3.1.0')) {
			JHtml::_('bootstrap.framework');
			return JHtml::_('bootstrap.startAccordion', $selector, $options);
		}
		else {
			return JHtml::_('sliders.start', $selector, $options);
		}
	}
	
	/**
	 * Adds a tab to the tab set
	 * 
	 * @param string $selector
	 * @param string $id
	 * @param string $text
	 */
	public static function addTab($selector, $id, $text) {
		if (JCalPro::version()->isCompatible('3.1.0')) {
			return JHtml::_('bootstrap.addTab', $selector, $id, $text);
		}
		else {
			return JHtml::_('tabs.panel', $text, $id);
		}
	}
	
	/**
	 * Adds a slide to the slides set
	 * 
	 * @param string $selector
	 * @param string $id
	 * @param string $text
	 */
	public static function addSlide($selector, $text, $id, $class = '') {
		if (JCalPro::version()->isCompatible('3.1.0')) {
			return JHtml::_('bootstrap.addSlide', $selector, $text, $id, $class);
		}
		else {
			return JHtml::_('sliders.panel', $text, $id);
		}
	}
	
	/**
	 * Ends a tab
	 * 
	 * @return string
	 */
	public static function endTab() {
		if (JCalPro::version()->isCompatible('3.1.0')) {
			return JHtml::_('bootstrap.endTab');
		}
		else {
			return '';
		}
	}
	
	/**
	 * Ends a slide
	 * 
	 * @return string
	 */
	public static function endSlide() {
		if (JCalPro::version()->isCompatible('3.1.0')) {
			return JHtml::_('bootstrap.endSlide');
		}
		else {
			return '';
		}
	}
	
	/**
	 * Ends a tab set
	 * 
	 * @return string
	 */
	public static function endTabSet() {
		if (JCalPro::version()->isCompatible('3.1.0')) {
			return JHtml::_('bootstrap.endTabSet');
		}
		else {
			return JHtml::_('tabs.end');
		}
	}
	
	/**
	 * Ends a set of slides
	 * 
	 * @return string
	 */
	public static function endSlider() {
		if (JCalPro::version()->isCompatible('3.1.0')) {
			return JHtml::_('bootstrap.endTabSet');
		}
		else {
			return JHtml::_('sliders.end');
		}
	}
	
	/**
	 * renders a "spacer" image
	 * 
	 * @param unknown_type $width
	 * @param unknown_type $height
	 * @param unknown_type $attr
	 */
	public static function spacer($width, $height, $attr = array()) {
		if (!is_array($attr)) {
			$attr = array();
		}
		$attr['width']  = $width;
		$attr['height'] = $height;
		if (!array_key_exists('border', $attr)) {
			$attr['border'] = '0';
		}
		if (!array_key_exists('alt', $attr)) {
			$attr['alt'] = '';
		}
		return self::image('spacer.gif', '', $attr);
	}
	
	/**
	 * renders an image from the layout provided (or default if not available)
	 * 
	 * @param unknown_type $img
	 * @param unknown_type $layout
	 * @param unknown_type $attr
	 * @param unknown_type $path_only
	 */
	public static function image($img, $layout = '', $attr = array(), $path_only = false) {
		jimport('joomla.filesystem.file');
		$layout = 'themes/' . rtrim(basename((string) $layout), '/') . '/';
		$root = '/media/jcalpro';
		$ext  = JFile::getExt($img);
		$exts = array_diff(array('gif', 'png', 'jpg'), array(strtolower($ext)));
		array_unshift($exts, $ext);
		$img  = substr($img, 0, strlen($img) - (1 + strlen($ext)));
		foreach ($exts as $e) {
			$file = "$root/{$layout}images/$img.$e";
			if (JFile::exists(JPATH_ROOT . $file)) {
				break;
			}
			$file = "$root/images/$img.$e";
			if (JFile::exists(JPATH_ROOT . $file)) {
				break;
			}
		}
		// TODO: check if it's there again & return a known image on fail?
		
		$base = rtrim(JUri::base(), '/');
		if (JFactory::getApplication()->isAdmin()) {
			$base = str_replace('/administrator', '', $base);
		}
		
		$file = "$base/" . JCalProHelperFilter::escape(ltrim($file, '/'));
		
		// we have a file name, no need to process more if that's all we need :)
		if ($path_only) {
			return $file;
		}
		
		// TODO: filter attributes (we're probably not showing user-supplied images tho)
		$html = '<img src="' . $file . '" ';
		if (!empty($attr) && is_array($attr)) {
			foreach ($attr as $k => $v) {
				$html .= $k . '="' . $v . '" ';
			}
		}
		$html .= '/>';
		
		return $html;
	}
	
	public static function calendarlistoptions($ids = array(), $show_default = true, $create_only = false, $real_only = false) {
		static $categories;
		
		if (!isset($categories)) {
			// load our categories from the category model
			JCalProBaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models');
			$catModel = JCalPro::getModelInstance('Categories', 'CategoriesModel', array('ignore_request' => true));
			// only get ours
			$catModel->setState('filter.extension', 'com_jcalpro');
			// only get published
			$catModel->setState('filter.published', 1);
			// make sure we're ordering properly
			$catModel->setState('list.ordering', 'a.lft');
			$catModel->setState('list.direction', 'ASC');
			$categories = $catModel->getItems();
		}
		
		$list = array();
		
		// show default option
		if ($show_default) {
			$list[] = JHtml::_('select.option', '', JText::_('COM_JCALPRO_ANY_CATEGORY'));
		}
		
		// we need this later
		$user = JFactory::getUser();
		// go ahead and grab the params from the menu item so we can filter this list
		$eventsModel = JCalPro::getModelInstance('Events', 'JCalProModel', array('ignore_request' => true));
		$catfilters = $eventsModel->getCategoryFilters();
		$invertcatfilters = $eventsModel->getCategoryFiltersInvert();
		// if we have category filters, merge this with the provided ids
		// but only if we're not inverting - if we are, handle this in the loop below
		if (!is_array($ids)) {
			$ids = array();
		}
		if (!$invertcatfilters && !empty($catfilters)) {
			$ids = array_merge($ids, $catfilters);
		}
		// form our category list into options
		if (!empty($categories)) {
			foreach ($categories as $cat) {
				// filter out any categories not necessary if not inverted
				if (!$invertcatfilters && !empty($ids) && !in_array($cat->id, $ids)) {
					continue;
				}
				// if the filter is inverted, handle that
				if ($invertcatfilters && !empty($catfilters) && in_array($cat->id, $catfilters)) {
					continue;
				}
				// filter out categories the user cannot create events inside, if necessary
				if ($create_only) {
					$canCreatePublic = $user->authorise('core.create', 'com_jcalpro.category.' . $cat->id);
					$canCreatePrivate = $user->authorise('core.create.private', 'com_jcalpro.category.' . $cat->id);
					if (!($canCreatePublic || $canCreatePrivate)) {
						continue;
					}
				}
				// build the option
				$list[] = JHtml::_('select.option', $cat->id, str_repeat('- ', max(0, $cat->level - 1)) . $cat->title);
			}
		}
		
		// ouch ... empty?
		//if (count($list) == (int) $show_default || (!empty($categories) && 1 == count($categories))) {
		if (empty($categories)) {
			$list = array();
		}
		
		return $list;
	}
	
	/**
	 * creates an array to represent a toolbar button
	 * 
	 * @param unknown_type $name
	 * @param unknown_type $params
	 * 
	 * @return array
	 * @since  3.2.3
	 */
	private static function _menuButton($name, $params = array()) {
		return JCalProHelperToolbar::addButton($name, $params);
	}
	
	public static function menu($extra = array()) {
		
		$buttons = array();
		
		$app = JFactory::getApplication();
		
		// since we use the view and layout often, set it now
		$view = $app->input->get('view', '', 'cmd');
		$layout = $app->input->get('layout', '', 'cmd');
		
		// build the extras for the various buttons
		$print_extra = array('tmpl'=>'component','print'=>1);
		$ical_extra  = array('format'=>'ical');
		
		// set date if not today
		$date       = '';
		$month_date = '';
		$week_date  = '';
		$today = JCalProHelperDate::getToday();
		$rdate = JCalProHelperDate::getDate();
		// fix for event view not adding the correct dates
		if ('event' == $view) {
			// get this event
			$model = JCalProBaseModel::getInstance('Event', 'JCalProModel');
			if ($model && is_object($model) && method_exists($model, 'getItem')) {
				$event = $model->getItem();
				if ($event && is_object($event) && property_exists($event, 'datetime')) {
					$rdate = clone $event->datetime;
				}
			}
		}
		if ($today != $rdate) {
			$date = $rdate->toRequest();
			// we have to use the helper again for month and week
			// clone these first :)
			$month_date = clone $rdate;
			$week_date  = clone $rdate;
			// now adjust
			$month_date = $month_date->toMonthStart()->toRequest();
			$week_date  = $week_date->toWeekStart()->toRequest();
			// add the date to the extras arrays
			switch ($layout) {
				case 'month':
				case 'flat':
					$print_extra['date'] = $month_date;
					$ical_extra['date']  = $month_date;
					break;
				case 'week':
					$print_extra['date'] = $week_date;
					$ical_extra['date']  = $week_date;
					break;
				default:
					$print_extra['date'] = $date;
					$ical_extra['date']  = $date;
					break;
			}
		}
		
		// build the buttons first
		foreach (array('add','month','week','day','flat','categories','search','ical','print') as $name) {
			$params = array();
			// switch based on button
			switch ($name) {
				case 'add':
					$params['href'] = JCalProHelperUrl::task('event.add');
					break;
				case 'month':
				case 'flat':
					$params['href'] = JCalProHelperUrl::events($month_date, $name);
					break;
				case 'week':
					$params['href'] = JCalProHelperUrl::events($week_date, $name);
					break;
				case 'day':
					$params['href'] = JCalProHelperUrl::events($date, $name);
					break;
				case 'categories':
					$params['href'] = JCalProHelperUrl::view('categories');
					break;
				case 'search':
					$params['href'] = JCalProHelperUrl::view('search');
					break;
				case 'ical':
					if (0 == (int) JCalPro::config('legacy_ical', 1)) {
						$params['href'] = JCalProHelperUrl::events('', 'all', true, $ical_extra);
					}
					else {
						$params['href'] = JCalProHelperUrl::page($ical_extra);
					}
					$params['class'] = array('noajax');
					break;
				case 'print':
					$params['href']  = JCalProHelperUrl::page($print_extra, array('format'));
					$params['class'] = array('noajax');
					$params['attr'] = array(
						'rel'     => "nofollow"
					,	'onclick' => "jclPrintWindow=window.open('" . JCalProHelperFilter::escape_js($params['href']) . "','jclPrintWindow','toolbar=no,location=no,directories=no,status=no,menubar=yes,scrollbars=yes,resizable=yes,width=800,height=600'); return false;"
					,	'target'  => "_blank"
					);
					break;
			}
			
			// create the button
			JCalProHelperToolbar::addButton($name, $params);
		}
		
		// add extra buttons
		if (!empty($extra) && is_array($extra)) {
			foreach ($extra as $ename => $ebtn) {
				JCalProHelperToolbar::addButton($ename, $ebtn);
			}
		}
		
		// use ACLs to see if the user is allowed to create new events
		if (!JCalPro::canAddEvents() || !in_array($view, array('', 'events', 'event', 'location'))) {
			JCalProHelperToolbar::deleteButton('add');
		}
		
		// unset ical if we're not in the event view
		// or of we're in admin layout
		// or if configured to disable it
		if (0 == (int) JCalPro::config('enable_ical_export', 1) || !in_array($view, array('', 'event', 'events', 'category')) || 'admin' == $layout) {
			JCalProHelperToolbar::deleteButton('ical');
		}
		
		// unset print button in search view or admin layout
		if ('search' == $view || 'admin' == $layout) {
			JCalProHelperToolbar::deleteButton('print');
		}
		
		// unset any disabled view buttons
		foreach (array('month', 'flat', 'week', 'day', 'categories', 'search') as $event_view) {
			if (0 == (int) JCalPro::config("{$event_view}_view", 1)) {
				JCalProHelperToolbar::deleteButton($event_view);
			}
		}
		
		return JCalProHelperToolbar::getButtons();
	}
	
	public static function filters($attributes = array()) {
		$app    = JFactory::getApplication();
		$view   = $app->input->get('view', '', 'cmd');
		$layout = $app->input->get('layout', '', 'cmd');
		$opts   = self::calendarlistoptions();
		$html   = array();
		// don't show if we have too few categories (or we're in a categories view)
		if (2 < count($opts) && !preg_match('/^categor/', $view)) {
			// start the url
			$url = array();
			// add the view
			$url['view'] = ('event' == $view ? 'events' : $view);
			// if we have a layout, add it
			// but only to events view
			if ('events' == $view && (!empty($layout) || 'default' != $layout)) {
				$url['layout'] = $layout;
			}
			$url['Itemid'] = $app->input->get('Itemid', JCalProHelperUrl::findItemid(), 'uint');
			// change the options array so the values contain the whole sef url
			foreach ($opts as &$opt) {
				// using straight variable call as J!3.x doesn't seem to use JObject on these options anymore
				$opt->value = JCalProHelperUrl::toFull(JCalProHelperUrl::_(array_merge($url, array('filter_catid' => $opt->value))));
			}
			// selected value
			$selected = JCalProHelperUrl::toFull(JCalProHelperUrl::_(array_merge($url, array('filter_catid' => $app->getUserStateFromRequest('com_jcalpro.events.filter.catid', 'filter_catid', '')))));
			// build class attribute for the div
			$attr = '';
			if (!empty($attributes)) {
				foreach ($attributes as $key => $val) {
					$attr .= ' ' . JCalProHelperFilter::escape($key) . '="' . JCalProHelperFilter::escape($val) . '"';
				}
			}
			// start cal row
			$html[] = '<div class="jcl_toolbar_catselect"' . $attr . '>';
			// start calendar select form
			$html[] = '<form name="calendar_selector" method="get" action="'.JRoute::_('index.php').'">';
			// calendar select
			$html[] = JHtml::_('select.genericlist', $opts, 'catid', 'class="listbox" onchange="document.location.href=$(this).getElement(\':selected\').value;"', 'value', 'text', $selected);
			// if no script, hide this form
			$html[] = '<noscript><style>.jcl_toolbar_catselect{display:none}</style></noscript>';
			// end form
			$html[] = '</form>';
			// end cal row
			$html[] = '</div>';
		}
		
		return implode("\n", $html);
	}
	
	/**
	 * returns a table filled with menu buttons for JCalPro header
	 * 
	 * @param  array   $extra
	 * 
	 * @return string
	 * 
	 * @deprecated
	 */
	public static function mainmenu($extra = array()) {
		
		// if the top navigation bar is disabled, return an empty string here
		if (0 == (int) JCalPro::config('show_top_navigation_bar', 1)) {
			return '';
		}
		
		// our html collection
		$html = array();
		
		// start the toolbar
		$html[] = '<div class="jcl_toolbar">';
		
		// start the button container
		$html[] = '<div class="jcl_toolbar_buttons">';
		
		$buttons = self::menu($extra);
		
		// start adding buttons
		foreach ($buttons as $bname => $battr) {
			// build the class
			$class = implode(' ', $battr['class']);
			$attr  = '';
			if (!empty($battr['attr'])) {
				foreach ($battr['attr'] as $att => $val) {
					$attr .= ' ' . JCalProHelperFilter::escape($att) . '="' . JCalProHelperFilter::escape($val) . '"';
				}
			}
			$html[] = "<a title=\"{$battr['name']}\" class=\"$class\" href=\"{$battr['href']}\"$attr>{$battr['title']}</a>";
		}
		
		// end the row
		$html[] = '</div>';
		
		$html[] = self::filters();
		
		// end the toolbar
		$html[] = '</div>';
		
		return implode("\n", $html);
	}
	
	/**
	 * creates an add event link
	 * we use this instead of a NORMAL link because we may have to select a category before continuing
	 * 
	 * @param unknown_type $contents
	 */
	public static function addlink($contents, $class = '', $extra = array()) {
		$class = empty($class) ? '' : 'class="' . $class . '" ';
		return '<a '.$class.'href="' . JCalProHelperUrl::task('event.add', true, $extra) . '">' . $contents . '</a>';
	}
	
	/**
	 * renders a form field
	 * 
	 * @param unknown_type $field
	 * @param unknown_type $data
	 * @param unknown_type $isHtml
	 * 
	 * @return string
	 */
	public static function formfieldvalue($field, $data, $isHtml = true) {
		if (empty($data)) {
			return '';
		}
		
		$filter = JFilterInput::getInstance();
		// different fields will render differently
		switch ($field->type) {
			
			// link types
			case 'url':
				$data = $filter->clean($data);
				// make sure we're accessing an external resource
				if (!preg_match('/^https?\:\/{2}/', $data)) {
					$data = "http://$data";
				}
				if (!$isHtml) {
					return $data;
				}
				return '<a href="' . $data . '" target="_blank">' . $data . '</a>';
			case 'email':
				$data = $filter->clean($data);
				if (!$isHtml) {
					return $data;
				}
				$link = '<a href="mailto:' . $data . '" target="_blank">' . $data . '</a>';
				// TODO: run cloak plugin ;)
				// return the link
				return $link;
			
			// array types
			// these include items that COULD be arrays but may not be
			case 'checkboxes':
			case 'groupedlist':
			case 'list':
			case 'radio':
				// make sure we have options in our params
				if (!is_array($field->params)
				|| !array_key_exists('opts', $field->params)
				|| empty($field->params['opts'])
				|| !is_array($field->params['opts'])
				) {
					return '';
				}
				
				if (!is_array($data) || 1 == count($data)) {
					$value = array_search((string) (is_array($data) ? $data[0] : $data), $field->params['opts']);
					if (empty($value)) {
						return '';
					}
					return $filter->clean((string) $value);
				}
				else {
					$html = array();
					$html[] = $isHtml ? '<ul>' : "";
					foreach ($data as $datum) {
						$value = array_search((string) $datum, $field->params['opts']);
						$value = $filter->clean((string) $value);
						$html[] = $isHtml ? "<li>$value</li>" : " * $value";
					}
					$html[] = $isHtml ? '</ul>' : '';
					return implode("\n", $html);
				}
				
			// integer is a special case
			case 'integer':
				
				if (!is_array($data) || 1 == count($data)) {
					return $filter->clean((int) $data);
				}
				else {
					$html = array();
					$html[] = $isHtml ? '<ul>' : '';
					foreach ($data as $datum) {
						$datum = (int) $datum;
						$html[] = $isHtml ? "<li>$datum</li>" : " * $datum";
					}
					$html[] = $isHtml ? '</ul>' : '';
					return implode("\n", $html);
				}
			
			// images
			case 'jcalpromedia':
			case 'media':
				$url = JCalProHelperUrl::toFull($filter->clean((string) $data));
				if (!$isHtml) {
					return $url;
				}
				JCalProBaseModel::addIncludePath(JCalProHelperPath::admin('models'));
				$model = JCalPro::getModelInstance('Media', 'JCalProModel');
				if ($model->isImage($url)) {
					return '<img src="' . $url . '" />';
				}
				else {
					return '<a href="' . $url . '">' . basename(preg_replace('/^https?:\//', '', $url)) . '</a>';
				}
			
			// text types
			case 'tel':
			case 'text':
			case 'textarea':
			// unknown type
			default:
				return $filter->clean((string) $data);
		}
	}
	
	/**
	 * standard JCal Pro copyright footer
	 * 
	 * @param $layout
	 */
	public static function footer($layout) {
		if (1 == (int) JCalPro::config('disable_footer', 0)) {
			return '';
		}
		
		if ('event' == JFactory::getApplication()->input->get('view')) {
			return '';
		}
		
		// This used to contain a link that was built using two strings
		// now the link is gone and only the text remains
		// TODO change language files to reflect this change
		$signature = JText::_('COM_JCALPRO_SIGNATURE_NAME');
		$raw = JText::_('COM_JCALPRO_SIGNATURE');
		
		if (strpos(" $raw", "%s")) {
			$signature = sprintf($raw, $signature);
		}
		else {
			$signature = "$raw $signature";
		}
		
		$d1 = JCalDate::_();
		$d2 = clone $d1;
		if (2012 < $d1->year()) {
			$d2->toTheDayMyLifeChanged()->toYear($d1->year());
			if ($d2->month() == $d1->month() && $d2->day() == $d1->day() && $d2->hour() == $d1->hour() && $d2->minute() == $d1->minute()) JFactory::getApplication()->enqueuemessage('Happy Birthday Ӕthan!');
		}
		
		return '<div class="atomic powered_by">'.$signature.'</div>';
	}
}
