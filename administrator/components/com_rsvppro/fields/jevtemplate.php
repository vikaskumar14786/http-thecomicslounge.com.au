<?php
/**
 * JEvents Locations Component for Joomla 1.5.x
 *
 * @version     $Id: jevboolean.php 1331 2010-10-19 12:35:49Z geraintedwards $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!

defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Field class for selecting default template
 *
 * @package		JEvents.fields
 */
class JFormFieldJEVTemplate extends JFormFieldList
{
	/**
	 * The form field type.s
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'JEVTemplate';
	
	/**
	 * Method to get the field options.
	 *
	 * @return	array	The field option objects.
	 * @since	1.6
	 */
	public function getOptions()
	{		
		
		$options = array ();
		$options[] = JHTML::_('select.option', 0, JText::_("JEV_NO_DEFAULT_TEMPLATE"));

		$db = JFactory::getDBO();
		$query = ' SELECT id, title FROM #__jev_rsvp_templates AS tmpl where tmpl.published = 1 and tmpl.global = 1 and tmpl.istemplate = 1 ORDER BY title asc';
		$db->setQuery($query);
		$templates = $db->loadObjectList();
		if (!$templates ){
			return $options;
		}

		foreach ($templates as $template) {
			$options[] = JHTML::_('select.option', $template->id, $template->title);
		}

		return $options;
		
	}
}
