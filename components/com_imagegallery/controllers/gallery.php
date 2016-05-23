<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Imagegallery
 * @author     vikaskumar <vikaskumar14786@gmail.com>
 * @copyright  Copyright (C) 2016. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

require_once JPATH_COMPONENT . '/controller.php';

/**
 * Gallery list controller class.
 *
 * @since  1.6
 */
class ImagegalleryControllerGallery extends ImagegalleryController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional
	 * @param   array   $config  Configuration array for model. Optional
	 *
	 * @return object	The model
	 *
	 * @since	1.6
	 */
	public function &getModel($name = 'Gallery', $prefix = 'ImagegalleryModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}
}
