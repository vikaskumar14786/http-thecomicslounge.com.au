<form action="index.php?option=com_voucher" method="post" name="adminForm" enctype="multipart/form-data">

		<table>
		<tr>
			<td align="left" width="100%">
					</td>
			<td nowrap="nowrap">
				<?php
				echo $lists['state'];
				?>
			</td>
		</tr>
		</table>

			<table class="adminlist">
			<thead>
				<tr>
					
					<th width="10%" aling="center"  class="title">
						<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($rows); ?>);" />
					</th>
					<th width="5%" class="title" nowrap="nowrap">
						<?php echo JHTML::_('grid.sort', 'Id', 'cd.id', @$lists['order_Dir'], @$lists['order'] ); ?>
					</th>
					
					<th width="30%" class="title" nowrap="nowrap">
						<?php echo JHTML::_('grid.sort',   'Company Partner', 'cd.partner_id', @$lists['order_Dir'], @$lists['order'] ); ?>
					</th>
					
					<th width="20%" nowrap="nowrap">
						<?php echo JHTML::_('grid.sort',   'Voucher Type', 'cd.voucher_type', @$lists['order_Dir'], @$lists['order'] ); ?>
					</th>								

					<th class="title" nowrap="nowrap" width="20%">
						<?php echo JHTML::_('grid.sort',   'Voucher Issue', 'cd.id', @$lists['order_Dir'], @$lists['order'] ); ?>
					</th>

					<th class="title" nowrap="nowrap" width="5%">
						<?php echo JHTML::_('grid.sort',   'Published', 'cd.published', @$lists['order_Dir'], @$lists['order'] ); ?>
					</th>		

					<th class="title" nowrap="nowrap" width="10%">
						<?php echo JHTML::_('grid.sort',   'Voucher Detail', 'cd.id', @$lists['order_Dir'], @$lists['order'] ); ?>
					</th>
					
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="11">
						<?php echo $pageNav->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php
			$k = 0;
			for ($i=0, $n=count($rows); $i < $n; $i++) {
				$row = $rows[$i];

				$link = JRoute::_( 'index.php?option=com_voucher&task=edit&cid[]='. $row->id );
				$viewDetail = JRoute::_( 'index.php?option=com_voucher&task=readVoucherDetail&cid[]='. $row->id );
				$issuesLink = JRoute::_( 'index.php?option=com_voucher&task=issuedVouchers&id='. $row->id );
				$checked = JHTML::_('grid.checkedout',   $row, $i );
				$access = JHTML::_('grid.access',   $row, $i );
				$published = JHTML::_('grid.published', $row, $i );
			
				?>
				<tr class="<?php echo 'row'.$k; ?>">
					
					<td align="center">
						<?php echo $checked; ?>
					</td>

					<td align="center">
						<?php echo $row->id;?>
					</td> 
					
					</td>

					<td align="center">
						<?php $partnerDetails = $model->getPartnerDetailsById($row->partner_id);
									echo $partnerDetails->partner_name;
						?>
					</td>
					
					<td align="center">
						<?php
									if($row->voucher_type==1)
									echo JText::_('Show Only');
									else if($row->voucher_type==2)
									echo JText::_('Show with Meal');
						?>
					</td>								

					<td align="center">
					<?php if($row->	voucher_expiry_date == "0000-00-00 00:00:00") { ?>
						<a href="<?php echo $issuesLink; ?>"><?php echo JText::_('Issue vouchers'); ?></a>
					<?php } else {  ?>
						<strong><?php echo JText::_('Voucher Issue'); ?></strong>: <?php echo $row->voucher_issue_date; ?><br/>
						<strong><?php echo JText::_('Voucher Expiry'); ?></strong>: <?php echo $row->voucher_expiry_date; ?>
					<?php } ?>
					</td>

					<td align="center">
						<?php echo $published;?>
					</td>		

					<td align="center">
						<a href="<?php echo $viewDetail; ?>"><?php echo JText::_('Voucher Code Details'); ?></a>
					</td>
					
				</tr>
				<?php
				$k = 1 - $k;
			}
			?>
			</tbody>
			</table>

		<input type="hidden" name="option" value="<?php echo $option; ?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $lists['order_Dir']; ?>" />
		<?php echo JHTML::_( 'form.token' ); ?>
		</form>
		