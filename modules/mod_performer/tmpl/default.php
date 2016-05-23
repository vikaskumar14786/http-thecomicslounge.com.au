	<?php $url = JURI::Root();
	defined('_JEXEC') or die;	
	?>      
	    
		<div id="container">
			<div id="example">
				<div id="slides">
					<div class="slides_container">
						
						<?php  foreach($performerImages as $key=>$images) { ?>
						
							<div class="slide">
							<img src="<?php echo $url; ?>images/performerslideshow/<?php echo $images->performar_image; ?>"  width="576" height="270" alt="Slide 1">
							<div class="caption" style="bottom:0">
							<div class="hero_TxtBott">
							<?php
							$start_date = Date("Y-m-d",$images->vmeticket_start_validity);
							$end_date = Date("Y-m-d",$images->vmeticket_end_validity);
							$date_cmp = strcmp($start_date, $end_date);
							if($date_cmp !=0){
								echo $formatted = date('D d M', $images->vmeticket_start_validity);?>
							 To 
							<?php 	echo $formatted = date('D d M ', $images->vmeticket_end_validity);
							}else{
								echo $formatted = date('D d M', $images->vmeticket_start_validity);
								}
							?>
							</div>
							</div>
							</div>
							
						<?php if($key == 0){
						
							$mobileHtml = $mobileHtml . '<div class="banner-img"><img src="'. $url .'images/performerslideshow/'.$images->performer_image.'" width="576" height="270" alt="Slide 1">
							<div class="img-caption" style="bottom:0">
							<div class="hero_TxtBott">'.
							$formatted
							.'</div>
							</div>
						</div>';
						
						 } ?>	
						
						<?php } ?>
											
					</div>
					
					
				</div>
			</div>
			
		</div>
	<?php echo $mobileHtml; ?>
	<script type="text/javascript" src="<?php echo $url; ?>libraries/js/slides.min.jquery.js"></script>
	<script type="text/javascript">
	  var root_url ='<?php echo $url; ?>';
	</script>
	<script type="text/javascript" src="<?php echo $url; ?>templates/comicslounge/js/performer.js"></script>	