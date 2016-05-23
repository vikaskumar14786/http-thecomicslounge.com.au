<?php
/**
 * @version		:  2011-12-06 01:12:28$
 * @author		 
 * @package		Performar
 * @copyright	Copyright (C) 2011- . All rights reserved.
 * @license		
 */

// no direct access
defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once dirname(__FILE__).DS.'helper.php';


$msg = modPerformerHelper::getTest();
$performerImages = modPerformerHelper::getPerformerImages();

$document = &JFactory::getDocument();
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));


//$document->addScript( 'libraries/js/jquery.min.js' );
//$document->addScript( 'libraries/js/slides.min.jquery.js' );
$document->addStyleSheet( 'templates/comicslounge/css/slideshow.css' );

require JModuleHelper::getLayoutPath('mod_performer', $params->get('layout', 'default'));
