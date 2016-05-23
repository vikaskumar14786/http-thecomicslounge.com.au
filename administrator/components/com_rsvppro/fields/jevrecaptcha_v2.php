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

include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/rsvppro.defines.php");
include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/fields/JevrField.php");

class JFormFieldJevrecaptcha_v2 extends JevrField
{

	static function isEnabled()
	{
		return JComponentHelper::getParams('com_rsvppro')->get("recaptchav2", 0);
	}

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'jevreaptcha_v2';

	const name = 'jevrecaptcha_v2';

	public static function loadScript($field = false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrcaptcha.js');

		if ($field)
		{
			$id = 'field' . $field->field_id;
		}
		else
		{
			$id = '###';
		}
		ob_start();
		?>
		<div class='rsvpfieldinput'>

			<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_TYPE"); ?></div>
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRECAPTCHA_V2"); ?><?php RsvpHelper::fieldId($id); ?></div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::hidden($id, $field, self::name);
			RsvpHelper::label($id, $field);
			RsvpHelper::tooltip($id, $field);

			RsvpHelper::required($id, $field, 1);
			RsvpHelper::requiredMessage($id, $field);

			RsvpHelper::formonly($id, $field, 1);
			RsvpHelper::showinform($id, $field, 1);

			if ($field)
			{
				try {
					$params = json_decode($field->params);
				}
				catch (Exception $e) {
					$params = array();
				}
			}
			$deposittype = isset($params->deposittype) ? intval($params->deposittype) : 0;
			?>

			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::accessOptions($id, $field);
			RsvpHelper::applicableCategories("facc[$id]", "facs[$id]", $id, $field ? $field->applicablecategories : "all");
			?>

			<div class="rsvpclear"></div>

		</div>
		<div class='rsvpfieldpreview'  id='<?php echo $id; ?>preview'>
			<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW"); ?></div>
			<div class="rsvplabel rsvppl" id='pl<?php echo $id; ?>' ><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
		</div>
		<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id, $field, $html, self::name);

	}

	public static function paidOption()
	{
		return 0;

	}

	function getInput()
	{
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;

		if (JFactory::getApplication()->isAdmin() || JRequest::getCmd("task")=="attendees.edit"){
			$this->addAttribute("required",0);
			$this->addAttribute("label","");
			return "";
		}
		if (isset($this->attendee) && $this->attendee && $this->attendee->user_id==0 &&  $this->attendee->confirmed){
			return "";
		}
		
		$lang = JFactory::getLanguage();
		list ($tag1, $tag2) = explode("-", $lang->getTag());
		// See http://recaptcha.net/apidocs/captcha/client.html for list of supported languages
		$langs = array("en", "nl", "fr", "de", "pt", "ru", "es", "tr");
		
		$pluginpath = "";
		$pluginpath = "plugins/jevents/jevrsvppro/rsvppro/recaptcha/";

		$root = JURI::root() . $pluginpath;
		$token = JSession::getFormToken();;
		
		$recaptchalang = "en";
		if (in_array($tag1, $langs))
		{
			$recaptchalang = $tag1;
		}
		
		$checkscript = <<<SCRIPT
var recaptchaurlroot = '$root';
var RecaptchaOptions = {
theme : 'clean',
lang : '$recaptchalang'
};
SCRIPT;
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($checkscript);
		
		$params = JComponentHelper::getParams("com_rsvppro");

		// Version 2 API
		JHtml::script("https://www.google.com/recaptcha/api.js");
		$input = '<div class="g-recaptcha" data-sitekey="'.$params->get("recaptchapublic", "").'"></div>';
		
		// to pass secret validation code to captcha check in the backend
		$input .= "<input type='hidden' name='secretcaptcha'  id='secretcaptcha'  value='' />";

		return $input;

	}

	function fetchRequiredScript()
	{
		if (JFactory::getApplication()->isAdmin()){
			return "";
		}

		if (isset($this->attendee) && $this->attendee && $this->attendee->user_id==0 &&  $this->attendee->confirmed){
			return "";
		}

		$elementid = $this->id;
		$elementname = $this->name;
		
		$pluginpath = "";
			
		$pluginpath = "plugins/jevents/jevrsvppro/rsvppro/recaptcha/";
		JevHelper::script("recaptcha16_v2.js", $pluginpath, true);

		$mainframe = JFactory::getApplication();
		$mainframe->setUserState("jevrecaptcha", "error");

		$script = "jevrsvpRequiredFields.fields.push({'requiredCheckScript':'checkRecaptchav2', 'reqmsg':'" . trim(JText::_($this->attribute('requiredmessage'), true)) . "'}); ";
		return $script;
	}

}