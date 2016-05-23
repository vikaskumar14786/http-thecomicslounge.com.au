  jQuery.noConflict();
            jQuery(document).ready(function($) {
		jQuery(function(){
			jQuery('#slides').slides({
                                pagination: true,
				effect: 'fade',
				crossfade: true,
				preload: true,
				preloadImage: base_url_str+'templates/thecomicslounge/images/Pink_background_loading.gif',
				play: 5000,
				pause: 2500,
				hoverPause: true
			});
		});});
            function ChangeGallery(id)
            {
                //alert('OnClick Called'+id);
                jQuery.ajax({
                url: base_url_str+"index2.php?option=com_imagegallery&task=changegallery&id="+id,
                success: function(data){
			jQuery('#galleryResponce').html(data);	
                //alert('Ajax Called');
            }
            });
            }    