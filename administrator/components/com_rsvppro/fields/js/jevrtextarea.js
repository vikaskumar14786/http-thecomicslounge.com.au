/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id$
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrtextarea = {
	setvalue: function(id){
		jQuery('#pdv'+id).val( jQuery('#dv'+id).val());
	},
	changeCols:function(id){
		jQuery('#pdv'+id).cols = jQuery('#cols'+id).val();
		jQuery('#dv'+id).cols = jQuery('#cols'+id).val();
	},
	changeRows:function(id){
		jQuery('#pdv'+id).rows = jQuery('#rows'+id).val();
		jQuery('#dv'+id).rows = jQuery('#rows'+id).val();
	}
}