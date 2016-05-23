/*
 * @version $Id: shajax.js 599 2010-03-19 17:35:30Z shumisha $
 * @package sh404SEF
 * @copyright Copyright (C) 2008-2010 Yannick Gaultier. All rights reserved.
 * @license GNU/GPL, see LICENSE.php Joomla! is free software. This version may
 *          have been modified pursuant to the GNU General Public License, and
 *          as distributed it includes or is derivative of works licensed under
 *          the GNU General Public License or other free or open source software
 *          licenses. See COPYRIGHT.php for copyright notices and details.
 */

if(typeof(shajax)=="undefined"){var shajax=new Object();}
shajax.enabled=true;shajax.useCache=true;shajax.useCompression=false;shajax.enableDebug=false;shajax.enablePrefetch=true;shajax.maxCacheSize=400000;shajax.shajaxLiveSiteUrl='';shajax.shajaxProgressImage='';shajax.defaultProgressElement='shajaxProgress';shajax.shajaxUrlMap=new Array();shajax.toPrefetch=new Array();shajax.delayToPrefetch=400;shajax.addDOMLoadEvent=(function(){var load_events=[],load_timer,script,done,exec,old_onload,init=function(){done=true;clearInterval(load_timer);while(exec=load_events.shift())
exec();if(script)
script.onreadystatechange='';};return function(func){if(done)
return func();if(!load_events[0]){if(document.addEventListener)
document.addEventListener("DOMContentLoaded",init,false);if(/WebKit/i.test(navigator.userAgent)){load_timer=setInterval(function(){if(/loaded|complete/.test(document.readyState))
init();},10);}
old_onload=window.onload;window.onload=function(){init();if(old_onload)
old_onload();};}
load_events.push(func);}})();shajax.shAjaxifyLinks=function(elementId){shajax.toPrefetch=new Array();var container=elementId?document.getElementById(elementId):document;shajax.compressor.enabled=shajax.useCompression;var links=container.getElementsByTagName('a');if(links){var aRel,aPreFetch,toPrefetchCount=0,reg=new RegExp(" ","g"),relBits=new Array(),uniqueId='',targetElement='',prefetchLink;for(var i=0;i<links.length;i++){aPreFetch=false;aRel=links[i].rel;if(aRel){if(aRel.substr(0,10)=='shajaxLink'){relBits=aRel.split(reg);if(relBits.length==2||relBits.length==3){uniqueId=relBits[0];targetElement=relBits[1];if(relBits.length==3){aPreFetch=relBits[2]=="prefetch";}}
if(shajax.enablePrefetch&&aPreFetch){prefetchLink=links[i].href;if(typeof(shajax.shajaxUrlMap)!='undefined'){if(shajax.shajaxUrlMap[uniqueId]){prefetchLink=shajax.shajaxUrlMap[uniqueId];}}
shajax.toPrefetch[toPrefetchCount]=Array(prefetchLink,targetElement,false);toPrefetchCount++;}
links[i].onclick=shajax.onClickHandler;}}}
if(shajax.enablePrefetch&&toPrefetchCount){var prefetchTime=0;for(var j=0;j<toPrefetchCount;j++){var cached=shajax.getFromCache(shajax.toPrefetch[j][0]);if(!cached){if(!shajax.delayToPrefetch){shajax.shajax(shajax.toPrefetch[j][0],shajax.toPrefetch[j][1],'format=raw&tmpl=component&shajax=1','','cache');}else{prefetchTime+=shajax.delayToPrefetch;window.setTimeout('shajax.delayedPrefetch('+j+')',prefetchTime);}}else{shajax.cacheIncreaseHits(shajax.toPrefetch[j][0]);shajax.toPrefetch[j][2]=true;}}}}};shajax.debug=function(s){if(!shajax.enableDebug){return;}
var el=document.getElementById('pathway');el.innerHTML+=s+'<br />';};shajax.delayedPrefetch=function(prefetchId){var toPrefetchCount=shajax.toPrefetch.length;if(toPrefetchCount){if(!shajax.toPrefetch[prefetchId][2]){shajax.debug('Fetching delayed '+shajax.toPrefetch[prefetchId][0]);shajax.toPrefetch[prefetchId][2]=true;shajax.shajax(shajax.toPrefetch[prefetchId][0],shajax.toPrefetch[prefetchId][1],'format=raw&tmpl=component&shajax=1','','cache');}}};shajax.onClickHandler=function(){var aLink=this.href,aRel=this.rel,aPreFetch,reg=new RegExp(" ","g"),relBits=new Array(),uniqueId='',targetElement='';if(aRel){if(aRel.substr(0,10)=='shajaxLink'){aPreFetch=false;relBits=aRel.split(reg);if(relBits.length==2||relBits.length==3){uniqueId=relBits[0];targetElement=relBits[1];if(relBits.length==3){aPreFetch=relBits[2]=="prefetch";}}}
if(typeof(shajax.shajaxUrlMap)!='undefined'){if(shajax.shajaxUrlMap[uniqueId]){aLink=shajax.shajaxUrlMap[uniqueId];}}
if(shajax.shajaxLiveSiteUrl&&aLink.substr(0,7)!='http://'&&aLink.substr(0,9)=='index.php'){aLink=shajax.shajaxLiveSiteUrl+aLink;}
if(aPreFetch){var cached=shajax.getFromCache(aLink);if(cached){shajax.cacheIncreaseHits(aLink);var target=document.getElementById(targetElement);if(target){target.innerHTML=cached;}
shajax.redoLinks(targetElement);shajax.postDisplayAction(targetElement);return false;}}
shajax.shajax(aLink,targetElement,'format=raw&tmpl=component&shajax=1','shajaxProgress'+uniqueId.substr(10),'page');}
return false;};function GetXmlHttp(){var xmlhttp=false;if(window.XMLHttpRequest){xmlhttp=new XMLHttpRequest()}else if(window.ActiveXObject)
{try{xmlhttp=new ActiveXObject("Msxml2.XMLHTTP")}catch(e){try{xmlhttp=new ActiveXObject("Microsoft.XMLHTTP")}catch(E){xmlhttp=false}}}
return xmlhttp;}
shajax.shajax=function(targetUrl,elementId,params,progress,dest){var xmlHttp=new GetXmlHttp();if(progress&&!document.getElementById(progress)){progress=shajax.defaultProgressElement;}
if(xmlHttp){if(dest=='page'){shajax.showProgress(progress,true);}
var connector=targetUrl.indexOf('?')==-1?'?':'&';params=params?connector+params:params;xmlHttp.open('GET',targetUrl+params,true);if(typeof(xmlHttp.setRequestHeader)!="undefined"){xmlHttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');}
xmlHttp.onreadystatechange=function(){if(xmlHttp.readyState==4){if(dest=='page'){shajax.showProgress(progress,false);}
if(xmlHttp.status==200){shajax.putInCache(targetUrl,xmlHttp.responseText);if(dest=='page'){var target=document.getElementById(elementId);if(target){target.innerHTML=xmlHttp.responseText;}
shajax.redoLinks(elementId);shajax.postDisplayAction(elementId);}}}}
xmlHttp.send(null);}};shajax.redoLinks=function(elementId){var shMap=document.getElementById('shajaxRebuildUrlMap'+elementId);var html;if(shMap){html=shMap.innerHTML;}
if(html){html=html.replace(/<script type="text\/javascript">/i,'');html=html.replace(/<SCRIPT type=text\/javascript>/i,'');html=html.replace(/<\/script>/i,'');html=html.replace('<!--/*--><!\[CDATA\[//><!--','');html=html.replace(/\/\/--><!]]>/,'');eval(html);}
shajax.shAjaxifyLinks(elementId);};shajax.postDisplayAction=function(elementId){var shAction=document.getElementById('shajaxPostDisplayAction'+elementId);var html;if(shAction){html=shAction.innerHTML;}
if(html){html=html.replace(/<script type="text\/javascript">/i,'');html=html.replace(/<SCRIPT type=text\/javascript>/i,'');html=html.replace(/<\/script>/i,'');html=html.replace('<!--/*--><!\[CDATA\[//><!--','');html=html.replace(/\/\/--><!]]>/,'');eval(html);}
shajax.resetPopups('a.jcal_modal');};shajax.resetPopups=function(selector){if(typeof($$)!='undefined'){$$(selector).each(function(el){el.addEvent('click',function(e){new Event(e).stop();SqueezeBox.fromElement(el);});});}};shajax.putInCache=function(reference,data){if(!shajax.useCache){return;}
var current=document.getElementById(reference);var dataNode=document.createTextNode(shajax.compressor.compress(data));var ts=new Date();var tsDataNode=document.createTextNode(ts.getTime());if(!current){var cache=document.getElementById('shajaxCache');if(!cache){cache=shajax.createCache();}else{if(!shajax.cacheCheckSize(cache,dataNode.length)){return;}}
var item=document.createElement('div');item.setAttribute('id',reference);item.style.display="none";item.appendChild(dataNode);item.appendChild(tsDataNode);var hitCounterNode=document.createTextNode("1");item.appendChild(hitCounterNode);cache.appendChild(item);}else{var currentDataNode=current.childNodes[0];if(currentDataNode){var currentTS=current.childNodes[1];var currentHitCounter=current.childNodes[2];current.replaceChild(dataNode,currentDataNode);current.replaceChild(tsDataNode,currentTS)
currentHitCounter.data="1";}}};shajax.cacheIncreaseHits=function(reference){if(reference){var item=document.getElementById(reference);if(item){var currentHitCounter=item.childNodes[2];currentHitCounter.data=String(Number(currentHitCounter.data)+1);}}};shajax.cacheCheckSize=function(cache,needed){var count=cache.childNodes.length;var total=0,c=new Array();for(var i=0;i<count;i++){c[i]={"size":cache.childNodes[i].childNodes[0].length,"tStamp":cache.childNodes[i].childNodes[1].data,"hits":cache.childNodes[i].childNodes[2].data,"id":i};total=total+Number(c[i].size);}
var available=shajax.maxCacheSize-total;if(available<needed){function compareCacheItems(c1,c2){if(c1.hits<c2.hits){return-1}
if(c1.hits>c2.hits){return 1}
if(c1.tStamp<c2.tStamp){return-1}
if(c1.tStamp>c2.tStamp){return 1}
if(c1.size<c2.size){return 1}
if(c1.size>c2.size){return-1}
return 0}
c.sort(compareCacheItems);var i=0;do{available=available+Number(c[i].size);cache.removeChild(cache.childNodes[c[i].id]);i++;}while((available<needed)&&(i<c.length))}
return(available>needed);};shajax.getFromCache=function(reference){if(!shajax.useCache||!reference){return'';}
var item=document.getElementById(reference);if(item){return shajax.compressor.decompress(item.firstChild.data);}
return'';};shajax.resetCache=function(){var cache=document.getElementById('shajaxCache');if(!cache){return'';}
if(cache.hasChildNodes()){while(cache.childNodes.length>=1){cache.removeChild(cache.firstChild);}}};shajax.createCache=function(){var cacheRoot=document.createElement("div");cacheRoot.setAttribute('id','shajaxCache');cacheRoot.style.display="none";document.body.appendChild(cacheRoot);return cacheRoot;};shajax.showProgress=function(progress,state){if(typeof this.save=='undefined'){this.save='';}
if(state){this.save=document.getElementById(progress).innerHTML;document.getElementById(progress).innerHTML=shajax.shajaxProgressImage;}else{document.getElementById(progress).innerHTML=this.save;}};shajax.compressor={enabled:true,compress:function(s){if(!shajax.compressor.enabled||s=="")
return s;var dict={};var data=(s+"").split("");var out=[];var currChar;var phrase=data[0];var code=256;for(var i=1;i<data.length;i++){currChar=data[i];if(dict[phrase+currChar]!=null){phrase+=currChar;}else{out.push(phrase.length>1?dict[phrase]:phrase.charCodeAt(0));dict[phrase+currChar]=code;code++;phrase=currChar;}}
out.push(phrase.length>1?dict[phrase]:phrase.charCodeAt(0));for(var i=0;i<out.length;i++){out[i]=String.fromCharCode(out[i]);}
return out.join("");},decompress:function(s){if(!shajax.compressor.enabled)
return s;var dict={};var data=(s+"").split("");var currChar=data[0];var oldPhrase=currChar;var out=[currChar];var code=256;var phrase;for(var i=1;i<data.length;i++){var currCode=data[i].charCodeAt(0);if(currCode<256){phrase=data[i];}else{phrase=dict[currCode]?dict[currCode]:(oldPhrase+currChar);}
out.push(phrase);currChar=phrase.charAt(0);dict[code]=oldPhrase+currChar;code++;oldPhrase=phrase;}
return out.join("");}};if(shajax.enabled){shajax.addDOMLoadEvent(shajax.shAjaxifyLinks);}