<?php

class Netsuite_OrderItem
{
    public $itemId;
    public $quantity;
    public $price;
    public $lineNumber;
    
    public static function parseSearsOrderItem($oi)
    {
        if (!$oi) {
            throw new Exception('No order item');
        }
        $orderItem = new Netsuite_OrderItem();
        $orderItem->itemId = $oi->itemId;
        $orderItem->quantity = $oi->quantity;
        $orderItem->price = $oi->price;
        $orderItem->lineNumber = $oi->lineNumber;
        return $orderItem;
    }
    
    public function getSalesOrderItem()
    {
        $item = new SalesOrderItem();
        $item->item = new RecordRef();
        $item->item->internalId = $this->itemId;
        $item->quantity = $this->quantity;
        
        $customFields = array();
        $customField = new StringCustomFieldRef();
        $customField->internalId = 'custcolsearslinenumber';
        $customField->value = $this->lineNumber;
        $customFields[] = $customField;
        
        $item->customFieldList = new CustomFieldList();
        $item->customFieldList->customField = $customFields;
        
        $item->amount = $this->price * $this->quantity; //?
        
        return $item;
    }
    
}

