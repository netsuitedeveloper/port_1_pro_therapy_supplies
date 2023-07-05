<?php

class Netsuite_Shipment
{
    public $poNumber;
    public $poDate;
    public $shippingMethod;
    public $shippingCarrier;
    public $trackingNumber;
    
    public $items;
    public $order;
    
    public static function parseOrder($order)
    {
        $instance = new Netsuite_Shipment();
        $instance->order = $order;
        
        foreach($order->customFieldList->customField as $customField) {
            if ($customField->internalId == 'custbodystorefrontsearsorderdate') {
                $instance->poDate = $customField->value;
            } else if ($customField->internalId == 'custbodystorefrontorder') {
                $instance->poNumber = $customField->value;
            }
        }
        $shippingMethod = $order->shipMethod->name;
        
        if (stripos($shippingMethod, 'Express') !== false || stripos($shippingMethod, 'Next') !== false) {
            $instance->shippingMethod = 'Express';
        } else if (stripos($shippingMethod, 'Ground') !== false) {
            $instance->shippingMethod = 'Ground';
        } else if (stripos($shippingMethod, 'Priority') !== false) {
            $instance->shippingMethod = 'Priority';
        } else {
            $instance->shippingMethod = 'Ground'; // sears order should all be ups ground because of freeshipping
        }
        if (stripos($shippingMethod, 'UPS') !== false) {
            $instance->shippingCarrier = 'UPS';
        } else if (stripos($shippingMethod, 'FedEx') !== false) {
            $instance->shippingCarrier = 'FedEx';
        } else if (stripos($shippingMethod, 'USPS') !== false) {
            $instance->shippingCarrier = 'USPS';
        } else {
            $instance->shippingCarrier = 'UPS'; // sears order should all be ups ground because of freeshipping
        }
        
        $instance->trackingNumber = App::get($order, 'linkedTrackingNumbers'); // should only be one
        if (is_array($instance->trackingNumber) && $instance->trackingNumber) {
            $instance->trackingNumber = $instance->trackingNumber[0];
        }
        
        $instance->items = array();
        foreach($order->itemList->item as $item) {
            $item = Netsuite_ShipmentItem::parseItem($item);
            if ($item) {
                $instance->items[] = $item;
            }
        }
        
        return $instance;
    }
    
    private function _validate()
    {
        App::validate($this, 'poNumber', 'No order id');
        App::validate($this, 'poDate', 'No order date');
        App::validate($this, 'shippingMethod', 'No shipment method');
    }
    
    public function shipped()
    {
        $record = new SalesOrder();
        $record->internalId = $this->order->internalId;
        
        $customFields = array();
        
        $customField = new BooleanCustomFieldRef();
        $customField->internalId = 'custbodystorefrontsearsordershipped';
        $customField->value = true;
        $customFields[] = $customField;
        
        $record->customFieldList = new CustomFieldList();
        $record->customFieldList->customField = $customFields;
        
        Service::update($record);
    }
    
}

