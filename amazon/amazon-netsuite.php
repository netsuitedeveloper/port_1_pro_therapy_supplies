<?php

///////////////////////////////////////////////////////////////////////////////////////////////////

class AmazonNetsuite
{
    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public $mws;
    public $netsuite;

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct($amazon_config, $amazon_inventory_config, $netsuite_config) { 
        $this->mws = new AmazonMWS($amazon_config);
        $this->netsuite = new Netsuite($netsuite_config);
        $this->inventory = new AmazonInventory($amazon_inventory_config);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function getAmazonItemsMap($path = null) { 
        if (is_null($path)) { 
            $path = __DIR__ . '/amazon-items.csv';
        }
        $amazon_items = CSV::read($path);

        return APP::toMap($amazon_items['rows'], 'amazon_sku');
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function updateAmazonItemsMap($amazon_skus, $path = null) { 
        if (is_null($path)) { 
            $path = __DIR__ . '/amazon-items.csv';
        }
        $this->netsuite->exportInventoryItemsByAmazonSku($amazon_skus, $path);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function updateInventoryItems() { 
        $shipments = null;
        $inventory = null;

        $request_id_shipments = $this->mws->requestReportAsync('_GET_AMAZON_FULFILLED_SHIPMENTS_DATA_', array(
            'StartDate' => DT::getBefore('1month')->format('c'),
        ));
        $request_id_orders = $this->mws->requestReportAsync('_GET_FLAT_FILE_ALL_ORDERS_DATA_BY_LAST_UPDATE_', array(
            'StartDate' => DT::getBefore('2days')->format('c'),
        ));
        $inventory = $this->inventory->listInventorySupply(array(
            'QueryStartDateTime' => DT::getBefore('2 day')->format('c'),
            //'SellerSkus' => Config::$USE_AMAZON_SKUS,
        ));

        $report_id_shipments = $this->mws->waitForReport($request_id_shipments);
        $report_id_orders = $this->mws->waitForReport($request_id_orders);

        if (isset($report_id_shipments)) { 
            $shipments = $this->mws->getReport($report_id_shipments);
        }
        if (isset($report_id_orders)) { 
            $orders = $this->mws->getReport($report_id_orders);
        }

        Log::debug('updateInventoryItems inventory count=', count($inventory));
        Log::debug('updateInventoryItems orders count=', isset($orders) ? count(get_or_null($orders, 'rows')) : null);

        if (isset($orders)) { 
            $seller_skus = null;
            foreach($orders['rows'] as $row) { 
                $sku = $row['sku'];
                $seller_skus[] = $sku;
            }
            if ($seller_skus) { 
                $inventory_orders = $this->inventory->listInventorySupply(array(
                    'SellerSkus' => $seller_skus,
                ));
                Log::debug('updateInventoryItems order inventory count=', count($inventory_orders));
                $inventory = array_merge_ex($inventory, $inventory_orders);
            }
        }

        Log::debug('updateInventoryItems total inventory count=', count($inventory));
        Log::debug('updateInventoryItems inventory=', $inventory);

        $data = null;

        $amazon_item_map = $this->getAmazonItemsMap();

        $unknown_amazon_skus = null;
        $unknown_data = null;
        if (isset($shipments)) { 
            foreach($shipments['rows'] as $row) { 
                if (isset($amazon_item_map[$row['sku']])) { 
                    $internal_id = $amazon_item_map[$row['sku']]['internal_id'];
                    if (isset($data[$internal_id]) == false) { 
                        $data[$internal_id]['internal_id'] = $internal_id;
                        $data[$internal_id]['fba_30_days_sold'] = 0;
                    }
                    $data[$internal_id]['fba_30_days_sold'] += $row['quantity-shipped'];
                } else { 
                    $unknown_amazon_skus[] = $row['sku'];

                    $internal_id = $row['sku'];
                    if (isset($unknown_data[$row['sku']]) == false) { 
                        $unknown_data[$internal_id]['internal_id'] = $internal_id;
                        $unknown_data[$internal_id]['fba_30_days_sold'] = 0;
                    }
                    $unknown_data[$internal_id]['fba_30_days_sold'] += $row['quantity-shipped'];
                }
            }
        }
        if (isset($inventory)) { 
            foreach($inventory as $item) { 
                if (isset($item['SellerSKU']) == false) { 
                    Log::warning('item without seller sku: ', $item);
		    continue;
                }
                if (isset($amazon_item_map[$item['SellerSKU']])) { 
                    $internal_id = $amazon_item_map[$item['SellerSKU']]['internal_id'];
                    if (isset($data[$internal_id]) == false) { 
                        $data[$internal_id]['internal_id'] = $internal_id;
                    }
                    $data[$internal_id]['fba_qty'] = $item['TotalSupplyQuantity'];
                } else { 
                    $unknown_amazon_skus[] = $item['SellerSKU'];

                    $internal_id = $item['SellerSKU'];
                    if (isset($data[$internal_id]) == false) { 
                        $unknown_data[$internal_id]['internal_id'] = $internal_id;
                    }
                    $unknown_data[$item['SellerSKU']]['fba_qty'] = $item['TotalSupplyQuantity'];
                }
            }
        }
        $unknown_amazon_skus = array_unique_ex($unknown_amazon_skus);

        if ($data) { 
            Log::data('updating inventory items with known skus: ', $data);

            $this->netsuite->updateInventoryItems($data);
        }
        if ($unknown_amazon_skus) { 
            Log::debug('unknown amazon skus ', $unknown_amazon_skus);

            $this->updateAmazonItemsMap($unknown_amazon_skus);

            $amazon_item_map = $this->getAmazonItemsMap();

            foreach($unknown_data as $sku => $values) {
                if (isset($amazon_item_map[$sku])) {
                    $internal_id = $amazon_item_map[$sku]['internal_id'];
                    unset($unknown_data[$sku]);
                    $values['internal_id'] = $internal_id;
                    $unknown_data[$internal_id] = $values;
                } else { 
                    unset($unknown_data[$sku]);
                    Log::warning('unknown amazon sku after update: ', $sku);
                }
            }
            if ($unknown_data) { 
                $this->netsuite->updateInventoryItems($unknown_data);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

}

///////////////////////////////////////////////////////////////////////////////////////////////////

