<?php

/**
 *
 * Простой шаблонизатор на PHP
 * @author nexor
 * @uses cLog
 * @package cT
 *
 */
class cTemplate {
  /**
   * @var string путь к шаблонам
   */
  static private $root;
  /**
   *
   * Блоки для шаблона
   * @var array
   */
  static private $blocks = array();

  /**
   * по завершению скрипта done не покажет ничего, если значение этой переменной = TRUE
   * в противном случае произошла критическая ошибка и скрипт не дошел до выполнения page_show
   * значит надо сгенерировать страницу с сообщением об ошибке
   * @var bool
   */
  static private $page_showed = FALSE;

  /**
   * Шаблон, который будет использовать page_show по-умолчанию
   * @var string
   */
  static private $template;

  /**
   * @var cSession
   */
  static private $session;

  /**
   * @param string $root путь к шаблонам относительно cPath::getRootUrl()
   */
  static public function init($root) {
    self::$session = new cSession(__CLASS__);
    self::$root = $root;
    self::$blocks = array();
    self::$page_showed = FALSE;
    self::setpageTemplate('page');
    self::set_defaults();
    self::addCss('main');
    cLog::setUseTemplate(TRUE);
  }

  /**
   * Эти блоки выводятся в шаблоне основной страницы,
   * там нет проверки на существование переменной,
   * поэтому создадим предварительно пустые
   * @return array
   */
  static private function getDefaultBlocks() {
    return array('header', 'footer', 'content', 'errors', 'messages', 'js', 'css', 'js_init');
  }
  /**
   * Переменные блоков, которые сохраняем в сессии
   * @return array
   */
  static private function getSavedBlocks() {
    return array( 'messages' );
  }

  /**
   * установить начальные значениия, блоков, которые будут использоваться на основной странице
   */
  static private function set_defaults() {
    self::restoreState();
    foreach( self::getDefaultBlocks() as $key) {
      if( !isset(self::$blocks[$key]) ) {
        self::$blocks[$key] = array();
      }
    }
    self::setTitle(__CLASS__);
    self::setLang(cT::getLang());
    self::setCharset( cT::getCharset() );
  }

  /**
   * @return string полный путь к шаблонам
   */
  static public function getRootPath() {
    return cPath::getPrefix() . self::$root;
  }

  /**
   * Рендерит html шаблон
   * @param string $theme_pathname путь к файлу шаблона
   * @param array $theme_block_vars массив ключ=>значение, содержит переменные для шаблона
   * @return string html код шаблона
   */
  static private function render_file($theme_pathname, $theme_block_vars=NULL) {
    if ( !file_exists($theme_pathname) ) {
      return cT::t('Файл шаблона %filename не найден', array('%filename' => $theme_pathname));
    }

    /**
     * если заданы переменные для блока шаблона, выводим их в локальную область видимости,
     * что б в шаблоне можно было ссылаться на $var, а не на $theme_block_vars['var']
     */
    if($theme_block_vars) {
      /**
       * Используем EXTR_SKIP, что бы не перезатереть локальные переменные
       */
      extract($theme_block_vars, EXTR_SKIP);
    }
    //Перехватываем вывод шаблона
    ob_start();
    include($theme_pathname);
    // Помещаем в переменную
    $contents = ob_get_contents();
    ob_end_clean();
    return $contents;
  }

  /**
   * render block
   * @param string $block block name
   * @param array $vars block vars
   * @return strign rendered html
   */
  static public function render($block, $vars) {
    cLog::setUseTemplate(FALSE);
    $data = self::render_file( cPath::makePath(self::getRootPath(), $block) . '.tpl.php', $vars);
    cLog::setUseTemplate(TRUE);
    return $data;
  }

  /**
   * Добавляет в блок переменную для дальнейшего использования в основном шаблоне, который рендерит страницу в конце
   * @param string $key имя блока
   * @param mixed $vars строка или массив строк
   */
  static public function add($key, $vars) {
    if ( !isset(self::$blocks[$key]) ) {
      self::$blocks[$key] = array();
    }
    self::$blocks[$key][] = $vars;
  }

  /**
   * Устанавливает содержимое блока для дальнейшего использования в основном шаблоне
   * Вызывается один раз за сессию для каждого $key
   * @param string $key имя блока
   * @param string $data строка которая будет доступна в основном шаблоне через имя переменной $key
   */
  static public function set($key, $data) {
    self::$blocks[$key] = $data;
  }

  /**
   * Добавляет сообщение на страницу
   * @param string class
   * @param string $msg
   */
  static private function addMessageClass($class, $msg) {
    self::add('messages', array( 'attributes' => array('class'=> $class), 'value' => $msg) );
  }

  static public function addMessage($msg) {
    self::addMessageClass('message', $msg);
  }
  static public function addWarning($msg) {
    self::addMessageClass('warning', $msg);
  }
  static public function addError($msg) {
    self::addMessageClass('error', $msg);
  }
  static public function addDebug($msg) {
    self::addMessageClass('debug', $msg);
  }

  static public function addContent($data, $attributes = NULL) {
    self::add('content', array('data'=> $data, 'attributes' => $attributes) );
  }

  static public function setTitle($title) {
    self::set('title', $title);
  }
  static public function setLang($lang) {
    self::set('lang', $lang);
  }
  static public function setCharset($charset) {
    self::set('charset', $charset);
  }

  /**
   * Рендерит ранее добавленные переменные в блоки, для дальнейшего вывода в основном шаблоне
   * @param string $key_suffix имя шаблона основной страницы,
   * что бы можно было для разных типов страниц брать разные шаблоны
   * если $key_suffix == 'page', то это аналогично $key_suffix == ''
   * @return array имя блока => html код в строке
   */
  static private function prepareVariables($prefix) {
    $rendered_blocks = array();
    if ($prefix == 'page') {
      $prefix = '';
    } else if ( $prefix ) {
      $prefix .= '/';
    }

    if ( self::$blocks ) {
      foreach ( self::$blocks as $key => $values ) {
        //Если переменные блока одна строка - то вывести ее как есть, если массив,
        //то выполнить рендеринг с ипользованием шаблона, которому доступен этот массив через values
        if ( !$values ) {
          $rendered_blocks[$key] = '';
        } else {
          if ( is_array($values) ) {
            $rendered_blocks[$key] = self::render( $prefix . $key, array( 'values' => $values) );
          } else {
            $rendered_blocks[$key] = $values;
          }
        }

      }
    }
    return $rendered_blocks;
  }

  /**
   * Рендерит основной шаблон
   * @return string html код страницы
   */
  static private function page_render() {
    $vars = self::prepareVariables(self::getpageTemplate());
    return self::render(self::getpageTemplate(), $vars);
  }

  /**
   * Рендерит и выводит на stdout основной шаблон
   */
  static public function page_show() {
    self::$page_showed = TRUE;
    print self::page_render();
  }

  /**
   * Устанавливает шаблон по-умолчанию для генерации страниц
   */
  static public function setpageTemplate($key) {
    self::$template = $key;
  }

  /**
   * Возвращает текущий шаблон по-умолчанию
   */
  static public function getpageTemplate() {
    return self::$template;
  }

  /**
   * Используется в shutdown-функции cLog
   * Когда происходит критическая ошибка, а страница еще не была показана, то этот метод покажет ее
   */
  static public function done() {
    if ( !self::$page_showed) {
      self::page_show();
    }
  }

  /**
   * Получить url к корню тем, (относительно self::getRootUrl()
   * @return string url к корню
   */
  static public function getRootUrl() {
    return cPath::getRootUrl() . self::$root;
  }

  /**
   * Добавить js на страницу
   * @param string $name имя скрипта без расширения: self::getRootUrl() . '/js/' . $name . '.js'
   * @param array $params массив параметров, которые будут переданы .init методу скрипта
   * array('название_параметра' => значение)
   * значения могут быть строкой или числом
   */
  static public function addJs($name, $params = array()) {
    static $added = array();
    if (!isset($added[$name])) {
      $added[$name] = TRUE;
      $src = self::getRootUrl() . '/js/' . $name . '.js';
      self::add('js', array('src'=>$src));
      self::add('js_init', array('params'=>$params, 'name'=>$name));
    }
  }

  /**
   * Добавить css на страницу
   * по-умолчанию всегда добавляется main.css
   * @param string $name имя css файла без расширения, который будет включен в html
   * @param string $media
   */
  static public function addCss($name, $media = 'all') {
    static $added = array();
    if (!isset($added[$name])) {
      $added[$name] = TRUE;
      $href = self::getRootUrl() . '/css/' . $name . '.css';
      self::add('css', array('media'=> $media, 'href'=> $href));
    }
  }


  /**
   * Сохранить сообщения в сессии,
   * используется если используется отправка данных из формы с последующим редиректом
   */
  static public function saveState() {
    foreach( self::getSavedBlocks() as $key ) {
      self::$session->set($key, self::$blocks[$key]);
    }
  }
  /**
   * Восстановить сообщения из сесссии
   */
  static public function restoreState() {
    self::$blocks = self::$session->get();
    self::$session->del();
  }

}
