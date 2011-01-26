<?php

/**
 * Статические методы для работы с get/post и локализацией
 *
 * @author nexor <nexor@ya.ru>
 * @version $Id$
 *
 */
class cT {
  static private $charset;
  /**
   * язык сайта
   * @var string
   */
  static private $lang;
  
  static public $locale = array();
  
  /**
			TRUE, если сайт в кодировке utf-8
			используется в обертках на json_encode/decode
   */
  static private $isUtf = FALSE;

  static public function init($charset = 'UTF-8', $lang = 'ru') {
    self::$charset = $charset;
    self::$lang = $lang;
    self::$isUtf = preg_match('#^utf\-8$#i', $charset); 
  }
  
 /**
   * Переводит текстовую строку
   * @param string $string - Шаблон сообщения
   * @param array $params - параметры для подсатновки в шаблон (ключ => значение)
   * @return string - переведенная строка с подстановленными значениями
   */
  static public function t( $string, $params = array() ) {
    $string  = self::getText($string);
    return $params ? str_replace(array_keys($params), array_values($params), $string ) : $string;
  }
  
  /**
   * Получить текущую кодировку сайта
   * @return string html-кодировка
   */
  static public function getCharset() {
    return self::$charset;
  }
  
	/**
   * Получить текущий язык сайта
   * @return string lang-язык
   */
  static public function getLang() {
    return self::$lang;
  }
  
  /**
   * Аналогична функции gettext, переводит строку
   * @param string строка
   * @return string переведенная строка
   */
  static public function getText($string) {
    return isset(self::$locale[$string]) ? self::$locale[$string] : $string;
  }
  
}
