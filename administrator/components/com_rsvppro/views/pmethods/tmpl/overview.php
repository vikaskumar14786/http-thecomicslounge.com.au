<?php 
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: overview.php 1676 2010-01-20 02:50:34Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');
JHtml::_('behavior.tooltip');

$pathIMG = JURI::root() . '/administrator/images/';

$db	= JFactory::getDBO();
$user = JFactory::getUser();
$params = JComponentHelper::getParams('com_rsvppro');

$rows = $this->rows;
?>

<form action="index.php" method="post"  name="adminForm" id="adminForm">
		<?php if (!empty($this->sidebar)) : ?>
		<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
		</div>
		<div id="j-main-container" class="span10">
			<?php else : ?>
			<div id="j-main-container">
<?php endif; ?>
			<fieldset id="filter-bar">
                                <div style="float:left;">
                                        <?php echo JText::_('JEV_RSVPPRO_PMETHODS_DESC'); ?>
                                </div>
			</fieldset>


			<div id="editcell">
				<table class="adminlist table table-striped">
					<thead>
						<tr>
							<th width="20" nowrap="nowrap"  style="display:none;">
								<?php echo JHtml::_('grid.checkall'); ?>
							</th>
							<th width="5">
								<?php echo JText::_('NUM'); ?>
							</th>
							<th class="title">
								<?php echo JText::_('JEV_TITLE'); ?>
							</th>
							<th class="name">
							<?php echo JText::_('JEV_NAME'); ?>
							</th>			
							<th class="pub" width="10%" nowrap="nowrap"><?php echo JText::_('JEV_PUBLISHED'); ?></th>			
						</tr>
					</thead>
					<tbody>
						<?php
						$k = 0;
						for ($i = 0, $n = count($rows); $i < $n; $i++)
						{
							$row = &$rows[$i];
                                                        //Load the plugin params for the Description
                                                        //JPluginHelper does not fetch data if plugin is disabled! Create our own query.
                                                        //$plugin = JPluginHelper::getPlugin($row->folder, $row->element);
                                                        // Build query
                                                        $db    = JFactory::getDbo();
                                                        $query = $db->getQuery(true);

                                                        $query
                                                            ->select( 'manifest_cache' )
                                                            ->from(   '#__extensions' )
                                                            ->where(  'type = ' . $db->q('plugin') )
                                                            ->where(  'folder = ' . $db->q($row->folder) )  // Plugin type
                                                            ->where(  'element = ' . $db->q($row->element) );

                                                        // Execute query
                                                        $db->setQuery($query);
                                                        try
                                                        {
                                                            $result = $db->loadResult();
                                                        }
                                                        catch (RuntimeException $e)
                                                        {
                                                            return false;
                                                        }

                                                        // Parse parameters
                                                        if (!empty($result))
                                                        {
                                                            $params = new JRegistry($result);
                                                            $description    = $params->get('description', '');
                                                        }

							if (strpos($row->name, "com_") === 0)
							{
								$lang = JFactory::getLanguage();
								$parts = explode(".", $row->name);
								$lang->load($parts[0]);
							}
							$link = JRoute::_('index.php?option=com_plugins&filter_search='.$row->name);
							?>
							<tr class="<?php echo "row$k"; ?>">

								<td width="20" style="display:none;">
									<?php echo JHtml::_('grid.id', $i, $row->extension_id); ?>
								</td>
								<td>
                                                                        <?php echo $i + 1; ?>
								</td>
								<td>
									<span class="editlinktip hasjevtip" title="<?php RsvpHelper::tooltipText('JEV_RSVPPRO_EDIT_PAYMENT_METHOD',$row->name); ?>">
										<a href="<?php echo $link; ?>">
									<?php echo $this->escape(JText::_($row->name)); ?></a>   <br/>                                                                         
									</span>
                                                                    <span class="desc">
                                                                        <?php 
                                                                        // Ok now we need to load the language file for the translation!
                                                                        $lang = JFactory::getLanguage(); 
                                                                        $lang->load('plg_' . $row->folder . '_' . $row->element, JPATH_ADMINISTRATOR);
                                                                        //Output the translated language string.
                                                                        echo JText::_($description);
                                                                        ?>
                                                                    </span>
								</td>
								<td>
								<?php echo $this->escape($row->element); ?>

								</td>
								<td align="center">
                                                                <?php
                                                                $img = $row->enabled ? JHTML::_('image', 'admin/tick.png', '', array('title' => ''), true) : JHTML::_('image', 'admin/publish_x.png', '', array('title' => ''), true);
                                                                ?>
									<a href="javascript: void(0);" onclick="return listItemTask('cb<?php echo $i; ?>','<?php echo $row->enabled ? 'pmethods.unpublish' : 'pmethods.publish'; ?>')"><?php echo $img; ?></a>
								</td>
							</tr>
                                                        <?php
                                                        $k = 1 - $k;
                                                }
                                                ?>
					</tbody>
				</table>
			</div>

			<input type="hidden" name="option" value="com_rsvppro" />
			<input type="hidden" name="task" value="pmethods.overview" />
			<input type="hidden" name="boxchecked" value="0" />
<?php echo JHTML::_('form.token'); ?>
		</div>
</form>
