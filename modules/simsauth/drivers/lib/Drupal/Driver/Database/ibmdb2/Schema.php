<?php

/**
 * @file
 * Contains \Drupal\Database\Driver\ibmdb2\Schema.
 */

namespace Drupal\Database\Driver\ibmdb2;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\SchemaException;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\Database\SchemaObjectDoesNotExistException;
use Drupal\Core\Database\Schema as DatabaseSchema;
use Drupal\Component\Utility\Unicode;

/**
 * @addtogroup schemaapi
 * @{
 */

/**
 * ibmdb2 implementation of \Drupal\Core\Database\Schema.
 */
class Schema extends DatabaseSchema {

  /**
   * Maximum length of a table comment in ibmdb2.
   */
  const COMMENT_MAX_TABLE = 60;

  /**
   * Maximum length of a column comment in ibmdb2.
   */
  const COMMENT_MAX_COLUMN = 255;

  /**
   * @var array
   *   List of ibmdb2 string types.
   */
  protected $ibmdb2StringTypes = array(
    'VARCHAR',
    'CHARCTER',
    'CLOB',
    'BINARY',
    'VARBINARY',
    'BLOB',
    'GRAPHIG',
    'VARGRAPHIC',
    'DBCLOB',
  );

  /**
   * Get information about the table and database name from the prefix.
   *
   * @return
   *   A keyed array with information about the database, table name and prefix.
   */
  protected function getPrefixInfo($table = 'default', $add_prefix = TRUE) {
    $info = array('prefix' => $this->connection->tablePrefix($table));
    if ($add_prefix) {
      $table = $info['prefix'] . $table;
    }
    if (($pos = strpos($table, '.')) !== FALSE) {
      $info['database'] = substr($table, 0, $pos);
      $info['table'] = substr($table, ++$pos);
    }
    else {
      $info['database'] = $this->connection->getConnectionOptions()['database'];
      $info['table'] = $table;
    }
    return $info;
  }

  /**
   * Build a condition to match a table name against a standard information_schema.
   *
   * ibmdb2 uses databases like schemas rather than catalogs so when we build
   * a condition to query the information_schema.tables, we set the default
   * database as the schema unless specified otherwise, and exclude table_catalog
   * from the condition criteria.
   */
  protected function buildTableNameCondition($table_name, $operator = '=', $add_prefix = TRUE) {
    $table_info = $this->getPrefixInfo($table_name, $add_prefix);

    $condition = new Condition('AND');
    $condition->condition('TABSCHEMA', $table_info['database']);
    $condition->condition('TABNAME', $table_info['table'], $operator);
    return $condition;
  }

  /**
   * Generate SQL to create a new table from a Drupal schema definition.
   *
   * @param $name
   *   The name of the table to create.
   * @param $table
   *   A Schema API table definition array.
   * @return
   *   An array of SQL statements to create the table.
   */
  protected function createTableSql($name, $table) {
    $info = $this->connection->getConnectionOptions();

    // Provide defaults if needed.
    $table += array(
      'ibmdb2_character_set' => 'UTF-8',
    );

    $sql = 'CREATE TABLE "' . $name . '" (' . "\n";

    // Add the SQL statement for each field.
    foreach ($table['fields'] as $field_name => $field) {
      $sql .= $this->createFieldSql($field_name, $this->processField($field)) . ", \n";
    }

    // Process keys & indexes.
    $keys = $this->createKeysSql($table);
    if (count($keys)) {
      $sql .= implode(", \n", $keys) . ", \n";
    }

    // Remove the last comma and space.
    $sql = substr($sql, 0, -3) . "\n) ";

    $sql .= ' USING CODESET ' . $table['ibmdb2_character_set'];
    // By default, ibmdb2 uses the default collation for new tables, which is ASCII
    // if you can using alt collate like IDENTITY_16BIT.
    if (!empty($info['collation'])) {
      $sql .= ' USING ALT_COLLATE ' . $info['collation'];
    }

    // Add table comment.
    if (!empty($table['description'])) {
      $sql .= ' REMARKS ' . $this->prepareComment($table['description'], self::COMMENT_MAX_TABLE);
    }

    return array($sql);
  }

  /**
   * Create an SQL string for a field to be used in table creation or alteration.
   *
   * Before passing a field out of a schema definition into this function it has
   * to be processed by _db_process_field().
   *
   * @param string $name
   *   Name of the field.
   * @param array $spec
   *   The field specification, as per the schema data structure format.
   */
  protected function createFieldSql($name, $spec) {
    $sql = "`" . $name . "` " . $spec['ibmdb2_type'];

    if (in_array($spec['ibmdb2_type'], $this->ibmdb2StringTypes)) {
      if (isset($spec['length'])) {
        $sql .= '(' . $spec['length'] . ')';
      }
      if (!empty($spec['binary'])) {
        $sql .= ' BINARY';
      }
      if (isset($spec['type']) && $spec['type'] == 'varchar_ascii') {
        $sql .= ' VARBINARY';
      }
    }
    elseif (isset($spec['precision']) && isset($spec['scale'])) {
      $sql .= '(' . $spec['precision'] . ', ' . $spec['scale'] . ')';
    }

    if (!empty($spec['unsigned'])) {
      $sql .= ' UNSIGNED';
    }

    if (isset($spec['not null'])) {
      if ($spec['not null']) {
        $sql .= ' NOT NULL';
      }
      else {
        $sql .= ' NULL';
      }
    }

    if (!empty($spec['auto_increment'])) {
      $sql .= ' GENERATED ALWAYS AS IDENTITY (START WITH 1 INCREMENT BY 1)';
    }

    // $spec['default'] can be NULL, so we explicitly check for the key here.
    if (array_key_exists('default', $spec)) {
      $sql .= ' DEFAULT ' . $this->escapeDefaultValue($spec['default']);
    }

    if (empty($spec['not null']) && !isset($spec['default'])) {
      $sql .= ' DEFAULT NULL';
    }

    // Add column comment.
    if (!empty($spec['description'])) {
      $sql .= ' REMARKS ' . $this->prepareComment($spec['description'], self::COMMENT_MAX_COLUMN);
    }

    return $sql;
  }

  /**
   * Set database-engine specific properties for a field.
   *
   * @param $field
   *   A field description array, as specified in the schema documentation.
   */
  protected function processField($field) {

    if (!isset($field['size'])) {
      $field['size'] = 'normal';
    }

    // Set the correct database-engine specific datatype.
    // In case one is already provided, force it to uppercase.
    if (isset($field['ibmdb2_type'])) {
      $field['ibmdb2_type'] = Unicode::strtoupper($field['ibmdb2_type']);
    }
    else {
      $map = $this->getFieldTypeMap();
      $field['ibmdb2_type'] = $map[$field['type'] . ':' . $field['size']];
    }

    if (isset($field['type']) && $field['type'] == 'serial') {
      $field['auto_increment'] = TRUE;
    }

    return $field;
  }

  public function getFieldTypeMap() {
    // Put :normal last so it gets preserved by array_flip. This makes
    // it much easier for modules (such as schema.module) to map
    // database types back into schema types.
    // $map does not use drupal_static as its value never changes.
    static $map = array(
      'varchar_ascii:normal' => 'VARBINARY',

      'varchar:normal'  => 'VARCHAR',
      'char:normal'     => 'CHARACTER',

      'text:tiny'       => 'VARCHAR',
      'text:small'      => 'VARCHAR',
      'text:medium'     => 'VARCHAR',
      'text:big'        => 'CLOB',
      'text:normal'     => 'CLOB',

      'serial:tiny'     => 'SMALLINT',
      'serial:small'    => 'SMALLINT',
      'serial:medium'   => 'INT',
      'serial:big'      => 'BIGINT',
      'serial:normal'   => 'INT',

      'int:tiny'        => 'SMALLINT',
      'int:small'       => 'SMALLINT',
      'int:medium'      => 'INTEGER',
      'int:big'         => 'BIGINT',
      'int:normal'      => 'INTEGER',

      'float:tiny'      => 'REAL',
      'float:small'     => 'REAL',
      'float:medium'    => 'REAL',
      'float:big'       => 'DOUBLE',
      'float:normal'    => 'REAL',

      'numeric:normal'  => 'DECIMAL',

      'blob:big'        => 'XML',
      'blob:normal'     => 'BLOB',
    );
    return $map;
  }

  protected function createKeysSql($spec) {
    $keys = array();

    if (!empty($spec['primary key'])) {
      $keys[] = 'PRIMARY KEY (' . $this->createKeySql($spec['primary key']) . ')';
    }
    if (!empty($spec['unique keys'])) {
      foreach ($spec['unique keys'] as $key => $fields) {
        $keys[] = 'UNIQUE KEY `' . $key . '` (' . $this->createKeySql($fields) . ')';
      }
    }
    if (!empty($spec['indexes'])) {
      $indexes = $this->getNormalizedIndexes($spec);
      foreach ($indexes as $index => $fields) {
        $keys[] = 'INDEX `' . $index . '` (' . $this->createKeySql($fields) . ')';
      }
    }

    return $keys;
  }

  /**
   * Gets normalized indexes from a table specification.
   *
   * Shortens indexes to 191 characters if they apply to utf8mb4-encoded
   * fields, in order to comply with the InnoDB index limitation of 756 bytes.
   *
   * @param array $spec
   *   The table specification.
   *
   * @return array
   *   List of shortened indexes.
   *
   * @throws \Drupal\Core\Database\SchemaException
   *   Thrown if field specification is missing.
   */
  protected function getNormalizedIndexes(array $spec) {
    $indexes = isset($spec['indexes']) ? $spec['indexes'] : [];
    foreach ($indexes as $index_name => $index_fields) {
      foreach ($index_fields as $index_key => $index_field) {
        // Get the name of the field from the index specification.
        $field_name = is_array($index_field) ? $index_field[0] : $index_field;
        // Check whether the field is defined in the table specification.
        if (isset($spec['fields'][$field_name])) {
          // Get the ibmdb2 type from the processed field.
          $ibmdb2_field = $this->processField($spec['fields'][$field_name]);
          if (in_array($ibmdb2_field['ibmdb2_type'], $this->ibmdb2StringTypes)) {
            // Check whether we need to shorten the index.
            if ((!isset($ibmdb2_field['type']) || $ibmdb2_field['type'] != 'varchar_ascii') && (!isset($ibmdb2_field['length']) || $ibmdb2_field['length'] > 191)) {
              // Limit the index length to 191 characters.
              $this->shortenIndex($indexes[$index_name][$index_key]);
            }
          }
        }
        else {
          throw new SchemaException("ibmdb2 needs the '$field_name' field specification in order to normalize the '$index_name' index");
        }
      }
    }
    return $indexes;
  }

  /**
   * Helper function for normalizeIndexes().
   *
   * Shortens an index to 191 characters.
   *
   * @param array $index
   *   The index array to be used in createKeySql.
   *
   * @see Drupal\Database\Driver\ibmdb2\Schema::createKeySql()
   * @see Drupal\Database\Driver\ibmdb2\Schema::normalizeIndexes()
   */
  protected function shortenIndex(&$index) {
    if (is_array($index)) {
      if ($index[1] > 191) {
        $index[1] = 191;
      }
    }
    else {
      $index = array($index, 191);
    }
  }

  protected function createKeySql($fields) {
    $return = array();
    foreach ($fields as $field) {
      if (is_array($field)) {
        $return[] = '`' . $field[0] . '`(' . $field[1] . ')';
      }
      else {
        $return[] = '`' . $field . '`';
      }
    }
    return implode(', ', $return);
  }

  public function renameTable($table, $new_name) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException(t("Cannot rename @table to @table_new: table @table doesn't exist.", array('@table' => $table, '@table_new' => $new_name)));
    }
    if ($this->tableExists($new_name)) {
      throw new SchemaObjectExistsException(t("Cannot rename @table to @table_new: table @table_new already exists.", array('@table' => $table, '@table_new' => $new_name)));
    }

    $info = $this->getPrefixInfo($new_name);
    return $this->connection->query('ALTER TABLE {' . $table . '} RENAME TO `' . $info['table'] . '`');
  }

  public function dropTable($table) {
    if (!$this->tableExists($table)) {
      return FALSE;
    }

    $this->connection->query('DROP TABLE {' . $table . '}');
    return TRUE;
  }

  public function addField($table, $field, $spec, $keys_new = array()) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException(t("Cannot add field @table.@field: table doesn't exist.", array('@field' => $field, '@table' => $table)));
    }
    if ($this->fieldExists($table, $field)) {
      throw new SchemaObjectExistsException(t("Cannot add field @table.@field: field already exists.", array('@field' => $field, '@table' => $table)));
    }

    $fixnull = FALSE;
    if (!empty($spec['not null']) && !isset($spec['default'])) {
      $fixnull = TRUE;
      $spec['not null'] = FALSE;
    }
    $query = 'ALTER TABLE {' . $table . '} ADD ';
    $query .= $this->createFieldSql($field, $this->processField($spec));
    if ($keys_sql = $this->createKeysSql($keys_new)) {
      $query .= ', ADD ' . implode(', ADD ', $keys_sql);
    }
    $this->connection->query($query);
    if (isset($spec['initial'])) {
      $this->connection->update($table)
        ->fields(array($field => $spec['initial']))
        ->execute();
    }
    if ($fixnull) {
      $spec['not null'] = TRUE;
      $this->changeField($table, $field, $field, $spec);
    }
  }

  public function dropField($table, $field) {
    if (!$this->fieldExists($table, $field)) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP `' . $field . '`');
    return TRUE;
  }

  public function fieldSetDefault($table, $field, $default) {
    if (!$this->fieldExists($table, $field)) {
      throw new SchemaObjectDoesNotExistException(t("Cannot set default value of field @table.@field: field doesn't exist.", array('@table' => $table, '@field' => $field)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ALTER COLUMN `' . $field . '` SET DEFAULT ' . $this->escapeDefaultValue($default));
  }

  public function fieldSetNoDefault($table, $field) {
    if (!$this->fieldExists($table, $field)) {
      throw new SchemaObjectDoesNotExistException(t("Cannot remove default value of field @table.@field: field doesn't exist.", array('@table' => $table, '@field' => $field)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ALTER COLUMN `' . $field . '` DROP DEFAULT');
  }

  public function indexExists($table, $name) {
    // Returns one row for each column in the index. Result is string or FALSE.
    // Details at http://dev.ibmdb2.com/doc/refman/5.0/en/show-index.html
    $row = $this->connection->query('SHOW INDEX FROM {' . $table . '} WHERE key_name = ' . $this->connection->quote($name))->fetchAssoc();
    return isset($row['Key_name']);
  }

  public function addPrimaryKey($table, $fields) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException(t("Cannot add primary key to table @table: table doesn't exist.", array('@table' => $table)));
    }
    if ($this->indexExists($table, 'PRIMARY')) {
      throw new SchemaObjectExistsException(t("Cannot add primary key to table @table: primary key already exists.", array('@table' => $table)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ADD PRIMARY KEY (' . $this->createKeySql($fields) . ')');
  }

  public function dropPrimaryKey($table) {
    if (!$this->indexExists($table, 'PRIMARY')) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP PRIMARY KEY');
    return TRUE;
  }

  public function addUniqueKey($table, $name, $fields) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException(t("Cannot add unique key @name to table @table: table doesn't exist.", array('@table' => $table, '@name' => $name)));
    }
    if ($this->indexExists($table, $name)) {
      throw new SchemaObjectExistsException(t("Cannot add unique key @name to table @table: unique key already exists.", array('@table' => $table, '@name' => $name)));
    }

    $this->connection->query('ALTER TABLE {' . $table . '} ADD UNIQUE KEY `' . $name . '` (' . $this->createKeySql($fields) . ')');
  }

  public function dropUniqueKey($table, $name) {
    if (!$this->indexExists($table, $name)) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP KEY `' . $name . '`');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex($table, $name, $fields, array $spec) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException(t("Cannot add index @name to table @table: table doesn't exist.", array('@table' => $table, '@name' => $name)));
    }
    if ($this->indexExists($table, $name)) {
      throw new SchemaObjectExistsException(t("Cannot add index @name to table @table: index already exists.", array('@table' => $table, '@name' => $name)));
    }

    $spec['indexes'][$name] = $fields;
    $indexes = $this->getNormalizedIndexes($spec);

    $this->connection->query('ALTER TABLE {' . $table . '} ADD INDEX `' . $name . '` (' . $this->createKeySql($indexes[$name]) . ')');
  }

  public function dropIndex($table, $name) {
    if (!$this->indexExists($table, $name)) {
      return FALSE;
    }

    $this->connection->query('ALTER TABLE {' . $table . '} DROP INDEX `' . $name . '`');
    return TRUE;
  }

  public function changeField($table, $field, $field_new, $spec, $keys_new = array()) {
    if (!$this->fieldExists($table, $field)) {
      throw new SchemaObjectDoesNotExistException(t("Cannot change the definition of field @table.@name: field doesn't exist.", array('@table' => $table, '@name' => $field)));
    }
    if (($field != $field_new) && $this->fieldExists($table, $field_new)) {
      throw new SchemaObjectExistsException(t("Cannot rename field @table.@name to @name_new: target field already exists.", array('@table' => $table, '@name' => $field, '@name_new' => $field_new)));
    }

    $sql = 'ALTER TABLE {' . $table . '} CHANGE `' . $field . '` ' . $this->createFieldSql($field_new, $this->processField($spec));
    if ($keys_sql = $this->createKeysSql($keys_new)) {
      $sql .= ', ADD ' . implode(', ADD ', $keys_sql);
    }
    $this->connection->query($sql);
  }

  public function prepareComment($comment, $length = NULL) {
    if (isset($length)) {
      $comment = Unicode::truncate($this->connection->prefixTables($comment), $length, TRUE, TRUE);
    }
    $comment = strtr($comment, array(';' => '.'));
    return $this->connection->quote($comment);
  }

  /**
   * Retrieve a table or column comment.
   */
  public function getComment($table, $column = NULL) {
    $condition = $this->buildTableNameCondition($table);
    if (isset($column)) {
      $condition->condition('REMARKS', $column);
      $condition->compile($this->connection, $this);
      return $this->connection->query("SELECT REMARKS FROM SYSCAT.COLUMNS WHERE " . (string) $condition, $condition->arguments())->fetchField();
    }
    $condition->compile($this->connection, $this);
    return $this->connection->query("SELECT REMARKS FROM SYSCAT.TABLES WHERE " . (string) $condition, $condition->arguments())->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  protected function buildTableNameCondition($table_name, $operator = '=', $add_prefix = TRUE) {
    $info = $this->connection->getConnectionOptions();

    $table_info = $this->getPrefixInfo($table_name, $add_prefix);

    $condition = new Condition('AND');
    $condition->condition('TABSCHEMA', $table_info['schema']);
    $condition->condition('TABNAME', $table_info['table'], $operator);
    return $condition;
  }

  /**
   * {@inheritdoc}
   */
  public function tableExists($table) {
    $condition = $this->buildTableNameCondition($table);
    $condition->compile($this->connection, $this);
    return (bool) $this->connection->query("SELECT 1 FROM SYSCAT.TABLES WHERE " . (string) $condition, $condition->arguments())->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function findTables($table_expression) {
    $condition = $this->buildTableNameCondition('%', 'LIKE');
    $condition->compile($this->connection, $this);

    $individually_prefixed_tables = $this->connection->getUnprefixedTablesMap();
    $default_prefix = $this->connection->tablePrefix();
    $default_prefix_length = strlen($default_prefix);
    $tables = [];
    $results = $this->connection->query("SELECT TABNAME FROM SYSCAT.TABLES WHERE " . (string) $condition, $condition->arguments());
    foreach ($results as $table) {
      if (isset($individually_prefixed_tables[$table->TABNAME])) {
        $prefix_length = strlen($this->connection->tablePrefix($individually_prefixed_tables[$table->TABNAME]));
      }
      elseif ($default_prefix && substr($table->TABNAME, 0, $default_prefix_length) !== $default_prefix) {
        continue;
      }
      else {
        $prefix_length = $default_prefix_length;
      }

      $unprefixed_table_name = substr($table->TABNAME, $prefix_length);

      if (!empty($unprefixed_table_name)) {
        $tables[$unprefixed_table_name] = $unprefixed_table_name;
      }
    }

    $table_expression = str_replace(array('%', '_'), array('.*?', '.'), preg_quote($table_expression, '/'));
    $tables = preg_grep('/^' . $table_expression . '$/i', $tables);

    return $tables;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldExists($table, $column) {
    $condition = $this->buildTableNameCondition($table);
    $condition->condition('COLNAME', $column);
    $condition->compile($this->connection, $this);
    return (bool) $this->connection->query("SELECT 1 FROM SYSCAT.COLUMNS WHERE " . (string) $condition, $condition->arguments())->fetchField();
  }

}

/**
 * @} End of "addtogroup schemaapi".
 */
