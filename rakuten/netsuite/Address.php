<?php

class Netsuite_Address
{
    public $internalId;
    
    public $firstName;
    public $lastName;
    public $fullName;
    public $street;
    public $zipCode;
    public $city;
    public $state;
    public $countryId;
    public $telephone;
    public $addressType;
    
    public static function parseSearsAddress($address)
    {
        $t = new Netsuite_Address();
        foreach($address as $name => $value) {
            $t->$name = $value;
        }
        return $t;
    }
    
    public function addToCustomer($customer)
    {
        if (!$customer) {
            throw new Exception('No customer for address');
        }
        $addressbook = new CustomerAddressbook();
        $addressbook->defaultShipping = true;
        $addressbook->defaultBilling = true;
        $addressbook->isResidential = true;
        $addressbook->addressee = $this->fullName;
        $addressbook->phone = $this->telephone;
        $addressbook->addr1 = $this->street;
        $addressbook->city = $this->city;
        $addressbook->country = $this->country;
        $addressbook->zip = $this->zipCode;
        $addressbook->state = $this->state;
        
        $address = new CustomerAddressbookList();
        $address->addressbook = array($addressbook);
        //$address->replaceAll = true;
        
        $customer->addressbookList = $address;
        
        return $this;
    }
    
    public function getBillAddress()
    {
        $a = new BillAddress();
        $a->billAddressee = $this->fullName;
        $a->billPhone = $this->telephone;
        $a->billAddr1 = $this->street;
        $a->billCity = $this->city;
        $a->billState = $this->state;
        $a->billZip = $this->zipCode;
        $a->billCountry = Country::_unitedStates;
        return $a;
    }
    
    public function getShipAddress()
    {
        $a = new ShipAddress();
        $a->shipAddressee = $this->fullName;
        $a->shipPhone = $this->telephone;
        $a->shipAddr1 = $this->street;
        $a->shipCity = $this->city;
        $a->shipState = $this->state;
        $a->shipZip = $this->zipCode;
        $a->shipCountry = Country::_unitedStates;
        return $a;
    }
    
}

