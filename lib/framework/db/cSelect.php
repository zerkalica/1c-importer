<?php

/**
 * object sql pattern
 * @example
 * $select = new cSelect('users', array('id', 'name'));<br/>
 * $select->selectCols('COUNT(`posts`.*) AS `post_count`')<br/>
 * 	->join('posts', '`posts`.`id` = `users`.`id`')<br/>
 * 	->where('`posts`.`date` > NOW() OR `posts`.status > ?', array(1) )<br/>
 * 	->order('date DESC');<br/>
 * @author nexor
 */
class cSelect {
  /**
   * FROM clause spec
   *
   * @var string
   */
  private $from;
  /**
   * @var array of string
   */
  private $select;
  private $delete = array();
  private $join = array();
  private $where = array();
  private $order = array();
  private $group = array();
  private $having = array();
  /**
   * @var array
   */
  private $bind = array();

  /**
   * @var int
   */
  private $limit;
  private $offset;
  
  private $countExpression = '*';
  
  /**
   * @var PDO
   */
  private $db;
  
  /**
   * @var Pager
   */
  public $pager;

  function __construct(PDO $pdo, Pager $pager = NULL) {
    $this->pdo = $pdo;
    $this->pager = $pager;
  }
  /**
   * Specify FROM
   * @param string $from
   * @return cSelect
   */
  public function from($from) {
    $this->from = $from;
    return $this;
  }
  /**
   * @param string
   * @return cSelect
   */
  public function select($expression) {
    $this->select = $expression;
    return $this;
  }
  
  /**
   * @param string
   * @return cSelect
   */
  public function delete($expression) {
    $this->delete = $expression;
    return $this;
  }
  
  /**
   * @param string
   * @return cSelect
   */
  public function setCountExpression($expression) {
    $this->countExpression = $expression;
    return $this;
  }

  /**
   * sets limit and offset in query
   * @param int $limit
   * @param int $offset
   * @return cSelect
   */
  public function setLimit($limit, $offset = 0) {
    $this->limit = $limit;
    $this->offset = $offset;
    return $this;
  }

  /**
   * calculate values from page number and size
   * and sets offset and limit
   * @param int $pageNumber
   * @param int $pageSize
   * @return cSelect
   */
  public function setPaginate($pageNumber, $pageSize) {
    return $this->setLimit($pageSize, ($pageNumber - 1) * $pageSize);
  }

  /**
   * 
   * left join by raw condition (sql query part)
   * @param string $tableSrc
   * @param string $cond
   * @param bool $left internal use
   * @return cSelect 
   */
  public function join($sql, $left = TRUE) {
    $this->join[] =  'LEFT JOIN ' . $sql;
    return $this;
  }
  
  public function innerJoin($sql) {
    $this->join[] = 'INNER JOIN ' . $sql;
    return $this;
  }
  
  /**
   * Specify a WHERE condition
   * @param string $sql sql query
   * @param array $params bind params
   * @return cSelect
   */
  public function where($sql, array $params = array()) {
    $this->where[] = $sql;
    $this->bind = array_merge($this->bind, $params);
    return $this;
  }
  
	/**
   * Specify a HAVING clause
   *
   * @param string $sql
   * @param array $params
   * @return cSelect
   */
  function having($sql, array $params = array() ) {
    $this->having[] = $sql;
    $this->bind = array_merge($this->bind, $params);
    return $this;
  }
  
  /**
   * Specify a GROUP BY clause
   * @param string | array $expression
   * @return cSelect
   */
  function group($expression) {
    $this->group = array_merge($this->group, (array) $expression);
    return $this;
  }
  
  /**
   * Specify ORDER BY clause
   * @param string | array $expression
   * @return cSelect
   */
  function order($expression) {
    $this->order = array_merge($this->order, (array) $expression);
    return $this;
  }

  private function sqlMake($selStr, $where) {
    $sql = 'SELECT ' . $selStr . '
    FROM `' . $this->from . '`';
    if ( $this->join ) {
      $sql .= "\n". implode("\n", $this->join);
    }
    if ( $where ) {
      $sql .= "\n". implode("\n", $where);
    }
    return $sql;
  }

  /**
   * make count query string for pager ops
   * @return string sql count query
   */
  public function countsql() {
    return $this->sqlMake( 'COUNT(' . $this->countExpression . ') AS `count`', array_merge($this->where, $this->having));
  } 

  /**
   * make query string with placeholders
   * @param string $cols
   * @param bool $count whether we should build a COUNT query
   * @return string select sql query
   */
  public function sql() {
    $sql = $this->sqlMake( ($this->cols ? implode(', ', $this->cols) : '*'), $this->where);
    $sql .= cSqlHelper::makeGroup($this->group);
    $sql .= cSqlHelper::makeHaving($this->having);
    $sql .= cSqlHelper::makeOrder($this->order);
    $sql .= cSqlHelper::makeLimit($this->limit, $this->offset);
    return $sql;
  }
  
  public function execute() {
    if ( $this->pager ) {
      $count = $this->queryCount();
      $pager->setCount($count);
      $this->setPaginate($pager->getNumber(), $pager->getSize());
    } else {
      $count = TRUE;
    }
    $sth = $this->db->prepare( $this->sql() );
    if ($count) {
      $sth->execute($this->bind);
    }
    return $sth;
  }
  
  public function queryCount() {
    $sth = $this->db->prepare( $this->countSql() );
    $sth->execute($this->bind);
    return $sth->fetchColumn();
  }

}

/**
 * $select = new cSelect(cdb::get('db1'), new Pager(1));
 * 
 * $select->from('`users` u')
 * ->select( u.`name`, u.`nick`, u.`password`)
 * ->join('`topics` t ON t.`uid` = u.`uid`')
 * ->where('u.`nick` = ?', array('test'));
 * 
 * $rows = $select->execute();
 * $table = new tplTable($rows, $select->pager);
 * $page->setContent($table);
 * 
 */

