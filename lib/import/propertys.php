<?php

class cPropertyTypeVariant extends cXMLReaderIdValueItem {
}

/**
 * @author nexor
 *
 */
class cClassificatorPropertyType extends cXMLReaderItem {
  public $type;
  public $description;
  public $variants;

  public function getTagMap() {
    return array(
    		'Тип' => 'setType',
    		'Описание' => 'setDescription',
      	'ВариантыЗначений' => 'loadVariants',
    );
  }

  public function setType(cXMLReader $xml) {
    $this->type = $xml->readString();
  }

  public function setDescription(cXMLReader $xml) {
    $this->description = $xml->readString();
  }

  public function loadVariants(cXMLReader $xml) {
    $variant = new cPropertyTypeVariant($this->variants);
    $xml->readCall(array(
      	'ВариантЗначения' => $variant,
    ));
  }
  
  public function reset() {
    $this->type = NULL;
    $this->description = NULL;
    $this->variants = NULL;
  }
  
  public function isFieldsFilled() {
    return (bool)$this->type && (bool) $this->variants;
  }
  
  public function setParentArray() {
    $this->parentArrayPtr[$this->type] = array(
      'description' => $this->description,
      'variants' => $this->variants
    );
  }
  
}

class cClassificatorProperty extends cXMLReaderIdNameItem {
  public $types;
  public function getTagMap() {
    return array_merge( parent::getTagMap(), array(
      'ТипыЗначений' => 'loadTypes'
      )
    );
  }

  public function loadTypes(cXMLReader $xml) {
    $type = new cClassificatorPropertyType($this->types);
    $xml->readCall(array(
    	'ТипЗначений' => $type,
    ));
  }
  public function reset() {
    $this->types = NULL;
    parent::reset();
  }
  
  public function isFieldsFilled() {
    return parent::isFieldsFilled() && (bool)$this->types;
  }
  
  public function setParentArray() {
    $this->parentArrayPtr[$this->id] = array(
      'name' => $this->name,
      'types' => $this->types
    );
  }
  
}
