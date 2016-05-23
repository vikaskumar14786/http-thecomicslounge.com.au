<?php 

/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: overview.php 1676 2010-01-20 02:50:34Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C)  2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */
defined('_JEXEC') or die('Restricted Access');

ini_set("display_errors",0);
set_time_limit(180);
$filename = "rsvp_export".$this->repeat->rp_id().".csv";

$rsvpdata = $this->rsvpdata;

$data = array();

$headerrow = array(JText::_('RSVP_ATTENDEE_NUMBER'), JText::_('RSVP_ATTENDEE'), JText::_('RSVP_ATTENDEE_NAME'), JText::_('RSVP_ATTENDEE_EMAIL'), JText::_('RSVP_ATTENDEE_USERNAME'), JText::_('RSVP_CONFIRMED'),JText::_('JEV_WAITING'),JText::_('RSVP_ATTEND_STATUS'),JText::_('RSVP_ATTENDED'),JText::_('RSVP_ATTENDANCENOTES'));
$colcount = 5;

$template = $rsvpdata->template;
if ($template!=""){
	$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);
}
if (count($this->rows)>0){

	$attendee = $this->rows[0];
	
	// New parameterised fields
	if ($template!=""){
		//$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

		if (is_int($xmlfile) || file_exists($xmlfile)) {
			if (isset($this->xmlparams[$xmlfile])){
				$params = clone ($this->xmlparams[$xmlfile]);
			}
			else {
				$params = new JevRsvpParameter("",$xmlfile,$rsvpdata, $attendee);
				$this->xmlparams[$xmlfile] =$params ;
			}

			//$params = new JevRsvpParameter("",$xmlfile,$rsvpdata, $attendee);
			$params  = $params->renderToBasicArray();
			foreach ($params as $param) {
				if ($param['label']!="")	{
					$headerrow [] = JText::_($param['label']);
				}
			}
		}
	}
	$headerrow [] = JText::_('JEV_REGISTRATION_TIME');
	$headerrow [] =  JText::_('JEV_MODIFICATION_TIME');
	
	// Including Transactions
	/*
	$headerrow [] =  JText::_('RSVP_TRANSACTION_NUMBER');
	$headerrow [] =  JText::_('RSVP_TRANSACTION_GATEWAY');
	$headerrow [] =  JText::_('RSVP_TRANSACTION_CURRENCY');
	$headerrow [] =  JText::_('RSVP_TRANSACTION_AMOUNT');
	$headerrow [] =  JText::_('RSVP_TRANSACTION_DATE');
	$headerrow [] =  JText::_('RSVP_TRANSACTION_PAYMENTSTATE');
	*/
	$data[]=$headerrow;

	$attendstate = array(	JText::_('RSVP_NOT_ATTENDING'), JText::_('RSVP_ATTENDING'), JText::_('RSVP_MAYBE_ATTENDING'), JText::_('RSVP_PENDING_APPROVAL'), JText::_('RSVP_OUTSTANDING_BALANCE'));

	$n=count( $this->rows);
	for( $i=0; $i < $n ; $i++ ) {
		$attendee = $this->rows[$i];
		
		$guestcount = (isset($attendee->guestcount) && $attendee->guestcount>1) ?   $attendee->guestcount : 1;
		for($guest=0; $guest<$guestcount; $guest ++){
			$datarow = array();
			$datarow[] = $attendee->atdee_id;
			$datarow[] = $attendee->attendee;
			$datarow[] = $attendee->attendeename;
			$datarow[] = $attendee->attendeemail;
			$datarow[] = $attendee->attendeeusername ;
			$datarow[] = $attendee->confirmed;
			$datarow[] = $attendee->waiting;
			$datarow[] = $attendstate[$attendee->attendstate];
			$datarow[] = $attendee->didattend;
			$datarow[] = $attendee->notes;

			// New parameterised fields
			if ($rsvpdata->template!=""){
				//$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);

				if ((is_int($xmlfile) || file_exists($xmlfile)) && ($attendee->lockedtemplate == 0 || $attendee->lockedtemplate == $xmlfile)) {
					// transfer attendee specific information into the event row
					$eventrow = clone $this->repeat;
					if (isset($this->xmlparams[$xmlfile]))
					{
						$params = clone ($this->xmlparams[$xmlfile]);
					}
					else
					{
						$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
						$this->xmlparams[$xmlfile] = $params;
					}

					foreach (get_object_vars($attendee) as $key => $val)
					{
						$eventrow->$key = $val;
					}
					
					if (isset($attendee->params)){
						// building from scratch each time is slow! so use a cloned object!
						//$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $eventrow);
						$params->loadData($attendee->params, $rsvpdata, $eventrow);
						$feesAndBalances = $params->outstandingBalance($attendee);
					}
					else {
						//$params = new JevRsvpParameter("",$xmlfile,$rsvpdata, $eventrow);
						$params = clone ($this->xmlparams[$xmlfile]);
						$params->load($rsvpdata, $eventrow);
						$feesAndBalances =  false;
					}

					$params  = $params->renderToBasicArray('xmlfile', $attendee);
					foreach ($params as $param) {
						if ($param['label']!="")  {
							if ($param["peruser"]<=0){
								$datarow[] = $param['value'];
							}
							else if ($param["peruser"]==1){
								if (!is_array($param['value']) && $guest ==0){
									$datarow[] = $param['value'];
								}
								else if (array_key_exists($guest,$param['value'])){
									$datarow[] = $param['value'][$guest];
								}							
								else {
									$datarow[] = "";
								}
							}
							else if ($param["peruser"]==2){
								if (array_key_exists($guest,$param['value'])){
									$datarow[] = $param['value'][$guest];
								}							
								else {
									$datarow[] = "";
								}
							}
						}
					}
					unset($params);
					unset($eventrow);
				}
				else if ($attendee->lockedtemplate > 0)
				{
					$xmlfile = $attendee->lockedtemplate;

					// transfer attendee specific information into the event row
					$eventrow = clone $this->repeat;
					if (isset($this->xmlparams[$xmlfile]))
					{
						$params = clone ($this->xmlparams[$xmlfile]);
					}
					else
					{
						$params = new JevRsvpParameter("", $xmlfile, $rsvpdata, $eventrow);
						$this->xmlparams[$xmlfile] = $params;
					}
					foreach (get_object_vars($attendee) as $key => $val)
					{
						$eventrow->$key = $val;
					}

					if (isset($attendee->params)){
						// building from scratch each time is slow! so use a cloned object!
						//$params = new JevRsvpParameter($attendee->params, $xmlfile, $rsvpdata, $eventrow);
						$params->loadData($attendee->params, $rsvpdata, $eventrow);
						$feesAndBalances = $params->outstandingBalance($attendee);
					}
					else {
						//$params = new JevRsvpParameter("",$xmlfile,$rsvpdata, $eventrow);
						$params = clone ($this->xmlparams[$xmlfile]);
						$params->load($rsvpdata, $eventrow);
						$feesAndBalances =  false;
					}

					$params  = $params->renderToBasicArray('xmlfile', $attendee);
					foreach ($params as $param) {
						if ($param['label']!="")  {
							if ($param["peruser"]<=0){
								$datarow[] = $param['value'];
							}
							else if ($param["peruser"]==1){
								if (!is_array($param['value']) && $guest ==0){
									$datarow[] = $param['value'];
								}
								else if (array_key_exists($guest,$param['value'])){
									$datarow[] = $param['value'][$guest];
								}
								else {
									$datarow[] = "";
								}
							}
							else if ($param["peruser"]==2){
								if (array_key_exists($guest,$param['value'])){
									$datarow[] = $param['value'][$guest];
								}
								else {
									$datarow[] = "";
								}
							}
						}
					}
					unset($params);
					unset($eventrow);
				}
			}
			$datarow[] = $attendee->created;
			$datarow[] = $attendee->modified;

			// Transaction Data
			/*
			if ( $feesAndBalances && isset($feesAndBalances["transactions"]) && count($feesAndBalances["transactions"])>0){
				$keepdata = $datarow;
				for ($t=0; $t<count($feesAndBalances["transactions"]); $t++){
					$datarow = $keepdata;
					$transaction = $feesAndBalances["transactions"][$t];
					$datarow[] = $transaction->transaction_id;
					$datarow[] = $transaction->gateway;
					$datarow[] = $transaction->currency;
					$datarow[] = $transaction->amount;
					$datarow[] = $transaction->transaction_date;
					$datarow[] = $transaction->paymentstate;
					// Add the interim values here before going to the last one in the outer look
					if ($t<count($feesAndBalances["transactions"])-1){
						$data[] = $datarow;
					}
				}
			}
			else {
				for ($ti=0;$ti<6;$ti++) {$datarow[] ="";}
			}
			 */
			$data[] = $datarow;
		}
		unset($attendee);
		unset($this->rows[$i]);
//echo memory_get_peak_usage(true);
	}

	$data = exportAsCSV($data);

	// force UTF-8 BOM headers in file - see http://stackoverflow.com/questions/5368150/php-header-excel-and-utf-8
	$data = pack('CCC',0xef,0xbb,0xbf) . $data;

	// Finally, generate a file
	$size = strlen($data);

	@ob_end_clean();
	@ini_set("zlib.output_compression", "Off");
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: private");
	header("Content-Type: text/csv; charset=utf-8");
	header("Content-Disposition: attachment; filename=$filename");
	header("Accept-Ranges: bytes");
	header("Content-Length: $size");
	echo $data;
	exit();
}

function outputCSV($data) {
	ob_start();
    $outstream = fopen("php://output", 'w');

    function __outputCSV(&$vals, $key, $filehandler) {
		// TODO THIS IS NOT CORRECT!!
	$temp = array();
	foreach ($vals as $val){
		if (is_array($val)){
			$val = implode(", ",$val);
		}
		$temp[] = $val;
	}
        fputcsv($filehandler, $temp, ',', '"');
    }
    array_walk($data, '__outputCSV', $outstream);

    fclose($outstream);
	return ob_get_clean();
}

function exportAsCSV( &$exportData ) {
	return  outputCSV($exportData);
	/*
	// default csv options
	$titles = true;
	$fieldTerminator = ",";
	$fieldEnclosed = "\"";
	$fieldEscaped = "\\";
	$lineTerminator = "\n";

	$data = '';
	foreach ( $exportData as $record ) {

		// parse each field
		foreach ( $record as $field ) {
			$data .= $fieldEnclosed.str_replace($fieldEnclosed, $fieldEscaped.$fieldEnclosed, $field).$fieldEnclosed;
			$data .= $fieldTerminator;
		} // foreach row

		// Remove the dangling fieldTerminator, and add a lineTerminator
		$data = substr($data,0,strlen($data) - strlen($fieldTerminator)) . $lineTerminator;

	} // foreach record

	return $data;
	 * */

}
