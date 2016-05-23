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
class TableJevrlist extends TableField
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

	public function store($updateNulls = false)
	{
		// convert to html entities
		if ($this->options){
			$temp = json_decode($this->options);
			if ($temp){
				if ($temp->label) {
					foreach ($temp->label as $k => $v)
					{
						$temp->label[$k] = htmlspecialchars($v ,ENT_COMPAT | ENT_HTML401, "UTF-8", false);
					}
					$this->options= json_encode($temp );
				}
			}

		}
		
		return parent::store($updateNulls);
	}
}
