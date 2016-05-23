<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: view.html.php 1703 2010-02-16 12:23:46Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * HTML View class for the component
 *
 * @static
 */
class AdminPmethodsViewPmethods extends RSVPAbstractView
{
	function overview($tpl = null)
	{
		JHtml::stylesheet(  'components/'.RSVP_COM_COMPONENT.'/assets/css/rsvpadmin.css' );

		$document = JFactory::getDocument();
		$document->setTitle(JText::_('RSVP_SESSIONS'));

		// Set toolbar items for the page
		JToolBarHelper::title( JText::_( 'RSVP_SESSIONS' ), 'jevents' );

                JToolBarHelper::spacer();
		JToolBarHelper::custom( 'cpanel.cpanel', 'default.png', 'default.png', 'JEV_ADMIN_CPANEL', false );

		RsvpproHelper::addSubmenu();

		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		JHtml::_('behavior.tooltip');
	}
}