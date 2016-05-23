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

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

class JFormFieldJevcfpaidorfree extends JFormField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'Jevcfpaidorfree';
	
	function fetchElement($name, $value, &$node, $control_name)
	{
		$class = ( $this->element['class'] ? 'class="'.$this->element['class'].'"' : '' );

		// Must load admin language files
		$lang = JFactory::getLanguage();
		$lang->load("com_jevents", JPATH_ADMINISTRATOR);
		
		$options = array ();
		$options[] = JHTML::_('select.option', 1, JText::_("Yes"));
		$options[] = JHTML::_('select.option', 0, JText::_("No"));

		$extramessage  = "";
		// are we re-editing the event?
		if (isset($this->event) && $this->event->ev_id()>0){
			$orders = $this->getOrders(intval($this->attribute("vmproductid",0)), $this->event->ev_id());
			if (count($orders)==0){
				$extramessage  = "<strong style='color:red'>This event is not yet paid for.</strong>";
			}
			else {
				$extramessage  = "<strong style='color:blue'>This event has been paid for.</strong>";
			}
		}
		return $extramessage . JHTML::_('select.radiolist', $options, $control_name.$name, $class, 'value', 'text', $value, $control_name.$name );
	}
	
	function fetchRequiredScript($name, &$node, $control_name) 
	{
		return "JevrRequiredFields.fields.push({'name':'".$control_name.$name."', 'default' :'".$this->attribute('default') ."' ,'reqmsg':'".trim(JText::_($this->attribute('requiredmessage'),true))."'}); ";
	}

	
	public function convertValue($value, $node){
		static $values;
		if (!isset($values)){
			$values =  array();
		}
		if (!isset($values[$this->attribute('name')])){
			$values[$this->attribute('name')]=array();
			$values[$this->attribute('name')][0] = JText::_("No");
			$values[$this->attribute('name')][1] = JText::_("Yes");
		}
		return $values[$this->attribute('name')][$value];
	}
	
	public function constructFilter($node){
		$this->node = $node;
		$this->filterType = str_replace(" ","",$this->attribute("name"));
		$this->filterLabel = is_null($this->attribute("filterlabel"))?$this->attribute("label"):$this->attribute("filterlabel");
		$this->filterNullValue = is_null($this->attribute("filterdefault"))?(is_null($this->attribute("default"))?"":$this->attribute("default")):$this->attribute("filterdefault");
		$this->filter_value = $this->filterNullValue;
		$this->map = "csf".$this->filterType;

		$registry	= JRegistry::getInstance("jevents");
		$this->indexedvisiblefilters = $registry->get("indexedvisiblefilters",false);
		if ($this->indexedvisiblefilters===false) return;
		
		// This is our best guess as to whether this filter is visible on this page.
		$this->visible = in_array("customfield",$this->indexedvisiblefilters);
		
		// If using caching should disable session filtering if not logged in
		$cfg	 = JEVConfig::getInstance();
		$useCache = intval($cfg->get('com_cache', 0));
		$user = JFactory::getUser();
		global $mainframe;
		if (intval(JRequest::getVar('filter_reset',0))){
			JFactory::getApplication()->setUserState( $this->filterType.'_fv_ses', $this->filterNullValue );
			$this->filter_value = $this->filterNullValue;
		}
		// ALSO if this filter is not visible on the page then should not use filter value - does this supersede the previous condition ???
		else if (!$this->visible)
		{
			$this->filter_value =  JRequest::getVar($this->filterType.'_fv', $this->filterNullValue,"request", "int" );
		}
		else {
			$this->filter_value = JFactory::getApplication()->getUserStateFromRequest( $this->filterType.'_fv_ses', $this->filterType.'_fv', $this->filterNullValue );
		}
		$this->filter_value = intval($this->filter_value );

		//$this->filter_value = JRequest::getInt($this->filterType.'_fv', $this->filterNullValue );
		
	}
		
	public function createJoinFilter(){
		if (trim($this->filter_value)==$this->filterNullValue) return "";
		$join =  " #__jev_customfields AS $this->map ON det.evdet_id=$this->map.evdet_id";
		$db = JFactory::getDBO();
		$filter =  "$this->map.name=".$db->Quote($this->filterType)." AND $this->map.value=".$db->Quote($this->filter_value);
		return $join . " AND ". $filter;
	}
	
	public function createFilter(){
		if (trim($this->filter_value)==$this->filterNullValue) return "";
		return "$this->map.id IS NOT NULL";
	}

	public function createFilterHTML(){
		$filterList=array();
		$filterList["title"]="<label class='evdate_label' for='".$this->filterType."_fv'>".JText::_($this->filterLabel)."</label>";
		$name = $this->filterType."_fv";
		$filterList["html"] =  $this->fetchElement($name, $this->filter_value, $this->node, "");

		$name .= $this->filterNullValue;
		$script = "function reset".$this->filterType."_fv(){\$('$name').checked=true;};\n";
		$script .= "try {JeventsFilters.filters.push({action:'reset".$this->filterType."_fv()',id:'".$this->filterType."_fv',value:".$this->filterNullValue."});} catch (e) {}";
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);
		
		return $filterList;
	}

	/*
	 * Any special treatment after saving event?
	 */
	public 	function onAfterSaveEvent($node, $value, $event)
	{
		// A paid for event so must pay for it now unless editing from the backend
		if ($value ==1 && !JFactory::getApplication()->isAdmin()){

			$product = $this->getProduct(intval($this->attribute("vmproductid",0)));

			$cart = $this->getCart();

			$category = intval($this->attribute("vmcatid",0));
			if ($this->addToCart($cart, $product, $category, $event->ev_id)){

				$shoplink = JRoute::_("index.php?page=checkout.index&ssl_redirect=1&option=com_virtuemart". "&Itemid=" . intval($this->attribute("menuid",1)));
				JFactory::getApplication()->redirect($shoplink);
			}
		}

	}

	public function getProduct($pid)
	{

		$db = JFactory::getDBO();

		$sql = "SELECT p.* FROM #__vm_product as p"
				. " \n LEFT JOIN #__vm_product_product_type_xref as xr ON xr.product_id = p.product_id"
				. " \n WHERE p.product_id=".$pid;

		$db->setQuery($sql);

		// Make sure Joomfish doesn't translate this
		$product = $db->loadObject(false);

		return $product;

	}

	public function getOrders($pid, $eventid){
		$db = JFactory::getDBO();

        $sql = "SELECT ord.*, orit.product_attribute FROM #__vm_orders as ord"
                . " \n LEFT JOIN #__vm_order_item as orit ON orit.order_id = ord.order_id"
                . " \n WHERE (orit.order_status='C' OR orit.order_status='S' OR orit.order_status='I') "
				// anyone can pay for it otherwise editors would trigger a new transaction!!
                //. " \n AND ord.user_id=" . $user->id
                . " \n AND orit.product_id=" .  $pid
                . " \n AND orit.product_attribute LIKE ('EventId: " .  $eventid ."<br/>%')"
                . "\n ORDER BY cdate desc";
        $db->setQuery($sql);
		$orders = $db->loadObjectList();
		return $orders;
	}

	private function getCart()
	{
		$cart = array();
		if (array_key_exists("cart", $_SESSION))
		{
			$cart = $_SESSION['cart'];
		}

		$db = JFactory::getDBO();
		$user = JFactory::getUser();
		if ($user->id > 0)
		{
			$db->setQuery("SELECT * FROM #__vm_cart WHERE user_id=" . $user->id);
			$cart = $db->loadObject();

			if ($cart)
			{
				$cart = unserialize($cart->cart_content);
			}
		}

		if (!is_array($cart)){
			$cart = array();
		}
		return $cart;

	}

	private function addToCart($cart, $product, $category, $event_id)
	{
		$matched = false;
		foreach ($cart as $entry)
		{
			if ($entry["product_id"] == intval($product->product_id)
					&& strpos($entry["description"], "EventId:" .$event_id)===0)
			{
				$matched = true;
			}
		}

		// Also make sure there isn't a completed order for this product
		$db = JFactory::getDBO();

		$orders = $this->getOrders($product->product_id, $event_id );
		if (!is_null($orders) && count($orders)>0){
			return false;
		}

		$datamodel =new JEventsDataModel();
		$row = $datamodel->queryModel->getEventById( $event_id, 0,"icaldb");
		list($year,$month,$day) = JEVHelper::getYMD();
		$link = $row->viewDetailLink($year,$month,$day,false);
		$link = JRoute::_($link,false);
		$uri  = JURI::getInstance(JURI::base());
		$root = $uri->toString( array('scheme', 'host', 'port') );
		//$link = "<a href='$root$link'>".$row->title()."</a>";
		//$link = '<a href="'.$root.$link.'">'.$row->title().'</a>';
		//$link = "<a href=\'$root$link\'>".$row->title()."</a>";
		//$link = "<a href='$link'>".$row->title()."</a>";
		$link = $root.$link;
		
		if (!$matched)
		{
			$entry = array();
			$entry["quantity"] = 1;
			$entry["product_id"] = $product->product_id;
			$entry["parent_id"] = intval($product->product_id);
			$entry["category_id"] = intval($category);
			$entry["description"] = "EventId:" .$event_id.";EventLink:".$link;
			$cart[] = $entry;

			$cart["idx"] = count($cart) - (array_key_exists("idx", $cart) ? 1 : 0);
		}
		$_SESSION['cart'] = $cart;

		if ($user->id > 0)
		{
			$date = JFactory::getDate();
			$db->setQuery("UPDATE #__vm_cart SET cart_content = " . $db->Quote(serialize($cart)) . " , last_updated ='" . $date->toSql() . "' WHERE user_id=" . $user->id);
			$db->query();
		}
		return true;
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