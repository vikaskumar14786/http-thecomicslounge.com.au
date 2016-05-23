<?php defined('_JEXEC') or die('Restricted access'); ?>

<?php JHTML::_('behavior.tooltip'); 
	$compparams = JComponentHelper::getParams("com_jevpeople");
	$Itemid = JRequest::getInt("Itemid");

	$params = JComponentHelper::getParams('com_media');
	$mediabase = JURI::root().$params->get('image_path', 'images/stories');
	// folder relative to media folder
	$folder = "jevents/jevpeople";
?>
<?php if ($compparams ->get('show_page_heading', 1)) : ?>
<h1>
	<?php echo $this->escape($compparams->get('page_heading')); ?>
</h1>
<?php endif; ?>

<div class='jevpeople'>
<form action="<?php echo JRoute::_("index.php?option=com_jevpeople&task=people.people&Itemid=$Itemid");?>" method="post" name="adminForm">
<input type="hidden" value="0" id="filter_reset" name="filter_reset">
<input type="hidden" value="1" id="jevpcustomfields" name="jevpcustomfields">
<ul class="jevpeoplefloatlist"  style="list-style-type: none ">

	<li class="jevpeoplefloatlist_row">
        <span class="jevepeoplefloatlist_label">
		<?php echo JText::_( 'FILTER' ); ?>: </span>
		<input type="text" name="search" id="search" value="<?php echo $this->lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
	</li>
	<?php
	if ($compparams->get("type","")=="" && $this->lists['typefilter']!="") {
		?>
        <li class="jevpeoplefloatlist_row">
			<?php
			echo $this->lists['typefilter'];
			?>
		</li>
		<?php
	}
	if ($compparams->get("jevpcat","")=="" && $this->lists['catid']!="") {
		?>
        <li class="jevpeoplefloatlist_row">
			<?php
		echo $this->lists['catid'];
			?>
		</li>
		<?php
	}
	?>
    <li class="jevpeoplefloatlist_row">
			<br/>
			<button onclick="this.form.submit();"><?php echo JText::_('GO'); ?></button>
			<button onclick="if(document.getElementById('search')) document.getElementById('search').value='';
				if (this.form.getElementById('filter_catid')) this.form.getElementById('filter_catid').value='0';
				if (this.form.getElementById('filter_state')) this.form.getElementById('filter_state').value='';
				try{
					JeventsFilters.reset(this.form);
				}
				catch (e) {
				};
				this.form.submit();"><?php echo JText::_('RESET'); ?>
			</button>
		</li>
		<?php
	if (isset($this->lists["customfield"])) {

		$hasreset = false;
		foreach ($this->lists["customfield"] as $filter)
		{
			if (!isset($filter["title"]))
			{
				continue;
			}
			?>
            <li class="jevpeoplefloatlist_row">
				<?php
				if (strlen($filter["title"]) > 0)
				{
					echo "<span class='jevepeoplefloatlist_label'>" . $filter["title"] . "</span>";
				}
				?>
				<div class="jevfilterinput">
					<?php echo $filter["html"] ?>
				</div>
			</li>
			<?php
			if (strpos($filter["html"], 'filter_reset') > 0)
			{
				$hasreset = true;
			}
		}
	}
	?>
	</ul>
<div class="peoplelist" style="clear:left">
	<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist  table table-striped">
	<thead>
		<tr>
			<th class="title">
				<?php echo JHTML::_('grid.sort',  'PERSON', 'pers.title', $this->lists['order_Dir'], $this->lists['order'] , "people.people"); ?>
			</th>
			<th>
				<?php echo JText::_( 'JEV_PEOPLE_EVENTS' ); ?>
			</th>
			<?php if ($compparams->get('showimage',1)){ ?>
			<th>
				<?php 
				 echo JHTML::_('grid.sort',  JText::_('COM_JEVPEOPLE_PERSON_IMAGE'), 'pers.image', $this->lists['order_Dir'], $this->lists['order'], "people.people" );
				?>
			</th>
			<?php } ?>
			<th>
				<?php 
				echo JHTML::_('grid.sort',  'JEV_CATEGORY_1', 'catname0', $this->lists['order_Dir'], $this->lists['order'] , "people.people");
				?>
			</th>
			<th>
				<?php 
				echo JHTML::_('grid.sort',  'JEV_CATEGORY_2', 'catname1', $this->lists['order_Dir'], $this->lists['order'] , "people.people"); 
				?>
			</th>
			<th>
				<?php 
				echo JHTML::_('grid.sort',  'JEV_CATEGORY_3', 'catname2', $this->lists['order_Dir'], $this->lists['order'] , "people.people"); 
				?>
			</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="9">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	$params = JComponentHelper::getParams("com_jevpeople");
	$targetid = intval($params->get("targetmenu",0));
	if ($targetid>0){
		$menu =  JFactory::getApplication()->getMenu();
		$targetmenu = $menu->getItem($targetid);
		if ($targetmenu->component!="com_jevents"){
			$targetid = JEVHelper::getItemid();
		}
		else {
			$targetid = $targetmenu->id;
		}
	}
	else {
		$targetid = JEVHelper::getItemid();
	}
	$task = $params->get("jevview","month.calendar");


	$k = 0;
	for ($i=0, $n=count( $this->items ); $i < $n; $i++)
	{
		$row = &$this->items[$i];

		$tmpl = "";
		if (JRequest::getString("tmpl","")=="component"){
			$tmpl = "&tmpl=component";
		}

		$link 	= JRoute::_( 'index.php?option=com_jevpeople&task=people.detail&pers_id='. $row->pers_id . $tmpl ."&se=1"."&title=".JApplication::stringURLSafe($row->title));

		$eventslink = JRoute::_("index.php?option=com_jevents&task=$task&peoplelkup_fv=".$row->pers_id."&Itemid=".$targetid);

		// global list
		$global	= $this->_globalHTML($row,$i);

		$ordering = ($this->lists['order'] == 'pers.ordering');
		?>
		<tr class="<?php echo "row$k"; ?>">
			<td>
				<span class="editlinktip hasTip" title="<?php echo JText::_( 'JEV_VIEW_PERSON' );?>::<?php echo $this->escape($row->title); ?>">
					<a href="<?php echo $link; ?>">
						<?php echo $this->escape($row->title); ?></a>
				</span>
			</td>
			<td align="center">
				<?php if ($row->hasEvents) {?>
				<span class="editlinktip hasTip" title="<?php echo JText::_( 'JEV_VIEW_ASSOCIATED_EVENTS' );?>::<?php echo $this->escape($row->title); ?>">
					<a href="<?php echo $eventslink; ?>">
						<img src="<?php echo JURI::base();?>components/com_jevpeople/assets/images/jevents_event_sml.png" alt="Calendar" style="height:24px;margin:0px;"/>
				</span>
				<?php } ?>
			</td>
			<?php if ($compparams->get('showimage',1)){ ?>
			<td align="center">
				<?php 
				if ($row->image!=""){
					$thimg = '<img src="'.$mediabase.'/'.$folder.'/thumbnails/thumb_'.$row->image.'" />' ;
					?>
				<span class="editlinktip hasTip" title="<?php echo JText::_( 'JEV_VIEW_ASSOCIATED_EVENTS' );?>::<?php echo $this->escape($row->title); ?>">
					<a href="<?php echo $link; ?>"><?php	echo $thimg; ?></a>
				</span>
				<?php
				}
				?>
			</td>
			<?php } ?>
			<td>
				<?php echo $this->escape($row->catname0); ?>
			</td>
			<td>
				<?php echo $this->escape($row->catname1); ?>
			</td>
			<td>
				<?php echo $this->escape($row->catname2); ?>
			</td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
	</tbody>
	</table>
</div>

<?php if ($compparams->get("showmap",0)) echo $this->loadTemplate("map");?>

	<input type="hidden" name="option" value="com_jevpeople" />
	<input type="hidden" name="Itemid" value="<?php echo $Itemid;?>" />
	<input type="hidden" name="task" value="people.people" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
	<?php if (JRequest::getString("tmpl","")=="component"){ ?>
	<input type="hidden" name="tmpl" value="component" />	
	<?php } ?>
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
</div>