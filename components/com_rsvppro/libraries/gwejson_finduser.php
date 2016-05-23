<?php
/**
* @copyright	Copyright (C) 2015-2015 GWE Systems Ltd. All rights reserved.
 * @license		By negoriation with author via http://www.gwesystems.com
*/

function ProcessJsonRequest(&$requestObject, $returnData){

	$returnData	= array();

	ini_set("display_errors",0);

	$input = JFactory::getApplication()->input;

	include_once(JPATH_SITE."/components/com_jevents/jevents.defines.php");

	$token = JSession::getFormToken();;
	if ((isset($requestObject->token)  &&  $requestObject->token!=$token) || JFactory::getApplication()->input->get('token', '', 'string')!= $token) {
		PlgSystemGwejson::throwerror("There was an error - bad token.  Please refresh the page and try again.");
	}

	$user = JFactory::getUser();
	if ($user->id==0 &&  isset($requestObject->task) && isset($requestObject->task)== "checkUser") {
		$db = JFactory::getDBO();
		$title = JFilterInput::getInstance()->clean($requestObject->address,"string");
		$text  = $db->Quote($db->escape( strtolower($title), true ), false );

		if (trim($title)=="" && trim($title)==""){
			PlgSystemGwejson::throwerror("There was an error - no valid argument");
		}

		if (strlen($title)<2 && $title!="*"){

			return $returnData;
		}

		$sql = "SELECT username, name, id  FROM #__users WHERE email = ".$text." AND block=0 order by name asc" ;

	}
	else {
		if ($user->id==0){
			PlgSystemGwejson::throwerror("There was an error");
		}

		// If user is jevents can deleteall or has backend access then allow them to specify the creator
		$jevuser = JEVHelper::getAuthorisedUser();
		$access = false;
		if ($user->get('id')>0){
			$access = $user->authorise('core.deleteall', 'com_jevents');
		}

		$db = JFactory::getDBO();
		$json = $input->get('json', '', 'raw');
		if (!isset($json["rp_id"])){
			PlgSystemGwejson::throwerror("There is no event");
		}
		$db->setQuery("SELECT * FROM #__jevents_repetition where rp_id=" . intval($json["rp_id"]));
		$event = $db->loadObject();
		require_once(JPATH_SITE."/components/com_jevents/jevents.defines.php");

		if ($event) {
			$dataModel = new JEventsDataModel();
			$queryModel = new JEventsDBModel($dataModel);

			// Find the first repeat
			$event = $dataModel->queryModel->getEventById($event->ev_id, false, "icaldb");
		}

		if ($event && ($event->created_by == $user->get('id') || JEVHelper::isAdminUser($user) || JEVHelper::canDeleteEvent($event, $user)))
		{
			$access=true;
		}

		if (!$access){
			PlgSystemGwejson::throwerror("There was an error - no access");
		}

		$db = JFactory::getDBO();

		// Remove any dodgy characters from fields
		// Only allow a to z , 0 to 9, ', " space (\\040), hyphen (\\-), underscore (\\_)
		/*
		$regex     = '/[^a-zA-Z0-9_\'\"\'\\40\\-\\_]/';
		$title    = preg_replace($regex, "", $title);
		$title = substr($title."    ",0,4);
		*/

		$title = JFilterInput::getInstance()->clean($requestObject->typeahead,"string");
		$text  = $db->Quote( '%'.$db->escape( $title, true ).'%', false );

		if (trim($title)=="" && trim($title)==""){
			PlgSystemGwejson::throwerror("There was an error - no valid argument");
		}

		if (strlen($title)<2 && $title!="*"){

			return $returnData;
		}

		if ($title!="*"){
			$sql = "SELECT username, name, id  FROM #__users WHERE (name LIKE ".$text." OR username LIKE ".$text."  OR email LIKE ".$text." ) AND block=0 order by name asc" ;
		}
		else {
			$sql = "SELECT username, name, id  FROM #__users WHERE block = 0" ;
		}
	}
	$db->setQuery($sql);
	$matches = $db->loadObjectList();

	if (count($matches)>0){
		foreach ($matches as $match) {
			$result = new stdClass();
			if (isset($requestObject->task) && isset($requestObject->task)== "checkUser") {
				// Don't send real data for security reasons
				$result->title = "title";
				$result->user_id = 1;
			}
			else {
				$result = $match;
				$result->title = $match->name. " (".$match->username.")";
			}
			$returnData[] = $result;
		}
	}

	return $returnData;
}

/*
	if (!$access){
		PlgSystemGwejson::throwerror("There was an error - no access");
	}

	if (isset($requestObject->typeahead) && trim($requestObject->typeahead)!==""){
		$returnData->result = "title is ".$requestObject->typeahead;
	}
	else {
		PlgSystemGwejson::throwerror ( "There was an error - no valid argument");
	}

 */