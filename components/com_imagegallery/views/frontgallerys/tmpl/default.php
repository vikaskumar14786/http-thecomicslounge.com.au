<?php defined('_JEXEC') or die('Restricted access');
//get first image gallery
$db = JFactory::getDbo();
 // Create a new query object.
$query = $db->getQuery(true);
 // Select all records from the user profile table where key begins with "custom.".
// Order it by the ordering field.
$query->select($db->quoteName(array('id')));
$query->from($db->quoteName('#__imagegallery'));
$query->where($db->quoteName('home').' = 1');
$query->where($db->quoteName('published').' = 1');
$db->setQuery($query);
if (!$db->query()) {
echo "<script> alert('".$db->getErrorMsg(true)
."'); window.history.go(-1); </script>\n";
			}
$result = $db->loadResult();
if(!$result)
{
	$query1 = 'SELECT id FROM #__imagegallery WHERE published = 1 ORDER BY id DESC LIMIT 1 ';
    $db->setQuery($query1);
    $db->setQuery($query1);
	if (!$db->query()) {
	echo "<script> alert('".$db->getErrorMsg(true)."'); window.history.go(-1); </script>\n";
			}
			$result = $db->loadResult();
		}
				
$query4 = " SELECT image_name  FROM #__gallery as g".
		         " LEFT JOIN #__imagegallery as i ON i.id = g.gallery_id WHERE g.gallery_id = ".$result;
$db->setQuery($query4);
$row = $db->loadobjectlist() 
?>
<h1><?php echo JText::_('FEATURED PHOTO GALLERY'); ?></h1>
<?php $url = JURI::Root();   ?>
      
      <div class="article-content">
		<div class="videoBox">
				<div id="galleryResponce">
		                <div class="imageMainBox imgGallBox" id="slides">
				<div class="slides_container" >
				<?php			
				$result = $row;
			
				
				if($result)
				{
					     foreach($result as $res)
						     {
								?>
<img src="<?php echo JURI::Root().$res->image_name; ?>"  class="img-galleryshow" alt="Slide">
						      
						<?php
						
				}}else { ?>
<img src="<?php echo JURI::Root(); ?>images/noimagegallery.jpg"  class="img-galleryshow" alt="Slide <?php echo $counter; ?>" />
				<?php	
				
				}
					
				?>
				</div>
				
		<a href="javascript:void(0);" class="prev">
		        <img src="<?php echo JURI::Root();?>templates/thecomicslounge/images/feature_imgGallery_Lft.png"  alt="Arrow Prev"></a>
	<a href="javascript:void(0);" class="next"><img src="<?php echo JURI::Root();?>templates/thecomicslounge/images/feature_imgGallery_Rgt.png"  alt="Arrow Next"></a>		            
                
		</div>
				</div>				
		
<?php 
$query3 ="SELECT i.id,i.coverphoto,i.gallery_name FROM #__imagegallery as i ".
		"  JOIN #__gallery as g ON i.id = g.gallery_id ".
		"  WHERE i.id = g.gallery_id AND published = 1  GROUP BY i.id ORDER BY i.id DESC LIMIT ".'0'. ",".'2000';
$db->setQuery($query3);
$images= $db->loadObjectList();
	
						
						
						
                            $counter = 1;						
						    echo '<div class="imageBoxTboxBott">';
                            foreach($images as $img){

                            $folderName = $img->folder_name;
                            $coverImage = $img->coverphoto;
                            $thumb = $folderName."/".$coverImage;
                            ?> 
                            <div class="imgBox">              
								<div class="imageBoxTbox">    
								<img  onclick="javascript:ChangeGallery(<?php echo $img->id; ?>);"  title="Click To play Gallery" alt="Featured Gallery<?php echo $counter; ?>" src="<?php echo $coverImage; ?>" class="img-photogallery"> 
								</div>
								<span><?php echo $img->gallery_name; ?></span>  
							</div>
							<?php
							if($counter%6==0)
							{
								//echo "</div><div class='imageBoxTboxBott'>";
							}
								$counter++;
                            }
                        ?>  
                </div>
		             <?php //echo $this->pageNav->getPagesLinks(); ?>	    
				
            </div>

      <script type="text/javascript" src="<?php echo JURI::base();?>libraries/js/slides.min.jquery.js"></script>
      <script type="text/javascript">
		var base_url_str ='<?php echo JURI::base();  ?>';
      </script>
	  
      <script type="text/javascript" src="<?php echo JURI::base();?>templates/thecomicslounge/js/imagegallery_imagegallery.js"></script>
     </div>