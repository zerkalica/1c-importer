<?php
function include1CParser($drvName, $root = '') {
  $components = array(
    'framework/compat',
    'framework/session',
    'framework/template',
  	'framework/path',
    'framework/t',
    'framework/log',
    'framework/db/cSelect',
    'framework/db/db',
    'framework/xmlreader',
    'framework/db/installer',
    'framework/formatters',
    'framework/strings',
    'framework/config',
  	'base/xmlitems',
    'base/converter',
    'base/genericconverter',
  	'base/owner',
  	'base/commerceinfo',
  	'base/commercecontroller',
    'base/commerceinfo',
  	'import/catalog',
    'import/classificator',
    'import/groups',
    'import/propertys',
    'orders/offers',
    'orders/priceoffer',
    'orders/pricetype',
    'converters/' . $drvName,
    'controllers/install',
    'transliter/transliteration'
  );
  if (!$root) {
    $root = dirname(__FILE__);
  }

  $root .=  '/lib/';
  foreach ( $components as $component ) {
    require_once $root . $component . '.php';
  }
  
  //Инициализируем фреймворк
  cCompat::init();
  cSession::init();
  cConfig::init('php', array('file' => cPath::getPrefix() . 'config.php'));
  $defaultConfig = cConfig::get()->get('default');
  cT::init($defaultConfig['charset'], $defaultConfig['lang']);
  cPath::init(__FILE__);
  cLog::init($defaultConfig['debug'], cPath::getPrefix() . $defaultConfig['logfile']);
  cdb::init(NULL, cConfig::get()->get('db'), 'db1'); 
  /*
  cConfig::init('db', array('db' => cdb::get(), 'table' => 'config'));
  cConfig::get('db')->setDefaults('default', cConfig::get('php')->get('default'));
  cConfig::saveAll();
  */
  cTemplate::init($defaultConfig['templates']);
  
  cTemplate::setTitle('1C xml importer');
  cTemplate::set('header', '1C xml importer v1.0');
  cTemplate::set('footer', '&copy; 2010');
}

$drvName = 'Drupal5';
include1CParser($drvName, dirname(__FILE__));

$br = cConfig::get()->get('path', 'bitrixRootReal') . '/';

$installer = new cConverterInstaller();
$installer->installAction();
$converterDriverFactory = new cConverterDriver(); 

#$importFile = $br . 'import.xml';
#$classificatorConverter = $converterDriverFactory->getClassificator($drvName);
#$productConverter = $converterDriverFactory->getProduct($drvName);

#$importParser = new cCommerceImportController($importFile, $classificatorConverter, $productConverter);
#$importParser->run();


$ordersFile = $br . 'offers2.xml';
$orderConverter = $converterDriverFactory->getOrders($drvName);
$orderParser = new cCommerceOrdersController($ordersFile, $orderConverter);
$orderParser->run();
