<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Imagegallery
 * @author     vikaskumar <vikaskumar14786@gmail.com>
 * @copyright  Copyright (C) 2016. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::register('ImagegalleryFrontendHelper', JPATH_COMPONENT . '/helpers/imagegallery.php');

// Execute the task.
$controller = JControllerLegacy::getInstance('Imagegallery');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
