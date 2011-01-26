<?php

/**
 * factory base
 * @author nexor
 *
 */
abstract class cFactoryBase {
  /**
   * @var array of instances
   */
  static protected $storages;
  /**
   * @var string
   */
  static public $defaultKey;
  
  /**
   * return classname by driver name
   * @param string $drvName
   * @return string $className
   */
  //abstract static protected function getClassByDrvName( $drvName );
  
  /**
   * get instance of driver
   * @param string $drvName
   * @param array $params
   * @return object 
   */
  static public function getInstance($drvName, array $params) {
    $className = self::getClassByDrvName($drvName);
    $class = new $className($params);
    cLog::addSkipClass(get_class($class));
    return $class;
  }
  /**
   * static constructor
   * @param string drv Name, NULL - detect from parameters
   * @param array drv params
   * @parm string unique key for accessing instance  
   */
  static public function init($drvName, array $params, $key = NULL) {
    if ( !$key ) { 
      $key = $drvName;
    }
    self::$defaultKey = $key;
    self::$storages[$key] = self::getInstance($drvName, $params);
  }
  
  /**
   * get instance of inited driver
   * @param string $key driver instance key
   * @return object
   */
  static public function get($key = NULL) {
    return self::$storages[$key ? $key : self::$defaultKey];
  }
}

class cFactoryException extends ErrorException {}
