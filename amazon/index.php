<?php

///////////////////////////////////////////////////////////////////////////////////////////////////

require_once 'config.php';
require_once Config::PHP_LIB;
require_once 'amazon-mws.php';
require_once 'amazon-inventory.php';
require_once 'amazon-netsuite.php';
require_once 'netsuite.php';

///////////////////////////////////////////////////////////////////////////////////////////////////

Config::init();

///////////////////////////////////////////////////////////////////////////////////////////////////

if ($_SERVER['argc'] > 1) { 
    $action = $_SERVER['argv'][1];
    switch($action) { 
    case 'export-inventory-items':
        for ($i = 0; $i < count(AmazonConfig::$MWS); $i++) { 
            $netsuite = new Netsuite(NetsuiteConfig::toArray($i));
            $netsuite->exportInventoryItems(null, array(
                'append' => $i > 0,
            ));
        }
        break;
    case 'update-inventory-items':
        for ($i = 0; $i < count(AmazonConfig::$MWS); $i++) { 
            try { 
                $client = new AmazonNetsuite(AmazonConfig::getMWSConfig($i), AmazonConfig::getInventoryConfig($i), NetsuiteConfig::toArray($i));
                $client->updateInventoryItems();
            } catch(Exception $ex) { 
                Log::ex($ex, 'update-inventory-items for ', $i);
            }
        }
        break;
    case 'update-amazon-items-map':
        $amazon_skus = Config::$USE_AMAZON_SKUS;
        if ($amazon_skus == false) { 
            print_r('no amazon skus in config, aborting ...' . PHP_EOL);
            exit;
        }
        $amazon_skus = array_wrap($amazon_skus);
        for ($i = 0; $i < count(AmazonConfig::$MWS); $i++) { 
            $client = new AmazonNetsuite(AmazonConfig::getMWSConfig($i), AmazonConfig::getInventoryConfig($i), NetsuiteConfig::toArray($i));
            $client->updateAmazonItemsMap($amazon_skus);
        }
        break;
    case 'list-inventory-supply':
        $amazon_skus = Config::$USE_AMAZON_SKUS;
        if ($amazon_skus == false) { 
            print_r('no amazon skus in config, aborting ...' . PHP_EOL);
            exit;
        }
        $amazon_skus = array_wrap($amazon_skus);
        for ($i = 0; $i < count(AmazonConfig::$MWS); $i++) { 
            $client = new AmazonNetsuite(AmazonConfig::getMWSConfig($i), AmazonConfig::getInventoryConfig($i), NetsuiteConfig::toArray($i));
            $inventory = $client->inventory->listInventorySupply(array(
                'SellerSkus' => $amazon_skus,
            ));
        }
        print_r($inventory);
        break;
    case 'update-config-inventory-items':
        $amazon_skus = Config::$USE_AMAZON_SKUS;
        if ($amazon_skus == false) { 
            print_r('no amazon skus in config, aborting ...' . PHP_EOL);
            exit;
        }
        $amazon_skus = array_wrap($amazon_skus);
        for ($i = 0; $i < count(AmazonConfig::$MWS); $i++) { 
            $client = new AmazonNetsuite(AmazonConfig::getMWSConfig($i), AmazonConfig::getInventoryConfig($i), NetsuiteConfig::toArray($i));
            $inventory = $client->inventory->listInventorySupply(array(
                'SellerSkus' => $amazon_skus,
            ));
            print_n($inventory);
            if ($inventory) { 
                if (is_assoc($inventory)) {
                    $inventory = array($inventory);
                }
                $amazon_item_map = $client->getAmazonItemsMap();
                foreach($amazon_skus as $j => $amazon_sku) {
                    $internal_id = $amazon_item_map[$amazon_sku][0];

                    $data = array(
                        'internal_id' => $internal_id,
                        'fba_qty' => $inventory[$j]['TotalSupplyQuantity'],
                    );
                    print_n($data);
                    $client->netsuite->updateInventoryItems(array($data));
                }
            }
            break;
        }
        break;
    default:
        throw new CustomException('Unknown command line action: ', $action);
        break;
    }
} else { 
    if (file_exists(__DIR__ . '/test.php')) { 
        require_once __DIR__ . '/test.php';
    } else { 
        throw new Exception('No arguments');
    }
}

///////////////////////////////////////////////////////////////////////////////////////////////////

