<?php
/**
 * copyright (C) 2008-2015 GWE Systems Ltd - All rights reserved
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

include_once(dirname(__FILE__)."/field.php");

/**
* Template Field class
*
*/
class TableJevrcbfield extends TableField
{

	/**
	* Overloaded bind function
	*
	*/
	public function bind($array, $ignore=array(), $fieldid="")
	{
		$success = parent::bind($array, $ignore, $fieldid);
		return $success;
	}

}
