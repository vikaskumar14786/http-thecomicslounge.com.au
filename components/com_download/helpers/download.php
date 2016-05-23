<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Download
 * @author     vikaskumar <vikaskumar14786@gmail.com>
 * @copyright  vikaskumar
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class DownloadFrontendHelper
 *
 * @since  1.6
 */
class DownloadFrontendHelper
{
	/**
	 * Get an instance of the named model
	 *
	 * @param   string  $name  Model name
	 *
	 * @return null|object
	 */
	public static function getModel($name)
	{
		$model = null;

		// If the file exists, let's
		if (file_exists(JPATH_SITE . '/components/com_download/models/' . strtolower($name) . '.php'))
		{
			$model = JModelLegacy::getInstance($name, 'DownloadModel');
		}

		return $model;
	}
}
