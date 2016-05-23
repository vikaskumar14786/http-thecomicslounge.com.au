<?php
 /**
# plugin system jvjquerylib - JV JQuery Libraries
# @versions: 3.0.1
# ------------------------------------------------------------------------
# author    Open Source Code Solutions Co
# copyright Copyright (C) 2011 joomlavi.com. All Rights Reserved.
# @license - http://www.gnu.org/licenseses/gpl-3.0.html GNU/GPL or later.
# Websites: http://www.joomlavi.com
# Technical Support:  http://www.joomlavi.com/my-tickets.html
-------------------------------------------------------------------------*/
defined('_JEXEC') or die('Restricted access');
require_once(dirname(__FILE__).'/jvjquerylib/jquery/jquery.php');
require_once(dirname(__FILE__).'/jvjquerylib/customfield/customfield.php');

class plgSystemJVJQueryLib extends JPlugin{
    private $define;
    private $drops = "";
    function __construct($subject,$config){
    	
        if(class_exists('JParameter')) $params = new JFormField($config['params']);
        else $params = new JRegistry($config['params']);
        
        $j15 = JVERSION < '1.6' ? '': 'jvjquerylib/';
        $define = array(
            (object)array(
                'name' => 'root',
                'value' => substr(JURI::root(),0,strlen(JURI::root()) - 1) 
            ),
            (object)array(
                'name' => 'jspath',
                'value' => JURI::root()."plugins/system/{$j15}jvjquerylib/jquery"
            )
        );
        $this->define = array_merge($define,JVCustomParam::parse($params->get('define','[]')));
        $this->drops = explode(',',$params->get('drops','/jquery.js,/jquery.min'));
        $param = $params->get('configs');
        if(!$param || !$params->get('usecustom')) $param =   '[{"@data":{"name":"jquery","scripts":"{jspath}/jquery.js","styles":"","deny":"a=b&c=d,f=g","childs":[]}},{"@data":{"name":"ui","scripts":"","styles":"","deny":"","childs":[{"@data":{"name":"core","scripts":"jquery,{jspath}/ui/jquery.ui.core.min.js","styles":"{jspath}/ui/themes/base/jquery.ui.core.css","deny":"","childs":[]}},{"@data":{"name":"widget","scripts":"jquery,{jspath}/ui/jquery.ui.widget.min.js","styles":"{jspath}/ui/themes/base/jquery.ui.theme.css","deny":"","childs":[]}},{"@data":{"name":"position","scripts":"jquery,{jspath}/ui/jquery.ui.position.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"mouse","scripts":"ui.core,ui.widget,{jspath}/ui/jquery.ui.mouse.min.js%20","styles":"","deny":"","childs":[]}},{"@data":{"name":"draggable","scripts":"ui.mouse,{jspath}/ui/jquery.ui.draggable.min.js%20","styles":"","deny":"","childs":[]}},{"@data":{"name":"droppable","scripts":"ui.draggable,{jspath}/ui/jquery.ui.droppable.min.js%20","styles":"","deny":"","childs":[]}},{"@data":{"name":"resizable","scripts":"ui.mouse,{jspath}/ui/jquery.ui.resizable.min.js%20","styles":"{jspath}/ui/themes/base/jquery.ui.resizable.css","deny":"","childs":[]}},{"@data":{"name":"sortable","scripts":"ui.mouse,{jspath}/ui/jquery.ui.sortable.min.js%20","styles":"","deny":"","childs":[]}},{"@data":{"name":"accordion","scripts":"ui.core,ui.widget,{jspath}/ui/jquery.ui.accordion.min.js%20","styles":"{jspath}/ui/themes/base/jquery.ui.accordion.css","deny":"","childs":[]}},{"@data":{"name":"autocomplete","scripts":"ui.core,ui.widget,ui.position,{jspath}/ui/jquery.ui.autocomplete.min.js%20","styles":"{jspath}/ui/themes/base/jquery.ui.autocomplete.css","deny":"","childs":[]}},{"@data":{"name":"button","scripts":"ui.core,ui.widget,{jspath}/ui/jquery.ui.button.min.js%20","styles":"{jspath}/ui/themes/base/jquery.ui.button.css","deny":"","childs":[]}},{"@data":{"name":"dialog","scripts":"ui.button,ui.position,ui.draggable,ui.resizable,{jspath}/ui/jquery.ui.dialog.min.js%20","styles":"{jspath}/ui/themes/base/jquery.ui.dialog.css","deny":"","childs":[]}},{"@data":{"name":"slider","scripts":"ui.mouse,{jspath}/ui/jquery.ui.slider.min.js%20","styles":"{jspath}/ui/themes/base/jquery.ui.slider.css","deny":"","childs":[]}},{"@data":{"name":"tabs","scripts":"ui.core,ui.widget,{jspath}/ui/jquery.ui.tabs.min.js","styles":"{jspath}/ui/themes/base/jquery.ui.tabs.css","deny":"","childs":[]}},{"@data":{"name":"datepicker","scripts":"ui.core,{jspath}/ui/jquery.ui.datepicker.min.js","styles":"{jspath}/ui/themes/base/jquery.ui.datepicker.css","deny":"","childs":[]}},{"@data":{"name":"progressbar","scripts":"ui.core,ui.widget,{jspath}/ui/jquery.ui.progressbar.min.js","styles":"{jspath}/ui/themes/base/jquery.ui.progressbar.css","deny":"","childs":[]}},{"@data":{"name":"combobox","scripts":"ui.core,ui.button,ui.autocomplete,{jspath}/ui/jquery.ui.combobox.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"numericbox","scripts":"ui.core,ui.button,ui.autocomplete,ui.slider,{jspath}/ui/jquery.ui.numericbox.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"selectable","scripts":"ui.mouse,{jspath}/ui/jquery.ui.selectable.min.js%20","styles":"","deny":"","childs":[]}}]}},{"@data":{"name":"effects","scripts":"","styles":"","deny":"","childs":[{"@data":{"name":"core","scripts":"jquery,{jspath}/effects/jquery.effects.core.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"blind","scripts":"effects.core,{jspath}/effects/jquery.effects.blind.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"bounce","scripts":"effects.core,{jspath}/effects/jquery.effects.bounce.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"box","scripts":"effects.core,{jspath}/effects/jquery.effects.box.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"clip","scripts":"effects.core,{jspath}/effects/jquery.effects.clip.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"drop","scripts":"effects.core,{jspath}/effects/jquery.effects.drop.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"explode","scripts":"effects.core,{jspath}/effects/jquery.effects.explode.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"fade","scripts":"effects.core,{jspath}/effects/jquery.effects.fade.min.js%20","styles":"","deny":"","childs":[]}},{"@data":{"name":"fold","scripts":"effects.core,{jspath}/effects/jquery.effects.fold.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"highlight","scripts":"effects.core,{jspath}/effects/jquery.effects.highlight.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"pulsate","scripts":"effects.core,{jspath}/effects/jquery.effects.pulsate.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"scale","scripts":"effects.core,{jspath}/effects/jquery.effects.scale.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"shake","scripts":"effects.core,{jspath}/effects/jquery.effects.shake.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"slide","scripts":"effects.core,{jspath}/effects/jquery.effects.slide.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"transfer","scripts":"effects.core,{jspath}/effects/jquery.effects.transfer.min.js","styles":"","deny":"","childs":[]}}]}},{"@data":{"name":"plugins","scripts":"","styles":"","deny":"","childs":[{"@data":{"name":"colorpicker","scripts":"ui.widget,{jspath}/plugins/jquery.colorpicker.js","styles":"{jspath}/plugins/colorpicker/style.css","deny":"","childs":[]}},{"@data":{"name":"customfield","scripts":"ui.button,ui.sortable,ui.tabs,ui.autocomplete,ui.datepicker,ui.combobox,ui.numericbox,plugins.colorpicker,plugins.validate,{jspath}/plugins/customfield.js","styles":"{jspath}/plugins/customfield/style.css","deny":"","childs":[]}},{"@data":{"name":"mousewheel","scripts":"jquery,{jspath}/plugins/jquery.mousewheel.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"transform","scripts":"jquery,{jspath}/plugins/transformjs.1.0.beta.2.min.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"hotkey","scripts":"jquery,{jspath}/plugins/jquery.hotkey.js","styles":"","deny":"","childs":[]}},{"@data":{"name":"validate","scripts":"jquery,{jspath}/plugins/jquery.validate.min.js","styles":"{jspath}/plugins/validate/style.css","deny":"","childs":[]}}]}}]';
        $param = JVCustomParam::parse($param);
        
        JQuery::$data = new stdClass();
        JQuery::$data->childs = $this->parseData($param); 
       
        parent::__construct($subject,$config);
    }
    
    function parseData($param){
        $data = array();
        foreach($param as &$item){
            $key = $item->name;
            unset($item->name);
            $data[$key] = $item;
            $item->scripts = $this->replacePre($item->scripts);
            $item->styles = $this->replacePre($item->styles);
            if($item->childs) $item->childs = $this->parseData($item->childs);
        }
        return $data;
    }
    
    function replacePre($val){
        for($i = count($this->define) - 1; $i > -1; $i--){
            $define = $this->define[$i];
            $val = str_replace("{{$define->name}}",$define->value,$val);
        }
        return $val;
    }
    private function isJQueryFile($url){
        foreach($this->drops as $key) if(strpos($url,$key) != false) return true;
        return false;
    }
    public function onBeforeRender(){
        $doc = JFactory::getDocument();
        $count = 0;
        foreach($doc->_scripts as $script => $ops){
            if($this->isJQueryFile($script)){
                unset($doc->_scripts[$script]);
                $count ++;
            }
        }
        
        if($count > 0){
            $scripts = $doc->_scripts;
            $doc->_scripts = array(); 
            $doc->_scripts[JQuery::$data->childs['jquery']->scripts] = array('mime' => 'text/javascript', 'defer' => false, 'async' => false);
            $doc->_scripts[JURI::root().'plugins/system/jvjquerylib/jvjquerylib/jquery/noconflict.js'] = array('mime' => 'text/javascript', 'defer' => false, 'async' => false);
            foreach($scripts as $script => $ops) $doc->_scripts[$script] = $ops;
        }
    }
}

?>
