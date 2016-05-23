<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Download
 * @author     vikaskumar <vikaskumar14786@gmail.com>
 * @copyright  vikaskumar
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('Download', JPATH_COMPONENT);

// Execute the task.
$controller = JControllerLegacy::getInstance('Download');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
