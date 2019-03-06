<?php

namespace Drupal\civicrm_event_sync;

use Drupal\Core\Database\Connection;

class EventSync implements EventSyncInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * EventSyncBse constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The drupal db connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function set($params) {
    $this->connection->insert('civicrm_event_sync')
      ->fields([
        'cid' => $params['cid'],
        'nid' => $params['nid'],
        'origin' => $params['origin'],
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getByOrigin($origin, $id) {
    // Define the
    switch (strtolower($origin)) {
      case 'cid':
      case 'civicrm':
        $origin_id = 'cid';
        break;
      case 'drupal':
      case 'nid':
        $origin_id = 'nid';
        break;
    }

    $result = $this->connection->select('civicrm_event_sync', 's')
      ->fields('s', ['id', 'cid', 'nid', 'origin'])
      ->condition($origin_id, $id)
      ->execute();

    return $result;
  }

}