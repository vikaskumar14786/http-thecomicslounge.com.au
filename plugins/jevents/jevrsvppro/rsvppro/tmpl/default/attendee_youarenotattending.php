<?php

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

if (JRegistry::getInstance("jevents")->get("calledYouAreNotAttending", false)){
	return;
}
JRegistry::getInstance("jevents")->set("calledYouAreNotAttending", true);

$html ="<div class='jevattendstate'>" . JText::_('JEV_ARE_NOT_ATTENDING') . "</div>";
echo  $html;
