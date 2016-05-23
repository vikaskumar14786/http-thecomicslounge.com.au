<?php

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('calendar');

class JFormFieldJevcfcalendar extends JFormFieldCalendar
{

	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var $_name = 'Jevcfcalendar';

	function OLDgetInput()
	{
		JHTML::_('behavior.calendar'); //load the calendar behavior

		$format = ( $this->attribute('format') ? $this->attribute('format') : '%Y-%m-%d' );
		$class = $this->element['class'] ? $this->element['class'] : 'inputbox';

		// Joomla can only take date values in the format "%Y-%m-%d" despite taking the $format argument here so we do a workaround
		$this->newvalue = $this->convertTime("%Y-%m-%d", $format, $this->value);
		$HTML =  JHTML::_('calendar', $this->newvalue, $this->name, $this->id, $format, array('class' => $class));
		// This replaces the value in the input box and thankfully Joomla has told the calendar script to use the format provided even though Joomla itself can't cope with it.
		return str_replace($this->newvalue,$this->value,$HTML);
	}

	protected
			function getInput()
	{
		if(method_exists("JEVHelper","getMinYear"))
		{
			$minyear = JEVHelper::getMinYear();
			$maxyear = JEVHelper::getMaxYear();
		}
		else
		{
			$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
			$minyear = $params->get("com_earliestyear", 1970);
			$maxyear = $params->get("com_latestyear", 2150);
		}
		$inputdateformat  = ( $this->attribute('format') ? $this->attribute('format') : '%Y-%m-%d' );

		if ($this->value=="NOW"){
			list($cyear, $cmonth, $cday) = JEVHelper::getYMD();
                        $now = new JevDate("$cyear-$cmonth-$cday 08:00:00");
			$this->value = $now->toFormat($inputdateformat);
		}
		else if ($this->value=="--"){
			$this->value = "";
		}

		$this->newvalue = $this->value ? $this->convertTime( "%Y-%m-%d", $inputdateformat, $this->value) : "";

		static $firsttime;
		if (!defined($firsttime)){
			$document = JFactory::getDocument();
			$js = "\n cfEventEditDateFormat='$inputdateformat';Date.defineParser(cfEventEditDateFormat.replace('d','%d').replace('m','%m').replace('Y','%Y'));";
			$document->addScriptDeclaration($js);
		}
		$inputdateformat = str_replace("%","",$inputdateformat );
		ob_start();
		JEVHelper::loadCalendar($this->name, $this->id, $this->newvalue, $minyear, $maxyear, 'var elem = $("'.$this->name.'");'.$this->element['onhidestart'], "elem = $('".$this->name."');".$this->element['onchange'], $inputdateformat);
		?>
		<input type="hidden"  name="<?php echo str_replace("]","2]",$this->name);?>" id="<?php echo $this->id;?>2" value="" />
		<?php
		$html = ob_get_clean();
		return $html;

	}

	function fetchRequiredScript($name, &$node, $control_name)
	{
		return "JevrRequiredFields.fields.push({'name':'" . $control_name . $name . "', 'default' :'" . $this->attribute('default') . "' ,'reqmsg':'" . trim(JText::_($this->attribute('requiredmessage'), true)) . "'}); ";

	}

	public function attribute($attr)
	{
		$val = $this->element->attributes()->$attr;
		$val = !is_null($val) ? (string) $val : null;
		return $val;

	}

	/**
	 * Magic setter; allows us to set protected values
	 * @param string $name
	 * @return nothing
	 */
	public function setValue($value)
	{
		$this->value = $value;

	}

	// thanks to http://php.net/manual/en/function.strftime.php#90966
	private function convertTime($dformat, $sformat, $ts)
	{
		if (function_exists("strptime")){
			$datetime = strptime($ts, $sformat);
			$time = mktime(
			intval($datetime["tm_hour"]),
			intval($datetime["tm_min"]),
			intval($datetime["tm_sec"]),
			intval($datetime["tm_mon"]) + 1,
			intval($datetime["tm_mday"]),
			intval($datetime["tm_year"]) + 1900
			);
			return strftime($dformat, $time);
		}
		else {
			$masks = array(
			  '%d' => '(?P<d>[0-9]{2})',
			  '%m' => '(?P<m>[0-9]{2})',
			  '%Y' => '(?P<Y>[0-9]{4})',
			  '%H' => '(?P<H>[0-9]{2})',
			  '%M' => '(?P<M>[0-9]{2})',
			  '%S' => '(?P<S>[0-9]{2})',
			);

			$rexep = "#".strtr(preg_quote($sformat), $masks)."#";
			if(!preg_match($rexep, $ts, $out))
			  return false;

			$datetime = array(
			  "tm_sec"  => (int) $out['S'],
			  "tm_min"  => (int) $out['M'],
			  "tm_hour" => (int) $out['H'],
			  "tm_mday" => (int) $out['d'],
			  "tm_mon"  => $out['m']?$out['m']-1:0,
			  "tm_year" => $out['Y'] > 1900 ? $out['Y'] - 1900 : 0,
			);
			$time = mktime(
			intval($datetime["tm_hour"]),
			intval($datetime["tm_min"]),
			intval($datetime["tm_sec"]),
			intval($datetime["tm_mon"]) + 1,
			intval($datetime["tm_mday"]),
			intval($datetime["tm_year"]) + 1900
			);
			return strftime($dformat, $time);
		}
	}

	public function convertValue($value, $node)
	{
		if ($value =="--"){
			$value ="";
		}
		return $value;
	}

}