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

class JFormFieldJevrcoupon extends JevrFieldList
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'jevrcoupon';
	const name = 'jevrcoupon';

	public static function loadScript($field=false)
	{
		JHtml::script('administrator/components/' . RSVP_COM_COMPONENT . '/fields/js/jevrcoupon.js');

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
			<div class="rsvpinputs" style="font-weight:bold;"><?php echo JText::_("RSVP_TEMPLATE_TYPE_jevrcoupon"); ?><?php RsvpHelper::fieldId($id);?></div>
			<div class="rsvpclear"></div>

			<?php
			RsvpHelper::hidden($id, $field, self::name);
			RsvpHelper::label($id,  $field, self::name);
			RsvpHelper::tooltip($id, $field);

			$maxuses = "0";
			if ($field)
			{
				try {
					$params = json_decode($field->params);
					$maxuses = isset($params->maxuses)?$params->maxuses:0;
				}
				catch (Exception $e) {
					$params = array();
				}
			}

			$includeintotalcapacity = isset($params->includeintotalcapacity) ? intval($params->includeintotalcapacity) : 0;
			$capacity = isset($params->capacity) ? intval($params->capacity) : 0;
			$nocapacitymessage = isset($params->nocapacitymessage) ? $params->nocapacitymessage : "";
			$reducevaluefortotalcapacity = isset($params->reducevaluefortotalcapacity) ? intval($params->reducevaluefortotalcapacity) : 0;
			$additivepercent = isset($params->additivepercent) ? intval($params->additivepercent) : 0;
			?>

			<div class="rsvplabel"><?php echo JText::_("RSVP_OPTIONS"); ?></div>
			<div class="rsvpinputs">
				<!-- Put the selected option here //-->
				<input type="hidden" name="dv[<?php echo $id; ?>]" id="dv<?php echo $id; ?>" value="<?php echo $field ? $field->defaultvalue : ""; ?>" />
				<?php
				$options = array();
				if ($field && $field->options != "")
				{
					$optionvalues = json_decode($field->options);
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
							if ( isset($optionvalues->validfrom[$countoptions] )  && ($optionvalues->validfrom[$countoptions] ==""  && $optionvalues->validto[$countoptions]=="")){
								break;
							}
							else if ( !isset($optionvalues->validfrom[$countoptions] )) {
								break;
							}
						}
						$option = new stdClass();
						$option->value = $optionvalues->value[$countoptions];
						$option->price = isset($optionvalues->price) ? $optionvalues->price[$countoptions] : 0;
						$option->maxuses = isset($optionvalues->maxuses) ? $optionvalues->maxuses[$countoptions] : 0;
						$option->validfrom = isset($optionvalues->validfrom) ? $optionvalues->validfrom[$countoptions] : "";
						$option->validto = isset($optionvalues->validto) ? $optionvalues->validto[$countoptions] : "";
						$option->type = isset($optionvalues->type) ? $optionvalues->type[$countoptions] : 0;

						$option->label = $lab;
						$options[] = $option;
						$countoptions++;
					}
				}

				// add 20 blank options at the end
				for ($op = 0; $op < 2; $op++)
				{
					$option = new stdClass();
					$option->value = $maxvalue + 1;
					$option->price = 0;
					$option->maxuses = 0;
					$maxvalue++;
					$option->label = "";
					$option->validfrom="";
					$option->validto="";
					$option->type = 0;
					$options[] = $option;
				}
				?>
				<input type="button" value="<?php echo JText::_("RSVP_NEW_OPTION") ?>" onclick="jevrcoupon.newOption('<?php echo $id; ?>');"/>
				<table id="options<?php echo $id; ?>">
					<tr >
						<th><?php echo JText::_("RSVP_COUPON_CODE") ?></th>
						<th style="display:none;"><?php echo JText::_("RSVP_OPTION_VALUE") ?></th>
						<th ><?php echo JText::_("RSVP_DISCOUNT_VALUE") ?></th>
						<th ><?php echo JText::_("RSVP_DISCOUNT_TYPE") ?></th>
						<!-- <th ><?php echo JText::_("RSVP_MAX_USES") ?></th>//-->
						<th ><?php echo JText::_("RSVP_VALID_FROM") ?></th>
						<th ><?php echo JText::_("RSVP_VALID_TO") ?></th>
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
								<input type="text" class="inputlabel" name="options[<?php echo $id; ?>][label][]" id="options<?php echo $id; ?>_t_<?php echo $op; ?>" value="<?php echo $option->label; ?>" <?php JFormFieldJevrcoupon::buttonAction($id, $op); ?>/>
							</td>
							<td  style="display:none;">
								<input type="text" name="options[<?php echo $id; ?>][value][]" id="options<?php echo $id; ?>_v_<?php echo $op; ?>" value="<?php echo $option->value; ?>" <?php JFormFieldJevrcoupon::buttonAction($id, $op); ?> class="jevoption_value"/>
							</td>
							<td >
								<input type="text" name="options[<?php echo $id; ?>][price][]" id="options<?php echo $id; ?>_p_<?php echo $op; ?>" value="<?php echo $option->price; ?>" class="jevfee_value"/>
							</td>
							<td class="radio btn-group">
								<select name="options[<?php echo $id; ?>][type][]" id="options<?php echo $id; ?>_t_<?php echo $op; ?>" class="jevfee_type">
									<option  value="0" <?php
								if ($option->type == 0)
								{
									echo 'selected="selected"';
								}
								?> ><?php echo JText::_("RSVP_FIXED"); ?></option>
									<option  value="1" <?php
								if ($option->type == 1)
								{
									echo 'selected="selected"';
								}
								?> ><?php echo JText::_("RSVP_PERCENT"); ?></option>
								</select>

							</td>
							<!--
							<td >
								<input type="text" name="options[<?php echo $id; ?>][maxuses][]" id="options<?php echo $id; ?>_m_<?php echo $op; ?>" value="<?php echo $option->maxuses; ?>" class="jevfee_value"/>
							</td>
							//-->
							<td >
								<?php
								echo  JFormFieldJevrcoupon::calendar($option->validfrom, "options[". $id ."][validfrom][]", "options".$id."_vf_". $op, '%Y-%m-%d', array("size"=>12, 'class'=>"rsvpcal" ));
								?>
							</td>
							<td >
								<?php
								echo  JFormFieldJevrcoupon::calendar($option->validto, "options[". $id ."][validto][]", "options".$id."_vt_". $op, '%Y-%m-%d', array("size"=>12, 'class'=>"rsvpcal" ));
								?>
							</td>
							<td>
								<input type="button" value="<?php echo JText::_("RSVP_DELETE_OPTION") ?>" onclick="jevrcoupon.deleteOption(this);"/>
							</td>
						</tr>
						<?php
					}
					?>
				</table>

			</div>
			<div class="rsvpclear"></div>

			<div class="rsvplabel"><?php echo JText::_("RSVP_PERCENTAGE_SURCHARGES_ARE_ADDITIVE"); ?></div>
			<div class="rsvpinputs">
				<label for="additivepercent1<?php echo $id; ?>"><?php echo JText::_("JYES"); ?>
				<input type="radio" name="params[<?php echo $id; ?>][additivepercent]"  id="additivepercent1<?php echo $id; ?>" value="1" <?php
			if ($additivepercent == 1)
			{
				echo 'checked="checked"';
			}
					?> />
				</label>
				<label for="additivepercent0<?php echo $id; ?>"><?php echo JText::_("JNO"); ?>
				<input type="radio" name="params[<?php echo $id; ?>][additivepercent]" id="additivepercent0<?php echo $id; ?>" value="0" <?php
			   if ($additivepercent == 0)
			   {
				   echo 'checked="checked"';
			   }
					?> />
				</label>
			</div>
			<div class="rsvpclear"></div>

			<div class="rsvplabel"><?php echo JText::_("RSVP_MAX_USES_OF_EACH_COUPON"); ?></div>
			<div class="rsvpinputs">
				<input type="text" name="params[<?php echo $id; ?>][maxuses]" id="dv<?php echo $id; ?>maxuses" size="15"   value="<?php echo $maxuses; ?>" size="5" maxsize="10" />
				&nbsp; <?php echo JText::_("RSVP_COUPON_MAXUSE_NOTES") ; ?>
			</div>
			<div class="rsvpclear"></div>

			<?php
			//RsvpHelper::required($id, $field);
			//RsvpHelper::requiredMessage($id, $field);
			RsvpHelper::size($id, $field, self::name);
			RsvpHelper::maxlength($id, $field, self::name);
			RsvpHelper::conditional($id,  $field);

			// Special version of per user that means there is a single input but multi application
			//RsvpHelper::peruser($id, $field);

			$appliesperuser = isset($params->appliesperuser) ? intval($params->appliesperuser) : 0;
			$fieldname = "params[$id][appliesperuser]";
			?>
			<div class="rsvplabel"><?php echo JText::_("RSVP_PER_USER"); ?></div>
			<div class="rsvpinputs">
				<?php
				$options = array();
				$options[] = JHtml::_('select.option', 0, JText::_("RSVP_PRIMARY_REGISTRATION"));
				$options[] = JHtml::_('select.option', 1, JText::_("RSVP_PRIMARY_REGISTRATION_AND_GUESTS"));
				$options[] = JHtml::_('select.option', 2, JText::_("RSVP_PRIMARY_GUESTS_ONLY"));

				// build the html select list
				echo JHtml::_('select.genericlist', $options, $fieldname, 'class="inputbox" size="1"', 'value', 'text', $appliesperuser);
				?>
				<br/>
				<em><?php  echo JText::_("RSVP_COUPON_PER_USER_NOTES") ; ?></em><br/>
			</div>
			<div class="rsvpclear"></div>

			<?php

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
			<div class="rsvplabel rsvppl" id='pl<?php echo $id; ?>' ><?php echo $field ? $field->label : JText::_("RSVP_FIELD_LABEL"); ?></div>
			<input type="text"  id="pdv<?php echo $id; ?>" value="<?php echo $field ? $field->defaultvalue : ""; ?>" size="<?php echo $field ? $field->size : 5; ?>"  />
		</div>
		<div class="rsvpclear"></div>
		<?php
		$html = ob_get_clean();

		return RsvpHelper::setField($id, $field, $html, self::name);

	}

	public static function paidOption()
	{
		return 1;

	}

	function getInput()
	{
		$this->setPrices();

		if (!$this->showEarlyBirdInput()){
			return "";
		}
		$showEarlyBird = $this->isEarlyBird;

		$node =  $this->element;
		$name = $this->name;
		$id = $this->id;
		$value = $this->value;
		$fieldname = $this->fieldname;

		$attribs = ( $this->attribute('class') ? 'class="' . $this->attribute('class') . ' xxx"' : 'class=" xxx"' );

		$html = "";
		$hasprice = false;
		$options = array();
		$prices = array();
		$hassurcharges = false;
		$additivepercent = $this->attribute('additivepercent') ? intval($this->attribute('additivepercent')) : 0;
		$surcharges = array();

		foreach ($this->element->children() as $option)
		{
			$val	= (float) $option["value"];
			$text = (string)$option;
			$htmloption = JHtml::_('select.option', $val, JText::_($text));
			$price =  (string) $option['price'];
			$coupontype = (integer) $option['type'];

			// coupontype=0 for fixed and 1 for percentage
			if ($coupontype==1)
			{
				$htmloption->surcharge =-1 *  $price;
				$surcharges[$text] = -1 * $price;
				$hassurcharges = true;
			}
			else
			{
				$surcharges[$text] = 0;
			}

			if (!is_null($price) && !$coupontype)
			{
				$htmloption->price = $price;
				$prices[$text] = $price;
				$hasprice = true;
			}
			else
			{
				$prices[$text] = 0;
			}
			$options[] = $htmloption;
		}

		if ($hasprice)
		{
			$this->hasPrices = count($prices) > 0;
			$this->pricesArray = $prices;
			$this->prices = json_encode($prices);

			$attribs .= " onchange='JevrFees.calculate(document.updateattendance);'";
		}

		if ($hassurcharges)
		{
			$this->hasSurcharges = count($surcharges) > 0;
			$this->surchargesArray = $surcharges;
			$this->surcharges = json_encode($surcharges);

			if (!$hasprice) {
				$attribs .= " onchange='JevrFees.calculate(document.updateattendance);'";
			}
		}


		$client = JFactory::getApplication()->isAdmin()?"administrator":"site";
		if (version_compare(JVERSION, "1.6.0", 'ge')){
			$pluginpath = 'plugins/jevents/jevrsvppro/rsvppro/';
		}
		else {
			$pluginpath = 'plugins/jevents/rsvppro/';
		}

		$fieldid = intval(str_replace("field", "",$this->fieldname));
		//$attribs .= 'onchange="'.$action.'" onkeyup="'.$action.'"';

		$attdendeeid = isset($this->attendee->id) ? $this->attendee->id : 0;

		// no need for getting coupons for each guest
		$elementname = $name;
		$thisclass = str_replace(" xxx", " ", $attribs);

		$action = 'checkCoupon(event,jQuery(\'#' . $id.'\'),\''.JURI::root().$pluginpath."checkcoupon.php".'\', \''.$client.'\', \''.$fieldid.'\', \''.$this->event->rp_id().'\', \''.$this->rsvpdata->id.'\', \''.$attdendeeid.'\')';
		if ($this->isEarlyBird){
			$html = '<input type="hidden" name="' . $elementname . '" id="' . $id . '" value="' . $value . '" ' . $thisclass . '  />';
		}
		else {
			$html .= "<span $thisclass>";
			$html = '<input type="text" name="' . $elementname . '" id="' . $id . '" value="' . $value . '" ' . $thisclass . '  />';
			$html .= ' <input type="button" onclick="'.$action.'" value="'.JText::_("RSVP_APPLY_COUPON",true).'" /> ';
			$html .= ' <br/>';
		}
		if (array_key_exists($value, $surcharges) && $surcharges[$value]!=0){
			if ($additivepercent){
				$couponInUse = JText::sprintf("RSVP_SALES_TAX",  "<span id='rsvpst_".$id."'></span> ", $surcharges[$value]);
				$html .= $couponInUse;
			}
			else {
				$couponInUse = JText::sprintf("RSVP_PERCENTAGE_DISCOUNT",  $surcharges[$value]);
				$html .= $couponInUse;
			}
		}
		if (array_key_exists($value, $prices) && $prices[$value]!=0){
			$appliesperuser = $this->attribute("appliesperuser", 0);
			$couponInUse = JText::sprintf("RSVP_FIXED_DISCOUNT_".$appliesperuser,  RsvpHelper::phpMoneyFormat( $prices[$value]));
			$html .= $couponInUse;
		}
		if (!$this->isEarlyBird){
			$html .= "</span>";
		}

		return $html;

	}

	function getLabel() {
		$this->setPrices();

		if (!$this->showEarlyBirdInput()){
			return "";
		}

		return parent::getLabel();
	}

	// use this JS function to fetch the fee calculation script!

	function fetchBalanceScript( $value)
	{
		$id = $this->fieldname;
		$this->setPrices();

		$earlybird= false;
		if (is_array($value) && count($value)>0 && $value[0] == "")
		{
			// is this an early bird discount?
			if ($this->attendee && isset($this->attendee->id) && $this->attendee->id) {
				$created = strtotime($this->attendee->created);
			}
			else {
				$created = time();
			}
			for($vf=0;$vf<count($this->validfromArray); $vf++){
				$validFrom = strtotime(($this->validfromArray[$vf] ? $this->validfromArray[$vf] : "1970-01-01"). " 00:00:00");
				$validTo = strtotime(($this->validtoArray[$vf]?$this->validtoArray[$vf]:"2199-12-31"). " 23:59:59");
				if ($earlybird===false && $created>=$validFrom && $created<=$validTo){
					for($v=0;$v<count($value);$v++){
						$value[$v] = $earlybird = "earlybird###".$vf;
					}
					break;
				}
			}
			if ($earlybird===false){
				return "";
			}
		}
		// is this coupon valid for the date
		else if (is_array($value) && count($value)>0) {
			// is this coupon within dates
			if ($this->attendee && isset($this->attendee->id) && $this->attendee->id) {
				$created = strtotime($this->attendee->created);
			}
			else {
				$created=time();
			}
			if (array_key_exists($value[0], $this->pricesArray)) {
				$vf = array_search($value[0],array_keys($this->pricesArray));
				if (isset($this->validfromArray[$vf])) {
					$validFrom = strtotime(($this->validfromArray[$vf] ? $this->validfromArray[$vf] : "1970-01-01"). " 00:00:00");
					$validTo = strtotime(($this->validtoArray[$vf]?$this->validtoArray[$vf]:"2199-12-31"). " 23:59:59");
					if ($created<$validFrom || $created>$validTo){
						return "";
					}
				}
			}
		}

		// dynamic checking of coupon codes done using JSON NOT JAVASCRIPT for obvious reasons!
		if ($this->hasPrices)
		{
			$appliesperuser = $this->attribute("appliesperuser", 0);
			$pricefunction = " function(name){if ($appliesperuser==1) { return \$('guestcount').value * ".$id."discount; } \nelse if ($appliesperuser==2)  { return (\$('guestcount').value-1) * ".$id."discount; } \n else return ".$id."discount;}";

			static $values;
			$name = $this->attribute("name");
			if (!isset($values))
			{
				$values = array();
			}
			if (!isset($values[$id]))
			{
				$values[$id] = array();
				$count = 0;
				foreach ($this->element->children() as $option)
				{
					$discount = (string)$option["price"];
					$text = (string)$option;
					if ($text=="" && ((string) $option["validfrom"] || (string) $option["validto"])){
						$text= "earlybird###".$count;
					}
					$coupontype = (integer) $option['type'];
					// coupontype=0 for fixed and 1 for percentage
					if ($coupontype)
					{
						$values[$id][$text] = 0;
					}
					else {
						$values[$id][$text] = $discount;
					}
					$count++;
				}
			}
			if (!array_key_exists($value[0], $values[$id]))
			{
				$discval = 0;
			}
			// early bird discounts

			else {
				if ($appliesperuser==0){
					$discval =  -$values[$id][$value[0]];
				}
				else {
					$discval -=  $values[$id][$value[0]];
				}
			}


			return "var ".$id."discount=".$discval.";\n  JevrFees.fields.push({'name':'" . $this->id. "',  'amount' :0, 'peruser' :" . $appliesperuser . ", 'byguest' :" . $appliesperuser . ", 'price' : " . $pricefunction . "});\n ";
		}
		return "";

	}

	function fetchBalance()
	{
		$this->setPrices();

		if (!$this->hasPrices)
		{
			return 0;
		}

		$prices = $this->pricesArray;
		$surcharges = $this->surchargesArray;
		$params = new JRegistry($this->attendee->params);
		$value = $params->get($this->attribute("name"), "INVALID RSVP SELECTION");
		$earlybird = false;
		if ($value == "INVALID RSVP SELECTION")
		{
			// is this an early bird discount?
			if ($this->attendee && isset($this->attendee->id) && $this->attendee->id &&  isset($this->validfromArray)) {
				$created = strtotime($this->attendee->created);
				for($vf=0;$vf<count($this->validfromArray); $vf++){
					$validFrom = strtotime(($this->validfromArray[$vf] ? $this->validfromArray[$vf] : "1970-01-01"). " 00:00:00");
					$validTo = strtotime(($this->validtoArray[$vf]?$this->validtoArray[$vf]:"2199-12-31"). " 23:59:59");
					if ($earlybird===false && $created>=$validFrom && $created<=$validTo){
						$value = $earlybird = "earlybird###".$vf;
						break;
					}
				}
			}
			if ($earlybird===false){
				// TODO - do we need a warning here?
				return 0;
			}
		}
		// is this coupon valid for the date
		else {
			// is this coupon within dates
			if ($this->attendee && isset($this->attendee->id) && $this->attendee->id) {
				$created = strtotime($this->attendee->created);
				if (array_key_exists($value, $this->pricesArray)) {
					$vf = array_search($value,array_keys($this->pricesArray));
					if (isset($this->validfromArray[$vf])) {
						$validFrom = strtotime(($this->validfromArray[$vf] ? $this->validfromArray[$vf] : "1970-01-01"). " 00:00:00");
						$validTo = strtotime(($this->validtoArray[$vf]?$this->validtoArray[$vf]:"2199-12-31"). " 23:59:59");
						if ($created<$validFrom || $created>$validTo){
							return 0;
						}
					}
				}

			}
		}

		$appliesperuser = $this->attribute("appliesperuser", 0);
		if ($appliesperuser){
			$gc = $this->attendee->guestcount;
			if ($gc==0){
				return 0;
			}
			$value = trim($value);
			if (array_key_exists($value, $prices))
			{
				// coupons are negative prices
				return -1 * $prices[$value] * (($appliesperuser==1) ? $gc : $gc-1);
			}
			else
			{
				// Invalid coupon so ignore it
				return -0;
			}
		}
		else
		{
			$value = trim($value);

			if (array_key_exists($value, $prices))
			{
				// coupons are negative prices
				return  - $prices[$value];
			}
			else
			{
				// Invalid coupon so ignore it
				return -0;
			}

		}

	}

	// use this JS function to fetch the fee calculation script!

	function fetchSurchargeScript($name, &$node, $control_name, $value)
	{

		$id = $this->fieldname;
		$this->setPrices();

		$earlybird= false;
		if (is_array($value) && count($value)>0 && $value[0] == "")
		{
			// is this an early bird discount?
			if ($this->attendee && isset($this->attendee->id) && $this->attendee->id) {
				$created = strtotime($this->attendee->created);
			}
			else {
				$created = time();
			}
			for($vf=0;$vf<count($this->validfromArray); $vf++){
				$validFrom = strtotime(($this->validfromArray[$vf] ? $this->validfromArray[$vf] : "1970-01-01"). " 00:00:00");
				$validTo = strtotime(($this->validtoArray[$vf]?$this->validtoArray[$vf]:"2199-12-31"). " 23:59:59");
				if ($earlybird===false && $created>=$validFrom && $created<=$validTo){
					for($v=0;$v<count($value);$v++){
						$value[$v] = $earlybird = "earlybird###".$vf;
					}
					break;
				}
			}
			if ($earlybird===false){
				return "";
			}
		}
		// is this coupon valid for the date
		else if (is_array($value) && count($value)>0) {
			// is this coupon within dates
			if ($this->attendee && isset($this->attendee->id) && $this->attendee->id) {
				$created = strtotime($this->attendee->created);
			}
			else {
				$created=time();
			}
			if (array_key_exists($value[0], $this->surchargesArray)) {
				$vf = array_search($value[0],array_keys($this->surchargesArray));
				if (isset($this->validfromArray[$vf])) {
					$validFrom = strtotime(($this->validfromArray[$vf] ? $this->validfromArray[$vf] : "1970-01-01"). " 00:00:00");
					$validTo = strtotime(($this->validtoArray[$vf]?$this->validtoArray[$vf]:"2199-12-31"). " 23:59:59");
					if ($created<$validFrom || $created>$validTo){
						return "";
					}
				}
			}
		}


		// dynamic checking of coupon codes done using JSON NOT JAVASCRIPT for obvious reasons!
		if ($this->hasSurcharges)
		{
			$surchargefunction = " function(name){return ".$id."surcharge;}";
			// NEVER applies more than once!
			$appliesperuser = 0;

			// If any of the options are additive percentages then they all are treated as that
			$additivepercent = $this->attribute('additivepercent') ? intval($this->attribute('additivepercent')) : 0;

			static $values;
			$name = $this->attribute("name");
			if (!isset($values))
			{
				$values = array();
			}
			if (!isset($values[$id]))
			{
				$values[$id] = array();
				$count = 0;
				foreach ($this->element->children() as $option)
				{
					$discount = (string)$option["price"];
					$text = (string)$option;
					if ($text=="" && ((string) $option["validfrom"] || (string) $option["validto"])){
						$text= "earlybird###".$count;
					}
					$coupontype = (integer) $option['type'];
					// coupontype=0 for fixed and 1 for percentage
					if ($coupontype)
					{
						$values[$id][$text] = $discount;
					}
					else {
						$values[$id][$text] = 0;
					}

					$count++;
				}
			}
			if (!array_key_exists($value[0], $values[$id]))
			{
				$scval = 0;
			}
			// early bird discounts
			else {
				$scval =  -$values[$id][$value[0]];
			}

			return "var ".$id."surcharge=".$scval.";\n  JevrFees.fields.push({'name':'" . $this->id. "', 'surcharge' :0,  'amount' :0, 'peruser' :" . $appliesperuser .  ", 'surchargefunction' : " . $surchargefunction . ", 'additivesurcharge' :".$additivepercent. "});\n ";
		}
		return "";

	}

	function fetchSurcharge(&$node)
	{
		$this->setPrices();

		if (!$this->hasSurcharges)
		{
			return 0;
		}

		$surcharges = $this->surchargesArray;
		$params = new JRegistry($this->attendee->params);
		$value = $params->get($this->attribute("name"), "INVALID RSVP SELECTION");
		$earlybird = false;
		if ($value == "INVALID RSVP SELECTION")
		{
			// is this an early bird discount?
			if ($this->attendee && isset($this->attendee->id) && $this->attendee->id && isset($this->validfromArray)) {
				$created = strtotime($this->attendee->created);
				for($vf=0;$vf<count($this->validfromArray); $vf++){
					if ($this->validfromArray[$vf] == "" && $this->validtoArray[$vf] ==""){
						continue;
					}
					$validFrom = strtotime(($this->validfromArray[$vf] ? $this->validfromArray[$vf] : "1970-01-01"). " 00:00:00");
					$validTo = strtotime(($this->validtoArray[$vf]?$this->validtoArray[$vf]:"2199-12-31"). " 23:59:59");
					if ($earlybird===false && $created>=$validFrom && $created<=$validTo){
						$value = $earlybird = "earlybird###".$vf;
						break;
					}
				}
			}
			if ($earlybird===false){
				// TODO - do we need a warning here?
				return 0;
			}
		}
		// is this coupon valid for the date
		else {
			// is this coupon within dates
			if ($this->attendee && isset($this->attendee->id) && $this->attendee->id) {
				$created = strtotime($this->attendee->created);
				if (array_key_exists($value, $this->surchargesArray)) {
					$vf = array_search($value,array_keys($this->surchargesArray));
					if (isset($this->validfromArray[$vf])) {
						$validFrom = strtotime(($this->validfromArray[$vf] ? $this->validfromArray[$vf] : "1970-01-01"). " 00:00:00");
						$validTo = strtotime(($this->validtoArray[$vf]?$this->validtoArray[$vf]:"2199-12-31"). " 23:59:59");
						if ($created<$validFrom || $created>$validTo){
							return 0;
						}
					}
				}

			}
		}

		// never guest specific
		if (!$this->isVisible( $this->attendee, 0))
			return 0;

		// data intgerity check (in case value was an array before a template change removing guests on this field)
		if (is_array($value)){
			$value = current($value);
		}
		if (array_key_exists($value, $surcharges))
		{
			return $surcharges[$value];
		}
		else
		{
			// TODO - we need a warning here
			if (JFactory::getApplication()->isAdmin()){
				static $warning;
				if (!isset($warning)){
					JFactory::getApplication()->enqueueMessage(JText::_("RSVP_APPLIED_COUPON_IS_INVALID"),"warning");
					$warning = 1;
				}
			}
			return 0;
		}

	}

	public function setPrices() {
		$name = $this->attribute("name");

		static $hasPricesData = array();
		static $pricesArrayData = array();
		static $pricesData = array();
		static $hasSurchargesData = array();
		static $surchargesArrayData = array();
		static $surchargesData = array();
		static $validfromArrayData = array();
		static $validtoArrayData = array();
		static $isEarlyBird = array();

		if (!isset($hasPricesData[$name]))
		{
			$prices = array();
			$surcharges = array();
			$validfrom = array();
			$validto = array();
			$count = 0;
			$earlybird = false;
			foreach ($this->element->children() as $option)
			{
				$code = (string) $option;
				$val = (float) $option["value"];
				$price = (string) $option['price'];
				$coupontype = (integer) $option['type'];
				$text = (string) $option;
				$validfrom[] = (string) $option["validfrom"];
				$validto[]	= (string) $option["validto"];
				if ($code=="" && ((string) $option["validfrom"] || (string) $option["validto"])){
					$code= "earlybird###".$count;
					$earlybird = true;
				}

				// coupontype=0 for fixed and 1 for percentage
				if ($coupontype)
				{
					$surcharges[$code] = -1 * $price;
					$hassurcharges = true;
				}
				else
				{
					$surcharges[$code] = 0;
				}

				if (!is_null($price) && !$coupontype)
				{
					$prices[$code] = $price;
					$hasprice = true;
				}
				else
				{
					$prices[$code] = 0;
				}
				$count++;
			}
			$hasPricesData[$name] = count($prices) > 0;
			$pricesArrayData[$name] = $prices;
			$pricesData[$name] = json_encode($prices);
			$hasSurchargesData[$name] = count($surcharges) > 0;
			$surchargesArrayData[$name] = $surcharges;
			$surchargesData[$name] = json_encode($surcharges);
			$validfromArrayData[$name]=$validfrom;
			$validtoArrayData[$name]=$validto;
			$isEarlyBird[$name]=$earlybird;
		}
		$this->hasPrices = $hasPricesData[$name];
		$this->pricesArray = $pricesArrayData[$name];
		$this->prices = $pricesData[$name];
		$this->hasSurcharges = $hasSurchargesData[$name];
		$this->surchargesArray = $surchargesArrayData[$name];
		$this->surcharges = $surchargesData[$name];

		$this->validfromArray = $validfromArrayData[$name];
		$this->validtoArray = $validtoArrayData[$name];

		$this->isEarlyBird = $isEarlyBird[$name];
	}

	public function fixPerUser(){
		// This setting is controlled by a special parameter and not the peruser field - so set peruser to zero
		$this->addAttribute("peruser",0);
		return;
	}

	public function fixRawValues(&$values) {
		$this->setPrices();
		$hasEarlyBird=false;
		$count =0;
		foreach ($this->element->children() as $option)
		{
			$discount	= (float) $option["price"];
			$text = (string)$option;
			if ($text=="" && ((string) $option["validfrom"] || (string) $option["validto"])){
				$text = "earlybird###".$count;
				$hasEarlyBird=true;
				break;
			}
			$count ++;
		}
		$isSurcharge = false;
		if (array_key_exists($this->value, $this->surchargesArray) && floatval($this->surchargesArray[$this->value])!=0){
			$isSurcharge = true;
		}

		// new version
		if ($hasEarlyBird) {
			$values= $text;
		}
		return;

	}

	public
	function convertValue($value)
	{
		$this->fixPerUser();

		static $values;
		static $hasEarlyBird=false;
		$name = $this->attribute("name");
		$additivepercent = intval($this->attribute("additivepercent"));
		if (!isset($values))
		{
			$values = array();
		}
		if (!isset($values[$name]))
		{
			$values[$name] = array();
			$count = 0;
			foreach ($this->element->children() as $option)
			{
				$discount	= (float) $option["price"];
				$text = (string)$option;
				if ($text=="" && ((string) $option["validfrom"] || (string) $option["validto"])){
					$text = "earlybird###".$count;
					$hasEarlyBird=true;
				}

				$values[$name][$text] = $discount;

				$coupontype = (integer) $option['type'];
				// coupontype=0 for fixed and 1 for percentage
				if ($additivepercent && $coupontype)
				{
					if (isset($this->form->outstandingBalances["rawtotalfee"])){
						$values[$name][$text] = RsvpHelper::phpMoneyFormat( - $values[$name][$text] * 0.01 * $this->form->outstandingBalances["rawtotalfee"]).  " (".(-1*$values[$name][$text]) . " %)";
					}
					else {
						$values[$name][$text] = $values[$name][$text] . " %";
					}
				}
				else if ($coupontype)
				{
					$values[$name][$text] = $values[$name][$text] . " %";
				}
				else {
					$values[$name][$text] =  RsvpHelper::phpMoneyFormat( - $values[$name][$text]) ;
				}

				$count ++;
			}
		}

		if ($value =="" && $hasEarlyBird){
			$this->setPrices();
			// is this an early bird discount?
			if ($this->attendee && isset($this->attendee->id) && $this->attendee->id) {
				$created = strtotime($this->attendee->created);
			}
			else {
				$created = time();
			}
			for($vf=0;$vf<count($this->validfromArray); $vf++){
				$validFrom = strtotime(($this->validfromArray[$vf] ? $this->validfromArray[$vf] : "1970-01-01"). " 00:00:00");
				$validTo = strtotime(($this->validtoArray[$vf]?$this->validtoArray[$vf]:"2199-12-31"). " 23:59:59");
				if ($value =="" && $created>=$validFrom && $created<=$validTo){
					$value = "earlybird###".$vf;
					break;
				}
			}
		}

		if (!array_key_exists($value, $values[$name]))
		{
			return RsvpHelper::phpMoneyFormat(0);
		}
		$user = JFactory::getUser ();

		$output = $values[$name][$value] ;

		$appliesperuser = $this->attribute("appliesperuser", 0);
		if ($appliesperuser){
			$gc = $this->attendee->guestcount;
			if ($gc==0){
				$output =  "";
			}
			//  Don't show multiple on %age discounts
			if (strpos($output, '%')===false){
				if ($appliesperuser==1){
					if ($gc>1){
						 $output .= " x $gc";
					}
				}
				else {
					if ($gc>1){
						$output .= " x ".($gc-1);
					}
				}
			}
		}

		// only show coupon used to the event creator and if not an early bird discount
		if ($user->id == $this->event->created_by() && $value !="" && strpos($value, "earlybird###")===false){
			$output .= " (".$value.")";
		}
		return $output;
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
		echo 'onkeyup="jevrcoupon.updatePreview( \'' . $id . '\');" '; //onblur="jevrcoupon.updatePreview( \''.$id.'\');"';
		return "";
		echo 'onkeyup="jevrcoupon.showNext(this, \'' . $id . '\', ' . $op . ');" onblur="jevrcoupon.showNext(this, \'' . $id . '\', ' . $op . ');"';

	}


	public static function calendar($value, $name, $id, $format = '%Y-%m-%d', $attribs = null)
	{
		static $done;

		if ($done === null)
		{
			$done = array();
			// Load the calendar behavior
			JHtml::_('behavior.calendar');
			JHtml::_('behavior.tooltip');

			$document = JFactory::getDocument();
			$firstday = JFactory::getLanguage()->getFirstDay();
			$script = <<<SCRIPT
 function clonecalendar(){
	var imgid = this.id;
	var fieldid = this.id.replace("_img","");

Calendar.setup({
				// Id of the input field
				inputField: fieldid,
				//displayArea : fieldid+'div',
				// Format of the input field
				//ifFormat:  "%Y-%m-%d",
				ifFormat:  "$format",
				//   daFormat      | the date format that will be used to display the date in displayArea
				daFormat: "$format",
				// Trigger for the calendar (button ID)
				button: imgid,
				// Alignment (defaults to "Bl")
				align: "Tl",
				singleClick: true,
				firstDay: '. $firstday .'
				});
}
SCRIPT;
			$document->addScriptDeclaration($script);
		}

		// Only display the triggers once for each control.
		if (!in_array($id, $done))
		{
		}

		$done[] = $id;

		$imgattribs = array('class' => 'calendar ' . (isset($attribs["class"])?$attribs["class"]:""), 'id' => $id . '_img', 'onload' => 'clonecalendar.delay(1100,this);');

		$readonly = isset($attribs['readonly']) && $attribs['readonly'] == 'readonly';
		$disabled = isset($attribs['disabled']) && $attribs['disabled'] == 'disabled';
		if (is_array($attribs))
		{
			$attribs = JArrayHelper::toString($attribs);
		}
		$titlevalue = "";
		if ($value != "")
		{
			try {
				$tempdate = is_callable("DateTime::createFromFormat") ? DateTime::createFromFormat(str_replace("%", "", $format), $value) : JFormFieldJevrcoupon::datetotime($value,$format);
				$titlevalue = "";
				if ($tempdate)
				{
					$invalue = $tempdate->format("Y-m-d");
					$titlevalue = JHtml::_('date', $invalue);
				}
			}
			catch (Exception $e) {
				$titlevalue = "";
			}
		}

		return '<span id="'.$id.'div" ><input type="text" title="' . $titlevalue . '" name="' . $name . '" id="' . $id . '" '.(isset($attribs["class"])?"class='".$attribs["class"]."'":"").' placeholder="YYYY-MM-DD" value="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '" ' . $attribs . '   />' .
				JHtml::_('image', 'system/calendar.png', JText::_('JLIB_HTML_CALENDAR'), $imgattribs, true) .
				"</span>"	;

	}

	protected function showEarlyBirdInput(){
		// is this an early bird discount to be shown?
		$earlybird=false;
		if (isset($this->validfromArray) || isset($this->validtoArray)) {
			if ($this->attendee && isset($this->attendee->id) && $this->attendee->id) {
				$created = strtotime($this->attendee->created);
			}
			else {
				$created = time();
			}
			$count = max(array(count($this->validfromArray), count($this->validtoArray)));
			for($vf=0;$vf<$count; $vf++){
				$validFrom = strtotime(($this->validfromArray[$vf] ? $this->validfromArray[$vf] : "1970-01-01"). " 00:00:00");
				$validTo = strtotime(($this->validtoArray[$vf]?$this->validtoArray[$vf]:"2199-12-31"). " 23:59:59");
				// is the attendee using this coupon already - in which case we ignore the date range
				if ($this->attendee){
					if (!isset($this->attendee->fieldValues)){
						$this->attendee->fieldValues = new JRegistry($this->attendee->params);
					}
					$fieldValue = $this->attendee->fieldValues->get($this->fieldname, "");
					if (is_array($fieldValue)){
						foreach ($fieldValue as $fv){
							if (array_key_exists($fv, $this->surchargesArray) && $this->surchargesArray[$fv]==$vf){
								$earlybird = true;
								return $earlybird;
							}
						}
					}
					else if (array_key_exists($fieldValue, $this->surchargesArray) && $this->surchargesArray[$fieldValue]==$vf){
						$earlybird = true;
						return $earlybird;
					}
					else if (array_key_exists($fieldValue, $this->pricesArray) && $this->pricesArray[$fieldValue]==$vf){
						$earlybird = true;
						return $earlybird;
					}
				}
				if ($earlybird===false && $created>=$validFrom && $created<=$validTo){
					$earlybird = true;
					return $earlybird;
				}
			}
		}
		return $earlybird;
	}

	public static function datetotime($date, $format = 'Y-M-D')
	{
		$format = strtoupper(str_replace("%", "", $format));
		if ($format == 'Y-M-D')
			list($year, $month, $day) = explode('-', $date);
		if ($format == 'Y/M/D')
			list($year, $month, $day) = explode('/', $date);
		if ($format == 'Y.M.D')
			list($year, $month, $day) = explode('.', $date);

		if ($format == 'D-M-Y')
			list($day, $month, $year) = explode('-', $date);
		if ($format == 'D/M/Y')
			list($day, $month, $year) = explode('/', $date);
		if ($format == 'D.M.Y')
			list($day, $month, $year) = explode('.', $date);

		if ($format == 'M-D-Y')
			list($month, $day, $year) = explode('-', $date);
		if ($format == 'M/D/Y')
			list($month, $day, $year) = explode('/', $date);
		if ($format == 'M.D.Y')
			list($month, $day, $year) = explode('.', $date);


		$datetime = new DateTime ("$year-$month-$day");
		return $datetime;
	}


	function toXML($field)
	{
		$result = array();
		$result[] = "<field ";
		foreach (get_object_vars($field) as $k => $v)
		{
			if ($k=="options" || $k=="html"  || $k=="defaultvalue" || $k=="name") continue;
			if ($k=="field_id") {
				$k="name";
				$v = "field".$v;
			}
			 if ($k == "params")
			{
				   if (is_string($field->params))
				   {
					   $field->params = @json_decode($field->params);
				   }
				   if (is_object($field->params))
				   {
					   foreach (get_object_vars($field->params) as $label=>$value)
					   {
						   $result[] = $label . '="' . addslashes($value) . '" ';
					   }
				   }
				   continue;
			   }

			$result[] = $k . '="' . addslashes(htmlspecialchars($v)) . '" ';
		}
		$result[] = " >";
		if (is_string($field->options))
		{
			$field->options = @json_decode($field->options);
		}
		if (is_object($field->options))
		{
			for ($i = 0; $i < count($field->options->label); $i++)
			{
				$validoption = false;

				if ($field->options->label[$i] != "") {
					$validoption = true;
				}
				if (!$validoption && isset($field->options->validto) && $field->options->validto[$i] != "") {
					$validoption = true;
				}
				if (!$validoption && isset($field->options->validfrom) && $field->options->validfrom[$i] != "") {
					$validoption = true;
				}
				if (!$validoption){
					continue;
				}
				$result[] = "<option ";
				$result[] = ' value="' . addslashes($field->options->value[$i]) . '"';
				if (isset($field->options->price))
				{
					$result[] = ' price="' . addslashes($field->options->price[$i]) . '"';
				}
				if (isset($field->options->type))
				{
					$result[] = ' type="' . addslashes($field->options->type[$i]) . '"';
				}
				if (isset($field->options->surcharge))
				{
					$result[] = ' surcharge="' . addslashes($field->options->surcharge[$i]) . '"';
				}
				if (isset($field->options->capacity))
				{
					$result[] = ' capacity="' . addslashes($field->options->capacity[$i]) . '"';
				}
				if (isset($field->options->waiting))
				{
					$result[] = ' waiting="' . addslashes($field->options->waiting[$i]) . '"';
				}
				if (isset($field->options->validfrom))
				{
					$result[] = ' validfrom="' . addslashes($field->options->validfrom[$i]) . '"';
				}
				if (isset($field->options->validto))
				{
					$result[] = ' validto="' . addslashes($field->options->validto[$i]) . '"';
				}
				$result[] = ">".addslashes(htmlspecialchars($field->options->label[$i]))."</option>";
				//$result[] = " ><![CDATA[".addslashes($field->options->label[$i])."]]></option>";
			}
		}
		$result[] = " </field>";
		$xml = implode(" ", $result);
		return $xml;

	}



}

