<?php

/**
 * Инсталятор, который при запуске создает таблицы в базе из шаблонов в sql
 * @author nexor
 *
 */
abstract class cInstaller {
  
  /**
   * Возвращает индексный массив нимен таблиц для поиск шаблонов и создания
   * @return array
   */
  abstract protected function getCheckTables();
   
  /**
   * Создать таблицы из шаблонов в ./sql/$driverName/$tableName.sql
   * вообще этот метод должен вызываться в инсталяторе, но по ТЗ таблицы должны создаваться автоматически при запуске скрипта
   */
  public function installAction() {
    $table_root = cPath::getPrefix() . cPath::makePath('sql', cdb::get()->getDriverName());
    foreach ( $this->getCheckTables() as $table ) {
      $file_data = $this->loadTable( cPath::makePath($table_root, $table) . '.sql');
      cdb::get()->query($file_data);
    }
  }

  /**
   *
   * Читает из дампа данные для создания таблицы
   * @param string $filename путь+имя файла
   * @throws ErrorException если не может найти файл или прочитать его
   * @returns string $table_dump дамп таблицы
   */
  private function loadTable($filename) {
    if ( !file_exists($filename) ) {
      throw new ErrorException(
      cT::t('Не могу найти шаблон для создания таблиц БД: [$filename]', array('$filename' => $filename)));
    }
    $file_data = file_get_contents($filename);
    if ( !$file_data ) {
      throw new ErrorException( cT::t('Не могу прочитать содержимое файла: [$filename]', 
        array('$filename' => $filename)));
    }
    if ( cdb::get()->getdefaultCharset() != 'utf8' ) {
      //Пусть по стандарту дамп по-умолчанию создает таблицы в utf8 кодировке
      //cp1251 вообще не очень хорошо использовать, но если она установлена принудительно, то фиксим дамп
      $file_data = str_replace('=utf8;', '=' . cdb::get()->getdefaultCharset() . ';', $file_data);
    }
    return $file_data;
  }
}

