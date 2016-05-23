/*
mediaboxAdvanced v2.0.0 - The ultimate extension of Slimbox and Mediabox; an all-media script
jQuery script from Cédric KEIFLIN - http://www.joomlack.fr - http://www.template-creator.com

Original Mootools script from
	(c) 2007-2011 John Einselen - http://iaian7.com
based on Slimbox v1.64 - The ultimate lightweight Lightbox clone
	(c) 2007-2008 Christophe Beyls - http://www.digitalia.be

license: MIT-style

authors:
- John Einselen
- Christophe Beyls
- Contributions from many others
- Cédric KEIFLIN

provides: [Mediabox.open, Mediabox.close, Mediabox.recenter, Mediabox.scanPage]

modification by ced1870
version 2.0.1 - 07/04/15 :
- fix an issue with Dailymotion

version 2.0.0 - 23/03/15 :
- migrated to jQuery by ced1870
version 1.5.4a :
- fix an issue for IE11 margin left line 217+218
*/

var Mediabox;

(function($) {

	// browser detection
	if ($.browser == undefined) {
		var userAgent = navigator.userAgent.toLowerCase();
		$.browser = {
		   version: (userAgent.match( /.+(?:rv|it|ra|ie|me)[/: ]([d.]+)/ ) || [])[1],
		   chrome: /chrome/.test( userAgent ),
		   safari: /webkit/.test( userAgent ) && !/chrome/.test( userAgent ),
		   opera: /opera/.test( userAgent ),
		   msie: /msie/.test( userAgent ) && !/opera/.test( userAgent ),
		   ios: /(iPod|iPhone|iPad)/.test( userAgent ),
		   mozilla: /mozilla/.test( userAgent ) && !/(compatible|webkit)/.test( userAgent )
		};
	}
	// Global variables, accessible to Mediabox only
	var options, mediaArray, activeMedia, prevMedia, nextMedia, top, mTop, left, mLeft, winWidth, winHeight, fx, preload, preloadPrev = new Image(), preloadNext = new Image(),
	// DOM elements
	overlay, center, media, bottom, captionSplit, title, caption, number, prevLink, nextLink,
	// added by ced1870
	centerInitW, centerInitH, mediaInitW, mediaInitH,
	// Mediabox specific vars
	URL, WH, WHL, elrel, mediaWidth, mediaHeight, mediaType = "none", mediaSplit, mediaId = "mediaBox", margin, marginBottom;

	/*	Initialization	*/

	jQuery(document).ready(function(){
		// Create and append the Mediabox HTML code at the bottom of the document
		overlay = $('<div id="mbOverlay" />').css("display", "none").click(close);
		center = $('<div id="mbCenter" />').css("display", "none");
		$(document.body).append(
			overlay, center
		);

		center.append($('<div id="mbContainer" />'));
		container = $('#mbContainer');
		container.append($('<div id="mbMedia" />'));
		media = $('#mbMedia');
		center.append(bottom = $('<div id="mbBottom">'));
		closeLink = $('<a id="mbCloseLink" href="#" />').click(close);
		nextLink = $('<a id="mbNextLink" href="#" />').click(next);
		prevLink = $('<a id="mbPrevLink" href="#" />').click(previous);
		title = $('<div id="mbTitle" />');
		number = $('<div id="mbNumber" />');
		caption = $('<div id="mbCaption" />');
		bottom.append(closeLink, nextLink, prevLink, title, number, caption);
	});

	/*	API		*/

	Mediabox = {
		close: function(){
			close();	// Thanks to Yosha on the google group for fixing the close function API!
		},

		recenter: function(){	// Thanks to Garo Hussenjian (Xapnet Productions http://www.xapnet.com) for suggesting this addition
			if (center) {
				// fix by ced1870
				left = '50%';
				center.css({left: left, marginLeft: -(mediaWidth/2)-margin});
			}
		},

		open: function(_mediaArray, startMedia, _options) {
			options = {
//			Text options (translate as needed)
				buttonText: ['<big>&laquo;</big>','<big>&raquo;</big>','<big>&times;</big>'],		// Array defines "previous", "next", and "close" button content (HTML code should be written as entity codes or properly escaped)
//				buttonText: ['<big>«</big>','<big>»</big>','<big>×</big>'],
//				buttonText: ['<b>P</b>rev','<b>N</b>ext','<b>C</b>lose'],
				counterText: '({x} of {y})',	// Counter text, {x} = current item number, {y} = total gallery length
				linkText: '<a href="{x}" target="_new">{x}</a><br/>open in a new tab</div>',	// Text shown on iOS devices for non-image links
				flashText: '<b>Error</b><br/>Adobe Flash is either not installed or not up to date, please visit <a href="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" title="Get Flash" target="_new">Adobe.com</a> to download the free player.',	// Text shown if Flash is not installed.
//			General overlay options
				center: true,					// Set to false for use with custom CSS layouts
				loop: false,					// Navigate from last to first elements in a gallery
				keyboard: true,					// Enables keyboard control; escape key, left arrow, and right arrow
				keyboardAlpha: false,			// Adds 'x', 'c', 'p', and 'n' when keyboard control is also set to true
				keyboardStop: false,			// Stops all default keyboard actions while overlay is open (such as up/down arrows)
												// Does not apply to iFrame content, does not affect mouse scrolling
				overlayOpacity: 0.8,			// 1 is opaque, 0 is completely transparent (change the color in the CSS file)
				resizeOpening: true,			// Determines if box opens small and grows (true) or starts at larger size (false)
				resizeDuration: 240,			// Duration of each of the box resize animations (in milliseconds)
				initialWidth: 320,				// Initial width of the box (in pixels)
				initialHeight: 180,				// Initial height of the box (in pixels)
				defaultWidth: 640,				// Default width of the box (in pixels) for undefined media (MP4, FLV, etc.)
				defaultHeight: 360,				// Default height of the box (in pixels) for undefined media (MP4, FLV, etc.)
				showCaption: true,				// Display the title and caption, true / false
				showCounter: true,				// If true, a counter will only be shown if there is more than 1 image to display
				countBack: false,				// Inverts the displayed number (so instead of the first element being labeled 1/10, it's 10/10)
				clickBlock: true,				// Adds an event on right-click to block saving of images from the context menu in most browsers (this can't prevent other ways of downloading, but works as a casual deterent)
								// due to less than ideal code ordering, clickBlock on links must be removed manually around line 250
//			iOS device options
//				iOSenable: false,				// When set to false, disables overlay entirely (links open in new tab)
												// IMAGES and INLINE content will display normally,
												// while ALL OTHER content will display a direct link (this is required so as to not break mixed-media galleries)
				iOShtml: true,					// If set to true, HTML content is displayed normally as well (useful if your HTML content is minimal and UI oriented instead of external sites)
//			Image options
				imgBackground: false,		// Embed images as CSS background (true) or <img> tag (false)
											// CSS background is naturally non-clickable, preventing downloads
											// IMG tag allows automatic scaling for smaller screens
											// (all images have no-click code applied, albeit not Opera compatible. To remove, comment lines 212 and 822)
				imgPadding: 100,			// Clearance necessary for images larger than the window size (only used when imgBackground is false)
											// Change this number only if the CSS style is significantly divergent from the original, and requires different sizes
//			Inline options
				overflow: 'auto',			// If set, overides CSS settings for inline content only, set to "false" to leave CSS settings intact.
				inlineClone: false,			// Clones the inline element instead of moving it from the page to the overlay
//			Global media options
				html5: 'true',				// HTML5 settings for YouTube and Vimeo, false = off, true = on
				scriptaccess: 'true',		// Allow script access to flash files
				fullscreen: 'true',			// Use fullscreen
				fullscreenNum: '1',			// 1 = true
				autoplay: 'true',			// Plays the video as soon as it's opened
				autoplayNum: '1',			// 1 = true
				autoplayYes: 'yes',			// yes = true
				volume: '100',				// 0-100, used for NonverBlaster and Quicktime players
				medialoop: 'true',			// Loop video playback, true / false, used for NonverBlaster and Quicktime players
				bgcolor: '#000000',			// Background color, used for flash and QT media
				wmode: 'transparent',			// Background setting for Adobe Flash ('opaque' and 'transparent' are most common)
//			NonverBlaster
				playerpath: 'plugins/system/mediabox_ck/mediabox_ck/NonverBlaster.swf',	// Path to NonverBlaster.swf
				showTimecode: 'false',		// turn timecode display off or on (true, false)
				controlColor: '0xFFFFFF',	// set the control color
				controlBackColor: '0x0000000',	// set the bakcground color (video only)
//				playerBackColor: '0x0000FF',	// set the player background color (leave blank to allow CSS styles to show through for audio)
				playerBackColor: '',	// set the player background color (leave blank to allow CSS styles to show through)
				wmodeNB: 'transparent',			// Background setting for Adobe Flash (set to 'transparent' for a blank background, 'opaque' in other situations)
//				autoAdvance: 'false',		// placeholder setting only - not currently implemented (intending to add auto gallery list navigation on play-end)
//			Quicktime options
				controller: 'true',			// Show controller, true / false
//			Flickr options
				flInfo: 'true',				// Show title and info at video start
//			Revver options
				revverID: '187866',			// Revver affiliate ID, required for ad revinue sharing
				revverFullscreen: 'true',	// Fullscreen option
				revverBack: '000000',		// Background color
				revverFront: 'ffffff',		// Foreground color
				revverGrad: '000000',		// Gradation color
//			Ustream options
				usViewers: 'true',			// Show online viewer count (true, false)
//			Youtube options
				ytBorder: '0',				// Outline				(1=true, 0=false)
				ytColor1: '000000',			// Outline color
				ytColor2: '333333',			// Base interface color (highlight colors stay consistent)
				ytRel: '0',					// Show related videos	(1=true, 0=false)
				ytInfo: '1',				// Show video info		(1=true, 0=false)
				ytSearch: '0',				// Show search field	(1=true, 0=false)
//			Viddyou options
				vuPlayer: 'basic',			// Use 'full' or 'basic' players
//			Vimeo options
				vmTitle: '1',				// Show video title
				vmByline: '1',				// Show byline
				vmPortrait: '1',			// Show author portrait
				vmColor: 'ffffff'			// Custom controller colors, hex value minus the # sign, defult is 5ca0b5
			};

			options = $.extend({}, options, _options);

			prevLink.html( options.buttonText[0]);
			nextLink.html( options.buttonText[1]);
			closeLink.html( options.buttonText[2]);

// TODO : à migrer
			// if (Browser.firefox2) {	// Fixes Firefox 2 and Camino 1.6 incompatibility with opacity + flash
				// options.overlayOpacity = 1;
				// overlay.className = 'mbOverlayOpaque';
			// }

			// if (Browser.Platform.ios) {
				// options.keyboard = false;
				// options.resizeOpening = false;	// Speeds up interaction on small devices (mobile) or older computers (IE6)
				// overlay.className = 'mbMobile';
				// bottom.className = 'mbMobile';
				// position();
			// }

			// if (Browser.ie6) {
				// options.resizeOpening = false;	// Speeds up interaction on small devices (mobile) or older computers (IE6)
				// overlay.className = 'mbOverlayAbsolute';
				// position();
			// }

			if (typeof _mediaArray == "string") {	// Used for single mediaArray only, with URL and Title as first two arguments
				_mediaArray = [[_mediaArray,startMedia,_options]];
				startMedia = 0;
			}

			mediaArray = _mediaArray;
			options.loop = options.loop && (mediaArray.length > 1);

			size();
			setup(true);
			top = $(window).scrollTop() + ($(window).height()/2);
			// fix by ced1870
			left = '50%';

			mediaLeftMargin = ( media.css('margin-left') == 'auto' ) ? 0 : media.css('margin-left');
			margin = parseInt(center.css('padding-left'))+parseInt(mediaLeftMargin)+parseInt(media.css('padding-left')); // modif ced1870
			marginBottom = parseInt(bottom.css('margin-left'))+parseInt(bottom.css('padding-left'))+parseInt(bottom.css('margin-right'))+parseInt(bottom.css('padding-right'));
			center.css({top: top, left: left, width: options.initialWidth, height: options.initialHeight, marginTop: -(options.initialHeight/2)-margin, marginLeft: -(options.initialWidth/2)-margin, display: ""});

			$('#mbOverlay').css('opacity', 0).fadeTo( 360, options.overlayOpacity);
			return changeMedia(startMedia);
		}
	};

	$.fn.mediabox = function(_options, linkMapper, linksFilter) {
			linkMapper = linkMapper || function(el) {
				elrel = $(el).attr('rel').split(/[\[\]]/);
				elrel = elrel[1];
				return [$(el).attr('href'), $(el).attr('title'), elrel];	// thanks to Dušan Medlín for figuring out the URL bug!
			};

			linksFilter = linksFilter || function() {
				return true;
			};

			var links = this;

/*  clickBlock code - remove the following three lines to enable right-clicking on links to images  */
			// links.contextmenu(function(e){
				// if (this.toString().match(/\.gif|\.jpg|\.jpeg|\.png/i)) e.preventDefault();
			// });

			links.off("click").on("click", function(e) {
				curlink = this;
				e.preventDefault();
				// Build the list of media that will be displayed
				var filteredArray = links.filter( function() { return linksFilter(curlink, this); });
				var filteredLinks = [];
				var filteredHrefs = [];

				filteredArray.each(function(index, item){
					if(filteredHrefs.indexOf(item.toString()) < 0) {
						filteredLinks.push(filteredArray[index]);
						filteredHrefs.push(filteredArray[index].toString());
					}
				});

				return Mediabox.open(filteredLinks.map(linkMapper), filteredHrefs.indexOf(this.toString()), _options);
			});

			return links;
		}


	/*	Internal functions	*/

	function position() {
		overlay.css({top: window.getScrollTop(), left: window.getScrollLeft()});
	}

	function size() {
		winWidth = $(window).width();
		winHeight = $(window).height();
		// if the window is bigger than the image, get the initial width back
		if (mediaHeight >= winHeight-options.imgPadding && (mediaHeight / winHeight) >= (mediaWidth / winWidth)) {
			media.css({width: mediaInitW, height: mediaInitH});
			bottom.css({width: mediaInitW-marginBottom+"px"});
			caption.css({width: mediaInitW-marginBottom+"px"});
			center.css({width: centerInitW, height: centerInitH});
		// if the window gets smaller, it fits the image
		} else if (mediaWidth >= winWidth-options.imgPadding && (mediaHeight / winHeight) < (mediaWidth / winWidth)) {
			tmpWidth = winWidth-options.imgPadding;
			tmpHeight = parseInt((tmpWidth/mediaWidth)*mediaHeight, 10);
			media.css({width: tmpWidth, height: tmpHeight});
			bottom.css({width: tmpWidth-marginBottom+"px"});
			caption.css({width: tmpWidth-marginBottom+"px"});
			tmpWidth = media.outerWidth();
			tmpHeight = media.outerHeight()+bottom.outerHeight();
			center.css({width: media.outerWidth(), height: media.outerHeight()+bottom.outerHeight(), marginTop: mTop-margin, marginLeft: -(tmpWidth/2)-margin});
		}
	}

	function setup(open) {
		// Hides on-page objects and embeds while the overlay is open, nessesary to counteract Firefox stupidity
		// if (Browser.firefox) {
			// ["object", window.ie ? "select" : "embed"].forEach(function(tag) {
				// Array.forEach($$(tag), function(el) {
					// if (open) el._mediabox = el.style.visibility;
					// el.style.visibility = open ? "hidden" : el._mediabox;
				// });
			// });
		// }

		overlay.css('display', (open ? "" : "none") );

		// var fn = open ? "addEvent" : "removeEvent";
		// TODO : browser à migrer
		// if (Browser.Platform.ios || Browser.ie6) window[fn]("scroll", position);	// scroll position is updated only after movement has stopped

		if (options.keyboard) $(window).bind('keydown',keyDown);
	}

	function keyDown(event) {
		if (options.keyboardAlpha) {
			switch(event.code) {
				case 27:	// Esc
				case 88:	// 'x'
				case 67:	// 'c'
					close();
					break;
				case 37:	// Left arrow
				case 80:	// 'p'
					previous();
					break;
				case 39:	// Right arrow
				case 78:	// 'n'
					next();
			}
		} else {
			switch(event.code) {
				case 27:	// Esc
					close();
					break;
				case 37:	// Left arrow
					previous();
					break;
				case 39:	// Right arrow
					next();
			}
		}
		if (options.keyboardStop) { return false; }
	}

	function previous() {
		return changeMedia(prevMedia);
	}

	function next() {
		return changeMedia(nextMedia);
	}

	function createMedia(url, params) {
		// var html = '<video controls="controls" poster="linktoposter.jpg" width="'+params['width']+'" height="'+params['height']+'">'
			// +'<source src="'+url+'" type="video/mp4" />'
			// +'<source src="linktomovie.webm" type="video/webm" />'
			// +'<source src="linktomovie.ogv" type="video/ogg" />'
			// +'<!-- Fallback object using Flow Player -->'
			var html = '<object data="'+url+'" width="'+params['width']+'" height="'+params['height']+'" type="application/x-shockwave-flash">'
			+'<param name="movie" value="'+url+'" />'
			+'<param name="allowFullScreen" value="true" />'
			+'<param name="wmode" value="transparent" />';

			for (var param in params['params']) {
				html += '<param name="'+param+'" value="'+params['params'][param]+'" />';
			}
			//+'<param name="flashVars" value="config={'playlist':[ 'linktoposter.jpg',{'url':'linktomovie.mp4','autoPlay':false}]}" />'
			// +'<img alt="My Movie" src="linktoposter.jpg" width="'+params['width']+'" height="'+params['height']+'" title="No video playback capabilities, please download the video below." />'
			html += '</object>';
			// +'<!-- Fallback Text -->'
			// +'Your browser does not appear to support a browser. Please download the video below.'
			// +'</video>'
			// +'<p>'
			// +'<strong>Download video:</strong> <a href="linktomovie.mp4">MP4 format</a> | <a href=" linktomovie.ogv">Ogg format</a> | <a href=" linktomovie.webm">WebM format</a>'
			// +'</p>';
		return $(html);
	}

	function changeMedia(mediaIndex) {
		if (mediaIndex >= 0) {
//			if (Browser.Platform.ios && !options.iOSenable) {
//				window.open(mediaArray[mediaIndex][0], "_blank");
//				close();
//				return false;
//			}
			media.html('');
			activeMedia = mediaIndex;
			prevMedia = ((activeMedia || !options.loop) ? activeMedia : mediaArray.length) - 1;
			nextMedia = activeMedia + 1;
			if (nextMedia == mediaArray.length) nextMedia = options.loop ? 0 : -1;
			stop();
			center.addClass("mbLoading");
			if (preload && mediaType == "inline" && !options.inlineClone) preload.adopt(media.getChildren());	// prevents loss of adopted data

	/*	mediaboxAdvanced link formatting and media support	*/

			if (!mediaArray[mediaIndex][2]) mediaArray[mediaIndex][2] = '';	// Thanks to Leo Feyer for offering this fix
			WH = mediaArray[mediaIndex][2].split(' ');
			WHL = WH.length;
			if (WHL>1) {
//				mediaWidth = (WH[WHL-2].match("%")) ? (window.getWidth()*((WH[WHL-2].replace("%", ""))*0.01))+"px" : WH[WHL-2]+"px";
				mediaWidth = (WH[WHL-2].match("%")) ? ($(window).width()*((WH[WHL-2].replace("%", ""))*0.01)) : WH[WHL-2];
//				mediaHeight = (WH[WHL-1].match("%")) ? (window.getHeight()*((WH[WHL-1].replace("%", ""))*0.01))+"px" : WH[WHL-1]+"px";
				mediaHeight = (WH[WHL-1].match("%")) ? ($(window).height()*((WH[WHL-1].replace("%", ""))*0.01)) : WH[WHL-1];
			} else {
				mediaWidth = "";
				mediaHeight = "";
			}
			URL = mediaArray[mediaIndex][0];
//			URL = encodeURI(URL).replace("(","%28").replace(")","%29");
//			URL = encodeURI(URL).replace("(","%28").replace(")","%29").replace("%20"," ");

			captionSplit = (mediaArray[activeMedia][1] != undefined) ? mediaArray[activeMedia][1].split('::') : '';

// Quietube and yFrog support
			if (URL.match(/quietube\.com/i)) {
				mediaSplit = URL.split('v.php/');
				URL = mediaSplit[1];
			} else if (URL.match(/\/\/yfrog/i)) {
				mediaType = (URL.substring(URL.length-1));
				if (mediaType.match(/b|g|j|p|t/i)) mediaType = 'image';
				if (mediaType == 's') mediaType = 'flash';
				if (mediaType.match(/f|z/i)) mediaType = 'video';
				URL = URL+":iphone";
			}

	/*	Specific Media Types	*/ 
 
// GIF, JPG, PNG
			if (URL.match(/\.gif|\.jpg|\.jpeg|\.png|twitpic\.com/i) || mediaType == 'image') {
				mediaType = 'img';
				URL = URL.replace(/twitpic\.com/i, "twitpic.com/show/full");
				preload = new Image();
				preload.onload = startEffect;
				preload.src = URL;
// FLV, MP4
			} else if (URL.match(/\.flv|\.mp4/i) || mediaType == 'video') {
				mediaType = 'obj';
				mediaWidth = mediaWidth || options.defaultWidth;
				mediaHeight = mediaHeight || options.defaultHeight;
				preload = createMedia(''+options.playerpath+'?mediaURL='+URL+'&allowSmoothing=true&autoPlay='+options.autoplay+'&buffer=6&showTimecode='+options.showTimecode+'&loop='+options.medialoop+'&controlColor='+options.controlColor+'&controlBackColor='+options.controlBackColor+'&playerBackColor='+options.playerBackColor+'&defaultVolume='+options.volume+'&scaleIfFullScreen=true&showScalingButton=true&crop=false', {
					id: 'mbVideo',
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmodeNB, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// MP3, AAC
			} else if (URL.match(/\.mp3|\.aac|tweetmic\.com|tmic\.fm/i) || mediaType == 'audio') {
				mediaType = 'obj';
				mediaWidth = mediaWidth || options.defaultWidth;
				mediaHeight = mediaHeight || "17";
				if (URL.match(/tweetmic\.com|tmic\.fm/i)) {
					URL = URL.split('/');
					URL[4] = URL[4] || URL[3];
					URL = "http://media4.fjarnet.net/tweet/tweetmicapp-"+URL[4]+'.mp3';
				}
				preload = createMedia(''+options.playerpath+'?mediaURL='+URL+'&allowSmoothing=true&autoPlay='+options.autoplay+'&buffer=6&showTimecode='+options.showTimecode+'&loop='+options.medialoop+'&controlColor='+options.controlColor+'&controlBackColor='+options.controlBackColor+'&defaultVolume='+options.volume+'&scaleIfFullScreen=true&showScalingButton=true&crop=false', {
					id: 'mbAudio',
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// SWF
			} else if (URL.match(/\.swf/i) || mediaType == 'flash') {
				mediaType = 'obj';
				mediaWidth = mediaWidth || options.defaultWidth;
				mediaHeight = mediaHeight || options.defaultHeight;
				preload = createMedia(URL, {
					id: 'mbFlash',
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
				// removed by ced1870, .mov no more useful
// MOV, M4V, M4A, MP4, AIFF, etc.
			/*} else if (URL.match(/\.mov|\.m4v|\.m4a|\.aiff|\.avi|\.caf|\.dv|\.mid|\.m3u|\.mp2||\.qtz/i) || mediaType == 'qt') {
				mediaType = 'qt';
				mediaWidth = mediaWidth || options.defaultWidth;
//				mediaHeight = (parseInt(mediaHeight, 10)+16)+"px" || options.defaultHeight;
				mediaHeight = (parseInt(mediaHeight, 10)+16) || options.defaultHeight;
				preload = new Quickie(URL, {
					id: 'MediaboxQT',
					width: mediaWidth,
					height: mediaHeight,
					attributes: {controller: options.controller, autoplay: options.autoplay, volume: options.volume, loop: options.medialoop, bgcolor: options.bgcolor}
					});
				startEffect();
				*/
// Blip.tv
			}
				/*	Social Media Sites	*/
			else if (URL.match(/blip\.tv/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "640";
				mediaHeight = mediaHeight || "390";
				preload = createMedia(URL, {
					src: URL,
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Break.com
			} else if (URL.match(/break\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "464";
				mediaHeight = mediaHeight || "376";
				mediaId = URL.match(/\d{6}/g);
				preload = createMedia('http://embed.break.com/'+mediaId, {
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// DailyMotion
			} else if (URL.match(/dailymotion\.com/i)) {
				// mediaType = 'obj';
				// mediaWidth = mediaWidth || "480";
				// mediaHeight = mediaHeight || "381";
				// preload = createMedia(URL, {
					// id: mediaId,
					// width: mediaWidth,
					// height: mediaHeight,
					// params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					// });
				// startEffect();
				mediaSplit_video = URL.split('video/');
				mediaSplit_swf = URL.split('swf/');
				if (options.html5 && mediaSplit_video.length > 1) {
					mediaType = 'url';
					mediaWidth = mediaWidth || "640";
					mediaHeight = mediaHeight || "381";
					mediaId = "mediaId_"+new Date().getTime();	// Safari may not update iframe content with a static id.
					preload = $('<iframe />', {
						'src': 'http://www.dailymotion.com/embed/video/'+mediaSplit_video[1],
						'id': mediaId,
						'width': mediaWidth,
						'height': mediaHeight,
						'frameborder': 0
						});
					startEffect();
				} else {
					mediaType = 'obj';
					mediaId = mediaSplit_swf[1];
					mediaWidth = mediaWidth || "480";
					mediaHeight = mediaHeight || "381";
					preload = createMedia('http://www.dailymotion.com/swf/'+mediaId, {
						id: mediaId,
						width: mediaWidth,
						height: mediaHeight,
						params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
						});
					startEffect();
				}
// Facebook
			} else if (URL.match(/facebook\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "320";
				mediaHeight = mediaHeight || "240";
				mediaSplit = URL.split('v=');
				mediaSplit = mediaSplit[1].split('&');
				mediaId = mediaSplit[0];
				preload = createMedia('http://www.facebook.com/v/'+mediaId, {
					movie: 'http://www.facebook.com/v/'+mediaId,
					classid: 'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000',
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Flickr
			} else if (URL.match(/flickr\.com(?!.+\/show\/)/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "500";
				mediaHeight = mediaHeight || "375";
				mediaSplit = URL.split('/');
				mediaId = mediaSplit[5];
				preload = createMedia('http://www.flickr.com/apps/video/stewart.swf', {
					id: mediaId,
					classid: 'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000',
					width: mediaWidth,
					height: mediaHeight,
					params: {flashvars: 'photo_id='+mediaId+'&amp;show_info_box='+options.flInfo, wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// GameTrailers Video
			} else if (URL.match(/gametrailers\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "480";
				mediaHeight = mediaHeight || "392";
				mediaId = URL.match(/\d{5}/g);
				preload = createMedia('http://www.gametrailers.com/remote_wrap.php?mid='+mediaId, {
					id: mediaId,
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Google Video
			} else if (URL.match(/google\.com\/videoplay/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "400";
				mediaHeight = mediaHeight || "326";
				mediaSplit = URL.split('=');
				mediaId = mediaSplit[1];
				preload = createMedia('http://video.google.com/googleplayer.swf?docId='+mediaId+'&autoplay='+options.autoplayNum, {
					id: mediaId,
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Megavideo - Thanks to Robert Jandreu for suggesting this code!
			} else if (URL.match(/megavideo\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "640";
				mediaHeight = mediaHeight || "360";
				mediaSplit = URL.split('=');
				mediaId = mediaSplit[1];
				preload = createMedia('http://wwwstatic.megavideo.com/mv_player.swf?v='+mediaId, {
					id: mediaId,
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Metacafe
			} else if (URL.match(/metacafe\.com\/watch/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "400";
				mediaHeight = mediaHeight || "345";
				mediaSplit = URL.split('/');
				mediaId = mediaSplit[4];
				preload = createMedia('http://www.metacafe.com/fplayer/'+mediaId+'/.swf?playerVars=autoPlay='+options.autoplayYes, {
					id: mediaId,
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Myspace
			} else if (URL.match(/vids\.myspace\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "425";
				mediaHeight = mediaHeight || "360";
				preload = createMedia(URL, {
					id: mediaId,
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Revver
			} else if (URL.match(/revver\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "480";
				mediaHeight = mediaHeight || "392";
				mediaSplit = URL.split('/');
				mediaId = mediaSplit[4];
				preload = createMedia('http://flash.revver.com/player/1.0/player.swf?mediaId='+mediaId+'&affiliateId='+options.revverID+'&allowFullScreen='+options.revverFullscreen+'&autoStart='+options.autoplay+'&backColor=#'+options.revverBack+'&frontColor=#'+options.revverFront+'&gradColor=#'+options.revverGrad+'&shareUrl=revver', {
					id: mediaId,
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Rutube
			} else if (URL.match(/rutube\.ru/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "470";
				mediaHeight = mediaHeight || "353";
				mediaSplit = URL.split('=');
				mediaId = mediaSplit[1];
				preload = createMedia('http://video.rutube.ru/'+mediaId, {
					movie: 'http://video.rutube.ru/'+mediaId,
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Tudou
			} else if (URL.match(/tudou\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "400";
				mediaHeight = mediaHeight || "340";
				mediaSplit = URL.split('/');
				mediaId = mediaSplit[5];
				preload = createMedia('http://www.tudou.com/v/'+mediaId, {
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Twitcam
			} else if (URL.match(/twitcam\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "320";
				mediaHeight = mediaHeight || "265";
				mediaSplit = URL.split('/');
				mediaId = mediaSplit[3];
				preload = createMedia('http://static.livestream.com/chromelessPlayer/wrappers/TwitcamPlayer.swf?hash='+mediaId, {
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Twitvid
			} else if (URL.match(/twitvid\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "600";
				mediaHeight = mediaHeight || "338";
				mediaSplit = URL.split('/');
				mediaId = mediaSplit[3];
				preload = createMedia('http://www.twitvid.com/player/'+mediaId, {
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Ustream.tv
			} else if (URL.match(/ustream\.tv/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "400";
				mediaHeight = mediaHeight || "326";
				preload = createMedia(URL+'&amp;viewcount='+options.usViewers+'&amp;autoplay='+options.autoplay, {
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// YouKu
			} else if (URL.match(/youku\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "480";
				mediaHeight = mediaHeight || "400";
				mediaSplit = URL.split('id_');
				mediaId = mediaSplit[1];
				preload = createMedia('http://player.youku.com/player.php/sid/'+mediaId+'=/v.swf', {
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// YouTube Video (now includes HTML5 option)
			} else if (URL.match(/youtube\.com\/watch/i)) {
				mediaSplit = URL.split('v=');
				if (options.html5) {
					mediaType = 'url';
					mediaWidth = mediaWidth || "640";
					mediaHeight = mediaHeight || "385";
					mediaId = "mediaId_"+new Date().getTime();	// Safari may not update iframe content with a static id.
					preload = $('<iframe />', {
						'src': 'http://www.youtube.com/embed/'+mediaSplit[1],
						'id': mediaId,
						'width': mediaWidth,
						'height': mediaHeight,
						'frameborder': 0
						});
					startEffect();
				} else {
					mediaType = 'obj';
					mediaId = mediaSplit[1];
					mediaWidth = mediaWidth || "480";
					mediaHeight = mediaHeight || "385";
					preload = createMedia('http://www.youtube.com/v/'+mediaId+'&autoplay='+options.autoplayNum+'&fs='+options.fullscreenNum+'&border='+options.ytBorder+'&color1=0x'+options.ytColor1+'&color2=0x'+options.ytColor2+'&rel='+options.ytRel+'&showinfo='+options.ytInfo+'&showsearch='+options.ytSearch, {
						id: mediaId,
						width: mediaWidth,
						height: mediaHeight,
						params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
						});
					startEffect();
				}
// YouTube Playlist
			} else if (URL.match(/youtube\.com\/view/i)) {
				mediaType = 'obj';
				mediaSplit = URL.split('p=');
				mediaId = mediaSplit[1];
				mediaWidth = mediaWidth || "480";
				mediaHeight = mediaHeight || "385";
				preload = createMedia('http://www.youtube.com/p/'+mediaId+'&autoplay='+options.autoplayNum+'&fs='+options.fullscreenNum+'&border='+options.ytBorder+'&color1=0x'+options.ytColor1+'&color2=0x'+options.ytColor2+'&rel='+options.ytRel+'&showinfo='+options.ytInfo+'&showsearch='+options.ytSearch, {
					id: mediaId,
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Veoh
			} else if (URL.match(/veoh\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "410";
				mediaHeight = mediaHeight || "341";
				URL = URL.replace('%3D','/');
				mediaSplit = URL.split('watch/');
				mediaId = mediaSplit[1];
				preload = createMedia('http://www.veoh.com/static/swf/webplayer/WebPlayer.swf?version=AFrontend.5.5.2.1001&permalinkId='+mediaId+'&player=videodetailsembedded&videoAutoPlay='+options.AutoplayNum+'&id=anonymous', {
					id: mediaId,
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
					});
				startEffect();
// Viddler
			} else if (URL.match(/viddler\.com/i)) {
				mediaType = 'obj';
				mediaWidth = mediaWidth || "437";
				mediaHeight = mediaHeight || "370";
				mediaSplit = URL.split('/');
				mediaId = mediaSplit[4];
				preload = createMedia(URL, {
					id: 'viddler_'+mediaId,
					movie: URL,
					classid: 'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000',
					width: mediaWidth,
					height: mediaHeight,
					params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen, id: 'viddler_'+mediaId, movie: URL}
					});
				startEffect();
// Vimeo (now includes HTML5 option)
			} else if (URL.match(/vimeo\.com/i)) {
				mediaWidth = mediaWidth || "640";		// site defualt: 400px
				mediaHeight = mediaHeight || "360";		// site defualt: 225px
				mediaSplit = URL.split('/');
				mediaId = mediaSplit[3];

				if (options.html5) {
					mediaType = 'url';
					mediaId = "mediaId_"+new Date().getTime();	// Safari may not update iframe content with a static id.
					preload = $('<iframe />', {
						'src': 'http://player.vimeo.com/video/'+mediaSplit[3]+'?portrait='+options.vmPortrait,
						'id': mediaId,
						'width': mediaWidth,
						'height': mediaHeight,
						'frameborder': 0
						});
					startEffect();
				} else {
					mediaType = 'obj';
					preload = createMedia('http://www.vimeo.com/moogaloop.swf?clip_id='+mediaId+'&amp;server=www.vimeo.com&amp;fullscreen='+options.fullscreenNum+'&amp;autoplay='+options.autoplayNum+'&amp;show_title='+options.vmTitle+'&amp;show_byline='+options.vmByline+'&amp;show_portrait='+options.vmPortrait+'&amp;color='+options.vmColor, {
						id: mediaId,
						width: mediaWidth,
						height: mediaHeight,
						params: {wmode: options.wmode, bgcolor: options.bgcolor, allowscriptaccess: options.scriptaccess, allowfullscreen: options.fullscreen}
						});
					startEffect();
				}
// INLINE
			} else if (URL.match(/\#mb_/i)) {
				mediaType = 'inline';
				mediaWidth = mediaWidth || options.defaultWidth;
				mediaHeight = mediaHeight || options.defaultHeight;
				URLsplit = URL.split('#');
//				preload = new Element("div", {id: "mbMediaInline"}).adopt($('#'+URLsplit[1]).getChildren().clone([true,true]));
				preload = $('#'+URLsplit[1]);
				startEffect();
// HTML (applies to ALL links not recognised as a specific media type)
			} else {
				mediaType = 'url';
				mediaWidth = mediaWidth || options.defaultWidth;
				mediaHeight = mediaHeight || options.defaultHeight;
				mediaId = "mediaId_"+new Date().getTime();	// Safari may not update iframe content with a static id.
				preload = $('<iframe />', {
					'src': URL,
					'id': mediaId,
					'width': mediaWidth,
					'height': mediaHeight,
					'frameborder': 0
					});
				startEffect();
			}
		}
		return false;
	}

	function startEffect() {

//		if (Browser.Platform.ios && (mediaType == "obj" || mediaType == "qt" || mediaType == "html")) alert("this isn't gonna work");
//		if (Browser.Platform.ios && (mediaType == "obj" || mediaType == "qt" || mediaType == "html")) mediaType = "ios";
		// added by ced1870
		media.css('opacity', 0);

		(mediaType == "img") ? media.on("click", next) : media.off("click", next);
		if (mediaType == "img"){
			mediaWidth = preload.width;
			mediaHeight = preload.height;
			if (options.imgBackground) {
				media.css({backgroundImage: "url("+URL+")", display: ""});
			} else {	// Thanks to Dusan Medlin for fixing large 16x9 image errors in a 4x3 browser
				if (mediaHeight >= winHeight-options.imgPadding && (mediaHeight / winHeight) >= (mediaWidth / winWidth)) {
					mediaHeight = winHeight-options.imgPadding;
					mediaWidth = preload.width = parseInt((mediaHeight/preload.height)*mediaWidth, 10);
					preload.height = mediaHeight;
				} else if (mediaWidth >= winWidth-options.imgPadding && (mediaHeight / winHeight) < (mediaWidth / winWidth)) {
					mediaWidth = winWidth-options.imgPadding;
					mediaHeight = preload.height = parseInt((mediaWidth/preload.width)*mediaHeight, 10);
					preload.width = mediaWidth;
				}
				// TODO : migrer
				// if (Browser.ie) preload = $('#'+preload);
				if (options.clickBlock) $(preload).on('mousedown', function(e){ e.stopPropagation(); }).on('contextmenu', function(e){ e.stopPropagation(); });
				media.css({backgroundImage: "none", display: ""});
				media.append(preload);
			}
//			mediaWidth += "px";
//			mediaHeight += "px";
		} else if (mediaType == "inline") {
//			if (options.overflow) media.css({overflow: options.overflow});
			media.css({backgroundImage: "none", display: ""});
//			media.append(preload);
//			media.grab(preload.get('html'));
			(options.inlineClone)?media.grab(preload.get('html')):media.adopt(preload.getChildren());
		} else if (mediaType == "qt") {
			media.css({backgroundImage: "none", display: ""});
			media.append(preload);
//			preload;
		} else if (mediaType == "ios" || $.browser['ios']) {
			media.css({backgroundImage: "none", display: ""});
			media.html(options.linkText.replace(/\{x\}/gi, URL));
			mediaWidth = options.DefaultWidth;
			mediaHeight = options.DefaultHeight;
		} else if (mediaType == "url") {
			media.css({backgroundImage: "none", display: ""});
			media.append(preload);
//			if (Browser.safari) options.resizeOpening = false;	// Prevents occasional blank video display errors in Safari, thanks to Kris Gale for the solution
		} else if (mediaType == "obj") {
			if (! $.flash.hasVersion(8)) {
				media.css({backgroundImage: "none", display: ""});
				media.html('<div id="mbError"><b>Error</b><br/>Adobe Flash is either not installed or not up to date, please visit <a href="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" title="Get Flash" target="_new">Adobe.com</a> to download the free player.</div>');
				mediaWidth = options.DefaultWidth;
				mediaHeight = options.DefaultHeight;
			} else {
				media.css({backgroundImage: "none", display: ""});
				media.append(preload);
//				if (Browser.safari) options.resizeOpening = false;	// Prevents occasional blank video display errors in Safari, thanks to Kris Gale for the solution
			}
		} else {
			media.css({backgroundImage: "none", display: ""});
			media.html(options.flashText);
			mediaWidth = options.defaultWidth;
			mediaHeight = options.defaultHeight;
		}

		$(window).bind('resize',size);

		title.empty().html( (options.showCaption) ? captionSplit[0] : "");
		caption.empty().html( (options.showCaption && (captionSplit.length > 1)) ? captionSplit[1] : "");
		number.empty().html( (options.showCounter && (mediaArray.length > 1)) ? options.counterText.replace(/\{x\}/, (options.countBack)?mediaArray.length-activeMedia:activeMedia+1).replace(/\{y\}/, mediaArray.length) : "");

//		if (options.countBack) {
//			number.html((options.showCounter && (mediaArray.length > 1)) ? options.counterText.replace(/{x}/, activeMedia + 1).replace(/{y}/, mediaArray.length) : "");
//		} else {
//			number.html((options.showCounter && (mediaArray.length > 1)) ? options.counterText.replace(/{x}/, mediaArray.length - activeMedia).replace(/{y}/, mediaArray.length) : "");
//		}

		if ((prevMedia >= 0) && (mediaArray[prevMedia][0].match(/\.gif|\.jpg|\.jpeg|\.png|twitpic\.com/i))) preloadPrev.src = mediaArray[prevMedia][0].replace(/twitpic\.com/i, "twitpic.com/show/full");
		if ((nextMedia >= 0) && (mediaArray[nextMedia][0].match(/\.gif|\.jpg|\.jpeg|\.png|twitpic\.com/i))) preloadNext.src = mediaArray[nextMedia][0].replace(/twitpic\.com/i, "twitpic.com/show/full");
		if (prevMedia >= 0) prevLink.css('display','');
		if (nextMedia >= 0) nextLink.css('display','');
		mediaInitW = mediaWidth;
		mediaInitH = mediaHeight;
		media.css({width: mediaWidth+"px", height: mediaHeight+"px"});
		bottom.css({width: mediaWidth-marginBottom+"px"});
		caption.css({width: mediaWidth-marginBottom+"px"});

		mediaWidth = media.outerWidth();
		mediaHeight = media.outerHeight()+bottom.outerHeight();
		centerInitW = mediaWidth;
		centerInitH = mediaHeight;
		if (mediaHeight >= top+top) { mTop = -top; } else { mTop = -(mediaHeight/2); }
		if (mediaWidth >= left+left) { mLeft = -left; } else { mLeft = -(mediaWidth/2); }
		if (options.resizeOpening ) { 
			center.animate({width: mediaWidth, height: mediaHeight, marginTop: mTop-margin, marginLeft: mLeft-margin}, options.resizeDuration, mediaAnimate);
		} else { 
			center.css({width: mediaWidth, height: mediaHeight, marginTop: mTop-margin, marginLeft: mLeft-margin}); mediaAnimate(); 
		}
	}

	function mediaAnimate() {
		media.animate({opacity: 1}, 360, captionAnimate);
	}

	function captionAnimate() {
		center.removeClass('mbLoading');
		bottom.animate({opacity: 1}, 240);
	}

	function stop() {
		if (preload) {
			if (mediaType == "inline" && !options.inlineClone) preload.adopt(media.getChildren());	// prevents loss of adopted data
			preload.onload = function(){}; // $empty replacement
		}
		// TODO : à migrer
		// overlay.stop();
		// media.stop();
		// bottom.stop();
		// fx.media.cancel().set(0);
		// fx.bottom.cancel().set(0);
		prevLink.css("display", "none");
		nextLink.css("display", "none");
	}

	function close() {
		if (activeMedia >= 0) {
			if (mediaType == "inline" && !options.inlineClone) preload.adopt(media.getChildren());	// prevents loss of adopted data
			preload.onload = function(){}; // $empty replacement
			media.empty();
			for (var f in fx) fx[f].cancel();
			center.css("display", "none");
			$('#mbOverlay').fadeTo( 360, 0, function() {$(this).css('display', 'none');});
			// fx.overlay.chain(setup).start(0);
		}
		return false;
	}
})(jQuery);

// Mediabox.scanPage = function() {
//	if (Browser.Platform.ios && !(navigator.userAgent.match(/iPad/i))) return;	// this quits the process if the visitor is using a non-iPad iOS device (iPhone or iPod Touch)
	
	// var links = jQuery("a").filter(function(i) {
		// if ( jQuery(this).attr('rel') ) {
			// var patt = new RegExp(/^lightbox/i);
			// return patt.test(jQuery(this).attr('rel'));
		// }
	// });
	
	// links.mediabox({/* Put custom options here */}, null, function(curlink, el) {
		// var rel0 = curlink.rel.replace(/[\[\]|]/gi," ");
		// var relsize = rel0.split(" ");
		// return (curlink == el) || ((curlink.rel.length > 8) && el.rel.match(relsize[1]));
	// });
// };

// jQuery(document).ready(function(){
// Mediabox.scanPage();
// });


// jQuery SWFObject v1.1.1 MIT/GPL @jon_neal
// http://jquery.thewikies.com/swfobject
(function(f,h,i){function k(a,c){var b=(a[0]||0)-(c[0]||0);return b>0||!b&&a.length>0&&k(a.slice(1),c.slice(1))}function l(a){if(typeof a!=g)return a;var c=[],b="";for(var d in a){b=typeof a[d]==g?l(a[d]):[d,m?encodeURI(a[d]):a[d]].join("=");c.push(b)}return c.join("&")}function n(a){var c=[];for(var b in a)a[b]&&c.push([b,'="',a[b],'"'].join(""));return c.join(" ")}function o(a){var c=[];for(var b in a)c.push(['<param name="',b,'" value="',l(a[b]),'" />'].join(""));return c.join("")}var g="object",m=true;try{var j=i.description||function(){return(new i("ShockwaveFlash.ShockwaveFlash")).GetVariable("$version")}()}catch(p){j="Unavailable"}var e=j.match(/\d+/g)||[0];f[h]={available:e[0]>0,activeX:i&&!i.name,version:{original:j,array:e,string:e.join("."),major:parseInt(e[0],10)||0,minor:parseInt(e[1],10)||0,release:parseInt(e[2],10)||0},hasVersion:function(a){a=/string|number/.test(typeof a)?a.toString().split("."):/object/.test(typeof a)?[a.major,a.minor]:a||[0,0];return k(e,a)},encodeParams:true,expressInstall:"expressInstall.swf",expressInstallIsActive:false,create:function(a){if(!a.swf||this.expressInstallIsActive||!this.available&&!a.hasVersionFail)return false;if(!this.hasVersion(a.hasVersion||1)){this.expressInstallIsActive=true;if(typeof a.hasVersionFail=="function")if(!a.hasVersionFail.apply(a))return false;a={swf:a.expressInstall||this.expressInstall,height:137,width:214,flashvars:{MMredirectURL:location.href,MMplayerType:this.activeX?"ActiveX":"PlugIn",MMdoctitle:document.title.slice(0,47)+" - Flash Player Installation"}}}attrs={data:a.swf,type:"application/x-shockwave-flash",id:a.id||"flash_"+Math.floor(Math.random()*999999999),width:a.width||320,height:a.height||180,style:a.style||""};m=typeof a.useEncode!=="undefined"?a.useEncode:this.encodeParams;a.movie=a.swf;a.wmode=a.wmode||"opaque";delete a.fallback;delete a.hasVersion;delete a.hasVersionFail;delete a.height;delete a.id;delete a.swf;delete a.useEncode;delete a.width;var c=document.createElement("div");c.innerHTML=["<object ",n(attrs),">",o(a),"</object>"].join("");return c.firstChild}};f.fn[h]=function(a){var c=this.find(g).andSelf().filter(g);/string|object/.test(typeof a)&&this.each(function(){var b=f(this),d;a=typeof a==g?a:{swf:a};a.fallback=this;if(d=f[h].create(a)){b.children().remove();b.html(d)}});typeof a=="function"&&c.each(function(){var b=this;b.jsInteractionTimeoutMs=b.jsInteractionTimeoutMs||0;if(b.jsInteractionTimeoutMs<660)b.clientWidth||b.clientHeight?a.call(b):setTimeout(function(){f(b)[h](a)},b.jsInteractionTimeoutMs+66)});return c}})(jQuery,"flash",navigator.plugins["Shockwave Flash"]||window.ActiveXObject);