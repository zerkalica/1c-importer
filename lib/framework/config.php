<?php

/**
 * abstract config storage class
 * @author nexor
 *
 */
abstract class cConfigStorage {
  /**
   * @var array (section => array(key => value, ... ))
   */
  protected $cache;
  
  /**
   * @var array
   */
  private $defaults;
  /**
   * @var array assoc array of updated section names => array of keys or empty for all section
   */
  protected $updatedSections = array();
  
  /**
   * load data from storage
   */
  abstract public function load();
  /**
   * save all storage values
   */
  abstract public function save();
  
  /**
   * get section values
   * @param string $section
   * @return array assoc
   */
  private function getSection($section) {
    return isset($this->cache[$section]) ? $this->cache[$section] : NULL; 
    
  }
  
  /**
   * get config value from section by key
   * @param string $section
   * @param string $key
   * @return string config value
   */
  private function getValue($section, $key) {
    return isset($this->cache[$section][$key]) ? $this->cache[$section][$key] : NULL; 
  }
  
  /**
   * set section values
   * @param string $section
   * @param array $values assoc array of key => string value
   */
  private function setSection($section, array $values) {
    $this->cache[$section] = $values;
    $this->updatedSections[$section] = array();
  }
  
  /**
   * set value in section by key
   * @param string $section
   * @param string $key
   * @param string $value
   */
  private function setValue($section, $key, $value) {
    $this->cache[$section][$key] = $value;
    $this->updatedSections[$section][$key] = TRUE;
  }
  
  /**
   * sets default values for section or for whole entry of config (if section is null)
   * @param string $section
   * @param array $defaults default values
   */
  public function setDefaults($section = NULL, array $defaults) {
    if ( !$section ) {
      $this->defaults = $defaults;
      foreach ( array_keys($defaults) as $section ) {
        $this->updatedSections[$section] = array();
      }
    } else {
      $this->defaults[$section] = $defaults;
      $this->updatedSections[$section] = array();
    }
  }
  
  /**
   * get config value or section
   * @param string $section
   * @param string $key if NULL - return all section
   * @return mixed value or section assoc array, if no $key
   */
  public function get($section = NULL, $key = NULL) {
    $result = NULL;
    if (!$key) {
      $result = $this->getSection($section);
      if ( !$result && isset($this->defaults[$section]) ) {
        $result = $this->defaults[$section];
        $this->set($section, $key, NULL, $result); 
      }
    } else {
      $result = $this->getValue($section, $key);
      if ( !$result && isset($this->defaults[$section][$key]) ) {
        $result = $this->defaults[$section][$key];
        $this->set($section, $key, $result); 
      }
    }
    return $result;  
  }
  
  /**
   * set config value or section
   * @param string $section
   * @param string $key
   * @param mixed $value, if $key is NULL - assoc array of section values
   */
  public function set($section, $key = NULL, $value) {
    if ( !$key ) {
      $this->setSection($section, $value);
    } else {
      $this->setValue($section, $key, $value);
    }
  }
}

/**
 * stores config values in php file
 * @author nexor
 */
class cConfigStorage_php extends cConfigStorage {
  /**
   * filename of config file with php $config array 
   * @var string 
   */
  private $filename;
  
  /**
   * init php storage
   * @param array file => filename of config file with php $config array
   */
  public function __construct(array $params) {
    $this->filename = $params['file'];
  }
  
  public function save() {
    if ( $this->updatedSections ) {
      $configData = '<?php $config = ' . var_export($this->cache, TRUE) . ";\n";
      file_put_contents($this->filename, $configData);
    }  
  }
  
  public function load() {
    if ( file_exists($this->filename) ) {
      include ($this->filename);
      $this->cache = $config;
    }
  }
}

/**
 * stores config values in db table
 * @author nexor
 */
class cConfigStorage_db extends cConfigStorage {
  /**
   * @var DbDriverBase 
   */
  private $db;
  /**
   * @var string
   */
  private $configTable;
  
  /**
   * @var array of instances
   */
  static protected $storages;
  /**
   * @var string
   */
  static public $defaultKey;
  
  /**
   * init php storage
   * @param array file => filename of config file with php $config array
   */
  public function __construct(array $params) {
    $this->db = $params['db'];
    $this->configTable = $params['table'];
  }
  
  public function save() {
    foreach ($this->updatedSections as $section => $updatedKeys ) {
      $sectionValues = empty($updatedKeys) ? array_keys($this->cache[$section]) : array_keys($updatedKeys);
      
      foreach ($sectionValues as $key) {
        $value = &$this->cache[$key];
        assert( !is_array($value) );
        $dbCond = array(
          'section' => $section,
          'key' => $key,
        );
        $dataCell = array('data' => $value);
        //@TODO: replace to insert or update on duplicate key
        $this->db->delete($this->configTable, $dbCond);
        $this->db->insert($this->configTable, array_merge($dbCond, $dataCell));
      } 
    }
  }
  
  public function load() {
    $sth = $this->db->find($this->configTable);
    $this->cache = $sth->fetchAll(PDO::FETCH_ASSOC);
  }
}

/**
 * config storage factory
 * @author nexor
 *
 */
class cConfig {

  /**
   * @var array of instances
   */
  static protected $storages;
  /**
   * @var string
   */
  static public $defaultKey;

  /**
   * (non-PHPdoc)
   * @see cFactoryBase::getClassByDrvName()
   */  
  static protected function getClassByDrvName( $drvName ) {
    return 'cConfigStorage_' . $drvName;
  }
  
  /**
   * get instance of config storage driver
   * @param string $drvName
   * @param array $params
   * @return cConfigStorage 
   */
  static public function getInstance($drvName, array $params) {
    $className = self::getClassByDrvName($drvName);
    $class = new $className($params);
    cLog::addSkipClass(get_class($class));
    $class->load();
    return $class;
  }
  
  static public function saveAll() {
    foreach( $this->storages as $key => $instance ) {
      $instance->save();
    }
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

