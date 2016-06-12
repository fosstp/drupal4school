<?php

/**
 * @file
 * Contains \Drupal\Database\Driver\ibmdb2\Upsert.
 */

namespace Drupal\Database\Driver\ibmdb2;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

/**
 * ibmdb2 implementation of \Drupal\Core\Database\Query\Upsert.
 */
class Upsert extends QueryUpsert {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    // Default fields are always placed first for consistency.
    $fields = array_merge($this->defaultFields, $this->insertFields);
    $values = $this->getInsertPlaceholderFragment($this->insertValues, $this->defaultFields);

    $query = $comments . 'MERGE INTO "' . $this->table . '" AS T USING (VALUES(' . implode(', ', $values) . ')) AS S(' . implode(', ', $fields) . ') ON T.' . $this->key . '=S.' . $this->key;

    // Updating the unique / primary key is not necessary.
    unset($insert_fields[$this->key]);

    $update = [];
    foreach ($fields as $field) {
      $update[] = "T.$field=S.$field";
    }

    $insert = [];
    foreach ($fields as $field) {
      $insert[] = "S.$field";
    }

    $query .= ' WHEN MATCHED THEN UPDATE SET ' . implode(', ', $update);
    $query .= ' WHEN NOT MATCHED THEN INSERT (' . implode(', ', $fields) . ') VALUES(' . implode(', ', $insert) . ')';

    return $query;
  }

}
