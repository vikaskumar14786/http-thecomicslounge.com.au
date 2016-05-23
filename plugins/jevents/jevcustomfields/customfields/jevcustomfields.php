<?php
/**
 * copyright (C) 2008 GWE Systems Ltd - All rights reserved
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
* Location Table class
*
*/
class JTableJevcustomfields extends JTable
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	var $id = null;

	/**
	 * evdet_id - Event detail id
	 * @var int
	 */
	var $evdet_id = 0;

	/**
	 * name = custom field name
	 * 
	 * @var string
	 */
	var $name = "";

	/**
	 * value  = custom field value
	 * 
	 * @var string
	 */
	var $value = "";

	
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct() {
		$db = JFactory::getDBO();
		parent::__construct('#__jev_customfields', 'id', $db);
	}

}
