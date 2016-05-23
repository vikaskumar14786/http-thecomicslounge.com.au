<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @copyright   Copyright (C) 2010 JEvents Project Group
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://joomlacode.org/gf/project/jevents
 */
defined('_JEXEC') or die('Restricted access');

JPluginHelper::importPlugin('jevents');

$plugin = JPluginHelper::getPlugin('jevents', 'jevpaidsubs');
if (!$plugin)
    return;

$plugparams = new JRegistry($plugin->params);

// load plugin parameters
$plugin = JPluginHelper::getPlugin($plugin->type, $plugin->name);
// create the plugin
$className = 'plg' . $plugin->type . $plugin->name;
$dispatcher = JDispatcher::getInstance();

if (!class_exists($className)) {
    echo "No such class as " . $className . " check JEvents Paid Submission Module and Plugin";
    die();
}
$plugin = new $className($dispatcher, (array)($plugin));

//Lets load the style sheets
JHtml::stylesheet('modules/mod_jevents_paidsubs/assets/css/jevpaidsubs.css');

if (JFile::exists(JPATH_SITE . "/components/com_jevents/assets/css/jevcustom.css")) {
    JHtml::stylesheet("components/com_jevents/assets/css/jevcustom.css");
}

$targetmenu = $params->get("targetid", 0);
$menuitem = false;
if ($targetmenu > 0) {
    $menu = JFactory::getApplication()->getMenu();
    $menuitem = $menu->getItem($targetmenu);
}
if ($menuitem && $menuitem->component == "com_virtuemart") {
    $shoplink = JRoute::_($menuitem->link . ("&Itemid=" . $targetmenu));
} else {
    global $Itemid;
    $products = $plugin->getProducts();
    if (count($products) == 0) {
        $shoplink = false;
    }
    if (count($products) == 1) {
        //$shoplink = JRoute::_("index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=" . $products[0]->virtuemart_product_id . "&virtuemart_category_id=2&Itemid=" . $targetmenu);
        $shoplink = JRoute::_("index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=" . $products[0]->virtuemart_product_id . "&Itemid=" . $targetmenu);
    } else {
        $catid = $plugin->getCategory();
        $shoplink = JRoute::_("index.php?option=com_virtuemart&view=category&virtuemart_category_id=" . $catid . "&Itemid=" . $targetmenu);
    }
}

// setup for all required function and classes
$file = JPATH_SITE . '/components/com_jevents/mod.defines.php';
if (file_exists($file)) {
    include_once($file);
    include_once(JEV_LIBS . "/modfunctions.php");
} else {
    die("JEvents Calendar\n<br />This module needs the JEvents component");
}


require_once(JModuleHelper::getLayoutPath('mod_jevents_paidsubs', "summary"));
