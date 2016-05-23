<?php defined('_JEXEC') or die('Restricted access'); ?>

<?php JHTML::_('behavior.tooltip'); ?>
<div class='jevpeople'>
<form action="index.php" method="post" name="adminForm"  id="adminForm">
<table>
<tr>
	<td align="left" width="100%">
		<?php echo JText::_( 'FILTER' ); ?>:
		<input type="text" name="search" id="search" value="<?php echo $this->lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
		<button onclick="this.form.submit();"><?php echo JText::_( 'GO' ); ?></button>
		<button onclick="document.getElementById('search').value='';this.form.getElementById('filter_catid').value='0';this.form.getElementById('filter_state').value='';this.form.submit();"><?php echo JText::_( 'RESET' ); ?></button>
	</td>
	<td nowrap="nowrap">
		<?php
		echo $this->lists['typefilter'];
		echo $this->lists['catid'];
		echo $this->lists['state'];
		?>
	</td>
</tr>
</table>
<div >
	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist  table table-striped">
	<thead>
		<tr>
			<th width="5">
				<?php echo JText::_( 'NUM' ); ?>
			</th>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</th>
			<th class="title">
				<?php echo JHTML::_('grid.sort',  'Title', 'pers.title', $this->lists['order_Dir'], $this->lists['order'] , "people.list"); ?>
			</th>
			<th width="5%" nowrap="nowrap">
				<?php echo JHTML::_('grid.sort',  'Published', 'pers.published', $this->lists['order_Dir'], $this->lists['order'] , "people.list"); ?>
			</th>
			<th width="5%" nowrap="nowrap">
				<?php echo JHTML::_('grid.sort',  'Global', 'pers.global', $this->lists['order_Dir'], $this->lists['order'] , "people.list"); ?>
			</th>
			<th width="5%">
				<?php echo JHTML::_('grid.sort',  'Type', 'pt.title', $this->lists['order_Dir'], $this->lists['order'] , "people.list"); ?>
			</th>
			<th width="5%">
				<?php echo JHTML::_('grid.sort',  'Country', 'pers.catid', $this->lists['order_Dir'], $this->lists['order'] , "people.list"); ?>
			</th>
			<th width="5%">
				<?php echo JHTML::_('grid.sort',  'State', 'pers.catid1', $this->lists['order_Dir'], $this->lists['order'] , "people.list"); ?>
			</th>
			<th width="5%">
				<?php echo JHTML::_('grid.sort',  'City', 'pers.catid2', $this->lists['order_Dir'], $this->lists['order'] , "people.list"); ?>
			</th>
			<th width="1%" nowrap="nowrap">
				<?php echo JHTML::_('grid.sort',  'ID', 'pers.pers_id', $this->lists['order_Dir'], $this->lists['order'] , "people.list"); ?>
			</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="10">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$k = 0;
	for ($i=0, $n=count( $this->items ); $i < $n; $i++)
	{
		$row = &$this->items[$i];

		$tmpl = "";
		if (JRequest::getString("tmpl","")=="component"){
			$tmpl = "&tmpl=component";
		}
		$mainframe=JFactory::getApplication();$Itemid=JRequest::getInt("Itemid");
		if (!JFactory::getApplication()->isAdmin()){
			$tmpl .="&Itemid=$Itemid";
		}

		$link 	= JRoute::_( 'index.php?option=com_jevpeople&task=people.edit&cid[]='. $row->pers_id . $tmpl );

		$checked 	= JHTML::_('grid.checkedout',   $row, $i ,"pers_id");
		$checked = str_replace('"images/' ,'"../administrator/images/',$checked);
		$published 	= JURI::root().($row->published?'components/com_jevpeople/assets/images/Tick.png': 'components/com_jevpeople/assets/images/Trash.png');

		// global list
		$global	= $this->_globalHTML($row,$i);

		$ordering = ($this->lists['order'] == 'pers.ordering');

		?>
		<tr class="<?php echo "row$k"; ?>">
			<td>
				<?php echo $this->pagination->getRowOffset( $i ); ?>
			</td>
			<td>
				<?php echo $checked; ?>
			</td>
			<td>
				<?php
				if (  $this->user->get ('id') ==  $row->checked_out )  {
					echo $this->escape($row->title);
				} else {
				?>
				<span class="editlinktip hasTip" title="<?php echo JText::_( 'EDIT_PERSON' );?>::<?php echo $this->escape($row->title); ?>">
					<a href="<?php echo $link; ?>">
						<?php echo $this->escape($row->title); ?></a></span>
				<?php
				}
				?>
			</td>
			<td align="center">
				<img src="<?php echo $published;?>" alt="<?php echo JText::_( 'PUBLISHED_STATE' );?>" />
			</td>
			<td align="center">
				<?php echo $global;?>
			</td>
			<td>
				<?php
				echo $row->typename;
				?>
			</td>
			<td>
				<?php echo $this->escape($row->country); ?>
			</td>
			<td>
				<?php echo $this->escape($row->state); ?>
			</td>
			<td>
				<?php echo $this->escape($row->city); ?>
			</td>
			<td align="center">
				<?php echo $row->pers_id; ?>
			</td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
	</tbody>
	</table>
</div>

	<input type="hidden" name="option" value="com_jevpeople" />
	<input type="hidden" name="task" value="people.list" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
	<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt("Itemid",0);?>" />
	<?php if (JRequest::getString("tmpl","")=="component"){ ?>
	<input type="hidden" name="tmpl" value="component" />	
	<?php } ?>
	<?php echo JHTML::_( 'form.token' ); ?>
</form>

</div>