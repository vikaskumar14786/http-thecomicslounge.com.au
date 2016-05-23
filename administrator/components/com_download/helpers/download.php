<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Download
 * @author     vikaskumar <vikaskumar14786@gmail.com>
 * @copyright  vikaskumar
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Download helper.
 *
 * @since  1.6
 */
class DownloadHelpersDownload
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

		$assetName = 'com_download';

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
