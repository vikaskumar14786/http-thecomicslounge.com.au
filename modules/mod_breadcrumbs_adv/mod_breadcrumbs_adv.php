<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_breadcrumbs_adv
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Modified by UWiX - Apr 2013
 *
 */

defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

// Get the breadcrumbs
$list	= modBreadCrumbsAdvHelper::getList($params);
$count	= count($list);

// Set the default separator
$separator = modBreadCrumbsAdvHelper::setSeparator($params->get('separator'));
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));

require JModuleHelper::getLayoutPath('mod_breadcrumbs_adv', $params->get('layout', 'default'));
