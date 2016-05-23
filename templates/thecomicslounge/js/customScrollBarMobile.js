/*
 * Copyright (c) 2012, kartogram
 *
 * Version 1.2 - mobile browser support for touch
 *
 * What's new: full touch support for the content div allowing to scroll the area without using extra buttons
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 * Includes detectmobilebrowser.js
 * http://detectmobilebrowser.com
 * Created by Chad Smith
 */

(function(a){jQuery.browser.mobile=/android.+mobile|android|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ipod|iphone|ipad|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|playbook|plucker|pocket|psp|silk|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))})(navigator.userAgent||navigator.vendor||window.opera);

(function ($) { 
	$.fn.mCustomScrollbarMobile	= function (scrollType,scrollByDistance,scrollByPixels){
		var id = $(this).attr("id");
		var $scrollUpBtn=$("#"+id+" .scrollUpBtn");
		var $scrollDownBtn=$("#"+id+" .scrollDownBtn");
		var $dragThis = $("#"+id+" .dragger");
		var $scrollThis = $("#"+id+" .container");
		///touch variables
		var spp = 0; //start pointer position
		var cpp = 0; //current pointer position
		var mb = scrollByPixels; //distance to move at each moved pixel
		var tmbt = 0; //temporary dragger distance variable
		if(scrollType == "vertical"){
			var $scrollContHeight = $("#"+id+" .customScrollBox").height();
			var $scrollHeight = $("#"+id+" .container").height();
			var $dragContHeight = $("#"+id+" .dragger_container").height();
			var $dragHeight = $("#"+id+" .dragger").height();
			var $dragCof = ($scrollHeight - $scrollContHeight) / ($dragContHeight - $dragHeight);
			var dragDiff = $dragContHeight - $dragHeight;
			var scrollDiff = $scrollHeight - $scrollContHeight;
			$scrollUpBtn.unbind("mouseup, mousedown");
			$scrollDownBtn.unbind("mouseup, mousedown");
			$scrollUpBtn.bind("click", function(){scroll_up();});
			$scrollDownBtn.bind("click", function(){scroll_down();});
			function scroll_up(){
				scrollBackThis = -$scrollThis.position().top;
				dragBackThis = $dragThis.position().top;
				if(scrollBackThis <= scrollByDistance && scrollBackThis > 0){
					$scrollThis.animate({top:"+="+scrollBackThis});
					$dragThis.animate({top:"-="+dragBackThis});
				}else if(scrollBackThis > scrollByDistance){
					$scrollThis.animate({top:"+="+scrollByDistance+"px"});
					$dragThis.animate({top:"-="+dragBackThis/$dragCof});
				}
			}
			function scroll_down() {
				scrollDownThis = $scrollThis.position().top + scrollDiff;
				dragDownThis = dragDiff - $dragThis.position().top;
				if(scrollDownThis <= scrollByDistance && scrollDownThis > 0){
					$scrollThis.animate({top:-scrollDiff});
					$dragThis.animate({top:"+="+dragDownThis});
				}else if(scrollDownThis > scrollByDistance){
					dragDown = scrollByDistance / $dragCof;
					$scrollThis.animate({top:"-="+scrollByDistance+"px"});
					$dragThis.animate({top:"+="+dragDown});
				}
			}
			$("#"+id+" .container").bind('touchstart touchmove touchend',function(event){
        			console.log(event.type);
        			if (event.type == "touchstart") {
					//event.preventDefault();
					$scrollThis.stop();
					spp = event.originalEvent.touches[0].pageY;
        			}
        			if (event.type == "touchmove") {
					var touch = event.originalEvent.touches[0] || event.originalEvent.changedTouches[0];
					mpp = touch.pageY;
					mbt = mb/$dragCof;
					tmbt = tmbt + mbt;
					cc=(tmbt<1)?0:1;
					$scrollThis.stop().css({top: (mpp < spp) ? $(this).position().top - mb : $(this).position().top + mb});
					$dragThis.stop().css({top: (mpp > spp) ? $dragThis.position().top - cc : $dragThis.position().top + cc});					
					spp = touch.pageY;
					tmbt=(tmbt>=1)?0:tmbt;
					if( navigator.userAgent.match(/Android/i) ) {
   	 					event.preventDefault();
  					}
				}
				if (event.type == "touchend") {
					$scrollThis.stop();
					$dragThis.stop().animate({top:-$scrollThis.position().top / $dragCof});
					if($scrollThis.position().top > 0){
						$scrollThis.stop().animate({top: "0px"},{easing : "easeOutCirc"});
						$dragThis.stop().animate({top: "0px"},{easing : "easeOutCirc"});
					}
					if($scrollThis.position().top < -scrollDiff ){
						$scrollThis.stop().animate({top: -scrollDiff+"px"},{easing : "easeOutCirc"});
						$dragThis.stop().animate({top: dragDiff+"px"},{easing : "easeOutCirc"});
					}
        			}
				
    			});
		}
		if(scrollType == "horizontal"){
			var $scrollContWidth = $("#"+id+" .customScrollBox").width();
			var $scrollWidth = $("#"+id+" .container").width();
			var $dragContWidth = $("#"+id+" .dragger_container").width();
			var $dragWidth = $("#"+id+" .dragger").width();
			var $dragCof = ($scrollWidth - $scrollContWidth) / ($dragContWidth - $dragWidth);
			var dragDiff = $dragContWidth - $dragWidth;
			var scrollDiff = $scrollWidth - $scrollContWidth;
			$scrollUpBtn.unbind("mouseup, mousedown");
			$scrollDownBtn.unbind("mouseup, mousedown");
			$scrollUpBtn.bind("click", function(){scroll_left();});
			$scrollDownBtn.bind("click", function(){scroll_right();});

			function scroll_left(){
				scrollBackThis = -$scrollThis.position().left;
				dragBackThis = $dragThis.position().left;
				if(scrollBackThis <= scrollByDistance && scrollBackThis > 0){
					$scrollThis.stop().animate({left:"+="+scrollBackThis});
					$dragThis.stop().animate({left:"-="+dragBackThis});
				}else if(scrollBackThis > scrollByDistance){
					$scrollThis.stop().animate({left:"+="+scrollByDistance+"px"});
					$dragThis.stop().animate({left:"-="+scrollByDistance/$dragCof});
				}
			}
			function scroll_right(){
				scrollRightThis = $scrollThis.position().left + scrollDiff;
				dragRightThis = dragDiff - $dragThis.position().left;
				if(scrollRightThis <= scrollByDistance && scrollRightThis > 0){
					$scrollThis.stop().animate({left:-scrollDiff});
					$dragThis.stop().animate({left:"+="+dragRightThis});
				}else if(scrollRightThis > scrollByDistance){
					dragRight = 300 / $dragCof;
					$scrollThis.stop().animate({left:"-="+scrollByDistance+"px"});
					$dragThis.stop().animate({left:"+="+dragRight});
				}
			}
			$("#"+id+" .container").bind('touchstart touchmove touchend',function(event){
        			console.log(event.type);
        			if (event.type == "touchstart") {
					//event.preventDefault();
					$scrollThis.stop();
					spp = event.originalEvent.touches[0].pageX;
        			}
        			if (event.type == "touchmove") {
					var touch = event.originalEvent.touches[0] || event.originalEvent.changedTouches[0];
					mpp = touch.pageX;
					mbt = mb/$dragCof;
					tmbt = tmbt + mbt;
					cc=(tmbt<1)?0:1;
					$scrollThis.stop().css({left: (mpp < spp) ? $(this).position().left - mb : $(this).position().left + mb});
					$dragThis.stop().css({left: (mpp > spp) ? $dragThis.position().left - cc : $dragThis.position().left + cc});					
					spp = touch.pageX;
					tmbt=(tmbt>=1)?0:tmbt;
					if( navigator.userAgent.match(/Android/i) ) {
   	 					event.preventDefault();
  					}
    			
				}
				if (event.type == "touchend") {
					$scrollThis.stop();
					$dragThis.stop().animate({left:-$scrollThis.position().left / $dragCof});
					if($scrollThis.position().left > 0){
						$scrollThis.stop().animate({left: "0px"},{easing : "easeOutCirc"});
						$dragThis.stop().animate({left: "0px"},{easing : "easeOutCirc"});
					}
					if($scrollThis.position().left < -scrollDiff ){
						$scrollThis.stop().animate({left: -scrollDiff+"px"},{easing : "easeOutCirc"});
						$dragThis.stop().animate({left: dragDiff+"px"},{easing : "easeOutCirc"});
					}
        			}
    			});
		}
	}
})(jQuery);