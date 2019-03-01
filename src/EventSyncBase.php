<?php

namespace Drupal\civicrm_event_sync;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Controller for syncing events into Civicrm.
 */
abstract class EventSyncBase implements EventSyncBaseInterface {

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

    $this->drupalRefField = $this->configFactory->get('civicrm_event_sync.settings')
      ->get('drupal_field');
    $this->civicrmRefField = $this->configFactory->get('civicrm_event_sync.settings')
      ->get('custom_field');

    $this->update = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCivicrmEventsFromNodeId(int $id): array {
    $result = $this->apiService->api('Event', 'get', ['custom_10' => $id]);
    return $result['values'];
  }

  /**
   * {@inheritdoc}
   */
  public function getNodesFromCivicrmEventId(int $id, string $conjunction = 'AND'): array {
    $query = \Drupal::service('entity.query')->get('node');
    $result = $query->condition('type', 'event')
      ->condition($this->civicrmRefField . ".event_id", $id)
      ->execute();

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCivicrmEventDrupalId(int $event_id): int {
    $result = $this->apiService->api('Event', 'getSingle', [
      'id' => $event_id,
      'return' => ["custom_10"],
    ]);

    return $result['custom_10'];
  }

  /**
   * {@inheritdoc}
   */
  public function isEventTemplate(int $event_id): bool {
    $result = $this->apiService->api('Event', 'getSingle', [
      'id' => $event_id,
      'return' => ["is_template"],
    ]);

    return (bool) $result['is_template'];
  }

}
