<?php
/**
 * @component AwoCoupon
 * @copyright Copyright (C) Seyi Awofadeju - All rights reserved.
 * @Website : http://awodev.com
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );


if(version_compare( JVERSION, '3.0.0', 'ge' )) { class AwoCouponViewConnect extends JViewLegacy {} }
else {
	jimport( 'joomla.application.component.view');
	class AwoCouponViewConnect extends JView {}
}

class AwocouponSiteViewCoupondelete extends AwoCouponViewConnect {
	
	function display($tpl = null) {
		$document = JFactory::getDocument();
		$pathway  = JFactory::getApplication()->getPathway();
		$params = JFactory::getApplication()->getParams();
		
		//JRequest::setVar('tmpl', 'component');
		
		parent::display($tpl);
	}

}
