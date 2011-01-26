<?php

abstract class cConverter {
  /**
   * @var cCommerceInfoBase
   */
  protected $commerceInfo;
  private $readedIds = array();
  
  public function setCommerceInfo(cCommerceInfoBase $commerceInfo) {
    $this->commerceInfo = $commerceInfo;
  }
  
  protected function markUpdated($internalId) {
    cdb::get()->insert($this->getMarksTable(), array('id'=>$internalId));
  }

  protected function resetUpdateMarks() {
    cdb::get()->truncate($this->getMarksTable());
  }
  
  protected function checkImported() {
  }
  
  /**
   * delete unused groups in classificator vocabulary
   * @return int affected rows count
   */
  protected function deleteUnused() {
    return 0;
  }

  protected function getMarksTable() {
  	return NULL;
  }

  protected function getMapTable() {
  	return NULL;
  }
  
  public function begin() {
    cLog::Notice(cT::t('Старт импортирования %class: %date', array('%class' => get_class($this), '%date' => date('r'))));
    
    $sth = cdb::get()->query('SELECT COUNT(*) AS `count` FROM `' . $this->getMarksTable() . '`');
    $count = $sth->fetchColumn();
    if ( $count ) {
      $count = $this->deleteUnused();
      cLog::Warning(cT::t(
      	'Во время предыдущего импортирования произошла ошибка - удаляем товары, которые не были импортированы: %count',
        array('%count' => $count)
      ));
    }
    $this->resetUpdateMarks();
  }
  
  public function end() {
    $this->checkImported();
    $deleted = $this->deleteUnused();
    $this->resetUpdateMarks();
    if ($deleted) {
      cLog::Notice(cT::t('Удалено старых элементов в %class: %count', array('%class' => get_class($this), '%count' => $deleted)));
    }
    cLog::Notice(cT::t('Конец импортирования %class: %date', array('%class' => get_class($this), '%date' => date('r'))));
  }
  
  
  protected function mapId($id) {
    if (empty($this->readedIds[$id])) {
      $this->readedIds[$id] = cdb::get()->readRow($this->getMapTable(), array('cid' => $id), 'id');
    }
    return $this->readedIds[$id];
  }
  
  protected function addMapId($id, $internalId) {
    $this->readedIds[$id] = $internalId;
    cdb::get()->insert($this->getMapTable(), array('id' => $internalId, 'cid' => $id));
  }
  
  protected function delMapId($internalId) {
    unset($this->readedIds[$internalId]);
    cdb::get()->delete($this->getMapTable(), array('id' => $internalId));
  }
  
}

abstract class cProductConverter extends cConverter {
  abstract public function onProduct(cProduct $product);
}

abstract class cOrdersConverter extends cConverter {
  abstract public function onOffer(cPriceOffer $offer);
}
abstract class cClassificatorConverter extends cConverter {
  abstract public function onClassificator(cClassificator $classificator);
}

/**
 * factory to load converter instance
 * @author nexor
 */
class cConverterDriver {
  
  public function __construct() {
  }
  public function getOrders($driver) {
    return $this->getInstance($driver, 'OrdersConverter');
  }
  public function getProduct($driver) {
    return $this->getInstance($driver, 'ProductConverter');
  }
  public function getClassificator($driver) {
    return $this->getInstance($driver, 'ClassificatorConverter');
  }
  /**
   * load and return instance of driver
   * @param string $driver
   * @param string $classNameSuffix
   * @throws ErrorException
   * @return object
   */
  private function getInstance($driver, $classNameSuffix) {
    $className = 'c' . $driver .  $classNameSuffix; 
    if ( !class_exists($className) ) {
      throw new ErrorException('Не найден класс драйвера: ' . $className);
    }
    $class = new $className();
    return $class;
  }
}
