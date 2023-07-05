<?php

class Netsuite_ShipmentItem
{
    public $itemId;
    public $quantity;
    public $lineNumber;
    
    public static function parseItem($item)
    {
        $instance = new Netsuite_ShipmentItem();
        
        $instance->itemId = $item->item->internalId;
        $instance->quantity = $item->quantity;
        
        foreach($item->customFieldList->customField as $field) {
            if ($field->internalId == 'custcolsearslinenumber') {
                $instance->lineNumber = $field->value;
            }
            
        }
        
        $instance->_validate();
        
        return $instance;
    }
    
    private function _validate()
    {
        App::validate($this, 'lineNumber', 'No line number');
        App::validate($this, 'itemId', 'No item id number');
        App::validate($this, 'quantity', 'No quantity id number');
    }
    
}

