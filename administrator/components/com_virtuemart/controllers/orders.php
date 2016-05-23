<?php
/**
 *
 * Orders controller
 *
 * @package	VirtueMart
 * @subpackage
 * @author Max Milbers, Valerie Isaksen
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: orders.php 9029 2015-10-28 12:51:49Z Milbo $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

if(!class_exists('VmController'))require(VMPATH_ADMIN.DS.'helpers'.DS.'vmcontroller.php');


/**
 * Orders Controller
 *
 * @package    VirtueMart
 * @author
 */
class VirtuemartControllerOrders extends VmController {

	/**
	 * Method to display the view
	 *
	 * @access	public
	 * @author
	 */
	function __construct() {
		VmConfig::loadJLang('com_virtuemart_orders',TRUE);
		parent::__construct();

	}

	/**
	 * Shows the order details
	 */
	public function edit($layout='order'){

		parent::edit($layout);
	}

	public function updateCustomsOrderItems(){

		$q = 'SELECT `product_attribute` FROM `#__virtuemart_order_items` LIMIT ';
		$do = true;
		$db = JFactory::getDbo();
		$start = 0;
		$hunk  = 1000;
		while($do){
			$db->setQuery($q.$start.','.$hunk);
			$items = $db->loadColumn();
			if(!$items){
				vmdebug('updateCustomsOrderItems Reached end after '.$start/$hunk.' loops');
				break;
			}
			//The stored result in vm2.0.14 looks like this {"48":{"textinput":{"comment":"test"}}}
			//{"96":"18"} download plugin
			// 46 is virtuemart_customfield_id
			//{"46":" <span class=\"costumTitle\">Cap Size<\/span><span class=\"costumValue\" >S<\/span>","110":{"istraxx_customsize":{"invala":"10","invalb":"10"}}}
			//and now {"32":[{"invala":"100"}]}
			foreach($items as $field){
				if(strpos($field,'{')!==FALSE){
					$jsField = json_decode($field);
					$fieldProps = get_object_vars($jsField);
					vmdebug('updateCustomsOrderItems',$fieldProps);
					$nJsField = array();
					foreach($fieldProps as $k=>$props){
						if(is_object($props)){

							$props = (array)$props;
							foreach($props as $ke=>$prop){
								if(!is_numeric($ke)){
									vmdebug('Found old param style',$ke,$prop);
									if(is_object($prop)){
										$prop = (array)$prop;
										$nJsField[$k] = $prop;
										/*foreach($prop as $name => $propvalue){
											$nJsField[$k][$name] = $propvalue;
										}*/
									}
								}
								 else {
									//$nJsField[$k][$name] = $prop;
								}
							}
						} else {
							if(is_numeric($k) and is_numeric($props)){
							$nJsField[$props] = $k;
							} else {
								$nJsField[$k] = $props;
							}
						}
					}
					$nJsField = vmJsApi::safe_json_encode($nJsField);
					vmdebug('updateCustomsOrderItems json $field encoded',$field,$nJsField);
				} else {
					vmdebug('updateCustomsOrderItems $field',$field);
				}

			}
			if(count($items)<$hunk){
				vmdebug('Reached end');
				break;
			}
			$start += $hunk;
		}
		// Create the view object
		$view = $this->getView('orders', 'html');
		$view->display();
	}

	/**
	 * NextOrder
	 * renamed, the name was ambigous notice by Max Milbers
	 * @author Kohl Patrick
	 */
	public function nextItem($dir = 'ASC'){
		$model = VmModel::getModel('orders');
		$id = vRequest::getInt('virtuemart_order_id');
		if (!$order_id = $model->getOrderId($id, $dir)) {
			$order_id  = $id;
			$msg = vmText::_('COM_VIRTUEMART_NO_MORE_ORDERS');
		} else {
			$msg ='';
		}
		$this->setRedirect('index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id='.$order_id ,$msg );
	}

	/**
	 * NextOrder
	 * renamed, the name was ambigous notice by Max Milbers
	 * @author Kohl Patrick
	 */
	public function prevItem(){

		$this->nextItem('DESC');
	}
	/**
	 * Generic cancel task
	 *
	 * @author Max Milbers
	 */
	public function cancel(){
		// back from order
		$this->setRedirect('index.php?option=com_virtuemart&view=orders' );
	}
	/**
	 * Shows the order details
	 * @deprecated
	 */
	public function editOrderStatus() {

		$view = $this->getView('orders', 'html');

		if($this->getPermOrderStatus()){
			$model = VmModel::getModel('orders');
			$model->updateOrderStatus();
		} else {
			vmInfo('Restricted');
		}

		$view->display();
	}

	function getPermOrderStatus(){

		if(vmAccess::manager('orders.status')){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Update an order status
	 *
	 * @author Max Milbers
	 */
	public function updatestatus() {

		$app = Jfactory::getApplication();
		$lastTask = vRequest::getCmd('last_task');

		/* Load the view object */
		$view = $this->getView('orders', 'html');

		if(!$this->getPermOrderStatus()){
			vmInfo('Restricted');
			$view->display();
			return true;
		}

		/* Update the statuses */
		$model = VmModel::getModel('orders');

		if ($lastTask == 'updatestatus') {
			// single order is in POST but we need an array
			$order = array() ;
			$virtuemart_order_id = vRequest::getInt('virtuemart_order_id');
			$order[$virtuemart_order_id] = (vRequest::getRequest());

			$result = $model->updateOrderStatus($order);
		} else {
			$result = $model->updateOrderStatus();
		}

		$msg='';
		if ($result['updated'] > 0)
		$msg = vmText::sprintf('COM_VIRTUEMART_ORDER_UPDATED_SUCCESSFULLY', $result['updated'] );
		else if ($result['error'] == 0)
		$msg .= vmText::_('COM_VIRTUEMART_ORDER_NOT_UPDATED');
		if ($result['error'] > 0)
		$msg .= vmText::sprintf('COM_VIRTUEMART_ORDER_NOT_UPDATED_SUCCESSFULLY', $result['error'] , $result['total']);
		if ('updatestatus'== $lastTask ) {
			$app->redirect('index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id='.$virtuemart_order_id , $msg);
		}
		else {
			$app->redirect('index.php?option=com_virtuemart&view=orders', $msg);
		}
	}


	/**
	 * Save changes to the order item status
	 *
	 */
	public function saveItemStatus() {

		$mainframe = Jfactory::getApplication();

		$data = vRequest::getRequest();
		$model = VmModel::getModel();
		$model->updateItemStatus(JArrayHelper::toObject($data), $data['new_status']);

		$mainframe->redirect('index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id='.$data['virtuemart_order_id']);
	}


	/**
	 * Display the order item details for editing
	 */
	public function editOrderItem() {

		vRequest::setVar('layout', 'orders_editorderitem');

		parent::display();
	}


	/**
	 * correct position, working with json? actually? WHat ist that?
	 *
	 * Get a list of related products
	 * @author Max Milbers
	 */
	public function getProducts() {

		$view = $this->getView('orders', 'json');
		$view->setLayout('orders_editorderitem');

		$view->display();
	}


	/**
	 * Update status for the selected order items
	 */
	public function updateOrderItemStatus()
	{

		$mainframe = Jfactory::getApplication();
		$model = VmModel::getModel();


		$_items = vRequest::getVar('item_id',  0, '', 'array');

		$_orderID = vRequest::getInt('virtuemart_order_id', false);
		$model->updateStatusForOneOrder($_orderID,$_items,true);

		$model->deleteInvoice($_orderID);
		$mainframe->redirect('index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id='.$_orderID);
	}

	public function updateOrderHead()
	{
		$mainframe = Jfactory::getApplication();
		$model = VmModel::getModel();
		$_items = vRequest::getVar('item_id',  0, '', 'array');
		$_orderID = vRequest::getInt('virtuemart_order_id', '');
		$model->UpdateOrderHead((int)$_orderID, vRequest::getRequest());
		$model->deleteInvoice($_orderID);
		$mainframe->redirect('index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id='.$_orderID);
	}

	public function CreateOrderHead()
	{
		$mainframe = Jfactory::getApplication();
		$model = VmModel::getModel();
		$orderid = $model->CreateOrderHead();
		$mainframe->redirect('index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id='.$orderid );
	}

	public function newOrderItem() {

		$orderId = vRequest::getInt('virtuemart_order_id', '');
		$model = VmModel::getModel();
		$msg = '';
		$data = vRequest::getRequest();
		$model->saveOrderLineItem($data);
		$model->deleteInvoice($orderId);
		$editLink = 'index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id=' . $orderId;
		$this->setRedirect($editLink, $msg);
	}

	/**
	 * Removes the given order item
	 */
	public function removeOrderItem() {

		$model = VmModel::getModel();
		$msg = '';
		$orderId = vRequest::getInt('orderId', '');
		// TODO $orderLineItem as int ???
		$orderLineItem = vRequest::getInt('orderLineId', '');

		$model->removeOrderLineItem($orderLineItem);

		$model->deleteInvoice($orderId);
		$editLink = 'index.php?option=com_virtuemart&view=orders&task=edit&virtuemart_order_id=' . $orderId;
		$this->setRedirect($editLink, $msg);
	}
	
	public function exportOrders()
    {
		$model = VmModel::getModel();
		$orderStatusModel =VmModel::getModel('orderstatus');
		$orderStates = $orderStatusModel->getOrderStatusList(true);
		$orderslist = $model->getOrdersList();
		if(!class_exists('CurrencyDisplay'))require(VMPATH_ADMIN.DS.'helpers'.DS.'currencydisplay.php');
		$_currencies = array();
		if ($orderslist) {

			    foreach ($orderslist as $virtuemart_order_id => $order) {

				    if(!empty($order->order_currency)){
					    $currency = $order->order_currency;
				    } else if($order->virtuemart_vendor_id){
					    if(!class_exists('VirtueMartModelVendor')) require(VMPATH_ADMIN.DS.'models'.DS.'vendor.php');
					    $currObj = VirtueMartModelVendor::getVendorCurrency($order->virtuemart_vendor_id);
				        $currency = $currObj->virtuemart_currency_id;
					}
				    //This is really interesting for multi-X, but I avoid to support it now already, lets stay it in the code
				    if (!array_key_exists('curr'.$currency, $_currencies)) {

					    $_currencies['curr'.$currency] = CurrencyDisplay::getInstance($currency,$order->virtuemart_vendor_id);
				    }

				    $order->order_total = $_currencies['curr'.$currency]->priceDisplay($order->order_total);
				    $order->invoiceNumber = $model->getInvoiceNumber($order->virtuemart_order_id);
			    }

			}

		
				
				
		

/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('Australia/Melbourne');

if (PHP_SAPI == 'cli')
	die('This example should only be run from a Web Browser');

require_once dirname(__FILE__) . '/Classes/PHPExcel.php';

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
							 ->setLastModifiedBy("Maarten Balliauw")
							 ->setTitle("Office 2007 XLSX Test Document")
							 ->setSubject("Office 2007 XLSX Test Document")
							 ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
							 ->setKeywords("office 2007 openxml php")
							 ->setCategory("Test result file");
// Set default font

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial')
                                          ->setSize(10);

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
$sharedStyle1 = new PHPExcel_Style();
$sharedStyle2 = new PHPExcel_Style();
$sharedStyle3 = new PHPExcel_Style();
$sharedStyle4 = new PHPExcel_Style();

$sharedStyle1->applyFromArray(
	array('fill' 	=> array(
								'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
								'color'		=> array('argb' => 'FFCCFFCC')
							),
		  'borders' => array(
								'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
								'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
							)
		 ));

$sharedStyle2->applyFromArray(
	array('fill' 	=> array(
								'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
								'bold'  => true,
								'color'		=> array('argb' => 'FFFFFF00')
							),
		  'borders' => array(
								'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
								'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
							)
		 ));

$sharedStyle3->applyFromArray(
	array('fill' 	=> array(
								'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
								'bold'  => true,
								'color'		=> array('argb' => 'FFCCFFCC')
							),
		  'borders' => array(
								'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
								'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
							)
		 ));

$sharedStyle4->applyFromArray(
	array('fill' 	=> array(
								'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
								'bold'  => true,
								'color'		=> array('argb' => 'FFFFFF00')
							),
		  'borders' => array(
								'bottom'	=> array('style' => PHPExcel_Style_Border::BORDER_THIN),
								'right'		=> array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
							)
		 ));

// Add some data
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Order Number')
			->setCellValue('B1', 'Customer Name')
            ->setCellValue('C1', 'Customer Email')
            ->setCellValue('D1', 'Payment Method Used')
			->setCellValue('E1', 'Order Date')
            ->setCellValue('F1', 'Order Total');
			
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle3,'A1');
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle4,'B1');
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle3,'C1');
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle4,'D1');
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle3,'E1');
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle4,'F1');
			
			
$j=2;
if (count ($orderslist) > 0) {
			$i = 0;
			$k = 0;
foreach ($orderslist as $key => $order) {
	          $order_number =    $order->order_number;
			  $order_name   =    $order->order_name;
			  $order_email  =    $order->order_email;
			  $payment_method =  $order->payment_method;
			  $created_on     =  $order->created_on;
			  $modified_on    =  $order->modified_on;
			  $order_total    =  $order->order_total;
			  $k = 1 - $k;
			  $i++;
              $objPHPExcel->setActiveSheetIndex(0)
			  ->setCellValue('A' . $j, $order->order_number)
	          ->setCellValue('B' . $j, $order->order_name)
			  ->setCellValue('C' . $j, $order->order_email)
			  ->setCellValue('D' . $j, $order->payment_method)
			  ->setCellValue('E' . $j, $order->created_on)
			  ->setCellValue('F' . $j, $order->order_total);
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1,'A' . $j);
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle2,'B' . $j);
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1,'C' . $j);
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle2,'D' . $j);
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle1,'E' . $j);
			  $objPHPExcel->getActiveSheet()->setSharedStyle($sharedStyle2,'F' . $j);
			  
	          $j++;                            
			}

    }


// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('Simple');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);


// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Orders.xlsx"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;
			
			
		
	}	

}
// pure php no closing tag

