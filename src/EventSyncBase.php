<?php

namespace Drupal\civicrm_event_sync;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Controller for syncing events into Civicrm.
 */
class EventSyncBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The CiviCRM API service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The civicrm_event_sync api service.
   *
   * @var \Drupal\civicrm_event_sync\ApiService
   */
  protected $apiService;

  /**
   * Contains the name of the configured CiviCRM sync field in Drupal.
   *
   * @var string
   */
  var $civicrmRefField;

  /**
   * Contains the name of the configured Drupal sync field CiviCRM.
   *
   * @var string
   */
  var $drupalRefField;

  /**
   * @var
   */
  var $update;

  /**
   * EventSyncBse constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration service.
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM API service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory service.
   * @param \Drupal\civicrm_event_sync\ApiService $apiService
   *   The civicrm_event_sync api service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, Civicrm $civicrm, LoggerChannelFactoryInterface $logger, ApiService $apiService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->civicrm = $civicrm;
    $this->logger = $logger;
    $this->apiService = $apiService;

    $this->civicrmRefField = $this->getCivicrmRefField();
    $this->drupalRefField = $this->getDrupalRefField();
    $this->update = 0;
  }

  /**
   * Get the configured field name for the sync.
   *
   * @return string
   *   The field configured in the backend to use as sync base.
   */
  public function getCivicrmRefField(): string {
    $field_name = $this->configFactory->get('civicrm_event_sync.settings')
      ->get('drupal_field');
    return $field_name;
  }

  /**
   * Get the configured field name for the sync.
   *
   * @return string
   *   The field configured in the backend to use as sync base.
   */
  public function getDrupalRefField(): string {
    $field_name = $this->configFactory->get('civicrm_event_sync.settings')
      ->get('custom_field');
    return $field_name;
  }

  /**
   * Check if an event in CiviCRM exists in Drupal.
   *
   * @param int $event_id
   *   The ID of the CiviCRM event.
   *
   * @return bool
   *   returns true if exists, false otherwise.
   *
   */
  public function existsInDrupal(int $event_id): bool {
    $events = $this->getNodesFromCivicrmEventId($event_id);
    if (!empty($events)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if an event in Drupal exists in Civicrm.
   *
   * @param int $event_id
   *   The ID of the Drupal event.
   *
   * @return bool
   *   returns true if exists, false otherwise.
   *
   * @throws \Exception
   */
  public function existsInCivicrm(int $event_id): bool {
    $events = $this->getCivicrmEventsFromNodeId($event_id);
    if (!empty($events)) {
      return TRUE;
    }
    return FALSE;
  }

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
  public function getCivicrmEventsFromNodeId(int $id): array {
    $result = $this->apiService->api('Event', 'get', ['custom_10' => $id]);
    return $result['values'];
  }

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
  public function getNodesFromCivicrmEventId(int $id, string $conjunction = 'AND'): array {
    $query = \Drupal::service('entity.query')->get('node');
    $result = $query->condition('type', 'event')
      ->condition($this->civicrmRefField . ".event_id", $id)
      ->execute();

    return $result;
  }

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
  public function getCivicrmEventDrupalId(int $event_id): int {
    $result = $this->apiService->api('Event', 'getSingle', [
      'id' => $event_id,
      'return' => ["custom_10"],
    ]);

    return $result['custom_10'];
  }

  /**
   * Helper function that checks if an event is a templates. We need to check
   * this because we don't allow a templates override.
   *
   * @param int $event_id
   *
   * @return bool
   * @throws \Exception
   */
  public function isEventTemplate(int $event_id): bool {
    $result = $this->apiService->api('Event', 'getSingle', [
      'id' => $event_id,
      'return' => ["is_template"],
    ]);

    return (bool) $result['is_template'];
  }

  /**
   * @param $date
   *
   * @return string
   */
  public function formatDate($date): string {
    return (string) date('Y-m-d h:m:s', strtotime($date));
  }

}
