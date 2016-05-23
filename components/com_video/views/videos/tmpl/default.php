<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Video
 * @author     vikas Kumar <vikaskumar14786@gmail.com>
 * @copyright  Copyright (C) 2016. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
 defined('_JEXEC') or die('Restricted access'); ?>
 <?php
 // get the listing of the videos
 
$url = JURI::Root();
$db = JFactory::getDbo();
$query = $db->getQuery(true);
$query->select('*');
$query->from($db->quoteName('#__video'));
$query->where($db->quoteName('published')." =1");
$query->order('ID Desc');
$db->setQuery($query);
$result = $db->loadObjectList();
$query2 = $db->getQuery(true);
$query2->select('*');
$query2->from($db->quoteName('#__video'));
$query2->where($db->quoteName('published')." =1");
$query2->where($db->quoteName('home')." =1");
$db->setQuery($query2);
$home = $db->loadObjectList();


?>

<script type="text/javascript">
function SetIFrameSource(url)
{
    url = "http://www.youtube.com/embed/"+url;
    var myframe = document.getElementById('myframe');
    
    if(myframe !== null)
    {
        if(myframe.src){
        myframe.src = url;
    }
    else if(myframe.contentWindow !== null && myframe.contentWindow.location !== null){
     myframe.contentWindow.location = url; }
       else{ myframe.setAttribute('src', url); }
    }
}</script>
            <h1><?php echo JText::_('FEATURED VIDEO');?></h1>
            <div class="article-content">
	    	<?php $i=1;
			if($home[0]->home)
			{?>
			<div class="videoBoxLft">
    <iframe id="myframe" width="725px;" height="444px" src="http://www.youtube.com/embed/<?php echo $home[0]->video_url; ?>" frameborder="0" allowfullscreen >
    </iframe>  
			</div>   	
			<?php	
			}else{?>
				
			<img width="725px;" height="444px" src="images/nomainvideo.jpg" alt="No video found " title="No video found" />
    
			<?php }
		    $i=1;		
		    echo '<div  class="main"><div class="videoBoxBott">';
		    foreach($result as $video)
		    {			
			$videoId = $video->video_url;
			?>
			
			<div class="videoBoxBottIn" onclick="SetIFrameSource('<?php echo $video->video_url;?>');">
			
			<img src="http://img.youtube.com/vi/<?php echo $videoId;?>/0.jpg" >
            <img src='/images/videoThumbFrontImg.png' class="thumbPos" >
			<span><?php  echo $video->video_name; ?></span></div>
			<?php  
			
				if($i%4==0)
				{
					echo "</div><div class='videoBoxBott'>";
				}
			 $i++; 
			 
			 }
		    ?>
            </div>
		    </div>
             <?php //echo $this->pageNav->getPagesLinks(); ?>	
		</div>
      
    <div class="clear"></div>

	
	
	
	
	



