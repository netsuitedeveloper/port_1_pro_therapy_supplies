<?php

class NewEgg
{
    private $_generalConfig;
    private $_ftpConfig;
    private $_serviceConfig;

    const SHIPPING_CARRIER_UPS = 'UPS';
    const SHIPPING_CARRIER_USPS = 'USPS';
    const SHIPPING_CARRIER_FEDEX = 'FedEx';
    const SHIPPING_CARRIER_DHL = 'DHL';
    const SHIPPING_CARRIER_OTHER = 'Other';

    const DEFAULT_CATEGORY_NAME = 'hwhomehealthcare';

    private static $CATEGORY_NAME_URI_MAP = array(
        'chairsstoolsandseatingaccessories' => 'ChairsStoolsandSeatingAccessories.csv',
        'fitnessaccessories' => 'FitnessAccessories.csv',
        'hwalternativemedicine' => 'HWAlternativeMedicine.csv',
        'hwfootcare' => 'HWFootCare.csv',
        'hwhomehealthcare' => 'HWHomeHealthCare.csv',
        'hwincontinence' => 'HWIncontinence.csv',
        'hwmassagerelaxation' => 'HWMassageRelaxation.csv',
        'hwsleepsnoringaids' => 'HWSleepSnoringAids.csv',
        'hwsupportsbraces' => 'HWSupportsBraces.csv',
        'hwvitaminsmineralssupplements' => 'HWVitaminsMineralsSupplements.csv',
        'sportsmedicine' => 'SportsMedicine.csv',
        'weighttraininghomegyms' => 'WeightTrainingHomeGyms.csv',
        'yogapilatestoning' => 'YogaPilatesToning.csv',
    );

    private static $CSV_REQUIRED_FIELDS = array(
        'sellerpart#' => 'sellerpart#',
        'manufacturer' => 'manufacturer',
        'manufacturerpart#/isbn' => array('manufacturerpart#/isbn' => null, 'upc' => null),
        'upc' => array('manufacturerpart#/isbn' => null, 'upc' => null),
        'websiteshorttitle' => 'websiteshorttitle',
        'productdescription' => 'productdescription',
        'sellingprice' => 'sellingprice', // dot, no dollar
        'shipping' => array('shipping' => array('Default', 'Free')),
        'inventory' => 'inventory',
        'itemimages' => 'itemimages',
        'itemlength' => 'itemlength',
        'itemwidth' => 'itemwidth',
        'itemheight' => 'itemheight',
        'itemweight' => 'itemweight',		'packsorsets' => 'packsorsets',
    );

    const CSV_ACTION_CREATE_ITEM = 'Create Item';
    const CSV_ACTION_UPDATE_ITEM = 'Update Item';
    const CSV_ACTION_REPLACE_IMAGES = 'Replace Image';

    public function __construct($general_config, $ftp_config, $service_config)
    {
        $this->_generalConfig = $general_config;
        $this->_ftpConfig = $ftp_config;
        $this->_serviceConfig = $service_config;
    }

    public function acknowledgeOrderList($order_key)
    {
        $local_dir = $this->_generalConfig['dir_newegg_orders'];

        Log::debug('deleting newegg ftp file: ', $order_key);

        FILE::delete(PATH::combine($local_dir, $order_key));
        if (PDEBUG::DO_NOT_DELETE_ORDERLIST_FILE == false) {
            FTP::delete($this->_ftpConfig, '/Outbound/OrderList/' . $order_key);
        }
    }

    public function downloadOrders()
    {
        $local_dir = $this->_generalConfig['dir_newegg_orders'];

        FTP::downloadDir($this->_ftpConfig, '/Outbound/OrderList', $local_dir, FTP::MODE_ASCII);

        $order_file_map = array();
        $orders = array();
        foreach(PATH::enumerateFiles($local_dir) as $order_uri)
        {
            if (is_dir($order_uri)) {
                continue;
            }

            $order_key = FILE::basename($order_uri);

            try {
                $order_csv = CSV::read($order_uri);

                // do not use 'Order Number' for indexing order number as CSV file has BOM prepended and PHP does not stripe this character,
                // use index '0' instead
                $csv_newegg_orders = APP::toMapArray($order_csv['rows'], '0');

                $order_file_map[$order_key] = array_keys($csv_newegg_orders);

                $general_order_data_headers = array(
                    'Order Shipping Method',
                    'Order Date & Time',
	            'Ship To Address Line 1',
	            'Ship To Address Line 2',
	            'Ship To City',
	            'Ship To State',
	            'Ship To ZipCode',
	            'Ship To Country',
	            'Ship To First Name',
	            'Ship To LastName',
	            'Ship To Company',
	            'Ship To Phone Number',
	            'Order Customer Email',
	            'Order Shipping Method',
                    'Order Total',
                    'Order Shipping Total',
                );

                $item_order_data_headers = array(
                    'Item Seller Part #',
                    'Item Newegg #',
                    'Item Unit Price',
	            'Extend Unit Price',
	            'Item Unit Shipping Charge',
	            'Extend Shipping Charge',
	            'Quantity Ordered',
                );

                foreach($csv_newegg_orders as $order_number => $csv_newegg_order) {
                    $first_csv_row = $csv_newegg_order[0];

                    $newegg_order = array();
                    foreach($general_order_data_headers as $header) {
                        $newegg_order[$header] = $first_csv_row[$header];
                    }
                    foreach($csv_newegg_order as $csv_newegg_order_item) {
                        $newegg_order_item = array();
                        foreach($item_order_data_headers as $header) {
                            $newegg_order_item[$header] = $csv_newegg_order_item[$header];
                        }
                        $newegg_order['items'][] = $newegg_order_item;
                    }

                    $orders[$order_key][$order_number] = $newegg_order;
                }

            } catch(Exception $ex) {
                Log::ex($ex, 'downloadOrders');

                $orders[$order_key] = $ex;
            }
        }

        Log::debug('downloaded orders for: ', array_keys($orders));
        Log::debug('downloaded orders with: ', $order_file_map);

        Log::data('downloaded orders: ', $orders);

        return $orders;
    }

    public function shipNotice($order_number, $shipment)
    {
        $order_xml = array(
            'OrderNumber' => $order_number,
            'ItemInformation' => array(),
            'ShipDate' => DT::getTodayString(),
            'ActualShippingCarrier' => $shipment['ActualShippingCarrier'],
            'ActualShippingMethod' => $shipment['ActualShippingMethod'],
            'TrackingNumber' => $shipment['TrackingNumber'],
            'ShippingFromInformation' => array(
                'ShippingFromAddress' => $this->_generalConfig['ship_from_address'],
                'ShippingFromCity' => $this->_generalConfig['ship_from_city'],
                'ShippingFromState' => $this->_generalConfig['ship_from_state'],
                'ShippingFromZipCode' => $this->_generalConfig['ship_from_zipcode'],
            ),
            'PhoneNumber' => $this->_generalConfig['ship_from_phone_number'],
        );

        foreach($shipment['items'] as $shipment_item) {
            $order_xml['ItemInformation']['Item'][] = array(
                'SellerPartNumber' => $shipment_item['SellerPartNumber'],
                'ShippedQuantity' => $shipment_item['ShippedQuantity'],
            );
        }

        $temp_uri = tempnam('/tmp', $order_number);

        $xml = array(
            'Header' => array(
                'DocumentVersion'  => '1.0'
            ),
            'MessageType' => 'ShipNotice',
            'Message' => array(
                'ShipNotice' => array(
                    'Package' => $order_xml
                ),
            )
        );
        XML::write($temp_uri, $xml, 'NeweggEnvelope');

        /*
        $csv = array('header' => $newegg_order_header, 'rows' => array($newegg_order_row));
        CSV::write($temp_uri, $csv);
        $upload_uri = PATH::combine('Inbound', 'Shipping', $order_number . '.csv');
         */

        $upload_uri = '/Inbound/Shipping/' . $order_number . '.xml';

        Log::debug('uploading ship notice from ', $temp_uri, ' to ', $upload_uri);
        Log::data(file_get_contents($temp_uri));

        FTP::upload($this->_ftpConfig, $temp_uri, $upload_uri, FTP::MODE_ASCII);
    }

    public function getShipNoticeResults(&$exceptions)
    {
        $local_dir = $this->_generalConfig['dir_newegg_results'];

        FTP::downloadDir($this->_ftpConfig, '/Outbound/Shipping', $local_dir, FTP::MODE_ASCII);

        $error_messages = array();
        foreach(PATH::enumerateFiles($local_dir) as $uri) {
            if (is_dir($uri)) {
                continue;
            }

            $result = XML::read($uri);
            Log::data($result);
            Log::info($result);

            $with_error_count = get($result, array('Message', 'ProcessingReport', 'ProcessingSummary', 'WithErrorCount'), 0);
            if (intval($with_error_count)) {
                $order_number = get($result, array('Message', 'ProcessingReport', 'Result', 'AdditionalInfo', 'OrderNumber'), '');
                $error_message = get($result, array('Message', 'ProcessingReport', 'Result', 'ErrorList', 'Error', 'ErrorDescription'), '');
                $error_messages[FILE::basename($uri)] = $error_message . ($order_number ? ' (order-number: ' . $order_number . ')' : '');
            } else {
                $this->acknowledgeShipNoticeResults(FILE::basename($uri));
            }
        }

        $error_messages && Log::error($error_messages);

        return $error_messages;
    }

    public function acknowledgeShipNoticeResults($result_key)
    {
        $local_dir = $this->_generalConfig['dir_newegg_results'];

        Log::debug('deleting newegg result ftp file: ', $result_key);

        FILE::delete(PATH::combine($local_dir, $result_key));
        if (PDEBUG::DO_NOT_DELETE_RESULT_FILE == false) {
            FTP::delete($this->_ftpConfig, '/Outbound/Shipping/' . $result_key);
        }
    }

    public function uploadProducts($upload, $existing_items_csv) {
        Log::data('datafeed: ', $upload);

        $upload_name = $upload['name'];
        $csv = $upload['data'];

        Log::data('datafeed csv: ', $csv);

        $existing_seller_part_numbers = array_keys(APP::toMap($existing_items_csv['rows'], 'Seller Part #'));

        Log::data('existing seller part numbers: ', $existing_seller_part_numbers);
        Log::debug(count($existing_seller_part_numbers), ' existing seller part numbers');

        $category_groups = APP::toMapArray($csv['rows'], 'Newegg Category');

        $category_upload_csvs = array();
        foreach(self::$CATEGORY_NAME_URI_MAP as $name => $file_name) {
            $uri = PATH::combine($this->_generalConfig['dir_newegg_templates'], $file_name);
            $category_upload_csvs[$name] = CSV::read($uri, ',', 2);
        }

        foreach($category_groups as $category_name => $category_rows)
        {
            try {
                $category_upload_csv = APP::get($category_upload_csvs, strtolower($category_name));
                if (!$category_upload_csv) {
                    ErrorReport::add('products', 'Unknown category name ', $category_name, ', using default category');
                    $category_name = self::DEFAULT_CATEGORY_NAME;
                    $category_upload_csv = APP::get($category_upload_csvs, $category_name);
                }
                if (!$category_upload_csv) {
                    throw new CustomException('Missing template for category: ', $category_name);
                }

                $csv = $category_upload_csv;
                foreach($category_rows as $category_row) {
                    try {
                        $csv = $this->_addProductCSVRow($csv, $existing_seller_part_numbers, $category_name, $category_row, $upload_name);
                    } catch(Exception $ex) {
                        Log::ex($ex, 'prepare CSV rows');

                        ErrorReport::add('products', 'prepare product CSV for ' . $upload_name, $ex);
                    }
                }
                $category_upload_csvs[strtolower($category_name)] = $csv;
            } catch(Exception $ex) {
                Log::ex($ex, 'uploadProducts');

                ErrorReport::add('products', 'upload products for ' . $upload_name, $exception);
            }
        }

        foreach($category_upload_csvs as $name => $category_upload_csv) {
            try {
                $temp_name = $name . '.csv';
                $temp_uri = PATH::combine($this->_generalConfig['dir_newegg_product_uploads'], $temp_name);

                CSV::write($temp_uri, $category_upload_csv);

                $upload_uri = PATH::combine('/Inbound/CreateItem', $temp_name);

                if ($category_upload_csv['rows']) {
                    Log::debug('uploading product CSV: ', $temp_uri, ' => ', $upload_uri, ' ', $category_upload_csv);

                    FTP::upload($this->_ftpConfig, $temp_uri, $upload_uri, FTP::MODE_ASCII);
                }
            } catch(Exception $ex) {
                Log::ex($ex, 'uploadProducts to FTP:', $upload_name);

                ErrorReport::add('products', 'upload products to FTP for ' . $upload_name, $ex);
            }
        }

    }

    public function downloadProductReport()
    { 
        $local_dir = $this->_generalConfig['dir_newegg_product_results'];

        FTP::downloadDir($this->_ftpConfig, '/Outbound/CreateItem', $local_dir, FTP::MODE_ASCII);

        $reports = array();

        foreach(PATH::enumerateFiles($local_dir) as $report_uri)
        {
            $report = array();

            if (is_dir($report_uri)) {
                continue;
            }

            try { 
                $report_file_name = FILE::basename($report_uri);

                Log::data($report_uri);
                $report_lines = FILE::readAllLines($report_uri);

                Log::data($report_lines);

                $summary_line = $report_lines[0];
                $summary_report = STRING::splitBy($summary_line, ',');
                foreach($summary_report as $summary_entry) {
                    if (!$summary_entry) {
                        continue;
                    }
                    $summary_entry_parts = STRING::splitBy($summary_entry, ':');
                    if ($summary_entry_parts[0] == 'ProcessedCount') {
                        $processed_count = $summary_entry_parts[1];
                    } else if ($summary_entry_parts[0] == 'SuccessCount') {
                        $success_count = $summary_entry_parts[1];
                    } else if ($summary_entry_parts[0] == 'WithErrorCount') {
                        $with_error_count = $summary_entry_parts[1];
                    } else {
                        throw new CustomException('Unrecognized summary entry in product upload report: ', $summary_entry);
                    }
                }

                if (count($report_lines) > 2) {
                    $header_line = $report_lines[2];
                    $headers = STRING::splitBy($header_line, ',');
                    $headers = APP::trimArray($headers);
                    $headers[] = 'Reason';
                }

                $error_lines = array_slice($report_lines, 3);

                $errors = array();
                foreach($error_lines as $error_line) {
                    $error_line_parts = STRING::splitBy($error_line, ',');
                    $error_line_headers = array_slice($error_line_parts, 0, 4);
                    $error_message = implode(',', array_slice($error_line_parts, 4));

                    $error_line_data = $error_line_headers;
                    $error_line_data[] = $error_message;

                    if (count($headers) == count($error_line_data)) {
                        $error = array_combine($headers, $error_line_data);

                        // server returns an error on duplicate image upload
                        if (stripos($error['Reason'], 'Duplicated request received') === false) {
                            $errors[] = print_r($error, true);
                        }

                    }
                }

                if (!$errors) {
                    $with_error_count = 0;
                }

                $report = array('processed_count' => $processed_count, 'success_count' => $success_count, 'with_error_count' => $with_error_count, 'errors' => $errors);
                if ($with_error_count > 0) {
                    Log::error('product upload with errors: ', $report);
                }
                $reports[] = $report;
            } catch(Exception $ex) {
                Log::ex($ex, 'downloadProductResults');

                ErrorReport::add('products', 'result', $ex);
            }

            if (PDEBUG::DO_NOT_DELETE_PRODUCT_RESULT_FILE == false) {
                FTP::delete($this->_ftpConfig, '/Outbound/CreateItem/' . $report_file_name);
            }

            FILE::delete($report_uri);
        }

        return $reports;
    }

    public function downloadInventoryReport()
    { 
        $local_dir = $this->_generalConfig['dir_newegg_inventory_results'];

        FTP::downloadDir($this->_ftpConfig, '/Outbound/Inventory/', $local_dir, FTP::MODE_ASCII, 'Result_*'.strftime('%y%m%d').'*.xml');

        $reports = array();

        foreach(PATH::enumerateFiles($local_dir) as $report_uri)
        {
            $report = array();

            if (is_dir($report_uri)) {
                continue;
            }

            try { 
                $report_file_name = FILE::basename($report_uri);

                $report = XML::read($report_uri);

                Log::data($report);

                $processed_count = 0;

                foreach(APP::get($report, array('Message', 'ProcessingReport', 'ProcessingSummary'), array()) as $name => $value) {
                    if ($name == 'ProcessedCount') {
                        $processed_count = $value;
                    } else if ($name == 'SuccessCount') {
                        $success_count = $value;
                    } else if ($name == 'WithErrorCount') {
                        $with_error_count = $value;
                    } else {
                        throw new CustomException('Unrecognized summary entry in product upload report: ', $summary_entry);
                    }
                }

                $errors = array();
                foreach(APP::get($report, array('Message', 'ProcessingReport', 'Result'), array()) as $result) {
                    if ($result) {
                        $_error = print_r($result, true);
                        if (stripos($_error, 'processed because the item is currently deactivated') === false) {
                            $errors[] = $_error;
                        }
                    }
                }

                if ($processed_count == 0) {
                    //$errors[] = 'No inventory has been processed for one specific file upload. Please look into your newegg account for further error details!';
                }

                if (!$errors) {
                    $with_error_count = 0;
                }

                $report = array('processed_count' => $processed_count, 'success_count' => $success_count, 'with_error_count' => $with_error_count, 'errors' => $errors);
                if ($with_error_count > 0) {
                    Log::error('inventory upload with errors: ', $report);
                }
                $reports[] = $report;
            } catch(Exception $ex) {
                Log::ex($ex, 'downloadInventoryResults');

                ErrorReport::add('inventory', 'result', $ex);
            }

            //not enough rights
            //if (PDEBUG::DO_NOT_DELETE_INVENTORY_RESULT_FILE == false) {
            //FTP::delete($this->_ftpConfig, '/Outbound/CreateItem/' . $report_file_name);
            //}

            FILE::delete($report_uri);
        }

        return $reports;
    }

    private function _addProductCSVRow($upload_csv, $existing_seller_part_numbers, $category_name,  $datafeed_csv_row, $upload_name)
    {
        $row = array();

        $datafeed_csv_row = ARR::toAssociativeArray($datafeed_csv_row);
        unset($datafeed_csv_row['Newegg Category']);

        $datafeed_csv_headers_used = array();
        $category_upload_headers = $upload_csv['header'];

        $seller_part_number = null;
        $datafeed_headers_no_value = array();
        $missing_header_values = array();

        foreach($category_upload_headers as $upload_header) {
            $normalized_upload_header = $this->_normalizeHeader($upload_header);

            $datafeed_value = $this->_getDataFeedValueByHeader($datafeed_csv_row, $upload_header, $datafeed_csv_headers_used);

            if ($normalized_upload_header == 'action') {
                $row[$upload_header] = null;
                continue;
            }
            if ($datafeed_value || is_numeric($datafeed_value)) {
                if ($normalized_upload_header == 'sellerpart#') {
                    $seller_part_number = $datafeed_value;
                }
                $row[$upload_header] = $datafeed_value;
            } else {
                if (in_array($normalized_upload_header, self::$CSV_REQUIRED_FIELDS) == true && is_array(self::$CSV_REQUIRED_FIELDS[$normalized_upload_header]) == false) { // !!!
                    $missing_header_values[] = $upload_header;
                }
                $datafeed_headers_no_value[] = $upload_header;
                $row[$upload_header] = null;
            }
        }

        if (!$seller_part_number) {
            throw new CustomException('No seller part number for current datefeed row: ', $datafeed_csv_row, ' in ', $upload_name);
        }

        if ($missing_header_values) {
            throw new CustomException('No datafeed value for header ', $missing_header_values, ' for seller part # ', $datafeed_csv_row['Seller Part #'], ' in ', $upload_name);
        }

        if (count($datafeed_csv_headers_used) !== count(array_keys($datafeed_csv_row))) {
            Log::data('CSV data feed with unused columns: ', 'used = ', $datafeed_csv_headers_used,  'row = ', $row, ' empties = ', $datafeed_headers_no_value);

            $unused_headers = array_diff(array_keys($datafeed_csv_row), $datafeed_csv_headers_used);

            //throw new CustomException('Datafeed CSV with unused column values: ', $unused_headers);
            //Log::debug('CSV row ', $seller_part_number, ' with unused columns: ', $unused_headers, ' in ', $upload_name);
        }

        $this->_validateCSVRow($row, $category_name, $upload_name, $seller_part_number);

        $product_attributes_row = $row;
        $product_attributes_row['Action'] = in_array($seller_part_number, $existing_seller_part_numbers) ? self::CSV_ACTION_UPDATE_ITEM : self::CSV_ACTION_CREATE_ITEM; 
        $product_attributes_row['ActivationMark'] = $product_attributes_row['Inventory'] > 0 ? 'True' : 'False'; //$product_attributes_row['ActivationMark'];

        $product_images_row = $row;
        $product_images_row['Action'] = self::CSV_ACTION_REPLACE_IMAGES;

        $upload_csv['rows'][] = $product_attributes_row;
        if ($product_attributes_row['Action'] == self::CSV_ACTION_UPDATE_ITEM) {
            $upload_csv['rows'][] = $product_images_row;
        }

        return $upload_csv;
    }

    private function _validateCSVRow($row, $category_name, $upload_name, $seller_part_number)
    {
        // 1. validate required field alternations
        foreach(self::$CSV_REQUIRED_FIELDS as $name => $value) {
            if (is_array($value) == false) {
                $row_value = $this->_getDataFeedValueByHeader($row, $name);
                if (!$row_value && !is_numeric($row_value)) {
                    throw new CustomException('Missing required column ', $name, ' for category ', $category_name, ' for seller part # ', $seller_part_number, ' for ', $upload_name);
                }
            } else {
                $any_row_value = false;
                foreach($value as $alternation_name => $alternation_values) {
                    $header_used = array();
                    $row_value = $this->_getDataFeedValueByHeader($row, $alternation_name, $header_used);
                    if ($row_value || is_numeric($row_value)) {
                        $any_row_value = true;
                    }
                    if ($alternation_values !== null) {
                        // 2. validate field alternation values
                        if (!$row_value && !is_numeric($row_value)) {
                            throw new CustomException('Missing value for required column ', $name, ' for category ', $category_name, ' for seller part # ', $seller_part_number, ' with allowed values ', $alternation_values, ' for ', $upload_name);
                        } else {
                            $any_alternation_value = false;
                            foreach($alternation_values as $alternation_value) {
                                if ($this->_normalizeHeader($alternation_value) == $this->_normalizeHeader($row_value)) {
                                    $any_alternation_value = true;

                                    $row[array_pop($header_used)] = $alternation_value; // overwrite current value with valid value from required field specification (!)

                                    break;
                                }
                            }
                            if (!$any_alternation_value) {
                                throw new CustomException('Invalid value ', $row_value, ' for column ', $name, ' for category ', $category_name, ' for seller part # ', $seller_part_number, ' with allowed values ', $alternation_values, ' for ' , $upload_name);
                            }
                        }
                    }
                }
                if (!$any_row_value) {
                    throw new CustomException('Missing value for required columns ', $value, ' for category ', $category_name, ' for seller part # ', $seller_part_number, ' for ', $upload_name);
                }
            }

        }
    }

    private function _normalizeHeader($header)
    {
        return strtolower(str_ireplace(' ', '', str_ireplace('_', '', $header)));
    }

    private function _getDataFeedValueByHeader($datafeed_csv_row, $upload_header, &$datafeed_csv_headers_used = null)
    {
        $normalized_upload_header = $this->_normalizeHeader($upload_header);
        $headers = array_keys($datafeed_csv_row);

        foreach($headers as $header) {
            if ($this->_normalizeHeader($header) == $normalized_upload_header) {
                if ($datafeed_csv_headers_used !== null) {
                    $datafeed_csv_headers_used[] = $header;
                }
                return $datafeed_csv_row[$header];
            }
        }

        return null;
    }

    public function getInventory() {
        $local_dir = $this->_generalConfig['dir_newegg_inventory'];

        PATH::deleteFiles($local_dir);

        $todays_inventory = null;

        $todays_inventory = FTP::nlist($this->_ftpConfig, '/Outbound/Inventory', strftime('%y%m%d').'_*.zip');

        $inventory_csv = null;

        //using last inventory ?

        if ($todays_inventory) {
            $remote_inventory_path = $todays_inventory[count($todays_inventory)-1];

            $local_inventory_path = PATH::combine($local_dir, basename($remote_inventory_path));

            FTP::download($this->_ftpConfig, $remote_inventory_path, $local_inventory_path, FTP::MODE_BINARY);

            foreach(PATH::enumerateFiles($local_dir) as $inventory_uri)
            {
                if (is_dir($inventory_uri)) {
                    continue;
                }

                $inventory_file_name = FILE::basename($inventory_uri);

                $inventory_data = ZIP::unzipFirstFile($inventory_uri);

                $temp_uri = PATH::combine($local_dir, 'inventory.csv');

                file_put_contents($temp_uri, $inventory_data);

                $inventory_csv = CSV::read($temp_uri, ',', 2);
                if (PDEBUG::DO_NOT_DELETE_INVENTORY_FILE == false) {
                    FTP::delete($this->_ftpConfig, '/Outbound/Inventory/' . $inventory_file_name);
                }

                FILE::delete($temp_uri);

                if (!$inventory_csv) {
                    throw new CustomException('Unable to get inventory file from server');
                }

                break;
            }
        }

        if (!$inventory_csv) {
            $inventory_csv = TEMP::popValue(null, 'inventory');
        } else {
            TEMP::pushValue($inventory_csv, 'inventory');
        }

        if (!$inventory_csv) {
            throw new CustomException('Unable to get inventory file from server');
        }

        Log::data('inventory: ', $inventory_csv);

        return $inventory_csv;
    }

    public function uploadInventory($upload, $existing_items_csv) {
        Log::data('inventory datafeed: ', $upload);

        $upload_name = $upload['name'];
        $csv = $upload['data'];

        Log::data('datafeed csv: ', $csv);

        $existing_seller_part_numbers = array_keys(APP::toMap($existing_items_csv['rows'], 'Seller Part #'));

        Log::data('existing seller part numbers: ', $existing_seller_part_numbers);
        Log::debug(count($existing_seller_part_numbers), ' existing seller part numbers');

        if (!$existing_seller_part_numbers) {
            throw new Exception('No existing seller part numbers. Check your implementation!');
        }

        $inventory_upload_xml = array(
            'Header' => array(
                'DocumentVersion'  => '1.0'
            ),
            'MessageType' => 'Inventory',
            'Message' => array(
                'Inventory' => array(
                    'Item' => array(),
                ),
            )
        );

        foreach($csv['rows'] as $row) {
            try {
                if (!trim($row['Seller Part #'])) {
                    throw new CustomException('Missing Seller Part # in row: ', print_r($row));
                }
                if (in_array($row['Seller Part #'], $existing_seller_part_numbers) == false) {
                    continue;
                }
                $inventory_upload_xml['Message']['Inventory']['Item'][] = array(
                    'SellerPartNumber' => trim($row['Seller Part #']),
                    'Inventory' => $row['Inventory'],
                    'ActivationMark' => $row['Inventory'] > 0 ? 'True' : 'False',
                );
            } catch(Exception $ex) {
                Log::ex($ex, 'uploadInventory');

                ErrorReport::add('inventory', 'upload inventory for ' . $upload_name, $exception);
            }
        }

        if ($csv['rows']) {
            $name = "inventory-upload-" . strftime('%H%M%S') . '.xml';
            try {
                $temp_uri = tempnam('/tmp', 'inventory-upload');

                XML::write($temp_uri, $inventory_upload_xml, 'NeweggEnvelope');

                if (PDEBUG::COPY_INVENTORY_XML_FILE_TO_TEMP_DIR) {
                    $copy_path = PATH::combine($this->_generalConfig['dir_newegg_inventory_results'], $name);

                    copy($temp_uri, $copy_path);
                }

                $upload_uri = PATH::combine('/Inbound/Inventory', $name);

                if ($inventory_upload_xml) {
                    Log::debug('uploading inventory XML: ', $temp_uri, ' => ', $upload_uri, ' ', $inventory_upload_xml);

                    FTP::upload($this->_ftpConfig, $temp_uri, $upload_uri, FTP::MODE_ASCII);
                }
            } catch(Exception $ex) {
                Log::ex($ex, 'uploadInventory to FTP:', $upload_name);

                ErrorReport::add('inventory', 'upload inventory to FTP for ' . $upload_name, $ex);
            }
        }

    }

}
