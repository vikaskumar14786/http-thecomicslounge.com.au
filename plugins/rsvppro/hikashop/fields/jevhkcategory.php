<?php


defined('JPATH_BASE') or die;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

$lang = JFactory::getLanguage();
$lang->load("plg_hikashop_rsvppro", JPATH_ADMINISTRATOR);

class JFormFieldJevhkcategory extends JFormFieldList
{ 
	protected $type = 'jevhkcategory';

 
     function getInput() {
        $multiple= ($this->element['multiple'] ? 'multiple="multiple"' : "");
        $size= ($this->element['size'] ? 'size="'.$this->element['size'].'"' : "");

	$name = $this->element["name"];
	
	if(!include_once(JPATH_ADMINISTRATOR.'/components/com_hikashop/helpers/helper.php'))
		return true;
	$categoryclass = hikashop_get('class.category');

        
        $categorylist = $categoryclass->getList("product",0,false);

	$options = array();
	foreach ($categorylist  as $cat){
		$options[] = JHTML::_('select.option',  $cat->category_id, str_repeat("-", $cat->category_depth)." ".$cat->category_name);

	}
	return JHtml::_('select.genericlist', $options, $this->name, 'class="inputbox" size="1" ', 'value',  'text', $this->value);

        

    }



}