 jQuery.noConflict();
            jQuery(document).ready(function($) {
			jQuery(function(){
			jQuery('#slides').slides({
                                    
                pagination: true,
				preload: true,
				preloadImage: root_url+'templates/comicslounge/images/Pink_background_loading.gif',
				play: 5000,
                                effect: "fade",
				pause: 2500,
				hoverPause: true,animationStart: function(current){
				  jQuery('.caption').animate({
						bottom:-35
					},200);
				if (window.console && console.log) {
					// example return of current slide number
					console.log('animationStart on slide: ', current);
				};
				},
				animationComplete: function(current){
				  		jQuery('.caption').animate({
							bottom:0
				},200);
				if (window.console && console.log) {
						// example return of current slide number
						console.log('animationComplete on slide: ', current);
				};
				},
				slidesLoaded: function() {
					jQuery('.caption').animate({
						bottom:0
					},200);
				}
			});
		});});