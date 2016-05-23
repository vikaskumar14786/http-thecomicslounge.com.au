<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted Access' );

JLoader::register('RsvpForm', JPATH_ADMINISTRATOR. "/components/com_rsvppro/libraries/rsvpform.php");
jimport('joomla.application.component.modeladmin');

class TemplatesModelTemplate extends JModelAdmin
{
	/**
	 * Template id
	 *
	 * @var int
	 */
	var $_id = null;

	/**
	 * Template data
	 *
	 * @var array
	 */
	var $_data = null;

	/**
	 * Template data
	 *
	 * @var array
	 */
	var $_dataobject = null;

	/**
	 * Constructor
	 *
	 * @since 1.5
	 */
	function __construct()
	{
		parent::__construct();

		$array = JRequest::getVar('cid', array(0), '', 'array');
		$edit	= JRequest::getVar('edit',true);
		if($edit)
		$this->setId((int)$array[0]);
	}

	/**
	 * Method to set the template identifier
	 *
	 * @access	public
	 * @param	int Template identifier
	 */
	function setId($id)
	{
		// Set template id and wipe data
		$this->_id		= $id;
		$this->_data	= null;
		$this->_dataobject	= null;
	}

		/**
	 * Method to get a form object.
	 *
	 * @param	array	$data		Data for the form.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 *
	 * @return	mixed	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Prepare the data
		// Experiment in the use of JForm and template override for forms and fields
		JForm::addFormPath(JPATH_COMPONENT_ADMINISTRATOR . "/models/forms/");
		$template = JFactory::getApplication()->getTemplate();
		JForm::addFormPath(JPATH_THEMES."/$template/html/com_rsvppro/forms");

		$xpath = false;
		// leave form control blank since we want the fields as ev_id and not jform[ev_id]
		$form = $this->loadForm("rsvpprro.edit.template", 'template', array('control' => '', 'load_data' => false), false, $xpath);
		JForm::addFieldPath(JPATH_THEMES."/$template/html/com_rsvppro/fields");

		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to get a template
	 *
	 * @since 1.5
	 */
	function &getData()
	{
		// Load the template data
		if ($this->_loadData())
		{
			// Initialize some variables
			$user = JFactory::getUser();

		}
		else  $this->_initData();


		return $this->_dataobject;
	}

	/**
	 * Tests if template is checked out
	 *
	 * @access	public
	 * @param	int	A user id
	 * @return	boolean	True if checked out
	 * @since	1.5
	 */
	function isCheckedOut( $uid=0 )
	{
		if ($this->_loadData())
		{
			if ($uid) {
				return ($this->_data->checked_out && $this->_data->checked_out != $uid);
			} else {
				return $this->_data->checked_out;
			}
		}
	}

	/**
	 * Method to checkin/unlock the template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function checkin($pks = array() )
	{
		if ($this->_id)
		{
			$template =  $this->getTable();
			if(! $template->checkin($this->_id)) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}
		if (count($pks)>0){
			return parent::checkin($pks);
		}
		return false;
	}

	/**
	 * Method to checkout/lock the template
	 *
	 * @access	public
	 * @param	int	$uid	User ID of the user checking the article out
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function checkout($uid = null)
	{
		if ($this->_id)
		{
			// Make sure we have a user id to checkout the article with
			if (is_null($uid)) {
				$user	= JFactory::getUser();
				$uid	= $user->get('id');
			}
			// Lets get to it and checkout the thing...
			$template =  $this->getTable();
			if(!$template->checkout($uid, $this->_id)) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}

			return true;
		}
		return false;
	}

	/**
	 * Method to store the template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function store($data)
	{
		unset($this->template );
		$row = $this->getTable();
			
		// Bind the form fields to the template table
		if (!$row->bind($data)) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		// Make sure the template table is valid
		if (!$row->check()) {
			$this->setError($row->getError());
			return false;
		}

		// Store the template table to the database
		if (!$row->store()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		// attach a reference to I can refetch the saved row
		$this->template = $row;
		return true;
	}

	/**
	 * Method to store a copy of the template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function storecopy($data)
	{
		$row = $this->getTable();

		// Bind the form fields to the template table
		if (!$row->bindcopy($data)) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		// Make sure the template table is valid
		if (!$row->check()) {
			$this->setError($row->getError());
			return false;
		}

		// Store the template table to the database
		if (!$row->store()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		return true;
	}

	/**
	 * Method to remove a template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function delete(&$cid = array())
	{
		$result = false;

		if (count( $cid ))
		{
			JArrayHelper::toInteger($cid);
			
			// create archive before deleting
			foreach ($cid as $id){
				$row = $this->getTable("templatearchive", "JTable");
				if ($row->loadByTemplateId($id)){
					$row->store();
				}
			}
			
			$cids = implode( ',', $cid );
			$query = 'DELETE FROM #__jev_rsvp_templates'
			. ' WHERE id IN ( '.$cids.' )';
			$this->_db->setQuery( $query );
			if(!$this->_db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to (un)publish a template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	public function publish(&$cid = array(), $publish = 1)
	{
		$user 	= JFactory::getUser();

		if (count( $cid ))
		{
			JArrayHelper::toInteger($cid);
			$cids = implode( ',', $cid );

			$query = 'UPDATE #__jev_rsvp_templates'
			. ' SET published = '.(int) $publish
			. ' WHERE id IN ( '.$cids.' )'
			. ' AND ( checked_out = 0 OR ( checked_out = '.(int) $user->get('id').' ) )'
			;
			$this->_db->setQuery( $query );
			if (!$this->_db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to (un)globalise a template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function globalise($cid = array(), $global = 1)
	{

		if (count( $cid ))
		{
			JArrayHelper::toInteger($cid);
			$cids = implode( ',', $cid );

			$query = 'UPDATE #__jev_rsvp_templates'
			. ' SET global = '.(int) $global
			. ' WHERE id IN ( '.$cids.' )'
			;
			$this->_db->setQuery( $query );
			if (!$this->_db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to (un)lock a template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function lock($cid = array(), $locked = 1)
	{

		if (count( $cid ))
		{
			JArrayHelper::toInteger($cid);
			$cids = implode( ',', $cid );

			$query = 'UPDATE #__jev_rsvp_templates'
			. ' SET locked = '.(int) $locked
			. ' WHERE id IN ( '.$cids.' )'
			;
			$this->_db->setQuery( $query );
			if (!$this->_db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to (un)templatise a template
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function templatise($cid = array(), $global = 1)
	{

		if (count( $cid ))
		{
			JArrayHelper::toInteger($cid);
			$cids = implode( ',', $cid );

			$query = 'UPDATE #__jev_rsvp_templates'
			. ' SET istemplate = '.(int) $global
			. ' WHERE id IN ( '.$cids.' )'
			;
			$this->_db->setQuery( $query );
			if (!$this->_db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
		}

		return true;
	}

	/**
	 * Method to load content template data
	 *
	 * @access	private
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function _loadData()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_dataobject))
		{
			$query = 'SELECT w.* FROM #__jev_rsvp_templates AS w WHERE w.id = '.(int) $this->_id;
			$this->_db->setQuery($query);
			$this->_data = $this->_db->loadObject();

			// now attach the fields
			if ($this->_data){
				$this->_dataobject =  $this->getTable();
				$this->_dataobject->bind((array) $this->_data);

				$query = 'SELECT w.* FROM #__jev_rsvp_fields AS w WHERE w.template_id = '.(int) $this->_dataobject->id. " ORDER BY ordering ASC";
				$this->_db->setQuery($query);
				$fielddata = $this->_db->loadObjectList();
				foreach ($fielddata as $field) {
					$type= $field->type;
					include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/rsvppro.defines.php");
					jimport("joomla.html.parameter.element");
					jimport('joomla.filesystem.file');
					if (JFile::exists(JPATH_ADMINISTRATOR."/components/com_rsvppro/fields/$type.php")) {
						include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/fields/$type.php");
					}
					else {
						include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/customfields/$type.php");
					}
					// only need to load Scripts when editing templates!
					if (JRequest::getCmd("option")=="com_rsvppro" && (JRequest::getCmd('task', 'sessions.overview')=='templates.edit' || JRequest::getCmd('task', 'sessions.overview')=='templates.editcopy'  || JRequest::getCmd('task', 'sessions.overview')=='templates.translate')){
						$field->html=call_user_func(array("JFormField".ucfirst($type),"loadScript"),$field);
					}

					$field->default = $field->defaultvalue;
					$this->_dataobject->fields[$field->field_id]=$field;
					
				}
			}
			return (boolean) $this->_data;
		}
		return true;
	}

	/**
	 * Method to initialise the template data
	 *
	 * @access	private
	 * @return	boolean	True on success
	 * @since	1.5
	 */
	function _initData()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_dataobject))
		{
			$this->_dataobject = $this->getTable();

			return (boolean) $this->_data;
		}
		return true;
	}

		/**
	 * Get the params for the currency variables etc.
	 */
	function &getParams($template, $translation=null)
	{
		static $instance;

		if ($instance == null)
		{
			$component	= RSVP_COM_COMPONENT;

			$path	= JPATH_ADMINISTRATOR.'/'.'components/com_rsvppro/templates.xml';

			// Use our own class to add more functionality!
			$instance = RsvpForm::getInstance("com_rsvppro.template", $path, array('control' => 'params', 'load_data' => true), true, "/form");
			
			$templateparams = $template->params;
			if (is_string($templateparams) && strlen(trim($templateparams))>2){
				$templateparams = new JRegistry($templateparams);
			}
			else if (is_string($templateparams) && trim($templateparams)==""){
				$templateparams = JComponentHelper::getParams($component)->toArray();
			}
			
			// Now load the payment engines
			$plugins = JPluginHelper::getPlugin("rsvppro");
			foreach ($plugins as $plugin){
				if (!$plugin->params) continue;
				$pluginpath =JPATH_SITE."/plugins/rsvppro/".$plugin->name."/".$plugin->name.".xml" ;
								
				$instance->loadfile($pluginpath,false,"/extension/template/form");							
				$lang = JFactory::getLanguage();
				$lang->load("plg_rsvppro_".$plugin->name, JPATH_ADMINISTRATOR);
				$pluginparams = $plugin->params;
				if (is_string($pluginparams) && strlen(trim($pluginparams))>2){
					$pluginparams = new JRegistry($pluginparams);
				}					
				$instance->bind($pluginparams);
			}
			$instance->bind($templateparams);
			
		}
		return $instance;
	}

	/**
	 * Either returns 0 if we don't need to lock any existing 
	 * @param type $template
	 * @return type int
	 */
	function hasAttendeesToLock($template) {
		return 0;
		// TODO locked attendees - test properly and limit scope e.g. can't handle capacity changes etc.
		
		if ($template->istemplate ){
			// this is a customisation of a template - this can only happen on an event specific basis
			if (JRequest::getInt("customise")){
				// in this case we ONLY lock existing attendees if this is an existing event
				if (JRequest::getInt("evid")>0){
					return true;
				}
				return false;
			}
			// this a template that is being changed
			else {
				// we offer to lock if its an existing template
				if ($template->id>0){
					return true;
				}
				return false;				
			}			
		}
		else {
			// this is a customisation of a non-template or a new template being edited as a raw template
			// in this case we ONLY lock existing attendees if this is an existing event
			// we offer to lock if its an existing template
			if ($template->id>0){
				return true;
			}
			return false;
		}
	}

	public function getTranslateForm($data = array(), $loadData = true)
	{
		// Prepare the data
		// Experiment in the use of JForm and template override for forms and fields
		JForm::addFormPath(JPATH_COMPONENT_ADMINISTRATOR . "/models/forms/");
		$template = JFactory::getApplication()->getTemplate();
		JForm::addFormPath(JPATH_THEMES."/$template/html/com_rsvppro/forms");

		$xpath = false;
		// leave form control blank since we want the fields as ev_id and not jform[ev_id]
		$form = $this->loadForm("rsvppro.translate.template", 'translate', array('control' => '', 'load_data' => false), false, $xpath);
		JForm::addFieldPath(JPATH_THEMES."/$template/html/com_rsvppro/fields");

		if (empty($form)) {
			return false;
		}

		return $form;
	}


	public function getTranslation($template_id=0, $lang="")
	{
		if ($this->_id==0){
			$this->setId(JRequest::getInt("template_id",$template_id));
		}
		$this->template_id = JRequest::getInt("template_id",$template_id);
		$data = $this->getData();
		
		$db = JFactory::getDbo();
		$lang = JRequest::getString("lang", $lang);
		$db->setQuery("SELECT * FROM #__jev_rsvp_template_translation where template_id = ".$this->template_id . " AND language = ". $db->quote($lang));
		$tempdata = $db->loadAssoc();
		if (is_null($this->_data)){
			$this->_data = new stdClass();
		}
		if ($tempdata){
			foreach ($tempdata as $key => $val) {
				if (is_string($val) && $val != ""){
					$this->_data->$key = $val;
				}
			}

			$this->_data->translation_id = $tempdata["translation_id"];
		}
		else {
			$this->_data->translation_id = 0;
		}
		$this->_data->translationLanguage = $lang;


		// now attach the field translations
		$this->_dataobject =  $this->getTable();
		$this->_dataobject->bind((array) $this->_data);

		$this->_dataobject->translation_id = $this->_data->translation_id ;
		$this->_dataobject->translationLanguage = $this->_data->translationLanguage;

		$query = 'SELECT w.* FROM #__jev_rsvp_fields AS w WHERE w.template_id = '.(int) $this->_dataobject->id. " ORDER BY ordering ASC";
		$this->_db->setQuery($query);
		$fielddata = $this->_db->loadObjectList();

		if ($fielddata) {
			$query = 'SELECT w.* FROM #__jev_rsvp_fields_translation AS w WHERE w.template_id = '.(int) $this->_dataobject->id. " AND w.language = ". $db->quote($lang);
			$this->_db->setQuery($query);
			$fieldTranslationData = $this->_db->loadObjectList("field_id");
			if ($fieldTranslationData) {
				foreach ($fielddata as &$field) {
					if (array_key_exists($field->field_id, $fieldTranslationData)){
						foreach (get_object_vars($fieldTranslationData[$field->field_id]) as $key => $val){
							if ($key == "options"  && $val !=""){
								$translatedoptions = json_decode($val);
								$rawoptions = json_decode($field->$key);
								foreach (get_object_vars($translatedoptions) as $optkey => $optval){
									if (is_array($optval)) {
										foreach ($optval as $optvalkey => $optvalval){
											$rawoption =& $rawoptions->$optkey;
											if (!is_numeric($rawoption[$optvalkey]) &&  $rawoption[$optvalkey] != "" && $optvalval !=""){
												 $rawoption[$optvalkey] = $optvalval;
											}
										}
									}
									else if (is_string($optval) & !is_numeric($optval) && $rawoptions->$optkey!="") {
										$rawoptions->$optkey = $optval;
									}
									else {
										$x  = 1;
									}
								}
								$field->$key = json_encode($rawoptions);
							}
							else if ($key == "params" && $val !=""){
								$translatedparams = json_decode($val);
								$rawparams = json_decode($field->$key);
								foreach (get_object_vars($translatedparams) as $paramkey => $paramval){
									if (is_array($paramval)) {
										foreach ($paramval as $paramvalkey => $paramvalval){
											$rawparam =& $rawparams->$paramkey;
											if (!is_numeric($rawparam[$paramvalkey]) &&  $rawparam[$paramvalkey] != "" && $paramvalval !=""){
												 $rawparam[$paramvalkey] = $paramvalval;
											}
										}
									}
									else if (is_string($paramval) & !is_numeric($paramval) && $rawparams->$paramkey!="") {
										$rawparams->$paramkey = $paramval;
									}
									else {
										$x  = 1;
									}
								}
								$field->$key = json_encode($rawparams);
							}
							else if (isset($field->$key) && is_string($val) && !is_numeric($val) && $val!= ""){
								$field->$key = $val;
							}
						}
					}
				}
			}

			foreach ($fielddata as $field) {
				$type= $field->type;
				include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/rsvppro.defines.php");
				jimport("joomla.html.parameter.element");
				jimport('joomla.filesystem.file');
				if (JFile::exists(JPATH_ADMINISTRATOR."/components/com_rsvppro/fields/$type.php")) {
					include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/fields/$type.php");
				}
				else {
					include_once(JPATH_ADMINISTRATOR."/components/com_rsvppro/customfields/$type.php");
				}
				// only need to load Scripts when editing templates!
				if (JRequest::getCmd("option")=="com_rsvppro" && (JRequest::getCmd('task', 'sessions.overview')=='templates.edit' || JRequest::getCmd('task', 'sessions.overview')=='templates.editcopy'  || JRequest::getCmd('task', 'sessions.overview')=='templates.translate')){
					if (method_exists("JFormField" . ucfirst($type), "loadTranslationScript")){
						$field->html=call_user_func(array("JFormField" . ucfirst($type), "loadTranslationScript"), $field);
					}
					else {
						$field->html=call_user_func(array("JFormField" . ucfirst($type), "loadScript"), $field);
					}
				}

				$field->default = $field->defaultvalue;
				$this->_dataobject->fields[$field->field_id]=$field;
			}
		}

		return $this->_dataobject;
	}

	public function saveTranslation()
	{
		
		$data = JRequest::get('request', JREQUEST_ALLOWHTML);

		unset($this->template );
		$row = $this->getTable("templatetranslation");

		// Bind the form fields to the template table
		if (!$row->bind($data)) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		// Make sure the template table is valid
		if (!$row->check()) {
			$this->setError($row->getError());
			return false;
		}
		// Store the template table to the database
		if (!$row->storeTranslation()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		return true;
	}

	public function deleteTranslation()
	{

		$db = JFactory::getDbo();
		$translationid = JRequest::getInt("translation_id", -1);
		$templateid = JRequest::getInt("template_id", -1 );
		$language = JRequest::getCmd("lang", "xxx" );
		
		$db->setQuery("DELETE FROM #__jev_rsvp_fields_translation where template_id=".$templateid." and language=".$db->quote($language));
		$db->execute();

		$row = $this->getTable("templatetranslation");
		$row->delete($translationid);
		
		return true;
	}


}