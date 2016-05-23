<?php
/**
 * @package		JCalPro
 * @subpackage	plg_editors-xtd_jcalpro

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
JLoader::register('JCalPro', JPATH_ADMINISTRATOR.'/components/com_jcalpro/helpers/jcalpro.php');
JCalPro::registerHelper('url');

class plgButtonJCalPro extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @access      protected
	 * @param       object  $subject The object to observe
	 * @param       array   $config  An array that holds the plugin configuration
	 */
	public function __construct(& $subject, $config) {
		parent::__construct($subject, $config);
		JCalPro::language('plg_editors-xtd_jcalpro.sys', JPATH_ADMINISTRATOR);
	}
	
	/**
	 * Display the button
	 *
	 * @return JObject
	 */
	public function onDisplay($name) {
		$app = JFactory::getApplication();
		
		/**
		 * Javascript to insert the link
		 * View element calls jInsertEvent when an article is clicked
		 * jInsertEvent creates the link tag, sends it to the editor,
		 * and closes the select frame.
		 */
		
		$js = "
		function jInsertEvent(title, link, start_date, end_date, description, itemid, add_title, add_link, add_start_date, add_end_date, add_description) {
			var tag = '';
			if (1 == add_link) {
				if (0 != itemid) link = link.replace(/Itemid=[0-9]+/, 'Itemid=' + itemid);
				tag += '<a href=\"' + link + '\">' + title + '</a>';
			}
			else if (1 == add_title) {
				tag += title;
			}
			if (1 == add_start_date) tag += (0 != tag.length ? '<br />' : '') + start_date;
			if (1 == add_end_date) tag += (0 != tag.length ? '<br />' : '') + end_date;
			if (1 == add_description) tag += (0 != tag.length ? '<br />' : '') + description;
			jInsertEditorText(tag, '".$name."');
			SqueezeBox.close();
		}";
		
		$css = '.button2-left .jcalpro {background: url(' . JCalProHelperUrl::media(true) . '/plugins/editors-xtd/jcalpro/images/button.png) no-repeat scroll 100% 0 transparent;}';
		
		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration($js);
		$doc->addStyleDeclaration($css);
		
		$link = 'index.php?option=com_jcalpro&amp;view=events&amp;layout=button&amp;tmpl=component&amp;function=jInsertEvent&amp;fieldid='.$name;
		
		JHtml::_('behavior.modal');
		
		$button = new JObject;
		$button->set('modal', true);
		$button->set('link', $link);
		$button->set('text', JText::_('PLG_EDITORS-XTD_JCALPRO_BUTTON'));
		$button->set('name', 'jcalpro');
		$button->set('options', "{handler: 'iframe', size: {x: 800, y: 400}}");
		
		return $button;
	}
}
