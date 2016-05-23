<?php
/**
 * @version		:  03-05-2015
 * @author		: Modified by Glen Greenwood
 * @package		Modified Performer Module
 * @copyright	Copyright (C) 2011- . All rights reserved.
 * @license		
 */

// no direct access
defined('_JEXEC') or die;

class modPerformerHelper
{
	
	function getPerformerImages()
	{
			$today = Date("Y-m-d",time());
			$db =& JFactory::getDBO();
			$time = time();
			$query =  " SELECT DISTINCT performer_image, vmeticket_start_validity, vmeticket_end_validity "
					 ." FROM jos_vm_product as vp  RIGHT JOIN (jos_performar as pr ,jos_vm_product_type_1 as pt ) ON "
					 ." (vp.performer_id  = pr.id  AND vp.product_id = pt.product_id ) WHERE vp.product_parent_id = 0 AND vmeticket_end_validity > " .$time." AND vp.product_publish='Y' AND display_carousel=1 order by vmeticket_start_validity LIMIT 5 ";
			$db->setQuery($query);
			$performerImages = $db->loadObjectList();
			return $performerImages;
   		
		
		
	}
	static function getTest()
	{
		return 'This is a test message!';
	}
}