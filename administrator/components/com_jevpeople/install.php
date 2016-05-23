<?php

/**
 * copyright (C) 2012-2014 GWE Systems Ltd - All rights reserved
 * @license GNU/GPLv3 www.gnu.org/licenses/gpl-3.0.html
 * Version 3.0.35
 * */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

class com_jevpeopleInstallerScript
{

	public function preflight($action, $adapter)
	{		
		$this->cleanupManifestMess();
	}

	//
	// Joomla installer functions
	//
	
	function install($parent)
	{

		$this->createTables();

		$this->cleanupFilters();
		
		return true;

	}

	private function cleanupManifestMess()
	{
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		
		$db = JFactory::getDBO();
		if (version_compare(JVERSION, "3.3", 'ge')){
			$charset = ($db->hasUTFSupport()) ?  ' DEFAULT CHARACTER SET `utf8`' : '';
			$rowcharset = ($db->hasUTFSupport()) ?  'CHARACTER SET utf8' : '';
		}
		else {
			$charset = ($db->hasUTF()) ?  ' DEFAULT CHARACTER SET `utf8`' : '';
			$rowcharset = ($db->hasUTF()) ?  'CHARACTER SET utf8' : '';
		}

		$query = $db->getQuery(true);
		$query->select('extension_id');
		$query->from('#__extensions');
		$query->where('name = "jeventsmanagedpeopleresources-component"');
		$db->setQuery($query);
		$wrongExtension_id = $db->loadResult();

		if($wrongExtension_id != null)
		{
			
			$query = $db->getQuery(true);
			$query->select('extension_id');
			$query->from('#__extensions');
			$query->where('name = "jevpeople"');
			$query->where('type = "component"');
			$db->setQuery($query);
			$rightExtension_id = $db->loadResult();			

			//We remove old menu items
			$query = $db->getQuery(true);
			$query->delete();
			$query->from('#__menu');
			$query->where('alias="com-jevpeople"');
			$db->setQuery($query);
			try{
				$db->query();
			}catch (Exception $e)
			{
				JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				return false;
			}

			//After fixing, we clean up db removing broken component from DB
			$query = $db->getQuery(true);
			$query->delete();
			$query->from('#__extensions');
			$query->where('name = "jeventsmanagedpeopleresources-component"');
			$query->where('type = "component"');
			$query->where('extension_id='.$wrongExtension_id);
			$db->setQuery($query);
			try{
				$db->query();
			}catch (Exception $e)
			{
				JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				return false;
			}

			$query = $db->getQuery(true);
			$query->delete();
			$query->from('#__assets');
			$query->where('name = "com_jeventsmanagedpeopleresources-component"');
			$db->setQuery($query);
			try{
				$db->query();
			}catch (Exception $e)
			{
				JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				return false;
			}

			// Rebuild the categories table
			$table = JTable::getInstance('Category', 'JTable', array('dbo' => $db));
			try{
				$table->rebuild();
			} catch (Exception $e) {
				JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				return false;
			}
		}

		//And obviously we remove extra folders
		if(JFolder::exists(JPATH_ROOT.'/administrator/components/com_jeventsmanagedpeopleresources-component'))
		{
			JFolder::delete(JPATH_ROOT.'/administrator/components/com_jeventsmanagedpeopleresources-component');

			if(JFolder::exists(JPATH_ROOT.'/administrator/components/com_jeventsmanagedpeopleresources-component'))
			{
				JFactory::getApplication()->enqueueMessage("There was a problem in version 3.0.15 package and your system cannot remove the superfluos admin folder: /administrator/components/com_jeventsmanagedpeopleresources-component . Please remove it manually.",'error');
				return false;
			}

		}

		if(JFolder::exists(JPATH_ROOT.'/components/com_jeventsmanagedpeopleresources-component'))
		{
			JFolder::delete(JPATH_ROOT.'/components/com_jeventsmanagedpeopleresources-component');
				
			if(JFolder::exists(JPATH_ROOT.'/components/com_jeventsmanagedpeopleresources-component'))
			{
				JFactory::getApplication()->enqueueMessage("There was a problem in version 3.0.15 package and your system cannot remove the superfluos admin folder: /components/com_jeventsmanagedpeopleresources-component . Please remove it manually.",'error');
				return false;
			}
		}

		return;
	}

	function uninstall($parent)
	{
		// No nothing for now

	}

	function update($parent)
	{
		$this->createTables();
		$this->updateTables();

		$this->cleanupFilters();
		return true;

	}

	private function createTables()
	{
		$db = JFactory::getDBO();
		if (version_compare(JVERSION, "3.3", 'ge')){
			$charset = ($db->hasUTFSupport()) ?  ' DEFAULT CHARACTER SET `utf8`' : '';
			$rowcharset = ($db->hasUTFSupport()) ?  'CHARACTER SET utf8' : '';
		}
		else {
			$charset = ($db->hasUTF()) ?  ' DEFAULT CHARACTER SET `utf8`' : '';
			$rowcharset = ($db->hasUTF()) ?  'CHARACTER SET utf8' : '';
		}

		/**
		 * create tables if it doesn't exit
		 * 
		 */
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_people(
	pers_id int(12) NOT NULL auto_increment,
	title VARCHAR(255) NOT NULL default "",
	alias VARCHAR(255) NOT NULL default "",
	street varchar(255) NOT NULL default "",
	postcode varchar(255) NOT NULL default "",
	city varchar(255) NOT NULL default "",
	state varchar(255) NOT NULL default "",
	country varchar(255) NOT NULL default "",
	phone varchar(255) NOT NULL default "",
	image varchar(255) NOT NULL default "",
	imagetitle varchar(255) NOT NULL default "",
	description text NOT NULL ,
	geolon float NOT NULL default 0,
	geolat float NOT NULL default 0,
	geozoom int(2) NOT NULL default 10,
	pcode_id int(12) NOT NULL default 0,
	www varchar(255) NOT NULL default "",

	type_id int(11) NOT NULL default 0,
	linktouser int(11) NOT NULL default 0,
 
	catid0 int(11) NOT NULL default 0,
	catid1 int(11) NOT NULL default 0,
	catid2 int(11) NOT NULL default 0,
	catid3 int(11) NOT NULL default 0,
	catid4 int(11) NOT NULL default 0,
	
	global tinyint(1) unsigned NOT NULL default 0,
	mapicon varchar(255) NOT NULL DEFAULT 'blue-dot.png',

	ordering int(11) NOT NULL default '0',
	access int(11) unsigned NOT NULL default 0,
	published tinyint(1) unsigned NOT NULL default 0,
	created datetime  NOT NULL default '0000-00-00 00:00:00',
	created_by int(11) unsigned NOT NULL default '0',
	created_by_alias varchar(100) NOT NULL default '',
	modified_by int(11) unsigned NOT NULL default '0',
		
	checked_out int(11) unsigned NOT NULL default '0',
	checked_out_time DATETIME NOT NULL default '0000-00-00 00:00:00',

	overlaps tinyint(3) unsigned NOT NULL DEFAULT '0',
				
	params text NOT NULL ,
	
	PRIMARY KEY  (pers_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_peopletypes(
	type_id int(12) NOT NULL auto_increment,
	title VARCHAR(255) NOT NULL default "",
	multiple tinyint(1) unsigned NOT NULL default 0,	
	maxperevent int(5) unsigned NOT NULL default 1,	
	multicat tinyint(1) NOT NULL default 0,	
	selfallocate tinyint(1) NOT NULL default 0,
	allowedgroups  VARCHAR(255) NOT NULL default '',
	showaddress tinyint(1) NOT NULL default 0,	
	typetemplate varchar(255) NOT NULL DEFAULT '',
	presentationfields text NOT NULL,
	categories VARCHAR(255) NOT NULL default "",
	calendars VARCHAR(255) NOT NULL default "",

	PRIMARY KEY  (type_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_peopleeventsmap(
	pers_id int(12) NOT NULL  default 0,
	evdet_id int(12) NOT NULL  default 0,
	ordering int(4) NOT NULL  default 0,

	PRIMARY KEY (pers_id,evdet_id),
	INDEX  (evdet_id),
	INDEX  (pers_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_peopleeventsmaxallocation(
	type_id int(12) NOT NULL  default 0,
	evdet_id int(12) NOT NULL  default 0,
	maxallocation  int(10) NOT NULL  default 0,

	PRIMARY KEY (type_id,evdet_id),
	INDEX  (evdet_id),
	INDEX  (type_id)
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}

		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS #__jev_customfields2(
	id int(11) NOT NULL auto_increment,
	target_id int(11) NOT NULL default 0,
	targettype varchar(255) NOT NULL default '',
	name varchar(255) NOT NULL default '',
	value text NOT NULL ,

	PRIMARY KEY  (id),
	INDEX (target_id, targettype),
	INDEX combo (name,value(10))
)  $charset;
SQL;
		$db->setQuery($sql);
		if (!$db->query())
		{
			echo $db->getErrorMsg();
		}

	}

	private function updateTables()
	{
		$db = JFactory::getDBO();

		$sql = "SHOW COLUMNS FROM `#__jev_people`";
		$db->setQuery($sql);
		$cols = @$db->loadObjectList("Field");

		if (!array_key_exists("phone", $cols))
		{
			$sql = "ALTER TABLE #__jev_people ADD  column phone varchar(255) NOT NULL DEFAULT '' ";
			$db->setQuery($sql);
			if (!@$db->query())
			{
				echo $db->getErrorMsg() . "<br/>";
			}
		}

		if (!array_key_exists("mapicon", $cols))
		{
			$sql = "ALTER TABLE #__jev_people ADD  column mapicon varchar(255) NOT NULL DEFAULT 'blue-dot.png'";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("overlaps", $cols))
		{
			$sql = "ALTER TABLE #__jev_people ADD  column overlaps tinyint(3) unsigned NOT NULL DEFAULT '0' ";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("linktouser", $cols))
		{
			$sql = "ALTER TABLE #__jev_people ADD column linktouser int(11) NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("www", $cols))
		{
			$sql = "ALTER TABLE #__jev_people ADD column www varchar(255) NOT NULL default ''";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("image", $cols))
		{
			$sql = "ALTER TABLE #__jev_people ADD column image varchar(255) NOT NULL default ''";
			$db->setQuery($sql);
			@$db->query();
		}
		if (!array_key_exists("imagetitle", $cols))
		{
			$sql = "ALTER TABLE #__jev_people ADD column imagetitle varchar(255) NOT NULL default ''";
			$db->setQuery($sql);
			@$db->query();
		}


		$sql = "SHOW COLUMNS FROM `#__jev_peopletypes`";
		$db->setQuery($sql);
		$cols = @$db->loadObjectList("Field");

		if (!array_key_exists("allowedgroups", $cols))
		{
			$sql = "ALTER TABLE #__jev_peopletypes ADD column allowedgroups  VARCHAR(255) NOT NULL default '' ";
			$db->setQuery($sql);
			if (!@$db->query())
			{
				echo $db->getErrorMsg() . "<br/>";
			}
		}

		if (!array_key_exists("typetemplate", $cols))
		{
			$sql = "ALTER TABLE #__jev_peopletypes ADD  column typetemplate varchar(255) NOT NULL DEFAULT ''";
			$db->setQuery($sql);
			if (!@$db->query())
			{
				echo $db->getErrorMsg() . "<br/>";
			}
		}

		if (!array_key_exists("selfallocate", $cols))
		{
			$sql = "ALTER TABLE #__jev_peopletypes ADD column selfallocate tinyint(1) NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}

		if (!array_key_exists("categories", $cols))
		{
			$sql = "ALTER TABLE #__jev_peopletypes ADD column categories VARCHAR(255) NOT NULL default ''";
			$db->setQuery($sql);
			@$db->query();
		}

		if (!array_key_exists("calendars", $cols))
		{
			$sql = "ALTER TABLE #__jev_peopletypes ADD column calendars VARCHAR(255) NOT NULL default ''";
			$db->setQuery($sql);
			@$db->query();
		}

		if (!array_key_exists("maxperevent", $cols))
		{
			$sql = "ALTER TABLE #__jev_peopletypes ADD column maxperevent int(5) unsigned NOT NULL default 1";
			$db->setQuery($sql);
			@$db->query();
		}

		if (!array_key_exists("presentationfields", $cols))
		{
			$sql = "ALTER TABLE #__jev_peopletypes ADD column presentationfields text NOT NULL ";
			$db->setQuery($sql);
			@$db->query();
		}


		$sql = "SHOW COLUMNS FROM `#__jev_peopleeventsmap`";
		$db->setQuery($sql);
		$cols = $db->loadObjectList();
		$uptodate = false;
		foreach ($cols as $col)
		{
			if ($col->Field == "ordering")
			{
				$uptodate = true;
				break;
			}
		}
		if (!$uptodate)
		{
			$sql = "ALTER TABLE #__jev_peopleeventsmap ADD column ordering int(4) NOT NULL  default 0";
			$db->setQuery($sql);
			if (!@$db->query())
			{
				echo $db->getErrorMsg() . "<br/>";
			}
		}

		// If upgrading then add new columns - do all the tables at once
		$sql = "SHOW COLUMNS FROM `#__jev_customfields2`";
		$db->setQuery($sql);
		$cols = $db->loadObjectList();
		$uptodate = false;
		foreach ($cols as $col)
		{
			if ($col->Field == "target_id")
			{
				$uptodate = true;
				break;
			}
		}
		if (!$uptodate)
		{
			$sql = "ALTER TABLE #__jev_customfields2 ADD COLUMN target_id int(11) NOT NULL default 0";
			$db->setQuery($sql);
			@$db->query();
		}

	}

	// removes old redundance filter files
	private function cleanupFilters(){
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');		
		$folder = "jevents/jevlocations";
		if (JFolder::exists(JPATH_ROOT.'/plugins/filters') || JFolder::exists(JPATH_ROOT.'/plugins/jevents/filters')) {
			$filters = array("Peoplelookup","Peoplesearch");
			foreach ($filters as $filter){
				if (JFile::exists(JPATH_ROOT.'/plugins/filters/'.$filter.".php")) {
					try {
						JFile::delete(JPATH_ROOT.'/plugins/filters/'.$filter.".php");
					}
					catch (Exception $e){
						
					}
				}
				
				if (JFile::exists(JPATH_ROOT.'/plugins/jevents/filters/'.$filter.".php")) {
					try {
						JFile::delete(JPATH_ROOT.'/plugins/jevents/filters/'.$filter.".php");
					}
					catch (Exception $e){
						
					}
				}
				
			}
		}
	}
	
}
