<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Voucher
 * @author     vikas Kumar <vikaskumar14786@gmail.com>
 * @copyright  2016 vikaskumar
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::register('VoucherFrontendHelper', JPATH_COMPONENT . '/helpers/voucher.php');

// Execute the task.
$controller = JControllerLegacy::getInstance('Voucher');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
