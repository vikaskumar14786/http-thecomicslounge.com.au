<?php
defined('_JEXEC') or die('Restricted access');

function DefaultGetRadioCategories($view, $catid, $args, $catidList = null, $with_unpublished = false, $require_sel = false, $catidtop = 0, $fieldname = "catid", $sectionname = JEV_COM_COMPONENT, $excludeid = false, $order = "ordering", $eventediting = false)
{

	// need to declare this because of bug in Joomla JHtml::_('select.options', on content pages - it loade the WRONG CLASS!
	if (JVersion::isCompatible("3.0"))
	{
		include_once(JPATH_SITE . "/libraries/cms/html/category.php");
	}
	else
	{
		include_once(JPATH_SITE . "/libraries/joomla/html/html/category.php");
	}

	ob_start();
	$options = JHtml::_('category.options', $sectionname);
	if ($catidList != null)
	{
		$cats = explode(',', $catidList);
		$count = count($options);
		for ($o = 0; $o < $count; $o++)
		{
			if (!in_array($options[$o]->value, $cats))
			{
				unset($options[$o]);
			}
		}
		$options = array_values($options);
	}

	$user = JFactory::getUser();
	$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
	$authorisedonly = $params->get("authorisedonly", 0);
	if ($authorisedonly)
	{
		$jevuser = JEVHelper::getAuthorisedUser();
		if ($jevuser)
		{
			if ($jevuser->categories == "all")
			{
				$cats = array();
				foreach ($options as $opt)
				{
					$cats[] = $opt->value;
				}
			}
			else if ($jevuser->categories != "")
			{
				$cats = explode("|", $jevuser->categories);
			}
			else
			{
				if (JRequest::getInt("evid", 0) > 0)
				{
					// TODO - this should check the creator of the event
					$action = 'core.edit';
					$cats = $user->getAuthorisedCategories('com_jevents', $action);
					$action = 'core.edit.own';
					$cats = array_merge($cats, $user->getAuthorisedCategories('com_jevents', $action));
				}
				else
				{
					$action = 'core.create';
					$cats = $user->getAuthorisedCategories('com_jevents', $action);
				}
			}
		}
		else
		{
			if (JRequest::getInt("evid", 0) > 0)
			{
				// TODO - this should check the creator of the event
				$action = 'core.edit';
				$cats = $user->getAuthorisedCategories('com_jevents', $action);
				$action = 'core.edit.own';
				$cats = array_merge($cats, $user->getAuthorisedCategories('com_jevents', $action));
			}
			else
			{
				$action = 'core.create';
				$cats = $user->getAuthorisedCategories('com_jevents', $action);
			}
		}
	}
	else
	{
		if (JRequest::getInt("evid", 0) > 0)
		{
			// TODO - this should check the creator of the event
			$action = 'core.edit';
			$cats = $user->getAuthorisedCategories('com_jevents', $action);
			$action = 'core.edit.own';
			$cats = array_merge($cats, $user->getAuthorisedCategories('com_jevents', $action));
		}
		else
		{
			$action = 'core.create';
			$cats = $user->getAuthorisedCategories('com_jevents', $action);
		}
	}

	$dispatcher = & JEventDispatcher::getInstance();
	$dispatcher->trigger('onGetAccessibleCategoriesForEditing', array(& $cats));

	// allow anon-user event creation through
	if (isset($user->id))
	{
		$count = count($options);
		for ($o = 0; $o < $count; $o++)
		{
			if (!in_array($options[$o]->value, $cats))
			{
				unset($options[$o]);
			}
		}
		$options = array_values($options);
	}

	// Attach the images
	$db = JFactory::getDbo();
	$query = $db->getQuery(true)->select('c.id AS id, a.name AS asset_name, c.params')->from('#__categories AS c')
					->innerJoin('#__assets AS a ON c.asset_id = a.id')->where('c.extension = ' . $db->quote('com_jevents'))->where('c.published = 1');
	$db->setQuery($query);
	$allCategories = $db->loadObjectList('id');
	$count = count($options);
	for ($o = 0; $o < $count; $o++)
	{
		$cat = $allCategories[$options[$o]->value];
		$catparams = new JRegistry(isset($cat->params) ? $cat->params : null);
		$image = $catparams->get("image", false);
		if ($image)
		{
			$options[$o]->image = "<img src='" . JURI::root() . $image . "' style='width:20px' />";
		}
		else
		{
			$options[$o]->image = "";
		}
	}


	// if only one category then preselect it
	if (count($options) == 1)
	{
		$catid = current($options)->value;
	}
	
	$script = <<<SCRIPT
   function clickCategory(elem){
	   elem = elem.getParent().getElementsByTagName('input')[0];
	   var elemText = elem.getParent().getElementsByTagName('span')[0];
	   $$($('catid').options).each(function(opt){
		if (opt.value == elem.value){
			opt.selected = elem.checked;
		}
	   });
	   }
SCRIPT;
	$doc = JFactory::getDocument();
	$doc->addScriptDeclaration($script);
	?>
<div class="radiocats">
	<?php
	// should we offer multi-choice categories?
	// do not use jev_com_component incase we call this from locations etc.
	$params = JComponentHelper::getParams(JRequest::getCmd("option"));
	foreach ($options as $key => $opt)
	{
		?>
		<label for="<?php echo $fieldname . $key; ?>" class="catlabels" >
			<input type="checkbox" name="<?php echo $fieldname; ?>[]" id="<?php echo $fieldname . $key; ?>" value="<?php echo $opt->value; ?>" onchange="clickCategory(this)" />
			<?php echo $opt->image; ?>			
			<span><?php echo str_replace("- ","",$opt->text); ?></span>
		</label>
		<?php
	}
	?>
</div>
	<?php
	return ob_get_clean();

}