<?php

/**
 * sql string helpers and parsers
 * for building sql queryes
 * @author nexor
 */
class cSqlHelper {
  /**
   * make comma separated placeholders string
   * @param array $row
   * @return string ?, ?, ?
   */
  static public function makePlaceholdersString(array $row) {
    return ('?' . str_repeat(', ?', count($row) - 1));
  }

  /**
   * make comma separated where statement
   * @param array $row db row
   * @param bool $updateMode
   * @return array `field1` = ?, `field2` = ?, `field3` IN (?, ?, ?), `field4` IS NULL
   */
  static public function makePlaceholdersPair(array $row, $updateMode = FALSE) {
    $updates = array();
    foreach ( array_keys($row) as $field) {
      if ( is_array($row[$field])) {
        $eqStr = 'IN(' . self::makePlaceholdersString($row[$field]) . ')';
      } else {
        $eqStr = (!$updateMode && $row[$field] === NULL) ? 'IS NULL' : '= ?';
      }
      $updates[] = '`' . $field . '` ' . $eqStr;
    }
    return $updates;
  }

  /**
   * make where condition values array
   * for PDOStatement::execute
   * @param array $condition assoc array of conditions
   * array (id => 1, vid => 2, name => 'test'), etc
   * @param bool $updateMode
   * @return array index array of values
   */
  static public function makeConditionValues(array $condition, $updateMode = FALSE) {
    $values = array();
    foreach (array_values($condition) as $value ) {
      if ( is_array($value) ) {
        $values = array_merge($values, $value);
      } else if ( $updateMode || $value !== NULL ) {
        $values[] = $value;
      }
    }
    return $values;
  }
  
  /**
   * @param array $select data to select, index array, default NULL = *
   * @return string field1, field2, or *
   */
  static public function makeSelectString(array $select) {
    return $select ? ('`' . implode('`, `', $select) . '`') : '*';
  }
  
  /**
   * make where string
   * @param array $condition array of ('id' =>1, 'vid' =>2, ...)
   * @param string $separator AND, OR, NOT
   * @return string WHERE sql part 
   */
  static public function makeWhereString(array $condition, $separator = 'AND') {
    $conditions = self::makePlaceholdersPair($condition);
    return "\n". 'WHERE (' . implode(') ' . $separator . ' (', $conditions) .')';
  }
  
  /**
   * make join string
   * @param array $joins array('left' =>bool, 'table' =>string, 'cond'=>string);
   * @return string join sql part
   */
  static public function makeJoinString(array $joins) {
    $joinCmd = '';
    foreach ($joins as $item) {
      $joinCmd .= ($item['left'] ? 'LEFT' : 'INNER') . ' JOIN';
      $joinCmd .= ' `' . $item['table'] . '` ON ' . $item['sql'] . "\n";    
    }
    return $joinCmd;
  }
  
  
  /**
   * get sequence for lastInsertedId
   * @param string $table
   * @param string $pk
   * @return string sequence
   */
  static public function getSequence($table, $pk) {
    return $table . '_' . $pk . '_seq';
  }

  /**
   * Перевести unix timestamp в формат sql datetime
   * @param int $timestamp
   * @return string sql datetime
   */
  static public function timeStampToSqlDateTime($timestamp = NULL) {
    return date( 'Y-m-d H:i:s', $timestamp === NULL ? time() : $timestamp);
  }

  /**
   * Перевести sql datetime в unix timestamp
   * @param string $sqlDateTimeStr datetime
   * @return int $timestamp
   */
  static public function sqlDateTimeToTimeStamp($sqlDateTimeStr) {
    list($date, $time) = explode(' ', $sqlDateTimeStr);
    list($year, $month, $day) = explode('-', $date);
    list($hour, $minute, $second) = explode(':', $time);
    return mktime($hour, $minute, $second, $month, $day, $year);
  }
  
  static public function makeLimit($limit, $offset) {
     return $limit ? ("\nLIMIT " . (int) $limit . ' OFFSET ' . (int) $offset) : '';
  }
  static public function makeOrders(array $orders) {
    return $orders ? ("\nORDER BY " . implode(', ', $orders)) : '';
  }
  static public function makeHaving(array $havings) {
    return $havings ? ("\nHAVING (" . implode(', ', $havings) .')') : '';
  }
  static public function makeGroup(array $groups) {
    return $groups ? ("\nGROUP BY " . implode(', ', $groups)) : '';
  }
  
}

/**
 * Базовый класс драйвера, наследован от PDO
 * @author <nexor@ya.ru>
 */
abstract class cDbDriverBase extends PDO {
  /**
   * Кодировка по-умолчанию
   * @var string
   */
  private $charset = 'utf8';

  public function getDriverName() {
    return $this->getAttribute(PDO::ATTR_DRIVER_NAME);
  }

  /**
   * Установка кодировки по-умолчанию, необходимо всегда вызывать после connect
   *
   * @param string $charset название кодировки, например UTF8
   */
  public function setdefaultCharset( $charset ) {
    $this->charset = $charset;
  }

  public function getdefaultCharset() {
    return $this->charset;
  }
  
  public $lastSth;
  
  /**
   * exec sql query
   * @param string $queryStr
   * @param array $values placeholders values
   * @return bool result
   */
  public function queryWithPlaceholders($queryStr, array $values) {
    $this->lastSth = $sth = $this->prepare($queryStr);
    $result = FALSE;
    try {
      $result = $sth->execute( $values );
    } catch (Exception $e) {
      throw new PDOException(cT::t('query: %q, msg:%msg', array('%q' => $queryStr, '%msg' => $e->getMessage())));
    } 
    return $result;
  }
  
  /**
   * @param mixed $table
   * @return sth
   */
  public function truncate($tables) {
    if (!is_array($tables) ) {
      $tables = array($tables);
    }
    foreach ( $tables as $table ) {
      $queryStr = 'TRUNCATE TABLE `' . $table . '`';
      $this->query($queryStr);
    }
  }

  /**
   * Delete rows by condition
   * @param string $table
   * @param array $condition
   * @return bool result
   */
  public function delete($table, array $condition) {
    $queryStr = 'DELETE FROM `' . $table . '`' . cSqlHelper::makeWhereString($condition);
    return $this->queryWithPlaceholders($queryStr, cSqlHelper::makeConditionValues($condition, TRUE));
  }
  
  /**
   * simple select, no pagelimit, no order
   * @param string $table
   * @param array $condition assoc array of AND joined conditions
   * @param array $select data to select, index array, default NULL = *
   * @param array order index array of order parts 
   * @param int limit
   * @param int offset
   * @return PDOStatement
   */
  public function find($table, array $condition = NULL, $select = NULL, $order = NULL, $limit = 0, $offset = 0) {
    if ( $select && !is_array($select) ) {
      $select = array($select);
    }
    $selStr = cSqlHelper::makeSelectString($select);
    $condStr = $condition ? cSqlHelper::makeWhereString($condition) : '';
    $queryStr = 'SELECT ' . $selStr . ' FROM `' . $table . '`' . $condStr;
    if ($order) {
      $queryStr .= cSqlHelper::makeOrders($order);
    }
    if ($limit) {
      $queryStr .= cSqlHelper::makeLimit($limit, $offset);
    }
    
    $sth = $this->prepare($queryStr);
    $sth->execute( cSqlHelper::makeConditionValues($condition) );
    return $sth;
  }
  
  /**
   * return record count in table
   * @param string $table
   * @param array $condition AND condition
   * @param string $expression NULL == '*'
   * @return int count
   */
  function count($table, $condition = NULL, $expression = NULL) {
    if (! $expression) {
      $expression = '*';
    }
    $condStr = $condition ? cSqlHelper::makeWhereString($condition) : '';
    $queryStr='SELECT COUNT(' . $expression . ') AS `count` FROM `' . $table . '`' . $condStr;
    $sth = $this->prepare($queryStr);
    $sth->execute( cSqlHelper::makeConditionValues($condition) );
    return (int) $sth->fetchColumn();
  }

  /**
   * read table row
   * @param string $table
   * @param array $condition assoc array of AND joined conditions
   * @param mixed $select data to select, index array, or string for single value default NULL = *
   * @return mixed row data in assoc array, or string, if select is string
   */
  public function readRow($table, array $condition, $select = NULL) {
    $sth = $this->find($table, $condition, $select);
    return is_string($select) ? $sth->fetchColumn() : $sth->fetch(PDO::FETCH_ASSOC);
  }
  
  /**
   * read all selected rows into array
   * @param string $table
   * @param array $condition
   * @param array $select
   * @return array fetchAll(PDO::FETCH_ASSOC) result 
   */
  public function readAllRows($table, array $condition, $select = NULL) {
    $sth = $this->find($table, $condition, $select);
    return $sth->fetchAll(PDO::FETCH_ASSOC);
  }
  
  /**
   * Выполняет INSERT
   * @param string $table название таблицы
   * @param array $data массив название поля таблицы => значение для использования в INSERT, UPDATE
   * @param string $pk - primary key
   * @return int lastInsertId
   */
  public function insert($table, array $data, $pk = 'id') {
    $queryStr = 'INSERT INTO `' . $table . '`
    (`' . implode('`, `', array_keys($data) ) .'`)
    VALUES ('. cSqlHelper::makePlaceholdersString($data) .')';
    $bool = $this->queryWithPlaceholders($queryStr, array_values($data));
    return $this->lastInsertId( cSqlHelper::getSequence($table, $pk) );
  }

  /**
   * Выполняет UPDATE
   * @param string $table название таблицы
   * @param array $data массив название поля таблицы => значение для использования в INSERT, UPDATE
   * @return bool result
   */
  public function update($table, array $data, array $update_condition) {
    if (! is_array($update_condition)) {
      $update_condition = array('id'=> $update_condition);
    }
    $updates = cSqlHelper::makePlaceholdersPair($data, TRUE);

    $queryStr = 'UPDATE `'. $table .'`
    SET ' . implode(', ', $updates) . cSqlHelper::makeWhereString($update_condition);
    $values = array_merge( array_values($data), cSqlHelper::makeConditionValues($update_condition, TRUE));
    return $this->queryWithPlaceholders($queryStr, $values);
  }
}

/**
 *
 * Драйвер для Mysql
 *
 * @author nexor <nexor@ya.ru>
 * @version $Id$
 *
 */
class cDbDriver_mysql extends cDbDriverBase {

  /**
   * (non-PHPdoc)
   * @see cDbDriverBase::setdefaultCharset()
   */
  public function setdefaultCharset($charset = 'utf8') {
    $this->query('SET NAMES "' . $charset . '"');
    parent::setdefaultCharset($charset);
  }
}

/**
 * Фабрика, возвращает объект драйвера ДБ
 * @author nexor
 */
class cdb {
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
    return 'cDbDriver_' . $drvName;
  }

  /**
   * @param string $drvName, NULL for detect driver name from params['dsn']
   * @param array $params dsn, user, password, options, charset
   * @throws ErrorException
   * @return cDbDriverBase
   */
  static public function getInstance($drvName, array $params) {
    if ( empty($params['charset']) ) {
      throw new cFactoryException(cT::t('Необходимо указать кодировку, с которой будет работать база данных: %drv', array('%drv' => $drvName)));
    }
    if (empty($params['dsn'])) {
      throw new cFactoryException(cT::t('Необходимо указать PDO DSN для работы с базой данных: %drv', array('%drv' => $drvName)));
    }

    $pos = strpos($params['dsn'], ':');
    $drvName = $drvName ? $drvName : substr($params['dsn'], 0, $pos);
    $driverClassName = self::getClassByDrvName($drvName);

    try {
      $class = new $driverClassName($params['dsn'], $params['user'], $params['password'], $params['options']);
    } catch( Exception $e) {
      throw new cFactoryException(cT::t('Создайте базу данных и настройте скрипт в config.php, %msg', array('$driverClassName' => $drvName, '%msg' => $e->getMessage() ) ));
    }
    $class->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $class->setdefaultCharset($params['charset']);
    cLog::addSkipClass('cDbDriverBase');
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
   * @return cDbDriverBase
   */
  static public function get($key = NULL) {
    return self::$storages[$key ? $key : self::$defaultKey];
  }


}
