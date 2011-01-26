<?php

abstract class cXMLReaderIdItem extends cXMLReaderItem {
  /**
   * @var string
   */
  public $id;
  
  public function getTagMap() {
    return array(
    	'Ид' => 'setId',
    );
  }

  public function setId(cXMLReader $xml) {
    $this->id = $xml->readString();
  }
  
  public function reset() {
    $this->id = NULL;
  }
  
  public function setParentArray() {
    $this->parentArrayPtr[$this->id] = NULL;
  }
  public function isFieldsFilled() {
    if ($this->id == '00000000-0000-0000-0000-000000000000') {
      cLog::Warning(cT::t('Нулевой Ид - глюк в базе 1С'));
      $this->id = NULL;
    }
    return (bool)$this->id;
  }
}

abstract class cXMLReaderIdValueItem extends cXMLReaderIdItem{
  /**
   * @var string
   */
  public $value;
  public function getTagMap() {
    return array_merge(parent::getTagMap(), array(
    	'Значение' => 'setValue'
    ));
  }
  public function setValue(cXMLReader $xml) {
    $this->value = $xml->readString();
  }
  
  public function reset() {
    $this->value = NULL;
    parent::reset();
  }
  public function setParentArray() {
    parent::setParentArray();
    $this->parentArrayPtr[$this->id] = $this->value;
  }
  
  public function isFieldsFilled() {
    return (bool)$this->value && parent::isFieldsFilled();
  }
  
}

abstract class cXMLReaderIdNameItem extends cXMLReaderIdItem{
  public $name;
  public function getTagMap() {
    return array_merge(parent::getTagMap(), array(
    	'Наименование' => 'setName'
    ));
  }
  public function setName(cXMLReader $xml) {
    $this->name = $xml->readString();
  }
  
  public function reset() {
    $this->name = NULL;
    parent::reset();
  }
  
  public function setParentArray() {
    parent::setParentArray();
    $this->parentArrayPtr[$this->id] = $this->name;
  }

  public function isFieldsFilled() {
    return (bool)$this->name && parent::isFieldsFilled();
  }
  
}

abstract class cXMLReaderTypeValueItem extends cXMLReaderItem {
  public $type;
  public $value;
  public function getTagMap() {
    return array(
      'Тип' => 'setType',
      'Значение' => 'setValue',
    );
  }

  public function setType(cXMLReader $xml) {
    $this->type = $xml->readString();
  }

  public function setValue(cXMLReader $xml) {
    $this->value = $xml->readString();
  }
  
  public function reset() {
    $this->type = NULL;
    $this->value = NULL;
  }
  
  public function setParentArray() {
    parent::setParentArray();
    $this->parentArrayPtr[$this->type] = $this->value;
  }
  
  public function isFieldsFilled() {
    return (bool)$this->value && (bool)$this->type;
  }
  
}


abstract class cXMLReaderNameValueItem extends cXMLReaderItem {
  public $name;
  public $value;
  public function getTagMap() {
    return array(
      'Наименование' => 'setName',
      'Значение' => 'setValue',
    );
  }
  public function setValue(cXMLReader $xml) {
    $this->value = $xml->readString();
  }
  
  public function setName(cXMLReader $xml) {
    $this->name = $xml->readString();
  }
  
  public function reset() {
    $this->name = NULL;
    $this->value = NULL;
  }
  
  public function setParentArray() {
    parent::setParentArray();
    $this->parentArrayPtr[$this->name] = $this->value;
  }
  
  public function isFieldsFilled() {
    return (bool)$this->value && (bool)$this->name;
  }
  
}
