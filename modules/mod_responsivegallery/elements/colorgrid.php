<?php
defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

class JFormFieldColorgrid extends JFormField
{
	protected $type = 'colorgrid';

	protected function getInput()
	{
		// Initialize some field attributes.		
		$LiveSite = JURI::base();
		$document = JFactory::getDocument();
		$dirname = basename(dirname(dirname(__FILE__)));
		$document->addScript($LiveSite."../modules/$dirname/assets/colorgrid/jscolor.js");		
		$size		= $this->element['size'] ? ' size="'.(int) $this->element['size'].'"' : '';
		$maxLength	= $this->element['maxlength'] ? ' maxlength="'.(int) $this->element['maxlength'].'"' : '';
		$id			= $this->element['id'] ? ' id="'.(string) $this->element['id'].'"' : '';
		$previewid	= $this->element['previewid'] ? ' id="'.(string) $this->element['previewid'].'"' : '';
		$readonly	= ((string) $this->element['readonly'] == 'true') ? ' readonly="readonly"' : '';
		$disabled	= ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';

		// Initialize JavaScript field attributes.
		$onchange	= $this->element['onchange'] ? ' onchange="'.(string) $this->element['onchange'].'"' : '';

		$html = array();
		$class = $this->element['class'] ? (string) $this->element['class'] : 'color {hash:true,pickerClosable:true,adjust:false}';

		$value = htmlspecialchars(html_entity_decode($this->value, ENT_QUOTES), ENT_QUOTES);
        	$background = ' style="background-color: '.$value.'"';

		return '<input type="text" name="'.$this->name.'" id="'.$this->id.'" '.$background.' class="'.$class.'" value="'.htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8').'">';
	}
}
