<?php

namespace EntityCRUD;

/**
* PrepareStatementParts encapsulate the parts needed for preparing a sql statment.
*/
class PrepareStatementParts {

  private $queryPart;
  private $map;

  /**
  * Validate the map structure.
  * @param  array  $map [description]
  * @throws InvalidArgumentException if the passed map is not structured properly.
  */
  private function validateMap(array $map) {
    foreach ($map as $value) {
      // Validate the structure
      if (!is_array($value) || count($value) != 3 || array_keys($value) != array(0, 1, 2) || !is_string($value[0]) || !is_string($value[1]) || empty($value[0]) || strlen($value[1]) != 1) {
        throw new \InvalidArgumentException('Bad map structure');
      } else if (preg_match('/[^sid]/', $value[1], $matches)) { // Validate the passed types.
        throw new \InvalidArgumentException(($matches[0] == 'b' ? 'Unsupported' : 'Unknown') . ' type (' . $matches[0] . ')');
      }
    }
  }

  /**
   * @param string $queryPart
   * @param array  $map       A map is a 2D array. Each array inside the map must have three elements.
   * The first is the column name. The second is the expected type of the value (it colud be either an 'i' for integer, an 's' for string, or a 'd' for double).
   * The third is the value.
   * For example, this is a record that has two columns, [['name', 's', 'Ali'], ['age', 'i', '23']];
   */
  function __construct(string $queryPart, array $map) {
    $this->validateMap($map);
    $this->queryPart = $queryPart;
    $this->map = $map;
  }

  /**
  * Get the query part.
  *
  * @return string
  */
  public function getQueryPart(): string {
    return $this->queryPart;
  }

  /**
  * Get the query part.
  *
  * @return array
  */
  public function getMap(): array {
    return $this->map;
  }

}

?>
