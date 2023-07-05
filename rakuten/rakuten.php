<?php

class Rakuten
{
    private static $_instance;

    private $_report;

    private static function instance() {
        if (!self::$_instance) {
            self::$_instance = new Rakuten();
        }
        return self::$_instance;
    }

    public static function ping() {
        echo "usage: php -f index.php -- [sync-shipments|sync-inventory|sync-results]" . PHP_EOL;
    }

    public static function syncResults() {
        try {
            self::instance()->_syncResults('shipments', 'Fulfillment');
        } catch(Exception $ex) {
            ErrorReport::add('shipments', 'fatal error during operation', $ex);
        }
        self::_sendErrorMail('shipments');
        try {
            self::instance()->_syncResults('inventory', 'Inventory');
        } catch(Exception $ex) {
            ErrorReport::add('inventory', 'fatal error during operation', $ex);
        }
        self::_sendErrorMail('inventory');
    }

    public static function syncShipments() {
        try {
            self::instance()->_syncShipments();
        } catch(Exception $ex) {
            ErrorReport::add('shipments', 'fatal error during operation', $ex);
        }
        self::_sendErrorMail('shipments');
    }

    public static function syncOrders() {
        try {
            self::instance()->_syncOrders();
        } catch(Exception $ex) {
            ErrorReport::add('orders', 'fatal error during operation', $ex);
        }
        self::_sendErrorMail('orders');
    }

    public static function syncInventory() {
        try {
            self::instance()->_syncInventory();
        } catch(Exception $ex) {
            ErrorReport::add('inventory', 'fatal error during operation', $ex);
        }
        self::_sendErrorMail('inventory');
    }

    private function __construct() {
        ErrorReport::init('Rakuten');
    }

    private function _syncShipments() {
        $path = $this->_downloadFeed(Config::getRakutenShippingFeed());

        $hash = md5(file_get_contents($path));

        $previous_hash = TEMP::popValue('', 'rakuten-shipments-hash');

        if ($previous_hash == $hash) {
            Log::debug('No changes in rakuten shipments feed');

            return;
        }

        $ftp = Config::getRakutenFTP();

        $local_path = $path;

        $file_name = 'Shipments-' . preg_replace('/[^a-zA-Z0-9]/', '-', DT::getNowString());

        $remote_path = '/Fulfillment/' . $file_name . '.txt';

        FTP::upload($ftp, $local_path, $remote_path, FTP::MODE_ASCII);

        TEMP::pushValue($hash, 'rakuten-shipments-hash');
        TEMP::pushValue($file_name, 'rakuten-shipments-last-upload-shipments');
    }

    private function _syncInventory() {
        $feeds = Config::getRakutenInventoryFeeds();

        foreach($feeds as $feed) {
            try {
                Log::debug('syncing inventory feed: ', $feed);

                $path = $this->_downloadFeed($feed);

                $hash = md5(file_get_contents($path));

                $previous_hash = TEMP::popValue(array(), 'rakuten-inventory-hash-' . $feed);

                if ($previous_hash == $hash) {
                    Log::debug('No changes in rakuten inventory feed');

                    continue;
                }

                $ftp = Config::getRakutenFTP();

                $local_path = $path;

                $file_name = 'Inventory-' . preg_replace('/[^a-zA-Z0-9]/', '-', DT::getNowString());

                $remote_path = '/Inventory/' . $file_name . '.txt';

                FTP::upload($ftp, $local_path, $remote_path, FTP::MODE_ASCII);

                TEMP::pushValue($hash, 'rakuten-inventory-hash-' . $feed);

                TEMP::pushValue($file_name, 'rakuten-inventory-last-upload-' . $feed);
            } catch(Exception $ex) {
                ErrorReport::add('inventory', 'fatal error during operation for ' . $feed, $ex);
            }
        }

    }

    private function _syncResults($action, $base_folder) {
        if ($action == 'inventory') {
            $feeds = Config::getRakutenInventoryFeeds();
        } else if ($action == 'shipments') {
            $feeds = array('shipment');
        }

        foreach($feeds as $feed) {
            try { 
                Log::debug('results for: ', $feed);

                $last_upload = TEMP::popValue(null, 'rakuten-' . $action . '-last-upload-' . $feed);
                $last_result = TEMP::popValue(null, 'rakuten-' . $action . '-last-result-' . $feed);

                if ($last_upload && $last_result != $last_upload) {
                    $ftp = Config::getRakutenFTP();

                    $file_name = $last_upload . '.resp';

                    $remote_dir = $base_folder . '/Archive/';
                    $remote_path = $remote_dir . $file_name;

                    $local_path = tempnam('/tmp', 'rakuten');

                    try {
                        $list = FTP::nlist($ftp, $remote_dir, $file_name);

                        if ($list) {
                            FTP::download($ftp, $remote_path, $local_path, FTP::MODE_ASCII);
                        } else {
                            Log::debug('No new result file for: ', $file_name);

                            continue;
                        }
                    } catch(Exception $ex) {
                        ErrorReport::add($action, 'unable to download response file', $ex);

                        continue;
                    }

                    if (file_exists($local_path)) {
                        $result = CSV::read($local_path, "\t");

                        Log::data('rakuten-' . $action . '-results ', $result);

                        if ($result['rows']) {
                            foreach($result['rows'] as $row) {
                                if ($action == 'shipments') {
                                    if (isset($row['Result']) && $row['Result'] == false) {
                                        $error = array(
                                            'ItemId' => APP::get($row, 'ItemId'),
                                            'Message' => APP::get($row, 'Message'),
                                        );
                                        if (stripos(print_r($error, true), 'Quantity specified exceeds remaining quantity') !== false) {
                                            continue;
                                        }
                                        if (stripos(print_r($error, true), 'Ship Date cannot be before the Order Received Date or scheduled too far into the future from the Order') !== false) {
                                            continue;
                                        }
                                        ErrorReport::add($action, $error['ItemId'], $error['Message']);
                                    } else if (isset($row['Result']) == false) {
                                        ErrorReport::add($action, 'fatal error', print_r($row, true));
                                    }
                                } else if ($action == 'inventory') {
                                    if ($row['WasSuccessful'] == false) {
                                        $error = array(
                                            'Reference Id' => APP::get($row, 'ReferenceId'),
                                            'Error Code' => APP::get($row, 'ErrorCode'),
                                            'Error Message' => APP::get($row, 'ErrorMessage'),
                                        );
					if (stripos(print_r($error, true), 'This listing is already closed') !== false) {
					    continue;
					}
                                        ErrorReport::add($action, $error['Reference Id'], $error['Error Message']);
                                    }
                                }
                            }
                        } else if ($result['header']) {
                            foreach($result['header'] as $fatal_error) {
                                ErrorReport::add($action, 'fatal error', $fatal_error);
                            }
                        }
                        TEMP::pushValue($last_upload, 'rakuten-' . $action . '-last-result-' . $feed);
                    }
                } else {
                    Log::debug('No new result file necessary for: ', $action);
                }
            } catch(Exception $ex) {
                ErrorReport::add($action, 'fatal error during operation' . $feed, $ex);
            }
        }
    }

    private function _syncOrders() { 
        $netsuite = new NetSuite(null, null);

        $orders = $this->_downloadOrders(); 

        Log::data('rakuten orders ', $orders);

        foreach($orders as $order_key => $order_list) {
            try {
                if ($order_list instanceof Exception) {
                    ErrorReport::add('orders', 'download orders', $order_list);

                    continue;
                }
                foreach($order_list as $order_number => $rakuten_order)  {
                    try {
                        if (APP::getClassConstant('PDEBUG', 'USE_THIS_RAKUTEN_ORDER') && $order_number != PDEBUG::USE_THIS_RAKUTEN_ORDER) {
                            continue;
                        }

                        $netsuite->placeRakutenOrder($order_number, $rakuten_order);
                    } catch(Exception $ex) {
                        Log::ex($ex, 'placeOrder');

                        ErrorReport::add('orders', 'place order in netsuite', $ex);
                    }
                }

                $this->_acknowledgeOrderList($order_key);

            } catch(Exception $ex) {
                Log::ex($ex, 'syncOrders');

                ErrorReport::add('orders', 'synchronize orders', $ex);
            }
        }

        self::_sendErrorMail('orders');
    }

    private function _downloadFeed($url) {
        $data = CURL::get($url);

        $path = FILe::writeToTemporary($data);

        return $path;
    }

    private function _acknowledgeOrderList($key) {
        $file_name = $key;
        $source_path = '/Orders/' . $file_name;
        $target_path = '/Orders/Archive/' . $file_name;

        $ftp = Config::getRakutenFTP();

        Log::debug('moving rakuten order file to archive: ', $key);

        if (APP::getClassConstant('PDEBUG', 'DO_NOT_ACKNOWLEDGE_ORDER_FILE')) {
            Log::debug('acknowledge is deactivated, order file stays in orders directory');
        }

        FTP::move($ftp, $source_path, $target_path, FTP::MODE_ASCII);
    }

    private function _downloadOrders() {
        $orders = array(); 

        $ftp = Config::getRakutenFTP();

        $list = FTP::nlist($ftp, '/Orders');
        if ($list == false || (count($list) == 1 && $list[0] == 'Archive')) {
            return $orders;
        }

        foreach($list as $file_path) {
            $file_name = basename($file_path);

            if (stripos($file_name,  'Archive') !== false) {
                continue;
            }

            try {
                $path = $file_name == $file_path ? '/Orders/' . $file_name : $file_path;

                $local_path = tempnam('/tmp', 'rakuten');

                FTP::download($ftp, $path, $local_path, FTP::MODE_ASCII);

                $data = CSV::read($local_path, "\t");

                $order_list = array();

                foreach($data['rows'] as $row) {
                    $order_number = $row['Receipt_ID'];

                    $order_list[$order_number][] = $row;
                }

                $orders[$file_name] = $order_list;

            } catch(Exception $ex) {
                Log::ex($ex, 'rakuten downloadOrders');

                $orders[$file_name] = $ex;
            }
        }

        return $orders;
    }

    private static function _sendErrorMail($action, $content = false) {
        $uri = PATH::combine(EMailConfig::$URI_EMAIL_TEMPLATE[0], EMailConfig::$URI_EMAIL_TEMPLATE[1]);

        $from = EMailConfig::$RAKUTEN['from'];
        $tos = EMailConfig::$RAKUTEN['tos'][$action];

        ErrorReport::sendErrorMail($action, $from, $tos, $uri, $content);
    }

};

