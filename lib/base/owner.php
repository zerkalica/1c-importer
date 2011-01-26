<?php

class cAddressField extends cXMLReaderTypeValueItem {
  
}

/**
 * Владелец, определенный в классификаторе
 * @author nexor
 *
 */
class cClassificatorOwner extends cXMLReaderIdNameItem {
  /**
   * @var string
   */
  public $officialName;
  public $legalAddressView;
  public $address;

  public function getTagMap() {
    return array_merge(parent::getTagMap(), array(
      'ОфициальноеНаименование' => 'setOfficialName',
    	'ЮридическийАдрес' => 'loadLegalAddress',
    ));
  }

  public function setOfficialName(cXMLReader $xml) {
    $this->officialName = $xml->readString();
  }

  public function loadLegalAddress(cXMLReader $xml) {
    $address = new cAddressField($this->address);
    $xml->readCall(array(
  		'Представление' => 'setAddressView',
  		'АдресноеПоле' => $address,
    ), $this);
  }

  public function setAddressView(cXMLReader $xml) {
    $this->legalAddressView = $xml->readString();
  }

}
