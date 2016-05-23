jQuery(window).load(function() {
//alert("Pageload Done");
jQuery('#showloadingimage').hide();	
jQuery('#mcs5_container').show();
	mCustomScrollbars();
});

function mCustomScrollbars(){
	/* 
	malihu custom scrollbar function parameters: 
	1) scroll type (values: "vertical" or "horizontal")
	2) scroll easing amount (0 for no easing) 
	3) scroll easing type 
	4) extra bottom scrolling space for vertical scroll type only (minimum value: 1)
	5) scrollbar height/width adjustment (values: "auto" or "fixed")
	6) mouse-wheel support (values: "yes" or "no")
	7) scrolling via buttons support (values: "yes" or "no")
	8) buttons scrolling speed (values: 1-20, 1 being the slowest)
	*/
	//$("#mcs_container").mCustomScrollbar("vertical",400,"easeOutCirc",1.05,"auto","yes","yes",10); 
	//$("#mcs2_container").mCustomScrollbar("vertical",0,"easeOutCirc",1.05,"auto","yes","no",0); 
	//$("#mcs3_container").mCustomScrollbar("vertical",900,"easeOutCirc",1.05,"auto","no","no",0); 
	//$("#mcs4_container").mCustomScrollbar("vertical",200,"easeOutCirc",1.25,"fixed","yes","no",0); 
	jQuery("#mcs5_container").mCustomScrollbar("horizontal",500,"easeOutCirc",1,"fixed","yes","yes",225);
	var isiPad = navigator.userAgent.match(/iPad/i) != null;
	var ua = navigator.userAgent;
        var isiPad = /iPad/i.test(ua);
        if(isiPad == true) {
	   jQuery("#mcs5_container").mCustomScrollbarMobile("horizontal",400,"easeOutCirc",1,"fixed","yes","yes");
	   
        } else {
	    jQuery("#mcs5_container").mCustomScrollbar("horizontal",500,"easeOutCirc",1,"fixed","yes","yes",225);  
	}

}
/* Call orientation function on orientation change */
	jQuery(window).bind( 'orientationchange', function(e){
	    //jQuery("#mcs5_container").mCustomScrollbarMobile("horizontal",400,"easeOutCirc",1,"fixed","yes","yes");
	    mCustomScrollbars();
	});

/* function to fix the -10000 pixel limit of jquery.animate */
jQuery.fx.prototype.cur = function(){
    if ( this.elem[this.prop] != null && (!this.elem.style || this.elem.style[this.prop] == null) ) {
      return this.elem[ this.prop ];
    }
    var r = parseFloat( jQuery.css( this.elem, this.prop ) );
    return typeof r == 'undefined' ? 0 : r;
}

/* function to load new content dynamically */
function LoadNewContent(id,file){
	jQuery("#"+id+" .customScrollBox .content").load(file,function(){
		mCustomScrollbars();
	});
}