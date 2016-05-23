<?php
/**
 * JEvents Component for Joomla 
 *
 * @version     $Id: overview.php 1676 2010-01-20 02:50:34Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');

JHtml::_('behavior.tooltip');

$db = JFactory::getDBO();
$user = JFactory::getUser();
$compparams = JComponentHelper::getParams('com_rsvppro');
$params = false;
$feesAndBalances = false;
$showtransactions = false;
if (count($this->rows) > 0)
{

	$attendee = $this->rows[0];

	$sql = "SELECT * FROM #__jev_attendance WHERE id=" . $attendee->at_id;
	$db->setQuery($sql);
	$rsvpdata = $db->loadObject();
}
else
{
	$rsvpdata = false;
}

$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';

$pathIMG = JURI::root() . 'administrator/images/';

if (!isset($this->templateInfo))
{
	$xmlfile = JevTemplateHelper::getTemplate($this->rsvpdata);
	if (is_int($xmlfile) && $xmlfile > 0)
	{
		$db = JFactory::getDbo();
		$db->setQuery("Select * from #__jev_rsvp_templates where id=" . intval($xmlfile));
		$this->templateInfo = $db->loadObject();
		if ($this->templateInfo)
		{
			$this->templateParams = $this->templateInfo->params;
			$this->templateParams = json_decode($this->templateParams);
		}
		else
		{
			$this->templateParams = false;
		}
	}
	else
	{
		$this->templateParams = false;
	}
}
if (!empty($this->sidebar)) {
	?>
	<div id="j-sidebar-container" class="span2">

		<?php echo $this->sidebar; ?>

	</div>
<?php }
$mainspan = 10;
$fullspan = 12;

?>
<div id="j-main-container" class="span<?php echo (!empty($this->sidebar)) ? $mainspan : $fullspan; ?>  ">

<form action="index.php" method="post" name="adminForm"  id="adminForm"  >
	<table cellpadding="4" cellspacing="0" border="0" >
		<tr>
			<td><?php echo JText::_('JEV_SEARCH'); ?>&nbsp;<input type="text" name="search" value="<?php echo $this->search; ?>" class="inputbox" onchange="document.adminForm.task.value = 'attendees.list';document.adminForm.submit();" /></td>
			<td><?php echo $this->confirmed; ?></td>
			<td><?php echo $this->attendstate; ?></td>
			<td><?php echo $this->waiting; ?></td>
		</tr>
	</table>

	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist  table table-striped" id="attendeelist">
		<thead>
			<tr>
				<th width="20" nowrap="nowrap">
					<?php echo JHtml::_('grid.checkall'); ?>
				</th>
				<th class="title" >
<?php echo JHtml::_('grid.sort', 'RSVP_ATTENDEE_NUMBER', 'atdees.id', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<th class="title" >
<?php echo JHtml::_('grid.sort', 'JEV_ATTENDEE', 'attendee', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<th class="title" >
<?php echo JText::_("RSVP_DELETE"); ?>
				</th>
				<th class="title" >
<?php echo JHtml::_('grid.sort', 'RSVP_CONFIRMED_STATUS', 'atdees.confirmed', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<th class="title" >
<?php echo JHtml::_('grid.sort', 'JEV_ATTENDANCE_STATUS', 'atdees.attendstate', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<th class="title" >
<?php echo JHtml::_('grid.sort', 'JEV_WAITING', 'atdees.waiting', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<th class="title" >
<?php echo JHtml::_('grid.sort', 'JEV_REGISTRATION_TIME', 'atdees.created', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<th class="title" >
<?php echo JHtml::_('grid.sort', 'JEV_MODIFICATION_TIME', 'atdees.modified', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<th class="title" >
<?php echo JHtml::_('grid.sort', 'RSVP_ATTENDED', 'atdees.didattend', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<th class="title" >
				<?php echo JHtml::_('grid.sort', 'RSVP_ATTENDANCENOTES', 'atdees.notes', $this->orderdir, $this->order, "attendees.list"); ?>
				</th>
				<?php
				if ($this->templateParams && $this->templateInfo->withticket)
				{
					?>
					<th class="title jevticket">
					<?php echo JText::_("JEV_PRINT_TICKET"); ?>
					</th>
					<?php
				}

				$html = "";
				$colcount = 11;
				if (count($this->rows) > 0)
				{

					$attendee = $this->rows[0];

					$template = $rsvpdata->template;

					// Store details in registry - will need them for waiting lists!
					$registry = JRegistry::getInstance("jevents");
					$registry->set("rsvpdata", $rsvpdata);
					$registry->set("event", $attendee->eventrepeat);

					// New parameterised fields
					$params = false;
					if ($template != "")
					{
						$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);
						if (is_int($xmlfile))
						{
							$eventrow = clone $this->repeat;
							$masterparams = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
						}

						if (is_int($xmlfile) || file_exists($xmlfile))
						{
							// no need to check attendee or locked templates for the headers !!!
							// transfer attendee specific information into the event row
							$eventrow = clone $this->repeat;
							if (isset($this->xmlparams[$xmlfile]))
							{
								$params = clone ($this->xmlparams[$xmlfile]);
							}
							else
							{
								$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
								$this->xmlparams[$xmlfile] = $params;
							}

							$html = "";
							$html .='<th class="title transactionscolumn"   >' . JText::_("RSVP_TRANSACTIONS") . '</th>';
							$colcount++;

							$params = $params->renderToBasicArray();
							foreach ($params as $param)
							{
								if ($param["capacity"] > 0 && isset($param["capacitycount"]))
								{
									$html .='<th>' . JText::_($param['label']) . ' (' . $param["capacitycount"] . '/' . $param["capacity"] . ')</th>';
									$colcount++;
								}
								else
								{
									if ($param['label'] != "" && $param["showinlist"])
									{
										$html .='<th>' . stripslashes(JText::_($param['label'])) . '</th>';
										$colcount++;
									}
								}
							}
							//}
						}
					}
				}
				echo $html;
				?>
			</tr>
		</thead>
		<tbody>

			<?php
			$k = 0;
			$nullDate = $db->getNullDate();

			for ($i = 0, $n = count($this->rows); $i < $n; $i++)
			{
				$attendee = &$this->rows[$i];

				$rowspan = $attendee->guestcount > 0 ? " rowspan='" . $attendee->guestcount . "' " : "";

				// New parameterised fields
				$params = false;
				if ($rsvpdata->template != "")
				{
					$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

					if ((is_int($xmlfile) || file_exists($xmlfile)) && ($attendee->lockedtemplate == 0 || $attendee->lockedtemplate == $xmlfile))
					{
						// transfer attendee specific information into the event row
						$eventrow = clone $this->repeat;
						if (isset($this->xmlparams[$xmlfile]))
						{
							$params = clone ($this->xmlparams[$xmlfile]);
						}
						else
						{
							$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
							$this->xmlparams[$xmlfile] = $params;
						}
						foreach (get_object_vars($attendee) as $key => $val)
						{
							$eventrow->$key = $val;
						}
						if (isset($attendee->params))
						{
							// building from scratch each time is slow! so use a cloned object!
							//$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $eventrow);
							$params->loadData($attendee->params, $rsvpdata, $eventrow);
							$feesAndBalances = $params->outstandingBalance($attendee);
						}
						else
						{
							//$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
							$feesAndBalances = false;
						}
						$params = $params->renderToBasicArray('xmlfile', $attendee);
					}
					else if ($attendee->lockedtemplate > 0)
					{
						$xmlfile = $attendee->lockedtemplate;

						// transfer attendee specific information into the event row
						$eventrow = clone $this->repeat;
						if (isset($this->xmlparams[$xmlfile]))
						{
							$params = clone ($this->xmlparams[$xmlfile]);
						}
						else
						{
							$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
							$this->xmlparams[$xmlfile] = $params;
						}
						foreach (get_object_vars($attendee) as $key => $val)
						{
							$eventrow->$key = $val;
						}
						if (isset($attendee->params))
						{
							// building from scratch each time is slow! so use a cloned object!
							//$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $eventrow);
							$params->loadData($attendee->params, $rsvpdata, $eventrow);
							$feesAndBalances = $params->outstandingBalance($attendee);
						}
						else
						{
							//$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
							$feesAndBalances = false;
						}
						$params = $params->renderToBasicArray('xmlfile', $attendee);
					}
					else
					{
						$params = false;
					}
				}

				if (!$attendee->confirmed)
				{
					$cimg = 'Cross.png';
				}
				else
				{
					$cimg = 'Tick.png';
				}
				$calt = "";
				if (!$attendee->waiting)
				{
					$wimg = 'Cross.png';
				}
				else
				{
					$wimg = 'Tick.png';
				}
				$walt = "";

				$trashimg = "Trash.png";
				$talt = "";

				$wimg = '<img src="' . JURI::root() . $pluginpath . '/assets/' . $wimg . '" border="0" alt="' . $walt . '" style="height:16px;border:none;" /></a>';
				$cimg = '<img src="' . JURI::root() . $pluginpath . '/assets/' . $cimg . '" border="0" alt="' . $walt . '" style="height:16px;border:none;" /></a>';
				$trashimg = '<img src="' . JURI::root() . $pluginpath . '/assets/' . $trashimg . '" border="0" alt="' . $talt . '" style="height:16px;border:none;" /></a>';
				$mainframe = JFactory::getApplication();
				?>
				<tr class="row<?php echo $k; ?>">
					<td width="20"  <?php echo $rowspan; ?>>
	<?php echo JHtml::_('grid.id', $i, $attendee->atdee_id); ?>
					</td>
					<td   <?php echo $rowspan; ?>>
	<?php echo $attendee->atdee_id; ?>
					</td>
					<td   <?php echo $rowspan; ?>>
						<a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>', 'attendees.edit')" title="<?php echo JText::_('JEV_CLICK_TO_EDIT'); ?>"><?php echo $attendee->attendee; ?></a>
					</td>
					<td   <?php echo $rowspan; ?>>
						<a href="#delete" onclick="if (confirm('<?php echo addslashes(JText::sprintf("RSVP_DELETE_ATTENDEE_NAMED", $attendee->attendee)); ?>'))
									return listItemTask('cb<?php echo $i; ?>', 'attendees.delete');
								else
									return false;" title="<?php echo JText::_('JEV_CLICK_TO_DELETE_ATTENDEE'); ?>">
						<?php echo $trashimg; ?>
						</a>
					</td>
					<td   <?php echo $rowspan; ?>>
						<?php
						if (!$attendee->confirmed)
						{
							?>
							<a href="javascript: void(0);" onclick="return listItemTask('cb<?php echo $i; ?>', 'attendees.confirm')" title="<?php echo JText::_('JEV_CLICK_TO_CONFIRM', true); ?>"><?php echo $cimg; ?></a>
							<?php
						}
						else
						{
							?>
							<?php echo $cimg; ?>
						<?php } ?>
					</td>
					<td  <?php echo $rowspan; ?>>
						<?php
						$images = array("Cross.png", "Tick.png", "Question.png", "Pending.png", "MoneyBag.png", "RedMoneyBag.png");
						$img = $images[$attendee->attendstate];
						if ($attendee->attendstate == 1 && $feesAndBalances)
						{
							if ($feesAndBalances["feebalance"] < -0.01)
							{
								$img = $images[5];
							}
						}
						// pending state allowing for approval
						if ($attendee->attendstate == 3)
						{
							?>
							<img src="<?php echo JURI::root() . $pluginpath . '/assets/Pending.png'; ?>"  alt="<?php echo JText::_("JEV_PENDING"); ?>" />
							<a href="#changestate" onclick="if (confirm('<?php echo JText::_("JEV_APPROVE_ATTENDANCE_CHECK_CAPACITY") . "?"; ?>'))
										return listItemTask('cb<?php echo $i; ?>', 'attendees.approve');
									else
										return false;" title="<?php echo JText::_('JEV_APPROVE_ATTENDANCE'); ?>">
								(<img src="<?php echo JURI::root() . $pluginpath . '/assets/Tick.png'; ?>"  alt="<?php echo JText::_("JEV_APPROVE_ATTENDANCE"); ?>" />)
							</a>
								<?php
							}
							else
							{
								?>
							<a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>', 'attendees.changestate')" title="<?php echo JText::_('RSVP_CLICK_TO_CHANGE_STATE'); ?>">
		<?php
		echo '<img src="' . JURI::root() . $pluginpath . '/assets/' . $img . '"  style="height:16px;" alt="' . $img . '" />';
		?>
							</a>
							<?php
						}
						?>
					</td>
					<td   <?php echo $rowspan; ?>>
						<?php if ($attendee->waiting) { ?>
						<a href="#notwaiting" onclick="if (confirm('<?php echo addslashes(JText::sprintf("RSVP_PROMOTE_FROM_WAITING_LIST", $attendee->attendee)); ?>'))
									return listItemTask('cb<?php echo $i; ?>', 'attendees.notwaiting');
								else
									return false;" title="<?php echo JText::_('RSVP_PROMOTE_FROM_WAITING_LIST', true); ?>">
						<?php echo $wimg; ?>
						</a>
						<?php }
						else { ?>
						<a href="#waiting" onclick="if (confirm('<?php echo addslashes(JText::sprintf("RSVP_DEMOTE_TO_WAITING_LIST", $attendee->attendee)); ?>'))
									return listItemTask('cb<?php echo $i; ?>', 'attendees.waiting');
								else
									return false;" title="<?php echo JText::_('RSVP_DEMOTE_TO_WAITING_LIST', true); ?>">
						<?php echo $wimg; ?>
						</a>
						<?php } ?>
					</td>
					<td   <?php echo $rowspan; ?>>
						<?php
						$format = $this->params->get("timestampformat", "%Y-%m-%d %H:%M");
						echo strftime($format, strtotime($attendee->created));
						?>
					</td>
					<td   <?php echo $rowspan; ?>>
	<?php
	$format = $this->params->get("timestampformat", "%Y-%m-%d %H:%M");
	if ($attendee->modified != "0000-00-00 00:00:00")
	{
		echo strftime($format, strtotime($attendee->modified));
	}
	?>
					</td>
					<td   <?php echo $rowspan; ?>>
						<?php
						$img = $attendee->didattend ? 'Tick.png' : 'Cross.png';
						$task = $attendee->didattend ? 'notattend' : 'attend';
						$alt = !$attendee->didattend ? JText::_('RSVP_MARK_ATTENDANCE') : JText::_('RSVP_MARK_NONATTENDANCE');
						$action = !$attendee->didattend ? JText::_('RSVP_MARK_ATTENDANCE') : JText::_('RSVP_MARK_NONATTENDANCE');

						$mainframe = JFactory::getApplication();
						$img = '<img src="' . JURI::root() . $pluginpath . '/assets/' . $img . '"  style="height:16px;" alt="' . $alt . '" />';

						$didattend = '
							<a href="javascript:void(0);" onclick="return listItemTask(\'cb' . $i . '\',\'attendees.' . $task . '\')" title="' . $action . '">	' . $img . '</a>';
						// should we show how many guests are attending?
						if ($attendee->guestcount > 1 && $attendee->guestattend != "")
						{
							$guests = explode(",", $attendee->guestattend);
							$didattend .=" <br/>" . count($guests) . "/" . $attendee->guestcount;
						}
						echo $didattend;
						?>
					</td>
					<?php if (!isset($attendee->atdee_id))
					{
						$attendee->notes = "";
					} ?>
					<td  <?php echo $rowspan; ?>> <textarea rows="1" cols="5" name="notes[<?php echo $attendee->atdee_id; ?>]" id="attendeenotes"><?php echo $attendee->notes; ?></textarea></td>
	<?php
	if ($this->templateParams && $this->templateInfo->withticket)
	{
		if ($attendee->attendstate == 0)
		{
			?>
							<td   <?php echo $rowspan; ?> class="jevtickets" />
							<?php
						}
						else
						{
							JHtml::_('behavior.modal', 'a.jevmodal');
							?>
							<td   <?php echo $rowspan; ?> class="jevtickets">
								<div class="jevtickets">
									<a href="<?php echo JRoute::_("index.php?option=com_rsvppro&tmpl=component&task=attendees.ticket&attendee=" . $attendee->id); ?>"  title="<?php echo JText::_("JEV_PRINT_TICKET"); ?>"
									   class="jevmodal" rel="{handler: 'iframe', size: {x:600, y:500}}"  >
										<img src="<?php echo JURI::root() . "/components/com_rsvppro/assets/images/ticketicon.jpg"; ?>" alt="<?php echo JText::_("JEV_PRINT_TICKET"); ?>" style='vertical-align:middle' />
									</a>
								</div>
							</td>
									<?php
								}
							}
							if ($feesAndBalances && $feesAndBalances["hasfees"])
							{
								$showtransactions = true;
								?>
						<td   <?php echo $rowspan; ?> class="transactionscolumn">
							<a href="#edit" onclick="return listItemTask('cb<?php echo $i; ?>', 'attendees.transactions')" title="<?php echo JText::_('RSVP_TRANSACTIONS'); ?>">
						<?php
						echo count($feesAndBalances["transactions"]);
						$img = "MoneyBag.png";
						echo ' <img src="' . JURI::root() . $pluginpath . '/assets/' . $img . '"  style="height:16px;" alt="' . $img . '" />';
						?>
							</a>
						</td>
						<?php
					}
					else
					{
						?>
						<td   <?php echo $rowspan; ?> class="transactionscolumn"> - </td>
						<?php
					}

					$html = "";

					if ($params)
					{
						foreach ($params as $param)
						{
							if ($param['label'] != "" && $param["showinlist"])
							{
								if (is_array($param['value']) && $attendee->guestcount > 0)
								{
									$val = $param['value'][0];
									if ($val == "")
									{
										// need hard space for Joomla 3.3 backend stylinh
										$val = "&nbsp";
									}
									if ($attendee->attendstate != 0)
									{
										$html .='<td >' . stripslashes($val) . '</td>';
									}
									else
									{
										$html .='<td class="notattending"><em>' . stripslashes($val) . '</em></td>';
									}
								}
								else
								{
									if ($param['value'] == "")
									{
										// need hard space for Joomla 3.3 backend stylinh
										$param['value'] = "&nbsp";
									}
									if ($attendee->attendstate != 0)
									{
										$html .='<td ' . $rowspan . '>' . stripslashes($param['value']) . '</td>';
									}
									else
									{
										$html .='<td class="notattending"><em>' . stripslashes($param['value']) . '</em></td>';
									}
								}
							}
						}
					}

					echo $html;
					?>
				</tr>
					<?php
					// Now the other param rows
					if ($attendee->guestcount > 0 && $params)
					{
						for ($a = 1; $a < $attendee->guestcount; $a++)
						{
							?>
						<tr class="row<?php echo $k; ?>">
						<?php
						foreach ($params as $param)
						{
							if ($param['label'] != "" && $param["showinlist"])
							{
								if (is_array($param['value']))
								{
									$val = $param['accessible'] && isset($param['value'][$a]) ? $param['value'][$a] : "";
									if ($param['peruser'] <= 0)
									{
										// need hard space for Joomla 3.3 backend stylinh
										$val = "&nbsp;";
									}

									echo '<td >' . stripslashes($val) . '</td>';
								}
							}
						}
						?>
						</tr>
								<?php
							}
						}

						$k = 1 - $k;
					}

					if (!$showtransactions)
					{
						JFactory::getDocument()->addStyleDeclaration(".transactionscolumn {display:none;}");
					}
					?>
		</tbody>
		<tfoot>
			<tr>
				<td align="center" colspan="<?php echo $colcount; ?>"><?php
					echo $this->pageNav->getListFooter();
					if (version_compare(JVERSION, "3.0", 'ge'))
					{
						echo $this->pageNav->getLimitBox();
					}
					?>
				</td>
			</tr>
		</tfoot>
	</table>
	<input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT; ?>" />
	<input type="hidden" name="task" value="attendees.list" />
	<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
	<input type="hidden" name="atd_id[]" value="<?php echo $this->atd_id . "|" . $this->rp_id; ?>" />
	<input type="hidden" name="repeating" value="<?php echo $this->repeating; ?>" />
	<input type="hidden" name="filter_order" value="<?php echo $this->order; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->orderdir; ?>" />
	<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt("Itemid", 0); ?>" />

	<?php
	$securitycheck = md5($compparams->get("emailkey", "email key")." attendee list ".$user->id);
	?>
	<input type="hidden" name="sc" value="<?php echo $securitycheck ?>" />

<?php echo JHtml::_('form.token'); ?>
</form>
</div>

