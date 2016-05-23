<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: cpanel.php 1432 2009-04-29 15:24:53Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');
$option = JRequest::getCmd("option");

if (!empty($this->sidebar))
{
	?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
<?php
}
$mainspan = 10;
$fullspan = 12;
?>
<div id="jevents" class="span12">
	<form action="index.php" method="post" name="adminForm"  id="adminForm"  >
		<div id="j-main-container" class="span<?php echo (!empty($this->sidebar)) ? $mainspan : $fullspan; ?>  ">
			<div id="cpanel" class="well well-small clearfix ">

				<?php
				$link = "index.php?option=" . RSVP_COM_COMPONENT . "&task=sessions.list";
				$this->_quickiconButtonWHover($link, "cpanel/RSVPCool.png", "cpanel/RSVPHot.png", JText::_('RSVP_SESSIONS'), "/administrator/components/" . JEV_COM_COMPONENT . "/assets/images/");

				// new version
				$juser = JFactory::getUser();
				$authorised = false;
				if ($juser->authorise('core.admin', 'com_rsvppro'))
				{
					$authorised = true;
				}
				if ($authorised)
				{
					$link = "index.php?option=" . RSVP_COM_COMPONENT . "&task=pmethods.overview";
					$this->_quickiconButtonWHover($link, "rsvppro_payments_sml.png", "rsvppro_payments_sml.png", JText::_('RSVP_PRO_PAYMENT_METHODS'), "/administrator/components/" . RSVP_COM_COMPONENT . "/assets/images/");
				}

				$link = "index.php?option=$option&task=templates.list";
				$this->_quickiconButtonWHover($link, "jevents_layouts_sml.png", "jevents_layouts_sml.png", JText::_('RSVP_TEMPLATES'), "/administrator/components/" . RSVP_COM_COMPONENT . "/assets/images/");
				if ($authorised)
				{
					$link = "index.php?option=" . RSVP_COM_COMPONENT . "&task=params.edit";
					$this->_quickiconButtonWHover($link, "cpanel/ConfigCool.png", "cpanel/ConfigHot.png", JText::_('RSVP_CONFIGURATION'), "/administrator/components/" . JEV_COM_COMPONENT . "/assets/images/");
				}

				$link = "index.php?option=" . JEV_COM_COMPONENT . "&task=cpanel.cpanel";
				$this->_quickiconButtonWHover($link, "cpanel/EventsCool.png", "cpanel/EventsHot.png", JText::_('COM_RSVPPRO_RETURN_TO_JEVENTS'), "/administrator/components/" . JEV_COM_COMPONENT . "/assets/images/");
				?>
                <div class="clear"></div>
            </div>
        </div>
		<input type="hidden" name="task" value="cpanel" />
		<input type="hidden" name="act" value="" />
		<input type="hidden" name="option" value="<?php echo RSVP_COM_COMPONENT; ?>" />
	</form>
</div>
