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
   * Contains the name of the configured Drupal content type to store an Event.
   *
   * @var string
   */
  var $drupalContentType;

  /**
   * @var int
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
    $this->drupalContentType = $this->configFactory->get('civicrm_event_sync.settings')
      ->get('content_type');

    $this->update = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchCivicrm($id): array {
    $result = $this->apiService->api('Event', 'get', [
      'custom_10' => $id
    ]);
    return $result['values'];
  }

  /**
   * {@inheritdoc}
   */
  public function fetchDrupal($id): array {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();

    $result = $query->condition('type', $this->drupalContentType)
      ->condition($this->drupalRefField . ".event_id", $id)
      ->execute();

    return $result;
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
