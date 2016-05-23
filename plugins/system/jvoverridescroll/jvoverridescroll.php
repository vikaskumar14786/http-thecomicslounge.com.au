<?php
/**
# plugin system jvoverridescroll - JV Override Scroll
# @versions: 1.5.x,1.6.x,1.7.x,2.5.x
# ------------------------------------------------------------------------
# author    Open Source Code Solutions Co
# copyright Copyright (C) 2011 joomlavi.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/licenses.htmls GNU/GPL or later.
# Websites: http://www.joomlavi.com
# Technical Support:  http://www.joomlavi.com/my-tickets.html
-------------------------------------------------------------------------*/
defined('_JEXEC') or die('Restricted access');

class plgSystemJVOverrideScroll extends JPlugin{
    function __construct($subject,$config){
   
    	 if(class_exists('JVCustomParam')){
            if(class_exists('JParameter')) $param = new JFormField($config['params']);
            else $param = new JRegistry($config['params']); 
            $param = JVCustomParam::parse($param->get('configs'));
            JQuery('ui.draggable,plugins.mousewheel,plugins.hotkey');
            $doc = JFactory::getDocument();
            $path17 = "jvoverridescroll/";
            if(JVERSION < '1.6') $path17 = '';
            $doc->addStyleSheet(JURI::root()."plugins/system/{$path17}jvoverridescroll/jvoverridescroll.css");
            $doc->addScript(JURI::root()."plugins/system/{$path17}jvoverridescroll/jvoverridescroll.js");
            $doc->addScriptDeclaration("
                JVOverrideScroll(".json_encode($param).");
            ");
        }else  JError::raiseWarning(null,"Make sure you have installed and enabled, set Order first the newest version of jvjquerylib plug-in to use Override scroll plug-in");
    }
}
?>
