<?php

class cConverterInstaller extends cInstaller {
  protected function getCheckTables() {
    return array(
    'cgroupmarks',
    'cproductmarks',
    'cordersmarks',
    'cgroupmap',
    'cproductmap',
    );
  }
}
