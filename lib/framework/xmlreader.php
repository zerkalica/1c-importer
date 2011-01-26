<?php



/**
 * Расширение для преобразование xml данных в плоские списки
 * @author nexor
 *
 */
class cXMLReader extends XMLReader {
  //private $elementRead  = FALSE;
  
  /**
   * @param array $map array(имя_тега=>объект, ...)  или array(имя_тега2=>array(объект, метод), ...)
   * если метод не указан, вызывается метод load
   * ко всем методам в качестве параметра передается $this
   */
  public function readCall($map, cXMLReaderItem $baseObject = NULL) {
    $map = $this->prepareObjectMap($map, $baseObject);
     
    //$packedEnded = TRUE;
    
    while ( ($this->read()) ) { // $packedEnded &&  
      if ( $this->nodeType == XMLREADER::ELEMENT ) {
        if ( isset($map[$this->localName]) ) {
          $item = $map[$this->localName];
          $object = $item['object'];
          $method = $item['loadMethod'];
          $object->$method($this);
        } else {
          throw new ErrorException(cT::t(
          	'Тег %tag не должен быть здесь, допустимые теги: %args',
            array('%tag' => $this->localName, '%args' => implode(', ', array_keys($map)))
          ));
        }
      } else if ( $this->nodeType == XMLREADER::END_ELEMENT ) {
        if ( !isset($map[$this->localName]) ) {
          if ( $baseObject ) {
            $baseObject->end();
          }
          break;
          //$packedEnded = FALSE;
        }
      }
    }
  }
  
  private function prepareObjectMap( $map, cXMLReaderItem $baseObject = NULL ) {
    $map_converted = array();
    
    foreach($map as $tag => $item) {
      $item_converted = array();
      $loadMethod = NULL;
      
      if( is_string($item) ) {
        if($baseObject == NULL) {
          throw new BadMethodCallException("baseObject должен быть определен для этого способа передачи параметров");
        }
        $object = $baseObject;
        $loadMethod = $item;
      } else if ( is_object($item) ) {
        $object = $item;
        $loadMethod = 'load';
      } else {
        throw new ErrorException("prepareObjectMap item не опознана");
      }
      if ( !$loadMethod ) {
        throw new ErrorException("loadMethod не определен");
      }
      $map_converted[$tag] = array(
        'object' => $object,
        'loadMethod' => $loadMethod,
      );
    }
    
    return $map_converted;
  }

  public function readString() {
    if ( $this->nodeType != XMLREADER::ELEMENT ) {
      throw new Exception("Должен быть элемент: {$this->value}");
    }
    $value = parent::readString();
    return $value;
  }
  
  public function readBool() {
    return $this->getBool($this->readString());
  }
  
  public function getBool($value) {
    return $value != 'false';
  }
  public function readFloat() {
    return $this->getFloat($this->readString());
  }
  public function getFloat($value) {
    return (float)str_replace(',', '.', $value);
  }
  
}

/**
 * Базовый класс для всех методов, которые вызывает cXMLReader
 * @author nexor
 */
abstract class cXMLReaderItem {
  
  protected $parentArrayPtr;
  
  public function __construct( &$parentArrayPtr = FALSE ) {
    if ( $parentArrayPtr !== FALSE ) {
      $this->setParentArrayPtr($parentArrayPtr);
    }
  }
  
  /**
   * Вызывается из cXMLReader, когда найден соотвествующий тег в $map
   * @param cXMLReader $xml
   */
  public function load(cXMLReader $xml) {
    $xml->readCall($this->getTagMap(), $this);
  }
  
  public function setParentArrayPtr(&$parentArrayPtr) {
    $this->parentArrayPtr = &$parentArrayPtr;
    $this->parentArrayPtr = array();
  }
  
  abstract public function getTagMap();
  
  public function end() {
    if ( !is_null($this->parentArrayPtr) && $this->isFieldsFilled() ) {
      $this->setParentArray();
      $this->reset();
    }
  }
  
  public function isFieldsFilled() {
    return FALSE;
  }
  
  public function reset() {
    
  }
  public function setParentArray() {
    
  }
}
