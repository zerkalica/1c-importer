<?php

class cOffers extends cXMLReaderIdNameItem {
  /**
   * @var bool
   */
  public $onlyChanges;
  /**
   * @var string
   */
  public $catalogId;
  /**
   * @var string
   */
  public $classificatorId;
  
  /**
   * @var cClassificatorOwner
   */
  public $owner;
  
  /**
   * @var array
   */
  public $priceTypes;
  
  /**
   * @var cPriceOffer
   */
  public $offer;
  
  public function __construct(cPriceOffer $offer) {
    $this->offer = $offer;
    $this->owner = new cClassificatorOwner();
  }
  
  public function load(cXMLReader $xml) {
    $this->onlyChanges = $xml->getBool($xml->getAttribute('СодержитТолькоИзменения'));
    parent::load($xml);
  }
  
  public function getTagMap() {
    return array_merge(parent::getTagMap(), array(
      'ИдКаталога' => 'setCatalogId',
      'ИдКлассификатора' => 'setClassificatorId',
      'Владелец' => $this->owner,
      'ТипыЦен' => 'setPriceTypes',
      'Предложения' => 'setOffers',
    ));
  }
  
  public function setClassificatorId(cXMLReader $xml) {
    $this->classificatorId = $xml->readString();
  }

  public function setCatalogId(cXMLReader $xml) {
    $this->catalogId = $xml->readString();
  }
  
  public function setPriceTypes(cXMLReader $xml) {
    $priceType = new cPriceType($this->priceTypes);
    $xml->readCall( array(
      'ТипЦены' => $priceType,
    ));
  }
  
  public function setOffers(cXMLReader $xml) {
    $xml->readCall(array(
      'Предложение' => $this->offer,
    ));
  }
  
}

