<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevents.defines.php 1417 2009-04-19 07:32:52Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

defined('_JEXEC') or die( 'No Direct Access' );

if (!defined("RSVP_COM_COMPONENT")){
	define("RSVP_COM_COMPONENT","com_rsvp");
	define("RSVP_COMPONENT",str_replace("com_","",RSVP_COM_COMPONENT));
}

if (!defined("RSVP_LIBS")){
	define("RSVP_ADMINPATH",JPATH_ADMINISTRATOR."/components/".RSVP_COM_COMPONENT."/");
	define("RSVP_PATH",JPATH_SITE."/components/".RSVP_COM_COMPONENT."/");
	define("RSVP_LIBS",RSVP_PATH."libraries/");
	define("RSVP_ADMINLIBS",RSVP_ADMINPATH."libraries/");
	define("RSVP_HELPERS",RSVP_ADMINPATH."helpers/");
	define("RSVP_CONFIG",RSVP_ADMINPATH."config/");
	define("RSVP_FILTERS",RSVP_ADMINPATH."filters/");
	define("RSVP_LAYOUTS",RSVP_ADMINPATH."layouts/");
	define("RSVP_VIEWS",RSVP_ADMINPATH."views");
}

if (!defined("JEV_COM_COMPONENT")){
	define("JEV_COM_COMPONENT","com_jevents");
	define("JEV_COMPONENT",str_replace("com_","",JEV_COM_COMPONENT));
}

if (!defined("JEV_LIBS")){
	define("JEV_ADMINPATH",JPATH_ADMINISTRATOR."/components/".JEV_COM_COMPONENT."/");
	define("JEV_PATH",JPATH_SITE."/components/".JEV_COM_COMPONENT."/");
	define("JEV_LIBS",JEV_PATH."libraries");
	define("JEV_ADMINLIBS",JEV_ADMINPATH."libraries");
	define("JEV_HELPERS",JEV_PATH."helpers/");
	define("JEV_FILTERS",JEV_ATH."libraries/filters/");
	define("JEV_LAYOUTS",JEV_PATH."layouts/");
	define("JEV_VIEWS",JEV_PATH."views");
}


JLoader::register('JSite' , JPATH_SITE.'/includes/application.php');

JLoader::register('RSVPConfig',RSVP_ADMINPATH."config.php");
JLoader::register('RSVPAbstractView',RSVP_VIEWS."/abstract/abstract.php");
JLoader::register('RSVP_Helper',RSVP_ADMINLIBS."helper.php");
JLoader::register('JevTemplateHelper',RSVP_ADMINLIBS."templatehelper.php");
JLoader::register('JevRsvpParameter',RSVP_ADMINLIBS."jevrsvpparameter.php");

include_once(JEV_PATH."jevents.defines.php");

JLoader::register('JEVConfig',JEV_ADMINLIBS."config.php");
JLoader::register('JEventsDBModel',JEV_PATH."libraries/dbmodel.php");
JLoader::register('JEventsDataModel',JEV_PATH."libraries/datamodel.php");
JLoader::register('JEVAccess',JEV_PATH."libraries/access.php");
JLoader::register('JEVHelper',JEV_PATH."libraries/helper.php");
JLoader::register('JEventsHTML',JEV_PATH."libraries/jeventshtml.php");

JLoader::register('jEventCal',JEV_PATH."libraries/jeventcal.php");
JLoader::register('jIcal',JEV_PATH."libraries/jical.php");
JLoader::register('jIcalEventDB',JEV_PATH."libraries/jicaleventdb.php");
JLoader::register('jIcalEventRepeat',JEV_PATH."libraries/jicaleventrepeat.php");
JLoader::register('JevModuleHelper',JEV_PATH."libraries/jevmodulehelper.php");

JLoader::register('JEV_CommonFunctions',JEV_PATH."libraries/commonfunctions.php");
JLoader::register('JevDate',JEV_PATH."libraries/jevdate.php");

JLoader::register('JEventsVersion',JPATH_ADMINISTRATOR."/components/com_jevents/libraries/version.php");
$jevversion = JEventsVersion::getInstance();
