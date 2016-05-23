<?php

define('_JEXEC', 1);

$x = realpath(dirname(__FILE__) . '/' . ".." . '/' . ".." . '/' . ".." . '/');
if (!file_exists($x . '/' . "plugins"))
{
	$x = realpath(dirname(__FILE__) . '/' . ".." . '/' . ".." . '/' . ".." . '/' . ".." . '/');
}
if (!file_exists($x . '/' . "plugins") && isset($_SERVER["SCRIPT_FILENAME"]))
{
	$x = dirname(dirname(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))));
}
define('JPATH_BASE', $x);

// create the mainframe object
$_REQUEST['tmpl'] = 'component';

require_once JPATH_BASE . '/' . 'includes' . '/' . 'defines.php';
require_once JPATH_BASE . '/' . 'includes' . '/' . 'framework.php';

$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

include_once ("phpqrcode.php");

$code = JRequest::getString("bc", "");

if (!strpos($code, "-") || !strpos($code, ":"))
{
	echo "Problem";
	exit();
}

$parts = explode(":", $code);
if (count($parts) != 2)
{
	echo "Problem 2";
	exit();
}
$code = $parts[0];
$security = $parts[1];

$parts = explode("-", $code);
if (count($parts) != 3)
{
	echo "Problem 3";
	exit();
}

$code = $parts[1] . "-" . $parts[2];
$attendee_id = intval($parts[1]);
$guestcount = intval($parts[2]);

$uri = JURI::getInstance(JURI::base());
$path = $uri->toString(array('scheme', 'host', 'port', 'path'));
$path = str_replace("components/com_rsvppro/assets/images/", "", $path);

$db = JFactory::getDbo();
$db->setQuery("SELECT * FROM #__jev_attendees where id=" . $attendee_id);
$attendee = $db->loadObject();
if (!$attendee)
{
	echo "Problem 4";
	exit();
}

$params = JComponentHelper::getParams("com_rsvppro");
$securitycheck = md5($params->get("emailkey", "email key") . " gwe did attend " . $attendee_id . "-" . $guestcount);

if ($security !== $securitycheck)
{
	echo "Problem 4";
	exit();
}

$atd_id = $attendee->at_id . "|" . $attendee->rp_id;
$cid = $attendee->id;
$repeating = $attendee->rp_id == 0 ? 1 : 0;

$url = $path . "?option=com_rsvppro&amp;task=attendees.attend&amp;atd_id[]=$atd_id&amp;cid[]=$cid&amp;gc=$guestcount&amp;notes[]=qrscanned&amp;repeating=$repeating&sc=$securitycheck";

//var_dump(full_url($_SERVER));

QRcode::png($url);

// Create temporary local copy for PDF library to use!
if ( JRequest::getInt("fc") ) {
	// we need to generate filename somehow,
	// with md5 or with database ID used to obtains $codeContents...
	$fileName = "QRC_" . JRequest::getString("bc", "") . '.png';

	$pngAbsoluteFilePath = JPATH_CACHE."/qr/" . $fileName;

	jimport('joomla.filesystem.folder');
	if (!JFolder::exists(JPATH_CACHE."/qr/")){
		JFolder::create(JPATH_CACHE."/qr/");
	}
	// generating
	if (!file_exists($pngAbsoluteFilePath))
	{
		QRcode::png($url, $pngAbsoluteFilePath);
	}
}