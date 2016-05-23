<?php
/**
 * @copyright	Copyright (C) 2011-2015 GWE Systems Ltd. All rights reserved.
 */
// Set flag that this is a parent file
define('_JEXEC', 1);

$x = realpath(dirname(__FILE__) . "/../../../../../");
if (!file_exists($x . "/plugins") && isset($_SERVER["SCRIPT_FILENAME"]))
{
	$x = dirname(dirname(dirname(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"]))))));
}

define('JPATH_BASE', $x);

require_once (JPATH_BASE . '/includes/defines.php');
require_once (JPATH_BASE . '/includes/framework.php');

ini_set("display_errors", 1);

$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

$db = JFactory::getDBO();
JHTML::_('behavior.mootools');
$user = JFactory::getUser();

$uri = JURI::getInstance();
$root = str_replace("/administrator/components/com_rsvppro/fields/SeatSelector/","",$uri->base());

$activeCol = 1;
$formfield = JRequest::getCmd("field");

function displaySeat($id, $activeCol)
{
	static $seats, $concessions, $special, $specialseats, $myseats;
	$mainframe = JFactory::getApplication();
	if (!isset($myseats))
	{

		// special  seat
		$seats = array();
		$concessions = array();
		// pre-reserved seats
		$special = array("A1","A2","A3","A4","A5","A6","A7","A8","A9","A10", "A11","A12","A13","A14","A15","A16","A17","A18","A19","A20",  "A21", "A22", 'G24', 'H22', 'H23');

		// disabled access seat
		$specialseats = array('D1', 'D2', 'D19', 'D20');
		$myseats = array();

		$limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
		$limitstart = $mainframe->getUserStateFromRequest("com_rsvppro" . '.limitstart', 'limitstart', 0, 'int');

		$rp_id = JRequest::getInt("rpid", 0);
		$at_id = JRequest::getInt("at_id", 0);
		$atdee = JRequest::getInt("atdee",0);
		$formfield = str_replace("xmlfile_","",JRequest::getCmd("field"));

		$db = JFactory::getDbo();
		$db->setQuery("select a.* from #__jev_attendance as a where a.id=".intval($at_id));
		$rsvpdata = $db->loadObject();

		require_once(JPATH_SITE."/components/com_jevents/jevents.defines.php");
		$datamodel = new JEventsDataModel();
		$repeat  = $datamodel->queryModel->listEventsById($rp_id);

		if (!$repeat){
			return;
		}

		// seats paid or unpaid for.
		$sql = "SELECT u.name, u.username, a.* FROM #__jev_attendees as a LEFT JOIN #__users as u on u.id=a.user_id WHERE (a.attendstate=1 OR a.attendstate=4) AND a.at_id=" . $rsvpdata->id ;
		if (!$rsvpdata->allrepeats)
		{
			$sql .= " and a.rp_id=" . $row->rp_id();
		}
		$sql .= " ORDER BY a.created asc";

		$db->setQuery($sql);
		$attendees = $db->loadObjectList();

		static $rsvpParameters;
		if ($rsvpdata->template != "")
		{
			$xmlfile = JevTemplateHelper::getTemplate($rsvpdata);
			if (is_int($xmlfile) || file_exists($xmlfile) )
			{
				if (!isset($rsvpParameters))
				{
					$rsvpParameters = new JevRsvpParameter("", $xmlfile, $rsvpdata, $repeat);
				}
			}
		}
		$fieldsets = $rsvpParameters->getFieldsets();
		$seatfield = $rsvpParameters->getField($formfield, "xmlfile");
		if (!$seatfield){
			break;
		}
		
		$seatmapping = $seatfield->getSeatMapping();

		foreach ($attendees as $attendee)
		{
			$fielddata =  json_decode($attendee->params);
			if (!isset($fielddata->$formfield)){
				continue;
			}

			foreach ($fielddata->$formfield as $field)
			{
				if (array_key_exists($field, $seatmapping)) {
					if ($attendee->id == $atdee){
						$myseats[] = $seatmapping[$field];
					}
					else {
						$seats[] = $seatmapping[$field];
					}
				}
			}
		}
	}

	$id .= $activeCol;
	$class = "available";
	$action = 'onclick="javascript:reserveSeat(this, false);return false;"';

	if ($id=="B11"){
		//echo "sx";
	}
	if (in_array($id, $specialseats))
	{
		$class = "disabled";
		$action = "href='#null' onclick='return false;'";
		$class .= " nolink";
	}
	if (in_array($id, $seats) || in_array($id, $concessions) || in_array($id, $special) || in_array($id, $myseats))
	{
		$class = "reserved";
		if (!in_array($id, $myseats))
		{
			$action = "href='#null' onclick='return false;'";
			$class .= " nolink";
		}
		else
		{
			$class .= " myseat";
		}
	}
	?>
	<li class="seat <?php echo $class; ?>" id="<?php echo $id; ?>"><a <?php echo $action; ?>  title="<?php echo $id; ?>" id="<?php echo $id; ?>a" ><?php echo $id; ?></a></li>
	<?php

}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<title>Seat Planner</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<!-- gwe //-->
		<script type="text/javascript" src="<?php echo $root; ?>/media/system/js/mootools-core.js"></script>
		<style>
			/*** THEATRE SEAT PLANNER ****/
			.closebutton{background-color: #136DA2 !important;
						 border: medium none !important;
						 color: white !important;
						 font-family: arial !important;
						 float:right;
						 font-size: 14px !important;
						 font-weight: bold !important;
						 margin-bottom: 10px;
						 text-decoration:none;
						 padding: 5px 4px !important;
						 margin-top:12px;
			}
			.seatplanner a{
				color:#133478;
				cursor:pointer;
			}
			.seatplanner .seat a{
				text-align:center;
				color:white!important;
				text-decoration:none;
				font-size:9px;
				height:18px;
				padding:8px 0 0;
				display:block;
			}
			.seatplanner  li.nolink a {
				cursor:default!important;
			}
			.seatplanner .seat{
				width:25px;
				height:26px;
				list-style:none;
				margin:0 0 5px 0;
				padding:0px;
				text-align:center;
				color:white!important;
				font-size:9px;
			}
			.seatplanner .available{
				background: url("/images/theatre/seat_available.jpg") no-repeat 0 0;
			}
            .seatplanner .available a:hover, 
            .seatplanner .available a:hover{
				color:#133478!important;
				cursor:pointer;
			}
			.seatplanner .available:hover, .seatplanner .available a:hover{
				background: url("/images/theatre/seat_reserve.jpg") no-repeat 0 0;
			}
			.seatplanner .reserve{
				background: url("/images/theatre/seat_reserve.jpg") no-repeat 0 0;
			}
			.seatplanner .reserve a, .seatplanner .reserve:hover a, .seatplanner .reserve a:hover{
				color:#133478!important;
				cursor:pointer;
			}
			.seatplanner .reserve_concession{
				background: url("/images/theatre/seat_reserve_concession.jpg") no-repeat 0 0;
			}
			.seatplanner .reserve_concession a, .seatplanner .reserve_concession:hover a,  .seatplanner .reserve_concession a:hover{
				color:white;
				cursor:pointer;
			}
			.seatplanner .reserved{
				background: url("/images/theatre/seat_reserved.jpg") no-repeat 0 0;
			}
            .seatplanner .reserved.myseat{
				background: url("/images/theatre/seat_reserve.jpg") no-repeat 0 0;
			}
            .seatplanner .reserved.myseat a{
                color:#133478 !important
            }
			.seatplanner .reserved:hover a, .seatplanner .reserved a:hover{
				color:white;
				cursor:pointer;
			}
			.seatplanner .disabled{
				background: url("/images/theatre/disabled_booking.jpg") no-repeat 0 0;
			}
			.seatplanner .column{
				float:left;
				margin:0;
				padding:0;
			}
			#col1{
				margin-top:0px;
			}
			#D1,#D2,#D3,#D4,#D5,#D6,#D7,#D8,#D9,#D10,#D11,#D12,#D13,#D14,#D15,#D16,#D17,#D18,#D19,#D20,#D21,#D22,#D23,#D24{
				height:55px;
			}
			#G24{
				margin-top:104px;
			}
			#J7,#J8,#J21,#J22{
				margin-bottom:191px;
			}
			#H12,#G12,#F12,#E12,#D12{
				margin-left:2px;
			}
			#J15{
				margin-left:2px;
			}
			#H22,#H23,#H24{
				margin-top:36px;
			}
			#J1{
				margin-top:49px;
			}
			#J2{
				margin-top:35px;
			}
			#J3{
				margin-top:24px;
			}
			#J4{
				margin-top:17px;
			}
			#J5{
				margin-top:14px;
			}
			#J6{
				margin-top:12px;
			}
			#J7{
				margin-top:9px;
			}
			#J8{
				margin-top:6px;
			}
			#J9{
				margin-top:5px;
			}
			#J10{
				margin-top:4px;
			}
			#J11{
				margin-top:3px;
			}
			#J12{
				margin-top:2px;
			}
			#J13{
				margin-top:1px;
			}
			#J14{
				margin-top:0px;
			}
			#J15{
				margin-top:0px;
			}
			#J16{
				margin-top:1px;
			}
			#J17{
				margin-top:3px;
			}
			#J18{
				margin-top:4px;
			}
			#J19{
				margin-top:5px;
			}
			#J20{
				margin-top:7px;
			}
			#J21{
				margin-top:8px;
			}
			#J22{
				margin-top:9px;
			}
			#J23{
				margin-top:12px;
			}
			#J24{
				margin-top:17px;
			}
			#J25{
				margin-top:20px;
			}
			#J26{
				margin-top:21px;
                display:none;
			}
			#H22{
				margin-top:57px;
			}
			#H23{
				margin-top:65px;
			}
			#H24{
				margin-top:68px;
			}

			#legend li{
				color:#136DA3!important;
				font-weight:bold;
			}

			/** END OF SEAT PLANNER */

		</style>
		<script type="text/javascript" >
			var specialseats = new Array('D1', 'D2', 'D19', 'D20');

			var selectedFields = {
				fields: new Array(),
				seatcount: 0,
				seatnumber: new Array(),
				setupSeatNumbers: function() {
					// reset the arrays !
					selectedFields.seatnumber = new Array();

					var registrations = $(window.parent.document.body).getElements('select.rsvp_<?php echo $formfield;?>');
					selectedFields.seattypes = $(window.parent.document.body).getElements('select.jevrseattype');
					if (selectedFields.seattypes.length !=registrations.length){
						alert("mismatch in seattypes.length and registrations.length = please report this problem "+  selectedFields.seattypes.length + " vs "+registrations.length);
						return;
					}
					for (var r=0;r<registrations.length;r++){
						var registration = registrations[r];
						if (registration.hasClass("paramtmpl")) {
							return;
						}
						// if selected no or maybe then clear the reservations
						if (($(window.parent.document.body).getElement('#jevattend_no') && $(window.parent.document.body).getElement('#jevattend_no').checked)
						|| ($(window.parent.document.body).getElement('#jevattend_maybe') && $(window.parent.document.body).getElement('#jevattend_maybe').checked)){
							//this.clearReserved();
						}

						var seattype = selectedFields.seattypes[r];
						seatcount= registration.id.replace("<?php echo $formfield;?>_","");
						fieldname = "<?php echo $formfield;?>";
						selectedFields.seatnumber.push({'fieldname': fieldname,'seatcount':seatcount,
							'value':$(registration).value , 'text':$(registration).options[$(registration).selectedIndex].text, 'reg': $(registration),
							'option':$(registration).options[$(registration).selectedIndex], 'seattype':selectedFields.seattypes[r]});
						
					};

				},
				click: function(seat, initial) {
					var matched = false;
					// if called from DOM we need the ID from the parent element
					if (seat.getParent){
						 seat = seat.getParent().id;
					}
					// MSIE 8 - ARGH!
					else if ($(seat) && $(seat).nodeName != "LI" && $(seat).getParent){
						seat = $(seat).getParent().id;
					}

					selectedFields.fields.each(function(item, i) {
						if (item.seat != seat)
							return;
						selectedFields.clickitem(item, seat, initial);
						matched = true;
					});
					if (!matched) {
						var item = {'seat': seat, 'clicks': 0};
						selectedFields.fields.push(item);
						//selectedFields.seatnumber.push(item);
						selectedFields.clickitem(item, seat, initial);
					}
					;
				},
				clearReserved: function(seat, item) {
					if (!$(seat)){
						alert(seat+ " can't be made unavailable");return;
					}
					$(seat).removeClass('myseat');
					$(seat).addClass('available');
					$(seat).removeClass('reserve_concession');
					$(seat).removeClass('reserved');
					selectedFields.seatcount--;
				},
				setReserved: function(seat, item) {
					if (!$(seat)){
						alert(seat+ " can't be made unavailable");return;
					}
					$(seat).addClass('myseat');
					$(seat).removeClass('available');
					$(seat).removeClass('reserve_concession');
					$(seat).addClass('reserved');
					$(seat).removeClass('disabled');
					selectedFields.seatcount++;
				},
				setConcession: function(seat, item) {
					$(seat).addClass('myseat');
					$(seat).removeClass('available');
					$(seat).addClass('reserve_concession');
					$(seat).removeClass('reserved');
					$(seat).removeClass('disabled');
				},
				clickitem: function(item, seat, initial) {
					item.clicks++;

					if (item.clicks == 1) {
						selectedFields.setReserved(seat, item);
						//	selectedFields.seatnumber[selectedFields.seatcount].each(function(el){el.value  = seat});
						//selectedFields.concessionseatnumber[selectedFields.seatcount].each(function(el){el.value  = ''});
					}
					else if (item.clicks == 2) {
						selectedFields.setConcession(seat, item);
					}
					else {
						selectedFields.clearReserved(seat, item);
						item.clicks = 0;
					}
					if (selectedFields.seatnumber.length < selectedFields.seatcount && selectedFields.seatnumber.length > 0) {
						//window.parent.addGuest(0);
						this.addGuest(0);
					}
					else if (selectedFields.seatnumber.length > selectedFields.seatcount && selectedFields.seatnumber.length > 1) {
						// in initial setup we don't clear the tabs and their values'
						if (!initial) {
							var registrations = $(window.parent.document.body).getElements('select.rsvp_<?php echo $formfield;?>');
							for (var r=0;r<registrations.length;r++){
								var registration = registrations[r];
								if (registration.hasClass("paramtmpl")) {
									continue;
								}
								for (var sn=0;sn<selectedFields.seatnumber.length;sn++) {
									if (selectedFields.seatnumber[sn].value==registration.value){
										if (selectedFields.seatnumber[sn].seatcount>0){
											window.parent.regTabs.activate(window.parent.regTabs.titles[seatcount]);
											this.removeGuest(0);
											item.removed = true;
										}
									}
								}
							}
							//this.removeGuest(0);
						}
					}
					selectedFields.setupSeatNumbers();
					if (initial) {
						// in initial setup we don't clear the tabs and their values'
						return;
					}
					// now propagate the values
					var ticketcount = 0;

					selectedFields.fields.each(function(item, i) {
						if (item.clicks == 0 || item.clicks >2) {
							if (item.removed){
								return;
							}
							selectedFields.seatnumber[ticketcount].reg.selectedIndex=0;
							selectedFields.seattypes[ticketcount].selectedIndex=1;
							ticketcount++;
						}
						else if (item.clicks == 1) {
							selectedFields.seatnumber[ticketcount].reg.selectedIndex=0;
							$$(selectedFields.seatnumber[ticketcount].reg.options).each(function(opt, i) {

								if (item.seat.getParent){
									// already reserved
									if (opt.text == item.seat.getParent().id){
										opt.selected=true;
									};
								}
								else  {
									// clicked
									if (opt.text == item.seat){
										opt.selected=true;
									};
								}
							});
							selectedFields.seattypes[ticketcount].selectedIndex=1;
							ticketcount++;
						}
						else if (item.clicks == 2) {
							selectedFields.seatnumber[ticketcount].reg.selectedIndex=0;
							$$(selectedFields.seatnumber[ticketcount].reg.options).each(function(opt, i) {
								if (item.seat.getParent){
									// already reserved
									if (opt.text == item.seat.getParent().id){
										opt.selected=true;
									};
								}
								else  {
									// clicked
									if (opt.text == item.seat){
										opt.selected=true;
									};
								}
							});
							selectedFields.seattypes[ticketcount].selectedIndex=2;
							ticketcount++;
						}
						else {
						}
					});

					window.parent.JevrFees.calculate(window.parent.document.updateattendance);
				},
				addGuest : function(limit){
					// update the guest count
					// Do not change this to remove +1 without changing call to addStates
					window.parent.$('guestcount').value = parseInt(window.parent.$('guestcount').value)+1;
					window.parent.$('lastguest').value = parseInt(window.parent.$('lastguest').value)+1;
					var title = window.parent.$("jevnexttabtitle").value;
					title = title.replace('xxx',window.parent.$('lastguest').value);
					window.parent.regTabs.addTab(title,title,window.parent.$('lastguest').value);
					// Watch this count is 1 less than the label on the tab!
					// call before the fees since the bisibility affect the calculation
					window.parent.JevrConditionFieldState.addStates(window.parent.$('lastguest').value - 1);
					// recalculate the fees!
					window.parent.JevrFees.calculate(window.parent.document.updateattendance);
					if (limit>0 && window.parent.$('guestcount').value>=limit){
						if (window.parent.$('addguest')) window.parent.$('addguest').style.display="none";
					}
				},
				removeGuest:function (limit){
					// update the guest count
					window.parent.$('guestcount').value = parseInt(window.parent.$('guestcount').value)-1;
					window.parent.regTabs.removeActiveTab();
					// recalculate the fees!
					window.parent.JevrFees.calculate(window.parent.document.updateattendance);
					if (limit>0 && window.parent.$('guestcount').value<limit){
						if (window.parent.$('addguest')) window.parent.$('addguest').style.display="block";
					}
				}
			};
			function reserveSeat(seat, initial) {
				selectedFields.click(seat, initial);
			}
			window.addEvent("load",function() {
				selectedFields.setupSeatNumbers();

				for (var s=0;s<selectedFields.seatnumber.length;s++){
					var seatitem = selectedFields.seatnumber[s];
					if (seatitem.value!="" && seatitem.value !="0" && seatitem.value !=undefined ){
						reserveSeat(seatitem.text, true);
						// concession type field
						if (seatitem.seattype.selectedIndex==2){
							reserveSeat(seatitem.text, true);
						}
					}
				}
			});
		</script>
	</head>
	<body>
		<!--
		<?php
//var_dump($data);
//echo "xxxxx<br/>";
//$db->setQuery("select a.* from #__aje_registrations as a where a.session_id=17");
//var_dump($db->loadObjectList());
//echo $datasql."\n\n";
		?>
  //-->
		<div class="seatplanner" style="margin-left: 17px; width: 710px; position: relative; height: 50px; overflow: auto;">
			<ul id="legend" style="list-style: none outside none; padding: 0pt; margin-left: 10px;">
				<li class="seat available" style="margin: 0px; float: left; width: 160px; color: black; font-size: 14px; padding-top: 6px;">Seat Available</li>
				<li style="margin: 0px; float: left; width: 170px; color: black; font-size: 14px; padding-top: 6px;" class="seat reserved">Seat Reserved</li>
				<li style="margin: 0px; float: left; width: 145px; color: black; font-size: 14px; padding-top: 6px;" class="seat reserve">My Seat</li>
				<li style="margin: 0px; float: left; width: 210px; color: black; font-size: 14px; padding-top: 6px;" class="seat reserve_concession">My Concession Seat</li>
			</ul>
		</div>
		<div class="seatplanner" style="margin-left: 17px; width: 710px; height: 332px; position: relative;">
			<ul id="col1" class="column">
				<?php
				$activeCol = 1;
				echo displaySeat("J", $activeCol);
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				?>
			</ul>
			<ul id="col2" class="column">
				<?php
				$activeCol = 2;
				echo displaySeat("J",  $activeCol);
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				?>
			</ul>
			<ul id="col3" class="column">
				<?php
				$activeCol = 3;
				echo displaySeat("J",  $activeCol);
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);

				$activeCol = 1;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col4" class="column">
				<?php
				$activeCol = 4;
				echo displaySeat("J",  $activeCol);
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);

				$activeCol = 2;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col5" class="column">
				<?php
				$activeCol = 5;
				echo displaySeat("J",  $activeCol);
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);

				$activeCol = 3;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col6" class="column">
				<?php
				$activeCol = 6;
				echo displaySeat("J",  $activeCol);
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);

				$activeCol = 4;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col7" class="column">
				<?php
				$activeCol = 7;
				echo displaySeat("J",  $activeCol);

				$activeCol = 5;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col8" class="column">
				<?php
				$activeCol = 8;
				echo displaySeat("J",  $activeCol);

				$activeCol = 6;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col9" class="column">
				<?php
				$activeCol = 9;
				echo displaySeat("J",  $activeCol);
				$activeCol = 7;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);

				$activeCol = 7;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>

			<ul id="col10" class="column">
				<?php
				$activeCol = 10;
				echo displaySeat("J",  $activeCol);

				$activeCol = 8;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);

				$activeCol = 8;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col11" class="column">
				<?php
				$activeCol = 11;
				echo displaySeat("J",  $activeCol);
				$activeCol = 9;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);

				$activeCol = 9;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col12" class="column">
				<?php
				$activeCol = 12;
				echo displaySeat("J",  $activeCol);
				$activeCol = 10;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);

				$activeCol = 10;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col13" class="column">
				<?php
				$activeCol = 13;
				echo displaySeat("J",  $activeCol);
				$activeCol = 11;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);

				$activeCol = 11;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col14" class="column">
				<?php
				$activeCol = 14;
				echo displaySeat("J",  $activeCol);
				$activeCol = 12;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				?>
			</ul>
			<ul id="col15" class="column">
				<?php
				$activeCol = 15;
				echo displaySeat("J",  $activeCol);
				$activeCol = 13;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				?>
			</ul>
			<ul id="col16" class="column">
				<?php
				$activeCol = 16;
				echo displaySeat("J",  $activeCol);
				$activeCol = 14;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				$activeCol = 12;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col17" class="column">
				<?php
				$activeCol = 17;
				echo displaySeat("J",  $activeCol);
				$activeCol = 15;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				$activeCol = 13;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col18" class="column">
				<?php
				$activeCol = 18;
				echo displaySeat("J",  $activeCol);
				$activeCol = 16;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				$activeCol = 14;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col19" class="column">
				<?php
				$activeCol = 19;
				echo displaySeat("J",  $activeCol);
				$activeCol = 17;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				$activeCol = 15;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col20" class="column">
				<?php
				$activeCol = 20;
				echo displaySeat("J",  $activeCol);
				$activeCol = 18;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				$activeCol = 16;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col21" class="column">
				<?php
				$activeCol = 21;
				echo displaySeat("J",  $activeCol);

				$activeCol = 17;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col22" class="column">
				<?php
				$activeCol = 22;
				echo displaySeat("J",  $activeCol);

				$activeCol = 18;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col23" class="column">
				<?php
				$activeCol = 23;
				echo displaySeat("J",  $activeCol);
				$activeCol = 19;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				$activeCol = 19;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col24" class="column">
				<?php
				$activeCol = 24;
				echo displaySeat("J",  $activeCol);
				$activeCol = 20;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				$activeCol = 20;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col25" class="column">
				<?php
				$activeCol = 25;
				echo displaySeat("J",  $activeCol);
				$activeCol = 21;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				$activeCol = 21;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col26" class="column">
				<?php
				$activeCol = 26;
				echo displaySeat("J",  $activeCol);
				$activeCol = 22;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				$activeCol = 22;
				echo displaySeat("C",  $activeCol);
				echo displaySeat("B",  $activeCol);
				echo displaySeat("A",  $activeCol);
				?>
			</ul>
			<ul id="col27" class="column">
				<?php
				$activeCol = 23;
				echo displaySeat("H",  $activeCol);
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				?>
			</ul>
			<ul id="col28" class="column">
				<?php
				$activeCol = 24;
				echo displaySeat("G",  $activeCol);
				echo displaySeat("F",  $activeCol);
				echo displaySeat("E",  $activeCol);
				echo displaySeat("D",  $activeCol);
				?>
			</ul>
		</div>
        <!-- Adam's bit -->
        <div class="seatplanner" style="margin-left: 17px; width: 710px; position: relative; height: 50px; overflow: auto;">
			<a href="javascript:window.parent.SqueezeBox.close();" class="closebutton" title="close">Continue to booking...</a>
			<ul id="legend" style="list-style: none outside none; padding: 0pt; margin-left: 10px;">
				<?php //They dont want the yellow seat -> li class="seat staffseat" style="margin: 0px; float: left; width: 171px; color: black; font-size: 14px; padding-top: 6px;">Staff Seat (No Fee)</li> ?>
				<li style="margin: 0px; float: left; width: 475px; color: black; font-size: 14px; padding-top: 6px;" class="seat disabled">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Disabled Seat (please call 0161 224 7201 Ext 244 to reserve)</li>
			</ul>
		</div>

	</body>
</html>
