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

jimport('joomla.application.component.controllerform');

/**
 * Gallerycategory controller class.
 *
 * @since  1.6
 */
class ImagegallerycategoryControllerGallerycategory extends JControllerForm
{
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'gallerycategorys';
		parent::__construct();
	}
}
