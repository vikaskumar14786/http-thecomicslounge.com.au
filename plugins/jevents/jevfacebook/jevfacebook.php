<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
JLoader::register('JevJoomlaVersion',JPATH_ADMINISTRATOR."/components/com_jevents/libraries/version.php");

class plgJEventsjevfacebook extends JPlugin
{

	public static function tagsDone($done=false){
		static $tagsdone = false;
		if ($done) {
			$tagsdone = true;
		}
		return $tagsdone ;
	}
	
	function onDisplayCustomFields(&$row)
	{

		$lang = JFactory::getLanguage();
		$lang->load("plg_jevents_jevfacebook", JPATH_ADMINISTRATOR);

		$Itemid = JRequest::getInt('Itemid');
		$uri =  JURI::getInstance(JURI::base());
		$root = $uri->toString(array('scheme', 'host', 'port'));
		$link = $root . JRoute::_($row->viewDetailLink($row->yup(), $row->mup(), $row->dup(), false, $Itemid), false);
               
		if (JBrowser::getInstance()->isSSLConnection())
        {
			$ssl = 'https://';
        } else {
            $ssl = 'http://';
        }
                        

		if ($this->params->get("like", 1))
		{
			$showfaces = $this->params->get("showfaces", 1) ? "true" : "false";
			$layout = $this->params->get("layoutstyle", "standard");
			$width = $this->params->get("width", 450);
			$height = $this->params->get("height", 40);
			$verb = $this->params->get("verb", "like");
			$commentslang = $this->params->get("commentslang", "en_US");			
			$colourscheme = $this->params->get("colourscheme", "light");
			$html = '<div class="facebook">
		<iframe src="' . $ssl . 'www.facebook.com/plugins/like.php?href=' . urlencode($link) . '&amp;layout=' . $layout . '&amp;show_faces=' . $showfaces . '&amp;width=' . $width . '&amp;action=' . $verb . '&amp;colorscheme=' . $colourscheme . '&amp;min-height=40" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:' . $width . 'px; min-height:' . $height . 'px;" allowTransparency="true">
		</iframe>
		</div>';
			$row->_fblike = $html;
		}
		else
		{
			$row->_fblike = "";
		}

		if ($this->params->get("share", 1))
		{
			if ($this->params->get("sharecounter", 1))
			{
				$row->_fbshare = '<a name="fb_share" type="button_count" href="' . $ssl . 'www.facebook.com/sharer.php">' . JText::_("JEV_Share") . '</a><script src="'.$ssl.'static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script>';
			}
			else
			{
				$row->_fbshare = '<a name="fb_share" type="button"  href="'.$ssl.'www.facebook.com/sharer.php">' . JText::_("JEV_Share") . '</a><script src="'.$ssl.'static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript"></script>';
			}
		}
		else
		{
			$row->_fbshare = "";
		}
		
		if ($this->params->get("comments", 0))
		{
			$width = $this->params->get("width", 450);
			ob_start();
			?>
			<div id="fb-root"></div>
			<!-- <script src="<?php echo $ssl;; ?>connect.facebook.net/<?php echo $commentslang; ?>/all.js#xfbml=1"></script>//-->
			<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/<?php echo $commentslang; ?>/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
			<fb:comments href="<?php $pageURL = JURI::current(); echo $pageURL; ?>" num_posts="10"   width="<?php echo $width; ?>"></fb:comments> 
			<?php
			$row->_fbcomments = ob_get_clean();
		}		
		else {
			$row->_fbcomments = "";
		}

		if ($this->params->get("like", 1) || $this->params->get("share", 1))
		{
			// check if detail page layout is enabled
			// find published template
			static $template_name;
			static $template;
			
			if (!isset($template))
			{
				$db = JFactory::getDBO();
				$db->setQuery("SELECT * FROM #__jev_defaults WHERE state=1 AND name= " . $db->Quote($template_name) . " AND value<>'' AND ".'language in ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
				//$db->setQuery("SELECT * FROM #__jev_defaults WHERE state=1 AND name= 'icalevent.detail_body'");
				$template = $db->loadObject();
			}
			if (is_null($template) || $template->value == "")
			{
				$mainframe = JFactory::getApplication();  // RSH 11/11/10 Make J!1.6 compatible
				// add facebook meta tags to page
				$facebooktags = "\n" . '<!--facebook tags-->' . "\n\t";
				$facebooktags .= '<meta property="og:title" content="' . $row->title() . '" />' . "\n\t";
				$desc = htmlspecialchars(strip_tags($row->content()));
				$desc = str_replace("\n","",$desc);
				$desc = str_replace("\r","",$desc);
				$length = 200; //modify for desired width
				if (strlen($desc) >= $length) {
					$desc = substr($desc, 0, strpos(wordwrap($desc, $length), "\n"));
				}
				//$desc = "Event Description";
				$facebooktags .= '<meta property="og:description" content="' . $desc . '" />' . "\n\t";
				$facebooktags .= '<meta property="og:site_name" content="' . $mainframe->getCfg('sitename') . '" />' . "\n\t";
				if (isset($row->_imageurl1) && $row->_imageurl1 != "")
				{
					$customimage = $row->_imageurl1;
					if (strpos($customimage, "/")===0) $customimage = substr ($customimage, 1);
					$customimage = (strpos($customimage, "http://")===false && strpos($customimage, "https://")===false)? JURI::root().$customimage: $customimage;
					$facebooktags .= '<meta property="og:image" content="' . $customimage . '" />';
				}
				$doc = JFactory::getDocument();
				if (is_callable(array($doc, "addCustomTag")))
				{
					$doc->addCustomTag($facebooktags);
					plgJEventsjevfacebook::tagsDone(true);
				}
			}
			return $row->_fblike . $row->_fbshare . $row->_fbcomments ;
			
		}
	}

	static function fieldNameArray($layout='detail')
	{
		if ($layout != "detail")
			return array();
		$labels = array();
		$values = array();
		$labels[] = JText::_("JEV_FACEBOOK_LIKE", true);
		$values[] = "JEV_FBLIKE";
		$labels[] = JText::_("JEV_FACEBOOK_SHARE", true);
		$values[] = "JEV_FBSHARE";
		$labels[] = JText::_("JEV_FACEBOOK_COMMENTS", true);
		$values[] = "JEV_FBCMT";

		$return = array();
		$return['group'] = JText::_("JEV_FACEBOOK_OUTPUT", true);
		$return['values'] = $values;
		$return['labels'] = $labels;

		return $return;

	}

	static function substitutefield($row, $code)
	{
		$mainframe = JFactory::getApplication();  // RSH 11/11/10 Make J!1.6 compatible
		// add facebook meta tags to page
		$facebooktags = "\n" . '<!--facebook tags-->' . "\n\t";
		$facebooktags .= '<meta property="og:title" content="' . $row->title() . '" />' . "\n\t";
		$desc = htmlspecialchars(strip_tags($row->content()));
		$desc = str_replace("\n","",$desc);
		$desc = str_replace("\r","",$desc);
		$length = 200; //modify for desired width
		if (strlen($desc) >= $length) {
			$desc = substr($desc, 0, strpos(wordwrap($desc, $length), "\n"));
		}
		//$desc = "Event Description";
		$facebooktags .= '<meta property="og:description" content="' . $desc . '" />' . "\n\t";
		$facebooktags .= '<meta property="og:site_name" content="' . $mainframe->getCfg('sitename') . '" />' . "\n\t";
		if (isset($row->_imageurl1) && $row->_imageurl1 != "")
		{
			$customimage = $row->_imageurl1;
			if (strpos($customimage, "/")===0) $customimage = substr ($customimage, 1);
			$customimage = (strpos($customimage, "http://")===false && strpos($customimage, "https://")===false)? JURI::root().$customimage: $customimage;
			$facebooktags .= '<meta property="og:image" content="' . $customimage . '" />';
		}
		$doc = JFactory::getDocument();

		if ($code == "JEV_FBLIKE")
		{
			if (isset($row->_fblike))
			{

				if (is_callable(array($doc, "addCustomTag")) && !plgJEventsjevfacebook::tagsDone() )
				{
					$doc->addCustomTag($facebooktags);
					plgJEventsjevfacebook::tagsDone(true);
				}

				return $row->_fblike;
			}
			return "";
		}
		if ($code == "JEV_FBSHARE")
		{
			if (isset($row->_fbshare))
			{

				if (is_callable(array($doc, "addCustomTag")) && !plgJEventsjevfacebook::tagsDone() )
				{
					$doc->addCustomTag($facebooktags);
					plgJEventsjevfacebook::tagsDone(true);
				}

				return $row->_fbshare;
			}
			return "";
		}
		if ($code == "JEV_FBCMT")
		{
			if (isset($row->_fbcomments))
			{
				if (is_callable(array($doc, "addCustomTag")) && !plgJEventsjevfacebook::tagsDone() )
				{
					$doc->addCustomTag($facebooktags);
					plgJEventsjevfacebook::tagsDone(true);
				}

				return $row->_fbcomments;
			}
		}

	}

}
