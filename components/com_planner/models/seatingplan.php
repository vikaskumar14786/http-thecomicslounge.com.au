<?php
/**
 * Joomla! 1.5 component video
 *
 * @version $Id: video.php 2011-12-10 03:24:58 svn $
 * @author vivek
 * @package Joomla
 * @subpackage video
 * @license GNU/GPL
 *
 * display feature video 
 *
 * This component file was created using the Joomla Component Creator by Not Web Design
 * http://www.notwebdesign.com/joomla_component_creator/
 *
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

/**
 * video Component video Model
 *
 * @author      notwebdesign
 * @package		Joomla
 * @subpackage	video
 * @since 1.5
 */
class PlannerModelSeatingplan extends JModel {
    /**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
    }
    
  /**
     * Function for geting Seat plan as per selected hall hall id = object id in vm ticket
     * */
     
    function getSeatingPlan($hallId)
    {
		
	$db =& JFactory::getDBO();
	$query = "SELECT *  FROM #__vmeticket_section WHERE object_id =  $hallId  ORDER BY id ";
	$db->setQuery($query);
	$row = $db->loadObjectlist();
	return $row;
	
    }
}
?>
