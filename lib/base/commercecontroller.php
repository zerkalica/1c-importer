<?php
abstract class cCommerceControllerBase {
  /**
   * @var string
   */
  protected $filename;
  /**
   * @var cCommerceInfoBase
   */
  protected $commerceInfo;
  
  /**
   * @var cConverter
   */
  protected $converter;

  public function run() {
    if (!file_exists($this->filename)) {
      throw new ErrorException( cT::t('Файл не существует: %file', array('%file' => $this->filename)));
    }
    $xml = new cXMLReader();
    $xml->open($this->filename);
    $this->converter->begin();
    $this->commerceInfo->load($xml);
    $xml->close();
    $this->converter->end();
  }
  
  public function __construct($filename) {
    $this->converter->setCommerceInfo($this->commerceInfo);
    $this->filename = $filename;
  }
  
}

class cCommerceImportController extends cCommerceControllerBase {
  
  /**
   * @var cClassificatorConverter
   */
  private $classificatorConverter;
  
  public function __construct($filename, cClassificatorConverter $classificatorConverter, cProductConverter $importConverter) {
    $this->converter = $importConverter;
    $this->classificatorConverter = $classificatorConverter;
    $this->commerceInfo = new cCommerceInfoImport(new cClassificator($this->classificatorConverter), new cCatalog(new cProduct($this->converter)));
    parent::__construct($filename);
  }
  public function run() {
    $this->classificatorConverter->begin();
    parent::run();
    $this->classificatorConverter->end();
  }
    
}

class cCommerceOrdersController extends cCommerceControllerBase {
  public function __construct($filename, cOrdersConverter $driver) {
    $this->converter = $driver;
    $this->commerceInfo = new cCommerceInfoOffers(new cOffers(new cPriceOffer($this->converter) ) );
    parent::__construct($filename);
  }
    
}

