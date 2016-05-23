<?php
defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );
if (trim($this->rsvpdata->attendintro)!==""){
?>
<fieldset>
	<legend><?php echo $this->repeat->title();?></legend>
	<?php
echo $this->rsvpdata->attendintro;
?>
</fieldset>
<?php
}