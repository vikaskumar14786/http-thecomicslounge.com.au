<?php defined('_JEXEC') or die('Restricted access'); 

JHtml::_('behavior.tooltip');
$pathIMG = JURI::root().'/administrator/images/';
?>
<div id="jevents">
<form action="index.php" method="post" name="adminForm"  id="adminForm">
<input type="hidden" name="section" value="<?php echo $this->section;?>" />
<table width="90%" border="0" cellpadding="2" cellspacing="2" class="adminform">
<tr><td>
<script>			
function saveCategoryOrder( n ) {
	for ( var j = 0; j <= n; j++ ) {
		box = eval( "document.adminForm.cb" + j );
		if ( box ) {
			if ( box.checked == false ) {
				box.checked = true;
			}
		} else {
			alert("You cannot change the order of items, as an item in the list is `Checked Out`");
			return;
		}
	}
	submitform('categories.saveorder');
}
</script>
<table cellpadding="4" cellspacing="0" border="0" align="right">
	<tr>
		<td>
			<?php
			echo $this->typeFilter();
			?>
		</td>
		<td>
			<?php
			echo $this->catFilter();
			?>
		</td>
	</tr>
</table>

<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist  table table-striped">
	<tr>
		<th width="20" nowrap="nowrap">
			<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
		</th>
		<th class="title" width="75%" nowrap="nowrap"><?php echo JText::_('JEV_CATEGORY_TITLE'); ?></th>
		<th class="title" width="25%" nowrap="nowrap"><?php echo JText::_('JEV_CATEGORY_PARENT'); ?></th>
		<th width="2%">	Order</th>
		<th width="1%">
		<a href="javascript: saveCategoryOrder( <?php echo count( $this->cats )-1; ?> )"><img src="images/filesave.png" border="0" width="16" height="16" alt="Save Order" /></a>
		</th>

		<th width="10%" nowrap="nowrap"><?php echo JText::_('JEV_PUBLISHED'); ?></th>
		<th width="10%" nowrap="nowrap"><?php echo JText::_('JEV_ACCESS'); ?></th>
	</tr>

    <?php
    $k=0;
    $i=0;
    foreach ($this->cats as $cat) {
    	?>
        <tr class="row<?php echo $k; ?>">
        	<td width="20" >
                <input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $cat->id; ?>" onclick="isChecked(this.checked);" />
        	</td>
          	<td >
          		<a href="#edit" onclick="return listItemTask('cb<?php echo $i;?>','categories.edit')" title="<?php echo JText::_('JEV_CLICK_TO_EDIT'); ?>">
          		<?php echo $cat->title; ?>
          		</a>
          	</td>
          	<td><?php echo ($cat->parent_id>0) ? $cat->parenttitle : "-"; ?></td>
			<td align="center" colspan="2">
			<input type="text" name="order[]" size="5" value="<?php echo $cat->ordering; ?>" class="text_area" style="text-align: center" />
			</td>
          	<td align="center">
          	<?php                      	
          	$img = $cat->published?'publish_g.png':'publish_r.png';
          	?>
          	<a href="javascript: void(0);" onclick="return listItemTask('cb<?php echo $i; ?>','<?php echo $cat->published ? 'categories.unpublish' : 'categories.publish'; ?>')"><img src="<?php echo $pathIMG . $img; ?>" width="12" height="12" border="0" alt="" /></a>
          	</td>
          	<td align="center"><?php echo $cat->_groupname;?></td>
        </tr>
        <?php
        $i++;
        $k = 1 - $k;
    } ?>
    <tfoot>
        <tr>
    	  <td align="center" colspan="7">
			<?php echo $this->pageNav->getListFooter(); ?>
		  </td>
		</tr>
    </tfoot>
</table>
</td>
</tr>  
</table>
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="task" value="categories.list" />
<input type="hidden" name="act" value="" />
<input type="hidden" name="option" value="<?php echo JEVEX_COM_COMPONENT;?>" />
</form>
</div>