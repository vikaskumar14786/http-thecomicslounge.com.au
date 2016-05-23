<?php
/**
 * @package		JCalPro
 * @subpackage	com_jcalpro

**********************************************
JCal Pro
Copyright (c) 2006-2012 Anything-Digital.com
**********************************************
JCalPro is a native Joomla! calendar component for Joomla!

JCal Pro was once a fork of the existing Extcalendar component for Joomla!
(com_extcal_0_9_2_RC4.zip from mamboguru.com).
Extcal (http://sourceforge.net/projects/extcal) was renamed
and adapted to become a Mambo/Joomla! component by
Matthew Friedman, and further modified by David McKinnis
(mamboguru.com) to repair some security holes.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This header must not be removed. Additional contributions/changes
may be added to this header as long as no information is deleted.
**********************************************
Get the latest version of JCal Pro at:
http://anything-digital.com/
**********************************************

 */

defined('JPATH_PLATFORM') or die;

if ($this->feed) : ?>
<div id="jcl_dashboard_feed">
<?php
	// channel header and link
	$channel['title'] = $this->feed->get_title();
	$channel['link'] = $this->feed->get_link();
	$channel['description'] = $this->feed->get_description();

	$rsstitle			= 1;
	$rssitems			= 5;
	$rssdesc			= 1;
	$rssitemdesc		= 1;
	$words				= 140;

	// items
	$items = $this->feed->get_items();

	// feed elements
	$items = array_slice($items, 0, $rssitems);

	// feed title
	if (!is_null( $channel['title'] ) && $rsstitle) {
	?>
		<h4><a href="<?php echo str_replace( '&', '&amp;', $channel['link']); ?>" target="_blank">
		<?php echo $channel['title']; ?></a></h4>
		<?php
	}

	// feed description
	if ($rssdesc) {
	?>
		<p><?php echo $channel['description']; ?></p>

	<?php
	}

	$actualItems = count( $items );
	$setItems = $rssitems;

	if ($setItems > $actualItems) {
		$totalItems = $actualItems;
	} else {
		$totalItems = $setItems;
	}
	?>

	<?php
	for ($j = 0; $j < $totalItems; $j ++)
	{
		$currItem = & $items[$j];
		// item title
		?>
		<hr class="as-ruler" />
		<?php
		if ( !is_null( $currItem->get_link() ) ) {
		?>
			<h5><a href="<?php echo $currItem->get_link(); ?>" target="_child">
			<?php echo $currItem->get_title(); ?></a></h5>
		<?php
		}

		// item description
		if ($rssitemdesc)
		{
			// item description
			$text = html_entity_decode($currItem->get_description());
			$text = str_replace('&apos;', "'", $text);
			$text = strip_tags($text);

			// word limit check
			if ($words) {
				$texts = explode(' ', $text);
				$count = count($texts);
				if ($count > $words) {
					$text = '';
					for ($i = 0; $i < $words; $i ++)
					{
						$text .= ' '.$texts[$i];
					}
					$text .= '...';
				}
			}
			?>
			<p>
				<?php echo $text; ?>
			</p>
			<?php
		}

	}
	?>

<?php endif; ?>
</div>