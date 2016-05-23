<?php
defined('_JEXEC') or die('Restricted access');

/**
 * Payment plugin for EWay
 *
 * @version 1.0
 * @subpackage Plugins - payment
 * @copyright Copyright (C) 2013 oneforallsoft - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 * http://www.virtuemart.com.au
 * @author virtuemart.com.au
 *
 */
if (!class_exists( 'VmConfig' )) require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart'.DS.'helpers'.DS.'config.php');
if (!class_exists('ShopFunctions'))
    require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'shopfunctions.php');

/**
 * @copyright	Copyright (C) 2009 Open Source Matters. All rights reserved.
 * @license	GNU/GPL
 */
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Renders a multiple item select element
 *
 */
JFormHelper::loadFieldClass('list');
jimport('joomla.form.formfield');

class JFormFieldEwayCreditCards extends JFormFieldList {

    /**
     * Element name
     *
     * @access	protected
     * @var		string
     */

    protected $type = 'ewaycreditcards';

	protected function getOptions() {
		return parent::getOptions();
	}

}
