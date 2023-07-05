<?php

require_once 'PhpLib.php';

require_once 'ErrorReport.php';
require_once 'NewEgg.php';
require_once 'netsuite/NetSuiteService.php';
require_once 'NetSuiteService.php';
require_once 'NetSuite.php';
require_once 'NewEggNetSuite.php';

class PDEBUG
{
    const USE_LOCAL_FTP = false;

    const DO_NOT_DELETE_ORDERLIST_FILE = false;
    const CREATE_FAKE_ORDERS = false;
    const USE_THIS_NEWEGG_ORDER = false; //166122896; 
    
    const DO_NOT_MARK_AS_SHIPPED_ON_NETSUITE = false;
    const DO_NOT_DELETE_RESULT_FILE = false;

    const UPLOAD_THIS_NEWEGG_CSV = false; 
    const DO_NOT_DELETE_INVENTORY_FILE = false;
    const DO_NOT_DELETE_PRODUCT_RESULT_FILE = false;
    //const DO_NOT_DELETE_INVENTORY_RESULT_FILE = false;

    const COPY_INVENTORY_XML_FILE_TO_TEMP_DIR = true;

    const USE_NUMBER_OF_CSV_FILES = false;
    const USE_NUMBER_OF_LINES_IN_CSV_FILE = false; //1;
}

class NewEggConfig
{
    const SELLER_ID = 'A214';

    const API_URL = 'https://api.newegg.com/marketplace/';
    const API_KEY = 'aaa';
    const API_SECRET_KEY = 'bbb';

    const FTP_HOST = 'ftp03.newegg.com';
    const FTP_USERNAME = 'a';
    const FTP_PASSWORD = 'b';
    
    public static function toArray() {
        $config = APP::getClassConstants('NewEggConfig');

        if (PDEBUG::USE_LOCAL_FTP) {
            $config['FTP_HOST'] = 'localhost';
            $config['FTP_USERNAME'] = 'user';
            $config['FTP_PASSWORD'] = 'pass';
        }

        return $config;
    }
}


class NetSuiteConfig
{
    const URL = 'https://webservices.netsuite.com';
    const ACCOUNT = '671309';
    const USERNAME = 'pts.webservice@gmail.com';
    const PASSWORD = 'Webservice00';
    const ROLE_ID = 1018;

    public static function toArray() {
        return APP::getClassConstants("NetSuiteConfig");
    }
}

class Config
{
    public static $DIR_NEWEGG_TEMP; 
    public static $DIR_NEWEGG_ORDERS;
    public static $DIR_NEWEGG_RESULTS;
    public static $DIR_NEWEGG_TEMPLATES;
    public static $DIR_NEWEGG_PRODUCT_UPLOADS;
    public static $DIR_NEWEGG_INVENTORY;
    public static $DIR_NEWEGG_PRODUCT_RESULTS;
    public static $DIR_NEWEGG_INVENTORY_RESULTS;

    public static $SHIP_FROM_ADDRESS = 'Pro Therapy Supplies, 1750 Breckinridge Pkwy, Suite 200';
    public static $SHIP_FROM_CITY = 'Duluth';
    public static $SHIP_FROM_STATE = 'GA';
    public static $SHIP_FROM_ZIPCODE = '30096';
    public static $SHIP_FROM_PHONE_NUMBER = '770-441-9808';

    public static $URI_EMAIL_TEMPLATE;
    public static $EMAIL_TOS;
    public static $EMAIL_FROM;

    public static $PRODUCT_URLS = array(
        'https://system.netsuite.com/core/media/media.nl?id=999378&c=671309&h=f017c29ef6755d7b3268&_xt=.xls',
        'https://system.netsuite.com/core/media/media.nl?id=999379&c=671309&h=185a87ecdbc9ac4da692&_xt=.xls',
        'https://system.netsuite.com/core/media/media.nl?id=999380&c=671309&h=a923d8bb3e40f628bb29&_xt=.xls',
        'https://system.netsuite.com/core/media/media.nl?id=999381&c=671309&h=1516653ed6300980f259&_xt=.xls',
        'https://system.netsuite.com/core/media/media.nl?id=999382&c=671309&h=ae4d41207044959dd3ac&_xt=.xls',
        'https://system.netsuite.com/core/media/media.nl?id=999483&c=671309&h=bf68f5088116d14f8596&_xt=.xls',
        'https://system.netsuite.com/core/media/media.nl?id=999485&c=671309&h=14e3b583ee7623567b88&_xt=.xls',
        'https://system.netsuite.com/core/media/media.nl?id=999486&c=671309&h=f131f356c091e4eae8ff&_xt=.xls',
        'https://system.netsuite.com/core/media/media.nl?id=999487&c=671309&h=5e6143202a074e0a5f40&_xt=.xls',
        'https://system.netsuite.com/core/media/media.nl?id=999588&c=671309&h=bc90e25314cef0f5310f&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425835&c=671309&h=c3c1a43bbdc901445b5e&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425836&c=671309&h=9392009927e35cc282a7&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425842&c=671309&h=707c900018af21cdcddb&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425843&c=671309&h=5ba89e24c008813b9977&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425844&c=671309&h=e4f3fecf5f06dca7afa9&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425837&c=671309&h=93347668c77d39708cbd&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425839&c=671309&h=4fd5958de3747ba4c130&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425845&c=671309&h=19f8acc64ebaff393fb4&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425847&c=671309&h=5c4580718d9617b98cae&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425849&c=671309&h=524bcc6459ad21f78719&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425840&c=671309&h=621e3052bdd909645054&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425841&c=671309&h=a72bd29fce0c3f14cd05&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425850&c=671309&h=1032d41cc8f955624a44&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425851&c=671309&h=7b59ba2fb1313634db8d&_xt=.xls',
		'https://system.netsuite.com/core/media/media.nl?id=1425852&c=671309&h=865ed4f4f9caca228619&_xt=.xls',
    );

    public static function toArray() {
        self::$DIR_NEWEGG_TEMP = PATH::combine(dirname(__FILE__), 'temp_newegg');
        self::$DIR_NEWEGG_ORDERS = PATH::combine(self::$DIR_NEWEGG_TEMP, 'orders');
        self::$DIR_NEWEGG_RESULTS = PATH::combine(self::$DIR_NEWEGG_TEMP, 'results');
        self::$URI_EMAIL_TEMPLATE = PATH::combine(__DIR__, 'email-template.htm');
        self::$EMAIL_TOS = array(
 			'orders' => array(
		'pts.vharbison@gmail.com',
	    ),
            'products' => array(
		'pts.vharbison@gmail.com',
	     ),
            'inventory' => array(
		'pts.vharbison@gmail.com',
            ),
            'shipments' => array(
		'pts.vharbison@gmail.com',
            ),
        );
        self::$EMAIL_FROM = 'haircapi@elastogels.com';
        self::$DIR_NEWEGG_TEMPLATES = PATH::combine(__DIR__, 'newegg_templates');
        self::$DIR_NEWEGG_PRODUCT_UPLOADS = PATH::combine(self::$DIR_NEWEGG_TEMP, 'products');
        self::$DIR_NEWEGG_INVENTORY = PATH::combine(self::$DIR_NEWEGG_TEMP, 'inventory');
        self::$DIR_NEWEGG_PRODUCT_RESULTS = PATH::combine(self::$DIR_NEWEGG_TEMP, 'product-results');
        self::$DIR_NEWEGG_INVENTORY_RESULTS = PATH::combine(self::$DIR_NEWEGG_TEMP, 'inventory-results');

        return APP::getClassPropertyValueMap('Config');
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
    if ($argv[1] == 'sync-orders') {
        NewEggNetSuite::syncOrders(Config::toArray(), NewEggConfig::toArray(), NetsuiteConfig::toArray());
    } else if ($argv[1] == 'sync-shipments') {
        NewEggNetSuite::syncShipments(Config::toArray(), NewEggConfig::toArray(), NetsuiteConfig::toArray());
    } else if ($argv[1] == 'sync-product-results') {
        NewEggNetSuite::syncProductResults(Config::toArray(), NewEggConfig::toArray(), NetsuiteConfig::toArray());
    } else if ($argv[1] == 'sync-products') {
        NewEggNetSuite::syncProducts(Config::toArray(), NewEggConfig::toArray(), NetsuiteConfig::toArray());
    } else if ($argv[1] == 'sync-inventory') {
        NewEggNetSuite::syncInventory(Config::toArray(), NewEggConfig::toArray(), NetsuiteConfig::toArray());
    } else if ($argv[1] == 'sync-inventory-results') {
        NewEggNetSuite::syncInventoryResults(Config::toArray(), NewEggConfig::toArray(), NetsuiteConfig::toArray());
    } else if ($argv[1] == 'custom') { 
        NewEggNetSuite::custom(Config::toArray(), NewEggConfig::toArray(), NetsuiteConfig::toArray());
    } else {
        throw new CustomException('Unknown argument: ', $argv[1]);
    }
} else {
    NewEggNetSuite::ping(Config::toArray(), NewEggConfig::toArray(), NetsuiteConfig::toArray());
}

