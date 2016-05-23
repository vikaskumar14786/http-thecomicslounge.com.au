<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

include_once(JPATH_ADMINISTRATOR.'/'."components/com_rsvppro/fields/JevrFieldText.php");

class JFormFieldJevremail extends JevrFieldText
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevremail';
	const name = 'jevremail';

	static function isEnabled(){
		return 1;
	}

	public static function loadScript($field=false){
		JHtml::script( 'administrator/components/'.RSVP_COM_COMPONENT.'/fields/js/jevremail.js' );

		if ($field){
			$id = 'field'.$field->field_id;
		}
		else {
			$id = '###';
		}
		ob_start();
?>
<div class='rsvpfieldinput'>

	<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_TYPE");?></div>
	<div class="rsvpinputs" style="font-weight:bold;">Email Address<?php RsvpHelper::fieldId($id);?></div>
	<div class="rsvpclear"></div>

	<?php
	RsvpHelper::hidden($id,  $field, self::name);
	RsvpHelper::label($id,  $field, self::name);
	RsvpHelper::tooltip($id,  $field);
	?>
	
	<div class="rsvplabel"><?php echo JText::_("RSVP_DEFAULT_VALUE");?></div>
	<div class="rsvpinputs">
		<input type="text" name="dv[<?php echo $id;?>]" id="dv<?php echo $id;?>" size="<?php echo $field?$field->size:5;?>"  value="<?php echo $field?$field->defaultvalue:"";?>"  onchange="jevremail.setvalue('<?php echo $id;?>');"  onkeyup="jevremail.setvalue('<?php echo $id;?>');"/>
	</div>
	<div class="rsvpclear"></div>

	<?php
	RsvpHelper::size($id,  $field, self::name);
	RsvpHelper::maxlength($id,  $field, self::name);
	RsvpHelper::required($id,  $field);
	RsvpHelper::requiredMessage($id,  $field);
	RsvpHelper::conditional($id,  $field);
	RsvpHelper::peruser($id,  $field);
	RsvpHelper::formonly($id,  $field);
	RsvpHelper::showinform($id,  $field);
	RsvpHelper::showindetail($id,  $field);
	RsvpHelper::showinlist($id,  $field);
	RsvpHelper::allowoverride($id,  $field);
	RsvpHelper::accessOptions($id,  $field);
	RsvpHelper::applicableCategories("facc[$id]","facs[$id]", $id,  $field?$field->applicablecategories:"all");
	?>

	<div class="rsvpclear"></div>
	
</div>
<div class='rsvpfieldpreview'  id='<?php echo $id;?>preview'>
	<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW");?></div>
	<div class="rsvplabel rsvppl" id='pl<?php echo $id;?>' ><?php echo $field?$field->label:JText::_("RSVP_FIELD_LABEL");?></div>
	<input type="text"  id="pdv<?php echo $id;?>" value="<?php echo $field?$field->defaultvalue:"";?>" size="<?php echo $field?$field->size:5;?>"  />
</div>
<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id,  $field, $html, self::name	);

	}

	function getInput()
	{
		//$name, $value, &$node
		$node =  $this->element;
		$name = $this->name;
		$value = $this->value;
		$id = $this->id;
		$fieldname = $this->fieldname;

		$size = ( $this->attribute('size') ? 'size="'.$this->attribute('size').'"' : '' );
		$class = ( $this->attribute('class') ? 'class="'.$this->attribute('class').' xxx"' : 'class="text_area xxx"' );

		/*
		 * Required to avoid a cycle of encoding &
		 * html_entity_decode was used in place of htmlspecialchars_decode because
		 * htmlspecialchars_decode is not compatible with PHP 4
		 */
		if (is_array($value)){
			foreach ($value as &$val){
				$val = htmlspecialchars(html_entity_decode($val, ENT_QUOTES), ENT_QUOTES);
			 }
			 unset($val);
		}
		else {
			$value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES), ENT_QUOTES);
		}
		
		$change = "";
		if ($this->attribute("peruser")==1 || $this->attribute("peruser")==2){
			if (!is_array($value)){
				$value = array($value);
			}
			if (count($value)<$this->currentAttendees){
				// flesh out the value if there are not the right number of items
				for ($i=0;$i<=$this->currentAttendees-count($value);$i++){
					$value[] = $this->attribute("default");
				}
			}
			$elementname = $name.'[]';
			$html = "";
			$i = 0;
			foreach ($value as $val){
				if ($i==0){
					if ($this->attribute("peruser")==2){
						$thisclass = str_replace(" xxx"," disabledfirstparam rsvpparam rsvpparam0 rsvp_" . $fieldname,$class);
					}
					else {
						// This is the attendee email address
						if (($val=="" || $val == $this->attribute("default")) && !JFactory::getApplication()->isAdmin() ){
							if ($this->attendee && $this->attendee->user_id>0){
								$atdee = JEVHelper::getUser($this->attendee->user_id);
								$val  = $atdee->email;
							}
							else {
								$atdee = JFactory::getUser();
								if ($atdee->id > 0){
									$val  = $atdee->email;
								}
								else {
									$val = $this->attribute("default");
								}
							}
						}
						$thisclass = str_replace(" xxx"," rsvpparam rsvpparam0 rsvp_" . $fieldname,$class);
					}
				}
				else {
					$thisclass = str_replace(" xxx"," rsvpparam rsvpparam".$i."  rsvp_" . $fieldname,$class);
				}
				$html .= '<input type="text" name="'.$elementname.'" id="'.$id."_".$i.'" value="'.$val.'" '.$thisclass.' '.$size.'  '.$change.' />';
				$i++;
			}
			$val = $this->attribute("default");
			$thisclass = str_replace(" xxx"," paramtmpl rsvp_" . $fieldname,$class);
			$html .= '<input type="text" name="paramtmpl_'.$elementname.'" id="'.$id.'_xxx" value="'.$val.'" '.$thisclass.' '.$size.'  '.$change.' />';
		}
		else {
			// this is the attendee email address
			if (($value==""  || $value == $this->attribute("default")) && !JFactory::getApplication()->isAdmin() ) {
				if ($this->attendee && $this->attendee->user_id>0){
					$atdee = JEVHelper::getUser($this->attendee->user_id);
					$value  = $atdee->email;
				}
				else {
					$atdee = JFactory::getUser();
					if ($atdee->id > 0){
						$value  = $atdee->email;
					}
					else {
						$value = $this->attribute("default");
					}
				}
			}
						
			$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_" . $fieldname, $class);
			$html = '<input type="text" name="'.$name.'" id="'.$id.'" value="'.$value.'" '.$thisclass.' '.$size.'  '.$change.' />';
		}
		return $html;
	}

	function currentAttendeeCount($node, $value){
		if (is_array($value) && count($value)>1) {
			return count($value)-1;
		}
		return 1;
	}


	function fetchRequiredScript()
	{

		$elementid = $this->id;
		$elementname = $this->name;

		static $js = false;
		if (!$js) {
		$js = <<<SCRIPT
function emailFieldCheck(form, field)
{
	//alert(JSON.stringify(field));
	var defaultvalue = field.default;

	var allvalid = true;
	for(f=0;f<parseInt(jQuery('#lastguest').val());f++) {
		var fieldid = '#'+field.id+"_"+f;
		if (!jQuery(fieldid).length){
			continue;
		}
		if (jQuery(fieldid).hasClass('disabledfirstparam')){
			continue;
		}
		var str = jQuery(fieldid).val();

		if (str == defaultvalue || str == ""){
			valid = false;
		}
		var at="@";
		var dot=".";
		var lat=str.indexOf(at);
		var lstr=str.length;
		var ldot=str.indexOf(dot);
		var valid = true;

		// must have an @ and must not start or end with @
		if (str.indexOf(at)==-1 || str.indexOf(at)==0 || str.indexOf(at)==lstr-1){
			valid=false;
		}
		// Must have a . and must not start or end with a .
		if (str.indexOf(dot)==-1 || str.indexOf(dot)==0 || str.indexOf(dot)==lstr-1){
			valid=false;
		}
		// must not have more than one @
		if (str.indexOf(at,(lat+1))!=-1){
			valid=false;
		}
		// must not have a . straight before or after a ?
		if (str.substring(lat-1,lat)==dot || str.substring(lat+1,lat+2)==dot){
			valid=false;
		}
		// there must be a . after the @
		if (str.indexOf(dot,(lat+2))==-1){
			valid=false;
		}
		// no spaces
		if (str.indexOf(" ")!=-1){
			valid=false;
		}

		if (!valid){
			jQuery(fieldid).css('background-color',"red");
			allvalid = false;
		}
		else {
			try {
				jQuery(fieldid).css('background-color',"inherit");
			}
			catch (e){
				jQuery(fieldid).css('background-color',"transparent");
			}
		}
	}
	return allvalid;
}
SCRIPT;
		JFactory::getDocument()->addScriptDeclaration($js);
		}

		$script = "jevrsvpRequiredFields.fields.push({'requiredCheckScript':'emailFieldCheck', 'id':'" . $elementid . "', 'default' :'" . $this->attribute("default") . "' , 'reqmsg':'" . trim(JText::_($this->attribute('requiredmessage'), true)) . "'}); ";
		return $script;
	}

}