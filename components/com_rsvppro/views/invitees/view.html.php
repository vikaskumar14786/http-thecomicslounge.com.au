<?php
/**
 * copyright (C) 2008-2015 GWE Systems Ltd - All rights reserved
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

include_once(JPATH_COMPONENT_ADMINISTRATOR."/views/".basename(dirname(__FILE__))."/".basename(__FILE__));

/**
 * HTML View class for the component
 *
 * @static
 */
class FrontInviteesViewInvitees extends AdminInviteesViewInvitees
{
	function __construct($config = array()){
		parent::__construct($config);
		include_once(JPATH_ADMINISTRATOR . '/' . "includes" . '/' . "toolbar.php");
		JHtml::stylesheet( 'components/'.RSVP_COM_COMPONENT.'/assets/css/rsvppro.css' );
		JHtml::stylesheet("components/com_rsvppro/assets/pagination/css/pagination.css");

		JHtml::stylesheet("components/com_rsvppro/assets/adminsim/css/admin.css");
		
	}




}