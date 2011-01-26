<?php

class cProductPropertyValues extends cXMLReaderIdItem {
  /**
   * @var string
   */
  public $valueId;
  
  public function getTagMap() {
    return array_merge( parent::getTagMap(), array(
      'ИдЗначения' => 'setValueId',
    ) );
  }
  
  public function setValueId(cXMLReader $xml) {
    $this->valueId = $xml->readString();
  }
  public function setParentArray() {
    $this->parentArrayPtr[$this->id] = $this->valueId;
  }
  
  public function isFieldsFilled() {
    $valueOk = $this->valueId != '00000000-0000-0000-0000-000000000000';
    if (!$valueOk) {
      cLog::Warning(cT::t('Нулевое ИдЗначения для ид %id - глюк в базе 1С', array('%id'=>$this->id)));
    }
    return parent::isFieldsFilled() && $valueOk;
  }
  
}

class cProductAttributeValues extends cXMLReaderNameValueItem {
}


class cProductGroup extends cXMLReaderIdItem {
  public function setParentArray() {
    $this->parentArrayPtr[] = $this->id;
  }
};

class cProductTaxRates extends cXMLReaderItem {
  public $name;
  public function getTagMap() {
    return array(
      'Наименование' => 'setTaxRate',
    );
  }
  
  public function setTaxRate( cXMLReader $xml ) {
    $this->name = $xml->readString();
  }
  
  public function reset() {
    $this->name = NULL;
  }
  
}

class cProduct extends cXMLReaderIdNameItem {
  
  /**
   * @var string
   */
  public $fullName;
  /**
   * @var string
   */
  public $description;
  /**
   * @var array of string
   */
  public $images = array();
  /**
   * @var array
   */
  public $groups;
  /**
   * @var array
   */
  public $propertys;
  /**
   * @var array
   */
  public $attributes;
  /**
   * @var array
   */
  public $baseUnit;
  /**
   * @var cProductConverter
   */
  private $converter;
  /**
   * filled in cGenericProductConverter::productAddGroupIdsAndProperyValues
   * @var array index array of ints
   */
  public $groupIds;
  /**
   * filled in cGenericProductConverter::productAddGroupIdsAndProperyValues
   * @var array assoc array of propery name => property value
   */
  public $propertysValues;

  public $taxRates;
  
  public $marking;
  
  public function __construct(cProductConverter $converter) {
    $this->converter = $converter;
  }
  
  public function end() {
    $this->converter->onProduct($this);
    $this->reset();
    //parent::end();
  }
  
  public function getTagMap() {
    return array_merge( parent::getTagMap(), array(
      'ПолноеНаименование' => 'setFullName',
      'Картинка' => 'addImage',
      'БазоваяЕдиница' => 'setBaseUnit',
    	'Группы' => 'setGroups',
      'Описание' => 'setDescription',
    	'ЗначенияСвойств' => 'setPropertysValues',
      'ЗначенияРеквизитов' => 'setAttributesValues',
      'СтавкиНалогов' => 'setTaxRates',
      'Артикул' => 'setMarking'
    ) );
  }
  
  public function setGroups(cXMLReader $xml) {
    $group = new cProductGroup($this->groups);
    $group->load($xml);
  }
  
  public function setDescription(cXMLReader $xml) {
    $this->description = $xml->readString();
  }
  
  public function setMarking(cXMLReader $xml) {
    $this->marking = $xml->readString();
  }
  
  public function setBaseUnit(cXMLReader $xml) {
    $this->baseUnit = array(
      'code' => $xml->getAttribute('Код'),
      'fullName' => $xml->getAttribute('НаименованиеПолное'),
      'internationalFormat' => $xml->getAttribute('МеждународноеСокращение'),
      'value' => $xml->readString()
    );
  }
  
  public function setFullName(cXMLReader $xml) {
    $this->fullName = $xml->readString();
  }
  
  public function addImage(cXMLReader $xml) {
    $this->images[] = $xml->readString();
  }
  
  public function setPropertysValues(cXMLReader $xml) {
    $propertyValues = new cProductPropertyValues($this->propertys);
    $xml->readCall(array(
    		'ЗначенияСвойства' => $propertyValues,
    ));
  }
  
  public function setAttributesValues(cXMLReader $xml) {
    $attributeValues = new cProductAttributeValues($this->attributes);
    $xml->readCall(array(
    		'ЗначениеРеквизита' => $attributeValues,
    ));
  }
  
  public function setTaxRates(cXMLReader $xml) {
    $propertyValues = new cProductTaxRates($this->taxRates);
    $xml->readCall( array(
    		'СтавкаНалога' => $propertyValues,
    ));
  }
  
  public function reset() {
    $this->fullName = NULL;
    $this->images = array();
    $this->groups = NULL;
    $this->propertys = NULL;
    $this->attributes = NULL;
    $this->baseUnit = NULL;
    $this->taxRates = NULL;
    $this->marking = NULL;
    parent::reset();
  }
}

class cCatalog extends cXMLReaderIdNameItem {
  /**
   * @var bool
   */
  public $onlyChanges = FALSE;
  
  public $classificatorId;
  
  /**
   * @var cClassificatorOwner
   */
  public $owner;
  
  /**
   * @var cProduct
   */
  public $product;

  public function __construct(cProduct $product) {
    $this->owner = new cClassificatorOwner();
    $this->product = $product;
  }
  
  public function load(cXMLReader $xml) {
    $this->onlyChanges = $xml->getBool($xml->getAttribute('СодержитТолькоИзменения'));
    parent::load($xml);
  }
  
  public function getTagMap() {
    return array_merge( parent::getTagMap(), array(
      'ИдКлассификатора' => 'setClassificatorId',
      'Владелец' => $this->owner,
      'Товары' => 'setProducts'
    ) );
  }
  
  public function setClassificatorId(cXMLReader $xml) {
    $this->classificatorId = $xml->readString();
  }
  
  public function setProducts(cXMLReader $xml) {
    $xml->readCall(array(
    		'Товар' => $this->product,
    ));
  }
  
}
