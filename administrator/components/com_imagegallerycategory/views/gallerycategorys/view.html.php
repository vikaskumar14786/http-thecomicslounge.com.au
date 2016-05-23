<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Imagegallerycategory
 * @author     vikas Kumar <vikaskumar14786@gmail.com>
 * @copyright  Copyright (C) 2016. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View class for a list of Imagegallerycategory.
 *
 * @since  1.6
 */
class ImagegallerycategoryViewGallerycategorys extends JViewLegacy
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		ImagegallerycategoryHelper::addSubmenu('gallerycategorys');

		$this->addToolbar();

		$this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	protected function addToolbar()
	{
		require_once JPATH_COMPONENT . '/helpers/imagegallerycategory.php';

		$state = $this->get('State');
		$canDo = ImagegallerycategoryHelper::getActions($state->get('filter.category_id'));

		JToolBarHelper::title(JText::_('COM_IMAGEGALLERYCATEGORY_TITLE_GALLERYCATEGORYS'), 'gallerycategorys.png');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/views/gallerycategory';

		if (file_exists($formPath))
		{
			if ($canDo->get('core.create'))
			{
				JToolBarHelper::addNew('gallerycategory.add', 'JTOOLBAR_NEW');
				JToolbarHelper::custom('gallerycategorys.duplicate', 'copy.png', 'copy_f2.png', 'JTOOLBAR_DUPLICATE', true);
			}

			if ($canDo->get('core.edit') && isset($this->items[0]))
			{
				JToolBarHelper::editList('gallerycategory.edit', 'JTOOLBAR_EDIT');
			}
		}

		if ($canDo->get('core.edit.state'))
		{
			if (isset($this->items[0]->state))
			{
				JToolBarHelper::divider();
				JToolBarHelper::custom('gallerycategorys.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::custom('gallerycategorys.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
			}
			elseif (isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				JToolBarHelper::deleteList('', 'gallerycategorys.delete', 'JTOOLBAR_DELETE');
			}

			if (isset($this->items[0]->state))
			{
				JToolBarHelper::divider();
				JToolBarHelper::archiveList('gallerycategorys.archive', 'JTOOLBAR_ARCHIVE');
			}

			if (isset($this->items[0]->checked_out))
			{
				JToolBarHelper::custom('gallerycategorys.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
			}
		}

		// Show trash and delete for components that uses the state field
		if (isset($this->items[0]->state))
		{
			if ($state->get('filter.state') == -2 && $canDo->get('core.delete'))
			{
				JToolBarHelper::deleteList('', 'gallerycategorys.delete', 'JTOOLBAR_EMPTY_TRASH');
				JToolBarHelper::divider();
			}
			elseif ($canDo->get('core.edit.state'))
			{
				JToolBarHelper::trash('gallerycategorys.trash', 'JTOOLBAR_TRASH');
				JToolBarHelper::divider();
			}
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_imagegallerycategory');
		}

		// Set sidebar action - New in 3.0
		JHtmlSidebar::setAction('index.php?option=com_imagegallerycategory&view=gallerycategorys');

		$this->extra_sidebar = '';
		JHtmlSidebar::addFilter(

			JText::_('JOPTION_SELECT_PUBLISHED'),

			'filter_published',

			JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), "value", "text", $this->state->get('filter.state'), true)

		);
	}

	/**
	 * Method to order fields 
	 *
	 * @return void 
	 */
	protected function getSortFields()
	{
		return array(
			'a.`id`' => JText::_('JGRID_HEADING_ID'),
			'a.`ordering`' => JText::_('JGRID_HEADING_ORDERING'),
			'a.`state`' => JText::_('JSTATUS'),
			'a.`gallery_name`' => JText::_('COM_IMAGEGALLERYCATEGORY_GALLERYCATEGORYS_GALLERY_NAME'),
			'a.`coverphoto`' => JText::_('COM_IMAGEGALLERYCATEGORY_GALLERYCATEGORYS_COVERPHOTO'),
			'a.`home`' => JText::_('COM_IMAGEGALLERYCATEGORY_GALLERYCATEGORYS_HOME'),
		);
	}
}
