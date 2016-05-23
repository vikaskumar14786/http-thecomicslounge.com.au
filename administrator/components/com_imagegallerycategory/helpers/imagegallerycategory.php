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

/**
 * Imagegallerycategory helper.
 *
 * @since  1.6
 */
class ImagegallerycategoryHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  string
	 *
	 * @return void
	 */
	public static function addSubmenu($vName = '')
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_IMAGEGALLERYCATEGORY_TITLE_GALLERYCATEGORYS'),
			'index.php?option=com_imagegallerycategory&view=gallerycategorys',
			$vName == 'gallerycategorys'
		);

	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return    JObject
	 *
	 * @since    1.6
	 */
	public static function getActions()
	{
		$user   = JFactory::getUser();
		$result = new JObject;

		$assetName = 'com_imagegallerycategory';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}
}
