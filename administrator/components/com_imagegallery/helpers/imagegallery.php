<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Imagegallery
 * @author     vikaskumar <vikaskumar14786@gmail.com>
 * @copyright  Copyright (C) 2016. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Imagegallery helper.
 *
 * @since  1.6
 */
class ImagegalleryHelper
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
			JText::_('COM_IMAGEGALLERY_TITLE_GALLERY'),
			'index.php?option=com_imagegallery&view=gallery',
			$vName == 'gallery'
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

		$assetName = 'com_imagegallery';

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
