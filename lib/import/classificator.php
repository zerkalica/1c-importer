<?php

/**
 * Классификатор товаров 1C
 * @author nexor
 *
 */
class cClassificator extends cXMLReaderIdNameItem {
  /**
   * @var cClassificatorGroups
   */
  private $groupsObject;

  /**
   * Свойства товаров
   * @var array of cClassificatorProperty
   */
  public $propertys;

  /**
   * Владелец
   * @var cClassificatorOwner
   */
  public $owner;
  /**
   * @var array of cClassificatorGroups
   */
  public $groups;
  /**
   * @var cCommerceInfo
   */
  public $commerceInfo;
  
  /**
   * @var cClassificatorConverter
   */
  public $converter;
  
  public function __construct(cClassificatorConverter $converter) {
    $this->groupsObject = new cClassificatorGroups($this->groups);
    $this->owner = new cClassificatorOwner();
    $this->converter=$converter;
  }
  
  public function getTagMap() {
    return array_merge( parent::getTagMap(), array(
      'Владелец' => $this->owner,
    	'Группы' => $this->groupsObject,
    	'Свойства' => 'loadPropertys',
    ));
  }
  
  public function loadPropertys(cXMLReader $xml) {
    $property = new cClassificatorProperty($this->propertys);
    $xml->readCall(array(
    		'Свойство' => $property,
    ));
  }

  public function end() {
    $this->converter->onClassificator($this);
    //parent::end();
  }
    
}
