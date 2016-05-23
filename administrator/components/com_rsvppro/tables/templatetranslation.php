<?php
/**
 * copyright (C) 2008-2015 GWE Systems Ltd - All rights reserved
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
* Template Table class
*
*/
class TableTemplatetranslation extends JTable
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	var $translation_id = null;

	var $template_id = null;
	var $language = null;

	/**
	 * @var string
	 */
	var $title = null;

	/**
	 * @var string
	 */
	var $description = null;

	/**
	 * @var string
	 */
	var $params = "";

	/**
	 * @var string
	 */
	var $ticket = "";

	var $fields = array();
	
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 * @since 1.0
	 */
	function __construct() {
		$db = JFactory::getDBO();
		parent::__construct('#__jev_rsvp_template_translation', 'translation_id', $db);
	}

	/**
	* Overloaded bind functionm - only used when saving translation
	*
	* @acces public
	* @param array $hash named array
	* @return null|string	null is operation was satisfactory, otherwise returns an error
	* @see JTable:bind
	* @since 1.5
	*/
	public function bind($array, $ignore=array(), $fieldid="")
	{
		$success = parent::bind($array, $ignore);

		$template_id = $array["template_id"];
		$this->language = $lang = $array["lang"];

		$query = 'SELECT w.* FROM #__jev_rsvp_fields_translation AS w WHERE w.template_id = '.(int) $template_id. " AND w.language = ". $this->_db->quote($lang);
		$this->_db->setQuery($query);
		$fieldTranslationData = $this->_db->loadObjectList("field_id");
		
		if (array_key_exists('type',$array)){
			foreach ($array['type'] as $fieldid => $fieldtype) {
				$id = str_replace("field","",$fieldid);
				$tableclass = "Table".ucfirst($fieldtype);
				if (!class_exists($tableclass) && file_exists(dirname(__FILE__)."/field.".$fieldtype.".php")){
					include_once(dirname(__FILE__)."/field.".$fieldtype.".php");
					$field = new $tableclass();
				}
				else {
					include_once(dirname(__FILE__)."/field.php");
					$field = new TableField();
				}
				$field->bind($array,'', $fieldid);

				if (array_key_exists($id, $fieldTranslationData)) {
					$field->translation_id =  $fieldTranslationData[$id]->translation_id;
				}
				else {
					$field->translation_id =  0;
				}
				$this->fields[$fieldid] = $field;
			}
		}
		if (array_key_exists('params',$array) && is_array($array["params"])){
			$this->params = json_encode($array["params"]);
		}
		return $success;
	}

	/*
	 * overloaded load method that converts the params field
	 */
	function load( $keys = NULL, $reset = truel )
	{
		parent::load($keys);

		if ($this->params !="" ){
	//		$this->params = json_decode($this->params);
		}
	}

	/**
	 * Overloaded check method to ensure data integrity
	 *
	 * @access public
	 * @return boolean True on success
	 * @since 1.0
	 */
	function check()
	{
		return true;
	}

	function storeTranslation( $updateNulls=false ) {
		
		$success = parent::store($updateNulls);

		$ids = array(0);
		$oldids = array();
		foreach ($this->fields as &$field) {
			if (!isset($field->translation_id)){
				$field->translation_id= 0;
			}
			$success |= $field->storeTranslation($updateNulls);
		}
		unset($field);
	
		return $success;
	}

}
