<?php

class NetSuite {

    private $_generalConfig;
    private $_netsuiteConfig;

    public function __construct($general_config, $netsuite_config) {
        $this->generalConfig = $general_config;
        $this->netsuiteConfig = $netsuite_config;
    }

    public function placeOrder($order_number, $newegg_order) {
        Log::debug('netsuite place order: ', $order_number);
        Log::data($newegg_order);

        $o = new NetSuite_Order();

        $o->orderId = $order_number;
        $o->orderDate = new DateTime($newegg_order['Order Date & Time']);
        $o->orderDate = $o->orderDate->format('c');

        $shipping_method = $newegg_order['Order Shipping Method'];
        if (stripos($shipping_method, 'Standard') !== false) {
            $o->shippingMethod = NetSuite_Order::SHIPPING_METHOD_STANDARD_SHIPPING;
        } else if (stripos($shipping_method, 'Expedited') !== false) {
            $o->shippingMethod = NetSuite_Order::SHIPPING_METHOD_EXPEDITED_SHIPPING;
        } else if (stripos($shipping_method, 'One') !== false) {
            $o->shippingMethod = NetSuite_Order::SHIPPING_METHOD_ONE_DAY_SHIPPING;
        } else if (stripos($shipping_method, 'Two') !== false) {
            $o->shippingMethod = NetSuite_Order::SHIPPING_METHOD_TWO_DAYS_SHIPPING;
        } else if (stripos($shipping_method, '2nd') !== false) {
            $o->shippingMethod = NetSuite_Order::SHIPPING_METHOD_THREE_DAYS_SHIPPING;
        } else {
            throw new CustomException('Unknown shipping method: ', $shipping_method);
        }

        $o->total = $newegg_order['Order Total'];
        $o->shippingTotal = $newegg_order['Order Shipping Total'];

        $address = new NetSuite_Address();
        $address->firstName = $newegg_order['Ship To First Name'];
        $address->lastName = $newegg_order['Ship To LastName'];
        $address->fullName = trim($newegg_order['Ship To First Name'] . ' ' . $newegg_order['Ship To LastName']);
        $address->street = trim($newegg_order['Ship To Address Line 1'] . ' ' . $newegg_order['Ship To Address Line 2']);
        $address->zipCode = $newegg_order['Ship To ZipCode'];
        $address->city = $newegg_order['Ship To City'];
        $address->state = $newegg_order['Ship To State'];
        $address->countryId = $newegg_order['Ship To Country'];
        $address->telephone = $newegg_order['Ship To Phone Number'];
        $o->shippingAddress = $address;

        $customer = new NetSuite_Customer();
        $customer->eMail = $newegg_order['Order Customer Email'];
        $customer->firstName = $newegg_order['Ship To First Name'];
        $customer->lastName = $newegg_order['Ship To LastName'];
        $customer->fullName = trim($customer->firstName . ' ' . $customer->lastName);
        $customer->address = $address;
        $o->customer = $customer;

        $o->items = array();
        foreach ($newegg_order['items'] as $newegg_order_item) {
            $item = new NetSuite_Order_Item();
            $item->itemId = $newegg_order_item['Item Seller Part #'];
            $item->quantity = $newegg_order_item['Quantity Ordered'];
            $item->price = $newegg_order_item['Item Unit Price'];
            $o->items[] = $item;
        }

        $o->orderStatus = SalesOrderOrderStatus::_pendingFulfillment;

        Log::data('netsuite order: ', $o);

        $o->place();
    }

    public function getShipments(&$exceptions) {
        $netsuite_shipments = new NetSuite_Shipments();
        $netsuite_shipments->load();

        $shipments = $netsuite_shipments->shipments;
        $exceptions = $netsuite_shipments->exceptions;

        Log::data('current shipments: ', $shipments);
        Log::debug('shipments for orders: ', array_keys(APP::toMap($shipments, 'poNumber')));

        return $shipments;
    }

}

class NetSuite_Order {

    const SHIPPING_METHOD_STANDARD_SHIPPING = 'Standard Shipping';
    const SHIPPING_METHOD_EXPEDITED_SHIPPING = 'UPS Ground Shipping';
    const SHIPPING_METHOD_ONE_DAY_SHIPPING = 'UPS Next Day Air';
    const SHIPPING_METHOD_TWO_DAYS_SHIPPING = 'UPS 2nd Day Air';
    const SHIPPING_METHOD_THREE_DAYS_SHIPPING = 'UPS 3 Day Select';

    public $orderId;
    public $orderDate;
    public $total;
    public $shippingTotal;
    public $customer;
    public $shippingAddress;
    public $items;
    public $shippingMethod;

    public function get() {
        $response = NetSuite_Service::search('TransactionSearchBasic', 'salesOrder', array(array('field' => 'custom', 'operator' => SearchStringFieldOperator::is, 'type' => 'string', 'internalId' => 'custbodystorefrontorder', 'searchValue' => $this->orderId)));

        if ($response->totalRecords) {
            if (isset($response->recordList->record)) {
                $response->recordList->record = APP::toArray($response->recordList->record);
                foreach ($response->recordList->record as $record) {
                    if ($record instanceof SalesOrder) {
                        $internalId = $record->internalId;

                        return NetSuite_Service::get('salesOrder', $internalId);
                    }
                }
            }
        }
        return false;
    }

    public function place() {
        $this->_validate();

        $internalId = false;
        if ($this->exists()) {
            Log::debug('netsuite order exists: ', $this->orderId);
            //$internalId = $this->get();
            //$internalId = $internalId->readResponse->record->internalId;
            return;
        }
        $this->customer->create();

        $o = new SalesOrder();
        //$o->createdDate = $this->orderDate; // insufficient permission -> netsuite should automatically select current date
        $o->toBeEmailed = false;

        if ($internalId) {
            $o->internalId = $internalId;
        }
        $o->entity = $this->customer->getEntity();
        $o->itemList = new SalesOrderItemList();
        $items = array();
        foreach ($this->items as $item) {
            $items[] = $item->getSalesOrderItem();
        }
        $o->itemList->item = $items;

        $o->shippingCost = $this->shippingTotal;

        $o->transactionShipAddress = $this->shippingAddress->getShipAddress();
        $o->transactionBillAddress = $this->shippingAddress->getBillAddress();

        if (PDEBUG::CREATE_FAKE_ORDERS) {
            $o->orderStatus = SalesOrderOrderStatus::_pendingApproval;
            $o->memo = 'Fake Order - Newegg API Test';
        }

        $customFields = array();
        $customField = new StringCustomFieldRef();
        $customField->internalId = 'custbodystorefront';
        $customField->value = 'Newegg';
        $customFields[] = $customField;
        $customField = new StringCustomFieldRef();
        $customField->internalId = 'custbodystorefrontorder';
        $customField->value = $this->orderId;
        $customFields[] = $customField;

        $customField = new BooleanCustomFieldRef();
        $customField->internalId = 'custbodycustbodystorefrontneweggorders';
        $customField->value = false;
        $customFields[] = $customField;

        /**
          $customField = new SelectCustomFieldRef();
          $customField->internalId = 'custbody_saleschannel';
          $customField->value = 'Newegg';
          $customFields[] = $customField;
         * */

        $o->customFieldList = new CustomFieldList();
        $o->customFieldList->customField = $customFields;

        $o->paymentMethod = new RecordRef();
        $o->paymentMethod->internalId = '26';
        $o->paymentMethod->name = 'Newegg Payment';

        $o->taxItem = new RecordRef();
        $o->taxItem->internalId = '-8';

        $o->location = new RecordRef();
        $o->location->internalId = 6;

        $o->shipMethod = new RecordRef();
        if ($this->shippingMethod == self::SHIPPING_METHOD_STANDARD_SHIPPING) { //Newegg Standard Shipping -> Standard Shipping
            $o->shipMethod->internalId = '50233';
        } else if ($this->shippingMethod == self::SHIPPING_METHOD_EXPEDITED_SHIPPING) { //Newegg Expedited Shipping -> UPS Ground Shipping
            $o->shipMethod->internalId = '272';
        } else if ($this->shippingMethod == self::SHIPPING_METHOD_ONE_DAY_SHIPPING) { //Newegg One-Day Shipping -> UPS Next Day Air
            $o->shipMethod->internalId = '275';
        } else if ($this->shippingMethod == self::SHIPPING_METHOD_TWO_DAYS_SHIPPING) { //Newegg Two-Day Shipping -> UPS 2nd Day Air
            $o->shipMethod->internalId = '273';
        } else if ($this->shippingMethod == self::SHIPPING_METHOD_THREE_DAYS_SHIPPING) { //Newegg UPS 2nd Day Air -> UPS 3 Day Select
            $o->shipMethod->internalId = '101488';
        }

        $o->orderStatus = SalesOrderOrderStatus::_pendingFulfillment;

        Log::network($o);

        if ($internalId) {
            //NetSuite_Service::update($o);
        } else {
            NetSuite_Service::add($o);
        }
    }

    private function _validate() {
        if (!$this->customer) {
            throw new Exception('No customer for netsuite order');
        }
        if (!$this->shippingAddress) {
            throw new Exception('No address for netsuite order');
        }
        if (!$this->items) {
            throw new Exception('No items for netsuite order');
        }
        $this->customer->validate();
    }

    public function exists() {
        $response = NetSuite_Service::search('TransactionSearchBasic', 'salesOrder', array(array('field' => 'custom', 'operator' => SearchStringFieldOperator::is, 'type' => 'string', 'internalId' => 'custbodystorefrontorder', 'searchValue' => $this->orderId)));

        if ($response->totalRecords) {
            if (isset($response->recordList->record)) {
                $response->recordList->record = APP::toArray($response->recordList->record);
                foreach ($response->recordList->record as $record) {
                    if ($record instanceof SalesOrder) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function closed($internal_id) {
        $record = new SalesOrder();
        $record->internalId = $internal_id;

        $customFields = array();

        $customField = new BooleanCustomFieldRef();
        $customField->internalId = 'custbodycustbodystorefrontneweggorders';
        $customField->value = true;
        $customFields[] = $customField;

        $record->customFieldList = new CustomFieldList();
        $record->customFieldList->customField = $customFields;

        NetSuite_Service::update($record);
    }

}

class NetSuite_Order_Item {

    public $itemId;
    public $quantity;
    public $price;

    public function getSalesOrderItem() {
        $item = new SalesOrderItem();
        $item->item = new RecordRef();
        $item->item->internalId = $this->itemId;

        $item->quantity = $this->quantity;

        $item->amount = $this->price * $this->quantity;

        return $item;
    }

}

class NetSuite_Address {

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

    //public $addressType;

    public function addToCustomer($customer) {
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
        if ($this->countryId == 'CA') {
            $addressbook->country = Country::_canadaEnglish;
        } else if ($this->countryId == 'US') {
            $addressbook->country = Country::_unitedStates;
        } else if ($this->countryId == 'UNITED STATES') {
            $addressbook->country = Country::_unitedStates;
        } else {
            throw new CustomException('Unrecognized country code for ', $this->countryId, '. No valid mapping available, please contact developer');
        }
        $addressbook->zip = $this->zipCode;
        $addressbook->state = $this->state;

        $address = new CustomerAddressbookList();
        $address->addressbook = array($addressbook);
        //$address->replaceAll = true;

        $customer->addressbookList = $address;

        return $this;
    }

    public function getBillAddress() {
        $a = new BillAddress();
        $a->billAddressee = $this->fullName;
        $a->billPhone = $this->telephone;
        $a->billAddr1 = $this->street;
        $a->billCity = $this->city;
        $a->billState = $this->state;
        $a->billZip = $this->zipCode;
        if ($this->countryId == 'CA') {
            $a->billCountry = Country::_canadaEnglish;
        } else if ($this->countryId == 'US') {
            $a->billCountry = Country::_unitedStates;
        } else if ($this->countryId == 'UNITED STATES') {
            $a->billCountry = Country::_unitedStates;
        } else {
            throw new CustomException('Unrecognized country code for ', $this->countryId, '. No valid mapping available, please contact developer');
        }
        return $a;
    }

    public function getShipAddress() {
        $a = new ShipAddress();
        $a->shipAddressee = $this->fullName;
        $a->shipPhone = $this->telephone;
        $a->shipAddr1 = $this->street;
        $a->shipCity = $this->city;
        $a->shipState = $this->state;
        $a->shipZip = $this->zipCode;
        if ($this->countryId == 'CA') {
            $a->shipCountry = Country::_canadaEnglish;
        } else if ($this->countryId == 'US') {
            $a->shipCountry = Country::_unitedStates;
        } else if ($this->countryId == 'UNITED STATES') {
            $a->shipCountry = Country::_unitedStates;
        } else {
            throw new CustomException('Unrecognized country code for ', $this->countryId, '. No valid mapping available, please contact developer');
        }
        return $a;
    }

}

class NetSuite_Customer {

    public $internalId;
    public $eMail;
    public $firstName;
    public $lastName;
    public $fullName;
    public $address;

    public function exists() {
        if (!$this->eMail) {
            throw new Exception('No e-mail');
        }
        $response = NetSuite_Service::search('CustomerSearchBasic', 'Customer', array(array('field' => 'email', 'type' => 'string', 'operator' => SearchStringFieldOperator::is, 'searchValue' => $this->eMail)));
        return $response->totalRecords > 0;
    }

    public function getInternalId() {
        if (!$this->eMail) {
            throw new Exception('No e-mail');
        }
        $response = NetSuite_Service::search('CustomerSearchBasic', 'Customer', array(array('field' => 'email', 'type' => 'string', 'operator' => SearchStringFieldOperator::is, 'searchValue' => $this->eMail)));
        if (isset($response->recordList->record[0])) {
            $record = $response->recordList->record[0];
            return $record->internalId;
        }
        throw new Exception('No customer internal id');
    }

    public function create() {
        if ($this->exists()) {
            $this->internalId = $this->getInternalId();
            Log::debug('netsuite customer exists: ', $this->eMail, ' with internal ID: ', $this->internalId);
            return;
        }
        $customer = new Customer();
        $customer->lastName = $this->lastName;
        $customer->firstName = $this->firstName;
        $customer->phone = $this->address->telephone;
        $customer->isPerson = true;
        $customer->email = $this->eMail;

        $this->address->addToCustomer($customer);

        $this->internalId = NetSuite_Service::add($customer);

        Log::debug('netsuite added customer: ', $this->eMail, ' with ', $this->internalId);

        return $this;
    }

    public function getEntity() {
        $e = new RecordRef();
        $e->internalId = $this->internalId;
        $e->name = $this->fullName;
        return $e;
    }

    public function validate() {
        if (!$this->eMail) {
            throw new Exception('No email for customer');
        }
        if (!$this->fullName) {
            throw new Exception('No name for customer');
        }
        return $this;
    }

}

class NetSuite_Shipments {

    public $shipments;
    public $exceptions;

    public function load() {
        $this->exceptions = array();
        $this->shipments = array();

        $this->_load(array(SalesOrderOrderStatus::_pendingBilling, SalesOrderOrderStatus::_fullyBilled, 'Billed'));

        return $this;
    }

    private function _load($orderStatuses) {
        if (PDEBUG::USE_THIS_NEWEGG_ORDER) {
            $response = NetSuite_Service::search('TransactionSearchBasic', 'salesOrder', array(
                array('field' => 'custom', 'operator' => 'is', 'type' => 'string', 'internalId' => 'custbodystorefront', 'searchValue' => 'Newegg'),
                array('field' => 'custom', 'operator' => 'is', 'type' => 'string', 'internalId' => 'custbodystorefrontorder', 'searchValue' => PDEBUG::USE_THIS_NEWEGG_ORDER),
            ));
        } else {
            $response = NetSuite_Service::search('TransactionSearchBasic', 'salesOrder', array(
                array('field' => 'custom', 'type' => 'boolean', 'internalId' => 'custbodycustbodystorefrontneweggorders', 'searchValue' => false),
                array('field' => 'custom', 'operator' => 'is', 'type' => 'string', 'internalId' => 'custbodystorefront', 'searchValue' => 'Newegg'),
                array('field' => 'orderStatus', 'operator' => 'anyOf', 'type' => 'enummultiselect', 'searchValue' => $orderStatuses),
            ));
        }

        $this->_filter($response, $orderStatuses);
    }

    private function _filter($response, $orderStatuses) {

        foreach ($orderStatuses as &$orderStatus) {
            $orderStatus = str_ireplace('_', '', $orderStatus);
            $orderStatus = strtolower(str_ireplace(' ', '', $orderStatus));
        }

        if (isset($response->recordList->record)) {
            $response->recordList->record == APP::toArray($response->recordList->record);
            foreach ($response->recordList->record as $record) {
                try {
                    $order = NetSuite_Service::get('salesOrder', $record->internalId)->readResponse->record;
                    $status = strtolower(str_ireplace(' ', '', $order->status));
                    if (in_array($status, $orderStatuses) == false) {
                        if ($status == 'closed') { 
                            NetSuite_Order::closed($record->internalId);
                        }
                        continue;
                    }

                    $shipment = NetSuite_Shipment::parseOrder($order);
                    if ($shipment) {
                        $this->shipments[] = $shipment;
                    }
                } catch (Exception $ex) {
                    $this->exceptions[] = $ex;

                    Log::ex($ex, 'loadShipments');
                }
            }
        }
    }

}

class NetSuite_Shipment {

    public $poNumber;
    public $shippingMethod;
    public $shippingCarrier;
    public $trackingNumber;
    public $items;
    public $order;

    public static function parseOrder($order) {
        $instance = new Netsuite_Shipment();
        $instance->order = $order;

        $custbody8TrackingNumber = null;
        foreach ($order->customFieldList->customField as $customField) {
            if ($customField->internalId == 'custbodystorefrontorder') {
                $instance->poNumber = $customField->value;
            } else if ($customField->internalId == 'custbody8') { 
                $custbody8TrackingNumber = $customField->value;
            }
        }

        $shipMethodId = $order->shipMethod->internalId;

        $instance->trackingNumber = APP::get($order, 'linkedTrackingNumbers'); // should only be one
        if (is_array($instance->trackingNumber) && $instance->trackingNumber) {
            $instance->trackingNumber = $instance->trackingNumber[0];
        }
        if (!$instance->trackingNumber && $custbody8TrackingNumber) { 
            $instance->trackingNumber = $custbody8TrackingNumber;
        }

        $instance->prepareShippingDetails($shipMethodId, $instance->trackingNumber, $instance->shippingCarrier, $instance->shippingMethod);

        Log::debug(__METHOD__, ' internal_id=', $shipMethodId, ' tracking_number=', $instance->trackingNumber, ' carrier=', $instance->shippingCarrier, ' method=', $instance->shippingMethod);


        if (false) { 
            if ($shipMethodId == '50233') {
                $instance->shippingMethod = NetSuite_Order::SHIPPING_METHOD_STANDARD_SHIPPING;
                $instance->shippingCarrier = 'UPS'; 
                if ($instance->trackingNumber && strlen($instance->trackingNumber) > 20) {
                    $instance->shippingCarrier = 'USPS';
                }
            } else if ($shipMethodId == '272') {
                $instance->shippingMethod = NetSuite_Order::SHIPPING_METHOD_EXPEDITED_SHIPPING;
                $instance->shippingCarrier = 'UPS';
            } else if ($shipMethodId == '275') {
                $instance->shippingMethod = NetSuite_Order::SHIPPING_METHOD_ONE_DAY_SHIPPING;
                $instance->shippingCarrier = 'UPS';
            } else if ($shipMethodId == '273') {
                $instance->shippingMethod = NetSuite_Order::SHIPPING_METHOD_TWO_DAYS_SHIPPING;
                $instance->shippingCarrier = 'UPS';
            } else if ($shipMethodId == '101488') {
                $instance->shippingMethod = NetSuite_Order::SHIPPING_METHOD_THREE_DAYS_SHIPPING;
                $instance->shippingCarrier = 'UPS';
            }
        }

        $instance->items = array();
        foreach ($order->itemList->item as $item) {
            $item = NetSuite_ShipmentItem::parseItem($item);
            $instance->items[] = $item;
        }

        return $instance;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function prepareShippingDetails($netsuite_internal_id, $tracking_number, & $shipping_carrier, & $shipping_method) {
        if ($netsuite_internal_id == 24362) { 
            $shipping_carrier = 'UPS';
            $shipping_method = 'GROUND';
        } else if ($netsuite_internal_id == 273) { 
            $shipping_carrier = 'UPS';
            $shipping_method = 'PRIORITY';
        } else if ($netsuite_internal_id == 275) { 
            $shipping_carrier = 'UPS';
            $shipping_method = 'EXPRESS';
        } else if ($netsuite_internal_id == 50233) { 
            if (strpos($tracking_number, '1Z') === 0) { 
                $shipping_carrier = 'UPS';
                $shipping_method = 'Ground';
            } else if (strpos($tracking_number, '92748901') === 0) { 
                $shipping_carrier = 'USPS';
                $shipping_method = 'Priority';
            } else if (strpos($tracking_number, '9') === 0 && strlen($tracking_number) >= 18) { 
                $shipping_carrier = 'USPS';
                $shipping_method = 'Priority';
            } else if (strpos($tracking_number, '9') !== 0 && strlen($tracking_number) < 22) { 
                $shipping_carrier = 'FDE';
                $shipping_method = 'Ground';
            } else { 
                $shipping_carrier = 'UPS';
                $shipping_method = 'Ground';
            }
        } else { 
            throw new Exception('invalid netsuite internal id: ' . $netsuite_internal_id);
        }
    }    

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private function _validate() {
        APP::req($this, 'poNumber');
        APP::req($this, 'shippingMethod');
    }

    public function shipped() {
        $record = new SalesOrder();
        $record->internalId = $this->order->internalId;

        $customFields = array();

        $customField = new BooleanCustomFieldRef();
        $customField->internalId = 'custbodycustbodystorefrontneweggorders';
        $customField->value = true;
        $customFields[] = $customField;

        $record->customFieldList = new CustomFieldList();
        $record->customFieldList->customField = $customFields;

        NetSuite_Service::update($record);
    }

}

class NetSuite_ShipmentItem {

    public $itemId;
    public $quantity;

    public static function parseItem($item) {
        $instance = new Netsuite_ShipmentItem();

        $instance->itemId = $item->item->internalId;
        $instance->quantity = $item->quantity;

        $instance->_validate();

        return $instance;
    }

    private function _validate() {
        APP::req($this, 'itemId', true);
        APP::req($this, 'quantity', true);
    }

}

