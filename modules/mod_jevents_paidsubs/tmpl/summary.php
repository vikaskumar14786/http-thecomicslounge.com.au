<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

$orderinfo = $plugin->getOrderSummary();

$purchased = intval($orderinfo['eventsAllowed']);
$used = intval($orderinfo['eventsUsed']);
$remaining =  $purchased - $used;
$remain_class = ($remaining == 0 ? "jev_ps_red" : "");
 ?>

    <div id="jev_ps_container">
        <div class="jev_ps_row">
            <div class="jev_ps_label"><strong><?php echo JText::_("JEV_EVENTS_ALLOWED") ?></strong></div>
            <div class="jev_ps_val "><?php echo $purchased > 0 ? $purchased : 0; ?></div>
        </div>
        <div class="jev_ps_row">
            <div class="jev_ps_label"><strong><?php echo JText::_("JEV_REMAINING_TOKENS") ?></strong></div>
            <div class="jev_ps_val <?php echo $remain_class; ?>"><?php echo $remaining > 0 ? $remaining : 0; ?></div>
        </div>
        <div class="jev_ps_row">
            <div class="jev_ps_label"><strong><?php echo JText::_("JEV_EVENTS_USED") ?></strong></div>
            <div
                class="jev_ps_val"><?php echo  $used > 0 ? $used : 0; ?></div>
        </div>
    </div>
    <div class="jev_ps_links">
<?php
if ($shoplink) {
    ?>
    <a class="btn" href="<?php echo $shoplink; ?>">
        <strong><?php echo JText::_("JEV_PURCHASE_SUBMISSION_CREDITS"); ?></strong>
    </a>
    <br/>
<?php
}
// Offer creation of events if there are credits available
if ($orderinfo['eventsAllowed'] > $orderinfo['eventsUsed']) {
    list($y, $m, $d) = JEVHelper::getYMD();
    global $Itemid;
    $editLink = JRoute::_('index.php?option=' . JEV_COM_COMPONENT
        . '&task=icalevent.edit' . '&year=' . $y . '&month=' . $m . '&day=' . $d
        . '&Itemid=' . $Itemid, true);
    $popup = false;
    $params = JComponentHelper::getParams(JEV_COM_COMPONENT);
    if ($params->get("editpopup", 0)) {
        JHTML::_('behavior.modal');
        JHTML::script('editpopup.js', 'components/' . JEV_COM_COMPONENT . '/assets/js/');
        $popup = true;
        $popupw = $params->get("popupw", 800);
        $popuph = $params->get("popuph", 600);
    }
    $eventlinkadd = $popup ? "javascript:jevEditPopup('" . $editLink . "',$popupw, $popuph);" : $editLink;
    ?>
    <a class="btn" href="<?php echo $eventlinkadd; ?>" title="<?php echo JText::_('JEV_ADDEVENT'); ?>">
        <strong><?php echo JText::_('JEV_ADDEVENT'); ?></strong>
    </a>
<?php
}
 echo "</div>";