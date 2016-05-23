<?php defined('_JEXEC') or die('Restricted access'); 

if (!$this->loadedFromTemplate('com_jevpeople.people.'.$this->person->type_id.'.detail', $this->person))
{
	if (JFile::exists(JPATH_SITE . "/components/com_jevents/assets/css/jevcustom.css")) {
		$document = JFactory::getDocument();
		JEVHelper::stylesheet('jevcustom.css', 'components/' . JEV_COM_COMPONENT . '/assets/css/');
	}
?>
<div class='jevpersondetail'>
	<h3><?php echo $this->person->title;?></h3>
	
	<fieldset class="adminform">
		<legend><?php echo JText::_( 'DESCRIPTION' ); ?></legend>
		<?php 
		$compparams = JComponentHelper::getParams("com_jevpeople");
		if ($compparams->get('menu-meta_description'))
		{
			$document->setDescription($compparams->get('menu-meta_description'));
		}

		if ($compparams->get('menu-meta_keywords'))
		{
			$document->setMetadata('keywords', $compparams->get('menu-meta_keywords'));
		}

		if ($compparams->get('robots'))
		{
			$document->setMetadata('robots', $compparams->get('robots'));
		}
		if (strlen($this->person->image)>0) {

			// Get the media component configuration settings
			$params = JComponentHelper::getParams('com_media');
			// Set the path definitions
			$mediapath =  JURI::root(true).'/'.$params->get('image_path', 'images/stories');

			echo '<img src="'.$mediapath.'/jevents/jevpeople/'.$this->person->image.'" alt="'.htmlspecialchars($this->person->imagetitle).'"/>';
		}
		if ($this->perstype->showaddress>0) {
			if (strlen($this->person->street)>0) echo $this->person->street."<br/>";
			if (strlen($this->person->city)>0) echo $this->person->city."<br/>";
			if (strlen($this->person->state)>0) echo $this->person->state."<br/>";
			if (strlen($this->person->postcode)>0) echo $this->person->postcode."<br/>";
			if (strlen($this->person->country)>0) echo $this->person->country."<br/>";
		}
		echo $this->person->description;
		echo "<br/>";
		if (strlen($this->person->www)>0) echo "www: <a href='".$this->person->www."' target='_blank' >".$this->person->title."</a>";


		// New custom fields
		$compparams = JComponentHelper::getParams("com_jevpeople");
		$template = $compparams->get("template","");
		if ($template!=""){
			$html = "";
			$plugin = JPluginHelper::getPlugin('jevents', 'jevcustomfields' );
			$pluginparams = new JRegistry($plugin->params);

			$templatetop = $pluginparams->get("templatetop","<table border='0'>");
			$templaterow = $pluginparams->get("templatebody","<tr><td class='label'>{LABEL}</td><td>{VALUE}</td>");
			$templatebottom = $pluginparams->get("templatebottom","</table>");

			$html = $templatetop;
			$user = JFactory::getUser();
			foreach ($this->person->customfields as $customfield) {
				$cfaccess = $customfield["access"];
				if (version_compare(JVERSION, "1.6.0", 'ge'))
				{
					$cfaccess = explode(",", $cfaccess);
					if (count(array_intersect($cfaccess, JEVHelper::getAid($user, 'array'))) == 0) continue;
				}
				else
				{
					if (intval($cfaccess) > $user->aid) continue;
				}

				if (!is_null($customfield["hiddenvalue"]) && trim($customfield["value"]) == $customfield["hiddenvalue"])
					continue;

				if (is_null($customfield["hiddenvalue"]) && trim($customfield["value"]) == "")
					continue;

				$outrow = str_replace("{LABEL}",$customfield["label"],$templaterow);
				$outrow = str_replace("{VALUE}",nl2br($customfield["value"]),$outrow );
				$html .= $outrow ;
			}
			$html .= $templatebottom;

			echo $html;
		}

		?>
	</fieldset>
	<?php if ($this->perstype->showaddress>0) {?>
	<fieldset class="adminform">
		<legend><?php echo JText::_( 'GOOGLE_MAP' ); ?></legend>
		<?php echo JText::_( 'CLICK_MAP' ); ?><br/><br/>
		<div id="gmapppl" style="width: 450px; height: 300px"></div>
	</fieldset>
	<?php } 

	if ($compparams->get("eventsindetail",0)){
?>
<fieldset class="adminform">
	<legend><?php echo JText::_( 'JEV_UPCOMING_EVENTS' ); ?></legend>
	<?php
	require_once (JPATH_SITE."/modules/mod_jevents_latest/helper.php");

	$jevhelper = new modJeventsLatestHelper();
	$theme = JEV_CommonFunctions::getJEventsViewName();

	JPluginHelper::importPlugin("jevents");
	$viewclass = $jevhelper->getViewClass($theme, 'mod_jevents_latest',$theme."/"."latest", $compparams);

	// record what is running - used by the filters
	$registry	= JRegistry::getInstance("jevents");
	$registry->set("jevents.activeprocess","mod_jevents_latest");
	$registry->set("jevents.moduleid", "mpdetail");

	$menuitem = intval($compparams->get("targetmenu",0));
	if ($menuitem>0){
		$compparams->set("target_itemid",$menuitem);
	}
	// ensure we use these settings
	$compparams->set("modlatest_useLocalParam",1);

        // don't use 19 since that is the last one and some of the other addons assume its not used :(
	$compparams->set("extras18","jevp:".intval($this->person->pers_id));

	$registry->set("jevents.moduleparams", $compparams);
        
        $task = $compparams->get("jevview","month.calendar");
	$link = JRoute::_("index.php?option=com_jevents&task=$task&peoplelkup_fv=".$this->person->pers_id."&Itemid=".$menuitem);

	$loclkup_fv = JRequest::setVar("peoplelkup_fv",$this->person->pers_id);
	$modview = new $viewclass($compparams, 0);
        if ($compparams->get("modlatest_LinkToCal", 2) == 1){
             echo "<strong>".JText::sprintf("JEV_ALL_EVENTS",$link)."</strong><br/><br/>";
         }
	echo $modview->displayLatestEvents();
	JRequest::setVar("peoplelkup_fv",$loclkup_fv);

	echo "<br style='clear:both'/>";

        if ($compparams->get("modlatest_LinkToCal", 2) == 2){
             echo "<strong>".JText::sprintf("JEV_ALL_EVENTS",$link)."</strong>";
        }
	?>
</fieldset>
<?php
	}
	?>
</div>
<?php 
}