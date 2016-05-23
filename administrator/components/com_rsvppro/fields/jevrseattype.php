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

include_once(JPATH_ADMINISTRATOR ."/components/com_rsvppro/fields/jevrlist.php");

class JFormFieldJevrseattype extends JFormFieldJevrlist
{

	/**
	 * Element name
	 *
	 * @access	protectedf
	 * @var		string
	 */
	var $_name = 'jevrseattype';
	const name = 'jevrseattype';
	const advancedoptions = false;
	const advancedoptions2 = false;

	static function isEnabled(){
		return 0;
	}

	public static function loadScript($field=false)
	{
		$comparams = JComponentHelper::getParams("com_rsvppro");		
				
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrlist.js');

		if ($field)
		{
			$id = 'field' . $field->field_id;
		}
		else
		{
			$id = '###';
		}
		$hasfeeClass = "rsvp_nofees";
		ob_start();
		?>
		<div class='rsvpfieldinput'>

			<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_TYPE"); ?></div>
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_jevrseattype"); ?><?php RsvpHelper::fieldId($id); ?></div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::hidden($id, $field, self::name);
			RsvpHelper::label($id, $field, self::name);
			RsvpHelper::tooltip($id, $field);

			if ($field)
			{
				try {
					$params = json_decode($field->params);
				}
				catch (Exception $e) {
					$params = array();
				}
			}
			$includeintotalcapacity = isset($params->includeintotalcapacity) ? intval($params->includeintotalcapacity) : 0;
			$capacity = isset($params->capacity) ? intval($params->capacity) : 0;
			$nocapacitymessage = isset($params->nocapacitymessage) ? $params->nocapacitymessage : "";
			$reducevaluefortotalcapacity = isset($params->reducevaluefortotalcapacity) ? intval($params->reducevaluefortotalcapacity) : 0;
			?>
			<div class="rsvplabel"><?php echo JText::_("RSVP_OPTIONS"); ?></div>
			<div class="rsvpinputs" style="height:200px;overflow-y:auto ">
				<?php echo JText::_("RSVP_NUMERIC_OPTION_NOTES"); ?><br/>
				<!-- Put the selected option here //-->
				<input type="hidden" name="dv[<?php echo $id; ?>]" id="dv<?php echo $id; ?>" value="<?php echo $field ? $field->defaultvalue : ""; ?>" />
				<?php
				$options = array();
				if ($field && $field->options != "")
				{
					$optionvalues = json_decode($field->options);
				}
				else {
					// HARD CODED defaults for convenience
					$optionvalues = new stdClass();
					$optionvalues->label = array();
					$optionvalues->capacity = array();
					$optionvalues->price = array();
					$optionvalues->value = array();

					$optionvalues->label[] = "Please Select Seat Type";
					$optionvalues->capacity[] = 0;
					$optionvalues->price[] = 0;
					$optionvalues->value[] = 0;
					
					$optionvalues->label[] = "Adult";
					$optionvalues->capacity[] = 0;
					$optionvalues->price[] = 20;
					$optionvalues->value[] = 1;

					$optionvalues->label[] = "Concession";
					$optionvalues->capacity[] = 0;
					$optionvalues->price[] = 12;
					$optionvalues->value[] = 2;

				}

				$maxvalue = -1;

				$countoptions = 0;
				if (isset($optionvalues))
				{
					foreach ($optionvalues->value as $val)
					{
						$maxvalue = $maxvalue > $val ? $maxvalue : $val;
					}

					foreach ($optionvalues->label as $lab)
					{
						if ($lab == "")
						{
							break;
						}
						$option = new stdClass();
						$option->value = $optionvalues->value[$countoptions];
						$option->price = isset($optionvalues->price) ? $optionvalues->price[$countoptions] : "0.0";
						$option->label = $lab;
						$option->capacity = (isset($optionvalues->capacity) && $optionvalues->capacity[$countoptions] != "") ? $optionvalues->capacity[$countoptions] : "";
						$option->waiting = (isset($optionvalues->waiting) && $optionvalues->waiting[$countoptions] != "") ? $optionvalues->waiting[$countoptions] : "";
						$options[] = $option;
						$countoptions++;
					}
				}

				// add 20 blank options at the end
				for ($op = 0; $op < 20; $op++)
				{
					$option = new stdClass();
					$option->value = $maxvalue + 1;
					$option->price = "0.0";
					$maxvalue++;
					$option->label = "";
					$option->capacity = "";
					$option->waiting = "";
					$options[] = $option;
				}
				?>
				<input type="button" value="<?php echo JText::_("RSVP_NEW_OPTION") ?>" onclick="jevrlist.newOption('<?php echo $id; ?>');"/>
				<table id="options<?php echo $id; ?>">
					<tr >
						<th><?php echo JText::_("RSVP_OPTION_TEXT") ?></th>
						<th><?php echo JText::_("RSVP_OPTION_VALUE") ?></th>
						<th class="<?php echo $hasfeeClass; ?>"><?php echo JText::_("RSVP_FEE_VALUE") ?></th>
						<th><?php echo JText::_("RSVP_DEFAULT_VALUE") ?></th>
						<?php if ($comparams->get("allowfieldoptioncapacities", 0))
						{ ?>
							<th><?php echo JText::_("RSVP_OPTION_CAPACITY") ?></th>
						<?php } ?>				
						<?php if (self::advancedoptions2)
						{ ?>
							<th><?php echo JText::_("RSVP_OPTION_WAITING") ?></th>
						<?php } ?>				
						<th/>
					</tr>
					<?php
					for ($op = 0; $op < count($options); $op++)
					{
						$option = $options[$op];
						$style = "";
						if ($op > 0 && $op >= $countoptions)
						{
							$style = "style='display:none;'";
						}

						$checked = "";
						if (($field && $option->value == $field->defaultvalue) || (!$field && $option->value == ""))
						{
							$checked = "checked='checked'";
						}
						?>
						<tr <?php echo $style; ?> >
							<td>
								<input type="text" class="inputlabel" name="options[<?php echo $id; ?>][label][]" id="options<?php echo $id; ?>_t_<?php echo $op; ?>" value="<?php echo $option->label; ?>" <?php JFormFieldJevrlist::buttonAction($id, $op); ?>/>
							</td>
							<td>
								<input type="text" name="options[<?php echo $id; ?>][value][]" id="options<?php echo $id; ?>_v_<?php echo $op; ?>" value="<?php echo $option->value; ?>" <?php JFormFieldJevrlist::buttonAction($id, $op); ?> class="jevoption_value"/>
							</td>
							<td class="<?php echo $hasfeeClass; ?>">
								<input type="text" name="options[<?php echo $id; ?>][price][]" id="options<?php echo $id; ?>_p_<?php echo $op; ?>" value="<?php echo $option->price; ?>" class="jevfee_value"/>
							</td>
							<td>
								<input type="radio" value="1" onclick="jevrlist.defaultOption(this, '<?php echo $id; ?>', '<?php echo $op; ?>');"  name="default<?php echo $id; ?>" <?php echo $checked; ?> />
							</td>
							<?php if ($comparams->get("allowfieldoptioncapacities", 0))
							{ ?>
								<td>
									<input type="text" name="options[<?php echo $id; ?>][capacity][]" id="options<?php echo $id; ?>_capacity_<?php echo $op; ?>" value="<?php echo $option->capacity; ?>"  class="jevoption_capacity" size ="4"/>
								</td>
							<?php } ?>
							<?php if (self::advancedoptions2)
							{ ?>
								<td>
									<input type="text" name="options[<?php echo $id; ?>][waiting][]" id="options<?php echo $id; ?>_waiting_<?php echo $op; ?>" value="<?php echo $option->waiting; ?>"  class="jevoption_waiting" size ="4"/>
								</td>
							<?php } ?>
							<td>
								<input type="button" value="<?php echo JText::_("RSVP_DELETE_OPTION") ?>" onclick="jevrlist.deleteOption(this);"/>
							</td>
						</tr>
						<?php
					}
					?>
				</table>

			</div>
			<div class="rsvpclear"></div>
			<!--
			<div class="rsvplabel"><?php echo JText::_("RSVP_INCLUDE_IN_CAPACITY"); ?></div>
			<div class="rsvpinputs">
				<label for="includeintotalcapacity1<?php echo $id; ?>"><?php echo JText::_("RSVP_YES"); ?>
				<input type="radio" name="params[<?php echo $id; ?>][includeintotalcapacity]"  id="includeintotalcapacity1<?php echo $id; ?>" value="1" <?php
			if ($includeintotalcapacity == 1)
			{
				echo 'checked="checked"';
			}
					?> />
				</label>
				<label for="includeintotalcapacity0<?php echo $id; ?>"><?php echo JText::_("RSVP_NO"); ?>
				<input type="radio" name="params[<?php echo $id; ?>][includeintotalcapacity]" id="includeintotalcapacity0<?php echo $id; ?>" value="0" <?php
			   if ($includeintotalcapacity == 0)
			   {
				   echo 'checked="checked"';
			   }
					?> />
				</label>
			</div>
			<div class="rsvpclear"></div>

			<div class="rsvplabel"><?php echo JText::_("RSVP_REDUCE_TOTAL_CAPACITY"); ?></div>
			<div class="rsvpinputs">
				<input type="text" name="params[<?php echo $id; ?>][reducevaluefortotalcapacity]" id="params<?php echo $id; ?>reducevaluefortotalcapacity" size="15"   value="<?php echo $reducevaluefortotalcapacity; ?>" />
			</div>
			<div class="rsvpclear"></div>

			<div class="rsvplabel"><?php echo JText::_("RSVP_FIELD_CAPACITY"); ?></div>
			<div class="rsvpinputs">
				<input type="text" name="params[<?php echo $id; ?>][capacity]" id="params<?php echo $id; ?>capacity" size="15"   value="<?php echo $capacity; ?>" />
			</div>
			<div class="rsvpclear"></div>
			//-->
			<?php if ($comparams->get("allowfieldoptioncapacities", 0)) {?>
			<div class="rsvplabel" ><?php echo JText::_("RSVP_NO_CAPACITY_MESSAGE"); ?></div>
			<div class="rsvpinputs">
				<input type="text" name="params[<?php echo $id; ?>][nocapacitymessage]" id="params<?php echo $id; ?>nocapacitymessage" size="50"   value="<?php echo $nocapacitymessage; ?>" />
			</div>
			<div class="rsvpclear"></div>
			<?php }
			else { ?>			
			<div class="rsvplabel" style="display:none"><?php echo JText::_("RSVP_NO_CAPACITY_MESSAGE"); ?></div>
			<div class="rsvpinputs" style="display:none">
				<input type="text" name="params[<?php echo $id; ?>][nocapacitymessage]" id="params<?php echo $id; ?>nocapacitymessage" size="50"   value="<?php echo $nocapacitymessage; ?>" />
			</div>
			<div class="rsvpclear"></div>
			<?php } ?>

			<?php
			RsvpHelper::required($id, $field);
			RsvpHelper::requiredMessage($id, $field);
			//RsvpHelper::conditional($id, $field);
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
		<div class='rsvpfieldpreview' id='<?php echo $id; ?>preview'>
			<div class="previewlabel"><?php echo JText::_("RSVP_PREVIEW"); ?></div>
			<div class="rsvplabel rsvppl" id='pl<?php echo $id; ?>' ><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
			<select name="pdv[<?php echo $id; ?>]" id="pdv<?php echo $id; ?>" >
				<?php
				foreach ($options as $option)
				{
					if ($option->label == "")
						continue;
					$selected = "";
					if (($field && $option->value == $field->defaultvalue) || (!$field && $option->value == ""))
					{
						$selected = "selected='selected'";
					}
					?>
					<option value="<?php echo $option->value; ?>" <?php echo $selected; ?> ><?php echo $option->label; ?></option>
					<?php
				}
				?>
			</select>

		</div>
		<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id, $field, $html, self::name);

	}

	function getInput()
	{
		$name = $this->name;
		$fieldname = $this->fieldname;
		$id = $this->id;
		$value = $this->value;

		if ((isset($this->attendee) && isset($this->attendee->attendstate) && $this->attendee->attendstate!=1 && $this->attendee->attendstate!=4)
				|| $this->attendee===false){
			$value  = array(0);
			//$this->attendee->guestcount=1;
			//$this->currentAttendees=1;
		}

		$attribs = ( $this->attribute('class') ? 'class="' . $this->attribute('class'). ' xxx"' : 'class="inputbox xxx"' );
		$comparams = JComponentHelper::getParams("com_rsvppro");		
		$includeintotalcapacity = intval($this->attribute("includeintotalcapacity"));

		$html = "";
		$hasprice = false;
		$options = array();
		$newoptions = array();
		$prices = array();
		
		// used for option specific capacities
		$capacities[-1] = 0;
		$waiting[-1] = 0;
		$counts[-1] = 0;
		$allcounts[-1] = 0;
		$optionlabels[-1] = "";
		$storedRows = 0;
		
		// Now find how much of the capacity is used up
		// 
		// capacity count for this specific field
		$fieldcount = 0;
		// for specific options (for future use)
		$allcounts = array();
		
		if (is_array($value)){
			$registrationSpecificCount = array_sum($value);
		}
		else {
			$registrationSpecificCount =intval($value);
		}
		
		// Fetch reference to current row and rsvpdata to the registry so that we have access to these in the fields
		$registry = JRegistry::getInstance("jevents");
		$rsvpdata = $registry->get("rsvpdata");
		$row = $registry->get("event");
		$sql = "SELECT params FROM #__jev_attendees as a WHERE a.at_id=" . $rsvpdata->id . " AND a.attendstate=1";
		if (!$rsvpdata->allrepeats)
		{
			$sql .= " and a.rp_id=" . $row->rp_id();
		}
		$db = JFactory::getDBO();
		$db->setQuery($sql);
		$attendeeData = $db->loadObjectList();
		foreach ($attendeeData as $data)
		{
			$dataparams = new JRegistry($data->params);
			$pvalue = $dataparams->get($fieldname, -1);
			if (!is_array($pvalue))
			{
				$pvalue = array($pvalue);
			}
			JArrayHelper::toInteger($pvalue);

			for ($i = 0; $i < count($pvalue); $i++)
			{
				$pval = $pvalue[$i];
				if (!isset($allcounts[$pval]))
					$allcounts[$pval] = 0;
				$allcounts[$pval] += 1;
				$fieldcount += $pval;
			}
		}
		
		if ($rsvpdata->capacity){
			if (!isset($rsvpdata->attendeeCount)){
				$db= JFactory::getDBO();
				$sql = "SELECT atdcount FROM #__jev_attendeecount as a WHERE a.at_id=".$rsvpdata->id;
				if (!$rsvpdata->allrepeats){
					$sql .= " and a.rp_id=".$row->rp_id();
				}
				$db->setQuery($sql);
				$rsvpdata->attendeeCount = $db->loadResult();
			}
			$attendeeCount = $rsvpdata->attendeeCount;
		}
		foreach ($this->element->children() as $option)
		{
			$val	= (string) $option["value"];
			$text = (string)$option;
			// some short strings like seat number e.g. J1=J20 are translated by Joomla to numbers - we don't want this
			if (!is_numeric($text) && is_numeric( JText::_($text))){
				$text =$text;
			}
			else {
				 $text = JText::_($text);
			}
			$htmloption = JHtml::_('select.option', $val,$text);
			$price =  (string) $option['price'];
			if (!is_null($price))
			{
				$htmloption->price = $price;
				$prices[$val] = $price;
				$hasprice = true;
			}
			else
			{
				$prices[$val] = 0;
			}
			$options[] = $htmloption;
			// field specific capacity too high
			if ($this->attribute("capacity")>0 && $fieldcount+intval($htmloption->value)>$this->attribute("capacity")){
				continue;
			}
			// overall capacity check
			if ($rsvpdata->capacity && $this->attribute("includeintotalcapacity") && $attendeeCount+intval($htmloption->value) -$registrationSpecificCount > $rsvpdata->capacity){
				continue;
			}
			$newoptions[] = $htmloption;
		}
		if ($rsvpdata->capacity && (count($newoptions)==0 || (count($newoptions)==1 && $newoptions[0]->value==0))){
			$htmloption = JHtml::_('select.option', 0, ($this->attribute("nocapacitymessage")!="" ? $this->attribute("nocapacitymessage") : JText::_("JEV_NO_MORE_CAPACITY")));
			$newoptions = array();
			$newoptions[] = $htmloption;
		}
		
		if ($comparams->get("allowfieldoptioncapacities", 0) && $this->attribute("capacity")>0)
		{
			$rsvpdata = $this->rsvpdata;
			$rsvpparams = new JRegistry($this->rsvpdata->params);

			$currentfieldname = $rsvpparams->get("fieldname_" . $fieldname, $this->attribute('label'));
			
			foreach ($this->element->children() as $option)
			{
				$p	= intval((string) $option["value"]);
				if ((string) $option["capacity"]=="" && !is_numeric((string) $option["value"])){
					$p = (string) $option["value"];
				}
				$capacities[$p] = intval( $option['capacity']);
				$waiting[$p] = intval($option['waiting']);
				$hascapacity = true;
				$allcounts[$p] = isset($allcounts[$p])?$allcounts[$p]:0;
				$counts[$p] = $allcounts[$p];
				$optionlabels[$p] = (string)$option;
			}

			// Add reference for use else where by other plugins etc
			$this->capacities = $capacities;
			$this->waiting = $waiting;
			$this->allcounts = $allcounts;
			$this->counts = $counts;
			$this->optionlabels = $optionlabels;
		}
		else {
			foreach ($this->element->children() as $option)
			{
				$p = $includeintotalcapacity ? intval($option["value"]):(string) $option["value"];
				$capacities[$p] = 0;
				$waiting[$p] = 0;
				$allcounts[$p] = isset($allcounts[$p])?$allcounts[$p]:0;
				$counts[$p] = $allcounts[$p];
				$optionlabels[$p] = (string)$option;
			}

			// Add reference for use else where by other plugins etc
			$this->capacities = $capacities;
			$this->waiting = $waiting;
			$this->allcounts = $allcounts;
			$this->counts = $counts;
			$this->optionlabels = $optionlabels;			
		}

		if ($hasprice)
		{
			
			$this->hasPrices = count($prices) > 0;
			$this->pricesArray = $prices;
			$this->prices = json_encode($prices);

			$attribs .= " onchange='JevrFees.calculate(document.updateattendance);'";
		}

		$hasSpaceLeft = false;
		$options = array();
		foreach ($optionlabels as $p => $text)
		{
			if ($text!="")
			{
				// some short strings like seat number e.g. J1=J20 are translated by Joomla to numbers - we don't want this
				if (!is_numeric($text) && is_numeric( JText::_($text))){
					$text =$text;
				}
				else {
					 $text = JText::_($text);
				}

				// if (excluding this attendee) we are over capacity then do not offer it!
				if ($capacities[$p] > 0 && ($allcounts[$p] - $counts[$p] ) >= ($capacities[$p] + $waiting[$p]) && (!is_array($value) || !in_array($p, $value)))
				{
					continue;
				}

				// if overall capacity over then skip it too
				if ($rsvpdata->capacity && $this->attribute("includeintotalcapacity") && $attendeeCount+$p -$registrationSpecificCount  > $rsvpdata->capacity){
					continue;
				}
				
				$hasSpaceLeft = true;

				$spacesleft = 0;
				if ($p >= 0 && $capacities[$p] > 0 && $allcounts[$p] < $capacities[$p])
				{
					$spacesleft = $capacities[$p] - $allcounts[$p];
					$text .= " (" . $spacesleft . ")";
				}
				else if ($p >= 0 && $capacities[$p] > 0 && $allcounts[$p] >= ($capacities[$p] + $waiting[$p]))
				{
					$text .= " (0)";
				}
				else if ($p >= 0 && $capacities[$p] > 0 && $allcounts[$p] >= $capacities[$p] && $waiting[$p] > 0)
				{
					$spacesleft = $capacities[$p] - $allcounts[$p] - (isset($allcounts[$p + 10000]) ? $allcounts[$p + 10000] : 0);
					if ($value[0] != $p)
					{
						$text .= " (Waiting spaces available : " . (-$spacesleft) . " already waiting)";
					}
				}

				// switching option values when into waiting zone to negative values
				if ($p >= 0 && $capacities[$p] > 0 && $allcounts[$p] >= $capacities[$p] && $value[0] != $p && $waiting[$p] > 0)
				{
					$htmloption = JHtml::_('select.option', 10000 + $p, $text);
				}
				else if ($p >= 0 && $capacities[$p] > 0 && $allcounts[$p] >= $capacities[$p] && $value[0] != $p)
				{
					// option is disabled
					$htmloption = JHtml::_('select.option', $p, $text, "value","text", true);
				}
				// this is editing a waiting entry AFTER the list has been released so reset the value!
				else if (is_array($value) && $value[0] == $p + 10000)
				{
					$value[0] = $p;
					$htmloption = JHtml::_('select.option', $p, $text);
				}
				else
				{
					$htmloption = JHtml::_('select.option', $p, $text);
				}

				$options[] = $htmloption;
			}
		}
		
		if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
		{
			$this->fixValue($value, $this);

			$elementname =  $name . '[]';
			$html = "";
			$i = 0;
			foreach ($value as $val)
			{
				if ($i == 0)
				{
					if ($this->attribute("peruser") == 2)
					{
						$thisclass = str_replace(" xxx", " disabledfirstparam rsvpparam rsvpparam0 rsvp_$fieldname rsvp_xmlfile_$fieldname jevrseattype", $attribs);
					}
					else
					{
						$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_$fieldname rsvp_xmlfile_$fieldname jevrseattype", $attribs);
					}
				}
				else
				{
					$thisclass = str_replace(" xxx", " rsvpparam rsvpparam$i  rsvp_$fieldname rsvp_xmlfile_$fieldname jevrseattype", $attribs);
				}
				$thisclass .= " id = 'rsvp_" . $fieldname . "_span_$i' ";

				// block any choices that increase current selection over the capacity 
				$specificoptions = array();
				foreach ($options as $htmloption){
					if ($this->attribute("capacity")>0 && $fieldcount+intval($htmloption->value)-$val>$this->attribute("capacity") ){
						continue;
					}
					$specificoptions[] = $htmloption;
				}
				if ($rsvpdata->capacity && (count($specificoptions)==0 || (count($specificoptions)==1 && $specificoptions[0]->value==0))){
					$htmloption = JHtml::_('select.option', 0, ($this->attribute("nocapacitymessage")!="" ? $this->attribute("nocapacitymessage") : JText::_("NO_MORE_CAPACITY")));
					$specificoptions = array();
					$specificoptions[] = $htmloption;
				}
				
				$html .= JHtml::_('jevrList.genericlist', $specificoptions, $elementname, $thisclass, 'value', 'text', $val, $id. "_" . $i);
				$i++;
			}
			$val = "";
			$val = "#%^£xx£^%#";
			$thisclass = str_replace(" xxx", " paramtmpl rsvp_$fieldname rsvp_xmlfile_$fieldname jevrseattype", $attribs);
			$thisclass .= " id = 'rsvp_" . $fieldname . "_span_xxxyyyzzz' ";
			$html .= JHtml::_('jevrList.genericlist', $newoptions, "paramtmpl_" . $elementname, $thisclass, 'value', 'text', $val, $id . "_xxx");
		}
		else
		{
			// data intgerity check (in case value was an array before a template change removing guests on this field)
			if (is_array($value)){
				$value = current($value);
			}

			$thisclass = str_replace(" xxx", " rsvpparam rsvpparam0 rsvp_$fieldname rsvp_xmlfile_$fieldname jevrseattype", $attribs);
			// per user is 0 so don't put a guest number on the element!
			$thisclass .= " id = 'rsvp_" . $fieldname . "_span_' ";
			
			// block any choices that increase current selection over the capacity 
			$specificoptions = array();
			foreach ($options as $htmloption){
				if ($this->attribute("capacity")>0 && $fieldcount+intval($htmloption->value)-$value>$this->attribute("capacity") ){
					continue;
				}
				$specificoptions[] = $htmloption;
			}
			if ($rsvpdata->capacity && (count($specificoptions)==0 || (count($specificoptions)==1 && $specificoptions[0]->value==0))){
				$htmloption = JHtml::_('select.option', 0, ($this->attribute("nocapacitymessage")!="" ? $this->attribute("nocapacitymessage") : JText::_("NO_MORE_CAPACITY")));
				$specificoptions = array();
				$specificoptions[] = $htmloption;
			}
			
			$html = JHtml::_('jevrList.genericlist', $specificoptions, '' . $name , $thisclass, 'value', 'text', $value , $id);
		}

		$atdee="";
		if (isset($this->attendee->id) ){
			$atdee="&atdee=".$this->attendee->id;
		}
		
		return $html;

	}

	// use this JS function to fetch the fee calculation script!

	function fetchBalanceScript($value)
	{
		$this->setPrices();
		if ($this->hasPrices)
		{
			$pricefunction = " function(name){return priceJevrList(name, " . $this->prices . ");}";
			$peruser = $this->attribute("peruser");
			if (is_null($peruser))
			{
				$peruser = 0;
			}
			return "JevrFees.fields.push({'name':'" . $this->id. "',  'amount' :0, 'peruser' :" . $peruser . ", 'price' : " . $pricefunction . "});\n ";
		}
		return "";

	}

	private function setPrices() {
		$name = $this->attribute("name");
		
		static $hasPricesData = array();
		static $pricesArrayData = array();
		static $pricesData = array();
		
		if (!isset($this->hasPricesData[$name]))
		{
			$prices = array();
			foreach ($this->element->children() as $option)
			{
				$val = (string) $option["value"];
				$price = (string) $option['price'];
				$text = (string) $option;
				if (!is_null($price))
				{
					$prices[$val] = $price;
					$hasprice = true;
				}
				else
				{
					$prices[$val] = 0;
				}
			}
			$hasPricesData[$name] = count($prices) > 0;
			$pricesArrayData[$name] = $prices;
			$pricesData[$name] = json_encode($prices);
		}		
		$this->hasPrices = $hasPricesData[$name];
		$this->pricesArray = $pricesArrayData[$name];
		$this->prices = $pricesData[$name];
	}
	
	function fetchBalance()
	{
		$this->setPrices();
		
		if (!$this->hasPrices)
		{
			return 0;
		}

		$prices = $this->pricesArray;
		$params = new JRegistry($this->attendee->params);
		$value = $params->get($this->fieldname, "INVALID RSVP SELECTION");
		if ($value == "INVALID RSVP SELECTION")
		{
			// TODO - do we need a warning here?
			return 0;
		}
		if ($this->attribute("peruser") == 1 || $this->attribute("peruser") == 2)
		{
			$this->fixValue($value, $this, false);

			$sum = 0;
			foreach ($value as $i => $val)
			{
				if ($val == "#%^£xx£^%#")
					continue;
				if (!$this->isVisible( $this->attendee, $i))
				{
					continue;
				}
				if (array_key_exists($val, $prices))
				{
					$sum += $prices[$val];
				}
				else
				{
					// TODO - we need a warning here
					$sum += 999999;
				}
			}
			return $sum;
		}
		else
		{
			if (!$this->isVisible( $this->attendee, 0))
				return 0;
			// data intgerity check (in case value was an array before a template change removing guests on this field)
			if (is_array($value)){
				$value = current($value);
			}
			if (array_key_exists($value, $prices))
			{
				return $prices[$value];
			}
			else
			{
				// TODO - we need a warning here
				return 999999;
			}
		}

	}

	public

	function convertValue($value)
	{
		static $values;
		$name = $this->attribute("name");
		if (!isset($values))
		{
			$values = array();
		}
		if (!isset($values[$name]))
		{
			$values[$name] = array();
			foreach ($this->element->children() as $option)
			{
				$val	= (string) $option["value"];
				$price =  (string) $option['price'];
				$text = (string)$option;
				$values[$name][$val] = $text;
			}
		}
		if (!array_key_exists($value, $values[$name]))
		{
			return $this->attribute("default");
		}
		return $values[$name][$value];

	}

	function currentAttendeeCount($node, $value)
	{
		if (is_array($value) && count($value) > 1)
		{
			return count($value) - 1;
		}
		return 1;

	}

	public static function buttonAction($id, $op)
	{
		echo 'onkeyup="jevrlist.updatePreview( \'' . $id . '\');" '; //onblur="jevrlist.updatePreview( \''.$id.'\');"';
		return "";
		echo 'onkeyup="jevrlist.showNext(this, \'' . $id . '\', ' . $op . ');" onblur="jevrlist.showNext(this, \'' . $id . '\', ' . $op . ');"';

	}

	public function getSeatMapping() {

		$mapping = array();
		foreach ($this->element->children() as $option)
		{
			$val	= (int) $option["value"];
			$text = (string)$option;
			$mapping[$val] = $text;
		}
		return $mapping;
	}

}
