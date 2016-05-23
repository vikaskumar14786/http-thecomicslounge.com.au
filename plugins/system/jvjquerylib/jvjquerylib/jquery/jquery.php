<?php
  /**
# plugin system jvjquerylib - JV JQuery Libraries
# @versions: 1.5.x,1.6.x,1.7.x,2.5.x
# ------------------------------------------------------------------------
# author    Open Source Code Solutions Co
# copyright Copyright (C) 2011 joomlavi.com. All Rights Reserved.
# @license - http://www.gnu.org/licenseses/gpl-3.0.html GNU/GPL or later.
# Websites: http://www.joomlavi.com
# Technical Support:  http://www.joomlavi.com/my-tickets.html
-------------------------------------------------------------------------*/
defined('_JEXEC') or die('Restricted access');
 Class JQuery{
    static 
        $path,
        $dir,
        $instance,
        $uitheme ="smoothness",
        $imported = array(),
        $data
    ;
    function __construct(){  }
    static function instance(){
        if(!self::$instance) self::$instance = new JQuery();
        return self::$instance;
    }
    function add($keystr){ 
        $keys = explode(',',$keystr);
        foreach($keys as $key) $this->addCurent(trim($key));
    }
    
    private function  addCurent($keystr){
        if(isset(self::$imported[$keystr])) return;
        self::$imported[$keystr] = true;
        
        $doc = JFactory::getDocument();
        $keys = array_filter(explode('.',$keystr));
        $data = &self::$data;
        foreach($keys as $key){
            $data = & $data->childs[$key];
            
            if(!$data) {
                $doc->addScript($keystr);
                return;
            }
            if(!$this->checkDenys($data)) return;
        }
        
        $this->add($data->scripts);
        foreach($data->childs as $k => $val) $this->add($val->scripts);
        $styles = array_filter(explode(',',$data->styles));
        foreach($styles as $style) $doc->addStyleSheet($style);
        return true;
    }
    
    private function checkDenys($data){
        switch(trim($data->deny)){
            case '': return true;
            case 'all': return false;
        }
        $denys = explode(',',$data->deny);
        foreach($denys as $deny) if(!$this->checkDeny($deny)) return false;
        return true;
    }
    
    private function checkDeny($data){
        $denys = explode('&',$data);
        $count = 0;
        foreach($denys as $deny){
            $deny = explode('=', $deny);
            if(JRequest::getVar(trim($deny[0])) === trim($deny[1])) $count ++;
        }
        if($count == count($denys)) return false;
        return true;
    }
}

function JQuery(){
    $libs = func_get_args();
    if(count($libs) == 0 ) JQuery::instance()->add('jquery');
    foreach($libs as $key) JQuery::instance()->add($key);
}
?>