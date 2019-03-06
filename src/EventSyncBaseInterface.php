<?php

namespace Drupal\civicrm_event_sync;

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
   * Fetch a sync row from the db.
   *
   * @param $id
   * @param $from
   *
   * @return array
   */
  public function get($id): array;

  /**
   * Fetch an event by id from Drupal
   *
   * @param $id
   * @param $from
   *
   * @return array
   */
  public function fetchDrupal($id): array;

  /**
   * Fetch an event by id from CiviCRM
   *
   * @param $id
   * @param string $by
   *
   * @return array
   */
  public function fetchCivicrm($id, $by = 'id'): array;

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
