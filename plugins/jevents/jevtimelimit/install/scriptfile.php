<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd, 2006-2008 JEvents Project Group
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
// We need to use a Directory Seperator as Windows does not like the usage of / still.
if(!defined('DS')){
	define('DS',DIRECTORY_SEPARATOR);
}
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class plgjeventsjevtimelimitInstallerScript
{

	var $pluginsDir;
	var $filterpath;
	var $pluginDir;
	var $plugin_filterpath;
	var $filters;
	
	public function __construct()
	{
		$this->pluginsDir = 'plugins/jevents';
		$this->filterpath = JFolder::makeSafe($this->pluginsDir ."/filters");
		$this->pluginDir = $this->pluginsDir.'/jevtimelimit';
		$this->plugin_filterpath = JFolder::makeSafe($this->pluginDir ."/filters");
		
		$this->filters = array('Afterdate.php', 'Beforedate.php', 'Timelimit.php');  // RSH 11/11/10 - Need a better way to do this!
	}
	
	public function preflight($type, $parent) {
		// Joomla! broke the update call, so we have to create a workaround check.
		$db = JFactory::getDbo ();
		$db->setQuery ( "SELECT enabled FROM #__extensions WHERE element = 'com_jevents' AND type='component' " );
		$is_enabled = $db->loadResult ();

		if ($is_enabled == 1) {
			$manifest  =  JPATH_SITE . "/administrator/components/com_jevents/manifest.xml";
			if (!JFile::exists($manifest) || ! $manifestdata = $this->getValidManifestFile ( $manifest )) {
				$manifest  =  JPATH_SITE . "/administrator/manifests/packages/pkg_jevents.xml";
				if (!JFile::exists($manifest) ||  ! $manifestdata = $this->getValidManifestFile ( $manifest )) {
					Jerror::raiseWarning ( null, 'JEvents Must be installed first to use this addon.');
					return false;
				}
			}

			$app = new stdClass ();
			$app->name = $manifestdata ["name"];
			$app->version = $manifestdata ["version"];

			if (version_compare( $app->version , '3.1.14', "lt")) {
				Jerror::raiseWarning ( null, 'A minimum of JEvents V3.1.14 is required for this addon. <br/>Please update JEvents first.' . $rel );
				return false;
			} else {
				$this->hasJEventsInst = 1;
				return;
			}
		} else {
                    $this->hasJEventsInst = 0;
                    if ($is_enabled == 0) {
                        Jerror::raiseWarning ( null, 'JEvents has been disabled, please enable it first.' . $rel );
                        return false;
                    } elseif(!$is_enabled) {
                        Jerror::raiseWarning ( null, 'This Addon Requires JEvents Core to be installed.<br/>Please first install JEvents' . $rel );
                        return false;
                    }
		}
	}

	public function update()
	{

		return true;
	}

	public function install($adapter)
	{
		return true;
	}

	public function postflight($action, $adapter)
	{
		// This should be done as part of the postflight - after the rest of the installation has occurred
		// Create, if necessary, the main jevents folder for the filters, copy the files from plugin's filter folder
		if (!(JFolder::exists(JPATH_ROOT . "/" . $this->plugin_filterpath))) {
			JError::raiseNotice(0, JText::_('PLG_JEVENTS_JEVTIMELIMIT_FILTERS_NOT_FOUND'));
			return false;
		} else {
			// The 'force' parameter on the copy() will create the destination directory if it doesn't already exist
			if (!(JFolder::copy($this->plugin_filterpath, $this->filterpath, JPATH_ROOT, $force = true))) {
				JError::raiseNotice(0, JText::_('PLG_JEVENTS_JEVTIMELIMIT_FILTERS_MOVE_ERROR'));
				return false;
			}
		}
		
		return true;  
		
	}

	public function uninstall($adapter)
	{
		$files = array();
		// need a better way to get files.  When this method is invoked by the uninstallation framework, the initially installed filter folder for 
		// this plugin is already deleted!
		foreach ($this->filters AS $file)
		{
			$files[] = JPATH_ROOT . "/" . $this->filterpath . "/" . $file;
		}
		
		try {
			JFile::delete($files);  // delete all of the files
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		
		// Delete the main filters folder if it is empty
		$files = JFolder::files(JPATH_ROOT ."/". $this->filterpath, $filter = '.', $recurse = true, $fullpath = true, $exclude = array());
		
		if ( (is_array($files)) && (count($files) == 0) ) {
			try {
				JFolder::delete(JPATH_ROOT . "/" . $this->filterpath);  // delete main folder
			} catch (Exception $e) {
				echo $e->getMessage();
			}		
		}

	}
        
        // Manifest validation
	function getValidManifestFile($manifest)
	{
		$filecontent = JFile::read($manifest);
		if (stripos($filecontent, "jevents.net") === false && stripos($filecontent, "gwesystems.com") === false && stripos($filecontent, "joomlacontenteditor") === false && stripos($filecontent, "virtuemart") === false && stripos($filecontent, "sh404sef") === false)
		{
			return false;
		}
		// for JCE and Virtuemart only check component version number
		if (stripos($filecontent, "joomlacontenteditor") !== false || stripos($filecontent, "virtuemart") !== false || stripos($filecontent, "sh404sef") !== false || strpos($filecontent, "JCE") !== false)
		{
			if (strpos($filecontent, "type='component'") === false && strpos($filecontent, 'type="component"') === false)
			{
				return false;
			}
		}
	
		$manifestdata = JApplicationHelper::parseXMLInstallFile($manifest);
		if (!$manifestdata)
			return false;
		if (strpos($manifestdata["authorUrl"], "jevents") === false && strpos($manifestdata["authorUrl"], "gwesystems") === false && strpos($manifestdata["authorUrl"], "joomlacontenteditor") === false && strpos($manifestdata["authorUrl"], "virtuemart") === false && strpos($manifestdata['name'], "sh404SEF") === false)
		{
			return false;
		}
		return $manifestdata;
	}
}
