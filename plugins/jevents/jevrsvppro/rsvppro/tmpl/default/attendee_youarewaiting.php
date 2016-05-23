<?php
defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

if (JRegistry::getInstance("jevents")->get("calledYouAreWaiting", false)){
	return;
}
JRegistry::getInstance("jevents")->set("calledYouAreWaiting", true);

echo "<div class='jevwaitinglist' style='font-weight:bold;color:red;'>".JText::_( 'JEV_EVENT_YOU_ARE_WAITING' )."</div>";

$registry = JRegistry::getInstance("jevents");
$registry->set("attendeeIsWaiting",true);