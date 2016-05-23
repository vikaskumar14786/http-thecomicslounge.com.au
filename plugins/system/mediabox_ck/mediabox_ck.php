<?php

/**
 * @copyright	Copyright (C) 2011 Cédric KEIFLIN alias ced1870
 * http://www.joomlack.fr
 * @license		GNU/GPL
 * */
defined('_JEXEC') or die('Restricted access');
jimport('joomla.plugin.plugin');


class plgSystemMediabox_ck extends JPlugin {

	function plgSystemMediabox_ck(&$subject, $config) {
		parent :: __construct($subject, $config);
		
		
	}

	function onAfterDispatch() {

		$mainframe = JFactory::getApplication();
		$document = JFactory::getDocument();
		$doctype = $document->getType();
		
		// si pas en frontend, on sort
		if ($mainframe->isAdmin()) {
			return false;
		}

		// si pas HTML, on sort
		if ($doctype !== 'html') {
			return;
		}
		
		/* Fonction pour gérer le chargement du plugin */

		// recupere l'ID de la page
		// $id = JRequest::getInt('Itemid');
		$input = new JInput();
		$id = $input->get('Itemid', 'int');

		// charge les parametres
		$IDs = explode(",", $this->params->get('pageselect', '0'));

		// test, si on n'est pas bon on sort
		if (!in_array($id, $IDs) && $IDs[0] != 0)
			return false;

		$cornerradius = $this->params->get('cornerradius', '10');
		$shadowoffset = $this->params->get('shadowoffset', '5');
		$overlayopacity = $this->params->get('overlayopacity', '0.7');
		$bgcolor = $this->params->get('bgcolor', '#1a1a1a');
		$overlaycolor = $this->params->get('overlaycolor', '#000');
		$text1color = $this->params->get('text1color', '#999');
		$text2color = $this->params->get('text2color', '#fff');
		$resizeopening = $this->params->get('resizeopening', 'true');
		$resizeduration = $this->params->get('resizeduration', '240');
		$initialwidth = $this->params->get('initialwidth', '320');
		$initialheight = $this->params->get('initialheight', '180');
		$defaultwidth = $this->params->get('defaultwidth', '640');
		$defaultheight = $this->params->get('defaultheight', '360');
		$showcaption = $this->params->get('showcaption', 'true');
		$showcounter = $this->params->get('showcounter', 'true');
		$attribtype = $this->params->get('attribtype', 'className');
		$attribname = $this->params->get('attribname', 'lightbox');

        /* fin de la fonction */

		// loads jQuery
        JHTML::_('jquery.framework',true);

        $document->addStyleSheet( 'plugins/system/mediabox_ck/mediabox_ck/mediaboxck.css' );
		$document->addStyleDeclaration("
			#mbCenter {
	background-color: ".$bgcolor.";
	-webkit-border-radius: ".$cornerradius."px;
	-khtml-border-radius: ".$cornerradius."px;
	-moz-border-radius: ".$cornerradius."px;
	border-radius: ".$cornerradius."px;
	-webkit-box-shadow: 0px ".$shadowoffset."px 20px rgba(0,0,0,0.50);
	-khtml-box-shadow: 0px ".$shadowoffset."px 20px rgba(0,0,0,0.50);
	-moz-box-shadow: 0px ".$shadowoffset."px 20px rgba(0,0,0,0.50);
	box-shadow: 0px ".$shadowoffset."px 20px rgba(0,0,0,0.50);
	/* For IE 8 */
	-ms-filter: \"progid:DXImageTransform.Microsoft.Shadow(Strength=".$shadowoffset.", Direction=180, Color='#000000')\";
	/* For IE 5.5 - 7 */
	filter: progid:DXImageTransform.Microsoft.Shadow(Strength=".$shadowoffset.", Direction=180, Color='#000000');
	}
	
	#mbOverlay {
		background-color: ".$overlaycolor.";
	}
	
	#mbCenter.mbLoading {
		background-color: ".$bgcolor.";
	}
	
	#mbBottom {
		color: ".$text1color.";
	}
	
	#mbTitle, #mbPrevLink, #mbNextLink, #mbCloseLink {
		color: ".$text2color.";
	}
		");
        $document->addScript(JURI::base(true)."/plugins/system/mediabox_ck/mediabox_ck/mediaboxck.min.js");
        // $document->addScript(JURI::base(true)."/plugins/system/mediabox_ck/mediabox_ck/quickie.js");
        $document->addScriptDeclaration("
                    Mediabox.scanPage = function() {
						var links = jQuery('a').filter(function(i) {
							if ( jQuery(this).attr('rel') ) {
								var patt = new RegExp(/^lightbox/i);
								return patt.test(jQuery(this).attr('rel'));
							}
						});
						links.mediabox({
						overlayOpacity : 	".$overlayopacity.",
						resizeOpening : 	".$resizeopening.",
						resizeDuration : 	".$resizeduration.",
						initialWidth : 		".$initialwidth.",
						initialHeight : 	".$initialheight.",
						defaultWidth : 		".$defaultwidth.",
						defaultHeight : 	".$defaultheight.",
						showCaption : 		".$showcaption.",
						showCounter : 		".$showcounter.",
						attribType :		'".$attribtype."',
						playerpath: '".JURI::base(true)."/plugins/system/mediabox_ck/mediabox_ck/NonverBlaster.swf'
						}, null, function(curlink, el) {
							var rel0 = curlink.".$attribtype.".replace(/[[]|]/gi,\" \");
							var relsize = rel0.split(\" \");
							return (curlink == el) || ((curlink.".$attribtype.".length > ".strlen($attribname).") && el.".$attribtype.".match(relsize[1]));
						});
					};
					jQuery(document).ready(function(){ Mediabox.scanPage(); });
					");
        
    }

}