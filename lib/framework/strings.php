<?php

class cStrings {
  /**
   * Обезопасить строку, полученную из формы для html
   * @param string $string
   * @return strign
   */
  static public function safeString($string) {
    return htmlspecialchars($string, ENT_QUOTES, cT::getCharset());
  }
  
  /**
   * Костыль, используется если кодировка сайта не utf-8,
   * что не рекомендуется
   * @param array $var
   */
  static private function json_fix_cyr($var, $cp_from, $cp_to) {
    if (is_array($var)) {
      $new = array();
      foreach ($var as $k => $v) {
        $new[json_fix_cyr($k)] = json_fix_cyr($v);
      }
      $var = $new;
    } elseif (is_object($var)) {
      $vars = get_object_vars($var);
      foreach ($vars as $m => $v) {
        $var->$m = json_fix_cyr($v, $cp_from, $cp_to);
      }
    } elseif (is_string($var)) {
      $var = iconv($cp_from, $cp_to, $var);
    }
    return $var;
  }

  /**
   * Дает возможность использовать json_encode в кодировках, отличных от utf-8
   * на костыльных сайтах
   * @param array $data
   */
  static public function json_encode( $data, $options = NULL ) {
    if (! self::$isUtf) {
      $data = self::json_fix_cyr($data, cT::getCharset(), 'utf-8');
    }
    return json_encode( $data, $options );
  }

  static public function json_decode( $data, $assoc = NULL, $depth = NULL ) {
    if (! self::$isUtf) {
      $data = self::json_fix_cyr($data, 'utf-8', cT::getCharset());
    }
    return json_decode( $data, $assoc, $depth );
  }
  
}
