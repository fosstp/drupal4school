<?php
/**
 * @file
 * Definition of Drupal\Driver\Database\ibmdb2\Connection
 */
namespace Drupal\Driver\Database\ibmdb2;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\TransactionCommitFailedException;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Component\Utility\Unicode;

/**
 * @addtogroup database
 */

/**
 * IBM DB2 implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends DatabaseConnection {

  /**
   * Constructs a Connection object.
   */
  public function __construct(\PDO $connection, array $connection_options = array()) {
    parent::__construct($connection, $connection_options);

    // This driver defaults to transaction support, except if explicitly passed FALSE.
    $this->transactionSupport = !isset($connection_options['transactions']) || ($connection_options['transactions'] !== FALSE);

    // MySQL never supports transactional DDL.
    $this->transactionalDDLSupport = FALSE;

    $this->connectionOptions = $connection_options;
  }

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'ibmdb2';
  }
  
  /**
   * {@inheritdoc}
   */
  public function databaseType() {
    return 'ibmdb2';
  }
  
  /**
   * SQLSTATE error code for "Syntax Error or Access Rule Violation".
   */
  const SQLSTATE_SYNTAX_ERROR = 42000;

  /**
   * {@inheritdoc}
   */
  public static function open(array &$connection_options = array()) {
    setlocale(LC_ALL, 'zh_TW');
    setlocale(LC_ALL, 'zh_TW.UTF-8');
    setlocale(LC_ALL, 'zh_TW.utf8');
    date_default_timezone_set('Asia/Taipei');

	if (!extension_loaded('PDO_IBM')) {
      throw new DatabaseNotFoundException('The PECL PDO_IBM library is not available.');
    }

    // Build the DSN
    $dsn = 'ibm:Driver={IBM DB2 ODBC DRIVER};Protocol=TCPIP;';
    foreach ($options as $key => $value) {
      $dsn .= (empty($key) ? '' : "$key=") . $value . ';';
    }
    // PDO Options are set at a connection level.
    // and apply to all statements.
    $connection_options['pdo'] = array();
    // Set proper error mode for all statements
    $connection_options['pdo'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
    // Actually instantiate the PDO.
    try {
      $pdo = new \PDO($dsn, $connection_options['uid'], $connection_options['pwd'], $connection_options['pdo']);
    }
    catch (\Exception $e) {
      throw $e;
    }
    return $pdo;
  }

  public function queryRange($query, $from, $count, array $args = array(), array $options = array()) {
    return $this->query($query . ' LIMIT ' . (int) $from . ', ' . (int) $count, $args, $options);
  }

  public function queryTemporary($query, array $args = array(), array $options = array()) {
    $tablename = $this->generateTemporaryTableName();
    $this->query('TEMPORARY ' . $tablename . ' DSN &DB..&TS..D&DATE ' . $query, $args, $options);
    return $tablename;
  }
  
  /**
   * {@inheritdoc}
   */
  public function quoteIdentifier($identifier) {
    return '"' . $identifier .'"';
  }
  /**
   * {@inheritdoc}
   */
  public function escapeField($field) {
    if (empty($field)) {
      return '';
    }
    return implode('.', array_map(array($this, 'quoteIdentifier'), explode('.', preg_replace('/[^A-Za-z0-9_.]+/', '', $field))));
  }

  /**
   * Prefix a single table name.
   *
   * @param string $table
   *   Name of the table.
   *
   * @return string
   */
  public function prefixTable($table) {
    $table = $this->escapeTable($table);
    return $this->prefixTables('"' . $table . '"');
  }
  /**
   * {@inheritdoc}
   */
  public function quoteIdentifiers($identifiers) {
    return array_map(array($this, 'quoteIdentifier'), $identifiers);
  }
  /**
   * {@inheritdoc}
   */
  public function escapeLike($string) {
    return preg_replace('/([\\[\\]%_])/', '[$1]', $string);
  }

  /**
   * Generates a temporary table name. Because we are using
   * global temporary tables, these are visible between
   * connections so we need to make sure that their
   * names are as unique as possible to prevent collisions.
   *
   * @return string
   *   A table name.
   */
  protected function generateTemporaryTableName() {
    static $temp_key;
    if (!isset($temp_key)) {
      $temp_key = strtoupper(md5(uniqid(rand(), true)));
    }
    return "temp_" . $this->temporaryNameIndex++ . '_' . $temp_key;
  }
  protected $connection;

  /**
   * {@inheritdoc}
   *
   * This method is overriden to manage the insecure (EMULATE_PREPARE)
   * behaviour to prevent some compatibility issues with SQL Server.
   */
  public function query($query, array $args = array(), $options = array()) {
    // Use default values if not already set.
    $options += $this->defaultOptions();
    $stmt = NULL;
    try {
      // We allow either a pre-bound statement object or a literal string.
      // In either case, we want to end up with an executed statement object,
      // which we pass to PDOStatement::execute.
      if ($query instanceof StatementInterface) {
        $stmt = $query;
        $stmt->execute(NULL, $options);
      }
      else {
        $this->expandArguments($query, $args);
        $insecure = isset($options['insecure']) ? $options['insecure'] : FALSE;
        // Try to detect duplicate place holders, this check's performance
        // is not a good addition to the driver, but does a good job preventing
        // duplicate placeholder errors.
        $argcount = count($args);
        if ($insecure === TRUE || $argcount >= 2100 || ($argcount != substr_count($query, ':'))) {
          $insecure = TRUE;
        }
        $stmt = $this->prepareQuery($query, array('insecure' => $insecure));
        $stmt->execute($args, $options);
      }
      // Depending on the type of query we may need to return a different value.
      // See DatabaseConnection::defaultOptions() for a description of each
      // value.
      switch ($options['return']) {
        case Database::RETURN_STATEMENT:
          return $stmt;
        case Database::RETURN_AFFECTED:
          $stmt->allowRowCount = TRUE;
          return $stmt->rowCount();
        case Database::RETURN_INSERT_ID:
          return $this->connection->lastInsertId();
        case Database::RETURN_NULL:
          return NULL;
        default:
          throw new \PDOException('Invalid return directive: ' . $options['return']);
      }
    }
    catch (\PDOException $e) {
      // Most database drivers will return NULL here, but some of them
      // (e.g. the SQLite driver) may need to re-run the query, so the return
      // value will be the same as for static::query().
      return $this->handleQueryException($e, $query, $args, $options);
    }
  }
  /**
   * Wraps and re-throws any PDO exception thrown by static::query().
   *
   * @param \PDOException $e
   *   The exception thrown by static::query().
   * @param $query
   *   The query executed by static::query().
   * @param array $args
   *   An array of arguments for the prepared statement.
   * @param array $options
   *   An associative array of options to control how the query is run.
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   *   Most database drivers will return NULL when a PDO exception is thrown for
   *   a query, but some of them may need to re-run the query, so they can also
   *   return a \Drupal\Core\Database\StatementInterface object or an integer.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   */
  public function handleQueryException(\PDOException $e, $query, array $args = array(), $options = array()) {
    if ($options['throw_exception']) {
      // Wrap the exception in another exception, because PHP does not allow
      // overriding Exception::getMessage(). Its message is the extra database
      // debug information.
      if ($query instanceof StatementInterface) {
        /** @var Statement $statement */
        $statement = $query;
        $e->query_string = $statement->getQueryString();
        $e->args = $statement->GetBoundParameters();
      }
      else {
        $e->query_string = $query;
      }
      $message = $e->getMessage();
      /** @var \Drupal\Core\Database\DatabaseException $exception */
      $exception = NULL;
      // Match all SQLSTATE 23xxx errors.
      if (substr($e->getCode(), -6, -3) == '23') {
        $exception = new IntegrityConstraintViolationException($message, $e->getCode(), $e);
      }
      else if ($e->getCode() == '42S02') {
        $exception = new SchemaObjectDoesNotExistException($e->getMessage(), 0, $e);
      }
      else {
        $exception = new DatabaseExceptionWrapper($message, 0, $e);
      }
      if (empty($e->args)) {
        $e->args = $args;
      }
      // Copy this info to the rethrown Exception for compatibility.
      $exception->query_string = $e->query_string;
      $exception->args = $e->args;
      throw $exception;
    }
    return NULL;
  }

  /**
   * {@inhertidoc}
   */
  public function nextId($existing_id = 0) {
    $new_id = $this->query('INSERT INTO {sequences} () VALUES ()', array(), array('return' => Database::RETURN_INSERT_ID));
    if ($existing_id >= $new_id) {
      $this->query('INSERT INTO {sequences} (value) VALUES (:value) ON DUPLICATE KEY UPDATE value = value', array(':value' => $existing_id));
      $new_id = $this->query('INSERT INTO {sequences} () VALUES ()', array(), array('return' => Database::RETURN_INSERT_ID));
    }
    $this->needsCleanup = TRUE;
    return $new_id;
  }

  public function nextId($existing_id = 0) {
    $new_id = $this->query('INSERT INTO "sequences" () VALUES ()', array(), array('return' => Database::RETURN_INSERT_ID));
    // This should only happen after an import or similar event.
    if ($existing_id >= $new_id) {
      $this->query('MERGE INTO "sequences" AS T USING (VALUES (:value)) AS S(value) ON T.value=S.value  WHEN MATCHED THEN UPDATE SET value = S.value WHEN NOT MATCHED THEN INSERT () VALUES (S.value)', array(':value' => $existing_id));
      $new_id = $this->query('INSERT INTO "sequences" () VALUES ()', array(), array('return' => Database::RETURN_INSERT_ID));
    }
    $this->needsCleanup = TRUE;
    return $new_id;
  }

  public function nextIdDelete() {
    try {
      $max_id = $this->query('SELECT MAX(value) FROM "sequences"')->fetchField();
      $this->query('DELETE FROM "sequences" WHERE value < :value', array(':value' => $max_id));
    }
    catch (DatabaseException $e) {
    }
  }

  /**
   * Overrides \Drupal\Core\Database\Connection::createDatabase().
   *
   * @param string $database
   *   The name of the database to create.
   *
   * @throws \Drupal\Core\Database\DatabaseNotFoundException
   */
  public function createDatabase($database) {
    $database = Database::getConnection()->escapeDatabase($database);

    try {
      // Create the database and set it as active.
      $this->connection->exec("CREATE DATABASE $database");
      $this->connection->exec("USE $database");
    }
    catch (\Exception $e) {
      throw new DatabaseNotFoundException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFullQualifiedTableName($table) {
    $options = $this->getConnectionOptions();
    $prefix = $this->tablePrefix($table);
    return $options['database'] . '.' . $this->schema()->GetDefaultSchema() . '.' . $prefix . $table;
  }
  /**
   * Error inform from the connection.
   * @return array
   */
  public function errorInfo() {
    return $this->connection->errorInfo();
  }
  /**
   * Return the name of the database in use,
   * not prefixed!
   */
  public function getDatabaseName() {
    // Database is defaulted from active connection.
    $options = $this->getConnectionOptions();
    return $options['database'];
  }
}

/**
 * @} End of "addtogroup database".
 */