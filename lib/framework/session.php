<?php
/**
 * Менеджер сессий, позволяет организовать 
 * раздельный доступ каждого модуля фреймворка к своим настройках
 * @author nexor
 *
 */
class cSession {

  /**
   * Инициализирут сессию, должен вызываться перед выводом в браузер
   */
  static public function init() {
    session_start();
  }
  
  /**
   * Конструктор, вызывается в использующем cSession модуле 
   * @param string $key идентификатор сесссии для модуля
   */
  public function __construct($key) {
    $this->class= $key;
  }
  
  /**
   * Получает значение сессии для модуля по ключу
   * @param string $key ключ, если не задан - результатом будет 
   * весь массив переменных модуля
   * @return mixed данные сессии для модуля
   */
  public function get($key = '') {
    if (isset($_SESSION[$this->class])) {
      if(!$key) {
        return $_SESSION[$this->class];
      } else if (isset($_SESSION[$this->class][$key])) {
        return $_SESSION[$this->class][$key];
      }
    }
    return NULL;
  }
  
  /**
   * Записывает в сессию данные по ключу
   * @param string $key ключ
   * @param mixed $data данные для установки
   */
  public function set($key, $data) {
    $_SESSION[$this->class][$key] = $data; 
  }

  /**
   * Удаляет данные сессии для модуля по ключу 
   * или все данные модуля, если ключ не задан
   * @param string $key
   */
  public function del($key = '') {
    if ( $key ) {
      unset($_SESSION[$this->class][$key]);
    } else {
      unset($_SESSION[$this->class]);
    } 
  }

}
