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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldJevcfuser extends JFormFieldList
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevcfuser';

	protected function getInput()
	{
		// make sure we have a helpful class set to get the width
		if (!$this->element['class'] ){
			$this->element['class'] =" jevminwidth";
		}
		return parent::getInput();
	}

	
	public function convertValue($value, $node){
		if (is_object($value) && isset($value->id)){
			$value = $value->id;
		}
		$value = intval($value);
		if ($value<=0) return "";

		$profileField = $this->attribute('profilefield');

		if ($profileField)
		{
			// Load the profile data from the database.
			$db = JFactory::getDbo();
			$db->setQuery(
				'SELECT profile_key, profile_value FROM #__user_profiles' .
					' WHERE user_id = ' . (int) $value . " AND profile_key LIKE '".$profileField.".%'" .
					' ORDER BY ordering'
			);

			try
			{
				$profile = $db->loadRowList();
			}
			catch (RuntimeException $e)
			{
				$this->_subject->setError($e->getMessage());

				return false;
			}

			$user = JEVHelper::getUser($value);

			foreach($profile as $profileRow)
			{
				$profileValue = substr($profileRow[1], 1, strlen($profileRow[1])-2);
				if($profileValue)
				{
					$profileHtmlArray[] = '<div class="jev_cfuser_'.strtolower($profileRow[0]).'">'
							. '<span class="jev_cfuser_label">'.JText::_(strtoupper('JEV_CFUSER_'.str_replace(".", "_", $profileRow[0]))).'</span>'
							. ' <span class="jev_cfuser_value">'.$profileValue.'</span>'
							. '</div>';
				}
			}

			$profileHtml = '<div class="jev_cfuser_profile"><div class="jev_cfuser_name">'.$user->name.'</div>';
			
			if(isset($profileHtmlArray))
			{
				$profileHtml .= implode('',$profileHtmlArray);
			}

			$profileHtml .= "</div>";
			
			return $profileHtml;
		}
		
		if ($this->attribute('contact'))
		{
			$userdet = JEVHelper::getContact($value);
			$contactlink = "";
			if ($userdet)
			{
				if (isset($userdet->slug) && $userdet->slug )
				{
					$contactlink = JRoute::_('index.php?option=com_contact&view=contact&id=' . $userdet->slug . '&catid=' . $userdet->catslug);
					$contactlink = '<a href="' . $contactlink . '"  target="_blank" >' . $userdet->contactname . '</a>';
					return $contactlink;
				}
			}
		}

		$user = JEVHelper::getUser($value);
		return $user->name;

	}
	
	public function getOptions()
	{
		$db = JFactory::getDBO();

		$sql = "SELECT u.id AS value, u.name AS text  FROM #__users as u";
		$usergroups = $this->attribute("usergroups");
		if (!empty($usergroups))
		{
			$sql .= " LEFT JOIN #__user_usergroup_map as map ON map.user_id=u.id"
					. " WHERE map.group_id IN ($usergroups)";
		}
		$sql .= " ORDER BY u.name asc";

		$db->setQuery($sql);
		$users = $db->loadObjectList();

		$nulluser = new stdClass();
		$nulluser->value = 0;
		$nulluser->text = JText::_("Select User");
		array_unshift($users,$nulluser);
		
		return $users;
		
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