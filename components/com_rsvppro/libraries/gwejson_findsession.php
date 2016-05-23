<?php

/**
 * @copyright	Copyright (C) 2008-2015 GWE Systems Ltd. All rights reserved.
 * @license		By negoriation with author via http://www.gwesystems.com
 */
defined('JPATH_PLATFORM') or die;

function ProcessJsonRequest(&$requestObject, $returnData)
{

	if (!isset($requestObject->typeahead))
	{
		return array();
	}

	$user = JFactory::getUser();
	if ($user->id == 0)
	{
		throwerror("There was an error");
	}

	$returnData->titles = array();
	$returnData->exactmatch = false;

	ini_set("display_errors", 0);

	$db = JFactory::getDbo();

	$search = $db->escape(trim(strtolower($requestObject->typeahead)));

	$limit = 100;
	$limitstart = 0;

	// category filter - implement based on permissions!
	$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
	$authorisedonly = $params->get("authorisedonly", 0);
	$cats = $user->getAuthorisedCategories('com_jevents', 'core.create');

	// first the whole event sessions
	$where = array();
	$join = array();

	$where[] = "det.summary LIKE '%$search%'";
	$where[] = "ev.ev_id IS NOT NULL";

	$user = JFactory::getUser();
	if ($params->get("multicategory", 0))
	{
		$join[] = "\n #__jevents_catmap as catmap ON catmap.evid = rpt.eventid";
		$join[] = "\n #__categories AS catmapcat ON catmap.catid = catmapcat.id";
		$where[] = " catmapcat.access " . ' IN (' . JEVHelper::getAid($user) . ')';
		$needsgroup = true;
	}

	$where[] = "(atd.allrepeats=1 and atd.allowregistration>0 )";
	// not trashed
	$where[] = " ev.state >=0 ";

	$orderby = 'ORDER BY det.dtstart ASC';

	$query = "SELECT det.summary as title , CONCAT_WS('|', atd.id ,  0) as session_id"
			. "\n FROM #__jevents_vevent as ev "
			. "\n LEFT JOIN #__jevents_vevdetail as det ON ev.detail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. ( count($join) ? "\n LEFT JOIN  " . implode(' LEFT JOIN ', $join) : '' )
			. ( count($where) ? "\n WHERE " . implode(' AND ', $where) : '' )
			. "\n GROUP BY ev.ev_id $orderby"
	;

	if ($limit > 0)
	{
		$query .= "\n LIMIT $limitstart, $limit";
	}
	$db->setQuery($query);

	$rows = $db->loadObjectList();

	// now the rest
	$where = array();
	$join = array();

	$where[] = "det.summary LIKE '%$search%'";
	$where[] = "ev.ev_id IS NOT NULL";

	if ($params->get("multicategory", 0))
	{
		$join[] = "\n #__jevents_catmap as catmap ON catmap.evid = rpt.eventid";
		$join[] = "\n #__categories AS catmapcat ON catmap.catid = catmapcat.id";
		$where[] = " catmapcat.access " . ' IN (' . JEVHelper::getAid($user) . ')';
	}

	$where[] = "(atd.allrepeats=0 and atd.allowregistration>0 )";

	// not trashed
	$where[] = " ev.state >=0 ";

	$orderby = ' ORDER BY rpt.startrepeat ASC';

	$query = "SELECT CONCAT_WS( ' - ', det.summary, DATE_FORMAT(rpt.startrepeat , '%e %b %Y')) as title , CONCAT_WS('|', atd.id ,  rpt.rp_id) as session_id"
			. "\n FROM #__jevents_vevent as ev "
			. "\n LEFT JOIN #__jevents_repetition as rpt ON rpt.eventid=ev.ev_id"
			. "\n LEFT JOIN #__jevents_vevdetail as det ON rpt.eventdetail_id=det.evdet_id"
			. "\n LEFT JOIN #__jev_attendance AS atd ON atd.ev_id = ev.ev_id"
			. ( count($join) ? "\n LEFT JOIN  " . implode(' LEFT JOIN ', $join) : '' )
			. ( count($where) ? "\n WHERE " . implode(' AND ', $where) : '' )
			. "\n GROUP BY rpt.rp_id $orderby"
	;

	$query .= "\n LIMIT $limitstart, $limit";
	$db->setQuery($query);

	$rows2 = $db->loadObjectList();

	return array_merge($rows, $rows2);	
}
