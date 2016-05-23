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

jimport('joomla.application.component.controllerform');

/**
 * Voucher controller class.
 *
 * @since  1.6
 */
class VoucherControllerVoucher extends JControllerForm
{
	/**
	 * Constructor
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->view_list = 'vouchers';
		parent::__construct();
	}
}
