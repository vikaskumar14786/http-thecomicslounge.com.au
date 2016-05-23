<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="google-site-verification" content="bxIq-dw7JQ7zxduiDWJWDz-Q9MyNyYEwq4-HeFyBmuA">
<link rel="stylesheet" href="<?php echo $this->baseurl . '/templates/' . $this->template?>/css/default.css" type="text/css" />
<link rel="stylesheet" type="text/css" href="<?php echo $this->baseurl . '/templates/' . $this->template?>/fonts/fontface.css" />
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<jdoc:include type="head" /> 
<link href="feed/rss" rel="alternate" type="application/rss+xml" title="RSS 2.0" />
<link href="feed/atom" rel="alternate" type="application/atom+xml" title="Atom 1.0" />
<link href="<?php echo $this->baseurl . '/templates/' . $this->template?>/favicon.ico" rel="shortcut icon" type="image/x-icon" />
<link rel="stylesheet" href="<?php echo $this->baseurl;?>/media/system/css/modal.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $this->baseurl .'/templates/' . $this->template?>/css/slideshow.css" type="text/css" />

<?php if($_REQUEST['option']!='com_virtuemart'){ ?>   
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js" type="text/javascript"></script>
  <script src="/plugins/system/jqueryeasy/jquerynoconflict.js" type="text/javascript"></script>
  <script type="text/javascript" src="<?php echo $this->baseurl;?>/media/system/js/mootools.js"></script>

<script type="text/javascript" src="<?php echo $this->baseurl;?>/media/system/js/caption.js"></script>
<script type="text/javascript" src="<?php echo $this->baseurl;?>/libraries/js/jquery.min.js"></script>


<script type="text/javascript" src="<?php echo $this->baseurl;?>/components/com_jcalpro/themes/default/template.js"></script>
<script type="text/javascript" src="<?php echo $this->baseurl;?>/media/system/js/modal.js"></script>
<script type="text/javascript" src="<?php echo $this->baseurl;?>/libraries/js/jquery.validate.js"></script>
<script type="text/javascript" src="<?php echo $this->baseurl;?>/libraries/js/jquery.hint.js"></script>
<script type="text/javascript" src="<?php echo $this->baseurl;?>/libraries/js/thickbox.js"></script>
<link type="text/css" rel="stylesheet" href="<?php echo $this->baseurl;?>/libraries/js/thickbox.css"> </link>
<?php } ?>

  <link href='<?php echo $this->baseurl;?>/components/com_jcalpro/themes/default/style.css' rel='stylesheet' type='text/css' />
  <!--[if IE 6]><link href='<?php echo $this->baseurl;?>/components/com_jcalpro/themes/default/styleie6.css' rel='stylesheet' type='text/css' /><![endif]-->
  <!--[if IE 7]><link href='<?php echo $this->baseurl;?>/components/com_jcalpro/themes/default/styleie7.css' rel='stylesheet' type='text/css' /><![endif]-->


<?php if($_REQUEST['option']=='com_vmeticket'){ ?> 
  <script type="text/javascript" src="<?php echo $this->baseurl;?>/components/com_vmeticket/assets/js/jacs.js"></script>
  <script type="text/javascript" src="<?php echo $this->baseurl;?>/components/com_vmeticket/assets/js/vmeticket.js"></script>
<?php } ?>

</head>
<body>
<?php
$app = JFactory::getApplication();
$menu = $app->getMenu();
?>
<!-- BEGIN: HEADER --> 
<!-- BEGIN: MAIN NAVIGATION -->
<div id="login_Block">
	<div class="login_InnerBlock"> 
		<!--Top Navigation -->
		<div class="logInTxt">	<a href="/index.php?option=com_content&view=article&id=2&Itemid=104"  title="Account Login" class="LoginborderRgt">Account Login</a>
	<a href="/index.php?option=com_content&view=article&id=2&Itemid=104" title="Sign Up">
	Sign Up</a>
	    </div>

				<div class="mini_cart">
<a href="/index.php?option=com_virtuemart&view=cart">	
	<div class="Mini_cart_icon">	
		<img src="<?php echo $this->baseurl;?>/images/cart.png" alt="shoping cart" title="shoping cart" />
	</div>	
	<div class="vmCartTxt">    
    <div style="margin: 0 auto;">
 </div>
   
<div class="total_tickets">
		</div>
<!--<div style="float: right;">
</div>-->
</div>	</a>
</div>

			</div>
</div>
<div id="header_Block">
	<div id="header"> 
		<!--inner page logo	-->
				
		<!--Top navigation--> 
		
		<!--Planner Menu -->		
		<div class="headLft">
			<label for="show-menu" class="show-menu"></label>
			<input type="checkbox" class="checkbox-menu" id="show-menu" role="button">
		<jdoc:include type="modules" name="hornav" />
		</div>		
		<!--FB Twitter logo-->
		<div class="headRgt">
		<jdoc:include type="modules" name="socialnetwork" />
		</div>
	</div>
	<input type="hidden" name="menu_item_ie" value="1">
	<div class="clear"></div>
</div>

<!-- END: HEADER -->
<!--blue bar will appear with home page onle-->
<div id="blue_block">
	<div class="blueBox"></div>
</div>
<?php
if ($menu->getActive() != $menu->getDefault()) {  ?>


<?php } ?>
<!-- END: MAIN NAVIGATION -->
<div id="content_Block">
	<div id="content"> 
<jdoc:include type="modules" name="accc" />
<?php
if ($menu->getActive() != $menu->getDefault()) {  ?>
<div class="contBottBox">
			<jdoc:include type="message" />
			
			<jdoc:include type="component" />
</div>			
<?php } ?>			
		<!--Module for display breadcrum-->
				<!--Module for display message-->
		<div class="">
					</div>
<?php
if ($menu->getActive() == $menu->getDefault()) {  ?>					
						<div class="bannerBox"> 
			<!--Home Page Logo -->
				
			<div class="bannerLft">
<p><a href="<?php echo $this->baseurl;?>"><img src="<?php echo $this->baseurl;?>/templates/thecomicslounge/images/bannerLogo.png" border="0" alt="Logo" /></a></p>
<p>LIVE COMEDY 6 NIGHTS A WEEK!</p>
</div>
					<div class="bannerRgt">
				<!--Right Performar Banner -->
	<script type="text/javascript" src="<?php echo $this->baseurl;?>/libraries/js/slides.min.jquery.js"></script>
  <script type="text/javascript">
	  var root_url ='<?php echo JURI::base();?>';
	</script>
<script type="text/javascript" src="<?php echo $this->baseurl;?>/templates/<?php echo $this->template?>/js/performer.js"></script>					      
	    
		<div id="container">
			
			<div id="example">
				<div id="slides">
					<div class="slides_container">
					<jdoc:include type="modules" name="position-4" />
					</div>
					
					
				</div>
			</div>
			
		</div>
	<div class="banner-img"><img src="/images/performarslideshow/M9165 Adam Richard BANNER14490578412.jpg" width="576" height="270" alt="Slide 1">
							<div class="img-caption" style="bottom:0">
							<div class="hero_TxtBott">Sat 30 Jan </div>
							</div>
						</div>	
	
	
			</div>
		</div>
						<!--Newsleeter Module -->
		<div class="newsLetterBox">
			
<jdoc:include type="modules" name="newsletter" />
<div class="newsLetterBox_Rgt">
                <a href="/index.php?option=com_virtuemart&view=category&virtuemart_category_id=16&virtuemart_manufacturer_id=0&categorylayout=0&showcategory=1&showproducts=1&productsublayout=products_horizon&Itemid=342"><img src="<?php echo $this->baseurl;?>/templates/thecomicslounge/images/voucherHere_Img.png" alt="Voucher Here" /></a>
           </div>
	  <div class="clearF"></div>
		</div>  
<?php } ?>			
				<!-- Event Scroller Module    -->
				<!--<div class="magScrollBox">-->
		
<link rel="stylesheet" type="text/css" href="<?php echo $this->baseurl .'/templates/' . $this->template?>/css/jquery.mCustomScrollbar.css" />

<script src="<?php echo $this->baseurl .'/templates/' . $this->template?>/js/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php echo $this->baseurl .'/templates/' . $this->template?>/js/jquery.easing.1.3.js"></script>
<script type="text/javascript" src="<?php echo $this->baseurl .'/templates/' . $this->template?>/js/jquery.mousewheel.min.js"></script>
<?php
if ($menu->getActive() == $menu->getDefault()) {  ?>
<div class="magScrollBox">
	<div class="homeMagScroll" id="showloadingimage" >
			<img src="<?php echo $this->baseurl .'/templates/' . $this->template?>/images/loading.gif" alt="" />
			<p>Loading please wait.....</p>
		</div>
<div id="mcs5_container" style="display:none;">
	<div class="customScrollBox">
		<div class="horWrapper"> 
		<div class="container">
			
    		<div class="content">
		
            	<ul class="magScroll" >
					<jdoc:include type="modules" name="scroller" />
				</ul>
			</div>
		</div>

		<div class="dragger_container">
    		<div class="dragger"></div>
                </div>
<div class="magScroll_BottRgt">FUTURE EVENTS </div>

							</div>
							
<div class="scrollerbottom"><a href="/index.php?option=com_virtuemart&amp;view=category&amp;virtuemart_category_id=16&amp;virtuemart_manufacturer_id=0&amp;categorylayout=0&amp;showcategory=1&amp;showproducts=1&amp;productsublayout=0&amp;Itemid=106">VIEW MORE SHOWS</a></div>
	</div>


    <a href="javascript:void(0);" class="scrollUpBtn">
        <img src="/templates/thecomicslounge/images/magScroll_LftA.png" alt="Left arrow" title="Click to scroll"/></a>
    <a href="javascript:void(0);" class="scrollDownBtn">
        <img src="/templates/thecomicslounge/images/magScroll_RgtA.png" alt="Right arrow" title="Click to scroll" /></a>
</div>

</div>
<?php
} ?>

<noscript>
	<style type="text/css" >
		#mcs_container .customScrollBox,#mcs2_container .customScrollBox,#mcs3_container .customScrollBox,#mcs4_container .customScrollBox,#mcs5_container .customScrollBox{overflow:auto;}
		#mcs_container .dragger_container,#mcs2_container .dragger_container,#mcs3_container .dragger_container,#mcs4_container .dragger_container,#mcs5_container .dragger_container{display:none;}
	</style>
</noscript>


<?php if($_REQUEST['option']=='com_content'):?>
<script src="<?php echo $this->baseurl .'/templates/' . $this->template?>/js/eventscrollbar.js"></script>
<script src="<?php echo $this->baseurl .'/templates/' . $this->template?>/js/jquery.mCustomScrollbar.js"></script>
<?php endif;?>

<script src="<?php echo $this->baseurl .'/templates/' . $this->template?>/js/customScrollBarMobile.js"></script>
<?php
if ($menu->getActive() == $menu->getDefault()) {  ?>	
		<div class="featureBox">
			<ul class="featureList list-feature-photo">
						<div class="moduletable">
					    <li>
      <a href="/index.php?option=com_video&view=videos&Itemid=264" title="Click To View More Featured Video" title="Click To View More Featured Video"> <div class="featureHead">FEATURED VIDEO       
        </div>
        <div class="featureTxt">
            <iframe width="314" height="190" frameborder="0" allowfullscreen="" src="http://www.youtube.com/embed/EVjXd-dM7Vg" type="text/html" id="" title="Comic Lounge"></iframe>
        </div>
            <div class="featureTxtDet">
              	<a href="videos.html" title="Click To View More Featured Video"  > 
                   	CLICK TO VIEW MORE VIDEOS</a>
            </div>
    </li>
		</div>
	
				<li>
    <div class="featureHead">  
                       UPCOMING CALENDAR           
					 <div class="featureTxt">  <jdoc:include type="modules" name="jcal" />  </div>
                    <div class="featureTxtDet">
                    <a style='font-family:"CuprumRegular";font-weight:lighter;font-size:18px;margin-top:-4px' href="/index.php?option=com_jcalpro&view=events&layout=month&Itemid=107">
                   		CLICK TO VIEW FULL CALENDAR</a>
                    </div>
                </li>		     
	  <li>
      	<a href="/index.php?option=com_imagegallery&view=frontgallerys&Itemid=265" title="Click To View More Featured Photo">
	      <div class="featureHead">
		 FEATURED PHOTOS</div>
         </a>
         
		 <div class="imgGallBox" id="slidesphoto">
		 <!--<div class="slides_container" >-->
		          
         <a href="/index.php?option=com_imagegallery&view=frontgallerys&Itemid=265" title="Click To View More Featured Photo">
		  <div>
		      <img src="/images/gallery/thumbs/homeAdamHillsStage213588300083.jpg" border="0" alt="AdamHillsStage213588300083.jpg" width="290" height="166" title="Click To View More Featured Photo"/>
		  </div>
          </a>
          
		  	  <a href="javascript:void(0);" class="prev">
	  <img src="/templates/thecomicslounge/images/imgGallery_LftA.png" width="24" height="43" alt="Arrow Prev"></a>
	  <a href="javascript:void(0);" class="next"><img src="/templates/thecomicslounge/images/imgGallery_RgtA.png" width="24" height="43" alt="Arrow Next"></a>				  
			  </div>
        <div class="featureTxtDet" ><a href="/index.php?option=com_imagegallery&view=frontgallerys&Itemid=265">CLICK TO VIEW MORE PHOTOS</a></div>
		  </li>


			</ul>
		</div>
										<!--Three Static Box -->
		<div class="featureBox staticFeatureBox">
			<jdoc:include type="modules" name="footerstaticbox" />
		</div>
							</div>
<?php
}  ?>	
	<div class="clear"></div>
</div>
<!--Footer Block -->
<div id="footer_Block">
	<div id="footer"> 
		<!--Footer Menu -->
		<div class="footerLft"> 
			<!--Footer Menu 1-->
			<jdoc:include type="modules" name="footermenu1" />
			<!--Footer Menu 2-->
			<jdoc:include type="modules" name="footermenu2" />
		</div>
		<!---Payment Icon paypal  -->
		<div class="footerMid">
		<jdoc:include type="modules" name="footerpaymenticon" />
		</div>
		<!--Copy Rigtht Address Of template-->
		<div class="footerRgt">
		<jdoc:include type="modules" name="footercopyright" />
		</div>
		<div class="clearF"></div>
	</div>
</div>





</body>
<!--End Body Close-->
</html>