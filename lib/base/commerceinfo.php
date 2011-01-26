<?php

abstract class cCommerceInfoBase extends cXMLReaderItem {
  
  const SCHEMA_ID = '2.04.1CBitrix';

  /**
   * версия схемы
   * @var string
   */
  public $schemaVersion;
  /**
   * Формат даты
   * @var string
   */
  public $dateFormat;
  /**
   * Формат времени
   * @var string
   */
  public $timeFormat;
  /**
   * Разделитель дата время
   * @var string
   */
  public $dateTimeSeparator;
  /**
   * Формат суммы
   * @var string
   */
  public $sumFormat;
  /**
   * Формат количества
   * @var string
   */
  public $countFormat;
  /**
   * Дата формирования
   * @var string
   */
  public $formDate;
  
  public function getTagMap() {
    return array(
    	'КоммерческаяИнформация' => 'setInfo',
    );
  }

  /**
   * Установить общую информацию о прайсе
   * @param cXMLReader $xml
   */
  public function setInfo(cXMLReader $xml) {
    $this->schemaVersion = $xml->getAttribute('ВерсияСхемы');
    $this->dateFormat = $xml->getAttribute('ФорматДаты');
    $this->timeFormat = $xml->getAttribute('ФорматВремени');
    $this->dateTimeSeparator = $xml->getAttribute('РазделительДатаВремя');
    $this->sumFormat = $xml->getAttribute('ФорматСуммы');
    $this->countFormat = $xml->getAttribute('ФорматКоличества');
    $this->formDate = $xml->getAttribute('ДатаФормирования');
    if ($this->schemaVersion != self::SCHEMA_ID) {
      throw new ErrorException("Схема версии {$this->schemaVersion}, парсер работает только с версией " . self::SCHEMA_ID);
    }
  }
}

class cCommerceInfoImport extends cCommerceInfoBase {
  /**
   *
   * Основной классификатор груп товаров
   * @var cClassificator
   */
  public $classificator;

  /**
   * Каталог груп товаров
   * @var cCatalog
   */
  public $catalog;

  public function __construct( cClassificator $classificator, cCatalog $catalog) {
    $this->classificator = $classificator;
    $this->catalog = $catalog;
  }

  public function setInfo(cXMLReader $xml) {
    parent::setInfo($xml);
    $xml->readCall( array(
    	'Классификатор' => $this->classificator,
    	'Каталог' => $this->catalog,
    ) );
  }
}

class cCommerceInfoOffers extends cCommerceInfoBase {
  /**
   *
   * @var cOffers
   */
  public $offers;

  public function __construct(cOffers $offers) {
    $this->offers = $offers;
  }

  public function setInfo(cXMLReader $xml) {
    parent::setInfo($xml);
    $xml->readCall( array(
    	'ПакетПредложений' => $this->offers,
    ) );
  }
}
