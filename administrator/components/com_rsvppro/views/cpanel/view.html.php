<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: view.html.php 1399 2009-03-30 08:31:52Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * HTML View class for the component
 *
 * @static
 */
class AdminCpanelViewCpanel extends RSVPAbstractView
{

	/**
	 * Control Panel display function
	 *
	 * @param template $tpl
	 */
	function cpanel($tpl = null)
	{
		jimport('joomla.html.pane');

		JHtml::stylesheet('components/' . RSVP_COM_COMPONENT . '/assets/css/rsvpadmin.css');
		JEVHelper::stylesheet('jev_cp.css', 'administrator/components/' . JEV_COM_COMPONENT . '/assets/css/');

		$document = JFactory::getDocument();
		$document->setTitle(JText::_('RSVP_RSVP') . ' :: ' . JText::_('Control_Panel'));

		// Set toolbar items for the page
		JToolBarHelper::title(JText::_('RSVP_RSVP') . ' :: ' . JText::_('Control_Panel'), 'jevents');

		RsvpproHelper::addSubmenu();

		if (JevJoomlaVersion::isCompatible("3.0"))
		{
			$this->sidebar = JHtmlSidebar::render();
		}
		else
		{
			//$this->setLayout("cpanel25");
		}

		//JToolBarHelper::help( 'screen.cpanel', true);

		$params = JComponentHelper::getParams(RSVP_COM_COMPONENT);

		JHtml::_('behavior.tooltip');

	}

	function _quickiconButtonWHover($link, $image, $image_hover, $text, $path = '/administrator/images/', $target = '', $onclick = '')
	{
		if ($target != '')
		{
			$target = 'target="' . $target . '"';
		}
		if ($onclick != '')
		{
			$onclick = 'onclick="' . $onclick . '"';
		}
		if ($path === null || $path === '')
		{
			$path = '/administrator/images/';
		}
		$alttext = str_replace("<br/>", " ", $text);
		?>
		<div id="cp_icon_container">
			<div class="cp_icon">
				<a href="<?php echo $link; ?>" <?php echo $target; ?>  <?php echo $onclick; ?> title="<?php echo $alttext; ?>">
					<?php
					//echo JHTML::_('image.administrator', $image, $path, NULL, NULL, $text );
					if (strpos($path, '/') === 0)
					{
						$path = substr($path, 1);
					}
					$atributes = array('title' => $alttext, 'onmouseover' => 'this.src=\'../' . $path . $image_hover . '\'', 'onmouseout' => 'this.src=\'../' . $path . $image . '\'');

					echo JHTML::_('image', $path . $image, $alttext, $atributes, false);
					//JHtml::_('image', 'mod_languages/'.$menuType->image.'.gif', $alt, array('title'=>$menuType->title_native), true)
					?>
					<span><?php echo $text; ?></span>
				</a>
			</div>
		</div>
		<?php

	}

}
