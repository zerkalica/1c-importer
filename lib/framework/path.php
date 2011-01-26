<?php

/**
 * методы для работы с путями и url
 * @author nexor
 *
 */
class cPath {
  /**
   * корневой url без названия сайта
   * @var string
   */
  static private $rootUrl;
  /**
   * Url корня движка (с учетом подпапок)
   * @var string
   */  
  static private $rootUrlAbsolute;
  
  /**
   * Протокол, по которому запущен скрипт
   * http или https 
   * @var string
   */
  static private $Scheme;
  
  static private $rootPathAbsolute;
  
  /**
   * 
   * Инициализация cPath
   * @param string $file_const константа __FILE__ , из базового контроллера
   */
  static public function init($file_const) {
    self::$rootPathAbsolute = dirname($file_const) . '/';
    self::$Scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
    if( isset ($_SERVER['HTTP_HOST']) ) { 
      self::$rootUrlAbsolute = self::$Scheme . '://' . $_SERVER['HTTP_HOST'];
    } else {
      self::$rootUrlAbsolute = '';
    }
    self::$rootUrl = '/';
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/');
    if ($dir) {
      $dir .= '/';
      //Скрипт запущен не из корня сайта, поэтому добавляем его в базовый путь и базовый url
      self::$rootUrlAbsolute .= $dir;
      self::$rootUrl = $dir;
    } else {
      self::$rootUrlAbsolute = rtrim(self::$rootUrlAbsolute, '\/') . '/';
    }
  }
  /**
   * Удаляет часть пути до корня движка
   * @param string $path
   */
  static public function removeAbsolute($path) {
    if ( strpos($path, self::$rootPathAbsolute) === 0 ) {
      $path = substr($path, strlen(self::$rootPathAbsolute));
    }
    return $path;
  }

  /**
   * Получить абсолютный url корня движка
   * @return string url
   */
  static public function getRootUrlAbsolute()  {
    return self::$rootUrlAbsolute;
  }
  
  /**
   * Получить относительный путь к корню движка
   * @return string path
   */
  static public function getRootUrl()  {
    return self::$rootUrl;
  }
  
  /**
   * Получить используемый тип протокола (http или https)
   * @return string scheme
   */
  static public function getScheme()  {
    return self::$Scheme;
  }
  
  /**
   * Создает урл с параметрами, который вызывает основной контроллер (index.php)
   * @param string $action тип действия
   * @param array $params, назвазние параметра => значение
   * @return string url
   */
  static public function makeActionUrl($action = '', $params = array()) {
    //В случае запроса индексной страницы без параметров, action не нужен
    if($action && $action != 'index') {
      //Добавляем action-контроллер в начало
      $params = array('action' => $action) + $params;
    }
    return self::makeUrl(self::getRootUrl(), $params);
  }
  
  /**
   * Преобразует ассоциативный массив key => value в строку 
   * url параметов ?key1=value&key2=value и т.д.
   * @param array $params
   * @return string
   */
  static public function makeUrl($url, array $params = NULL) {
    $paramStr = '';
    $sep = '';
    if ( $params ) {
      $paramStr = http_build_query($params);
      $sep = str_pos($url, '?') === FALSE ? '&' : '?';
    }
    return $url . $sep . $paramStr;
  }
  
  /**
   * Склеить путь и имя файла
   * @param string $path
   * @param string $file
   * @return string полный путь
   */
  static public function makePath($path, $file = '') {
    return $path . '/' . $file;
  }
    
  /**
   * Получить абсолютный путь к корню движка
   * @return string путь
   */
  static public function getPrefix() {
    return self::$rootPathAbsolute;
  }
  
  /**
   * Переход по указанному url
   * если url не указан, то произойдет переход на главную страницу
   * @param string $url
   * @param int $http_response_code код состояние запроса HTML 
   */
  static public function go($url = '', $http_response_code  = 302) {
    if(!$url) {
      $url = cPath::getRootUrlAbsolute();
    }
    cTemplate::saveState();
    header('Location: '. $url, TRUE, $http_response_code);
    exit();
  }
  
}
