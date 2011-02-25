<?php
/**
 * Classificator groups converter
 * @author nexor
 */
abstract class cGenericClassificatorConverter extends cClassificatorConverter {
  /**
   * @var int vocabulary id
   */
  public $classificatorInternalId;
  static public $marksTable = 'cgroupmarks';
  static public $mapTable = 'cgroupmap';
  private $updateCount = 0;
  private $newCount = 0;
  private $totalCount = 0;

  protected function getMarksTable() {
  	return self::$marksTable;
  }

  protected function getMapTable() {
  	return self::$mapTable;
  }
  
  public function onClassificator(cClassificator $classificator) {
    $this->classificatorInternalId = $this->mapId($classificator->id);
    if (! $this->classificatorInternalId ) {
      $this->classificatorInternalId = $this->getVocIdByName($classificator->name);
      if (!$this->classificatorInternalId) {
        cLog::Error(cT::t('Невозможно найти словарь с именем "%name" для групп', array('%name' => $classificator->name)));
        return;
      }
      $this->addMapId($classificator->id, $this->classificatorInternalId);
    }

    $vars = array();
    $vars['%voc'] = $classificator->name;
    if ( $this->classificatorInternalId === NULL ) {
      cLog::Error(cT::t('В базе не найден словарь для групп товаров: %voc', $vars));
      return;
    }
    $classificatorGroups = &$classificator->groups;
    cLog::Notice(cT::t('Импортируем группы'));
    foreach ($classificatorGroups as $id => $item) {
      $internalId = $this->setCategory($item, $id, $classificatorGroups);
      $classificatorGroups[$id]['internalId'] = $internalId;  
    }
    //$this->end();
  }
  /**
   * Sets category for item
   * @param array $product groups
   * @param int $id
   * @param array $classificatorGroups
   */
  public function setCategory($item, $id, $classificatorGroups) {
    $parentId = $item['parent'];
    $name = $item['name'];

    $internalId = $this->mapId($id);
    
    $parentInternalId = $this->mapId($parentId);

    if ($parentId && !$parentInternalId) {
      throw new PDOException(cT::t('Невозможно найти родительскую группу для товара: %product ', array(
        '%product' => $name
      ) ));
    }
    $ok = FALSE;
    $this->totalCount++;
    if (!$internalId) {
      $internalId = $this->addGroup($name, $parentInternalId);
      if ($internalId) {
        cLog::Debug(cT::t('Добавлена группа %voc / %id / %nid', array('%voc' => $item['name'] , '%id' => $id, '%nid' => $internalId) ));
        $this->addMapId($id, $internalId);
        $this->newCount++;
        $ok = TRUE;
      } else {
        throw new PDOException(cT::t('Невозможно добавить группу %name', array('%name' => $name)));
      }
    } else {
      $ok = $this->updateGroup($internalId, $name, $parentInternalId);
      if ( !$ok ) {
        throw new PDOException(cT::t('Невозможно обновить группу %name', array('%name' => $name)));
      }
      cLog::Debug(cT::t('Обновлена группа %voc / %id / %nid', array('%voc' => $item['name'] , '%id' => $id, '%nid' => $internalId) ));
      $this->updateCount++;
    }
    
    if ( $ok ) {
      $this->markUpdated($internalId);
    } else {
      throw new ErrorException('Что-то не так');
    }
    return $internalId;
  }
  
  public function end() {
    cLog::Notice(cT::t('Добавлено новых групп: %count', array('%count' => $this->newCount)));
    cLog::Notice(cT::t('Обновлено групп: %count', array('%count' => $this->updateCount)));
    cLog::Notice(cT::t('Всего групп: %count', array('%count' => $this->totalCount)));
    parent::end();
  }
  
  /**
   * save group to db
   * @param string $name title of group
   * @param int $parentInternalId parent tid
   * @return int internalId of saved group
   */
  abstract public function addGroup($name, $parentInternalId);
  
  /**
   * Update existing group
   * @param int $internalId tid to update
   * @param string $name new name
   * @param int $parentInternalId parent tid ro NULL, if no parent
   * @return bool TRUE, if success
   */
  abstract public function updateGroup($internalId, $name, $parentInternalId);
  
  abstract protected function getVocIdByName($name); 
  
}

/**
 * Product converter
 * @author nexor
 */
abstract class cGenericProductConverter extends cProductConverter {
  static protected $marksTable = 'cproductmarks';
  static public $mapTable = 'cproductmap';
  private $updateCount = 0;
  private $newCount = 0;
  private $totalCount = 0;
  private $imageCount = 0;

  protected function getMarksTable() {
  	return self::$marksTable;
  }

  protected function getMapTable() {
  	return self::$mapTable;
  }

  
  /**
   * @param array $productPropertys
   * @param array $classificatorPropertys
   * @return array values (propertyName => Value)
   */
  public function propertysToValues($productPropertys, $classificatorPropertys) {
    $values = array();
    if ( $productPropertys ) {
      foreach($productPropertys as $id => $idValue) {
        $classificatorProperty = $classificatorPropertys[$id];
        $key = $classificatorProperty['name'];
        $value = $classificatorProperty['types']['Справочник']['variants'][$idValue];
        $values[$key] = $value;
      }
    }
    return $values;
  }
  
  /**
   * @param cProduct $product
   * @return cProduct
   */
  public function productAddGroupIdsAndProperyValues(cProduct $product, array $vars) {
    $classificatorPropertys = &$this->commerceInfo->classificator->propertys;
    $propertysValues = $this->propertysToValues($product->propertys, $classificatorPropertys);
    $product->propertysValues = $propertysValues;
    return $product;
  }

  public function onProduct(cProduct $product) {
    $vars= array(
      '%fullname' => $product->fullName,
    	'%name' => $product->name,
      '%id' => $product->id,
    );
    $internalId  = $this->mapId($product->id);
    if (!$internalId) {
      $product = $this->productAddGroupIdsAndProperyValues($product, $vars);
      if ( !$product ) {
        throw new ErrorException(cT::t('Продукт не загрузился'));
      }
      $internalId  = $this->addProduct($product);
      $ok = (bool)$internalId;
      if ( $ok ) {
        $this->addMapId($product->id, $internalId);
      }
      $updated = FALSE;
    } else {
      cLog::Debug(cT::t('Обновляется товар %fullname / %id', $vars ));
      $ok = $this->updateProduct($internalId, $this->productAddGroupIdsAndProperyValues($product, $vars));
      $updated = TRUE;
    }
    if ($ok) {
      $this->totalCount++;
      if (!empty($product->images)) {
        $this->imageCount++;
      }
      $vars['%nid'] = $internalId;
      $this->markUpdated($internalId);
      if ($updated) {
        $this->updateCount++;
      } else {
        $this->newCount++;
        cLog::Debug(cT::t('Добавлен  товар %fullname / %id / %nid', $vars));
      }
    } else {
      throw new ErrorException(cT::t('Товар %fullname не был добавлен', $vars));
    }
  }
  
  public function end() {
    cLog::Notice(cT::t('Добавлено новых товаров: %count', array('%count' => $this->newCount)));
    cLog::Notice(cT::t('Обновлено товаров: %count', array('%count' => $this->updateCount)));
    cLog::Notice(cT::t('Всего товаров: %count', array('%count' => $this->totalCount)));
    cLog::Notice(cT::t('Всего картинок: %count', array('%count' => $this->imageCount)));
    parent::end();
  }
  
  /**
   * add product to db
   * @param array $product
   * @return nid of product or FALSE if fail
   */
  abstract public function addProduct(cProduct $product);
  
  /**
   * update existing product in db
   * @param int $internalId
   * @param array $product
   * @return bool result
   */
  abstract public function updateProduct($internalId, cProduct $product);
  
  
}

abstract class cGenericOrdersConverter extends cOrdersConverter {
  static protected $marksTable = 'cordersmarks';
  static protected $mapTable;
  protected $updateCount = 0;
  protected $totalCount = 0;
  
  public function __construct() {
    self::$mapTable = cGenericProductConverter::$mapTable; 
  }

  protected function getMarksTable() {
  	return self::$marksTable;
  }

  protected function getMapTable() {
  	return self::$mapTable;
  }

  
  public function onOffer(cPriceOffer $offer) {
    $priceTypes = &$this->commerceInfo->offers->priceTypes;
    if (!isset($priceTypes)) {
      cLog::Error(cT::t('Не удалось получить возможные типы цен из orders.xml'));
      return;
    }

    $vars = array('%product' => $offer->name, '%offer'=> (string)$offer);
    $priceType = NULL;
    $currency = $price = NULL;
    $price = 0;
    if ($offer->prices) {
        foreach ($offer->prices as $priceTypeId => $priceItem ) {
          $priceType = &$priceTypes[$priceTypeId];
          $vars['%priceType'] = $priceType['name'];
          if ( !$priceType['tax'] && $priceType['name'] == 'Розничная' ) {
            $price = $priceItem['price'];
            $currency = $priceItem['currency'];
            break;
          }
        }
    }
    $vars['%priceCurrency'] = $currency;

    $vars['%price'] = $price;
    $internalId = $this->mapId($offer->id);
    $ok = FALSE;
    if ( $internalId ) {
      $ok = $this->setPrice($internalId, $price, $currency);
    }
    
    if ( $ok ) {
      $this->markUpdated($internalId);
      $this->updateCount++;
      cLog::Debug(cT::t('Обновлена цена для товара %product: %price, тип цены: %priceType, валюта: %priceCurrency', $vars));
    } else {
      throw new ErrorException(cT::t('Ошибка импорта цены для %product: в базе товар предварительно нужно импортировать из import.xml: %offer', $vars));
    }
    $this->totalCount++;
  }
  
  public function end() {
    cLog::Notice(cT::t('Обновлено цен у товаров: %count', array('%count' => $this->updateCount)));
    cLog::Notice(cT::t('Всего обработано товаров: %count', array('%count' => $this->totalCount)));
    parent::end();
  }

  /**
   * set price for product
   * @param int $id
   * @param float $price
   * @param string $currency
   * @return TRUE, if success
   */
  abstract public function setPrice($internalId, $price, $currency);
}
