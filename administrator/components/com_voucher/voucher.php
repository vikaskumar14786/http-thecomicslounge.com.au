<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Voucher
 * @author     vikas Kumar <vikaskumar14786@gmail.com>
 * @copyright  2016 vikaskumar
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_voucher'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependancies
jimport('joomla.application.component.controller');

$controller = JControllerLegacy::getInstance('Voucher');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
