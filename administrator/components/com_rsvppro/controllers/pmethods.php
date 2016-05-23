<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: pmethods.php 1712 2010-03-04 07:33:11Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd, 2006-2008 JEvents Project Group
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined( '_JEXEC' ) or die( 'Restricted Access' );

jimport('joomla.application.component.controller');
//Import filesystem libraries. Perhaps not necessary, but does not hurt
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

//Import filesystem libraries. Perhaps not necessary, but does not hurt
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class AdminPmethodsController extends JControllerLegacy {

	/**
	 * Controler for Sessions 
	 * @param array		configuration
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		$this->registerTask( 'list',  'overview' );
		$this->registerDefaultTask("overview");

		// Must load admin language files
		$lang = JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);

		$this->dataModel = new JEventsDataModel();
		$this->queryModel = new JEventsDBModel($this->dataModel);

	}

	/**
	 * List Sessions
	 *
	 */
	function overview()
	{
		// get the view
		$this->view = $this->getView("pmethods","html");

		$mainframe = JFactory::getApplication();
		$option=JRequest::getCmd("option");

		$db	= JFactory::getDBO();

		$user = JFactory::getUser();
		$params =  JComponentHelper::getParams(JEV_COM_COMPONENT);
                
                $query = $db->getQuery(true);
                // Select all records from the user profile table where key begins with "custom.".
                // Order it by the ordering field.
                $query->select($db->quoteName(array('extension_id', 'name', 'element', 'folder', 'enabled')));
                $query->from($db->quoteName('#__extensions'));
                $query->where($db->quoteName('element') . ' LIKE '. $db->quote('rsvppro') . 'OR' . $db->quoteName('folder') . ' LIKE '. $db->quote('rsvppro') );
                $query->order('ordering ASC');

                // Reset the query using our newly populated query object.
                $db->setQuery($query);

                // Load the results as a list of stdClass objects (see later for more options on retrieving data).
                $rows = $db->loadObjectList();
                
                //var_dump($pmethods_inst);
                

		// Set the layout
		$this->view->setLayout('overview');
                $this->view->assign('rows',$rows);
		$this->view->display();
        }
        function unpublish(){
		$db= JFactory::getDBO();
		$cid = JRequest::getVar("cid",array());
		if (count($cid)!=1) {
			$this->setRedirect(JRoute::_("index.php?option=com_rsvpprotask=pmethods.overview",false) );
			return;
		}
		$pid = $cid[0];
		$sql = "UPDATE #__extensions SET enabled=0 where extension_id=".$db->Quote($pid);
		$db->setQuery($sql);
		$db->query();

		$this->setRedirect(JRoute::_("index.php?option=com_rsvppro&task=pmethods.overview",false) );
	}

	function publish(){
		$db= JFactory::getDBO();
		$cid = JRequest::getVar("cid",array());
		if (count($cid)!=1) {
			$this->setRedirect(JRoute::_("index.php?option=com_rsvppro&task=pmethods.overview",false) );
			return;
		}
                $pid = $cid[0];
                // Create a new query object
                $query = $db->getQuery(true);
                
                // Test what the plugin is for raising errors and so on
                $query->select($db->quoteName(array('extension_id', 'name', 'type', 'element', 'folder')));
                $query->from($db->quoteName('#__extensions'));
                $query->where($db->quoteName('extension_id') . 'LIKE' . $db->quote($pid));
                $query->order('ordering ASC');
                $db->setQuery($query);
               
                $plugind = $db->loadAssoc();
                // Lets check if virtuemart exists before enabling it!
                
                $virtuemart_exist = JFolder::exists(JPATH_SITE . "/administrator/components/com_virtuemart/");

                if ($plugind['element'] == "virtuemart" && !$virtuemart_exist == true || $plugind['folder'] == "vmcustom" && !$virtuemart_exist == true || $plugind['folder'] == "vmcoupon" && !$virtuemart_exist == true) {
                    // We need virtuemart! Raise a warning:
                    $this->setRedirect(JRoute::_("index.php?option=com_rsvppro&task=pmethods.overview",false) );
                    $config_exist = !JFile::exists(JPATH_SITE . "/administrator/components/com_virtuemart/config.xml");
                    JFactory::getApplication()->enqueueMessage(JText::_('RSVP_PRO_VIRTUEMART_NEEDED'), 'error');
                    return;
                    
                }
		$sql = "UPDATE #__extensions SET enabled=1 where extension_id=".$db->Quote($pid);
		$db->setQuery($sql);
		$db->query();

		$this->setRedirect(JRoute::_("index.php?option=com_rsvppro&task=pmethods.overview",false) );
	}
}
