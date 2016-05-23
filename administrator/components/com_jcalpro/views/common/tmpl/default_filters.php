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

JCalPro::registerHelper('url');

$floatButtons = JCalPro::version()->isCompatible('3.0');
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn     = JString::strtolower($this->escape($this->state->get('list.direction')));
$sortFields   = $this->getSortFields();
$saveOrder    = ($this->getDefaultOrdering() == $listOrder);
if ($saveOrder && JCalPro::version()->isCompatible('3.0.0')) {
	$saveOrderingUrl = JCalProHelperUrl::task($this->getName() . '.saveOrderAjax', false, array('tmpl' => 'component'));
	JHtml::_('sortablelist.sortable', 'articleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

?>

<?php if ($floatButtons) : ?>
<script type="text/javascript">
	Joomla = Joomla || {};
	Joomla.orderTable = function() {
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>') {
			dirn = 'asc';
		}
		else {
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	}
</script>
<?php endif; ?>

<fieldset id="filter-bar">
	<div class="filter-search fltlft btn-group pull-left">
		<label class="filter-search-lbl element-invisible" for="filter_search"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?></label>
		<input type="text" name="filter_search" id="filter_search" value="<?php echo JCalProHelperFilter::escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_JCALPRO_FILTER_SEARCH_DESC'); ?>" placeholder="<?php echo JText::_('COM_JCALPRO_FILTER_SEARCH_DESC'); ?>" />
		
<?php if (!$floatButtons) : ?>
		<button type="submit" class="btn"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
		<button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
<?php endif; ?>
		
	</div>
	
<?php if ($floatButtons) : ?>
	<div class="btn-group pull-left hidden-phone">
		<button type="submit" class="btn btn-primary tip hasTooltip" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
		<button type="button" class="btn tip hasTooltip" onclick="document.id('filter_search').value='';this.form.submit();" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
	</div>
	<?php if (property_exists($this, 'pagination') && is_object($this->pagination)) : ?>
	<div class="btn-group pull-right hidden-phone">
		<label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?></label>
		<?php echo $this->pagination->getLimitBox(); ?>
	</div>
	<?php endif; ?>
	<div class="btn-group pull-right hidden-phone">
		<label for="directionTable" class="element-invisible"><?php echo JText::_('JFIELD_ORDERING_DESC'); ?></label>
		<select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">
			<option value=""><?php echo JText::_('JFIELD_ORDERING_DESC'); ?></option>
			<option value="asc" <?php if ($listDirn == 'asc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_ASCENDING'); ?></option>
			<option value="desc" <?php if ($listDirn == 'desc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_DESCENDING');  ?></option>
		</select>
	</div>
	<div class="btn-group pull-right">
		<label for="sortTable" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY'); ?></label>
		<select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">
			<option value=""><?php echo JText::_('JGLOBAL_SORT_BY');?></option>
			<?php echo JHtml::_('select.options', $sortFields, 'value', 'text', $listOrder); ?>
		</select>
	</div>
<?php endif; ?>

	<?php $this->renderFilters(); ?>
</fieldset>
<div class="clr"> </div>