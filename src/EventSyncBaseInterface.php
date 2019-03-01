<?php

namespace Drupal\civicrm_event_sync;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Controller for syncing events into Civicrm.
 */
interface EventSyncBaseInterface {

  /**
   * Create an event from CiviCRM in Drupal.
   *
   * @param object|array $params
   *   The CiviCRM entity of type event.
   *
   * @throws \Exception
   */
  public function create($params): void;

  /**
   * Update an event from CiviCRM in Drupal.
   *
   * @param object $params
   *   The CiviCRM entity of type event.
   *
   * @throws \Exception
   */
  public function update($params): void;

  /**
   * Delete an event from CiviCRM in Drupal.
   *
   * @param object|array $params
   *   The CiviCRM entity of type event.
   *
   * @throws \Exception
   */
  public function delete($params): void;

  /**
   * Query CiviCRM to look for an event with the Drupal node id.
   *
   * @param int $id
   *   The ID of the Drupal event node.
   *
   * @return bool|array
   *   returns the found entity, FALSE otherwise.
   *
   * @throws \Exception
   */
  public function getCivicrmEventsFromNodeId(int $id): array;

  /**
   * Query Drupal to look for an event with the CiviCRM Event id.
   *
   * @param int $id
   *   The ID of the CiviCRM event.
   * @param string $conjunction
   *   (optional) The logical operator for the query, either:
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|\Drupal\node\Entity\Node[]
   *   returns the found entity, FALSE otherwise.
   *
   */
  public function getNodesFromCivicrmEventId(int $id, string $conjunction = 'AND'): array;

  /**
   * Helper function to quickly get the referenced Drupal event id from a
   * CiviCRM event.
   *
   * @param int $event_id
   *   The id of the event in CiviCRM.
   *
   * @return int
   * @throws \Exception
   */
  public function getCivicrmEventDrupalId(int $event_id): int;

  /**
   * Helper function that checks if an event is a templates. We need to check
   * this because we don't allow a templates override.
   *
   * @param int $event_id
   *
   * @return bool
   * @throws \Exception
   */
  public function isEventTemplate(int $event_id): bool;

}
