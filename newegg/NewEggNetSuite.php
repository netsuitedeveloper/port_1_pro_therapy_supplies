<?php

class NewEggNetSuite
{
    private static $_configs;

    public static function ping($config, $newegg_config, $netsuite_config)
    {
        self::_init($config, $newegg_config, $netsuite_config);

        Log::debug(self::$_configs);

        $ftp_config = self::$_configs['newegg_ftp'];

        //$newegg = new NewEgg(self::$_configs['general'], self::$_configs['newegg_ftp'], self::$_configs['newegg_service']);

        $list = FTP::nlist($ftp_config, '/Outbound/Inventory');
        //$list = FTP::nlist($ftp_config, '/Inbound/Inventory');
        //$local_dir = 'c:/myspace/tmp';
        //FTP::downloadDir($ftp_config, '/Outbound/Inventory', $local_dir, FTP::MODE_ASCII, '*Result*');

        print_r($list);

    }

    public static function syncShipments($config, $newegg_config, $netsuite_config)
    {
        self::_init($config, $newegg_config, $netsuite_config);

        $netsuite = new NetSuite(self::$_configs['general'], self::$_configs['netsuite']);

        $exceptions = array();
        $shipments = $netsuite->getShipments($exceptions);

        foreach($exceptions as $exception) {
            ErrorReport::add('shipments', 'get shipment from netsuite', $exception);
        }

        $newegg = new NewEgg(self::$_configs['general'], self::$_configs['newegg_ftp'], self::$_configs['newegg_service']);

        foreach($shipments as $shipment) {
            try {
                $ship_notice = array();

                foreach($shipment->items as $shipment_item) {
                    $ship_notice['items'][] = array(
                        'SellerPartNumber' => $shipment_item->itemId,
                        'ShippedQuantity' => $shipment_item->quantity,
                    );
                }

                $ship_notice['ActualShippingCarrier'] = $shipment->shippingCarrier;
                $ship_notice['ActualShippingMethod'] = $shipment->shippingMethod;
                $ship_notice['TrackingNumber'] = $shipment->trackingNumber;

                $newegg->shipNotice($shipment->poNumber, $ship_notice);
            } catch(Exception $ex) {
                Log::ex($ex, 'syncShipments: ', $shipment->poNumber);

                ErrorReport::add('shipments', 'create ship notice', $ex);
            }

            try {
                if (PDEBUG::DO_NOT_MARK_AS_SHIPPED_ON_NETSUITE == false) {
                    $shipment->shipped();
                }
            } catch(Exception $ex) {
                Log::ex($ex, 'set custom field for neweggordershipped');

                ErrorReport::add('shipments', 'set custom field for neweggordershipped', $ex);
            }

        }

        $error_messages = $newegg->getShipNoticeResults($exceptions);
        if ($error_messages) {
            foreach($error_messages as $key => $error_message) {
                ErrorReport::add('shipments', 'create ship notice', $error_message);

                $newegg->acknowledgeShipNoticeResults($key);
           }
        }

        self::_sendErrorMail('shipments');
    }

    public static function syncOrders($config, $newegg_config, $netsuite_config)
    {
        self::_init($config, $newegg_config, $netsuite_config);

        $newegg = new NewEgg(self::$_configs['general'], self::$_configs['newegg_ftp'], self::$_configs['newegg_service']);
        $netsuite = new NetSuite(self::$_configs['general'], self::$_configs['netsuite']);

        $orders = $newegg->downloadOrders(); 

        foreach($orders as $order_key => $order_list) {
            try {
                if ($order_list instanceof Exception) {
                    ErrorReport::add('orders', 'download orders', $ex);

                    continue;
                }
                foreach($order_list as $order_number => $newegg_order)  {
                    try {
                        if (PDEBUG::USE_THIS_NEWEGG_ORDER && $order_number != PDEBUG::USE_THIS_NEWEGG_ORDER) {
                            continue;
                        }

                        $netsuite->placeOrder($order_number, $newegg_order);
                    } catch(Exception $ex) {
                        Log::ex($ex, 'placeOrder');

                        ErrorReport::add('orders', 'place order in netsuite', $ex);
                    }
                }

                $newegg->acknowledgeOrderList($order_key);

            } catch(Exception $ex) {
                Log::ex($ex, 'syncOrders');

                ErrorReport::add('orders', 'synchronize orders', $ex);
            }
        }

        self::_sendErrorMail('orders');
    }

    public static function syncProducts($config, $newegg_config, $netsuite_config)
    {
        try {
            self::_init($config, $newegg_config, $netsuite_config);

            $newegg = new NewEgg(self::$_configs['general'], self::$_configs['newegg_ftp'], self::$_configs['newegg_service']);

            $existing_items_csv = $newegg->getInventory();

            $length = (PDEBUG::USE_NUMBER_OF_CSV_FILES ? PDEBUG::USE_NUMBER_OF_CSV_FILES : count(self::$_configs['general']['product_urls']));

            for ($i = 0; $i < $length; $i++) {
                try {
                    $url = self::$_configs['general']['product_urls'][$i];

                    if (PDEBUG::UPLOAD_THIS_NEWEGG_CSV !== false) {
                        $product_csv = array('name' => PDEBUG::UPLOAD_THIS_NEWEGG_CSV, 'data' => CSV::read(PDEBUG::UPLOAD_THIS_NEWEGG_CSV));
                    } else {

                        $csv_content = CURL::get($url);

                        Log::data('csv content for ' . $i . ': ', $csv_content);

                        $product_csv = array('name' => 'CSV #' . strval($i+1), 'data' => CSV::readString($csv_content));
                    }

                    if (is_numeric(PDEBUG::USE_NUMBER_OF_LINES_IN_CSV_FILE)) {
                        $product_csv['data']['rows'] = array_slice($product_csv['data']['rows'], 0, PDEBUG::USE_NUMBER_OF_LINES_IN_CSV_FILE);
                    }

                    $newegg->uploadProducts($product_csv, $existing_items_csv);

                    if (PDEBUG::UPLOAD_THIS_NEWEGG_CSV !== false) {
                        break;
                    }
                } catch(Exception $ex) {
                    Log::ex($ex, 'product upload');

                    ErrorReport::add('products', 'product upload for ' . $product_csv ? $product_csv['name'] : 'unknown', $ex);
                }
            }
        } catch(Exception $ex) {
            Log::ex($ex, 'product upload');

            ErrorReport::add('products', 'product upload', $ex);
        }

        self::_sendErrorMail('products');
    }

    public static function syncProductResults($config, $newegg_config, $netsuite_config)
    {
        self::_init($config, $newegg_config, $netsuite_config);

        try {
            $newegg = new NewEgg(self::$_configs['general'], self::$_configs['newegg_ftp'], self::$_configs['newegg_service']);
            $reports = $newegg->downloadProductReport();

            Log::data($reports);

            foreach($reports as $report) {
                if ($report['with_error_count'] > 0 && $report['errors']) { 
                    ErrorReport::add('products', 'product upload', $report['errors']);
                }
            }
        } catch(Exception $ex) {
            Log::ex($ex, 'syncProductResults');

            ErrorReport::add('products', 'product results', $ex);
        }

        self::_sendErrorMail('products');
    }

    public static function syncInventoryResults($config, $newegg_config, $netsuite_config)
    {
        self::_init($config, $newegg_config, $netsuite_config);

        try {
            $newegg = new NewEgg(self::$_configs['general'], self::$_configs['newegg_ftp'], self::$_configs['newegg_service']);
            $reports = $newegg->downloadInventoryReport();

            Log::data($reports);

            foreach($reports as $report) {
                if ($report['with_error_count'] > 0 && $report['errors']) { 
                    ErrorReport::add('inventory', 'inventory upload', $report['errors']);
                }
            }
        } catch(Exception $ex) {
            Log::ex($ex, 'syncProductResults');

            ErrorReport::add('products', 'product results', $ex);
        }

        self::_sendErrorMail('inventory');
    }

    public static function syncInventory($config, $newegg_config, $netsuite_config)
    {
        try {
            self::_init($config, $newegg_config, $netsuite_config);

            $newegg = new NewEgg(self::$_configs['general'], self::$_configs['newegg_ftp'], self::$_configs['newegg_service']);

            $existing_items_csv = $newegg->getInventory();

            $length = (PDEBUG::USE_NUMBER_OF_CSV_FILES ? PDEBUG::USE_NUMBER_OF_CSV_FILES : count(self::$_configs['general']['product_urls']));

            for ($i = 0; $i < $length; $i++) {
                try {
                    $url = self::$_configs['general']['product_urls'][$i];

                    if (PDEBUG::UPLOAD_THIS_NEWEGG_CSV !== false) {
                        $inventory_csv = array('name' => PDEBUG::UPLOAD_THIS_NEWEGG_CSV, 'data' => CSV::read(PDEBUG::UPLOAD_THIS_NEWEGG_CSV));
                    } else {
                        $csv_content = CURL::get($url);

                        Log::data('csv content for ' . $i . ': ', $csv_content);

                        $inventory_csv = array('name' => 'CSV #' . strval($i+1), 'data' => CSV::readString($csv_content));
                    }

                    if (is_numeric(PDEBUG::USE_NUMBER_OF_LINES_IN_CSV_FILE)) {
                        $inventory_csv['data']['rows'] = array_slice($inventory_csv['data']['rows'], 0, PDEBUG::USE_NUMBER_OF_LINES_IN_CSV_FILE);
                    }
                    $newegg->uploadInventory($inventory_csv, $existing_items_csv);

                    if (PDEBUG::UPLOAD_THIS_NEWEGG_CSV !== false) {
                        break;
                    }
                } catch(Exception $ex) {
                    Log::ex($ex, 'inventory upload');

                    ErrorReport::add('inventory', 'inventory upload for ' . $inventory_csv ? $inventory_csv['name'] : 'unknown', $ex);
                }
            }
        } catch(Exception $ex) {
            Log::ex($ex, 'inventory upload');

            ErrorReport::add('inventory', 'inventory upload', $ex);
        }

        self::_sendErrorMail('inventory');
    }

    public static function custom($config, $newegg_config, $netsuite_config)
    {
        self::_init($config, $newegg_config, $netsuite_config);

        $netsuite = new NetSuite(self::$_configs['general'], self::$_configs['netsuite']);

        $newegg = new NewEgg(self::$_configs['general'], self::$_configs['newegg_ftp'], self::$_configs['newegg_service']);

	$newegg->getInventory();
        //$netsuite_shipments = new NetSuite_Shipments();
        //$netsuite_shipments->load();

        //$shipments = $netsuite_shipments->shipments;
        //$exceptions = $netsuite_shipments->exceptions;

        //Log::info('SHIPMENTS: ', $shipments);

        //Log::data('current shipments: ', $shipments);
        //Log::debug('shipments for orders: ', array_keys(APP::toMap($shipments, 'poNumber')));

    }
 
    private static function _init($general_config, $newegg_config, $netsuite_config)
    {
        self::$_configs = array();

        $config = array();
        foreach($general_config as $name => $value) {
            if (stripos($name, 'DIR_') === 0) {
                if (!file_exists($value)) {
                    if (mkdir($value) === false) {
                        throw new CustomException('Unable to create directory: ', $value);
                    }
                }
            }
            $name = strtolower($name);
            $config[$name] = $value;
        }
        self::$_configs['general'] = $config;

        $newegg_ftp = array();
        $newegg_service = array();
        foreach($newegg_config as $name => $value) {
            if (stripos($name, 'FTP_') === 0) {
                $newegg_ftp[strtolower(str_ireplace('FTP_', '', $name))] = $value;
            } else if (stripos($name, 'SERVICE_') === 0) {
                $newegg_service[strtolower(str_ireplace('SERVICE_', '', $name))] = $value;
            }
        }
        self::$_configs['newegg_ftp'] = $newegg_ftp;
        self::$_configs['newegg_service'] = $newegg_service;

        $netsuite = array();
        foreach($netsuite_config as $name => $value) {
            $netsuite[strtolower($name)] = $value;
        }
        self::$_configs['netsuite'] = $netsuite;

        Log::debug(self::$_configs);
    }

    private static function _sendErrorMail($action, $content = false) {
        $uri = self::$_configs['general']['uri_email_template'];
        $from = self::$_configs['general']['email_from'];
        $tos = self::$_configs['general']['email_tos'][$action];
		
		Log::debug(self::$_configs['general']['email_tos'][$action]);

        ErrorReport::sendErrorMail($action, $from, $tos, $uri, $content);
    }

}
