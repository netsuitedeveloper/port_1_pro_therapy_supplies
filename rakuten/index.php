<?php

require_once 'PhpLib.php';

class PDEBUG
{
    const USE_LOCAL_FTP = false;
    const USE_LOCAL_FEEDS = false;

    const CREATE_FAKE_ORDERS = false;
    const USE_THIS_RAKUTEN_ORDER = false; //166122896; 

    const DO_NOT_ACKNOWLEDGE_ORDER_FILE = false;
}

require_once 'config.php';

require_once 'ErrorReport.php';
require_once 'netsuite/NetSuiteService.php';
require_once 'NetSuiteService.php';
require_once 'NetSuite.php';
require_once 'rakuten.php';

class Config
{
    public static $SHIP_FROM_ADDRESS = 'Pro Therapy Supplies, 1750 Breckinridge Pkwy, Suite 200';
    public static $SHIP_FROM_CITY = 'Duluth';
    public static $SHIP_FROM_STATE = 'GA';
    public static $SHIP_FROM_ZIPCODE = '30096';
    public static $SHIP_FROM_PHONE_NUMBER = '770-441-9808';

    public static function getRakutenFTP() {
        if (PDEBUG::USE_LOCAL_FTP) {
            $config['host'] = 'localhost';
            $config['username'] = 'user';
            $config['password'] = 'pass';
            return $config;
        }
        return RakutenConfig::$FTP;
    }

    public static function getRakutenShippingFeed() {
        if (PDEBUG::USE_LOCAL_FEEDS) {
            return 'http://localhost/BuycomShippingFeed.txt';
        }
        return RakutenConfig::SHIPMENT_FEED;
    }

    public static function getRakutenInventoryFeeds() {
        if (PDEBUG::USE_LOCAL_FEEDS) {
            return array('http://localhost/BuycomInventoryFeed.txt');
        }
        return RakutenConfig::$INVENTORY_FEEDS;
    }

}

APP::init(array(
    'log' => array(
        'level' => Log::LEVEL_ERROR,
        'append' => true,
        'limit' => 50,
    ),
    'curl' => array(
        'log' => true,
    ),
    'mail' => array(
        'log' => true,
        'do_not_send' => false,
    ),
));

date_default_timezone_set('America/Los_Angeles');

Log::welcome();

//$_SERVER['argv'] = array('', 'sync-shipments');
//$_SERVER['argc'] = 2;

$argv = $_SERVER['argv'];

if ($_SERVER['argc'] > 1) {
    if ($argv[1] == 'sync-shipments') {
        Rakuten::syncShipments();
    } else if ($argv[1] == 'sync-inventory') {
        Rakuten::syncInventory();
    } else if ($argv[1] == 'sync-orders') {
        Rakuten::syncOrders();
    } else if ($argv[1] == 'sync-results') {
        Rakuten::syncResults();
    }
} else {
    Rakuten::ping();
}

