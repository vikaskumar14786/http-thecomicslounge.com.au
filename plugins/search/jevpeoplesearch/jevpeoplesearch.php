<?php
/**
 * Events Calendar Search plugin for Joomla 1.5.x
 *
 * @version     $Id: eventsearch.php 969 2008-02-16 11:24:45Z geraint $
 * @package     Events
 * @subpackage  Mambot Events Calendar
 * @copyright   Copyright (C) 2006-2007 JEvents Project Group
 * @copyright   Copyright (C) 2000 - 2003 Eric Lamette, Dave McDonnell
 * @licence     http://www.gnu.org/copyleft/gpl.html
 * @link        http://joomlacode.org/gf/project/jevents
 */

/** ensure this file is being included by a parent file */
defined( '_JEXEC'  ) or die( 'Restricted access' );

// setup for all required function and classes
$file = JPATH_SITE . '/components/com_jevents/mod.defines.php';
if (file_exists($file) ) {
	include_once($file);
	include_once(JEV_LIBS."/modfunctions.php");

} else {
	die ("JEvents People-Resources\n<br />This plugin needs the JEvents component");
}


// Import library dependencies
jimport('joomla.event.plugin');

// Check for 1.6
if (!(version_compare(JVERSION, '1.6.0', ">=")))
{
	JFactory::getLanguage()->load( 'plg_search_jevpeoplesearch' );
	JFactory::getApplication()->registerEvent( 'onSearchAreas', 'plgSearchJevPeopleSearchAreas' );
}

/**
 * @return array An array of search areas
 */
function &plgSearchJevPeopleSearchAreas() {
	static $areas;
	if (!isset($areas)){
		$areas = array(
			'eventpeople' => JText::_('PLG_JEVENTS_JEVPEOPLESEARCH')
		);
	}
	return $areas;
}


class plgSearchJevpeoplesearch extends JPlugin {

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param 	object $subject The object to observe
	 * @param 	array  $config  An array that holds the plugin configuration
	 * @since 1.5
	 */

	function __construct( &$subject, $config = array())
	{
		parent::__construct( $subject, $config );

		// load plugin parameters
		if (!(version_compare(JVERSION, '1.6.0', ">="))) {
			$this->_plugin =  JPluginHelper::getPlugin( 'search', 'jevpeoplesearch' );
			$this->_params = new JRegistry( $this->_plugin->params );
		}
		$this->loadLanguage( 'plg_search_jevpeoplesearch' );
	}

	/**
	 * @return array An array of search areas
	 */
	function onContentSearchAreas()
	{
		if (version_compare(JVERSION, '1.6.0', ">="))
		{
			return array(
				'eventpeople' => JText::_('PLG_JEVENTS_JEVPEOPLESEARCH')
			);
		}

	}


	function onContentSearch($text, $phrase='', $ordering='', $areas=null)
	{
		return $this->onSearch($text, $phrase, $ordering, $areas);

	}

	
	/**
	* Search method
	*
	* The sql must return the following fields that are used in a common display
	* routine: href, title, section, created, text, browsernav
	* @param string Target search string
	* @param string matching option, exact|any|all
	* @param string ordering option, newest|oldest|popular|alpha|category
	*/
	function onSearch( $text, $phrase='', $ordering='' , $areas=null) {

		$db	= JFactory::getDBO();
		$user =  JFactory::getUser();
		$groups = (version_compare(JVERSION, '1.6.0', '>=')) ? implode(',', $user->getAuthorisedViewLevels()) : false;

		$limit = version_compare(JVERSION, '1.6.0', ">=")?$this->params->get( 'search_limit', 50 ):$this->_params->def( 'search_limit', 50 );
		$limit 		= "\n LIMIT $limit";

		$search_private = version_compare(JVERSION, '1.6.0', ">=")?$this->params->get( 'search_private', 0 ):$this->_params->def( 'search_private', 0 );

		$text = trim( $text );
		if ($text == '') {
			return array();
		}

		if (is_array( $areas )) {
			if (!array_intersect( $areas, array_keys(plgSearchJevPeopleSearchAreas() ) )) {
				return array();
			}
		}

		$search_attributes  = array('people.title', 'people.description', 'people.street', 'people.city', 'people.state', 'people.country');

		$wheres_ical = array();
		switch ($phrase) {
			case 'exact':
				$text		= $db->Quote( '%'.$db->escape( $text, true ).'%', false );
				$wheres2 = array();
				foreach ($search_attributes as $search_item) {
					$wheres2[] = "LOWER($search_item) LIKE ".$text;
				}
				$where_ical = '(' . implode( ') OR (', $wheres2 ) . ')';
				break;
			case 'all':
			case 'any':
			default:
				$words = explode( ' ', $text );

				$wheres = array();
				foreach ($words as $word) {
					$wheres2 = array();
					$word = $db->Quote('%' . $db->escape($word, true) . '%', false);
					foreach ($search_attributes as $search_item) 
					{
						$wheres2[] = "LOWER($search_item) LIKE ".$word;
					}
					$wheres[] = implode( ' OR ', $wheres2 );
				}
				$where_ical = '(' . implode( ($phrase == 'all' ? ') AND (' : ') OR ('), $wheres ) . ')';

				break;
		}

		$order = '';
		switch ($ordering) {
			case 'oldest':
				$order = 'people.created ASC ';
				break;

			case 'popular':
			case 'category':
			case 'alpha':
				$order = 'people.title ASC ';
				break;

			case 'newest':
			default:
				$order = 'people.created DESC ';
				break;
		}

		$eventstitle=JText::_("Event People/Resources");
		$display2 = array();
		foreach ($search_attributes as $search_attribute) {
			$display2[] = "$search_attribute";
		}
		$display = 'CONCAT('. implode(", ' ', ", $display2) . ')';
		$query = "SELECT people.title,"
		. "\n people.created,"
		. "\n $display as text,"
		. "\n CONCAT('$eventstitle','/',people.title) AS section,"
		. "\n CONCAT('index.php?option=com_jevpeople&task=people.detail&pers_id=',people.pers_id) AS href,"
		. "\n '2' AS browsernav "
		. "\n FROM #__jev_people as people"
		. "\n WHERE ($where_ical)"
		. "\n AND people.access " . ((version_compare(JVERSION, '1.6.0', '>=')) ? ' IN (' . $groups . ')' : ' <=  ' . $user->gid)				
		. "\n AND people.published = '1'"
		. ((!$search_private)?" \n AND people.global=1":"")
		. "\n ORDER BY " . $order
		.	$limit
		;

		$db->setQuery( $query );
		$list_ical = $db->loadObjectList();

		for ($i=0;$i<count($list_ical); $i++){				
			$list_ical[$i]->href .= "&title=".JApplication::stringURLSafe($list_ical[$i]->title)."&se=1";
		}
		return $list_ical;
	}
}
