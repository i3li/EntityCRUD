<?php

namespace EntityCRUD;

/**
* EntityCRUDService is a class that provides create, read, update and delete functionalities for a specific table.
* It only supports MySQL.
*/
class EntityCRUDService {

  private $db;
  private $tableName;
  private $preparedStatements = array();

  function __construct(string $tableName, \mysqli $db) {
    $this->db = $db;
    $this->tableName = $tableName;
  }

  function __destruct() {
    foreach ($this->preparedStatements as $stmt) { $stmt->close(); }
  }

  // Helpers:-

  /**
  * @param  mysqli_stmt $stmt The prepared statement
  * @param  array       $map
  */
  private function bindParametersFromMap(\mysqli_stmt $stmt, array $map) {
    $parameterTypes = implode('', array_map(function($element) {
      return $element[1];
    }, $map));
    $parameters = array();
    $values = array_column($map, 2);
    foreach ($values as $key => $value) { $parameters[$key] = &$values[$key]; }
    array_unshift($parameters, $parameterTypes);
    call_user_func_array(array($stmt, 'bind_param'), $parameters);
  }

  /**
  * @param  PrepareStatementParts $preparedStatement
  * @return mysqli_stmt
  */
  private function prepareAndBind(PrepareStatementParts $preparedStatement): \mysqli_stmt {
    $sql = $preparedStatement->getQueryPart();
    if (!$this->preparedStatements[$sql]) {
      $this->preparedStatements[$sql] = $this->db->prepare($sql);
    }
    $this->bindParametersFromMap($this->preparedStatements[$sql], $preparedStatement->getMap());
    return $this->preparedStatements[$sql];
  }

  // CRUD :-

  /**
  * Create a new record specified by the map.
  * @see PrepareStatementParts::__construct() The map structure.
  * @param  array  $map The record to be created represented as a map.
  * @return int|bool      returns the created record id or FALSE if an error occurred.
  */
  function create(array $map) {
    // Build the SQL stmt
    $table = $this->tableName;
    $columns = '(' . implode(',', array_column($map, 0)) . ')';
    $placeholders = '(' . rtrim(str_repeat('?,', count($map)), ',') . ')';
    $sql = 'INSERT INTO ' . $table . ' ' . $columns . ' VALUES ' . $placeholders;
    $stmt = $this->prepareAndBind(new PrepareStatementParts($sql, $map));
    return $stmt->execute() === true ? $stmt->insert_id : false;
  }

  /**
  * @param  array  $map The record to be created represented as a map.
  * @return int|bool      returns the created record id or FALSE if an error occurred.
  */
  /**
  * Read records.
  * @param  array  $columns An array of the columns to be retrieved. If the array is empty, all of them are retrieved. By default, this value is empty.
  * @param PrepareStatementParts $extra Pass this object to extend the SQL statement (e.g. you can filter or order the result). By default, all records are returned.
  * @return array          An array containing all records or NULL in case of failure.
  */
  function read(array $columns = array(), PrepareStatementParts $extra = NULL): array {
    $map = array();

    // Build the sql stmt
    $columnsStr = '';
    if (count($columns) == 0)  $columnsStr = '*';
    foreach ($columns as $column) {
      $columnsStr .= $column . ', ';
    }
    $columnsStr = rtrim($columnsStr, ', ');
    $table = $this->tableName;
    $sql = 'SELECT ' . $columnsStr . ' FROM ' . $table;

    if ($extra) {
      $sql .= ' ' . $extra->getQueryPart();
      $map = $extra->getMap();
    }
    $stmt = $this->prepareAndBind(new PrepareStatementParts($sql, $map));
    $rows = NULL;
    if ($stmt->execute()) {
      $result = $stmt->get_result();
      $rows = array();
      while ($row = $result->fetch_assoc()) {
        array_push($rows, $row);
      }
    }
    return $rows;
  }

  /**
  * Update records.
  * @see PrepareStatementParts::__construct() The map structure.
  * @param  array  $map   The new data.
  * @param  PrepareStatementParts $extra Pass this object to extend the SQL statement (e.g. you can filter the result using WHERE clause). By default, all records are updated.
  * @return bool
  */
  function update(array $map, PrepareStatementParts $extra = NULL): bool {
    // Build the sql stmt
    $table = $this->tableName;
    $sql = 'UPDATE ' . $table . ' SET ';
    foreach (array_column($map, 0) as $column) { $sql .= $column . ' = ?, '; }
    $sql = rtrim($sql, ', ');

    if ($extra) {
      $sql .= ' ' . $extra->getQueryPart();
      $map = array_merge($map, $extra->getMap());
    }
    $stmt = $this->prepareAndBind(new PrepareStatementParts($sql, $map));
    return $stmt->execute();
  }

  /**
  * Delete records.
  * @see PrepareStatementParts::__construct() The map structure.
  * @param  PrepareStatementParts $extra Pass this object to extend the SQL statement (e.g. you can filter the result using WHERE clause). By default, all records are deleted.
  * @return bool
  */
  function delete(PrepareStatementParts $extra = NULL): bool {
    $map = array();

    // Build the sql stmt
    $table = $this->tableName;
    $sql = 'DELETE FROM ' . $table;

    if ($extra) {
      $sql .= ' ' . $extra->getQueryPart();
      $map = $extra->getMap();
    }
    $stmt = $this->prepareAndBind(new PrepareStatementParts($sql, $map));
    return $stmt->execute();
  }

}

?>
