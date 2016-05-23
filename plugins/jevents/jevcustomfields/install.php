<?php

/**
 * copyright (C) 2012 GWE Systems Ltd - All rights reserved
 * @license GNU/GPLv3 www.gnu.org/licenses/gpl-3.0.html
 * */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

class plgjeventsjevcustomfieldsInstallerScript
{
	//
	// Joomla installer functions
	//
	public function preflight($type, $parent) {
// Joomla! broke the update call, so we have to create a workaround check.
        $db = JFactory::getDbo ();
        $db->setQuery ( "SELECT enabled FROM #__extensions WHERE element = 'com_jevents'" );
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
                Jerror::raiseWarning ( null, 'This Addon Requires JEvents core to be installed.<br/>Please first install JEvents' . $rel );
                return false;
            }
        }
	}
	
	function install($parent)
	{
		
		$this->update();
		
		//Whoops! must disable auto enable for now. We need to update the database default params at the same time, or add more fallbacks in code.
		
		// New install, lets enable the plugin! 
		//$db = JFactory::getDBO();
		//$db->setDebug(0);
		//$sql = "UPDATE #__extensions SET enabled=1 WHERE element='agendaminutes'";
		//$db->setQuery($sql);
		//$db->query();
		//echo $db->getErrorMsg();
		
		return true;

	}

	function uninstall($parent)
	{
		// No nothing for now, we want to keep the tables just incase they remove the plugin by accident. 
	}

	function update($parent)
	{
		$this->createTables();
		
		$db = JFactory::getDBO();
		// If upgrading then add new columns - do all the tables at once
		$sql = "SHOW COLUMNS FROM `#__jev_customfields`";
		$db->setQuery($sql);
		$cols = @$db->loadObjectList("Field");

		if (!array_key_exists("evdet_id", $cols))
		{
			$sql = "ALTER TABLE #__jev_customfields ADD COLUMN evdet_id int(11) NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}

		if (!array_key_exists("user_id", $cols))
		{
			$sql = "ALTER TABLE #__jev_customfields ADD COLUMN user_id int(11) NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}

		if (!array_key_exists("rp_id", $cols))
		{
			$sql = "ALTER TABLE #__jev_customfields ADD COLUMN rp_id int(11) NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}

		if (!array_key_exists("evdet_id", $cols))
		{
			$sql = "ALTER TABLE `#__jev_customfields` ADD INDEX user_id (user_id)";
			$db->setQuery($sql);
			@$db->query();
		}

		// If upgrading then add new columns - do all the tables at once
		$sql = "SHOW INDEX FROM `#__jev_customfields`";
		$db->setQuery($sql);
		$indexes = @$db->loadObjectList("Key_name");

		if (!array_key_exists("rp_id", $indexes))
		{
			$sql = "ALTER TABLE `#__jev_customfields` ADD INDEX rp_id (rp_id)";
			$db->setQuery($sql);
			@$db->query();
		}

		if (!array_key_exists("detval", $indexes))
		{
			$sql = "ALTER TABLE `#__jev_customfields` ADD INDEX detval (evdet_id, value(10) )";
			$db->setQuery($sql);
			@$db->query();
		}

	}
	
	function createTables() {

				$db = JFactory::getDBO();
				$charset = ($db->hasUTF()) ? 'DEFAULT CHARACTER SET `utf8`' : '';
				$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_customfields(
	id int(11) NOT NULL auto_increment,
	evdet_id int(11) NOT NULL default 0,
	user_id int(11) NOT NULL default 0,
	rp_id int(11) NOT NULL default 0,
	name varchar(255) NOT NULL default '',
	value text NOT NULL ,

	PRIMARY KEY  (id),
	INDEX (evdet_id),
	INDEX (user_id),
	INDEX (rp_id),
	INDEX combo (name,value(10)),
	INDEX detval (evdet_id,value(10))
)  $charset;
SQL;
				$db->setQuery($sql);
				if (!$db->query())
				{
					echo $db->getErrorMsg();
				}
	}
	function postflight($type, $parent) 
    {
		// $parent is the class calling this method
		// $type is the type of change (install, update or discover_install)
		echo '<h2>'.JText::_('JEV_CUSTOMFIELDS_PLUGIN') . ' ' . $parent->get('manifest')->version.' </h2>';
		echo '<strong>';

		if ($type == "update") {
			echo JText::_('JEV_CUSTOMFIELDS_INSTALL_SUCCESS_1') . '<br/>';
			echo JText::_('JEV_CUSTOMFIELDS_PLUGIN_DESC');
		} else {
			echo JText::_('JEV_CUSTOMFIELDS_INSTALL_SUCCESS_2') . '<br/>';
			echo JText::_('JEV_CUSTOMFIELDS_PLUGIN_DESC');
		}
		echo '</strong><br/><br/>';
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
/*
create temporary table tempTable (id int(11),evdet_id int(11),user_id int(11),name varchar(255),value text, 
	PRIMARY KEY  (id),
	INDEX (evdet_id),
	INDEX (user_id),
	INDEX combo (name,value(10)));

insert into tempTable (id,evdet_id,user_id,name,value) (select max(id), evdet_id, user_id, name, value  from jos_jev_customfields where user_id=0 group by evdet_id, name);

SELECT cf.*, cfsub.id  from jos_jev_customfields as cf
LEFT JOIN tempTable as cfsub ON cf.evdet_id= cfsub.evdet_id AND cf.name= cfsub.name and cf.id = cfsub.id
WHERE  cf.user_id=0
ORDER BY evdet_id desc

 */