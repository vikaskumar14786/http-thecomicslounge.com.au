<?php
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

if (JRegistry::getInstance("jevents")->get("calledYouMayBeAttending", false)){
	return;
}
JRegistry::getInstance("jevents")->set("calledYouMayBeAttending", true);

echo  JText::_( 'JEV_MAY_BE_ATTENDING' )."<br/>";