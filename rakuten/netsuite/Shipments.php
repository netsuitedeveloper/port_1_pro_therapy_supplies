<?php

class Netsuite_Shipments
{
    public $shipments;
    
    public static function load()
    {
        $shipments = array();
        
        $instance = new Netsuite_Shipments();
        $instance->shipments = array();
        
        //$instance->_load(SalesOrderOrderStatus::_closed);
        $instance->_load(SalesOrderOrderStatus::_pendingBilling);
        $instance->_load(SalesOrderOrderStatus::_fullyBilled);
        $instance->_load('Billed');
        
        return $instance;
    }
    
    private function _load($orderStatus)
    {
        if (true) {
            $response = Service::search('TransactionSearchBasic', 'salesOrder', 
                array(
                        array('field' => 'custom', 'operator' => 'notEmpty', 'type' => 'string', 'internalId' => 'custbodystorefrontsearsorderdate'),
                        array('field' => 'custom', 'type' => 'boolean', 'internalId' => 'custbodystorefrontsearsordershipped', 'searchValue' => false),
                        array('field' => 'custom', 'operator' => 'is', 'type' => 'string', 'internalId' => 'custbodystorefront', 'searchValue' => 'Sears'),
                        //array('field' => 'status', 'operator' => SearchEnumMultiSelectFieldOperator::anyOf, 'type' => 'enummultiselect', 'searchValue' => $orderStatus), // does not work
                        //array('field' => 'orderStatus', 'operator' => 'is', 'type' => 'string', 'searchValue' => $orderStatus), // does not work
                        )
                    );
            //file_put_contents('c:\\myspace\\tmp\\unshipped-orders.dat', serialize($response));
            App::debug($response);
        } else {
            $response = unserialize(file_get_contents('c:\\myspace\\tmp\\unshipped-orders.dat'));
        }
        
        $orderStatus = str_ireplace('_', '', $orderStatus);
        $orderStatus = strtolower(str_ireplace(' ', '', $orderStatus));
        
        if (isset($response->recordList->record)) {
            $response->recordList->record == App::toArray($response->recordList->record);
            foreach($response->recordList->record as $record) {
                try {
                    $order = Service::get('salesOrder', $record->internalId)->readResponse->record;
                    $status = strtolower(str_ireplace(' ', '', $order->status));
                    if ($status != $orderStatus) {
                        continue;
                    }
                    
                    $shipment = Netsuite_Shipment::parseOrder($order);
                    if ($shipment) {
                        $this->shipments[] = $shipment;
                    }
                } catch(exception $ex) {
                    App::ex($ex, 'loadShipments');
                }
            }
        }
        
    }
    
}

