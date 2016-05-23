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
if(JFactory::getApplication()->isAdmin()){
$jvcustomajax = JRequest::getVar('jvcustomajax');
if($jvcustomajax) require_once(JPATH_SITE.'/'.$jvcustomajax);
    
if(JVERSION > '1.6'){
    jimport('joomla.form.formfield');
     class JFormFieldJVCustom extends JFormField{
         protected $type = 'Jvcustom';
         protected function getInput()
         {
             if(get_magic_quotes_gpc()){
                 $this->value = stripslashes($this->value);
             }
             $el = (array)$this->element;
             $obj = new JVCustomParam($el["@attributes"]['xmlpath'],$this->fieldname,$this->id);
             $value = $this->value;
             if(!$value) $value = $obj->defaultData($this->fieldname);
             if(!$value) $value = '{}';

            return '
                <div class="clr"></div>
                <div class="jvcustomfieldpanel">
                    <textarea
                        style="display:none"
                        name="'.$this->name.'" 
                        id="'.$this->id.'">
                            ' .$value.'
                    </textarea>
                </div>
            ';
         }
         function getParams(){
             return json_encode($this->getJSONField($this->element));
         }
         protected function getLabel() {}
     }

 class JElementJVCustom extends JFormField{
        var    $type = 'JVCustom';
        
        function getInput(){
            $id = "JVCustom_".$name;
            $obj = new JVCustomParam($node->_attributes['xmlpath'],$name,$id);
             if(!$value) $value = $obj->defaultData($name);
             if(!$value) $value = '{}';
            
            return '
                <div class="jvcustomfieldpanel">
                    <textarea
                        style="display:none"
                        name="'.$control_name.'['.$name.']" 
                        id="'.$id.'">
                            ' .$value.'
                    </textarea>
                </div>
            ';
        }
     }
}else{
     jimport('joomla.form.formfield');
      class JElementJVCustom extends JFormField{
        var    $_name = 'JVCustom';
        function fetchTooltip(){}
        function fetchElement($name, $value, &$node, $control_name){
            $id  = $control_name.$name;
            $obj = new JVCustomParam($node->_attributes['xmlpath'],$name,$id);
             if(!$value) $value = $obj->defaultData($name);
             if(!$value) $value = '{}';
            return '
                <div class="jvcustomfieldpanel">
                    <textarea
                        style="display:none"
                        name="'.$control_name.'['.$name.']" 
                        id="'.$id.'">
                            ' .$value.'
                    </textarea>
                </div>
            ';
        }
     }
}
}
class JVCustomParam{
    private $xml;
    private $paramsNode;
    static $overrided = false;
    static $xmls = array();
    function __construct($path,$name,$id){
        $path = JPATH_SITE .'/'. $path;
        if(isset(self::$xmls[$path])){
            $this->xml = self::$xmls[$path];
        }else {
            $this->xml = simplexml_load_file($path);
            self::$xmls[$path] = $this->xml;
            
            
            $doc = JFactory::getDocument();
            
            if($this->xml->jvcustoms->php) foreach($this->xml->jvcustoms->php as $script){
                eval((string) $script);
            }
            
            if($this->xml->jvcustoms->style) foreach($this->xml->jvcustoms->style as $style){
                $src = ((array)$style);
                if(isset($src['@attributes'])){
	                $src = trim($src['@attributes']['src']);
	                if($src) $doc->addStyleSheet(JURI::root().$src);
                }
                $str = trim((string) $style);
                if($str) $doc->addStyleDeclaration($str);
                
            }
        }
        $this->paramsNode = $this->xml->jvcustoms->params;
        $this->eventsNode = $this->xml->jvcustoms->events;
        $this->dataNode = $this->xml->jvcustoms->datas;
        
        
        $params = $this->params($name);
        $doc = JFactory::getDocument();
        
        if(!self::$overrided){
            self::$overrided = true;
            JQuery('plugins.customfield');
            $doc->addScriptDeclaration("
                CustomField.initializes = [];
                CustomField.fields = [];
                CustomField.apply = function(){
                    var errors = 0;
                    jQuery.each(CustomField.fields,function(){
                        var 
                            custom = this,
                            formData = this.prev()
                        ;
                        errors += custom.data().validate();
                        if(errors > 0) return;
                        formData.val(JSON.stringify(custom.data().data()));
                    });
                    
                    return !(errors > 0);
                };
                
                
                jQuery(function($){
                    
                    var initialize = function(){ $.each(CustomField.initializes,function(){ this()});}
                    if(!JSON.parse || !JSON.stringify ){
                        $.getScript('http://ajax.cdnjs.com/ajax/libs/json2/20110223/json2.js',function(){
                            initialize();
                        });
                    }else initialize();
                });
            ");
        
        
            if(JVERSION > '1.6'){
                 $doc->addScriptDeclaration("
                    jQuery(function($){
                        var  _submit = Joomla.submitbutton;
                        Joomla.submitbutton = function(){
                            if(arguments[0].indexOf('cancel') > 0 || CustomField.apply()) _submit.apply(Joomla,arguments);
                        }
                    });
                 ");
            }else{
                $doc->addScriptDeclaration("
                    jQuery(function(){
                        var _submit = submitbutton;
                        submitbutton = function(){
                            if(arguments[0].indexOf('cancel') > 0 || CustomField.apply()) _submit.apply(window,arguments);
                        }
                    });
                ");
            }
        
        }
        $this->addScript("#{$id}",$params);
    }
    function params($name){
        $node = $this->paramsNode->{$name};
        if(!(bool)$node) return false;
        if(count($node->children()) == 0) return trim((string) $node);
        return json_encode($this->getJSON($node));
    }
    function events($name){
        $node = $this->eventsNode->{$name};
        if(!(bool)$node) return "{}";
        return (string)$node;
    }
    function defaultData($name){
        $node = $this->dataNode->{$name};
        return (string)$node;
    }
    
    function getJSON($xml){
        $json = array();
         foreach($xml->attributes() as $key => $val) $json[$key] = (string) $val;
         foreach(array('label','title') as $key){
             $json[$key] = JText::_($json[$key]);
         }
         
         $json['item'] = array();
         
         foreach($xml->children() as $children){
             $strValue = trim((string)($children));
             if($strValue != '') $json['item'][$children->getName()] = $strValue;
             else $json['item'][$children->getName()] = $this->getJSON($children);
         }
         if(in_array($json['field'],array('multi'))){
             if(isset($json['filter'])) $json['filter'] = (bool) $json['filter'];
             switch(count($json['item'])){
                 case 0: unset($json['item']);
                    break;
                 case 1: foreach($json['item']  as $children) $json['item'] = $children;
                    break;
             }
         } 
         return $json;
    }
    function addScript($id,$params){
        $doc = JFactory::getDocument();
         $doc->addScriptDeclaration("
            (function($){
                CustomField.initializes.push(function(){
                    var 
                        formData = $('{$id}'),
                        params = (function(param){return param ||{};})({$params}),
                        custom = new CustomField(params),
                        data
                    ;
                    try{ data = JSON.parse(formData.val()) }catch(e){}
                    formData.after(custom);
                    custom.data().data(data);
                    CustomField.fields.push(custom);
                });
            })(jQuery);
         ");
        if($this->xml->jvcustoms->script) foreach($this->xml->jvcustoms->script as $script){
            $src = ((array)$script);
            $src = trim($src['@attributes']['src']);
            if($src) $doc->addScript(JURI::root().$src);
            $str = trim((string) $script);
            if($str) $doc->addScriptDeclaration($str);
        }
    }
    
    static function parse($data){
        if(is_string($data)) $data = json_decode($data);
        return self::parseData($data);
    }
    
    private static function parseData($param){
        if(is_array($param)) return self::parseDataArray($param);
        else if(is_object($param)) return self::parseDataObject($param);
        else if(is_string($param)) return  urldecode($param);
        return $param;
    }
    
    private static function parseDataObject($datas){
    	//echo "<pre>"; print_r($datas); die;
    	if(isset($datas->{'@selected'})){
    		$selected = $datas->{'@selected'};
    		if($selected){
    			$data = new stdClass();
    			$data->{'@selected'} = $selected;
    			$data->{$selected} = self::parseData($datas->{$selected});
    			return $data;
    		}
    	}
        
        if(isset($datas->_disabled)) return null;
        foreach($datas as $key => $value) $datas->{$key} = self::parseData($value);
        return $datas;
    }
    private static function parseDataArray($datas){
        $arrdata = array();
        if(count($datas) == 0) return $datas;
        if(!is_object($datas[0])) return $datas;
        foreach($datas as $item){
            $checked = isset($item->{'@check'})?$item->{'@check'}:true;
            if($checked === false) continue;
            $arrdata[] = self::parseData($item->{'@data'});
        }
        return $arrdata;
    }  
}
?>
