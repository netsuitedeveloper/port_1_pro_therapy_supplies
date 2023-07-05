<?php

class Netsuite_Customer
{
    public $internalId;
    public $eMail;
    public $firstName;
    public $lastName;
    public $fullName;
    public $address;
    
    public static function parseSearsOrder($order)
    {
        $t = new Netsuite_Customer();
        $t->eMail = $order->customer->eMail;
        $t->firstName = $order->customer->firstName;
        $t->lastName = $order->customer->lastName;
        $t->fullName = $t->firstName . ' ' . $t->lastName;
        $t->address = Netsuite_Address::parseSearsAddress($order->shippingAddress);
        $t->_validate();
        return $t;
    }
    
    public function exists()
    {
        if (!$this->eMail) {
            throw new Exception('No e-mail');
        }
        $response = Service::search('CustomerSearchBasic', 'Customer', array(array('field' => 'email', 'type' => 'string', 'operator' => SearchStringFieldOperator::is, 'searchValue' => $this->eMail)));
        return $response->totalRecords > 0;
    }
    
    public function getInternalId()
    {
        if (!$this->eMail) {
            throw new Exception('No e-mail');
        }
        $response = Service::search('CustomerSearchBasic', 'Customer', array(array('field' => 'email', 'type' => 'string', 'operator' => SearchStringFieldOperator::is, 'searchValue' => $this->eMail)));
        if (isset($response->recordList->record[0])) {
            $record = $response->recordList->record[0];
            return $record->internalId;
        }
        throw new Exception('No customer internal id');
    }
    
    public function create()
    {
        if ($this->exists()) {
            $this->internalId = $this->getInternalId();
            return;
        }
        $customer = new Customer();
        $customer->lastName = $this->lastName;
        $customer->firstName = $this->firstName;
        $customer->phone = $this->address->telephone;
        $customer->isPerson = true;
        $customer->email = $this->eMail;
        
        $this->address->addToCustomer($customer);
        
        $this->internalId = Service::add($customer);
        
        return $this;
    }
    
    public function getEntity()
    {
        $e = new RecordRef();
        $e->internalId = $this->internalId;
        $e->name = $this->fullName;
        return $e;
    }
    
    private function _validate()
    {
        if (!$this->eMail) {
            throw new Exception('No email for customer');
        }
        if (!$this->fullName) {
            throw new Exception('No name for customer');
        }
        return $this;
    }
    
}

