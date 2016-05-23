<?php
/*------------------------------------------------------------------------	
# mod_responsivegallery - Responsive Photo Gallery for Joomla 3.x v2.9.3	
# ------------------------------------------------------------------------	
# author    GraphicAholic	
# copyright Copyright (C) 2011 GraphicAholic.com. All Rights Reserved.	
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL	
# Websites: http://www.graphicaholic.com	
-------------------------------------------------------------------------*/

// No direct access	
defined('_JEXEC') or die('Restricted access');	
$document = JFactory::getDocument();	
$path				= $params->get('path');	
$containerMargin	= trim($params->get('containerMargin', '0px'));	
$imageFeed			= $params->get('imageFeed');	
$thumbratio			= $params->get('thumbratio', 1) ? true : false;	
$thumbwidth			= trim($params->get('thumbwidth', 64));	
$thumbheight		= trim($params->get('thumbheight', 44));	
$galleryNumber		= ($params->get('galleryNumber', 1));	
$flickrAPI			= $params->get('flickrAPI');	
$flickrCache		= $params->get('flickrCache', 1);	
$flickrSecret		= $params->get('flickrSecret', '');	
$flickrToken		= $params->get('flickrToken', '');	
$flickrPrivate		= $params->get('flickrPrivate', 0);	
$flickrSet			= $params->get('flickrSet');	
$flickrNumber		= $params->get('flickrNumber', '');	
$flickrThumb		= $params->get('flickrThumb');	
if($flickrThumb == "1") $flickrThumb = "square";	
if($flickrThumb == "2") $flickrThumb = "largesquare";	
if($flickrThumb == "3") $flickrThumb = "thumbnail";	
$flickrCaption		= $params->get('flickrCaption', 2);	
$autoPlay			= $params->get('autoPlay');	
$infiniteLoop		= $params->get('infiniteLoop');	
$showJtitle			= $params->get('showJtitle');	
$picasaUser			= $params->get('picasaUser');	
$user_albumid		= $params->get('user_albumid');	
$photoSize			= $params->get('photoSize');	
$picasaPhoto		= $params->get('picasaPhoto');	
$picasaTitle		= $params->get('picasaTitle', 1);	
$picasaTag			= $params->get('picasaTag');	
$styles				= $params->get('styles');	
?>

	<?php if ($autoPlay == "autoplayYes") : ?>
		<script type="text/javascript">
			jQuery(function(){
				doTimer()
			})
			var t;
			var timer_is_on=0;

			function timedCount()
			{
			jQuery('.rg-image-nav-next').click()
			t=setTimeout("timedCount()",<?php echo $infiniteLoop; ?>);
			}

			function doTimer()
			{
			if (!timer_is_on)
			{
			timer_is_on=1;
			timedCount(<?php echo $infiniteLoop; ?>);
			}
			}

			function stopCount()
			{
			clearTimeout(t);
			timer_is_on=0;
			}
		</script>
	<?php endif ; ?>	
	<?php if ($imageFeed == "5"): ?>
			<style type="text/css">
				.rg-image img{
					max-height:<?php echo $params->get('maxHeight') ?> !important;
					max-width:<?php echo $params->get('maxWidth') ?> !important;
				} 
			</style>
		<noscript>
			<style type="text/css">
				.es-carousel<?php echo $moduleID; ?> ul{
					display:block;
				} 
			</style>
		</noscript>
		<script id="img-wrapper-tmpl" type="text/x-jquery-tmpl">	
			<div class="rg-image-wrapper">
				{{if itemsCount > 1}}
					<div class="rg-image-nav">
						<a href="#" class="rg-image-nav-prev">Previous Image</a>
						<a href="#" class="rg-image-nav-next">Next Image</a>
					</div>
				{{/if}}
				<div class="rg-image"></div>
				<div class="rg-loading"></div>
				<div class="rg-caption-wrapper">
					<div class="rg-caption" style="display:none;">
						<p></p>
					</div>
				</div>
			</div>			
		</script>
			<div class="contentText" style="margin-top:<?php echo $containerMargin; ?>">
		<script type="text/javascript">
			jQuery(function(){
				jQuery('#rg-gallery<?php echo $moduleID; ?>').rgallery({
					module: <?php echo $moduleID; ?>,
					position: '<?php echo $params->get('esPosition') ?>',
					mode: '<?php echo $params->get('displayThumbs') ?>'
				});
			});
		</script>
				<div id="rg-gallery<?php echo $moduleID; ?>" class="rg-gallery">
		<?php if ($styles == "custom"): ?>
			<style type="text/css">
				.rg-image-wrapper {background-color: <?php echo $params->get('backgroundSkin') ?> !important; background-image: none !important; border-radius: <?php echo $params->get('borderRadius') ?>;}
				.rg-image-nav a {background-color: <?php echo $params->get('backgroundSkin') ?> !important; background-image: ("../images/nav.png") !important;}
				.es-carousel {background-color: <?php echo $params->get('backgroundSkin') ?> !important;}
				.es-carousel-wrapper {background-color: <?php echo $params->get('backgroundSkin') ?> !important;}
				.rg-caption p {color: <?php echo $params->get('textColor') ?> !important;}
			</style>
		<?php endif ; ?>
				<?php if ($autoPlay == "autoplayYes") : ?>
					<div id="buttons">
					<div class="playbutton"><a href="javascript:doTimer();"><img src="modules/mod_responsivegallery/images/playButton.png" alt="Play" alt="Play"></a></div>
					<div class="pausebutton"><a href="javascript:stopCount();"><img src="modules/mod_responsivegallery/images/pauseButton.png" alt="Pause" alt="Pause"></a></div>
						</div>
				<?php endif ; ?>
				<?php if ($autoPlay == "0") : ?>
					<div id="buttons">
					<div class="noplaybutton"></div>
					<div class="nopausebutton"></div>
					</div>
				<?php endif ; ?>
					<div class="rg-thumbs">
						<div class="es-carousel-wrapper">
							<div class="es-nav">
								<span class="es-nav-prev">Previous</span>
								<span class="es-nav-next">Next</span>
							</div>
							<div class="es-carousel">
		<style>.es-carouse<?php echo $moduleID; ?> ul li a img{
			width: <?php echo $params->get('heightRatio') ?>;
			height: <?php echo $params->get('heightRatio') ?>;			
			}
		</style>	
	<div id="gallery">		
	<ul>
	<?php 
	$imagesJSON = new stdClass();
	if ($params->get('data_source.images')){
		$imagesJSON = json_decode($params->get('data_source.images'));
	}
	$folder = $params->get('data_source.folder');
	$images = array();
	foreach ($imagesJSON as $img){
		$images[$img->position] = $img;
	}
	ksort($images);
	foreach ($images as $k=>$image){	
		if ($params->get('thumbnail_mode') != 'none'){
			$imageCache = modResponsiveGalleryHelper::renderImage($folder.'/'.$image->image, $params);
		}else{
			$imageCache = $LiveSite. $folder.'/'.$image->image;
		}		
	?>	
			<li>					
				<a href="#"><img src="<?php echo $imageCache ?>" style="height:<?php echo $params->get('heightRatio') ?>; width:<?php echo $params->get('heightRatio') ?>; margin-top:-30px;" data-large="<?php echo $imageCache ?>" alt="<?php echo $image->title ?>" data-description="<?php echo $image->title ?>&nbsp;<?php echo $image->description; ?>" data-href="<?php echo $imageCache ?>" />
				</a>
			</li>		
	<?php } ?>
	</ul>
	</div>
							</div>
						</div>
					</div>
				</div>			
			</div>
	<?php endif ; ?>
	<?php if ($imageFeed == "4"): ?>
			<style type="text/css">
				.rg-image img{
					max-height:<?php echo $params->get('maxHeight') ?> !important;
					max-width:<?php echo $params->get('maxWidth') ?> !important;
				} 
			</style>
		<noscript>
			<style type="text/css">
				.es-carousel<?php echo $moduleID; ?> ul{
					display:block;
				} 
			</style>
		</noscript>
		<script id="img-wrapper-tmpl" type="text/x-jquery-tmpl">	
			<div class="rg-image-wrapper">
				{{if itemsCount > 1}}
					<div class="rg-image-nav">
						<a href="#" class="rg-image-nav-prev">Previous Image</a>
						<a href="#" class="rg-image-nav-next">Next Image</a>
					</div>
				{{/if}}
				<div class="rg-image"></div>
				<div class="rg-loading"></div>
				<div class="rg-caption-wrapper">
					<div class="rg-caption" style="display:none;">
						<p></p>
					</div>
				</div>
			</div>
		</script>
			<div class="contentText" style="margin-top:<?php echo $containerMargin; ?>">
		<script type="text/javascript">
			jQuery(function(){
				jQuery('#rg-gallery<?php echo $moduleID; ?>').rgallery({
					module: <?php echo $moduleID; ?>,
					position: '<?php echo $params->get('esPosition') ?>',
					mode: '<?php echo $params->get('displayThumbs') ?>'
				});
			});
		</script>
				<div id="rg-gallery<?php echo $moduleID; ?>" class="rg-gallery">
		<?php if ($styles == "custom"): ?>
			<style type="text/css">
				.rg-image-wrapper {background-color: <?php echo $params->get('backgroundSkin') ?> !important; background-image: none !important; border-radius: <?php echo $params->get('borderRadius') ?>;}
				.rg-image-nav a {background-color: <?php echo $params->get('backgroundSkin') ?> !important; background-image: ("../images/nav.png") !important;}
				.es-carousel {background-color: <?php echo $params->get('backgroundSkin') ?> !important;}
				.es-carousel-wrapper {background-color: <?php echo $params->get('backgroundSkin') ?> !important;}
				.rg-caption p {color: <?php echo $params->get('textColor') ?> !important;}
			</style>
		<?php endif ; ?>
				<?php if ($autoPlay == "autoplayYes") : ?>
					<div id="buttons">
					<div class="playbutton"><a href="javascript:doTimer();"><img src="modules/mod_responsivegallery/images/playButton.png" alt="Play" alt="Play"></a></div>
					<div class="pausebutton"><a href="javascript:stopCount();"><img src="modules/mod_responsivegallery/images/pauseButton.png" alt="Pause" alt="Pause"></a></div>
						</div>
				<?php endif ; ?>
				<?php if ($autoPlay == "0") : ?>
					<div id="buttons">
					<div class="noplaybutton"></div>
					<div class="nopausebutton"></div>
					</div>
				<?php endif ; ?>
					<div class="rg-thumbs">
						<div class="es-carousel-wrapper">
							<div class="es-nav">
								<span class="es-nav-prev">Previous</span>
								<span class="es-nav-next">Next</span>
							</div>
							<div class="es-carousel">
		<style>.es-carousel ul li a img{
			width: <?php echo $params->get('heightRatio') ?>;
			height: <?php echo $params->get('heightRatio') ?>;			
			margin-top: -30px !important;
			}
		</style>	
	<div id="gallery">
        <?php
        require_once dirname(__FILE__) . '/picasa/phpPicasahelper.php';
        $gallery=new phpPicasahelper();
        $user_picasaweb="$picasaUser";
        $useralbumid="$user_albumid";
		$picturesize="$photoSize";
	if ($picasaTitle == "2") {
        if (isset($_GET['pic']) AND $_GET['pic']!="") {
            echo "<img src=\"" . $_GET['pic'] . "\" alt=\"\"/>";
        }
        else {
            $albums=$gallery->getPictures($user_picasaweb,$useralbumid, $thumbsize, "".$photoSize."".$picasaPhoto."", $picturesize, $title, $description);
            foreach ($albums AS $key=>$value) {
		if ($value['title'] != "") {
						echo "<ul>";
				echo "<li>";
		echo "<a href=\"#\"><img src=\"" . $value['thumbnail']  . "\" data-large=\"" . $value['picture']  . "\" alt=\"" . $value['title'] . "\" data-description=\"" . $value['title'] . " - " . $value['caption'] . "" . $picasaTag . "\" data-href=\"" . $value['picture']  . "\"></a>";
				echo "</li>";
						echo "</ul>";
							}
						}
					}
			echo "</div>";
							echo "</div>";
						echo "</div>";
					echo "</div>";
				echo "</div>";
			echo "</div>";
	}
		if ($picasaTitle == "1") {
        if (isset($_GET['pic']) AND $_GET['pic']!="") {
            echo "<img src=\"" . $_GET['pic'] . "\" alt=\"\"/>";
        }
        else {
            $albums=$gallery->getPictures($user_picasaweb,$useralbumid, $thumbsize, "".$photoSize."".$picasaPhoto."", $picturesize, $title, $description);
            foreach ($albums AS $key=>$value) {
		if ($value['title'] != "") {
						echo "<ul>";
				echo "<li>";
		echo "<a href=\"#\"><img src=\"" . $value['thumbnail']  . "\" data-large=\"" . $value['picture']  . "\" alt=\"" . $value['title'] . "\" data-description=\"" . $value['title'] . "" . $picasaTag . "\" data-href=\"" . $value['picture']  . "\"></a>";
				echo "</li>";
						echo "</ul>";
							}
						}
					}
			echo "</div>";
							echo "</div>";
						echo "</div>";
					echo "</div>";
				echo "</div>";
			echo "</div>";
	}
	if ($picasaTitle == "0") {
        if (isset($_GET['pic']) AND $_GET['pic']!="") {
            echo "<img src=\"" . $_GET['pic'] . "\" alt=\"\"/>";
        }
        else {
            $albums=$gallery->getPictures($user_picasaweb,$useralbumid, $thumbsize, "".$photoSize."".$picasaPhoto."", $picturesize, $title, $description);
            foreach ($albums AS $key=>$value) {
		if ($value['title'] != "") {
						echo "<ul>";
				echo "<li>";
		echo "<a href=\"#\"><img src=\"" . $value['thumbnail']  . "\" data-large=\"" . $value['picture']  . "\" alt=\"" . $value['title'] . "\" data-href=\"" . $value['picture']  . "\"></a>";
				echo "</li>";
						echo "</ul>";
							}
						}
					}
			echo "</div>";
							echo "</div>";
						echo "</div>";
					echo "</div>";
				echo "</div>";
			echo "</div>";
	}
	endif; ?>	
	<?php if ($imageFeed == "3") : ?>
			<style type="text/css">
				.rg-image img{
					max-height:<?php echo $params->get('maxHeight') ?> !important;
					max-width:<?php echo $params->get('maxWidth') ?> !important;
				} 
			</style>
		<noscript>
			<style type="text/css">
				.es-carousel<?php echo $moduleID; ?> ul{
					display:block;
				} 
			</style>
		</noscript>
		<script id="img-wrapper-tmpl" type="text/x-jquery-tmpl">	
			<div class="rg-image-wrapper">
				{{if itemsCount > 1}}
					<div class="rg-image-nav">
						<a href="#" class="rg-image-nav-prev">Previous Image</a>
						<a href="#" class="rg-image-nav-next">Next Image</a>
					</div>
				{{/if}}
				<div class="rg-image"></div>
				<div class="rg-loading"></div>
				<div class="rg-caption-wrapper">
					<div class="rg-caption" style="display:none;">
						<p></p>
					</div>
				</div>
			</div>
		</script>
			<div class="contentText" style="margin-top:<?php echo $containerMargin; ?>">
		<script type="text/javascript">
			jQuery(function(){
				jQuery('#rg-gallery<?php echo $moduleID; ?>').rgallery({
					module: <?php echo $moduleID; ?>,
					position: '<?php echo $params->get('esPosition') ?>',
					mode: '<?php echo $params->get('displayThumbs') ?>'
				});
			});
		</script>
				<div id="rg-gallery<?php echo $moduleID; ?>" class="rg-gallery">
		<?php if ($styles == "custom"): ?>
			<style type="text/css">
				.rg-image-wrapper {background-color: <?php echo $params->get('backgroundSkin') ?> !important; background-image: none !important; border-radius: <?php echo $params->get('borderRadius') ?>;}
				.rg-image-nav a {background-color: <?php echo $params->get('backgroundSkin') ?> !important; background-image: ("../images/nav.png") !important;}
				.es-carousel {background-color: <?php echo $params->get('backgroundSkin') ?> !important;}
				.es-carousel-wrapper {background-color: <?php echo $params->get('backgroundSkin') ?> !important;}
				.rg-caption p {color: <?php echo $params->get('textColor') ?> !important;}
			</style>
		<?php endif ; ?>
				<?php if ($autoPlay == "autoplayYes") : ?>
					<div id="buttons">
					<div class="playbutton"><a href="javascript:doTimer();"><img src="modules/mod_responsivegallery/images/playButton.png" alt="Play" alt="Play"></a></div>
					<div class="pausebutton"><a href="javascript:stopCount();"><img src="modules/mod_responsivegallery/images/pauseButton.png" alt="Pause" alt="Pause"></a></div>
						</div>
				<?php endif ; ?>
				<?php if ($autoPlay == "0") : ?>
					<div id="buttons">
					<div class="noplaybutton"></div>
					<div class="nopausebutton"></div>
					</div>
				<?php endif ; ?>
					<div class="rg-thumbs">
						<div class="es-carousel-wrapper">
							<div class="es-nav">
								<span class="es-nav-prev">Previous</span>
								<span class="es-nav-next">Next</span>
							</div>
							<div class="es-carousel">
		<style>.es-carousel<?php echo $moduleID; ?> ul li a img{
			width: <?php echo $params->get('galThumbratio') ?>;
			height: <?php echo $params->get('galThumbratio') ?>;			
			}
		</style>	
	<div id="gallery">
	<ul>
		<?php if ($params->get('img1url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img1url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img1url') ?>" alt="<?php echo $params->get('img1title') ?>" data-description="<?php echo $params->get('img1title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img1url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img2url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img2url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img2url') ?>" alt="<?php echo $params->get('img2title') ?>" data-description="<?php echo $params->get('img2title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img2url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img3url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img3url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img3url') ?>" alt="<?php echo $params->get('img3title') ?>" data-description="<?php echo $params->get('img3title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img3url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img4url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img4url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img4url') ?>" alt="<?php echo $params->get('img4title') ?>" data-description="<?php echo $params->get('img4title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img4url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img5url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img5url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img5url') ?>" alt="<?php echo $params->get('img5title') ?>" data-description="<?php echo $params->get('img5title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img5url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img6url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img6url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img6url') ?>" alt="<?php echo $params->get('img6title') ?>" data-description="<?php echo $params->get('img6title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img6url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img7url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img7url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img7url') ?>" alt="<?php echo $params->get('img7title') ?>" data-description="<?php echo $params->get('img7title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img7url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img8url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img8url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img8url') ?>" alt="<?php echo $params->get('img8title') ?>" data-description="<?php echo $params->get('img8title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img8url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img9url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img9url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img9url') ?>" alt="<?php echo $params->get('img9title') ?>" data-description="<?php echo $params->get('img9title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img9url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img10url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img10url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img10url') ?>" alt="<?php echo $params->get('img10title') ?>" data-description="<?php echo $params->get('img10title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img10url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img11url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img11url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img11url') ?>" alt="<?php echo $params->get('img11title') ?>" data-description="<?php echo $params->get('img11title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img11url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img12url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img12url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img12url') ?>" alt="<?php echo $params->get('img12title') ?>" data-description="<?php echo $params->get('img12title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img12url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img13url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img13url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img13url') ?>" alt="<?php echo $params->get('img13title') ?>" data-description="<?php echo $params->get('img13title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img13url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img14url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img14url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img14url') ?>" alt="<?php echo $params->get('img14title') ?>" data-description="<?php echo $params->get('img14title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img14url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img15url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img15url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img15url') ?>" alt="<?php echo $params->get('img15title') ?>" data-description="<?php echo $params->get('img15title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img15url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img16url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img16url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img16url') ?>" alt="<?php echo $params->get('img16title') ?>" data-description="<?php echo $params->get('img16title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img16url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img17url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img17url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img17url') ?>" alt="<?php echo $params->get('img17title') ?>" data-description="<?php echo $params->get('img17title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img17url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img18url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img18url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img18url') ?>" alt="<?php echo $params->get('img18title') ?>" data-description="<?php echo $params->get('img18title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img18url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img19url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img19url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img19url') ?>" alt="<?php echo $params->get('img19title') ?>" data-description="<?php echo $params->get('img19title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img19url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img20url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img20url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img20url') ?>" alt="<?php echo $params->get('img20title') ?>" data-description="<?php echo $params->get('img20title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img20url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img21url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img21url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img21url') ?>" alt="<?php echo $params->get('img21title') ?>" data-description="<?php echo $params->get('img21title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img21url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img22url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img22url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img22url') ?>" alt="<?php echo $params->get('img22title') ?>" data-description="<?php echo $params->get('img22title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img22url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img23url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img23url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img23url') ?>" alt="<?php echo $params->get('img23title') ?>" data-description="<?php echo $params->get('img23title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img23url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img24url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img24url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img24url') ?>" alt="<?php echo $params->get('img24title') ?>" data-description="<?php echo $params->get('img24title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img24url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img25url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img25url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img25url') ?>" alt="<?php echo $params->get('img25title') ?>" data-description="<?php echo $params->get('img25title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img25url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img26url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img26url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img26url') ?>" alt="<?php echo $params->get('img26title') ?>" data-description="<?php echo $params->get('img26title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img26url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img27url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img27url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img27url') ?>" alt="<?php echo $params->get('img27title') ?>" data-description="<?php echo $params->get('img27title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img27url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img28url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img28url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img28url') ?>" alt="<?php echo $params->get('img28title') ?>" data-description="<?php echo $params->get('img28title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img28url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img29url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img29url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img29url') ?>" alt="<?php echo $params->get('img29title') ?>" data-description="<?php echo $params->get('img29title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img29url') ?>" /></a>
			</li><?php } ?>
		<?php if ($params->get('img30url') != "") { ?>
			<li><a href="#"><img style="width:<?php echo $params->get('galThumbratio') ?>;height:<?php echo $params->get('galThumbratio') ?>;margin-top:-30px;" src="<?php echo $LiveSite ?><?php echo $params->get('img30url') ?>" data-large="<?php echo $LiveSite ?><?php echo $params->get('img30url') ?>" alt="<?php echo $params->get('img30title') ?>" data-description="<?php echo $params->get('img30title') ?>" data-href="<?php echo $LiveSite ?><?php echo $params->get('img30url') ?>" /></a>
			</li><?php } ?>
	</ul>
	</div>
								</div>
							</div>
						</div>
					</div>
				</div>
	<?php endif; ?>
	<?php if ($imageFeed == "2") : ?>
			<style type="text/css">
				.rg-image img{
					max-height:<?php echo $params->get('maxHeight') ?> !important;
					max-width:<?php echo $params->get('maxWidth') ?> !important;
				} 
			</style>
		<noscript>
			<style type="text/css">
				.es-carousel<?php echo $moduleID; ?> ul{
					display:block;
				} 
			</style>
		</noscript>
		<script id="img-wrapper-tmpl" type="text/x-jquery-tmpl">	
			<div class="rg-image-wrapper">
				{{if itemsCount > 1}}
					<div class="rg-image-nav">
						<a href="#" class="rg-image-nav-prev">Previous Image</a>
						<a href="#" class="rg-image-nav-next">Next Image</a>
					</div>
				{{/if}}
				<div class="rg-image"></div>
				<div class="rg-loading"></div>
				<div class="rg-caption-wrapper">
					<div class="rg-caption" style="display:none;">
						<p></p>
					</div>
				</div>
			</div>
		</script>
			<div class="contentText" style="margin-top:<?php echo $containerMargin; ?>">
		<script type="text/javascript">
			jQuery(function(){
				jQuery('#rg-gallery<?php echo $moduleID; ?>').rgallery({
					module: <?php echo $moduleID; ?>,
					position: '<?php echo $params->get('esPosition') ?>',
					mode: '<?php echo $params->get('displayThumbs') ?>'
				});
			});
		</script>
				<div id="rg-gallery<?php echo $moduleID; ?>" class="rg-gallery">
		<?php if ($styles == "custom"): ?>
			<style type="text/css">
				.rg-image-wrapper {background-color: <?php echo $params->get('backgroundSkin') ?> !important; background-image: none !important; border-radius: <?php echo $params->get('borderRadius') ?>;}
				.rg-image-nav a {background-color: <?php echo $params->get('backgroundSkin') ?> !important; background-image: ("../images/nav.png") !important;}
				.es-carousel {background-color: <?php echo $params->get('backgroundSkin') ?> !important;}
				.es-carousel-wrapper {background-color: <?php echo $params->get('backgroundSkin') ?> !important;}
				.rg-caption p {color: <?php echo $params->get('textColor') ?> !important;}
			</style>
		<?php endif ; ?>
				<?php if ($autoPlay == "autoplayYes") : ?>
					<div id="buttons">
					<div class="playbutton"><a href="javascript:doTimer();"><img src="modules/mod_responsivegallery/images/playButton.png" alt="Play" alt="Play"></a></div>
					<div class="pausebutton"><a href="javascript:stopCount();"><img src="modules/mod_responsivegallery/images/pauseButton.png" alt="Pause" alt="Pause"></a></div>
						</div>
				<?php endif ; ?>
				<?php if ($autoPlay == "0") : ?>
					<div id="buttons">
					<div class="noplaybutton"></div>
					<div class="nopausebutton"></div>
					</div>
				<?php endif ; ?>
					<div class="rg-thumbs">
						<div class="es-carousel-wrapper">
							<div class="es-nav">
								<span class="es-nav-prev">Previous</span>
								<span class="es-nav-next">Next</span>
							</div>
							<div class="es-carousel">
			<style>.es-carousel ul li a img{			
			margin-top: -30px !important;
			}
		</style>
		<?php
 		require_once("flickr/phpFlickr.php");
		if($flickrPrivate =="privatephotosetYes") {			
 			$f = new phpFlickr("$flickrAPI", "$flickrSecret");
 			$f->setToken("$flickrToken");				
			}
		if($flickrPrivate =="privatephotosetNo") {
 			$f = new phpFlickr("$flickrAPI");
			}
 			$ph_sets = $f->photosets_getList();
 		if($flickrCache =="1") {
			$cacheFolderPath = JPATH_SITE.DS.'cache'.DS.'ResponsivePhotoGallery-'.$moduleTitle.'';
			if (file_exists($cacheFolderPath) && is_dir($cacheFolderPath))
			{
			// all OK
			}
			else
			{
			mkdir($cacheFolderPath);
			}
			$lifetime = 860 * 860; // 60 * 60=One hour
			$f->enableCache("fs", "$cacheFolderPath", "$lifetime");
			}
		?>
		<?php if ($flickrCaption == "1") : ?>
			<div id="gallery">
				<div class="photosets">
					<?php $photos = $f->photosets_getPhotos($flickrSet, NULL, NULL, $flickrNumber); ?>
					<?php foreach ($photos['photoset']['photo'] as $photo): $d = $f->photos_getInfo($photo['id']); ?>
						<div class="photos">
			<ul>
				<li>
					<a href="#"><img src="<?= $f->buildPhotoURL($photo, $flickrThumb) ?>" data-large="<?= $f->buildPhotoURL($photo, 'large') ?>" alt="<?= $photo['title'] ?>" data-description= "<?= $photo['title'] ?>" data-href="<?= $f->buildPhotoURL($photo, 'large') ?>" /></a>
					</a>
  						</div>
  					<?php endforeach ; ?>
  				</div>
				</li>
			</ul>
			</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php elseif ($flickrCaption == "2") : ?>
			<div id="gallery">
				<div class="photosets">
					<?php $photos = $f->photosets_getPhotos($flickrSet, NULL, NULL, $flickrNumber); ?>
					<?php foreach ($photos['photoset']['photo'] as $photo): $d = $f->photos_getInfo($photo['id']); ?>
						<div class="photos">
			<ul>
				<li>
					<a href="#"><img src="<?= $f->buildPhotoURL($photo, $flickrThumb) ?>" data-large="<?= $f->buildPhotoURL($photo, 'large') ?>" alt="<?= $photo['title'] ?>" data-description= "<?= $d['photo']['description'] ?>" data-href="<?= $f->buildPhotoURL($photo, 'large') ?>" /></a>
					</a>
  						</div>
  					<?php endforeach; ?>
  				</div>
				</li>
			</ul>
			</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php elseif ($flickrCaption == "3") : ?>
			<div id="gallery">
				<div class="photosets">
					<?php $photos = $f->photosets_getPhotos($flickrSet, NULL, NULL, $flickrNumber); ?>
					<?php foreach ($photos['photoset']['photo'] as $photo): $d = $f->photos_getInfo($photo['id']); ?>
						<div class="photos">
			<ul>
				<li>
					<a href="#"><img src="<?= $f->buildPhotoURL($photo, $flickrThumb) ?>" data-large="<?= $f->buildPhotoURL($photo, 'large') ?>" alt="<?= $photo['title'] ?>" data-description= "<?= $photo['title'] ?> - <?= $d['photo']['description'] ?>" data-href="<?= $f->buildPhotoURL($photo, 'large') ?>" /></a>
					</a>
  						</div>
  					<?php endforeach; ?>
  				</div>
				</li>
			</ul>
			</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php elseif ($flickrCaption == "0") : ?>
			<div id="gallery">
				<div class="photosets">
					<?php $photos = $f->photosets_getPhotos($flickrSet, NULL, NULL, $flickrNumber); ?>
					<?php foreach ($photos['photoset']['photo'] as $photo): $d = $f->photos_getInfo($photo['id']); ?>
						<div class="photos">
			<ul>
				<li>
					<a href="#"><img src="<?= $f->buildPhotoURL($photo, $flickrThumb) ?>" data-large="<?= $f->buildPhotoURL($photo, 'large') ?>" alt="" data-description= "" data-href="<?= $f->buildPhotoURL($photo, 'large') ?>" /></a>
					</a>
  						</div>
  					<?php endforeach ; ?>
  				</div>
				</li>
			</ul>
			</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php endif ; ?>		
	<?php endif ; ?>