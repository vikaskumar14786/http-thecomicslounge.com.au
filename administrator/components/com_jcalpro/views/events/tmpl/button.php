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

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('behavior.tooltip');

$function  = JFactory::getApplication()->input->get('function', 'jclSelectEvent', 'cmd');
$fieldid   = JFactory::getApplication()->input->get('fieldid', '', 'cmd');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>
<div id="jcl_component">
	<form action="<?php echo JRoute::_('index.php?option=com_jcalpro&view=events&layout=button&tmpl=component&function='.$function.'&fieldid='.$fieldid);?>" method="post" name="adminForm" id="adminForm">
		<fieldset class="filter clearfix">
			<div class="left">
				<label for="insert_link"><?php echo JText::_('COM_JCALPRO_INSERT_LINK_LABEL'); ?></label>&nbsp;
				<?php echo $this->insert_link; ?>
			</div>
			<div class="left">
				<label for="insert_title"><?php echo JText::_('COM_JCALPRO_INSERT_TITLE_LABEL'); ?></label>&nbsp;
				<?php echo $this->insert_title; ?>
			</div>
			<div class="left">
				<label for="insert_start_date"><?php echo JText::_('COM_JCALPRO_INSERT_START_DATE_LABEL'); ?></label>&nbsp;
				<?php echo $this->insert_start_date; ?>
			</div>
			<div class="left">
				<label for="insert_end_date"><?php echo JText::_('COM_JCALPRO_INSERT_END_DATE_LABEL'); ?></label>&nbsp;
				<?php echo $this->insert_end_date; ?>
			</div>
			<div class="left">
				<label for="insert_description"><?php echo JText::_('COM_JCALPRO_INSERT_DESCRIPTION_LABEL'); ?></label>&nbsp;
				<?php echo $this->insert_description; ?>
			</div>
			<div class="left">
				<label for="insert_itemid"><?php echo JText::_('COM_JCALPRO_INSERT_ITEMID_LABEL'); ?></label>&nbsp;
				<?php echo $this->insert_itemid; ?>
			</div>
		</fieldset>
		<fieldset class="filter clearfix">
			<div class="left">
				<label for="filter_search"><?php
					echo JText::_('JSEARCH_FILTER_LABEL');
				?></label>
				<input type="text" name="filter_search" id="filter_search" value="<?php echo JCalProHelperFilter::escape($this->state->get('filter.search')); ?>" size="30" title="<?php echo JText::_('COM_JCALPRO_FILTER_SEARCH_DESC'); ?>" />
				<button type="submit"><?php
					echo JText::_('JSEARCH_FILTER_SUBMIT');
				?></button>
				<button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php
					echo JText::_('JSEARCH_FILTER_CLEAR');
				?></button>
			</div>
		</fieldset>
		<table class="adminlist">
			<thead>
				<tr>
					<th>
						<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_TITLE', 'Event.title', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo JText::_('COM_JCALPRO_CATEGORIES'); ?>
					</th>
					<th>
						<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_TIMEZONE', 'Event.timezone', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_START_DATE', 'Event.start_date', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_END_DATE', 'Event.end_date', $listDirn, $listOrder); ?>
					</th>
					<th width="10%">
						<?php echo JHtml::_('grid.sort', 'COM_JCALPRO_KIND', 'Event.recur_type', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="6">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ($this->items as $i => $item) : ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td>
						<a class="pointer" onclick="if (window.parent) window.parent.<?php echo JCalProHelperFilter::escape($function);
							// jInsertEvent(title, link, start_date, end_date, description, itemid, add_title, add_link, add_start_date, add_end_date, add_description)
							?>('<?php   echo JCalProHelperFilter::escape_js($item->title);
							?>', '<?php echo JCalProHelperFilter::escape_js(JCalProHelperUrl::event($item->id, false, array('slug' => $item->alias)));
							?>', '<?php echo JCalProHelperFilter::escape_js(JText::sprintf('COM_JCALPRO_INSERT_START_DATE', $item->minidisplay . (isset($item->start_timedisplay) ? ' ' . $item->start_timedisplay : '')));
							?>', '<?php echo JCalProHelperFilter::escape_js(JText::sprintf('COM_JCALPRO_INSERT_END_DATE', @$item->end_minidisplay . (isset($item->end_timedisplay) ? ' ' . $item->end_timedisplay : '')));
							?>', '<?php echo JCalProHelperFilter::escape_js($item->description);
							?>', document.id('insert_itemid').value, document.id('insert_title').value, document.id('insert_link').value, document.id('insert_start_date').value, document.id('insert_end_date').value, document.id('insert_description').value);"><?php
							echo JCalProHelperFilter::escape($item->title);
						?></a>
					</td>
					<td class="center">
						<?php
							echo JCalProHelperFilter::escape($item->categories->canonical->title);
							if (!empty($item->categories->categories)) :
								?><p class="smallsub"><ul><?php
								foreach ($item->categories->categories as $cat) :
									?><li><span><?php echo JCalProHelperFilter::escape($cat->title); ?></span></li><?php
								endforeach;
								?></ul></p><?php
							endif;
						?>
					</td>
					<td class="center">
						<?php echo JCalProHelperFilter::escape($item->timezone); ?>
					</td>
					<td class="center">
						<?php
							echo JCalProHelperFilter::escape($item->minidisplay);
							if (isset($item->start_timedisplay)) :
								echo " " . JCalProHelperFilter::escape($item->start_timedisplay);
							endif;
						?>
						<?php if ('UTC' != $item->timezone): ?>
						<p class="smallsub"><span><?php
							echo JCalProHelperFilter::escape($item->utc_minidisplay);
							if (isset($item->utc_start_timedisplay)) :
								echo " " . JCalProHelperFilter::escape($item->utc_start_timedisplay);
							endif;
						?> (<?php echo JText::_('COM_JCALPRO_UTC'); ?>)</span></p>
						<?php endif; ?>
					</td>
					<td class="center">
						<?php
							switch ($item->duration_type) {
								case 0:
									echo JText::_('COM_JCALPRO_NO_END');
									break;
								case 1:
									echo JCalProHelperFilter::escape($item->end_minidisplay);
									if (isset($item->end_timedisplay)) :
										echo " " . JCalProHelperFilter::escape($item->end_timedisplay);
									endif;
									if ('UTC' != $item->timezone) : ?>
										<p class="smallsub"><span><?php
											echo JCalProHelperFilter::escape($item->utc_end_minidisplay);
											if (isset($item->utc_end_timedisplay)) :
												echo " " . JCalProHelperFilter::escape($item->utc_end_timedisplay);
											endif;
										?> (<?php echo JText::_('COM_JCALPRO_UTC'); ?>)</span></p><?php
									endif;
									break;
								case 2:
									echo JText::_('COM_JCALPRO_ALL_DAY');
									break;
							}
						?>
					</td>
					<td>
						<?php
							if (0 == $item->recur_type)
								echo JText::_('COM_JCALPRO_RECUR_TYPE_STATIC');
							else if (0 == $item->rec_id)
								echo JText::_('COM_JCALPRO_RECUR_TYPE_REPEAT_PARENT');
							else if (0 == $item->detached_from_rec)
								echo JText::_('COM_JCALPRO_RECUR_TYPE_REPEAT_CHILD');
							else echo JText::_('COM_JCALPRO_RECUR_TYPE_REPEAT_DETACHED');
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<div>
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="boxchecked" value="0" />
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
			<?php echo JHtml::_('form.token'); ?>
		</div>
	</form>
</div>