 /**
# plugin system jvjquerylib - JV JQuery Libraries
# @versions: 1.5.x,1.6.x,1.7.x,2.5.x
# ------------------------------------------------------------------------
# author    Open Source Code Solutions Co
# copyright Copyright (C) 2011 joomlavi.com. All Rights Reserved.
# @license - http://www.gnu.org/licenseses/gpl-3.0.html GNU/GPL or later
# Websites: http://www.joomlavi.com
# Technical Support:  http://www.joomlavi.com/my-tickets.html
-------------------------------------------------------------------------*/

// Autosize 1.10 - jQuery plugin for textareas
// (c) 2012 Jack Moore - jacklmoore.com
// license: www.opensource.org/licenses/mit-license.php
(function(e){var t="hidden",n="border-box",r="lineHeight",i='<textarea tabindex="-1" style="position:absolute; top:-9999px; left:-9999px; right:auto; bottom:auto; -moz-box-sizing:content-box; -webkit-box-sizing:content-box; box-sizing:content-box; word-wrap:break-word; height:0 !important; min-height:0 !important; overflow:hidden">',s=["fontFamily","fontSize","fontWeight","fontStyle","letterSpacing","textTransform","wordSpacing","textIndent"],o="oninput",u="onpropertychange",a=e(i)[0];a.setAttribute(o,"return"),e.isFunction(a[o])||u in a?(e(a).css(r,"99px"),e(a).css(r)==="99px"&&s.push(r),e.fn.autosize=function(r){return this.each(function(){function g(){var e,n;p||(p=!0,l.value=a.value,l.style.overflowY=a.style.overflowY,l.style.width=f.css("width"),l.scrollTop=0,l.scrollTop=9e4,e=l.scrollTop,n=t,e>h?(e=h,n="scroll"):e<c&&(e=c),a.style.overflowY=n,a.style.height=e+m+"px",setTimeout(function(){p=!1},1))}var a=this,f=e(a),l,c=f.height(),h=parseInt(f.css("maxHeight"),10),p,d=s.length,v,m=0;if(f.css("box-sizing")===n||f.css("-moz-box-sizing")===n||f.css("-webkit-box-sizing")===n)m=f.outerHeight()-f.height();if(f.data("mirror")||f.data("ismirror"))return;l=e(i).data("ismirror",!0).addClass(r||"autosizejs")[0],v=f.css("resize")==="none"?"none":"horizontal",f.data("mirror",e(l)).css({overflow:t,overflowY:t,wordWrap:"break-word",resize:v}),h=h&&h>0?h:9e4;while(d--)l.style[s[d]]=f.css(s[d]);e("body").append(l),u in a?o in a?a[o]=a.onkeyup=g:a[u]=g:a[o]=g,e(window).resize(g),f.bind("autosize",g),g()})}):e.fn.autosize=function(){return this}})(jQuery);
window.CustomField = window.CustomField || window.jQuery ? (function ($) {   
    var 
        counteditor = 0,
        custom = function (config) {
            if ($.type(config) !== 'object') return false;
            if (config.use) {
                var 
                    use = fieldLoops[config.use],
                    item = use.item
                ;
                config = $.extend({},use, config);
                use.item = item;
                config.item = item;
            }
            if (config.loop) {
                fieldLoops[config.loop] = config;
                delete config.loop;
            }
            if (!config.field) {
                config = { field: 'panel', item: config }
            }
            var fn = custom[config.field];
            if (!fn) {
                console.log("not support field " + config.field);
                return false;
            };
            var field = fn.call(config);
            field.trigger('ct_created',[field]);
            field.data().config = config;
            return field.addClass('ct');
        },
        parseDataType = function(type,value){
            return parseDataType[type](value);
        },
        formfield = function () {
            var 
                label = addAttrs.call(this, $('<label>')),
                title = $('<span>').text(this.label),
                field = $('<' + this.field + (this.type ? ' type="' + this.type + '"' : '') + '>'),
                datatype = this.datatype || 'string'
            ;
            label.append(title, field);
            label = (this.type || '').toLowerCase() == 'hidden' ? field : label;

            // data function
            label.data().data = function (val) {
                if (val != undefined) {
                    if(val === true) val = 1;
                    else if(val === false) val = 0;
                    else if($.type(val) === 'string')  val = val.replace(/%3C/g,'<').replace(/%3E/g,'>');
                    field.val(val);
                    label.data().lastdata = val;
                    return;
                }
                return datatype === 'string'?field.val().replace(/</g,'%3C').replace(/>/g,'%3E'): parseDataType(datatype, field.val());
            }
            field.bind(this.events);
            this.value != undefined && field.val(this.value);
            var valid;
            if(this.validates){
                var param = $.extend({},this.validates);
                param.errorDisplay = $.extend({title: this.label},param.errorDisplay);
                valid = new Validate.Validate(field, param);
                field.bind('errors',function(){ if(field.is(':visible')) valid.showStatus();});
            } 
            label.data().validate = function(){
                if(valid) return valid.validate()?0:1;
                return 0;
            }
            return label;
        },
        countTab = 0,
        attrs = {
            'style': 'style',
            'class': 'class',
            'id': 'id',
            'title':'title',
            'alt':'alt',
            'rel': 'rel'
        },
        fieldLoops = {},
        eventLoops = {},
        addAttrs = function (item) {
            var This = this;
            $.each(attrs, function (index, val) {
                if (This[val]) item.attr(index, This[val]);
            });
            return item;
        },
        notification = function(errors,to,toggle,field){
            if(errors == 0 || !field.is(':visible') || toggle.is(':visible') ) return;
            to.unbind('click.shownotification').one('click.shownotification',function(){
                pn.fadeOut(300,function(){
                    pn.remove();
                    field.data().validate();
                }); 
            }).children('.ct-notification').remove();
            var pn = $('<a>',{'class': 'ct-notification'})
                .append($('<span>').append(errors))
                .click(function(){
                    to.unbind('click.shownotification')
                    pn.fadeOut(300,function(){
                        pn.remove();
                    });
                    toggle.slideToggle(300,function(){
                        field.data().validate();
                    });
                    return false;
                });
            to.append(pn);
        }
    ;
    $.extend(parseDataType,{
        'bool': function(val){
            return !!parseInt(val);
        },
        'int':function(val){
            return parseInt(val);
        },
        'float': function(val){
            return parseFloat(val);
        },
        'string': function(val){
            return ""+val;
        }
    });
    
    
    $.extend(custom, {
        multi: function () {
            // html
            var 
                This = this,
                head = $('<div>').addClass('ct-multi-head').click(function () { body.slideToggle(300); }).text(this.label),
                btnAdd = $('<a>').attr('href', 'javascript:void(0)').addClass('ct-multi-btnAdd').text('add').click(function () { 
                    addLine().hide().slideDown(300); 
                }).button({icons: { primary: "ui-icon-plusthick" }}),
                items = $('<div>').addClass('ct-multi-items'),
                body = $('<div>').addClass('ct-multi-body').append(items, btnAdd),
                html = addAttrs.call(this, $('<div>')).addClass('ct-multi').append(head, body),
                addLine = function (data) {
                    var 
                        item = new custom(This.item),
                        btnDelete = $('<a>').attr('href', 'javascript:void(0)').addClass('ct-multi-btnDelete  ct-btn').click(function () {
                            if(!html.triggerHandler('ct_removeline', [item])  === false) return;
                            line.remove();
                        }),
                        btnFilter = This.filter !== undefined ? $('<a>').attr('href', 'javascript:void(0)').addClass('ct-multi-line-btnFilter  ct-btn').click(function () {
                            if(html.triggerHandler('ct_checkedchange', [item]) === false) return
                            line.toggleClass('ct-checked');
                        }) : undefined,
                        controlers = $('<div>').addClass('ct-multi-line-controlers').append(btnDelete, btnFilter).mousemove(function () { return false }),
                        controlPanel = $('<div>').addClass('ct-multi-line-controler').append(controlers),
                        head = $('<div>').addClass('ct-multi-line-head'),
                        linebody = $('<div>').addClass('ct-multi-line-body').append(item),
                        line = $('<div>').addClass('ct-multi-line').append(head, linebody,controlPanel).bind({
                            mouseenter: function () { controlers.stop().animate({ 'opacity': 1 }, 300); },
                            mouseleave: function () { controlers.stop().animate({ 'opacity': 0 }, 300); }
                        }).mouseover(function () {
                            line.addClass('mouseenter');
                            return false;
                        }).mouseout(function () {
                            line.removeClass('mouseenter');
                        })
                    ;
                    btnFilter && line.hasClass('ct-checked') !== This.filter && btnFilter.trigger('click');
                    line.data().data = function (val) {
                        if (!val) {
                            var data = {
                                '@data': item.data().data()
                            };
                            This.filter != undefined && (data['@check'] = line.hasClass('ct-checked'));
                            return data;
                        }

                        btnFilter && val['@check'] != line.hasClass('ct-checked') && btnFilter.trigger('click');
                        return item.data().data(val['@data']);
                    }
                    if(html.triggerHandler('ct_addline', [item, data]) === false) return;
                    data && item.data().data(data);
                    line.data().validate = item.data().validate;
                    return line.appendTo(items);
                }
            ;
            // event                
            this.sortable && items.sortable({ distance: 15, handle: ".ct-multi-line-head", connectWith: this.connect + ">.ct-multi-body>.ct-multi-items" });

            // data function
            html.data().data = function (val) {
                if ($.isArray(val)) {
                    $.each(val, function (index, data) {
                        addLine().data().data(data);
                    });
                    if (head.css('display') != 'none') body.hide();
                    return;
                }
                var data = [];
                items.children().each(function () {
                    var d = $(this).data().data();
                    d != null && data.push(d);
                });
                return data;
            }
            html.bind(this.events);
            html.data().validate = function(){
                var errors = 0;
                items.children().each(function () {
                    errors += $(this).data().validate();
                });
                notification(errors,head,body,html);
                return errors;
            } 
            return html;
        },
        group: function () {
            var 
                config = $.extend({}, this, { field: 'panel' }),
                head = $('<div>').addClass('ct-group-head').append(this.label).click(function () { body.slideToggle(300); }),
                body = new custom(config),
                html = addAttrs.call(this, $('<div>')).addClass('ct-group').append(head, body)
            ;

            //data function
            html.data().data = function (val) {
                if (val) {
                    body.data().data(val);
                    titlefield && titlefield.change();
                    body.hide();
                    return;
                }
                html.data().lastdata = val;
                return body.data().data(val);
            }

            var titlefield;
            if (this.titlefield) {

                titlefield = body;
                var fs = this.titlefield.split('.');
                $.each(fs, function (index, val) {
                    if (!titlefield.data().fields) {
                        titlefield = false;
                        return false;
                    }
                    titlefield = titlefield.data().fields[val];
                    if (!titlefield) return false;
                });
                if (titlefield) {
                    titlefield = titlefield.find('input,textarea,select').first().change(function () {
                        head.text(titlefield.val());
                    });
                    head.text(titlefield.val());
                }
            }
            html.bind(this.events);
            html.data().fields = body.data().fields;
            html.data().validate = function(){
                var errors = body.data().validate();
                notification(errors,head,body,html);
                return errors;
            } 
            return html;
        },
        panel: function () {
            var 
                body = $('<div>').addClass('ct-panel-body'),
                head = $('<div>').addClass('ct-panel-head').append(this.label),
                html = $('<div>').addClass('ct-panel').append(head,body),
                fields = {},
                check
            ;
            if (this.filter != undefined) {
                check = $('<input>', { 'class': 'ct-panel-filter', 'type': 'checkbox' })
                .attr('checked', !!this.filter).change(function () {
                    if (check.is(':checked')) body.removeClass('ct-panel-disabled');
                    else body.addClass('ct-panel-disabled');
                });
                head.prepend(check);
                !this.filter && body.addClass('ct-panel-disabled');
                body.click(function(){
                    check.attr('checked',true).trigger('change');
                });
            }

            this.label || head.remove();
            $.each(this.item || {}, function (index, val) {
                var field = new custom(val);
                if (!field) return;
                val.field == 'separate' ||( fields[index] = field);
                body.append(field);
            });

            this.sortable && html.sortable({ distance: 15 });

            // data function
            html.data().data = function (val) {
                if (check && val) {
                    check.attr('checked', !val._disabled);
                    delete val._disabled;
                }
                val = val || {};
                var data = {};
                $.each(fields, function (index, field) {
                    if(field) data[index] = field.data().data(val[index]);
                });
                html.data().lastdata = val;
                check && !check.is(':checked') && (data._disabled = true);
                return data;
            }
            html.bind(this.events);
            html.data().fields = fields;
            html.data().validate = function(){
                var errors = 0;
                $.each(fields, function (index, field) {
                    if(field.data().validate)errors += field.data().validate();
                });
                return errors;
            }
            return html;
        },
        tabs: function () {
            var 
                ul = $('<ul>').addClass('ct-tabs-head').click(function () {
                    tabs.children('div').slideToggle(300);
                }),
                tabs = addAttrs.call(this, $('<div>')).addClass('ct-tabs').append(ul).tabs(),
                fields = {},
                count = 0
            ;
            $.each(this.item || {}, function (index, config) {
                $.type(config) !== 'string' || (config = fieldLoops[config]);
                var panel = new custom(config);
                if (!panel) return;
                var 
                    id = '#ct-tabs-' + index + '-' + countTab++,
                    newtab = tabs.tabs('add', id, config.label || 'Tabs ' + (++count)),
                    tabPanel = tabs.find(id).addClass('ct-tabs-panel').append(panel)
                ;
                fields[index] = panel;
            });

            //data action
            tabs.data().data = function (val) {
                if (val) {
                    $.each(fields, function (index, field) {
                        if(field) field.data().data(val[index]);
                    });
                    tabs.children('div').hide();
                    tabs.data().lastdata = val;
                    return;
                }
                var data = {};
                $.each(fields, function (index, field) {
                    if(field) data[index] = field.data().data();
                });
                return data;
            }
            tabs.bind(this.events);
            tabs.data().validate = function(){
                var errors = 0;
                $.each(fields, function (index, field) {
                    var thisErrs = field.data().validate();
                    errors += thisErrs;
                    //if(thisErrs > 0 && !field.is(':visible')){
//                        notification(errors,head,field);
//                    }
                });
                return errors;
            };
            return tabs;
        },
        filter: function () {
            var 
                label = $('<span>').append(this.label),
                select = $('<select>').change(function () {
                    $.each(fields, function (index) {
                        this.hide().removeClass('active');
                    });
                    var selected = fields[select.val()];
                    selected && selected.show().addClass('active');
                    html.trigger('ct_filterchanged',[selected,select.val()]);
                    if(body.css('display') == 'none') body.slideToggle(300);
                }),
                head = $('<div>').addClass('ct-filter-head').append(label, select).click(function () { body.slideToggle(300) }),
                body = $('<div>').addClass('ct-filter-body'),
                html = addAttrs.call(this, $('<div>')).addClass('ct-filter').append(head, body),
                fields = {}
            ;
            $.each(this.item || {}, function (index, val) {
                var 
                    panel = new custom(val)
                ;
                if (!panel) return;
                fields[index] = panel;
                var op = $('<option value="' + index + '">').text(val.label || index);
                select.append(op);
                body.append(panel.hide());
            });
            // data function
            html.data().data = function (val) {
                var selected;
                if ($.type(val) === 'object') {
                    selected = val['@selected'];
                    $.each(fields, function (index, field) {
                        if(field) field.data().data(val[index]);
                    });
                    select.val(selected).trigger('change');
                    body.hide();
                    html.data().lastdata = val;
                    return;
                }
                selected = select.val();
                var data = { '@selected': selected };
                $.each(fields, function (index, field) {
                    if(field) data[index] = field.data().data();
                });
                return data;
            }
            select.combobox().prev().click(function(){return false;});
            html.bind(this.events).data().fields = fields;
            this.selected && select.val(this.selected);
            select.trigger('change');
            
            html.data().validate = function(){
                var errors =  fields[select.val()].data().validate();
                notification(errors,head,body,html);
                return errors;
            }  
            return html;
        },
        textarea: function () { 
            var 
                field = formfield.call(this).addClass('ct-textarea'),
                lastHeight
            ; 
            
            field.find('textarea').focusout(function(){
                lastHeight = $(this).height();
                $(this).css('height',''); 
            }).one('focusin',function(){
                var This = $(this).focusin(function(){
                    This.css('height',lastHeight);
                }).autosize();
            });
            return field;
        },
        input: function () { 
            var field = formfield.call(this).addClass('ct-input'); 
            if(this.type == 'checkbox'){
                var check = field.children('input').hide().change(function(){
                    if(check.is(':checked') != button.is('.checked')){
                        toggle();
                    }
                });
                
                
                field.data().data = function(val){
                    if(val != undefined){
                        var lastVal = check.is(':checked');
                        check.attr('checked', val);
                        check.trigger('change');
                        return;
                    }
                    return check.is(':checked');
                }
                
                var 
                    toggle = function(){
                        node.toggleClass('checked',300);
                        button.toggleClass('checked',300).animate({a:1},1,function(){
                                check.attr('checked',button.is('.checked'));
                        });
                    },
                    button = $('<a>',{'class':'ui-checkbox',href:'javascript:void(0)'}).append(node).click(toggle).button(),
                    node = button.children().button()
                ;
                button.append($('<span>',{'class':'ui-checkbox-on'}).text('On'),$('<span>',{'class':'ui-checkbox-off'}).text('Off'),node);
                field.append(button);
                if(this.value){
                    check.attr('checked',true);
                    node.addClass('checked');
                    button.addClass('checked');
                }
            }
            return field;
        },
        select: function () {
            var 
                field = formfield.call(this),
                select = field.children('select'),
                dataFn = field.data().data
            ;
            $.each(this.item || [], function (index, val) {
                select.append($('<option value="' + index + '">' + val + '</option>'));
            });
            select.combobox();
            field.data().data = function(val){
                var value = dataFn(val);
                if(val != undefined) select.trigger('change');
                return value;
            }
            return field.addClass('ct-select');
        },
        html: function(){
            var html = addAttrs(this,$('<div>')).addClass('ct-html');
            html.html(this.html).data().data = function(data){
                return html.find('input,select,textarea').val(data);
            };
            return html.bind(this.events);
        },
        datetime: function(){
            var
                field = formfield.call({field:'input',label: this.label}),
                input = field.children('input').datepicker(this).bind('focusout',function(){
                    input.datepicker('hide');
                })
            ;
            return field.addClass('ct-input');
        },
        color: function(){
            
            var 
                fo = $.extend({},this,{field: 'input'}),
                field = formfield.call(fo),
                input = field.children('input').colorpicker({
                    onOpen: function(a,b,c,d){
                        field.append(c.dialog.css({
                            top: 0,
                            right: 0,
                            left:'auto',
                            'z-index': 100,
                            position: 'fixed'
                        }));
                    },
                    onSelect: function(color,rgb){
                        input.css({
                            'background':color,
                            'color': 'rgb('+ parseInt(255 - rgb.r * 255)+','+parseInt(255 - rgb.g* 255)+','+parseInt(255 - rgb.b* 255)+')'
                        });
                    },
                    parts: ['bar', 'map']
                }),
                fn = field.data().data
            ;
            field.data().data = function(data){
                if(data) input.colorpicker('setColor',data);
                return fn();
            }
            return field.addClass('ct-input');
        },
        autocomplete:function(){
            var 
                This = this,
                field = formfield.call({field:'input', label: this.label}).addClass('ct-input'),
                input = field.children('input').click(function(){return false;}),
                itemsPanel = $('<span>').addClass('ct-autocomplete-items').appendTo(field).append(input).sortable({handle:'span'}).click(function(){
                    if(input.next().length ==0 ) return;
                    itemsPanel.append(input);
                    input.focus();
                }),
                from = This.source || This.item,
                valueData,
                editting = $(),
                allData = [],
                autoSizeInput = function(){
                    input.prop('size',input.val().length +1);
                },
                editItem = function(item){
                    editting = item.after(input).hide();
                    input.val(item.children('span').text())
                    autoSizeInput();
                    input.focus();
                    input.one('focusout',function(){
                        item.show();
                        input.val('');
                        autoSizeInput();
                    });
                    var handle = function(e){
                        if(!(e.which == 27)) return;
                        input.unbind('keyup',handle);
                        input.focusout();
                    }
                    input.bind('keyup',handle);
                },
                addNewItem = function(itemdata){
                    var 
                        item = $('<span>').addClass('ct-autocomplete-item').dblclick(function(){
                            editItem(item);
                        }),
                        btnDelete = $('<a>').addClass('ct-autocomplete-item-btndelete').text('x').click(function(){
                            item.remove();
                        }),
                        label = $('<span>').addClass('ct-autocomplete-item-label').text(itemdata.label || itemdata.value)
                    ;
                    if(itemdata.icon){
                        var icon = $('<img>').addClass('ct-autocomplete-item-icon').attr('src',itemdata.icon);
                        item.prepend(icon);
                    }
                    item.data().jvcustomvalue = itemdata.value;
                    item.append(label,btnDelete);
                    input.before(item).val('');
                    editting.remove();
                },
                loadDataAjax = function(data){
                    if(!data.length) return;
                    var value = data.shift();
                    $.getJSON( from ,{term:value}, function(datas, textStatus, jqXHR){
                        datas = datas[0];
                        addNewItem(datas);
                        loadDataAjax(data);
                    });
                }
            ;
            This.item = This.item || [];
            if(this.multi){
                input.bind('changed',function(e,item){
                    if(!item) return;
                    addNewItem(item);
                }).prop('size',3).keydown(function(e){
                    var inputVal = input.val(); 
                    autoSizeInput();
                    if(e.which == 8 && inputVal.length == 0) input.prev().remove();
                }).keyup(function(){autoSizeInput();});
            }
            $(function(){
                input.autocomplete({
                    minLength: 0,
                    source: from, 
                    focus: function( event, ui ) {return false;},
                    select: function( event, ui ) {
                        input.val( ui.item.label );
                        valueData = ui.item.value;
                        input.attr('title',ui.item.desc );
                        input.trigger('changed',[ui.item]);
                        return false;
                    }
                }).data( "autocomplete" )._renderItem = function( ul, item ) {
                    var icon;
                    if(item.icon) icon = $('<img>').addClass('jvui-icon').attr('src',item.icon);
                    return $( "<li></li>" )
                        .data( "item.autocomplete", item )
                        .append( "<a>" + item.label + (item.desc?"<br>" + item.desc:'') + "</a>" ,icon)
                        .appendTo( ul );
                };
            });
            field.data().data = function(data){
                if(data){
                    if($.type(from) == 'string') {
                        loadDataAjax(data);
                    }else{
                        
                    }
                    return;
                }
                var data = [];
                itemsPanel.children('span').each(function(){
                    data.push($(this).data().jvcustomvalue);
                });
                return data;
            }
            var a = function(data){
                if(data){
                    if(valueData === data) return;
                    if($.type(from) == 'string') {
                        $.getJSON( from ,{term:data}, function(datas, textStatus, jqXHR){
                            datas = datas[0];
                            if(!datas){
                                input.val( data );
                                return;
                            };
                            input.val( datas.label );
                            valueData = datas.value;
                            input.attr('title',datas.desc );
                        });
                    }
                    return;
                }
                return valueData || input.val();
            }
            return field;
        },
        range: function(evt){ 
            var
                slider = $('<div>'),
                numericBox = $('<input>'),
                field = $('<div>').append(numericBox,slider),
                label = $('<span>').append(this.label),
                html = $('<label>',{'class': 'ct-range'}).append(label,field)
            ;
            numericBox.numericBox($.extend({
                events: {change: function(){
                    slider.slider('option', 'value', numericBox.val());
                }}
            },this));                        
            slider[0].slide = null
            slider.slider($.extend({
                orientation: "horizontal",
                animate: 500,
                range: "min",
                change: function(){
                     numericBox.val(slider.slider('option','value'));
                },
                slide: function(){
                    numericBox.val(slider.slider('option','value'));
                }
            },this));
            
            
            html.data().data = function(val){
                if(val != undefined ){
                    slider.slider('option','value',val);
                    numericBox.val(val);
                }
                return numericBox.val();
            }
            return html; 
        },
        separate: function(){
            var 
                line = $('<hr>'),
                label = this.label? $('<span>').append(this.label):!1,
                html = $('<div>',{'class': 'ct-separate'}).append(line,label)
            ;
            return html;
        }
    });
    return custom;
})(jQuery) : (function () {
    var fn = function () {
        alert('Not supported jquery, please import jQuery to use custom params');
    }
    fn();
    return fn;
})();
