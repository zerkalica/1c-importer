<?php

class cDrupalTaxonomy {
  static public $termDataTable = 'dr_term_data';
  static public $termHierarchyTable = 'dr_term_hierarchy';
  static public $termNodeTable = 'dr_term_node';
  static public $vocabularyTable = 'dr_vocabulary';
  static public $symTable = 'dr_url_alias';
  
  /**
   * add term to taxonomy 
   * @param string $name
   * @param int $vid
   */
  static public function addTag($name, $vid, $parentInternalId = NULL) {
    if (!$vid) {
      throw new ErrorException(cT::t('Id словаря = 0 для %term', array('%term' => $name)));
    }
    
    $params = array(
      	'vid'=> $vid,
        'description' => '',
        'weight' => 0,
      	'name' => $name
    );
    
    $tid = cdb::get()->insert(self::$termDataTable, $params);
    
    if ( $tid ) {
      $params = array('tid' => $tid, 'parent' => (int) $parentInternalId);
      cdb::get()->insert(self::$termHierarchyTable, $params );
    }
    
    return $tid;
  }
  
  static public function makeLink($tagName, $prefix) {
    //product/by-usage/2878
    return $prefix . strtolower(preg_replace('#[^\w\d]#i', '_', transliteration_process($tagName)));
  }
  
  static public function updateSym($tagId, $tagName, $srcPrefix = 'taxonomy/term/', $dstPrefix = 'soft/') {
    $srcLink = $srcPrefix . $tagId;    
    $cond = array(
      'src' => $srcLink,
    );
    $link = self::makeLink($tagName, $dstPrefix);
    $data = $cond;
    $data = array(
      'dst' => $link,
    );
    
    $src = cdb::get()->readRow(self::$symTable, $data, 'src');
    if ($src) {
      cLog::Notice(cT::t('Синоним %sym уже есть в базе - заменяем', array('%sym' => $src)));
      cdb::get()->delete(self::$symTable, $data);
    }
    cdb::get()->delete(self::$symTable, $cond);
    $update = array_merge($cond, $data);
    cdb::get()->insert(self::$symTable, $update);
  }
  
  static public function updateTag($internalId, $name, $parentInternalId = 0, $vid) {
    $result = cdb::get()->update( self::$termDataTable, 
      array('name' => $name, 'vid' => $vid), 
      array('tid' => $internalId)
    );
    if ( $result && $parentInternalId ) {
      $result = cdb::get()->update( self::$termHierarchyTable, 
        array('parent' => (int)$parentInternalId), 
        array('tid' => $internalId)
      );
    }
    return $result;
  }
  
  static public function getVocIdByName($name) {
    $vid = cdb::get()->readRow(self::$vocabularyTable, array('name' => $name), 'vid');
    if (!$vid) {
      throw new LogicException(cT::t('Словарь %voc не найден в базе drupal / %table', array('%voc' => $name, '%table' => self::$vocabularyTable)));
    }
    return $vid;
  }
  
  /**
   * delete and insert new tids for node
   * @param int $internalId
   * @param array $tags index array of ints
   * @return int affected rowCount
   */
  static public function updateTagsForNode($internalId, array $tags ) {
    $result = 0;
    cdb::get()->delete(self::$termNodeTable, array('nid' => $internalId) );
    cLog::Debug(cT::t('Добавляются теги: %tids', array('%tids' => implode(',', $tags))));
    foreach ($tags as $tid) {
      if ($tid == 0 ) {
        throw new ErrorException(cT::t('номер группы не может быть нулевой : %tags', array('%tags' => var_export($tags, TRUE))));
      }
      $result += (int) (bool) cdb::get()->insert( self::$termNodeTable, array('nid' => $internalId, 'tid' => $tid));
    }
    return $result;
  }
  
  static public function getTagIdByName($name, $vid) {
    static $names = array();
    if ( !isset($name[$vid]) || !isset($name[$vid][$names]) ) {
      $names[$vid][$name] = cdb::get()->readRow(self::$termDataTable, array('name' => $name, 'vid' => $vid), 'tid');
    }
    return $names[$vid][$name]; 
  }
  
}

class cDrupal5Fixers {
  
  static private $cacheTables = array(
  	'dr_cache',
		'dr_cache_advcache_block',
		'dr_cache_comment',
		'dr_cache_content',
		'dr_cache_filter',
		'dr_cache_forum',
		'dr_cache_menu',
		'dr_cache_node',
		'dr_cache_page',
		'dr_cache_path',
		'dr_cache_search',
		'dr_cache_taxonomy',
		'dr_cache_tax_image',
		'dr_cache_views'
  );
  
  static private $syncTabs = array(
		'menu'=>'mid',
		'node'=>'nid',
    'node_revisions'=>'vid',
    'bueditor_editors'=>'eid',
    'vocabulary'=>'vid',
    'term_data'=>'tid',
    'view_view'=>'vid',
    'ec_mail'=>'mid',
    'users'=>'uid',
    'files'=>'fid',
    'imagecache_preset'=>'presetid',
    'imagecache_action'=>'actionid',
    'ec_transaction'=>'txnid',
    'bueditor_editors'=>'eid',
    'comments'=>'cid'
  );
  
  static public function clearCaches() {
    cdb::get()->truncate( self::$cacheTables );
  }
  
  static public function fixSeq() {
    $updateQ = 'UPDATE `dr_sequences` SET `id` = ? WHERE `name` = ? LIMIT 1';
    $updateSth = cdb::get()->prepare($updateQ);
    foreach ( self::$syncTabs as $table => $name ) {
      $table = 'dr_' . $table;
      $q = 'SELECT MAX('.$name.') FROM `' . $table . '`';
      $sth = cdb::get()->query($q);
      $max = $sth->fetchColumn();
      if($max) {
        $name = $table . '_' . $name;
        $updateSth->execute(array($max, $name));
      }
    }
  }
  
  static public function deleteEmptyImages() {
    $q = 'DELETE p
    FROM `'.cDrupal5ProductConverter::$pictureTable.'` p
    WHERE p.`field_picture_fid` = ?';
    $sth = cdb::get()->prepare($q);
    $sth->execute(array(0));
  }

  static public function reset() {
    cdb::get()->truncate( cDrupal5ClassificatorConverter::$mapTable );
    cdb::get()->truncate( cDrupal5ProductConverter::$mapTable );

    cdb::get()->truncate( 'dr_watchdog' );
    
    
    $query = 'DELETE t, h
    FROM `'.cDrupalTaxonomy::$termDataTable.'` AS t
    LEFT JOIN `' . cDrupalTaxonomy::$termHierarchyTable . '` AS h ON h.`tid` = t.`tid`
    LEFT JOIN `'.cDrupalTaxonomy::$termNodeTable.'` AS n ON n.`tid` = t.`tid`
    WHERE t.`vid` = ?';
    
    $sth = cdb::get()->prepare($query);
    $sth->execute(array( cDrupalTaxonomy::getVocIdByName('Классификатор (Основной каталог товаров)') ));
    cLog::Debug(cT::t('Очистка классификатора групп товаров, удалено групп: %c', array('%c' => $sth->rowCount() )));
    
    $sth->execute(array( cDrupalTaxonomy::getVocIdByName('Назначение') ));
    cLog::Debug(cT::t('Очистка классификатора по назначению, удалено тегов: %c', array('%c' => $sth->rowCount() )));
    
    $query = 'DELETE n, r, p, f, t, e, c
    FROM `'.cDrupal5ProductConverter::$nodeMainTable.'` AS n
    INNER JOIN `'.cDrupal5ProductConverter::$nodeRevisionsTable.'` AS r ON r.`nid` = n.`nid`
    INNER JOIN `'.cDrupal5ProductConverter::$contentTable.'` AS c ON c.`vid` = n.`vid`
    INNER JOIN `'.cDrupal5ProductConverter::$ecProductTable.'` AS e ON e.`vid` = n.`vid`
    LEFT JOIN `'.cDrupal5ProductConverter::$pictureTable.'` AS p ON p.`vid` = n.`vid`
    LEFT JOIN `'.cDrupal5ProductConverter::$filesTable.'` AS f ON f.`nid` = n.`nid`
    LEFT JOIN `'.cDrupalTaxonomy::$termNodeTable.'` AS t ON t.`nid` = n.`nid`
    WHERE n.`type` = ?';
    
    $sth = cdb::get()->prepare($query);
    $sth->execute(array( cDrupal5ProductConverter::$nodeProductType ));
    cLog::Debug(cT::t('Удалено материалов товаров: %c', array('%c' => $sth->rowCount() )));
  }
  
  
  static public function fixAll() {
    self::clearCaches();
    self::fixSeq();
    self::deleteEmptyImages();
  }
}

class cDrupal5ClassificatorConverter extends cGenericClassificatorConverter {
  
  public function addGroup($name, $parentInternalId) {
    $tagId = cDrupalTaxonomy::addTag($name, $this->classificatorInternalId, $parentInternalId);
    cDrupalTaxonomy::updateSym($tagId, $name, 'product/', 'soft/');
    return $tagId;
  }
  
  public function updateGroup($internalId, $name, $parentInternalId) {
    cDrupalTaxonomy::updateSym($internalId, $name, 'product/', 'soft/');
    return cDrupalTaxonomy::updateTag($internalId, $name, $parentInternalId, $this->classificatorInternalId);
  }
  
  public function deleteUnused() {
    if ($this->classificatorInternalId) {
      cLog::Debug(cT::t(
      	'Начинаем удалять классификатор: %vid', array(
      		'%vid' => $this->classificatorInternalId
      )));
      $query = 'DELETE d, h, n, map
      FROM `' . cDrupalTaxonomy::$termDataTable . '` AS d
      LEFT JOIN `' . cDrupalTaxonomy::$termHierarchyTable . '` AS h ON h.`tid` = d.`tid`
      LEFT JOIN `' . cDrupalTaxonomy::$termNodeTable . '` AS n ON n.`tid` = d.`tid`
      LEFT JOIN `' . self::$mapTable . '` AS map ON map.`id` = d.`tid`
      LEFT JOIN `' . self::$marksTable . '` AS m ON m.`id` = d.`tid`
      WHERE m.`id` IS NULL AND d.`vid` = ?';
      $sth = cdb::get()->prepare($query);
      $sth->execute(array( $this->classificatorInternalId ));
      $result = $sth->rowCount();
      cLog::Debug(cT::t(
      	'Закончили удалять классификатор %vid, удалили: %count', array(
      		'%vid' => $this->classificatorInternalId, 
      		'%count' => $result
      )));
    } else {
      $result = 0;
    }
    return $result;
  }
  
  public function begin() {
    parent::begin();
    //cDrupal5Fixers::reset();
  }
  
  protected function getVocIdByName($name) {
    return cDrupalTaxonomy::getVocIdByName($name);
  }
  
}

class cDrupal5ProductConverter extends cGenericProductConverter {
  static public $nodeMainTable = 'dr_node';
  static public $nodeRevisionsTable = 'dr_node_revisions';
  static public $contentTable = 'dr_content_type_product';
  static public $pictureTable = 'dr_content_field_picture';
  static public $filesTable = 'dr_files';
  static public $ecProductTable = 'dr_ec_product';
  
  static public $nodeProductType = 'product';
  static private $defaultUid = 1;
  static public $cdNid = 45296;
  
  private $imagePathPrefix;
  
  private $licencesPrefix;
  private $drupalPath;
  private $bitrixPath;
  private $bitrixPathPrefix;
  
  static public $tagPropName = 'Назначение';
  
  private $tagVid;
  
  public function __construct() {
    $c = cConfig::get();
    $this->bitrixPathPrefix = $c->get('path', 'bitrixRootReal') . '/' ;
     
    $this->drupalPath = $c->get('path', 'filesRootReal') . '/';
    $this->imagePathPrefix = $c->get('drupalPath', 'images') . '/';
    $this->licencesPrefix = $c->get('drupalPath', 'licences') . '/';
  }
  
  private function getDbRows(cProduct $product, $internalId = 0, $insert = FALSE) {
    $main = array(
      'vid' => $internalId,
      'type' => self::$nodeProductType,
      'title' => $product->name,
      'uid' => self::$defaultUid,
      'changed' => time(),
    );
    $description = $product->description;
    if (!$description) {
      $description = '';
    }
    $revisions = array(
      'vid' => $internalId,
      'uid' => self::$defaultUid,
    	'body' => $description,
      'title' => $product->name,
      'teaser' => substr($description, 0, 1024),
    );
    
    $props = &$product->propertysValues;
    $site = (string)$this->getSite($props, 'Сайт компании');
    $page = (string)$this->getSite($props, 'Страница программы');
    $licence = (string)$this->getValue($props, 'Лицензионное соглашение');
    
    if ($licence) {
      $licence = $this->licencesPrefix . $licence;
      if (!file_exists($this->drupalPath . $licence)) {
        cLog::Warning(cT::t('Файл лицензии не найден: %file', array('%file' => $licence)));
      }
    }
     
    $content = array(
      'vid' => $internalId,
      'field_cdwrite_value' => (string)$this->compareValue($props, 'Запись на CD'),
    	'field_nds_value' => (string)$this->compareValue($props, 'НДС', 'НДС облагается'),
      'field_registration_value' => $this->compareValue($props, 'Регистрация продукта'),
    	'field_site_url' => $site,
    	'field_site_title' => $this->makeSiteName($site),
      'field_site_attributes' => NULL,
      'field_shipdate_value' => (string)$this->getValue($props, 'Срок доставки'),
      'field_page_url' => $page,
      'field_page_title' => $this->makeSiteName($page),
      'field_page_attributes' => NULL,
      'field_lang_value' => (string)$this->getValue($props, 'Язык интерфейса'),
      'field_license_url' => $licence,
      'field_license_title' => '',
      'field_license_attributes' => NULL,
      'field_shiptype_value' => (string)$this->getValue($props, 'Вид поставки'),
      'field_discounts_value' => (string)$this->getDiscounts($props, 'Скидка%02d'),
      'field_action_value' => (string)$this->compareValue($props, 'Акция', '1'),
      'field_fresh_value' => (string)$this->compareValue($props, 'Новинка', '1'),
    );
    
    $ecproduct = array(
      'vid' => $internalId,
      'pparent' => 0,
      'sku' => $this->getValue($props, 'Артикул'),
      'ptype' => 'generic',
      'price' => 0.0,
      'hide_cart_link' => 0,
    );
    
    $result = array('main' => $main, 'revisions' => $revisions, 'content' => $content, 'ecproduct'=> $ecproduct);
    if ( $insert ) {
      foreach ( array_keys($result) as $key) {
        $result[$key]['nid'] = $internalId;
      }
    }
    return $result;
  }
  
  private function compareValue(array $array, $key, $compareValue = 'Да', $trueValue = 1, $falseValue = 0) {
    return (isset($array[$key]) && $array[$key] == $compareValue) ? $trueValue : $falseValue;
  }
  
  private function getValue(array $array, $key, $falseValue = NULL) {
    return isset($array[$key]) ? $array[$key] : $falseValue;
  }
  
  private function getSite(array $array, $key, $falseValue = NULL) { 
    $result = $this->getValue($array, $key, $falseValue);
    if ( $result ) {
      $result = rtrim(str_replace(array('http://','ftp://'), '', $result), '\/');
    }
    return $result;
  }
  
  private function makeSiteName($url) {
    return rtrim($url, '\/');
  }
  
  
  private function getMime($filename ) {
    return mime_content_type($filename);
  }
  
  private function getDiscounts(array $props, $key, $falseValue = NULL) {
    $discounts = array();
    for ($i = 0 ; $i < 10 ; $i++ ) {
      $fieldName = sprintf($key, $i);
      if ( isset($props[$fieldName]) ) {
        $parts = explode(':', $props[$fieldName]);
        $discounts[$parts[0]] = str_replace( array(',', ' '), array('.', ''), $parts[1]); 
      }
    }
    return $discounts ? serialize($discounts) : $falseValue;
  }
  
  
  private function reAddPictures($internalId, $image, $name) {
    static $images = array();
    $result = FALSE;
    $condition = array('nid' => $internalId);
    
    $vars = array(
    	'%file' => $image,
      '%id' => $internalId,
      '%name' => $name,
      '%image' => $image
    );
    if (isset($images[$image])) {
      throw new ErrorException(cT::t('Картинка встретилась дважды: %file / %id / %name / %image', $vars));
    }
    $images[$image] = 1;
    
    $fn = basename($image);
    
    $sth = cdb::get()->find(self::$filesTable, $condition, 'filepath');
    while ( $row = $sth->fetch(PDO::FETCH_COLUMN) ) {
      
      $imagePath = $this->drupalPath . $this->imagePathPrefix . $row;
      if ($fn == basename($row)) {
        cLog::Debug( cT::t('Картинка %image уже существует в базе, всеравно заменяем', array('%image' => $row)) );
        //return TRUE;
      } else if ( file_exists($imagePath) ) {
        unlink($imagePath);
        cLog::Debug( cT::t('Удаляем старую картинку: %file', array('%file' => $imagePath) ) );
      }
    }
    
    cdb::get()->delete(self::$filesTable, $condition );
    cdb::get()->delete(self::$pictureTable, $condition );
    
    if ( $image ) {
      
      $oldPath = $this->bitrixPathPrefix . $image;
      $newPathRelative = $this->imagePathPrefix  . $fn;
      $newPath = $this->drupalPath . $newPathRelative;
      $filesize = 0;
      if ( !file_exists($oldPath) ) {
        throw new ErrorException(cT::t('Файл %file не найден, хотя в xml он есть', array('%file' => $oldPath)));
      }
      if ( file_exists($newPath)) {
        cLog::Warning(cT::t('Файл существует %file - перезаписываем', array('%file' => $newPath)));
      }
      if ( copy($oldPath, $newPath) ) { 
        $filesize = filesize($newPath);
      } else {
        $dest = dirname($newPath);
        throw new ErrorException(cT::t('Не удалось скопировать файл в папку %dest', array('%dest' => $dest)));
      }
      if ( $filesize ) {
        $fid = cdb::get()->insert(self::$filesTable, array(
        		'nid' => $internalId, 
            'filename' => $fn,
            'filepath' => $newPathRelative,
            'filemime' => $this->getMime($newPath),
            'filesize' => $filesize,
          ) 
        );
        if (!$fid) {
          throw new PDOException(cT::t('Запись о файле не добавилась в таблицу файлов: %err', array('%err' => var_export(cdb::get()->lastSth,1))));
        }
        $vars['%newpath'] = $newPath;
        if ( $fid ) {
          $vars['%fid'] = $fid;
          
          cdb::get()->insert(self::$pictureTable, array(
              'vid' => $internalId,
              'nid' => $internalId,
              'field_picture_fid' => $fid,
              'field_picture_title' => $name,
          		'field_picture_alt' => $name,
            )
          );
          $result = TRUE;
          if ( !$result ) {
            throw new PDOException(cT::t('Ошибка вставки в базу %fid', $vars));
          }
          
          cLog::Debug(cT::t('К товару %name / %id добавлен файл %newpath с id %fid', $vars));
        }
      } else {
        cLog::Warning(cT::t('Файл %image не удалось скопировать, import.xml он задан и в images есть', $vars));
      }
      
      if ( !$result ) {
        throw new ErrorException(cT::t('Картинка %image не сохранена в базу для %name / %id', $vars));
      }
      
    } else {
      //картинки в материале нет, ок
      $result  = TRUE;
    }
    
    return $result;
  }
  
  
  public function getTagsIds(cProduct $product) {
    $tagIds = array();
    $props = &$product->propertysValues;
    $tagString = $this->getValue($props, self::$tagPropName);
    if ( $tagString ) {
      $vars = array(
      	'%voc'=> self::$tagPropName,
      	'%product' => $product->name,
        '%cid' => $product->id, 
      );
      
      $tagShortNames = explode(',', $tagString);
      foreach ($tagShortNames as $tagShortName) {
        $tagShortName = trim($tagShortName);
        $tagLongName = $this->getLongTagNameByShort($tagShortName);
        $vars['%shortterm'] = $tagShortName;
        if (!$tagLongName) {
          cLog::Warning(cT::t('Для словаря %voc не найдено полное имя тега по короткому имени: %shortterm (товар %product / %cid)', $vars));
          continue;
        }
        $vars['%term'] = $tagLongName;
        
        $tagId = cDrupalTaxonomy::getTagIdByName($tagLongName, $this->tagVid);
        if ( !$tagId ) {
          $tagId = cDrupalTaxonomy::addTag($tagLongName, $this->tagVid);
          
          if ( !$tagId ) {
            throw new LogicException(cT::t('В словаре %voc не найден термин %term', $vars));
          } else {
            $vars['%tid'] = $tagId;
            cDrupalTaxonomy::updateTag($tagId, $tagLongName, 0, $this->tagVid);
            cLog::Warning(cT::t('В словарь %voc добавлен термин %term / %tid, иерархию в drupal придется выставлять вручную', $vars));
          }
        }
        cDrupalTaxonomy::updateSym($tagId, $tagLongName);
        
        $tagIds[] = $tagId;
      }
    }
    
    return $tagIds;
  }
  
  public function getLongTagNameByShort($shortName) {
    return cConfig::get()->get('tagmap', $shortName); 
  }
  
  private function postUpdate($internalId, cProduct $product) {
    $result = 0;
    if ( empty($product->images) ) {
      $result++;
    } else {
      $result += (int)$this->reAddPictures($internalId, $product->images[ sizeof($product->images) - 1], $product->name);
    }
    $classificatorGroups = &$this->commerceInfo->classificator->groups;
    $tids = array();
    foreach ( $product->groups as $cid ) {
      $tids[] = $classificatorGroups[$cid]['internalId'];
    }
    
    if ( !$tids ) {
      $tids = array();
    }
    $tags = $this->getTagsIds($product);
    if ($tags) {
      $tids = array_merge($tids, $tags);
    }
    cDrupalTaxonomy::updateTagsForNode($internalId, $tids);
    return $result;
  }
  
  public function updateProduct($internalId, cProduct $product) {
    if ( !$internalId ) {
      throw new ErrorException(cT::t('nid не найден для %name', array('%name' => $product->name)));
    }
    $updateCond = array('nid' => $internalId);
    $rows = $this->getDbRows($product, $internalId);
    $result = (int)cdb::get()->update(self::$nodeMainTable, $rows['main'], $updateCond );
    $result += (int)cdb::get()->update(self::$nodeRevisionsTable, $rows['revisions'], $updateCond );
    $result += (int)cdb::get()->update(self::$contentTable, $rows['content'], $updateCond );
    $result += (int)cdb::get()->update(self::$ecProductTable, $rows['ecproduct'], $updateCond );
    $this->postUpdate($internalId, $product);
    return $result > 0; 
  }
  
  public function addProduct(cProduct $product) {
    $result = 0;
    $time = time();
    if (empty($product->name)) {
      throw new ErrorException(cT::t('У товара отсутствует заголовок: %id', array('%id' => $product->id)));
    }
    $main = array(
      'type' => self::$nodeProductType,
      'title' => $product->name,
      'uid' => self::$defaultUid,
      'changed' => $time,
      'created' => $time
    );
    $lastId = cdb::get()->insert(self::$nodeMainTable, $main);
    if ( $lastId ) {
      $result ++;
      //update vid
      cdb::get()->update(self::$nodeMainTable, array('vid' => $lastId), array('nid' => $lastId) );
      
      $rows = $this->getDbRows($product, $lastId, TRUE);
      cdb::get()->insert(self::$nodeRevisionsTable, $rows['revisions']);
      cdb::get()->insert(self::$contentTable, $rows['content']);
      cdb::get()->insert(self::$ecProductTable, $rows['ecproduct']);
      $this->postUpdate($lastId, $product);
    } else {
      throw new ErrorException(cT::t('nid не найден для %name', array('%name' => $product->name)));
    }
    
    return $lastId; 
  }
  
  
  public function deleteUnused() {
    
    //Для удаляемых нод находим все их картинки и удаляем
    $query = 'SELECT f.`filepath` AS filepath FROM `' . self::$nodeMainTable . '` AS n 
    INNER JOIN `' . self::$filesTable . '` AS f ON f.`nid` = n.`nid`
    LEFT JOIN `' . self::$marksTable . '` AS m ON m.`id` = n.`nid`
    WHERE m.`id` IS NULL AND n.`type` = ?';
    
    $sth = cdb::get()->prepare($query);
    
    cLog::Debug(cT::t(
    	'Начинаем удалять картинки: %d', array(
    		'%d' => cDrupal5ProductConverter::$nodeProductType
    )));
    
    $sth->execute( array(cDrupal5ProductConverter::$nodeProductType) );
    
    while ( $filepath = $sth->fetchColumn() ) {
      $filepath = $this->drupalPath . $filepath;
      if ( file_exists($filepath) && unlink($filepath) ) {
        cLog::Notice(cT::t('Удален файл %file', array('%file' => $filepath)));
      } else {
        cLog::Warning(cT::t('Невозможно удалить старый файл %file - его не существует', array('%file' => $filepath)));
      } 
    }
    cLog::Debug(cT::t(
    	'Закончили удалять картинки: %d', array(
    		'%d' => cDrupal5ProductConverter::$nodeProductType
    )));
    
    //Удалить все свойства по-назначению, которых нет ни у одного товара 
    /*
    $query = '
    FROM `' . cDrupalTaxonomy::$termDataTable . '` AS d
    LEFT JOIN `' . cDrupalTaxonomy::$termHierarchyTable . '` AS h ON h.`tid` = d.`tid`
    LEFT JOIN `' . cDrupalTaxonomy::$termNodeTable . '` AS tn ON tn.`tid` = d.`tid`
    WHERE tn.`nid` IS NULL AND d.`vid` = ?';
    $sth = cdb::get()->prepare('SELECT d.`name` AS `name` ' . $query);
    
    cLog::Debug(cT::t(
    	'Начинаем удалять таксономию по-назначению: %d', array(
    		'%d' => $this->tagVid
    )));
    $sth->execute(array( $this->tagVid ));
    $deletedTidNames = $sth->fetchAll(PDO::FETCH_COLUMN);
    
    if ( $deletedTidNames ) {
      cLog::Notice(cT::t('Удалены следующие теги: %tags', array('%tags' => implode(', ', $deletedTidNames))));
    }
    
    $sth = cdb::get()->prepare('DELETE d, h, tn ' . $query);
    $sth->execute(array( $this->tagVid ));
    cLog::Debug(cT::t(
    	'Закончили удалять таксономию по-назначению: %d', array(
    		'%d' => $this->tagVid
    )));
    */
    
    cLog::Debug(cT::t('Начинаем удалять из всех таблиц товары, которые не были импортированы из xml (полная выгрузка)'));
    
    //Удаляем из всех таблиц товары, которые не были импортированы из xml (полная выгрузка) 
    $query = 'DELETE n, r, tn, c, p, f, e, map
    FROM `' . self::$nodeMainTable . '` AS n
    INNER JOIN `' . self::$nodeRevisionsTable . '` AS r ON r.`nid` = n.`nid`
    INNER JOIN `' . self::$ecProductTable . '` AS e ON e.`vid` = n.`vid`
    INNER JOIN `' . self::$contentTable . '` AS c ON c.`vid` = n.`vid`
    LEFT JOIN `' . self::$pictureTable . '` AS p ON p.`vid` = n.`vid`
    LEFT JOIN `' . cDrupalTaxonomy::$termNodeTable . '` AS tn ON tn.`nid` = n.`nid`
    LEFT JOIN `' . self::$filesTable . '` AS f ON f.`nid` = n.`nid`
    LEFT JOIN `' . self::$marksTable . '` AS m ON m.`id` = n.`nid`
    LEFT JOIN `' . self::$mapTable . '` AS map ON map.`id` = n.`nid`
    WHERE m.`id` IS NULL AND n.`type` = ? AND n.`nid` != ?';
    $sth = cdb::get()->prepare($query);
    $sth->execute( array(cDrupal5ProductConverter::$nodeProductType, self::$cdNid) );
    $r = $sth->rowCount();
    cLog::Debug(cT::t(
    'Закончили удалять из всех таблиц товары, которые не были импортированы из xml (полная выгрузка): %ar',
    array('%ar' => $r)
    ));
    
    return $r;
  }
  
  public function begin() {
    $this->tagVid = cDrupalTaxonomy::getVocIdByName(self::$tagPropName);
    if ( !$this->tagVid ) { 
      throw new ErrorException(cT::t('Не найден словарь %voc', array('%voc' => self::$tagPropName)));
    }
    parent::begin();
    
  }
  
  public function end() {
    parent::end();
    cDrupal5Fixers::fixAll();
  }

}

class cDrupal5OrdersConverter extends cGenericOrdersConverter {
  private $drupalPath;
  private $currencyMul;
  public function __construct() {
    parent::__construct();
    $c = cConfig::get();
    $this->drupalPath = $c->get('path', 'filesRootReal') . '/';
    $file = $this->drupalPath . $c->get('drupalPath', 'currencyFile');
    if (!file_exists($file)) {
      throw new ErrorException(cT::t('Файл с курсом валют не найден: %file', array('%file' => $file) ));
    }
    $data = file($file);
    $this->currency = array();
    foreach ($data as $str) {
      $parts = explode('=', $str);
      $val = (float)trim($parts[1]);
      $this->currencyMul[trim($parts[0])] = $val;
    }
    if (! $this->currencyMul ) {
      throw new ErrorException(cT::t('Невозможно загрузить курсы валют: %file', array('%file' => $file) ));
    }
  }
  
  public function setPrice($internalId, $price, $currency) {
    $updateCond = array('nid' => $internalId);
    $mul = isset($this->currencyMul[$currency]) ? $this->currencyMul[$currency] : 1.0;
    $price = $price * $mul;
    $result = (bool)cdb::get()->update(cDrupal5ProductConverter::$ecProductTable, array('price' => $price), $updateCond);
    return $result;
  }
}

