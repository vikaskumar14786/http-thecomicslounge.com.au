<?php
/**
 * @copyright Copyright (C) 2008 GWE Systemts Ltd. All rights reserved.
 * @license By negotiation
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgContentJevpeople extends JPlugin
{

	private $alternative_article;
	private $sections;
	private $categories;

	function __construct(&$subject, $params)
	{
		parent::__construct($subject, $params);

	}

	// Joomla 1.6!!
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		return $this->onPrepareContent($article, $params, $page);

	}

	function onPrepareContent(&$article, &$params, $limitstart)
	{
		// only process events
		$option = JRequest::getCmd("option");
		if (!isset($option) || !($option == "com_content" || $option == "com_jevents" || $option == "com_jevpeople" || $option == "com_k2"))
			return true;

		// do not process the same article more than once (i.e. avoid recursion!)
		if (isset($article->jevp_processed)){
			return true;
		}
		$article->jevp_processed = true;
		
		// simple performance check to determine whether bot should process further
		if (strpos($article->text, '{jevp') === false)
		{
			return true;
		}

		// define the regular expression for the bot
		$regex = "#{jevp}(.*?){/jevp}#s";

		preg_match_all($regex, $article->text, $allmatches);
		if (!is_array($allmatches) || count($allmatches) !== 2)
		{
			return true;
		}
		for ($m=0;$m<count($allmatches[1]);$m++)
		{
			 $match = $allmatches[1][$m];
			 $replace = $allmatches[0][$m];

			$menuitem = intval($this->params->get("target_itemid", 0));

			for ($extra = 0; $extra < 20; $extra++)
			{
				$this->params->set("extras$extra","");
			}
			if (intval($match)>0)
			{
				JRequest::setVar("peoplelkup_fv", intval($match));
				$link = JRoute::_("index.php?option=com_jevents&peoplelkup_fv=" . $match . "&Itemid=" . $menuitem);
				$this->params->set("extras0","jevp:".intval($match));
			}
			else
			{
				JRequest::setVar("peoplesearch_fv", $match);
				$link = JRoute::_("index.php?option=com_jevents&peoplelkup_fv=" . urlencode($match) . "&Itemid=" . $menuitem);								
			}
			require_once (JPATH_SITE . "/modules/mod_jevents_latest/helper.php");

			$jevhelper = new modJeventsLatestHelper();
			$theme = JEV_CommonFunctions::getJEventsViewName();

			JPluginHelper::importPlugin("jevents");

			// record what is running - used by the filters
			$registry = JRegistry::getInstance("jevents");
			$registry->set("jevents.activeprocess", "mod_jevents_latest");
			$registry->set("jevents.moduleparams", $this->params);
			$registry->set("jevents.moduleid", rand());

			/*
			  if (JRequest::getInt("jsp",0)){
			  $this->params->set("modlatest_Mode",3);
			  $this->params->set("modlatest_Days",10);
			  }
			 */
			$viewclass = $jevhelper->getViewClass($theme, 'mod_jevents_latest', $theme .  "/latest", $this->params);

			$modview = new $viewclass($this->params, 0);
			$return = $modview->displayLatestEvents();

			$return .="<br style='clear:both'/>";

			//$return .="<strong>".JText::sprintf("View all my events <a href='%s'>here</a>",$link)."<strong>";
			$liveuri =  JURI::getInstance();
			$uri = clone $liveuri;
			$query = $uri->getQuery();

			if ($this->params->get("showpasteventreset", 0))
			{
				$query = str_replace("&timelimit_fv=1", "", $query);
				$query = str_replace("&timelimit_fv=0", "", $query);
				$query = str_replace("timelimit_fv=1", "", $query);
				$query = str_replace("timelimit_fv=0", "", $query);
				//$query .= strpos($query, "&") > 0 ? "&timelimit_fv=xxqqxxqqxxqq" : "timelimit_fv=xxqqxxqqxxqq";
				$uri->setQuery($query);
				$current = $uri->toString(array('scheme', 'host', 'port', 'path', 'query'));
				$current .= strpos($current, "?") > 0 ? "&timelimit_fv=xxqqxxqqxxqq" : "?timelimit_fv=xxqqxxqqxxqq";
				ob_start();
				?>
				<label for="jsp"><?php echo JText::_("JEV_SHOW_PAST"); ?>
					<input type="checkbox" id="jsp" name="jsp" value="1" <?php echo JRequest::getInt('timelimit_fv', 0) ? 'checked="checked"' : '' ?> onclick="var loc='<?php echo $current; ?>';var newval = document.getElementById('jsp').checked?1:0;loc=loc.replace('xxqqxxqqxxqq',newval);document.location.replace(loc)"/>
				</label>
				<?php
				$return .= ob_get_clean();
			}
			// must remove the jevpeople filter from the memory cache - and clear the session memory
			jevFilterProcessing::getInstance("", "", "peoplelookup");
			JRequest::setVar("peoplelkup_fv", "");
			JRequest::setVar("peoplesearch_fv", "");
			JFactory::getApplication()->setUserState('peoplelkup_fv_ses', "");

			$article->text = str_replace($replace, $return, $article->text);
		}
		return true;

	}

}
