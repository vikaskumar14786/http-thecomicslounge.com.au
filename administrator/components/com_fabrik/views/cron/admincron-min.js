var CronAdmin=new Class({Extends:PluginManager,Implements:[Options,Events],options:{plugin:""},initialize:function(a){plugins=[];this.parent(plugins);this.setOptions(a);this.watchSelector()},watchSelector:function(){if(typeof(jQuery)!=="undefined"){jQuery("#jform_plugin").bind("change",function(a){this.changePlugin(a)}.bind(this))}document.id("jform_plugin").addEvent("change",function(a){a.stop();this.changePlugin(a)}.bind(this))},changePlugin:function(b){var a=new Request.HTML({url:"index.php",data:{option:"com_fabrik",task:"cron.getPluginHTML",format:"raw",plugin:b.target.get("value")},update:document.id("plugin-container"),onComplete:function(){this.updateBootStrap()}.bind(this)}).send()}});