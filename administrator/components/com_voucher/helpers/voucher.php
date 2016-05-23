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

/**
 * Voucher helper.
 *
 * @since  1.6
 */
class VoucherHelper
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
			JText::_('COM_VOUCHER_TITLE_VOUCHERS'),
			'index.php?option=com_voucher&view=vouchers',
			$vName == 'vouchers'
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

		$assetName = 'com_voucher';

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
