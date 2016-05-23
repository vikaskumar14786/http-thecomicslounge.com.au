<?php
/**
 * JEvents Component for Joomla!
 *
 * @version     $Id: mod_jevents_slideshow.php 3309 2012-03-01 10:07:50Z geraintedwards $
 * @package     JEvents
 * @subpackage  Module Slideshow JEvents
 * @copyright   Copyright (C) 2006-2014 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * HTML View class for the module  frontend
 *
 * @static
 */
include_once(JPATH_SITE . "/modules/mod_jevents_latest/tmpl/default/latest.php");

class DefaultModSlideshowView extends DefaultModLatestView
{

	function displaySlideshowEvents()
	{

		$cfg = JEVConfig::getInstance();
		$compname = JEV_COM_COMPONENT;

		$dispatcher = JDispatcher::getInstance();
		$datenow = JEVHelper::getNow();

		$this->getLatestEventsData();


		if (isset($this->eventsByRelDay) && count($this->eventsByRelDay))
		{
			//$this->customFormatStr = '<div class="item active">${imageimg1}<div class="carousel-caption">${title}</div></div>';
			// only the first should be active!!
			$this->customFormatStr = $this->modparams->get('modlatest_CustFmtStr', '<div class="item ISACTIVE"><a href="${eventDetailLink}" target="_self">${imageimg1}</a><div class="carousel-caption">${title}<span class="jevcstart"><a href="${eventDetailLink}" target="_self">${startDate(%e %b %Y)}</a></span></div></div>');
			$this->processFormatString();

			JHtml::_('bootstrap.carousel', 'jevlatestcarousel', array('interval' => '500', 'pause' => 'hover'));
			JHTML::stylesheet("modules/mod_jevents_slideshow/css/mod_jevents_slideshow.css");
			//JFactory::getDocument()->addScript("//ajax.googleapis.com/ajax/libs/jquerymobile/1.4.2/jquery.mobile.min.js");

			$hasEventsWithImages = false;
			ob_start();
			?>
			<div class="jevcarousel">
				<div id="jevlatestcarousel" class="carousel slide jevlatest jevlatestcarousel" data-ride="carousel">
					<!-- Indicators -->
					<ol class="carousel-indicators">
						<?php
						$count = 0;

						foreach ($this->eventsByRelDay as $relDay => $daysEvents)
						{
							// get all of the events for this day
							foreach ($daysEvents as $dayEvent) {
								if (!isset($dayEvent->_imageimg1) || $dayEvent->_imageimg1==""){
									continue;
								}
								$hasEventsWithImages = true;
								?>
								<li data-target="#jevlatestcarousel" data-slide-to="<?php echo $count; ?>" <?php echo ($count == 0) ? 'class="active"' : ''; ?>></li>
								<?php
								$count++;
							}
						}
						?>
					</ol>
					<div class="carousel-inner">
						<?php
						$first = true;
						foreach ($this->eventsByRelDay as $relDay => $daysEvents)
						{

							reset($daysEvents);

							// get all of the events for this day
							foreach ($daysEvents as $dayEvent)
							{
								if (!isset($dayEvent->_imageimg1) || $dayEvent->_imageimg1==""){
									continue;
								}
								$html = "";
								// generate output according custom string
								foreach ($this->splitCustomFormat as $condtoken)
								{

									if (isset($condtoken['cond']))
									{
										if ($condtoken['cond'] == 'a' && !$dayEvent->alldayevent())
											continue;
										else if ($condtoken['cond'] == '!a' && $dayEvent->alldayevent())
											continue;
										else if ($condtoken['cond'] == 'e' && !($dayEvent->noendtime() || $dayEvent->alldayevent()))
											continue;
										else if ($condtoken['cond'] == '!e' && ($dayEvent->noendtime() || $dayEvent->alldayevent()))
											continue;
										else if ($condtoken['cond'] == '!m' && $dayEvent->getUnixStartDate() != $dayEvent->getUnixEndDate())
											continue;
										else if ($condtoken['cond'] == 'm' && $dayEvent->getUnixStartDate() == $dayEvent->getUnixEndDate())
											continue;
									}
									foreach ($condtoken['data'] as $token)
									{
										if (is_string($token) && strpos($token, "ISACTIVE"))
										{
											if ($first)
											{
												$token = str_replace("ISACTIVE", "active", $token);
												$first = false;
											}
											else
											{
												$token = str_replace("ISACTIVE", "", $token);
											}
										}
										unset($match);
										unset($dateParm);
										$dateParm = "";
										$match = '';
										if (is_array($token))
										{
											$match = $token['keyword'];
											$dateParm = isset($token['dateParm']) ? trim($token['dateParm']) : "";
										}
										else if (strpos($token, '${') !== false)
										{
											$match = $token;
										}
										else
										{
											$html .= $token;
											continue;
										}
										$this->processMatch($html, $match, $dayEvent, $dateParm, $relDay);
									}
								}
								echo $html;
							}
						}
						?>
					</div>

					<!-- Controls -->
					<a class="left carousel-control" href="#jevlatestcarousel" data-slide="prev"></a>
					<a class="right carousel-control" href="#jevlatestcarousel" data-slide="next"></a>
				</div>
			</div>
			<script>
				// Mootools fix - see http://stackoverflow.com/questions/10462747/twitter-bootstrap-carousel-using-joomla-and-its-mootools
				if (typeof jQuery != 'undefined') {
					(function($) {
						$(document).ready(function() {
							$('.jevcarousel .carousel').each(function(index, element) {
								$(this)[index].slide = null;
							});
							 $("#jevlatestcarousel").carousel('cycle');
							/*
							 * This doesn't work - May be a MooTools problem
							$(".jevcarousel .carousel").swiperight(function() {
								$(".jevcarousel .carousel").carousel('prev');
							});
							$(".jevcarousel .carousel").swipeleft(function() {
								$(.jevcarousel .carousel").carousel('next');
							});
							 */
							// Use this option to disable auto-start
							//$('.jevcarousel .carousel').carousel('pause');
						});
					})(jQuery);
				}</script>

			<?php
			$content = ob_get_clean();
			if (!$hasEventsWithImages) {
				$content = "";
			}
		}
		return $content;

	}

}
