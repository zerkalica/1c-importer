<?php

class cPrice extends cXMLReaderItem {
  /**
   * @var string
   */
  public $view;
  /**
   * @var string
   */
  public $typePriceId;
  /**
   * @var float
   */
  public $price;
  /**
   * @var string
   */
  public $currency;
  /**
   * @var string
   */
  public $unit;
  /**
   * @var float
   */
  public $coefficient;
  
  public function getTagMap() {
    return array(
      'Представление' => 'setView',
      'ИдТипаЦены' => 'setTypePriceId',
      'ЦенаЗаЕдиницу' => 'setPrice',
      'Валюта' => 'setCurrency',
      'Единица' => 'setUnit',
      'Коэффициент' => 'setCoefficient'
    );
  }
  public function setView(cXMLReader $xml) {
    $this->view = $xml->readString();
  }
  public function setTypePriceId(cXMLReader $xml) {
    $this->typePriceId = $xml->readString();
  }
  public function setPrice(cXMLReader $xml) {
    $this->price = $xml->readFloat();
  }
  public function setCurrency(cXMLReader $xml) {
    $this->currency = $xml->readString();
  }
  public function setUnit(cXMLReader $xml) {
    $this->unit = $xml->readString();
  }
  public function setCoefficient(cXMLReader $xml) {
    $this->coefficient = $xml->readFloat();
  }
  
  public function reset() {
    $this->view = $this->typePriceId = $this->currency = $this->unit = $this->coefficient = $this->price = NULL;
  }
  
  public function isFieldsFilled() {
    return (bool) $this->view && (bool) $this->typePriceId && (bool) $this->currency &&
      (bool) $this->unit && (bool) $this->price;
  }
  
  public function setParentArray() {
    $this->parentArrayPtr[$this->typePriceId] = array(
      'view' => $this->view,
      'price' => $this->price,
      'currency' => $this->currency,
      'unit' => $this->unit,
      'coefficient' => $this->coefficient
    );
  }
}

class cPriceOffer extends cXMLReaderIdNameItem {
  /**
   * @var array
   */
  public $prices;
  
  /**
   * @var cOrdersConverter
   */
  private $converter;
  
  public function __construct(cOrdersConverter $converter) {
    $this->converter = $converter;
  }
  
  public function getTagMap() {
    return array_merge(parent::getTagMap(), array(
      'Цены' => 'setPrices',
    ));
  }
  public function setPrices(cXMLReader $xml) {
    $price = new cPrice($this->prices);
    $xml->readCall(array(
      'Цена' => $price
    ));
  }
  
  public function isFieldsFilled() {
    return parent::isFieldsFilled() && (bool)$this->prices;
  }
  public function reset() {
    $this->prices = NULL;
    parent::reset();
  }
  
  public function end() {
    $this->converter->onOffer($this);
    parent::end();
  }
  
  public function __toString() {
    return $this->name . ': [' . print_r($this->prices, TRUE) .']';
  }
  
}
