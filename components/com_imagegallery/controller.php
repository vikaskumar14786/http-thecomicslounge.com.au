<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Imagegallery
 * @author     vikaskumar <vikaskumar14786@gmail.com>
 * @copyright  Copyright (C) 2016. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

/**
 * Class ImagegalleryController
 *
 * @since  1.6
 */
class ImagegalleryController extends JControllerLegacy
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   mixed    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController   This object to support chaining.
	 *
	 * @since    1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT . '/helpers/imagegallery.php';

		$view = JFactory::getApplication()->input->getCmd('view', 'gallery');
		JFactory::getApplication()->input->set('view', $view);

		parent::display($cachable, $urlparams);

		return $this;
	}
	
	function changegallery()
	{
		
		$gallery_id = JRequest::getVar('id');
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query = "SELECT image_name,folder_name  FROM jos_gallery ".
			 " RIGHT JOIN jos_imagegallery ON ".
			 " #__gallery.gallery_id = #__imagegallery.id ".
			 " WHERE jos_gallery.gallery_id = ".$gallery_id;
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		
		if($rows)
		{
		?>
		<script type="text/javascript">
			jQuery.noConflict();
		        jQuery(document).ready(function($) {
			jQuery(function(){
			jQuery('#slides').slides({
                pagination: true,
				effect: 'fade',
				crossfade: true,
				preload: true,
				preloadImage: '<?php echo JURI::base();?>templates/thecomicslounge/images/Pink_background_loading.gif',
				play: 5000,
				pause: 2500,
				hoverPause: true
			});
		});});
			
		</script>
		<div class="imageMainBox imgGallBox" id="slides">
		<div class="slides_container" >
				
			<?php
			if($rows)
			{ foreach($rows as $row){?>
			
	<img src="<?php echo JURI::base();?><?php echo $row->image_name; ?>"  width="540" height="320" alt="Slide 1">
	
			<?php }}else {
				echo JText::_('No Image in this Gallery');	
} ?>
		</div>	
			<a href="javascript:void(0);" class="prev">
		        <img src="<?php echo JURI::base();?>templates/thecomicslounge/images/feature_imgGallery_Lft.png"  alt="Arrow Prev"></a>
	<a href="javascript:void(0);" class="next"><img src="<?php echo JURI::base();?>templates/thecomicslounge/images/feature_imgGallery_Rgt.png"  alt="Arrow Next"></a>		            
                    <div class="imageInBox"></div>
		</div>
	
	<?php } 
		die(); //require for ajax responce 
				
	}
}
