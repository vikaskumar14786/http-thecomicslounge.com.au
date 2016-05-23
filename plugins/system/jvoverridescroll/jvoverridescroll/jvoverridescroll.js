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
window.JVOverrideScroll = (function($){
    var s = function(options){
        $(document).ready(function(){
            options && $.each(options,function(){
                var 
                    This = this,
                    selector = $(this.selector)
                ;
                selector.each(function(){
                    new JVScroll(this,This);
                });
            });
        });
    }
    return s;
})(jQuery);

var JVScroll = (function($){
    var interVal = function(ops){
        var 
            timeOut,
            to = ops.step,
            a = ops.delay,
            step = function(){
                timeOut = setTimeout(function(){
                    step();
                },a);
                change();
                to += 10;
                a = Math.sqrt(a/60) *60;
            },
            change = function(){
                var last = ops.obj[ops.prop];
                ops.obj[ops.prop] += to * ops.dir;
                if(last === ops.obj[ops.prop]) clearTimeout(timeOut);
            }
        ;
        timeOut = setTimeout(function(){
            step();
        },ops.delay);
        change();
        return {
            stop: function(){
                clearTimeout(timeOut);
            }
        };
    }
    
    var scroll = function(selector,options){ 
        selector = $(selector);
        options = $.extend({},scroll.defaults,options);
        selector.addClass('jvs-panel').addClass((options.prefix == 'other'?options.prefixOther:options.prefix) || 'jvs-default');
        
        if(selector.is('body')) {
            alert('JVOverrideScroll not support body!');
            return;
        }
        selector.animate({
            width: options.panelWidth || undefined,
            height: options.panelHeight || undefined
        },500);
        var 
            visibles = {v: false,h:false},
            contents = selector.children(),
            contentPanel = $('<div>',{'class':'jvs-content'})
        ;
        selector.append(contentPanel);
        contentPanel.append(contents);
        options.vertical && (function(){
            var 
                moveScrollTop = function(step,dir){
                    var a = interVal({
                        obj: contentPanel[0],
                        prop: 'scrollTop',
                        step: step,
                        delay: 700,
                        a: 10,
                        dir: dir
                    });
                    $(document).one('mouseup',function(){
                        a.stop();
                    });
                },
                ops = options.vertical,
                contentLastSize = 0,
                contentLastVisible = 0,
                contentSize, contentVisible, contentScroll, slideSize,slideScroll,btnSlideSize,percent,
                isHover = true,isMove = false,
                btnUp = $('<a>',{'class':'jvs-btnUp',href:'javascript:void(0)'}).append($('<span>')).bind({
                    mousedown: function(e){
                        moveScrollTop(30,-1)
                    }
                }),
                btnDown = $('<a>',{'class':'jvs-btnDown',href:'javascript:void(0)'}).append($('<span>')).bind({
                    mousedown: function(e){
                        moveScrollTop(30,1);
                    }
                }),
                btnMid = $('<a>',{'class':'jvs-btnMid',href:'javascript:void(0)'}).append(
                    $('<span>',{'class':'jvs-mid-top'}),
                    $('<span>',{'class':'jvs-mid-mid'}).append($('<span>')),
                    $('<span>',{'class':'jvs-mid-bottom'})
                ).bind({}).draggable({
                    start: function(){isMove = true;},
                    stop: function(){isMove = false; visibles.v && isHover || setTimeout(function(){ isHover || scroll.fadeOut(300);},500);},
                    drag: function(e,ui){
                        if(ui.position.top > slideScroll) ui.position.top = slideScroll;
                        contentPanel[0].scrollTop =  (ui.position.top / slideScroll) * contentScroll;
                    },
                    scroll: false,
                    axis:'y',
                    containment: "parent"
                }),
                slide = $('<div>',{'class':'jvs-ver-slide'}).append($('<span>',{'class':'jvs-ver-slide-top'}),btnMid,$('<span>',{'class':'jvs-ver-slide-bottom'})).bind({
                    mousedown: function(e){
                        var slide = $(e.target);
                        if(!slide.is('.jvs-ver-slide')) return;
                        var pos = e.offsetY || e.layerY;
                        if(pos > btnMid.position().top) moveScrollTop(contentVisible - 10,1);
                        else moveScrollTop(contentVisible - 10,-1);
                    }
                }),
                scroll = $('<div>',{'class': 'jvs-ver'}).append(btnUp,btnDown,slide).addClass('jvs-btnPos-' + ops.btnPos).css('height',ops.size),
                refresh = function(){
                    percent = contentVisible / contentSize;
                    if(percent >= 1){
                        scroll.hide();
                        visibles.v = false;
                        selector.removeClass('visible-y');
                        return;
                    }
                    selector.addClass('visible-y');
                    scroll.show();
                    visibles.v = true;
                    
                    
                    contentScroll = contentSize - contentVisible;
                    slideSize = slide.height();
                    btnSlideSize = slideSize * percent;
                    btnSlideSize = btnSlideSize < ops.minsizebtn ? ops.minsizebtn : btnSlideSize;
                    slideScroll = slideSize - btnSlideSize;
                    
                    
                    btnMid.stop().animate({
                        'height': btnSlideSize + 1,
                        'top': contentPanel[0].scrollTop / contentScroll * slideScroll
                    });
                }
            ;
            selector.append(scroll)
            setInterval(function(){
                contentSize = contentPanel[0].scrollHeight;
                contentVisible = contentPanel.innerHeight();
                if(contentLastSize === contentSize && contentLastVisible === contentVisible) return;
                contentLastSize = contentSize; contentLastVisible = contentVisible;
                refresh();
            },100);
            
            if(options.showWith === 'hover'){
                selector.hover(function(){
                    isHover = true;
                    visibles.v && scroll.fadeIn(300);
                },function(){
                    isHover = false;
                    visibles.v && isMove || setTimeout(function(){ isMove || isHover || scroll.fadeOut(300);},500);
                });
            }
            contentPanel.scroll(function(){
                if(isMove) return;
                btnMid.css('top', this.scrollTop / contentScroll * slideScroll); 
            });
            
            var changeScroll = function(to){
                var last = contentPanel[0].scrollTop;
                contentPanel[0].scrollTop += 30 * to;
                return last === contentPanel[0].scrollTop;
            }
            selector.hotkey({
                'up':function(){ return changeScroll(-1); },
                'down':function(){ return changeScroll(1);}
            });
            selector.bind({
                mousewheel: function(e,to){
                    if(visibles.h && options.priority === 'horizontal') return;
                    return changeScroll(-to);
                }
            })
        })();
        
        
        options.horizontal && (function(){
            var
                moveScrollLeft = function(step,dir){
                    var a = interVal({
                        obj: contentPanel[0],
                        prop: 'scrollLeft',
                        step: step,
                        delay: 700,
                        a: 10,
                        dir: dir
                    });
                    $(document).one('mouseup',function(){
                        a.stop();
                    });
                },
                ops = options.horizontal,
                contentLastSize = 0,
                contentLastVisible = 0,
                contentSize, contentVisible, contentScroll, slideSize,slideScroll,btnSlideSize,percent,               
                isHover = true,isMove = false,
                btnLeft = $('<a>',{'class':'jvs-btnLeft',href:'javascript:void(0)'}).append($('<span>')).bind({
                    mousedown: function(e){
                        moveScrollLeft(20,-1)
                    }
                }),
                btnRight = $('<a>',{'class':'jvs-btnRight',href:'javascript:void(0)'}).append($('<span>')).bind({
                    mousedown: function(e){
                        moveScrollLeft(20,1);
                    }
                }),
                btnCenter = $('<a>',{'class':'jvs-btnCent',href:'javascript:void(0)'}).append(
                    $('<span>',{'class':'jvs-cent-left'}),
                    $('<span>',{'class':'jvs-cent-cent'}).append($('<span>')),
                    $('<span>',{'class':'jvs-cent-right'})
                ).bind({}).draggable({
                    start: function(){isMove = true;},
                    stop: function(){isMove = false; visibles.h && isHover || setTimeout(function(){ isHover || scroll.fadeOut(300);},500);},
                    drag: function(e,ui){
                        if(ui.position.left > slideScroll) ui.position.left = slideScroll;
                        contentPanel[0].scrollLeft =  (ui.position.left / slideScroll) * contentScroll;
                    },
                    scroll: false,
                    axis:'x',
                    containment: "parent"
                }),
                slide = $('<div>',{'class':'jvs-hor-slide'}).append($('<span>',{'class':'jvs-hor-slide-left'}),btnCenter,$('<span>',{'class':'jvs-hor-slide-right'})).bind({
                    mousedown: function(e){
                        var slide = $(e.target);
                        if(!slide.is('.jvs-hor-slide')) return;
                        var pos = e.offsetX || e.layerX;
                        if(pos > btnCenter.position().left) moveScrollLeft(contentVisible - 10,1);
                        else moveScrollLeft(contentVisible - 10,-1);
                    }
                }),
                scroll = $('<div>',{'class': 'jvs-hor'}).append(btnLeft,btnRight,slide).addClass('jvs-btnPos-' + ops.btnPos).css('width',ops.size),
                refresh = function(){
                    percent = contentVisible / contentSize;
                    if(percent >= 1){
                        scroll.hide();
                        visibles.h = false;
                        selector.removeClass('visible-x');
                        return;
                    }
                    selector.addClass('visible-x');
                    scroll.show();
                    visibles.h = true;
                    
                    
                    contentScroll = contentSize - contentVisible;
                    slideSize = slide.width();
                    btnSlideSize = slideSize * percent;
                    btnSlideSize = btnSlideSize < ops.minsizebtn ? ops.minsizebtn : btnSlideSize;
                    slideScroll = slideSize - btnSlideSize;
                    
                    
                    btnCenter.stop().animate({
                        'width': btnSlideSize + 1,
                        'left': contentPanel[0].scrollTop / contentScroll * slideScroll
                    });
                }
            ;
            selector.append(scroll)
            setInterval(function(){
                contentSize = contentPanel[0].scrollWidth;
                contentVisible = contentPanel.innerWidth();
                if(contentLastSize === contentSize && contentLastVisible === contentVisible) return;
                contentLastSize = contentSize; contentLastVisible = contentVisible;
                refresh();
            },100);
            
            if(options.showWith === 'hover'){
                selector.hover(function(){
                    isHover = true;
                    visibles.h && scroll.fadeIn(300);
                },function(){
                    isHover = false;
                    visibles.v && isMove || setTimeout(function(){ isMove || isHover || scroll.fadeOut(300);},500);
                });
            }
            contentPanel.scroll(function(){
                if(isMove) return;
                btnCenter.css('left', this.scrollLeft / contentScroll * slideScroll);
            });
            
            var changeScroll = function(to){
                var last = contentPanel[0].scrollLeft;
                contentPanel[0].scrollLeft += 30 * to;
                return last === contentPanel[0].scrollLeft;
            }
            selector.hotkey({
                'left':function(){return changeScroll(-1);},
                'right':function(){ return changeScroll(1);}
            });
            selector.bind({
                mousewheel: function(e,to){
                    if(visibles.v && options.priority === 'vertical') return;
                    return changeScroll(-to)
                }
            })
        })();
        
    }
    scroll.defaults = {}
    return scroll;
})(jQuery);

