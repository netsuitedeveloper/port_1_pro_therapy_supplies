<?php

class NetSuite
{

}

class NetSuite_Order
{
    public $orderId;
    public $orderDate;
    public $total;
    public $shippingTotal;
    public $customer;
    public $shippingAddress;
    public $items;
    public $shippingMethod;
    
    public static function parseSearsOrder($order)
    {
        $t = new Netsuite_Order();
        $t->orderId = $order->poNumber;
        $t->orderDate = $order->poDate;
        $t->customer = Netsuite_Customer::parseSearsOrder($order);
        $t->shippingAddress = Netsuite_Address::parseSearsAddress($order->shippingAddress);
        $t->shippingMethod = $order->shippingMethod;
        if ($order->items) {
            $t->items = array();
            foreach($order->items as $item) {
                $oi = Netsuite_OrderItem::parseSearsOrderItem($item);
                if ($oi) {
                    $t->items[] = $oi;
                }
            }
        }
        $t->total = $order->total;
        $t->shippingTotal = $order->shippingTotal;
        $t->_validate();
        return $t;
    }
    
    public function get()
    {
        $response = Service::search('TransactionSearchBasic', 'salesOrder', array(array('field' => 'custom', 'operator' => SearchStringFieldOperator::is, 'type' => 'string', 'internalId' => 'custbodystorefrontorder', 'searchValue' => $this->orderId)));
        
        if ($response->totalRecords) {
            if (isset($response->recordList->record)) {
                $response->recordList->record = App::toArray($response->recordList->record);
                foreach($response->recordList->record as $record) {
                    if ($record instanceof SalesOrder) {
                        $internalId = $record->internalId;
                        
                        return Service::get('salesOrder', $internalId);
                    }
                }
            }
        }
        return false;
        
    }
    
    public function place()
    {
        $this->_validate();
        
        $internalId = false;
        if ($this->exists()) {
            //$internalId = $this->get();
            //$internalId = $internalId->readResponse->record->internalId;
            return;
        }
        $this->customer->create();
        
        $o = new SalesOrder();
        if ($internalId) {
            $o->internalId = $internalId;
        }
        $o->entity = $this->customer->getEntity();
        $o->itemList = new SalesOrderItemList();
        $items = array();
        foreach($this->items as $item) {
            $items[] = $item->getSalesOrderItem();
        }
        $o->itemList->item = $items;
        
        $o->shippingCost = $this->shippingTotal;
        
        $o->transactionShipAddress = $this->shippingAddress->getShipAddress();
        $o->transactionBillAddress = $this->shippingAddress->getBillAddress();
        
        if (GOOGLESEARSBING_NETSUITE_FAKEORDERS) {
            $o->orderStatus = SalesOrderOrderStatus::_pendingApproval;
            $o->memo = 'FAKE ORDER';
            $o->quickNote = 'FAKE ORDER';
        }
        
        $customFields = array();
        $customField = new StringCustomFieldRef();
        $customField->internalId = 'custbodystorefront';
        $customField->value = 'Sears';
        $customFields[] = $customField;
        $customField = new StringCustomFieldRef();
        $customField->internalId = 'custbodystorefrontorder';
        $customField->value = $this->orderId;
        $customFields[] = $customField;
        $customField = new StringCustomFieldRef();
        $customField->internalId = 'custbodystorefrontsearsorderdate';
        $customField->value = $this->orderDate;
        $customFields[] = $customField;
        $customField = new BooleanCustomFieldRef();
        $customField->internalId = 'custbodystorefrontsearsordershipped';
        $customField->value = false;
        $customFields[] = $customField;
        
        $o->customFieldList = new CustomFieldList();
        $o->customFieldList->customField = $customFields;
        
        $o->paymentMethod = new RecordRef();
        $o->paymentMethod->internalId = '18';
        $o->paymentMethod->name = 'Sears Payment';
        
        $o->taxItem = new RecordRef();
        $o->taxItem->internalId = '-8';
        
        // 275 ... UPS Next Day Air
        // 273 ... UPS 2nd Day Air
        // 272 ... UPS Ground
        // 50233 ... Standard Shipping
        // 5677 ... USPS Priority Mail
        if (stripos($this->shippingMethod, 'Express') !== false || stripos($this->shippingMethod, 'Next') !== false) {
            $o->shipMethod = new RecordRef();
            $o->shipMethod->internalId = '275';
        } else if (stripos($this->shippingMethod, 'Priority') !== false || stripos($this->shippingMethod, 'Second') !== false || stripos($this->shippingMethod, '2n') !== false) {
            $o->shipMethod = new RecordRef();
            $o->shipMethod->internalId = '273';
        } else {
            $o->shipMethod = new RecordRef();
            //$o->shipMethod->internalId = '272';
            //$o->shipMethod->internalId = '50233';
            $o->shipMethod->internalId = '5677';
        }
        
        Log::debug("[shippingMethod] => " . $this->shippingMethod . " [internalId] => " . $o->shipMethod->internalId);
        
        if ($internalId) {
            Service::update($o);
        } else {
            Service::add($o);
        }
    }
    
    private function _validate()
    {
        if (!$this->customer) {
            throw new Exception('No customer for netsuite order');
        }
        if (!$this->shippingAddress) {
            throw new Exception('No address for netsuite order');
        }
        if (!$this->items) {
            throw new Exception('No items for netsuite order');
        }
    }
    
    public function exists()
    {
        $response = Service::search('TransactionSearchBasic', 'salesOrder', array(array('field' => 'custom', 'operator' => SearchStringFieldOperator::is, 'type' => 'string', 'internalId' => 'custbodystorefrontorder', 'searchValue' => $this->orderId)));
        
        if ($response->totalRecords) {
            if (isset($response->recordList->record)) {
                $response->recordList->record = App::toArray($response->recordList->record);
                foreach($response->recordList->record as $record) {
                    if ($record instanceof SalesOrder) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
}

