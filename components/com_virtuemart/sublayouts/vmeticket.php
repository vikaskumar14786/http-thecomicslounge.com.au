<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Vmeticket
 * @author     vikaskumar <vikaskumar14786@gmail.com>
 * @copyright  Copyright (C) 2016. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

/**
 * Vmeticket model.
 *
 * @since  1.6
 */
class VmeticketModelVmeticket extends JModelAdmin
{
	/**
	 * array of tickets
	 * 
	 * @var array
	 */
	public $tickets = array();
	
	/**
	 * object, perpesenting ticket
	 * 
	 * @var object
	 */
	public $ticket = null;
	
	/**
	 * Id of product type of ticket
	 * 
	 * @var int
	 */
	public $ticket_product_type_id = null;
	
	public $ticket_id;
	
	public $publish;
	public $performer_id;
	public $supporter;
	public $sku;
	public $name;
	public $url;
	public $show_start_time;
	public $show_start_time_min;
	public $show_start_time_meridiem;
	public $dinner_start_time;
	public $dinner_start_time_min;
	public $dinner_start_time_meridiem;
	public $categories;
	public $product_price_net;
	public $currency;
	public $vat_id;
	public $short_desc;
	public $long_desc;
	public $in_stock;
	public $min_purchase_quantity;
	public $max_purchase_quantity;
	public $availability_date;
	public $vmeticket_start;
	public $vmeticket_end;
	public $vmeticket_durations;
	public $vmeticket_admissions;
	public $vmeticket_durations_prices;
	public $vmeticket_admissions_prices;
	public $vmeticket_variable_start;
	public $dates;
	public $vmeticket_dates_prices;		
	public $vmeticket_dates_mealprices;	//added for variable meal price
	public $vmeticket_dates_vipprices;	//added for variable meal price
	public $vmeticket_dates_vipmealprices;	//added for variable meal price
	public $ticket_template;
	public $ticket_type;
	public $download_attribude_id;
	public $file_id;
	public $length;
	public $width;
	public $height;
	public $size_unit;
	public $weight;
	public $weight_unit;
	
	public $vm_tab_prefix = 'vm';
	
	public $rows_count;
	
	public $limitstart;
	
	public $limit;
	
	public $filter;
	
	public $checked;
	
	public $variable_attributes = array();
	
	public $variable_attributes_name = array();
	
	public $common_stock_supply;
	
	public $object;
	
	public $section_price;
	
	public $section_color;
	
	public $owner;
	public $permission;
	public $is_free;
    public $selingstatus;
	public $meals;
	public $choice;
	public $shortintro;
    public $eventticketimg;
    public $cl_embeded;
    public $tv_description;
	public $display_carousel;
    public $display_eslider;
    public $display_calendar;
    public $desert_price;
    


	 public function getForm($data = array(), $loadData = true)
    {
    }
    
	/**
	 * Array of all organisations
	 * 
	 * @var array
	 */
	public $orgs = array();
	
	/**
	 * array of organisation-ticket permissions
	 * 
	 * @var array
	 */
	public $org_tic_perms = array();
    
    function getHallCapacity($objectid){ 
		
		$db =& JFactory::getDBO();
		$query = "SELECT capacity FROM #__vmeticket_object  WHERE id = ".$objectid;
		$db->setQuery($query);
		$capcity= $db->loadResult();
		return $capcity;	
		
		
	}
	
	function __construct($config = array())
	{
		$this->vm_tab_prefix = VMETICKET_VIRTUEMART_TABLE_PREFIX;
		parent::__construct();		
	}
	
	protected function replaceVMTablePrefix ($query) {
		return str_replace("{vm}", $this->vm_tab_prefix, $query);
	} 

	/**
	 * get performer id, name from performer table
	 */
	public function getPerformers()
	{	
			$db =& JFactory::getDBO();
			
			//get the id of product type, defined as ticket
			$query = "SELECT id, performar_title FROM #__performar WHERE published='1' ";		
			$db->setQuery($query);	
   			$list = $db->loadObjectList();
   		
   		return $list;
	}


	/**
	 * return id of ticket product type
	 */
	public function getTicketProductTypeId()
	{
		if (!$this->ticket_product_type_id) {
			$db =& JFactory::getDBO();
		
			//get the id of product type, defined as ticket
			$query = "SELECT product_type_id as id"
					." FROM #__{vm}_product_type "
					." WHERE product_type_name = '".VMETICKET_PRODUCT_TYPE_NAME."' "
					." LIMIT 1";
				
			$query = $this->replaceVMTablePrefix($query);
			$db->setQuery($query);	
   			$product_type = $db->loadObject();
   			$this->ticket_product_type_id = $product_type->id;
		}
   		
   		return $this->ticket_product_type_id;
	}
	
	public function deleteChecked()
	{
		if (is_array($this->checked)) {
			foreach ($this->checked as $check) {
				$this->ticket_id = $check;
				$this->deleteTicket();
			}
		}
	}
	
	/**
	 * returns array of products, which are deffined in ticket format. 
	 * list is filtered and limited
	 */
	public function getListOfTickets () 
	{
		$db =& JFactory::getDBO();
		
   		$product_type_id = $this->getTicketProductTypeId();
   		
		if ($this->limit) {
			$limit = " LIMIT $this->limitstart, $this->limit ";
		} else {
			$limit = " ";
		}
		
		//filter
   		$where = array();
		
		$where[] = " p.product_parent_id = 0 ";

   		if ($this->filter['filter_product_name']) {
   			$where[] = " p.`product_name` LIKE '%".$this->filter['filter_product_name']."%' ";
   		}
		if ($this->filter['filter_sku'] != '') {
			$where[] = " p.`product_sku` = '".$this->filter['filter_sku']."' ";
		}
		if ($this->filter['filter_giftbox']) {
			$where[] = " (pr.`product_publish` = 'Y' AND pr.product_name='GIFTBOX') ";
		}

		if (count($where) > 0) {
			$where = " WHERE ".implode (" AND ", $where);
		} else {
			$where = '';
		}
		
		$orderby = ' ORDER BY `p`.`product_id` DESC ';

   		//get list of products, which are of ticket type
		$query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT p.*" //, pa.attribute_value "
				." FROM #__{vm}_product p "
				." JOIN #__{vm}_product_type_".$product_type_id." pt ON (p.product_id = pt.product_id) "
				." LEFT JOIN #__{vm}_product pr ON (p.product_id = pr.product_parent_id) "
				//." LEFT JOIN #__{vm}_product_attribute pa ON (p.product_id = pa.product_id AND pa.attribute_name = 'download' AND p.product_name = pa.attribute_value ) "
				. $where
				. $orderby
				. $limit;
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$this->tickets = $db->loadObjectList();

		$query = "SELECT FOUND_ROWS() as rows";
		$db->setQuery($query);
		$count = $db->loadObject();
		$this->rows_count = $count->rows;
				
		return $this->tickets;		
	}
	
	public function getTicketSelectBoxesData()
	{
		$db =& JFactory::getDBO();

		//GET ALL ORGANISATIONS LIST
		$query = " SELECT o.id, o.name "
				." FROM #__vmeticket_orgs o "
				." ORDER BY o.name ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);				
		$this->orgs = $db->loadObjectList();
		//var_dump($this->orgs);
		//GET ALL ORGANISATION-TICKET PERMISSIONS
		$query = " SELECT id, name "
				." FROM #__vmeticket_perms "
				." ORDER BY `id` ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);				
		$this->org_tic_perms = $db->loadObjectList();

	}
	
	public function getTicketDetail()
	{
		$db =& JFactory::getDBO();
		
		if (!$this->ticket_id) {
			return false;
		}

		$product_type_id = $this->getTicketProductTypeId();
		
		 $query = "SELECT p.product_id,p.seling_status,p.meals,p.product_thumb_image,p.product_full_image,p.eventticketimg,p.choice,p.shortintro,p.product_publish, p.performer_id, p.is_free, p.supporter, p.product_sku, p.product_name, p.product_url, p.product_s_desc, p.product_desc, p.product_in_stock, p.product_tax_id, p.product_order_levels, p.attribute, p.show_start_time,  p.show_start_time_min, p.show_start_time_meridiem, p.dinner_start_time, p.dinner_start_time_min, p.dinner_start_time_meridiem, p.product_weight, p.product_weight_uom, p.product_length, p.product_width, p.product_height, p.product_lwh_uom ,p.cl_embeded, tv_description, p.display_carousel, p.display_eslider, p.display_calendar, p.desert_price "
				." FROM #__{vm}_product p"
				//." LEFT JOIN #__vm_product_price pp ON (p.product_id = pp.product_id)"
				." WHERE p.product_id=".$this->ticket_id." "
				." LIMIT 1 ";

		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$ticket = $db->loadObject();
		
		$this->is_free = $ticket->is_free;
		$this->publish = $ticket->product_publish;
		$this->performer_id = $ticket->performer_id;
		$this->supporter = $ticket->supporter;
		$this->sku = $ticket->product_sku;
		$this->name = $ticket->product_name;
		$this->url = $ticket->product_url;
		$this->vat_id = $ticket->product_tax_id;
		$this->short_desc = $ticket->product_s_desc;
		$this->long_desc = $ticket->product_desc;
		$this->in_stock = $ticket->product_in_stock;
		
		$this->length = $ticket->product_length;
		$this->width = $ticket->product_width;
		$this->height = $ticket->product_height;
		$this->size_unit = $ticket->product_lwh_uom;
		$this->weight = $ticket->product_weight;
		$this->weight_unit = $ticket->product_weight_uom;
		
		$this->show_start_time = $ticket->show_start_time;
		$this->show_start_time_min = $ticket->show_start_time_min;
		$this->show_start_time_meridiem = $ticket->show_start_time_meridiem;
		$this->dinner_start_time = $ticket->dinner_start_time;
		$this->dinner_start_time_min = $ticket->dinner_start_time_min;
		$this->dinner_start_time_meridiem = $ticket->dinner_start_time_meridiem;
                $this->seling_status = $ticket->seling_status;
                $this->meals = $ticket->meals;
                $this->eventticketimg = $ticket->eventticketimg;
                $this->choice= $ticket->choice;
                $this->cl_embeded= $ticket->cl_embeded;
                $this->tv_description = $ticket->tv_description;
                $this->shortintro = $ticket->shortintro;
                $this->product_thumb_image= $ticket->product_thumb_image;
                $this->product_full_image = $ticket->product_full_image;
                $this->display_carousel = $ticket->display_carousel;
                $this->display_eslider = $ticket->display_eslider;
                $this->display_calendar = $ticket->display_calendar;

                $this->desert_price = $ticket->desert_price;

		$pur_quant = explode(',', $ticket->product_order_levels);
		if (is_array($pur_quant)) {
			if (isset($pur_quant[0])) {
				$this->min_purchase_quantity = $pur_quant[0];
			}
			if (isset($pur_quant[1])) {
				$this->max_purchase_quantity = $pur_quant[1];
			}
		}
		
		$attributes = explode (';', $ticket->attribute);
		if (is_array($attributes)) {
			foreach ($attributes as $atrib) {
				$values = explode (',', $atrib);
				if (is_array($values)) {
					if ($values[0] == VMETICKET_DURATIONS_NAME) {
						unset ($values[0]);
						$this->vmeticket_durations = $values;
					}
					elseif ($values[0] == VMETICKET_ADMISSIONS_NAME) {
						unset ($values[0]);
						$this->vmeticket_admissions = $values;
					}
					elseif ($values[0] == VMETICKET_DATES_NAME) {
						unset ($values[0]);
						$this->vmeticket_variable_start = true;
						$this->dates = $values;
					}
					elseif ($values[0] == VMETICKET_OBJECT_NAME) {
						unset ($values[0]);
						$this->object = $values[1];
					}
					elseif ($values[0] == VMETICKET_SEATS_NAME) {
						
					} else {
						$row = null;
						$name = $values[0];
						unset ($values[0]);
						$row['name'] = $name;
						$row['values'] = $values;
						$this->variable_attributes[] = $row;
					}
				}
			}
			
			/*if (is_array($row)) {
				var_dump($this->variable_attributes);
			}*/
		}

		$query = " SELECT category_id as id "
				." FROM #__{vm}_product_category_xref "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);				
		$cats = $db->loadObjectList();
		if (is_array($cats)) {
			foreach ($cats as $cat) {
				$this->categories[] = $cat->id;
			}
		}
		
		$query = " SELECT product_price, product_currency "
				." FROM #__{vm}_product_price "
				." WHERE product_id='$this->ticket_id' AND shopper_group_id='".VMETICKET_DEFAULT_SHOPPER_GROUP_ID."' "
				." LIMIT 1 ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);				
		$price = $db->loadObject();
		$this->product_price_net = $price->product_price;
		$this->currency = $price->product_currency;
		
		$query = " SELECT vmeticket_start_validity, vmeticket_end_validity, vmeticket_ticket_template, common_stock_supply, owner, permission "
				." FROM #__{vm}_product_type_".VMETICKET_PRODUCT_TYPE_ID." "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);				
		$type = $db->loadObject();
		if ($type->vmeticket_start_validity) {
			$this->vmeticket_start = VmeticketHelper::dateFormat($type->vmeticket_start_validity);
		} else {
			$this->vmeticket_start = '';
		}
		if ($type->vmeticket_end_validity) {
			$this->vmeticket_end = VmeticketHelper::dateFormat($type->vmeticket_end_validity);
		} else {
			$this->vmeticket_end = '';
		}
		if ($type->owner) {
			$this->owner = $type->owner;
		}
		if ($type->permission) {
			$this->permission = $type->permission;
		} else {
			$this->permission = 'all';
		}
		$this->ticket_template = $type->vmeticket_ticket_template;
		$this->common_stock_supply = $type->common_stock_supply;
		
		$query = " SELECT p.`product_publish`, p.product_id FROM #__{vm}_product p "
				." JOIN #__{vm}_product_attribute pa ON (p.`product_id` = pa.`product_id`)"
				." WHERE `product_parent_id`='$this->ticket_id' AND (`attribute_name` = 'downloadable' AND `attribute_value`='false') ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);				
		$downl = $db->loadObject();
		
		if ($downl->product_publish == 'Y') {
			//$this->download_attribude_id = $downl->attribute_id;
			$this->ticket_type = 'giftbox';
		} else {
			$this->ticket_type = 'downloadable';
		}
		
		
		$query = " SELECT file_id FROM #__{vm}_product_files "
				." WHERE file_product_id='$this->ticket_id' AND file_name='".str_replace("'", '"', DS.$this->name)."' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);				
		$file = $db->loadObject();
		if ($file) {
			$this->file_id = $file->file_id;
		}
		
		
		//GENERATE variable start days
		if (!$this->dates) {
			$start_date = VmeticketHelper::dateToTimeStamp($this->vmeticket_start);
			$end_date = VmeticketHelper::dateToTimeStamp($this->vmeticket_end);

			if ($start_date && $end_date) {
				while ($start_date <= $end_date) {
					$dates[] = VmeticketHelper::dateFormat ($start_date). " 00:00";
					$start_date = strtotime("+1 day", $start_date);
				}
			}
		
			$this->dates = $dates;
		}
		
		//GET ALL ORGANISATIONS LIST
		$query = " SELECT o.id, o.name, otp.perm_id "
				." FROM #__vmeticket_orgs o "
				." LEFT JOIN #__vmeticket_org_ticket_perms otp ON (o.id = otp.org_id AND otp.product_id = '$this->ticket_id') "
				." ORDER BY o.name ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);				
		$this->orgs = $db->loadObjectList();
	
		return true;
	}
	
	public function saveTicket() {
		$db =& JFactory::getDBO();		
			
		$product_type_id = $this->getTicketProductTypeId();
		$performerDetails = $this->getPerformerDetailsById($this->performer_id);		
		
                //check SKU ATTRIBUTE
		if (!$this->sku) {
                    JError::raiseWarning( 100, "SKU " . Jtext::_('REQUIRED') );
                    return;
                }
                else {
                    $query = " SELECT product_id "
                            ." FROM #__{vm}_product "
                            ." WHERE (product_sku = '".str_replace("'", '"', $this->sku)."' OR product_sku = '".str_replace("'", '"', $this->sku)."_d' OR product_sku = '".str_replace("'", '"', $this->sku)."_g') "
                            ." AND (product_id <> '$this->ticket_id' AND product_parent_id <> '$this->ticket_id')";
                    $query = $this->replaceVMTablePrefix($query);
                    $db->setQuery($query);
                    $sku_array = $db->loadObjectList();

                    if ($sku_array) {
                        JError::raiseWarning( 100, "SKU " . Jtext::_('UNIQUE') );
                        return;
                    }
		}

		//ticket owner organisation and permission is required
		if (!$this->performer_id) {
                    JError::raiseWarning( 100, Jtext::_('Performer') . " " . Jtext::_('REQUIRED') );
                    return;
		}
		if (!$this->meals) {
                    JError::raiseWarning( 100, Jtext::_('Meals') . " " . Jtext::_('REQUIRED') );
                    return;
		}
		if (!$this->seling_status) {
                    JError::raiseWarning( 100, Jtext::_('Selling status') . " " . Jtext::_('REQUIRED') );
                    return;
		}

		if (!$this->eventticketimg) {
                    JError::raiseWarning( 100, Jtext::_('Event Ticket Image ') . " " . Jtext::_('REQUIRED') );
                    return;
		}

		if (!$this->product_price_net) {
                    JError::raiseWarning( 100, Jtext::_('Product Price (Net) ') . " " . Jtext::_('REQUIRED') );
                    return;
		}
                
                if(!$this->vat_id){
                    JError::raiseWarning( 100, Jtext::_('VAT Id ') . " " . Jtext::_('REQUIRED') );
                    return;
                }

		if (!$this->common_stock_supply) {
                    JError::raiseWarning( 100, Jtext::_('Common Stock Supply ') . " " . Jtext::_('REQUIRED') );
                    return;
		}
		
		if (!$this->vmeticket_start) {
                    JError::raiseWarning( 100, Jtext::_('Ticket validity start date') . " " . Jtext::_('REQUIRED') );
                    return;
		}
		
		if (!$this->vmeticket_end) {
                    JError::raiseWarning( 100, Jtext::_('Ticket validity end date') . " " . Jtext::_('REQUIRED') );
                    return;
		}
                if (!$this->object) {
                    JError::raiseWarning( 100, Jtext::_('Hall Type') . " " . Jtext::_('REQUIRED') );
                    return;
		}
                if (!$this->owner) {
                    JError::raiseWarning( 100, Jtext::_('OWNER') . " " . Jtext::_('REQUIRED') );
                    return;
		}
		if (!$this->permission) {
                    JError::raiseWarning( 100, Jtext::_('PERMISSION') . " " . Jtext::_('REQUIRED') );
                    return;
                }

                /////validating desert price, if desert is available with the dinner then its price must be valid number.
                if(!empty($this->desert_price)){
                    if(!is_numeric($this->desert_price)){
                        JError::raiseWarning(100, Jtext::_('Please enter numeric value for desert price'));
			return;
                    }
                }
        	
        	if (!$this->shortintro) {
                    JError::raiseWarning( 100, Jtext::_('Short Intro') . " " . Jtext::_('REQUIRED') );
                    return;
		}
       	
		if (!$this->cl_embeded) {
                    JError::raiseWarning( 100, Jtext::_('Embebeded Cl Tv Code required') . " " . Jtext::_('REQUIRED') );
                    return;
		}
        
		if ($this->vmeticket_variable_start) {
                    if (is_array($this->dates)) {
                        foreach ($this->dates as $key => $option) {
                            //$datum = date("Y-m-d  G:i:s", time());

                            //check date time string format
                            if (strtotime($option)) {
                                if(!preg_match ("/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-1][0-9]|2[0-3]):[0-5][0-9]$/", $option)) {
                                    JError::raiseWarning( 100, Jtext::_('VARIABLE_START_DAY_FORMAT_ERROR'));
                                    return;
                                }
                            }
                            else {
                                JError::raiseWarning( 100, Jtext::_('VARIABLE_START_DAY_FORMAT_ERROR'));
                                return;
                            }

                            $index=strpos($option, "[");
                            if (!$index) $index = strlen($option);
                            $day = trim(substr($option, 0, $index));
                            //$date = explode ("-", $day);
                            if ($day < $this->vmeticket_start." 00:00" || $day > $this->vmeticket_end." 23:59") {
                                //echo "document.getElementById('event').dates[$i] = new Date($date[0]," . ($date[1] - 1) .",$date[2]); ";
                                
                                JError::raiseWarning( 100, Jtext::_('VARIABLE_START_DAY_RANGE_ERROR'));
                                return;
			    }
                        }
                    }
		}
		//CONVERT ATTRIBUTES TO STRING FORMAT
		if (is_array($this->dates) && $this->dates[0] != '') {
                    //$dates  = VMETICKET_DATES_NAME.",";
                    $i = 0;
                    foreach ($this->dates as $date) {
                        $d = $date;
                        //modified to add variable price for meals
                        if ($this->vmeticket_dates_prices[$i] != "" || $this->vmeticket_dates_mealprices[$i] != "") {
                                $d .= "[".$this->vmeticket_dates_prices[$i]."|".$this->vmeticket_dates_mealprices[$i]."|".$this->vmeticket_dates_vipprices[$i]."|".$this->vmeticket_dates_vipmealprices[$i]."]";
                        }
                        $dates[] = $d;
                        $i++;
                    }
                    $this->dates = $dates;
                    sort($this->dates);
                    //$attributes[] = $durations.implode(',', $durs);
		}
		if (is_array($this->vmeticket_durations) && $this->vmeticket_durations[0] != '') {
                    $durations  = VMETICKET_DURATIONS_NAME.",";
                    $i = 0;
                    foreach ($this->vmeticket_durations as $dur) {
                        $d = $dur;
                        //var_dump($_POST);
                        if ($this->vmeticket_durations_prices[$i] != "") {
                            $d .= "[".$this->vmeticket_durations_prices[$i]."]";
                        }

                        $durs[] = $d;
                        $i++;
                    }
                    $attributes[] = $durations.implode(',', $durs);
		}
		if (is_array($this->vmeticket_admissions) && $this->vmeticket_admissions[0] != '') {
                    $admissions  = VMETICKET_ADMISSIONS_NAME.",";
                    $i = 0;
                    foreach ($this->vmeticket_admissions as $adm) {
                        $a = $adm;
                        if ($this->vmeticket_admissions_prices[$i]) {
                            $a .= "[".$this->vmeticket_admissions_prices[$i]."]";
                        }
                        $adms[] = $a;
                        $i++;
                    }
                    $attributes[] = $admissions.implode(',', $adms);
		}

		if (is_array($this->section_price)) {
                    $seats[]  = VMETICKET_SEATS_NAME;
                    $i = 0;
                    //echo $this->object;die;
                    if($this->object) {
                        $query = " SELECT id, seats "
                                ." FROM #__vmeticket_section "
                                ." WHERE object_id = '".$this->object."' ";
                        $query = $this->replaceVMTablePrefix($query);
                        $db->setQuery($query);
                        $sections = $db->loadObjectList();
                    }

                    //var_dump($sections);
                    if (is_array($sections)) {
                            foreach ($sections as $sec) {
                                    $s = explode (",", $sec->seats);
                                    foreach ($s as $seat_num) {
                                            $val = $seat_num;
                                            if ($this->section_price[$sec->id]) {
                                                    $val .= "[".$this->section_price[$sec->id]."]";
                                            }
                                            $seats[] = $val;
                                    }		 	
                            }
                            $attributes[] = implode(',', $seats);
                    }
		}
		if ($this->vmeticket_variable_start) {
			$save_dates = true;;
			if (!$this->vmeticket_start) {
				//JError::raiseWarning( 100, Jtext::_('TICKET_VALIDITY_START') . " " . Jtext::_('REQUIRED') );
				//return;
				$save_dates = false;
			}
			if (!$this->vmeticket_end) {
				//JError::raiseWarning( 100, Jtext::_('TICKET_VALIDITY_END') . " " . Jtext::_('REQUIRED') );
				//return;
				$save_dates = false;
			}
			
			$start_date = explode ("-", $this->vmeticket_start);
			$end_date = explode ("-", $this->vmeticket_end);
			
			if ($this->vmeticket_start && !checkdate($start_date[1], $start_date[2], $start_date[0])) {
				JError::raiseWarning( 100, Jtext::_('TICKET_VALIDITY_START') . " " . Jtext::_('WRONG_DATE_FORMAT') );
				return;
			}
			if ($this->vmeticket_end && !checkdate($end_date[1], $end_date[2], $end_date[0])) {
				JError::raiseWarning( 100, Jtext::_('TICKET_VALIDITY_END') . " " . Jtext::_('WRONG_DATE_FORMAT') );
				return;
			}

			$start_date = VmeticketHelper::dateToTimeStamp($this->vmeticket_start);
			$end_date = VmeticketHelper::dateToTimeStamp($this->vmeticket_end);
            
			
			if ($end_date && $start_date && $end_date < $start_date) {
				JError::raiseWarning( 100, Jtext::_('TICKET_VALIDITY_END')." ".Jtext::_('MUST_BE_LOWER')." ".Jtext::_('TICKET_VALIDITY_START'));
				return;
			}
			
			
			if ($save_dates) {
				if (!$this->dates) {
					while ($start_date <= $end_date) {
						$this->dates[] = VmeticketHelper::dateFormat ($start_date)." 00:00";
						$start_date = strtotime("+1 day", $start_date);
					}
				}
				$vmeticket_dates = VMETICKET_DATES_NAME.",";
				$vmeticket_dates .= implode(",", $this->dates);
				$attributes[] = $vmeticket_dates;
			}
			//var_dump($dates);
		}
		if ($this->ticket_template == '') {
			JError::raiseWarning( 100, Jtext::_('TICKET_TEMPLATE')." ".Jtext::_('REQUIRED'));
			return;
		}

		if (is_array($this->variable_attributes_name) AND count($this->variable_attributes_name) > 0) {
			foreach ($this->variable_attributes_name as $key => $name) {
				$attrib = $name;
				$vals = null;
				if (is_array($_POST['variable_attributes_'.$key])) {
					foreach ($_POST['variable_attributes_'.$key] as $index => $value){
						$val = $value;
						if (($_POST['variable_attributes_'.$key.'_prices'][$index])) {
							$val .= "[".$_POST['variable_attributes_'.$key.'_prices'][$index]."]";
						}
						$vals[] = $val;
					}					
				}
				if (is_array($vals)) {
					$attributes[] = $attrib.",".implode(",", $vals);
				}
			}
		}

		if ($this->object) {
			$attributes[] = VMETICKET_OBJECT_NAME.','.$this->object;
		}
        
		//IF NEW TICKET CREATED, GET HIS ID, OR USE EXISTING TICKET ID
                $product_id = $this->ticket_id+1;
                $order_count = 0;
		$query = "SELECT count(order_id) as total FROM `jos_vm_order_item` WHERE product_id= $product_id";
		$db->setQuery($query);
		$db->query();
		$order_count = $db->loadAssoc();
                if($order_count[total] <= 0){       
                    $numberOfDay = $end_date-$start_date;
                    $numberOfDay =  floor($numberOfDay/(60*60*24));
                    $numberOfDay = $numberOfDay + 1;
                    $capcity= $this->getHallCapacity($this->object);
                    $this->in_stock = $numberOfDay* $capcity;
                }
		//var_dump($attributes);die();
		
		if (is_array($attributes)) {
			$attributes = implode (";", $attributes);
		}

		if (is_array($this->supporter)) {
			$supporterStr = implode (",", $this->supporter);
		}		
		
		//SAVE VALUES INTO PRODUCT TABLE
		if ($this->ticket_id) {
			 $query = "UPDATE #__{vm}_product SET ";
		} else {
			 $query = "REPLACE INTO #__{vm}_product SET "; 
			 $query.= " cdate='".time()."', ";
		}
		if(!$this->vmeticket_variable_start){
                    $this->publish=0;
                }
		$query.= " product_publish='$this->publish', "
                        ." performer_id='$this->performer_id', "
                        ." supporter='$supporterStr', "
                        ." product_sku='".str_replace("'", '"', $this->sku)."', "
                        ." product_name='".addslashes($performerDetails->performar_title)."', ";
                
                $query.= " product_thumb_image='resized/$this->eventticketimg', "
                        ." product_full_image='$this->eventticketimg', ";
                
                $query.=   " product_url='$this->url', "
                            ." product_s_desc='".addslashes($this->short_desc)."', "
                            ." product_desc='".addslashes($performerDetails->performar_description)."', "
                            ." product_in_stock='$this->in_stock', "
                            ." product_tax_id='$this->vat_id', "
                            ." product_order_levels='".$this->min_purchase_quantity.",".$this->max_purchase_quantity."', "
                            ." attribute='".str_replace("'", '"', $attributes)."', "
                            ." product_weight='$this->weight', "
                            ." product_length='$this->length', "
                            ." product_width='$this->width', "
                            ." is_free='$this->is_free', "
                            ." show_start_time='$this->show_start_time', "
                            ." show_start_time_min='$this->show_start_time_min', "
                            ." show_start_time_meridiem='$this->show_start_time_meridiem', "
                            ." dinner_start_time='$this->dinner_start_time', "
                            ." dinner_start_time_min='$this->dinner_start_time_min', "
                            ." dinner_start_time_meridiem='$this->dinner_start_time_meridiem', "
                            ." seling_status='$this->seling_status', "
                            ." meals='$this->meals', "
                            ." shortintro='".addslashes($this->shortintro)."', "
                            ." choice='$this->choice', "
                            ." eventticketimg='$this->eventticketimg', "  
                            ." cl_embeded='$this->cl_embeded', "
                            ." tv_description='$this->tv_description', "                 
                            ." display_carousel='$this->display_carousel', "
                            ." display_eslider='$this->display_eslider', "
                            ." display_calendar='$this->display_calendar', "
                            ." product_height='$this->height' ";

                //////////if we have desert price then insert into db.
                //if($this->desert_price){
                    $query.=", desert_price='$this->desert_price'";
                //}                    
                                
				
                if ($this->size_unit != '') {
                        $query .= ", product_lwh_uom='$this->size_unit' ";
                }
                if ($this->weight_unit != '') {
                        $query .= ", product_weight_uom='$this->weight_unit' ";
                }
                if ($this->object) {
                        $query .= ", quantity_options='hide,0,0,1' ";
                }

		if ($this->ticket_id) {
			$query .= " WHERE product_id='$this->ticket_id' ";
		}
		//echo "query $query";
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//IF NEW TICKET CREATED, GET HIS ID, OR USE EXISTING TICKET ID
		$query = "SELECT LAST_INSERT_ID()";
		$db->setQuery($query);
		$db->query();
		$ret = $db->loadAssoc();
		
		if ($this->ticket_id) {
                    $id = $this->ticket_id;
		}
                else {
                    $id = $ret['LAST_INSERT_ID()'];
		}

		if (is_array($this->supporter)) {
			$supporterStr = implode (",", $this->supporter);
		}
		
		//insert ticket items (child tickets - downlodable & giftbox)
		$download_id = 0;
		$giftbox_id = 0;
		if (!$this->ticket_id) {
			//downloadable item			

			$query = "REPLACE INTO #__{vm}_product SET "; 
			$query.= " product_name='DOWNLOADABLE', ";
			$query.= " performer_id='$this->performer_id', ";
			$query.= " supporter='$supporterStr', ";	


                        $query.= " product_thumb_image='resized/$this->eventticketimg', ";  
                        $query.= " product_full_image='$this->eventticketimg', ";
            
			$query.= " product_desc='".addslashes($performerDetails->performar_description)."', ";
			$query.= " product_sku='".str_replace("'", '"', $this->sku)."_d', ";
			$query.= " product_publish='Y', ";			
			$query.= " product_parent_id='$id', ";
			$query.= " is_free='$this->is_free', ";
			$query.=" show_start_time='$this->show_start_time', ";
			$query.=" show_start_time_min='$this->show_start_time_min', ";
			$query.=" show_start_time_meridiem='$this->show_start_time_meridiem', ";
			$query.=" dinner_start_time='$this->dinner_start_time', ";
			$query.=" dinner_start_time_min='$this->dinner_start_time_min', ";
                        $query.=" seling_status='$this->seling_status', ";
                        $query.=" meals='$this->meals', ";
                        $query.=" shortintro='".addslashes($this->shortintro)."', ";
                        $query.=" choice='$this->choice', ";
                        $query.=" eventticketimg='$this->eventticketimg', ";
                        $query.=" tv_description= '".addslashes($this->tv_description)."',";
            
            

			$query.=" dinner_start_time_meridiem='$this->dinner_start_time_meridiem', ";
			$query.= "product_tax_id='$this->vat_id' ";
            
            
			
			if ($this->common_stock_supply == 1) {		
				$query.=", product_in_stock='$this->in_stock' ";
			}
			
			if ($this->object) {
					$query .= ", quantity_options='hide,0,0,1' ";
			}

                        //////////if we have desert price then insert into db.
                        //if($this->desert_price){
                            $query.=", desert_price='$this->desert_price'";
                        //}                        
                        
                        
			$query = $this->replaceVMTablePrefix($query);
			$db->setQuery($query);
			$db->query();
			
			$query = "SELECT LAST_INSERT_ID()";
			$db->setQuery($query);
			$db->query();
			$ret = $db->loadAssoc();
		
			$download_id = $ret['LAST_INSERT_ID()'];
			
			//giftbox item
			$query = "REPLACE INTO #__{vm}_product SET "; 
			$query.= " product_name='GIFTBOX', ";
			$query.= " performer_id='$this->performer_id', ";
			$query.= " supporter='$supporterStr', ";


                        $query.= " product_thumb_image='resized/$this->eventticketimg', ";
			$query.= " product_full_image='$this->eventticketimg', ";

			//if($performerDetails->performar_image)
			//{
			//$query.= " product_thumb_image='resized/$performerDetails->performar_image', ";
			//$query.= " product_full_image='$performerDetails->performar_image', ";
			//}
			$query.= " product_desc='".addslashes($performerDetails->performar_description)."', ";
			$query.= " product_sku='".str_replace("'", '"',$this->sku)."_g', ";
			$query.= " product_publish='Y', ";			
			$query.= " product_parent_id='$id', ";
			$query.= " is_free='$this->free', ";
			$query.=" show_start_time='$this->show_start_time', ";
			$query.=" show_start_time_min='$this->show_start_time_min', ";
			$query.=" show_start_time_meridiem='$this->show_start_time_meridiem', ";
			$query.=" dinner_start_time='$this->dinner_start_time', ";
			$query.=" dinner_start_time_min='$this->dinner_start_time_min', ";
                        $query.=" seling_status='$this->seling_status', ";
                        $query.=" meals='$this->meals', ";
                        $query.=" shortintro='".addslashes($this->shortintro)."', ";
                        $query.=" choice='$this->choice', ";
                        $query.=" eventticketimg='$this->eventticketimg', ";
                        $query.=" tv_description='".addslashes($this->tv_description)."', ";

			$query.=" dinner_start_time_meridiem='$this->dinner_start_time_meridiem', ";			
			$query.= "product_tax_id='$this->vat_id' ";
			
			if ($this->common_stock_supply == 1) {
                            $query.=", product_in_stock='$this->in_stock' ";
			}
			
			if ($this->object) {
                            $query .= ", quantity_options='hide,0,0,1' ";
			}

                        //////////if we have desert price then insert into db.
                        //if($this->desert_price){
                            $query.=", desert_price='$this->desert_price'";
                        //}                       
                        
                        
			$query = $this->replaceVMTablePrefix($query);
			$db->setQuery($query);
			$db->query();
			
			$query = "SELECT LAST_INSERT_ID()";
			$db->setQuery($query);
			$db->query();
			$ret = $db->loadAssoc();
		
			$giftbox_id = $ret['LAST_INSERT_ID()'];
			
			
			//insert data in jcalpro event table 			
			/*Add entry in event table Alw Additional code jan 17*/					
			$startTime = $this->vmeticket_start;
			$endTime =   $this->vmeticket_end;
			$performerDetails->performar_description = addslashes($performerDetails->performar_description);
                        $performerDetails->performar_title = addslashes($performerDetails->performar_title);
			if($startTime != "" && $endTime != "") {
                            if($this->publish == 'Y' && $this->display_calendar==1 ){$cal_publish = 1;}else{ $cal_publish = 0;}

				$query = "INSERT INTO `jos_jcalpro2_events` (`extid`, `common_event_id`, `cal_id`, `rec_id`, `detached_from_rec`, `owner_id`, `title`, `description`, `contact`, `url`, `registration_url`, `email`, `picture`, `cat`, `day`, `month`, `year`, `approved`, `private`, `start_date`, `end_date`, `recur_type`, `recur_val`, `recur_end_type`, `recur_count`, `recur_until`, `rec_type_select`, `rec_daily_period`, `rec_weekly_period`, `rec_weekly_on_monday`, `rec_weekly_on_tuesday`, `rec_weekly_on_wednesday`, `rec_weekly_on_thursday`, `rec_weekly_on_friday`, `rec_weekly_on_saturday`, `rec_weekly_on_sunday`, `rec_monthly_period`, `rec_monthly_type`, `rec_monthly_day_number`, `rec_monthly_day_list`, `rec_monthly_day_order`, `rec_monthly_day_type`, `rec_yearly_period`, `rec_yearly_on_month`, `rec_yearly_on_month_list`, `rec_yearly_type`, `rec_yearly_day_number`, `rec_yearly_day_order`, `rec_yearly_day_type`, `last_updated`, `published`, `checked_out`, `checked_out_time`, `event_hall`,`event_id`,`performar_id`) 
                          VALUES "."('',	'',	1,	0,	0,	62,".
				 "'$performerDetails->performar_title','$performerDetails->performar_description','$performerDetails->performar_title',	'',	'',	'',	'',	1,	14,	1,	2012,	1,	0,	'$startTime',	'$endTime',	NULL,	0,	1,	2,	'',	0,	1,	1,	0,	0,	0,	0,	0,	0,	0,	1,	0,	1,	'',	1,	0,	1,	0,	'',	0,	1,	1,	0,	$cal_publish,	1,	0,	'',0,$id,$this->performer_id);";	
				$db->setQuery($query);	
                            if (!$db->query()) {
                                    echo "<script> alert('".$db->getErrorMsg(true)
                                            ."'); window.history.go(-1); </script>\n";
                            }
			}
			/*Custom code end here */
		
			
		}
                else {

                    $performerDetails->performar_description = addslashes($performerDetails->performar_description);
                    $this->shortintro = addslashes($this->shortintro);
                    $this->tv_description = addslashes($this->tv_description);
                    $query = " SELECT p.product_id FROM #__{vm}_product p "
                            ." JOIN #__{vm}_product_attribute pa ON (p.`product_id` = pa.`product_id`)"
                            ." WHERE `product_parent_id`='$this->ticket_id' AND (`attribute_name` = 'downloadable' AND `attribute_value`='false') ";
				
                    $query = $this->replaceVMTablePrefix($query);
                    $db->setQuery($query);				
                    $giftbox = $db->loadObject();
				
                    //set child stock supply, when common stock supply is enabled
                    if ($this->common_stock_supply == 1) {				
                            $query = " UPDATE #__{vm}_product "
                                            ." SET "		
                                            ." product_in_stock='$this->in_stock' "
                                            ." WHERE product_id='$this->ticket_id' OR product_parent_id='$this->ticket_id' ";
                            
                            $query = $this->replaceVMTablePrefix($query);
                            $db->setQuery($query);
                            $db->query();	
                    }

                    if ($this->ticket_type == 'giftbox') {
                            $query = " UPDATE #__{vm}_product " 
                                            ." SET `product_publish`='Y' "
                                            ." WHERE `product_id`='$giftbox->product_id' ";
                                            
                            $query = $this->replaceVMTablePrefix($query);
                            $db->setQuery($query);
                            $db->query();	
                    } else {				
                            $query = " UPDATE #__{vm}_product " 
                                            ." SET `product_publish`='N' "
                                            ." WHERE `product_id`='$giftbox->product_id' ";
                                            
                            $query = $this->replaceVMTablePrefix($query);
                            $db->setQuery($query);
                            $db->query();
                    }

                    if ($this->object) {
                            $query = " UPDATE #__{vm}_product"
                                            ." SET quantity_options='hide,0,0,1' "
                                            ." WHERE `product_parent_id`='$id' ";
                                            
                            $query = $this->replaceVMTablePrefix($query);
                            $db->setQuery($query);
                            $db->query();
                    }

                    //update giftbox SKU
                    $query = " UPDATE #__{vm}_product"
                                    ." SET product_sku='".str_replace("'", '"', $this->sku)."_g' "
                                    ." WHERE `product_id`='$giftbox->product_id' ";
                                            
                    $query = $this->replaceVMTablePrefix($query);
                    $db->setQuery($query);
                    $db->query();

                    //update downloadable child SKU
                    $query = " UPDATE #__{vm}_product"
                            ." SET product_sku='".str_replace("'", '"', $this->sku)."_d', "
                            ." `performer_id`='$this->performer_id', ";

			//if($performerDetails->performar_image)
			//{
			//$query .= " `product_thumb_image`='resized/$performerDetails->performar_image', "
			//				." `product_full_image`='$performerDetails->performar_image', ";
			//}
            
                    $query.= " product_thumb_image='resized/$this->eventticketimg', ";
                    $query.= " product_full_image='$this->eventticketimg', ";
                    
                    $query.=" `show_start_time`='$this->show_start_time', ";
                    $query.=" `is_free`='$this->is_free', ";
                    $query.=" `show_start_time_min`='$this->show_start_time_min', ";
                    $query.=" `show_start_time_meridiem`='$this->show_start_time_meridiem', ";
                    $query.=" `dinner_start_time`='$this->dinner_start_time', ";
                    $query.=" `dinner_start_time_min`='$this->dinner_start_time_min', ";
                    $query.=" `dinner_start_time_meridiem`='$this->dinner_start_time_meridiem', ";
                    $query.=" seling_status='$this->seling_status', ";
                    $query.=" meals='$this->meals', ";
                    $query.=" shortintro='$this->shortintro', ";
                    $query.=" choice='$this->choice', ";
                    $query.=" eventticketimg='$this->eventticketimg', ";
                    $query.=" tv_description='$this->tv_description', ";
                    
                    //////////if we have desert price then insert into db.
                    //if($this->desert_price){
                        $query.=" desert_price='$this->desert_price', ";
                    //}

                    $query .= " `product_desc`='$performerDetails->performar_description', "
                            ." `supporter`='$supporterStr' "
                            ." WHERE `product_parent_id`='$id' AND `product_name`='DOWNLOADABLE' ";

                    $query = $this->replaceVMTablePrefix($query);
                    
                    $db->setQuery($query);
                    $db->query();

                    //UPDATE DOWNLOADABLE & GIFTBOX PRODUCT PRICE
                    $query = " UPDATE #__{vm}_product_price "
                            ." SET "
                            ." product_price='$this->product_price_net', product_currency='$this->currency', shopper_group_id='".VMETICKET_DEFAULT_SHOPPER_GROUP_ID."', mdate='".time()."'";

                    $query .= " WHERE `product_id` IN (SELECT product_id from #__{vm}_product WHERE product_parent_id='$id')";

                    //echo "query download ( ".$query." )";
                    $query = $this->replaceVMTablePrefix($query);
                    $db->setQuery($query);
                    $db->query();
                    
                    //die('testing');
                    $query = $this->replaceVMTablePrefix($query);
                    $db->setQuery($query);
                    $db->query();

                    //save product tax, performer_id 
                    $query = " UPDATE #__{vm}_product "
                            ." SET "
                            ." `product_tax_id`='$this->vat_id', "
                            ." `performer_id`='$this->performer_id', ";

                    $query.= " product_thumb_image='resized/$this->eventticketimg', ";
                    $query.= " product_full_image='$this->eventticketimg', ";

			//if($performerDetails->performar_image)
			//{
			//$query .=" `product_thumb_image`='resized/$performerDetails->performar_image', "
			//				." `product_full_image`='$performerDetails->performar_image', ";
			//}
			
                    $query.=" `show_start_time`='$this->show_start_time', ";
                    $query.=" `is_free`='$this->is_free', ";
                    $query.=" `show_start_time_min`='$this->show_start_time_min', ";
                    $query.=" `show_start_time_meridiem`='$this->show_start_time_meridiem', ";
                    $query.=" `dinner_start_time`='$this->dinner_start_time', ";
                    $query.=" `dinner_start_time_min`='$this->dinner_start_time_min', ";
                    $query.=" `dinner_start_time_meridiem`='$this->dinner_start_time_meridiem', ";
                    
                    //////////if we have desert price then insert into db.
                    //if($this->desert_price){
                        $query.=" `desert_price`='$this->desert_price', ";
                    //}

                    $query .=" `product_desc`='$performerDetails->performar_description', "
                            ." `supporter`='$supporterStr' "
                            ." WHERE product_parent_id='$this->ticket_id' ";
  

                    $query = $this->replaceVMTablePrefix($query);

                    $db->setQuery($query);
                    $db->query();
			
			
                    //Update data in jcalpro event table 			
                    /*Update entry in event table Alw Additional code jan 17*/					
                    $startTime = $this->vmeticket_start;
                    $endTime =   $this->vmeticket_end;
                    $performerDetails->performar_description = addslashes($performerDetails->performar_description);
                    $performerDetails->performar_title = addslashes($performerDetails->performar_title);
                    if($startTime != "" && $endTime != "") {
                        if($this->publish == 'Y' && $this->display_calendar==1){$cal_publish = 1;}else{ $cal_publish = 0;}
                        $query = " UPDATE #__jcalpro2_events "
                                ." SET "
                                ." `title`='$performerDetails->performar_title', "
                                ." `description`='$performerDescription', ";			

                        $query.=" `published`=$cal_publish,";
                        $query.=" `contact`='$performerDetails->performar_title', ";
                        $query.=" `start_date`='$startTime', ";
                        $query.=" `end_date`='$endTime', ";
                        $query.=" `event_id`='$this->ticket_id', ";
                        $query.=" `performar_id`='$this->performer_id' "
                                ." WHERE event_id='$this->ticket_id' ";						
						
                        $db->setQuery($query);
                        $db->query();
                        if (!$db->query()) {
                            echo "<script> alert('".$db->getErrorMsg(true)
                        	."'); window.history.go(-1); </script>\n";
			}
                    }
			/*Custom code end here */

                }
		
		//SAVE TICKET CATEGORIES
		$cats = implode (",", $this->categories);
		
		$query = " SELECT * FROM #__{vm}_product_category_xref "
				." WHERE product_id='$id' ";
		
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		
		$query = " DELETE FROM #__{vm}_product_category_xref "
				." WHERE product_id='$id' ";
		
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		if (is_array($this->categories) && ($this->categories[0] != '')) {
			foreach ($this->categories as $ca) {
				$c = null;
				$c->category_id = $ca;
				$c->product_id = $id;
				$c->product_list = 1;
			
				foreach ($rows as $row) {
					if ($row->category_id == $ca) {
						$c->product_list = $row->product_list;
					}
				}
			
				$cts[] = $c;
			}

			$query = " INSERT INTO #__{vm}_product_category_xref "
					." VALUES ";
				
			foreach ($cts as $ct) {
				$q[] = "('".$ct->category_id."', '".$ct->product_id."', '".$ct->product_list."')";
			}
		
			$query .= implode (", ", $q);
		
			$query = $this->replaceVMTablePrefix($query);
			$db->setQuery($query);
			$db->query();
		}
		

		//SAVE TICKET VALIDITY PARAMETERS
                if($order_count[total]<=0){
                    $vmeticket_start_validity = VmeticketHelper::dateToTimeStamp($this->vmeticket_start); 	
                    $vmeticket_end_validity = VmeticketHelper::endOfDayToTimeStamp($this->vmeticket_end);
                    
                    $query = " REPLACE INTO #__{vm}_product_type_".$product_type_id." "
                                    ." VALUES ('$id', '$vmeticket_start_validity', '$vmeticket_end_validity', '".str_replace("'", '"',$this->ticket_template)."', '$this->common_stock_supply', '$this->owner', '$this->permission')";
    
                    $query = $this->replaceVMTablePrefix($query);
                    $db->setQuery($query);
                    $db->query();
                }
		
		//SAVE PRODUCT PRICE
		if ($this->ticket_id) {
			$query = " UPDATE #__{vm}_product_price "
					." SET "
					." product_id='$id', product_price='$this->product_price_net', product_currency='$this->currency', shopper_group_id='".VMETICKET_DEFAULT_SHOPPER_GROUP_ID."', mdate='".time()."'";
		} else {
			$query = " INSERT INTO #__{vm}_product_price "
				  	." (product_id, product_price, product_currency, shopper_group_id, cdate, mdate, product_price_vdate, product_price_edate) "
				 	." VALUES "
				 	." ('$id',  '$this->product_price_net', '$this->currency', '".VMETICKET_DEFAULT_SHOPPER_GROUP_ID."', '".time()."', '".time()."', '0', '0')";
		}

		if ($this->ticket_id) {
			$query .= " WHERE product_id='$id' AND shopper_group_id='".VMETICKET_DEFAULT_SHOPPER_GROUP_ID."' ";
		}
		
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//IF CREATE NEW TICKET, ADD PRODUCT TYPE
		if (!$this->ticket_id) {
			$query = " INSERT INTO #__{vm}_product_product_type_xref "
					." VALUES "
					." ('$id', '$product_type_id') ";
		}
		
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		
		
		//DOWNLOADABLE CHILD PRODUCT (item) MUST HAVE DOWNLOADABLE FILE
		if ($download_id != 0) { //product was just created
			$query = " REPLACE INTO #__{vm}_product_attribute "
						." (`product_id`, `attribute_name`, `attribute_value`) "
						." VALUES "
						." ('$download_id', 'download', '".str_replace("'", '"', $this->name)."') ";
					
			$query = $this->replaceVMTablePrefix($query);
			$db->setQuery($query);
			$db->query();
		}
		
		/*$query = " SELECT attribute_id FROM #__vm_product_attribute "
				." WHERE product_id='$downloadable_id' AND attribute_name='download' ";
				
		$db->setQuery($query);				
		$downl = $db->loadObject();
		if ($downl) {
			$this->download_attribude_id = $downl->attribute_id;
		}
		
		if ($this->ticket_type == 'downloadable') {
			$query = " REPLACE INTO #__{vm}_product_attribute "
					." VALUES "
					." ('$this->download_attribude_id', '$id', 'download', '$this->name') ";
		} else {			
			$query = " DELETE FROM #__{vm}_product_attribute "
					." WHERE product_id='$id' AND attribute_name='download' ";
		}	
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();*/
		
		//set downloadable attribute value to child items
		if ($download_id != 0) { //product was just created
			$query = " REPLACE INTO #__{vm}_product_attribute "
						." (`product_id`, `attribute_name`, `attribute_value`) "
						." VALUES "
						." ('$download_id', 'downloadable', 'true') ";
					
			$query = $this->replaceVMTablePrefix($query);
			$db->setQuery($query);
			$db->query();
		}
		
		if ($giftbox_id != 0) {
			$query = " REPLACE INTO #__{vm}_product_attribute "
						." (`product_id`, `attribute_name`, `attribute_value`) "
						." VALUES "
						." ('$giftbox_id', 'downloadable', 'false') ";
					
			$query = $this->replaceVMTablePrefix($query);
			$db->setQuery($query);
			$db->query();
		}
		
		
		//SAVE SECTION (OBJECT) TO TICKET PRICE
		//var_dump($this->section_price);
		if (is_array($this->section_price)) {
			foreach ($this->section_price as $key => $price) {
				$query = " REPLACE INTO #__vmeticket_section_price "
						." VALUES ('$id', '$key', '$price', '#".$this->section_color[$key]."') ";
					
				$query = $this->replaceVMTablePrefix($query);
				$db->setQuery($query);
				$db->query();
			}
		}

		echo "<pre>";
		//var_dump($this->orgs);
		echo "</pre>";
		//Save organisation-ticket permissions
		if (is_array($this->orgs)) {
			foreach ($this->orgs as $org => $perm) {
				 $query = " REPLACE INTO #__vmeticket_org_ticket_perms "
						 ." VALUES (".$perm->id.", ".$id.", ".$perm->perm_id.") ";

				$query = $this->replaceVMTablePrefix($query);
				$db->setQuery($query);
				$db->query();
				
			}
		}
		
      /**
                * This code is used to insert and update data in #__fb_events table
                * return facebook event list
                * @version  1.0.0
                * Date      1July 2014
                */
                $facebookId         = JRequest::getVar('variable_facebook_id'); //get variable faccebook id
                $facebookDate       = JRequest::getVar('variable_facebook_date'); // get variable facebook date
                for($i=0 ;$i < count($facebookDate); $i++)
                {
                    $dayToPost  =   strtotime($facebookDate[$i]);
                    $query      = " SELECT id FROM #__fb_events "
				." WHERE date='$dayToPost' AND event_id='$this->ticket_id' ";	//check this event is exist or not	
                    $db->setQuery($query);				
                    $event = $db->loadAssoc();
                    if(empty($event['id'])) // if not exist then insert in db table
                    {
                        if(!empty($facebookId[$i]))
                        {
                            $query = "INSERT INTO #__fb_events(event_id, date, fb_event_id)VALUES
                            (".$this->ticket_id.", ".$dayToPost.", ".$facebookId[$i].")";
                            $db->setQuery($query);
                            $db->query();
                        }
                    }
                    else
                    {
                        $query = " UPDATE #__fb_events " 
                                ." SET `fb_event_id`='$facebookId[$i]' "
                                ." WHERE date='$dayToPost' AND event_id='$this->ticket_id' ";
                            $db->setQuery($query);
                            $db->query();
                    }
                }
                //-------------End of code insert and update data in facebook event table----------//
                
		//MANAGE PRODUCT FILE
		/*$query = " SELECT file_id FROM #__{vm}_product_files "
				." WHERE file_product_id='$this->ticket_id' AND file_name='".DS.$this->name."' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);				
		$file = $db->loadObject();
		$this->file_id = $file->file_id;*/
		
		/*if ($this->file_id) {
			$query = " UPDATE #__{vm}_product_files "
					." SET "
					." file_name='".DS.$this->name."', file_title='$this->name', file_url='".VMETICKET_TICKET_URL."', file_extension='', file_mimetype='' "
					." WHERE file_id='$this->file_id' ";
		} else {
			$query = " REPLACE INTO #__{vm}_product_files "
					." (file_product_id, file_name, file_title, file_url, file_published, file_image_thumb_height ) "
					." VALUES "
					." ('$id', '".DS.$this->name."', '$this->name', '".VMETICKET_TICKET_URL."', '1', '0') ";
		}
		
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);	
		$db->query();*/
		
		$mainframe = &JFactory::getApplication();
                $mainframe->enqueueMessage(Jtext::_('Event Saved'));

		return $id;
	}
	
	public function deleteTicket() {
		$db =& JFactory::getDBO();
		
		//DELETE XML FILES FOR FLASH COMPONENT 
		$xmlDir = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_vmeticket'.DS.'plugins'.DS.'xml'.DS.$this->ticket_id.DS;
		if (is_dir($xmlDir)) {
			JFolder::delete($xmlDir);
		}
		
		$product_type_id = $this->getTicketProductTypeId();
                
		//DELETE FROM CALENDAR  TABLE #Added by ALW to
                
		$query = " DELETE FROM #__jcalpro2_events "
			     ." WHERE event_id='$this->ticket_id' ";
		//$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
                //DELETE FROM CALENDAR  TABLE #Added by ALW to delete a record from calender table
                
		
		//DELETE FROM PRODUCT TABLE
		$query = " DELETE FROM #__{vm}_product "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//GET CHILD PRODUCTS
		$query = " SELECT product_id FROM #__{vm}_product "
				." WHERE product_parent_id='$this->ticket_id' ";
		
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$children = $db->loadObjectList();	
		
		//DELETE CHILD PRODUCTS
		$query = " DELETE FROM #__{vm}_product "
				." WHERE product_parent_id='$this->ticket_id' ";
		
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//DELETE CHILD PRODUCTS ATTRIBUTES
		if (is_array($children)) {
			foreach ($children as $child) {
				$query = " DELETE FROM #__{vm}_product "
						." WHERE product_parent_id='$child->product_id' ";
		
				$query = $this->replaceVMTablePrefix($query);
				$db->setQuery($query);
				$db->query();
			}
		}
		
		//DELETE FROM CATEGORIES TABLE
		$query = " DELETE FROM #__{vm}_product_category_xref "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//DELETE FROM PRODUCT ATTRIBUTE SKU
		$query = " DELETE FROM #__{vm}_product_attribute_sku "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//DELETE FROM PRODUCT ATTRIBUTE
		$query = " DELETE FROM #__{vm}_product_attribute "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//DELETE FROM PRODUCT MANUFACTURER XREF
		$query = " DELETE FROM #__{vm}_product_mf_xref "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//DELETE FROM PRODUCT VOTES
		$query = " DELETE FROM #__{vm}_product_votes "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//DELETE FROM PRODUCT REVIEWS
		$query = " DELETE FROM #__{vm}_product_reviews "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//DELETE FROM PRODUCT FILES
		$query = " DELETE FROM #__{vm}_product_files "
				." WHERE file_product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//DELETE FROM PRODUCT RELATIONS
		$query = " DELETE FROM #__{vm}_product_relations "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();		
		
		//DELETE FROM PRODUCT TYPE TABLE
		$query = " DELETE FROM #__{vm}_product_type_".$product_type_id." "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		
		//DELETE FROM PRODUCT PRICE TABLE
		$query = " DELETE FROM #__{vm}_product_price "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		
		//DELETE FROM PRODUCT TYPE XREF TABLE
		$query = " DELETE FROM #__{vm}_product_product_type_xref "
				." WHERE product_id='$this->ticket_id' ";
				
		$query = $this->replaceVMTablePrefix($query);
		$db->setQuery($query);
		$db->query();
		
		//DELETE FROM SECTIONS PRICE RELATIONS
		$query = " DELETE FROM #__vmeticket_section_price "
				." WHERE product_id='$this->ticket_id' ";
				
		$db->setQuery($query);
		$db->query();
		

		
		return true;
	}
       
        //--------------This code is added for facebook  event api--------------------//
        
        /**
        * this function is used to get performer details by id
        * parametter    performer id
        * return        performer list
        * @version      1.0.0
        * Date          1July 2014
        */
	public function getPerformerDetailsById($id)
	{	
	    $db     =& JFactory::getDBO();
	    $query  = "SELECT id, performar_title, performar_description, performar_image FROM #__performar WHERE id='$id' AND published='1'";		
	    $db->setQuery($query);	
   	    $list   = $db->loadObject();
   	    return $list;
	}
	
        /**
        * This function is used to get event list in facebook 
        * return        facebook event list
        * @version      1.0.0
        * Date          1July 2014
        */
        public function getFacebookEventList()
        {
            /*********** getting facebook api configuration details ************/
          /*  $configApp          = new JConfig();
	    $fbEventAppId       = $configApp->facebook_event_app_id;  // facebook event App Id
	    $fbEventAppSecret   = $configApp->facebook_event_app_secret; // facebook event app secret Id
	    $fbEventPageId      = $configApp->facebook_event_page_id; // facebook event page Id
            require_once(JPATH_ROOT."/facebook/facebook.php");
	    $config = array(
		'appId' => $fbEventAppId,
		'secret' => $fbEventAppSecret
	    );
	    $facebook   = new Facebook($config);
            $dates      = date("Y-m-d");
            $eventList  = $facebook->api('/'.$fbEventPageId.'/events?since='.$dates);
            return $eventList;  */          
        }
        
        /**
        * This function is used to find the event list in #__fb_events tables by eventID
        * parametter    eventID
        * return        event list
        * @version      1.0.0
        * Date          1July 2014
        */
        public function getEventListById($eventId)
        {
            $db     =& JFactory::getDBO();
            $query  = "SELECT id, event_id, date, fb_event_id FROM #__fb_events WHERE event_id='$eventId'";		
            $db->setQuery($query);	
            $list   = $db->loadObjectList();
   	    return $list;
        }
}