<?php


class cFormatters {
  static private function getSizesMap() {
    return array(
      '' => 1,
      'K' => 1024,
      'M' => 1048576, // 1024 * 1024
      'G' => 1073741824, // 1024 * 1024 * 1024
    );
  }

  /**
   * Т.к. в php.ini значения upload_max_filesize, post_max_size хранятся в суровом формате,
   * то что бы привести к нормальным байтам, которые можно потом передать в MAX_FILE_SIZE в форме,
   * приходится определять наличие суффиксов k, m, g в строке и умножать на соотвествующее число,
   * чтобы получить размер в байтах
   * @param string $size php.ini
   * @return int размер в байтах
   */
  static public function parse_size($size) {
    $suffixes = self::getSizesMap();
    if (preg_match('#([0-9]+)\s*(k|m|g)?(b?(ytes?)?)#i', $size, $match)) {
      return $match[1] * $suffixes[$match[2]];
    }
  }

  static public function formatSize($size , $suffix = 'K', $round = 1) {
    $suffixes = self::getSizesMap();
    $size = round($size / $suffixes[$suffix], $round);
    return cTemplate::render('formatsize', array('digit'=>$size, 'suffix'=>$suffix));
  }

  static public function attributes(array $attributes) {
    $attrsPrepared = array();
    if ( $attributes ) {
      foreach ( $attributes as $key => $value ) {
        $attrsPrepared[] = $key . '="' . $value . '"';
      }
    }
    return $attrsPrepared ? (' ' . implode(' ', $attrsPrepared)) : '';
  }
}

