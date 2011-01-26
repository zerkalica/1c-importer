<?php

class cInput {
  /**
   * Обертка над $_POST 
   * @param string key
   * @return $_POST[$key]
   */
  static public function getPost( $name ) {
    return isset( $_POST[$name] ) ? $_POST[$name] : NULL; 
  }
  /**
   * Обертка над $_GET 
   * @param string key
   * @return $_GET[$key]
   */
  static public function getGet( $name ) {
    return isset( $_GET[$name] ) ? $_GET[$name] : NULL; 
  }
  
  /**
   * Обертка над $_FILES 
   * @param string key
   * @return $_FILES[$key]
   */
  static public function getFiles($name) {
    return isset($_FILES[$name]) ? $_FILES[$name] : NULL;
  }
  
  /**
   * Возвращает действие, которое выполняется со скриптом
   * @return string action
   */
  static public function getAction() {
    return isset($_REQUEST['action']) ? $_REQUEST['action'] : FALSE;
  }
}
