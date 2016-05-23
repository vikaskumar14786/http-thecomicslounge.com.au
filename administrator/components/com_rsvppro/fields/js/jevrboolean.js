/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevboolean.php 1569 2009-09-16 06:22:03Z geraint $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2015 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

var jevrboolean = {
	settrue : function(id){
		jQuery('#defaultvalue'+id).val( 1);
		jQuery('#pdv'+1+id).prop('checked',true);
	},
	setfalse : function(id){
		jQuery('#defaultvalue'+id).val( 0);
		jQuery('#pdv'+0+id).prop('checked',true);
	}
}