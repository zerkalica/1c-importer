<?php
/**
 * 
 * Используется в обработчике исключений
 * в trigger_error это значение передавать нельзя,
 * используйте cLog::Debug()
 * @var int
 */
define('E_USER_DEBUG', E_USER_NOTICE + 100 );

/**
 * Стандартный обработчик ошибок, перехватывает throw, trigger_error
 * @author nexor <nexor@ya.ru>
 * @version $Id$
 * @uses cTemplate
 * @package cT
 *
 */
class cLog {
  const SHOW_SYSTEM_ERRORS = 1;
  const CLEAR_LOG = 2;
  const SHOW_DEBUG_ERRORS = 4;
  const MESSAGE_BRIEF = 8;
  const MESSAGE_FULL = 16;
  
  static private $olderrorHandler;
  static private $oldexceptionHandler;
  static private $skipClasses = array();
  static private $use_template = FALSE;
  /**
   * если TRUE, cLog::Debug будет выводить отладочные сообщения как E_USER_NOTICE 
   * @var bool
   */
  static private $debugLevel;
  /**
   * Если была ошибка, то эта переменная устанавливается в TRUE
   * @var bool
   */
  static private $isError = FALSE;
  
  static private $logEnable = FALSE;
  static private $logFile;
  static private $logDb;
  
  
  /**
   * Инициализация cLog 
   * @param int $debugLevel, TRUE, если нужно включить доп. отладочные сообщения
   */
  static public function init( $debugLevel = 1, $logFile = NULL, cDbDriverBase $logDb = NULL ) {
    if ( $logFile ) {
      self::$logFile = $logFile; 
    }
    if ( $logDb ) {
      self::$logDb = $logDb; 
    }
    if ( $logFile || $logDb ) {
      self::$logEnable = TRUE;
      self::logError(cT::t(
      	'Log started %class at %time / %url', array(
      		'%class' => __CLASS__, 
      		'%time'=>date('r'), 
      		'%url' => cPath::makeUrl(cPath::getRootUrlAbsolute(), $_GET), 
        ) ), 0, TRUE); 
    }
    error_reporting(0);
    ini_set('error_reporting', 0);  
    
    if ( ! self::$olderrorHandler ) {
      self::$debugLevel = $debugLevel;
      self::$olderrorHandler = set_error_handler(array(__CLASS__, 'errorHandler'));
      self::$oldexceptionHandler = set_exception_handler(array(__CLASS__, 'exceptionHandler'));
      self::$use_template = class_exists('cTemplate');
      self::addSkipClass(__CLASS__);
      register_shutdown_function(array(__CLASS__, 'shutdown') );
    }
  }
  
  /**
   * Отключить или включить вывод с использованием cTemplate
   * @param bool $state 
   */
  static public function setUseTemplate($state) {
    self::$use_template = $state;
  }
  
  /**
   * Карта кодов ошибок и их строковых расшифровок
   * @return array error_code => name 
   * 
   */
  static private function getErrorMap() {
    static $types;
    if ( !isset($types) ) {
      $types = array(
        E_ERROR => cT::t('Error'),
        E_WARNING => cT::t('Warning'),
        E_PARSE => cT::t('Parse error'),
        E_NOTICE => cT::t('Notice'),
        E_CORE_ERROR => cT::t('Core error'),
        E_CORE_WARNING => cT::t('Core warning'),
        E_COMPILE_ERROR => cT::t('Compile error'),
        E_COMPILE_WARNING => cT::t('Compile warning'),
        E_USER_ERROR => cT::t('User error'),
        E_USER_WARNING => cT::t('User warning'),
        E_USER_NOTICE => cT::t('User notice'),
        E_STRICT => cT::t('Strict warning'),
        E_RECOVERABLE_ERROR => cT::t('Recoverable fatal error'),
        E_USER_DEBUG => cT::t('Debug'),
      );
    }
    return $types;
  }
  /**
   * Добававляет класс, который не учитываем при выборке из backtrace стека
   * 
   * @param string $class имя класса
   */
  static public function addSkipClass( $class ) {
    self::$skipClasses[$class] = TRUE;
  }
  
  /**
   * Шаблоны для форматирования сообщений лога
   * @return array key = > template
   */
  static private function getErrorTemplates() {
    return array(
    	'full' => '%type: %message in %filename on line %line',
    	'brief' => '[%basename:%line] %message',
    	'mini' => '%message'
    );
  }
  
  /**
   * Если определяет по коду ошибки, критическая она или нет
   * @param int $errno код ошибки
   * @return bool TRUE, если не критическая
   */
  static private function isRecovarable( $errno ) {
    static $recoverable = array(
      E_WARNING,
      E_COMPILE_WARNING,
      E_CORE_WARNING,
      E_NOTICE,
      E_RECOVERABLE_ERROR,
      E_DEPRECATED,
      E_STRICT,
      E_USER_NOTICE,
      E_USER_WARNING,
      E_USER_DEPRECATED,
      E_USER_DEBUG,
    );
    return in_array($errno, $recoverable);
  }
  
  static private function isUser( $errno ) {
    static $recoverable = array(
      E_USER_NOTICE,
      E_USER_WARNING,
      E_USER_DEPRECATED,
      E_USER_DEBUG,
    );
    return in_array($errno, $recoverable);
  }
  
  /**
   * Определяет по номеру ошибки, нотис это или нет
   * @param int $errno
   * @return bool, TRUE, если нотис
   */
  static private function isNotice($errno) {
    return $errno == E_USER_NOTICE;
  }
  
  static private function isDebug($errno) {
    return $errno == E_USER_DEBUG;
  }
  

  /**
   * Стандартный обработчик ошибок php, обертка над обработчиком исключений
   * trigger_error вызовет именно его 
   * @param int $errno код ошибки
   * @param string $message сообщение
   * @param string $filename имя файла, в котором произошла ошибка
   * @param int $line строка в этом файле
   * @param array $context контекст
   * @throws ErrorException выпадет если критическая ошибка 
   */
  static public function errorHandler( $errno, $message, $filename, $line, $context) {
    $exception = new ErrorException($message, 0, $errno, $filename, $line);
    if ( self::isRecovarable($errno) ) {
      return self::exceptionHandler( $exception ); 
    } else {
      throw $exception;
    }    
  }
  
  /**
   * Стандартный обработчик всех исключений
   * 
   * @param Exception $exception
   */
  static public function exceptionHandler( Exception $exception ) {
    
//    if (error_reporting() == 0) {
//      return;
//    }
    
    if ( method_exists($exception, 'getSeverity') ) {
      $errno = $exception->getSeverity();
    } else {
      $errno = E_ERROR;
    }
    
    $message = $exception->getMessage();
    $backtrace = $exception->getTrace();
    if( ! self::isNotice($errno) ) {
      self::$isError = TRUE;
    }
    self::log($message, $errno, $backtrace, $exception->getFile(), $exception->getLine());
  }
  
  /**
   * return last backtrace element
   * @param array $backtrace
   * @return array bactrace element
   */
  static private function getTrace($backtrace) {
    if (!$backtrace) {
      $backtrace = debug_backtrace();
    }
    if (!$backtrace) {
      return NULL;
    }
    $index = 0;
    $backtrace = array_reverse($backtrace);
    foreach ($backtrace as $index => $item) {
      if ( isset($item['class']) && isset(self::$skipClasses[$item['class']]) ) {
        break;
      } 
    }
    return $backtrace[$index];
  }
  
  /**
   * Записывает сообщение в лог,
   * координаты откуда был вызван и номер ошибки
   * @param string $message
   * @param int $errno
   * @param array $backtrace дамп бэктрейса.
   * если не задан автоматически берется из debug_backtrace()
   * используется только в exceptionHandler
   */
  static private function log($message, $errno, $backtrace = NULL, $filename = NULL, $line = NULL) {
    $trace = self::getTrace($backtrace);
    if ( $trace && !empty($trace['file']) ) {
      $filename = $trace['file'];
      $line = $trace['line'];
    }
    $basename = str_replace('.php', '', cPath::removeAbsolute($filename));
    
    $key = (self::$debugLevel & self::MESSAGE_BRIEF) ? 'brief' : ( 
      (self::$debugLevel & self::MESSAGE_FULL) ? 'full' : 'mini'
    );
    $types = self::getErrorMap();
    
    $vars = array(
      '%type' => $types[$errno],
      '%message' => $message,
      '%filename' => $filename,
      '%basename' => $basename,
      '%line' => $line
    );
    
    $templates = self::getErrorTemplates();
    if ( (self::$debugLevel & self::SHOW_SYSTEM_ERRORS) || self::isUser($errno) ) {
      self::displayError(cT::t($templates[$key], $vars), $errno);
    }
    if ( self::$logEnable ) {
      self::logError(cT::t($templates['full'], $vars), $errno, FALSE, $vars);
    }
  }
  
  /**
   * Вывести ошибку в лог или на страницу
   * @param string $string сообщение
   * @param int $errno код ошибки
   */
  static private function displayError($string, $errno) {
    if (self::$use_template) {
      if ( self::isDebug($errno)) {
        cTemplate::addDebug($string);
      } else if(self::isNotice($errno)) {
        cTemplate::addMessage($string);
      } else if (self::isRecovarable($errno)) {
        cTemplate::addWarning($string);
      } else {
        cTemplate::addError($string);
      }
    } else {
      echo "$string<br/>\n";
    }
  }
  
  /**
   * add message to file or db log
   * @param string $string
   * @param int $errno
   * @param bool $clearLog, TRUE for start/end
   */
  static private function logError($string, $errno, $clearLog = FALSE, $vars = array()) {
    static $suffixes = array('.debug.txt', '.user.txt', '.system.txt');
    if ( self::$logFile ) {
      
      if (self::isDebug($errno)) {
        $logFile = self::$logFile . $suffixes[0];
      } else if (self::isUser($errno)) {
        $logFile = self::$logFile . $suffixes[1]; 
      } else {
        $logFile = self::$logFile . $suffixes[2];
      }
      
      if ($clearLog) {
        foreach ( $suffixes as $suffix ) {
          $filename = self::$logFile . $suffix;
          if ( file_exists($filename) ) {
            unlink($filename);
          }
        }
      }
      $string = $string . "\n";
      if ( $errno == 0 ) {
        foreach ( $suffixes as $suffix ) {
          $filename = self::$logFile . $suffix;
          error_log($string, 3, $filename);
        }
      } else {
        error_log($string, 3, $logFile);
      }
    }
    
    if ( self::$logDb ) {
      $logDb->insertLog($errno, $vars['message'], $vars['filename'], $vars['line']);
    } 
  }
  
  /**
   * Послать в лог нотис
   * @param string $string сообщение
   */
  static public function Notice($string) {
    return self::log($string, E_USER_NOTICE);
  }
  /**
   * Послать в лог предупреждение
   * @param string $string сообщение
   */
  static public function Warning($string) {
    return self::log($string, E_USER_WARNING);
  }
  
  /**
   * Послать отладочную строку в лог
   * если self::getDebug() установлен в TRUE
   * @param string $string сообщение
   */
  static public function Debug($string) {
    if (self::$debugLevel > 2) {
      self::log($string, E_USER_DEBUG);
    }
  }
  
  /**
   * Послать в лог ошибку
   * @param string $string сообщение
   */
  static public function Error($string) {
    return self::log($string, E_USER_ERROR);
  }
  
  /**
   * Если была ошибка или предупреждение, возвращает TRUE
   * @return bool
   */
  static public function isError() {
    return self::$isError;
  }
  
  /**
   * Статический деструктор
   * Восстанавливает обработчики ошибок на прежние
   */
  static private function done() {
    if ( self::$olderrorHandler ) {
      set_error_handler(self::$olderrorHandler);
      set_exception_handler(self::$oldexceptionHandler);
      self::$oldexceptionHandler = self::$olderrorHandler = NULL;
    }
    if ( self::$logEnable ) {
      self::logError(cT::t('Log ended %class at %time', array('%class' => __CLASS__, '%time'=>date('r')) ), 0); 
    } 
  }
  
  /**
   * Этот метод автоматически выполняется по завершении скрипта
   * Выводит страницу, если ранее она не была
   * выведена cTemplate::show_page 
   */
  public static function shutdown() {
    $error = error_get_last();
    if (isset($error['type']) ) {
        self::log($error['message'], $error['type'], NULL, $error['file'], $error['line']); 
    }
    if (self::$use_template) {
      cTemplate::done();
    }
    self::done();
  }
  
}
