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

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_download'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('Download', JPATH_COMPONENT_ADMINISTRATOR);

$controller = JControllerLegacy::getInstance('Download');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
