<?php
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

if ($this->jomsocial){
	echo  '<div class="cModule jevattendform"><h3><span>'.JText::_( 'JEV_REGISTRATIONS_NOT_YET_OPEN' ).'</span></h3>'. "</div>";
}
else {
	echo  JText::_("JEV_REGISTRATIONS_NOT_YET_OPEN");
}