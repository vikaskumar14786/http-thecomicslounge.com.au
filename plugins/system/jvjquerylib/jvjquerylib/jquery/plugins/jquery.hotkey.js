/**
# mod_jvslidepro - JV Slide Pro
# @versions: 1.5.x,1.6.x,1.7.x,2.5.x
# ------------------------------------------------------------------------
# author    Open Source Code Solutions Co
# copyright Copyright (C) 2011 joomlavi.com. All Rights Reserved.
# @license - http://www.gnu.org/licenseses/gpl-3.0.html GNU/GPL or later
# Websites: http://www.joomlavi.com
# Technical Support:  http://www.joomlavi.com/my-tickets.html
-------------------------------------------------------------------------*/

;(function($){
    var 
        lastTarget = $(document),
        findActive = function(){
            var 
                key = keydowns.join('-'),
                evtName = 'hotkey.'+key,
                parents = lastTarget.parents(),
                rs = true
            ;
            if(lastTarget.triggerHandler(evtName) === false) return false;
            parents.each(function(){
                return rs = $(this).triggerHandler(evtName);
            });
            return rs;
        },
        keydowns = [],
        toStringKey = function(keycode){
            return special[keycode] || String.fromCharCode(keycode).toLowerCase();
        }
    ;
    
    $(document).bind({
        keydown: function(e){
            
            var 
                keystr = toStringKey(e.which),
                index = $.inArray(keystr,keydowns)
            ;
            index === - 1 && keydowns.push(keystr);
            return findActive();
        },
        keyup: function(e){
            var 
                keystr = toStringKey(e.which),
                index = $.inArray(keystr,keydowns)
            ;
            index > -1 && keydowns.splice(index,1);
        },
        mouseup: function(e){
            lastTarget = $(e.target);
        }
    });
    
    $.fn.hotkey = function(key,fn){
        if($.type(key) === 'object'){
            var This = $(this);
            $.each(key,function(index){
                This.hotkey(index,this);
            });
            return;
        }
        
        return $(this).bind('hotkey.'+key.replace(/\+/g,'-').toLowerCase(),fn);
    }
    var special = $.fn.hotkey.specialKeys = {
            8: "backspace", 9: "tab", 13: "return", 16: "shift", 17: "ctrl", 18: "alt", 19: "pause",
            20: "capslock", 27: "esc", 32: "space", 33: "pageup", 34: "pagedown", 35: "end", 36: "home",
            37: "left", 38: "up", 39: "right", 40: "down", 45: "insert", 46: "del", 
            96: "0", 97: "1", 98: "2", 99: "3", 100: "4", 101: "5", 102: "6", 103: "7",
            104: "8", 105: "9", 106: "*", 107: "+", 109: "-", 110: ".", 111 : "/", 
            112: "f1", 113: "f2", 114: "f3", 115: "f4", 116: "f5", 117: "f6", 118: "f7", 119: "f8", 
            120: "f9", 121: "f10", 122: "f11", 123: "f12", 144: "numlock", 145: "scroll", 191: "/", 224: "meta"
    };
})( jQuery );