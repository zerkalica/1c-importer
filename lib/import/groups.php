<?php
/**
 * Группы классификатора
 * Превращает XML-дерево Группы из 1C классификатора в плоскую структуру данных
 * ид и имен
 * @author nexor
 *
 */
class cClassificatorGroups extends cXMLReaderItem {
  /**
   * используется для составления карты Ид => индекс, индекс => Наименование
   * @var int
   */
  private $id;
  private $name;
  private $parents = array(NULL);
  
  public function getTagMap() {
    return array(
      	'Группа' => 'loadGroup',
    );
  }

  public function loadGroup(cXMLReader $xml) {
    $xml->readCall(array(
    		'Ид' => 'setId',
      	'Наименование' => 'setName',
      	'Группы' => 'addGroups',
    ), $this);
  }
  
  public function addGroups(cXMLReader $xml) {
    if( $xml->isEmptyElement ) {
      return;
    }
    $this->end();
    $this->parents[] = $this->id;
    $this->load($xml);
    array_pop($this->parents);
  }
  
  public function setId(cXMLReader $xml) {
    $this->id = $xml->readString();
  }

  /**
   * Добавляет новое имя группы в массив
   * @param cXMLReader $xml
   */
  public function setName(cXMLReader $xml) {
    $this->name = $xml->readString();
  }
  
  public function reset() {
    //$this->id = NULL;
    $this->name = NULL;
    parent::reset();
  }
  
  public function setParentArray() {
    if ( sizeof($this->parents) - 1 < 0 ) {
      throw new ErrorException("Массив групп пострадал");
    }
    $parentId = $this->parents[sizeof($this->parents) - 1];
    $this->parentArrayPtr[$this->id] = array(
      'parent' => $parentId,
      'name' => $this->name,
    );
  }
  
  public function isFieldsFilled() {
    return (bool)$this->id && (bool)$this->name;
  }
  
}
