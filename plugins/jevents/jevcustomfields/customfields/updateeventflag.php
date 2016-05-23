<?php
/**
* @copyright	Copyright (C) 2008 GWE Systems Ltd. All rights reserved.
 * @license		By negoriation with author via http://www.gwesystems.com
*/
ini_set("display_errors",0);

list($usec, $sec) = explode(" ", microtime());
define('_SC_START', ((float)$usec + (float)$sec));

// Set flag that this is a parent file
define( '_JEXEC', 1 );
define( 'DS', DIRECTORY_SEPARATOR );
$x = realpath(dirname(__FILE__).DS ."..".DS. "..".DS. "..".DS) ;
if (!file_exists($x."/plugins")){
	$x = realpath(dirname(__FILE__).DS ."..".DS. "..".DS. "..".DS. "..".DS ) ;
}
if (!file_exists($x . DS . "plugins") && isset($_SERVER["SCRIPT_FILENAME"]))
{
	$x = dirname(dirname(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))));
}
define( 'JPATH_BASE', $x );

// create the mainframe object
$_REQUEST['tmpl'] = 'component';

// Create JSON data structure
$data = new stdClass();
$data->error = 0;
$data->result = "ERROR";
$data->user = "";

// Enforce referrer
if (!array_key_exists("HTTP_REFERER",$_SERVER) ){
	throwerror("There was an error");
}

$live_site = $_SERVER['HTTP_HOST'];
$ref_parts = parse_url($_SERVER["HTTP_REFERER"]);

if (!isset($ref_parts["host"]) || $ref_parts["host"] != $live_site ){
	throwerror("There was an error - missing host in referrer");
}


// Get JSON data
if (!array_key_exists("json",$_REQUEST)){
	throwerror("There was an error - no request data");
}
else {
	$requestData = $_REQUEST["json"];

	if (isset($requestData)){
		try {
			if (ini_get("magic_quotes_gpc")){
				$requestData= stripslashes($requestData);
			}

			$requestObject = json_decode($requestData, 0);
			if (!$requestObject){
				$requestObject = json_decode(utf8_encode($requestData), 0);
			}
		}
		catch (Exception $e) {
			throwerror("There was an exception");
		}

		if (!$requestObject){
			file_put_contents(dirname(__FILE__)."/cache/error.txt", var_export($requestData,true));
			throwerror("There was an error - no request object ");
		}
		else if ($requestObject->error){
			throwerror("There was an error - Request object error ".$requestObject->error);
		}
		else {

			try {
				$data = ProcessRequest($requestObject, $data);
			}
			catch (Exception $e){
				throwerror("There was an exception ".$e->getMessage());
			}
		}
	}
	else {
		throwerror("Invalid Input");
	}
}

header("Content-Type: application/x-javascript; charset=utf-8");

list ($usec,$sec) = explode(" ", microtime());
$time_end = (float)$usec + (float)$sec;
$data->timing = round($time_end - _SC_START,4);

// Must suppress any error messages
@ob_end_clean();
echo json_encode($data);

function ProcessRequest(&$requestObject, $returnData){

	define("REQUESTOBJECT",serialize($requestObject));
	define("RETURNDATA",serialize($returnData));

	require_once JPATH_BASE.DS.'includes'.DS.'defines.php';
	require_once JPATH_BASE.DS.'includes'.DS.'framework.php';

	$requestObject = unserialize(REQUESTOBJECT);
	$returnData = unserialize(RETURNDATA);

	ini_set("display_errors",0);

	global  $option, $mainframe;
	$option = "com_jevents";
	$client = "site";
	if (isset($requestObject->client) && in_array($requestObject->client,array("site","administrator"))){
		$client = $requestObject->client;
	}
	$mainframe = JFactory::getApplication($client);
	JFactory::getApplication()->initialise();

	if (!isset($requestObject->rpid) || $requestObject->rpid==0){
		throwerror("There was an error");
	}

	$user = JFactory::getUser();
	if ($user->id==0 || $user->id!=$requestObject->userid){
		throwerror("There was an error");
	}

	include_once(JPATH_SITE."/components/com_jevents/jevents.defines.php");

	$datamodel =new JEventsDataModel();
	$event = $datamodel->queryModel->listEventsById( $requestObject->rpid, 0,"icaldb");

	if (!$event || $event->rp_id()!= $requestObject->rpid)  throwerror("There was an error");

	$safeHtmlFilter =  JFilterInput::getInstance(null, null, 1, 1);
	$value = intval($requestObject->value);

	// If upgrading then add new columns - do all the tables at once
	$db = JFactory::getDBO();
	$sql = "SHOW COLUMNS FROM `#__jev_customfields`";
	$db->setQuery($sql);
	$cols = $db->loadObjectList();
	$uptodate = false;
	foreach ($cols as $col)
	{
		if ($col->Field == "rp_id")
		{
			$uptodate = true;
			break;
		}
	}
	if (!$uptodate)
	{
		$sql = "ALTER TABLE #__jev_customfields ADD COLUMN user_id int(11) NOT NULL default 0";
		$db->setQuery($sql);
		@$db->query();

		$sql = "ALTER TABLE `#__jev_customfields` ADD INDEX user_id (user_id)";
		$db->setQuery($sql);
		@$db->query();

		$sql = "ALTER TABLE #__jev_customfields ADD COLUMN rp_id int(11) NOT NULL default 0";
		$db->setQuery($sql);
		@$db->query();

		$sql = "ALTER TABLE `#__jev_customfields` ADD INDEX rp_id (rp_id)";
		$db->setQuery($sql);
		@$db->query();

		$sql = "ALTER TABLE `#__jev_customfields` ADD INDEX detval (evdet_id, value(10) )";
		$db->setQuery($sql);
		@$db->query();

	}

	// does the custom field exist already?
	$db = JFactory::getDBO();
	if ($requestObject->separaterepeats) {
		$sql = "SELECT * FROM #__jev_customfields where name=".$db->Quote($requestObject->field)." and evdet_id=".$event->_detail_id ." and user_id=".$user->id. " and value=".$requestObject->rpid;
	}
	else {
		$sql = "SELECT * FROM #__jev_customfields where name=".$db->Quote($requestObject->field)." and evdet_id=".$event->_detail_id ." and user_id=".$user->id;
	}
	$db->setQuery($sql);
	$field = $db->loadObject();
	$error = $db->getErrorMsg();
		
	if ($field){
		if ($requestObject->separaterepeats) {
			$sql = "DELETE FROM #__jev_customfields WHERE name=".$db->Quote($requestObject->field)." and evdet_id=".$event->_detail_id." and user_id=".$user->id. " and value=".$requestObject->rpid;
		}
		else {
			$sql = "DELETE FROM #__jev_customfields WHERE name=".$db->Quote($requestObject->field)." and evdet_id=".$event->_detail_id." and user_id=".$user->id;
		}
		$db->setQuery($sql);
		$success = $db->query();
		$value = 0;
	}
	else {
		// THIS IS A GOOD IDEA TO MAKE IT REPEAT SPECIFIC
		if ($requestObject->separaterepeats) {
			$sql = "INSERT INTO  #__jev_customfields (user_id, value, evdet_id, name ) VALUES(".$user->id.",".$requestObject->rpid.", ".intval($event->_detail_id).", ".$db->Quote($requestObject->field).")";
		}
		else {
			$sql = "INSERT INTO  #__jev_customfields (user_id, value, evdet_id, name ) VALUES(".$user->id.",1, ".intval($event->_detail_id).", ".$db->Quote($requestObject->field).")";
		}
		$db->setQuery($sql);
		$success = $db->query();
		$value = $user->id;
	}

	if (!$success) $returnData->error = 1;
	
	$returnData->result = $success;
	$returnData->newvalue = $value;

	return $returnData;
}



function throwerror ($msg){
	$data = new stdClass();
	//"document.getElementById('products').innerHTML='There was an error - no valid argument'");
	$data->error = "alert('".$msg."')";
	$data->result = "ERROR";
	$data->user = "";

	header("Content-Type: application/x-javascript");
	// Must suppress any error messages
	@ob_end_clean();
	echo json_encode($data);
	exit();
}