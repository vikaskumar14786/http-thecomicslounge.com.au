<?php
/**
 * JEvents Component for Joomla 1.5.x
 *
 * @version     $Id: jevcfuser.php 1453 2010-11-16 15:27:13Z geraintedwards $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2009 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class JFormFieldJevcfmenu extends JFormField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevcfmenu';

	function fetchElement($name, $value, &$node, $control_name)
	{

		// Must load admin language files
		$lang = JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);

		$db = JFactory::getDBO();

		// assemble menu items to the array
		$options 	= array();
		$options[]	= JHTML::_('select.option', '', '- '.JText::_('Select Item').' -');

		// load the list of menu types
		// TODO: move query to model
		$query = 'SELECT menutype, title' .
				' FROM #__menu_types' .
				' ORDER BY title';
		$db->setQuery( $query );
		$menuTypes = $db->loadObjectList();

		$menu =JFactory::getApplication()->getMenu('site', $options);
		$menuItems = unserialize(serialize(  $menu->getMenu()));
		foreach ($menuItems as &$item) {

			if ($item->component =="com_jevents"){
				$item->name = "*** ".$item->name." ***";
			}
			unset($item);
		 }

		// establish the hierarchy of the menu
		$children = array();

		if ($menuItems)
		{
			// first pass - collect children
			foreach ($menuItems as $v)
			{
				$pt 	= $v->parent;
				$list 	= @$children[$pt] ? $children[$pt] : array();
				array_push( $list, $v );
				$children[$pt] = $list;
			}
		}

		// second pass - get an indent list of the items
		$list = JHTML::_('menu.treerecurse', 0, '', array(), $children, 9999, 0, 0 );

		// assemble into menutype groups
		$n = count( $list );
		$groupedList = array();
		foreach ($list as $k => $v) {
			$groupedList[$v->menutype][] = &$list[$k];
		}

		foreach ($menuTypes as $type)
		{
			$options[]	= JHTML::_('select.option',  $type->menutype, $type->title , 'value', 'text', true );
			if (isset( $groupedList[$type->menutype] ))
			{
				$n = count( $groupedList[$type->menutype] );
				for ($i = 0; $i < $n; $i++)
				{
					$item = &$groupedList[$type->menutype][$i];

					//If menutype is changed but item is not saved yet, use the new type in the list
					if ( JRequest::getString('option', '', 'get') == 'com_menus' ) {
						$currentItemArray = JRequest::getVar('cid', array(0), '', 'array');
						$currentItemId = (int) $currentItemArray[0];
						$currentItemType = JRequest::getString('type', $item->type, 'get');
						if ( $currentItemId == $item->id && $currentItemType != $item->type) {
							$item->type = $currentItemType;
						}
					}

					$disable = strpos($this->attribute('disable'), $item->type) !== false ? true : false;
					$options[] = JHTML::_('select.option',  $item->id, '&nbsp;&nbsp;&nbsp;' .$item->treename, 'value', 'text', $disable );

				}
			}
		}

		return JHTML::_('select.genericlist',  $options, ''.$control_name.$name, 'class="inputbox"', 'value', 'text', $value);

	}

	public function attribute($attr){
		$val = $this->element->attributes()->$attr;
		$val = !is_null($val)?(string)$val:null;
		return $val;
	}


	/**
	 * Magic setter; allows us to set protected values
	 * @param string $name
	 * @return nothing
	 */
	public function setValue($value) {
		$this->value = $value;
	}
}