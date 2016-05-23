<?php
/**
 * @package     BreadCrumbs.Advanced
 * @subpackage  mod_breadcrumbs_adv - default layout
 *
 * @copyright   Copyright (C) 2013 UWiX, Inc. All rights reserved.
 */
 
defined('_JEXEC') or die;
JHtml::_('bootstrap.tooltip');
$padL = ($params->get('padLeft', 0) > 0) ? "style='padding-left: ".$params->get('padLeft', 0)."px;'" : "";

?>
<div class = "breadcrumbs<?php echo $moduleclass_sfx; ?>" <?php echo $padL; ?>>
<?php 
	if ($params->get('showHere', 1))
	{
		echo '<span class="showHere">' .JText::_('MOD_BREADCRUMBS_ADV_HERE').'</span>';
	}

	// Get rid of duplicated entries on trail including home page when using multilanguage
	for ($i = 0; $i < $count; $i++)
	{
		if ($i == 1 && !empty($list[$i]->link) && !empty($list[$i - 1]->link) && $list[$i]->link == $list[$i - 1]->link)
		{
			unset($list[$i]);
		}
	}

	// Find last and penultimate items in breadcrumbs list
	end($list);
	$last_item_key = key($list); // Last breadcrumb
	prev($list);
	$penult_item_key = key($list); // one-last breadcrumb

	// Generate the trail
	
	if($_REQUEST['virtuemart_category_id']!=20)
	{
			foreach ($list as $key => $item) :
		// Make a link if not the last item in the breadcrumbs
		$show_last = $params->get('showLast', 1);
		if ($key != $last_item_key)
		{
			// Render all but last item - along with separator
			if (!empty($item->link))
			{
				$hp = ($key == 0 && $params->get('homePath', '') != '') ? $params->get('homePath', '') : '';
				echo '<a href="' . $item->link. $hp . '" class="pathway">' . $item->name . '</a>';
			} else {
				echo '<span>' . $item->name . '</span>';
			}

			if (($key != $penult_item_key) || $show_last)
			{
				echo ' '.$separator.' ';
			}
		} elseif ($show_last) {
			// Render last item if reqd.
			if ( $params->get('cutLast', 0) && ( strlen($item->name) > $params->get('cutAt', 0) ) ) // If last breadcrumb must be cut off
			{
				echo '<span>'.rtrim( substr( $item->name, 0, $params->get('cutAt', 0) ) ).$params->get('cutChar', '...').'</span>';
			} else {
				if ($key == 0 && $params->get('clickHome', 0)) // If Home is the only breadcrumb and should be clickable
				{
					$hp = ($key == 0 && $params->get('homePath', '') != '') ? $params->get('homePath', '') : '';
					echo '<a href="' . $item->link. $hp . '" class="pathway">' . $item->name . '</a>';
				} else {
					echo '<span>' . $item->name . '</span>';
				}
			}
		}
	endforeach; 
	}
	else
	{
		
		echo '<a href="' . $list[0]->link. $hp . '" class="pathway">' . $list[0]->name. '</a>';
	}	
	?>
</div>
