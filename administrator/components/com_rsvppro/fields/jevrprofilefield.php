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

include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/fields/JevrField.php");

class JFormFieldJevrprofilefield extends JevrField
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevrprofilefield';
	const name = 'jevrprofilefield';

	static function isEnabled()
	{
		return true;
		$plugin = JPluginHelper::getPlugin('user', 'profile');
		return $plugin;
	}

	public static function loadScript($field=false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrprofilefield.js');

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
	<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_JEVRPROFILEFIELD"); ?><?php RsvpHelper::fieldId($id);?></div>
	<input type="hidden" name="dv[<?php echo $id; ?>]"  value="" />
	<select name="params[<?php echo $id; ?>][fieldname]" id="fieldname<?php echo $id; ?>"  onchange="jevrprofilefield.setvalue('<?php echo $id; ?>');">
		<?php
		// get the community builder language file - in the vain hope they have moved to Joomla 1.5 system
		$lang = JFactory::getLanguage();
		$lang->load("plg_user_profile", JPATH_ADMINISTRATOR);

		$plugin = JPluginHelper::getPlugin('user', 'profile');
		if (isset($plugin->params) && $plugin->params) {
			$params = new JRegistry($plugin->params);
			$hasPlugin = true;
		}
		else {
			$params = new JRegistry(null);
			$hasPlugin = false;
		}

		$fieldname = "";
		$activeField = "";
		if ($field)
		{
			try {
				$params = json_decode($field->params);
				$fieldname = isset($params->fieldname) ? $params->fieldname : "";
			}
			catch (Exception $e) {

			}
		}

		// Add the registration fields to the form.
		if ($hasPlugin) {
			JForm::addFormPath(JPATH_SITE. '/plugins/user/profile/profiles');

			$form = JForm::getInstance("profileform", "profile");
			$fieldsets = $form->getFieldsets();

			foreach ($fieldsets as $key=>$fieldset) {
				$fieldsetFields = $form->getFieldset($key);
				foreach ($fieldsetFields as $fsfield){
					$label = $fsfield->getLabel();
					$name = $fsfield->fieldname;
					$selected = "";
					if ($field && $fieldname == $name)
					{
						$selected = "selected='selected'";
						$activeField = $label;
					}
			?>
						<option value="<?php echo $fsfield->fieldname; ?>" <?php echo $selected; ?> ><?php echo $label ?></option>
			<?php
				}
			}
		}

		// Get the core JEvents user fields form.
		JForm::addFormPath(JPATH_ADMINISTRATOR. '/components/com_users/models/forms');
		JForm::addFieldPath(JPATH_ADMINISTRATOR. '/components/com_users/models/fields');

		$lang = JFactory::getLanguage();
		$lang->load("com_users", JPATH_ADMINISTRATOR);

		$form = JForm::getInstance('com_users.user', 'user');
		$fieldsets = $form->getFieldsets();

		foreach ($fieldsets as $key=>$fieldset) {
			$fieldsetFields = $form->getFieldset($key);
			foreach ($fieldsetFields as $fsfield){
				if ($fsfield->element->attributes()->readonly){
					continue;
				}
				$label = $fsfield->getLabel();
				$name = $fsfield->fieldname;
				$selected = "";
				if ($field && $fieldname == $name)
				{
					$selected = "selected='selected'";
					$activeField = $label;
				}
		?>
					<option value="<?php echo $fsfield->fieldname; ?>" <?php echo $selected; ?> ><?php echo $label ?></option>
		<?php
			}
		}

	?>
		</select>

		<div class="rsvpclear"></div>

<?php
		RsvpHelper::hidden($id, $field, self::name);
		RsvpHelper::label($id,  $field, self::name);
		JFormFieldJevrprofilefield::namefield($id,  $field);
		$canmodify = isset($params->canmodify) ? intval($params->canmodify) : 0;
?>
			<div class="rsvplabel"><?php echo JText::_("RSVP_CAN_MODIFY"); ?></div>
			<div class="rsvpinputs radio btn-group">
				<label for="canmodify1<?php echo $id; ?>"><?php echo JText::_("JYES"); ?>
				<input type="radio" name="params[<?php echo $id; ?>][canmodify]"  id="canmodify1<?php echo $id; ?>" value="1" <?php
			if ($canmodify == 1)
			{
				echo 'checked="checked"';
			}
					?> />
				</label>
				<label for="canmodify0<?php echo $id; ?>"><?php echo JText::_("JNO"); ?>
				<input type="radio" name="params[<?php echo $id; ?>][canmodify]" id="canmodify0<?php echo $id; ?>" value="0" <?php
			   if ($canmodify == 0)
			   {
				   echo 'checked="checked"';
			   }
					?> />
				</label>
			</div>
			<div class="rsvpclear"></div>

<?php
		RsvpHelper::required($id, $field);
		RsvpHelper::requiredMessage($id, $field);
		RsvpHelper::conditional($id,  $field);
		RsvpHelper::peruser($id, $field);
		RsvpHelper::formonly($id, $field);
		RsvpHelper::showinform($id, $field);
		RsvpHelper::showindetail($id, $field);
		RsvpHelper::showinlist($id, $field);
		RsvpHelper::allowoverride($id, $field);
		RsvpHelper::accessOptions($id, $field);
		RsvpHelper::applicableCategories("facc[$id]", "facs[$id]", $id, $field ? $field->applicablecategories : "all");
?>
	<div class="rsvpclear"></div>
</div>
<div class='rsvpfieldpreview'  id='<?php echo $id; ?>preview'>
			<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW"); ?></div>
			<div class="rsvplabel rsvppl" id='pl<?php echo $id; ?>'><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
			<div id="pdv<?php echo $id; ?>">
<?php
		echo $activeField;
?>
			</div>
		</div>
		<div class="rsvpclear"></div>
<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id, $field, $html, self::name);

	}

	function getInput()
	{
		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		if (empty($value)) $value ="";
		$inputfieldname = $this->fieldname;
		$class = ( $this->attribute('class') ? 'class="'.$this->attribute('class').' xxx"' : 'class="text_area xxx"' );
		
		$user =  JFactory::getUser();

		if (JRequest::getCmd("task")=="attendees.edit" && isset($this->attendee) && $this->attendee->user_id>0 && $this->attendee->user_id != $user->get("id")) {
			$userid = $this->attendee->user_id;
		}
		else if (JRequest::getCmd("task")=="attendees.edit" && isset($this->attendee) && $this->attendee->id>0 && $this->attendee->user_id==0) {
			$userid = 0;
		}
		else {
			$userid = $user->id;
		}
		
		$showinform = $this->attribute("showinform");

		$fieldname = $this->attribute("fieldname");
		$html = "";

		$canmodify = $this->attribute("canmodify") ? intval($this->attribute("canmodify")) : 0 ;

		if ($showinform)
		{
			if (is_null($value) || $value == "" || $value == "0" || $value == 0)
			{
				if (isset($this->attendee) && isset($this->attendee->user_id) && $this->attendee->user_id > 0)
				{
					$userid = $this->attendee->user_id;
				}
				else if ($user->id == 0)
					$userid = $user->id;//return $value;
				else {
					if (JRequest::getCmd("task")=="attendees.edit" && isset($this->attendee) && $this->attendee->user_id == 0) {
						$userid = 	$this->attendee->user_id; //return $value;
					}
				}
			}

			$user = JFactory::getUser($userid);
			
			if ($userid> 0 && (!isset($user->profile) || !$user->profile))
			{
				// Load the profile data from the database.
				$db = JFactory::getDbo();
				$db->setQuery(
					'SELECT profile_key, profile_value FROM #__user_profiles' .
					' WHERE user_id = '.(int) $userid." AND profile_key LIKE 'profile.%'" .
					' ORDER BY ordering'
				);
				$results = $db->loadRowList();

				// Check for a database error.
				if ($db->getErrorNum())
				{
					$this->_subject->setError($db->getErrorMsg());
					return false;
				}

				// Merge the profile data.
				$user->profile = array();

				foreach ($results as $v)
				{
					$k = str_replace('profile.', '', $v[0]);
					$user->profile[$k] = json_decode($v[1], true);
					if ($user->profile[$k] === null)
					{
						$user->profile[$k] = $v[1];
					}
				}
			}

			if ($userid> 0 && isset($user->profile[$fieldname]) && !$canmodify) {
				$html .= $user->profile[$fieldname];
			}
			else if ($userid> 0 && isset($user->$fieldname) && !$canmodify) {
				$html .= $user->$fieldname;
			}
		}

		if ($canmodify){

			if ($this->attribute("peruser")==1 || $this->attribute("peruser")==2){
				if (!is_array($value)){
					$value = array($value);
				}
				if (count($value)<$this->currentAttendees){
					// flesh out the value if there are not the right number of items
					for ($i=0;$i<=$this->currentAttendees-count($value);$i++){
						$value[] = "";
					}
				}

				$elementname = $name.'[]';
				$html = "";
				$i = 0;
				foreach ($value as $val){
					if ($i==0){
						if ($this->attribute("peruser")==2){
							$thisclass = str_replace(" xxx"," disabledfirstparam rsvpparam rsvpparam0 rsvp_" . $inputfieldname,$class);
						}
						else {
							// is this the attendee name field?
							if ($val=="" && !JFactory::getApplication()->isAdmin() && $this->attribute("isname")){
								if ($this->attendee && $this->attendee->user_id>0){
									$atdee = JEVHelper::getUser($this->attendee->user_id);
								}
								else {
									$atdee = JFactory::getUser();
								}
								$val  = $atdee->name;
							}
							$thisclass = str_replace(" xxx"," rsvpparam rsvpparam0 rsvp_" . $inputfieldname,$class);
						}
					}
					else {
						$thisclass = str_replace(" xxx"," rsvpparam rsvpparam".$i."  rsvp_" . $inputfieldname,$class);
					}

					if ($user->id > 0)
					{
						if ($val=="" && isset($user->profile[$fieldname]) && $user->profile[$fieldname]){
							$val  = $user->profile[$fieldname] ;
						}
						else if ($val=="" && isset($user->$fieldname) && $user->$fieldname){
							$val  = $user->$fieldname ;
						}
					}
					$html .= "<input type='text' value='$val' name='$elementname' id='".$id."_".$i."' ".$thisclass." size='40' />";

					$i++;
				}

				$thisclass = str_replace(" xxx"," paramtmpl rsvp_" . $inputfieldname,$class);
				$html .= '<input type="text" name="paramtmpl_'.$elementname.'" id="'.$id.'_xxx" value="'.$val.'" '.$thisclass.' size="40" />';

				return $html;
			}

			if ($user->id > 0)
			{
				if ($value=="" && isset($user->profile[$fieldname]) && $user->profile[$fieldname]){
					$value  = $user->profile[$fieldname] ;
				}
				else if ($value=="" && isset($user->$fieldname) && $user->$fieldname){
					$value  = $user->$fieldname ;
				}
			}
			$html .= "<input type='text' value='$value' name='$name' id='".$id ."' size='40' class='profile_$inputfieldname' />";
		}
		else {
			if ($user->id == 0)
			{
				$html .= "<input type='hidden' value='0' name='$name' id='".$id ."' />";
			}
			else
			{
				$val = $user->id ;
				if ($userid> 0 && isset($user->profile[$fieldname]) && !$canmodify) {
					$val = $user->profile[$fieldname];
				}
				else if ($userid> 0 && isset($user->$fieldname) && !$canmodify) {
					$val = $user->$fieldname;
				}

				$html .= '<input type="hidden" value="' . $val . '" name="'.$name.'"  id="'.$id .'"  />';
			}
		}


		return $html;

	}

	function render(&$xmlElement, $value, $control_name = 'params')
	{
		$showinform = $xmlElement->attributes("showinform");

		$name = $xmlElement->attributes()->name;
		$label = $xmlElement->attributes('label');
		$descr = $xmlElement->attributes('description');
		if (!$showinform)
			$label = "";

		$result[0] = $label . " ";
		$result[1] = $this->fetchElement($name, $value, $xmlElement, $control_name);
		$result[2] = $descr;
		$result[3] = $label;
		$result[4] = $value;
		$result[5] = $name;

		return $result;

	}

	function toXML($field)
	{
		$result = array();
		$result[] = "<field ";
		if (is_string($field->params) && strpos($field->params, "{") === 0)
		{
			$field->params = json_decode($field->params);
		}
		foreach (get_object_vars($field) as $k => $v)
		{
			if ($k == "options" || $k == "html" || $k == "defaultvalue" || $k == "name")
				continue;
			if ($k == "field_id")
			{
				$k = "name";
				$v = "field" . $v;
			}
			if ($k == "params")
			{
				if (is_object($field->params))
				{
				   foreach (get_object_vars($field->params) as $label=>$value)
				   {
					   $result[] = $label . '="' . addslashes(htmlspecialchars($value)) . '" ';
				   }
				}
				continue;
			}
			$result[] = $k . '="' . addslashes(htmlspecialchars($v)) . '" ';
		}
		$result[] = " />";
		$xml = implode(" ", $result);
		return $xml;

	}

	public function convertValue($value)
	{
		$fieldname = $this->attribute("fieldname");
		$attendeeUserId = 0;
		if (isset($this->attendee) && isset($this->attendee->user_id) && $this->attendee->user_id > 0)
		{
			$attendeeUserId = $this->attendee->user_id;
		}

		$html = "";
		if (is_null($value) || $value == "" || $value == "0" || (is_numeric($value)  && intval($value) == 0))
		{
			$user = JFactory::getUser();
			if (isset($this->attendee) && isset($this->attendee->user_id) && $this->attendee->user_id > 0)
			{
				$attendeeUserId = $value = $this->attendee->user_id;
			}
			else if ($user->id == 0)
				return "";
			else {
				if (JRequest::getCmd("task")=="attendees.edit" && isset($this->attendee) && $this->attendee->user_id == 0) {
					return "";
				}
				else {
					$value = $user->id;
				}
			}
		}

		if (is_numeric($value) && $value==$attendeeUserId){
			$user = JFactory::getUser($value);
			if (!isset($user->profile) || !$user->profile)
			{
				// Load the profile data from the database.
				$db = JFactory::getDbo();
				$db->setQuery(
					'SELECT profile_key, profile_value FROM #__user_profiles' .
					' WHERE user_id = '.(int) $value." AND profile_key LIKE 'profile.%'" .
					' ORDER BY ordering'
				);
				$results = $db->loadRowList();

				// Check for a database error.
				if ($db->getErrorNum())
				{
					$this->_subject->setError($db->getErrorMsg());
					return false;
				}

				// Merge the profile data.
				$user->profile = array();

				foreach ($results as $v)
				{
					$k = str_replace('profile.', '', $v[0]);
					$user->profile[$k] = json_decode($v[1], true);
					if ($user->profile[$k] === null)
					{
						$user->profile[$k] = $v[1];
					}
				}
			}

			if (isset($user->profile[$fieldname])) {
				$html = $user->profile[$fieldname];
			}
		}
		else {
			$html = $value;
		}
		
		if (JRequest::getCmd("task")=="attendees.export"){
			// there is a HTML error in some CB fields so missmatched tags and cannot use strip_tags
			//$value =  strip_tags($html);
			$search = array('@<*?[^<>]*?>@si');
			$value = preg_replace($search, '', $html); 			
			return $value;
		}
		
		if (!isset($this->attendee->guestcount)){
			$this->attendee->guestcount=1;
		}

		return $html;
		// No longer needed since we support multi-attendee fields
		/*
		 if ($this->attribute("peruser")==1 && $this->attendee->guestcount>0){
			return array_fill(0, $this->attendee->guestcount, $html);
		}
		else if ($this->attribute("peruser")==2 && $this->attendee->guestcount>0){
			$return = array_fill(0, $this->attendee->guestcount, $html);
			//$return[0]="";
			return $return;
		} 
		return $html;
		*/
	}

	static function namefield($id, $field, $default = 0)
	{
		$isname = 0;
		if ($field)
		{
			try {
				$params = json_decode($field->params);
				$isname = isset($params->isname)?$params->isname:0;
			}
			catch (Exception $e) {
				$isname = 0;
			}
		}
		?>
		<div class="rsvplabel"  ><?php echo JText::_("RSVP_IS_ATTENDEE_NAME_FIELD"); ?></div>
		<div class="rsvpinputs  radio btn-group">
			<label for="isname1<?php echo $id; ?>" class="btn radio"><?php echo JText::_("RSVP_YES"); ?>
			<input type="radio" name="params[<?php echo $id; ?>][isname]"  id="isname1<?php echo $id; ?>" value="1" <?php
		if ($isname)
		{
			echo 'checked="checked"';
		}
		?> />
			</label>
			<label for="isname0<?php echo $id; ?>"  class="btn radio"><?php echo JText::_("RSVP_NO"); ?>
			<input type="radio" name="params[<?php echo $id; ?>][isname]" id="isname0<?php echo $id; ?>" value="0" <?php
		   if (!$isname)
		   {
			   echo 'checked="checked"';
		   }
		?> />
			</label>
		</div>
		<div class="rsvpclear"></div>
		<?php

	}

}