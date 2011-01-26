<?php

class cPriceTax extends cXMLReaderItem {
  /**
   * @var string
   */
  public $name;
  /**
   * @var bool
   */
  public $takenIntoSum;
  public function getTagMap() {
    return array(
      'Наименование' => 'setName',
      'УчтеноВСумме' => 'setTaken',
    );
  }
  public function setName(cXMLReader $xml) {
    $this->name = $xml->readString();
  }
  public function setTaken(cXMLReader $xml) {
    $this->takenIntoSum = $xml->readBool();
  }
  public function isFieldsFilled() {
    return (bool)$this->name;
  }
  public function reset() {
    $this->name = NULL;
    $this->takenIntoSum = NULL;
  }
  public function setParentArray() {
    $this->parentArrayPtr[$this->name] = $this->takenIntoSum;
  }
}

class cPriceType extends cXMLReaderIdNameItem {
  /**
   * @var string
   */
  public $currency;
  
  /**
   * @var cPriceTax
   */
  private $taxObject;
  
  /**
   * @var array
   */
  public $tax;
  
  public function __construct(&$parentArrayPtr = FALSE) {
    parent::__construct($parentArrayPtr);
    $this->taxObject = new cPriceTax($this->tax);
  }
  
  public function getTagMap() {
    return array_merge(parent::getTagMap(), array(
      'Валюта' => 'setCurrency',
      'Налог' => $this->taxObject,
    ));
  }
  
  public function setCurrency(cXMLReader $xml) {
    $this->currency = $xml->readString();
  }
  
  public function isFieldsFilled() {
    return parent::isFieldsFilled() && (bool)$this->currency;
  }
  public function reset() {
    $this->tax = NULL;
    $this->currency = NULL;
    parent::reset();
  }
  
  public function setParentArray() {
    $this->parentArrayPtr[$this->id] = array(
      'name' => $this->name,
      'currency' => $this->currency,
      'tax' => $this->tax,
    );
  }
}
