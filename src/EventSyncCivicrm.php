<?php

namespace Drupal\civicrm_event_sync;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Controller for syncing events into Civicrm.
 */
class EventSyncCivicrm extends EventSyncBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, Civicrm $civicrm, LoggerChannelFactoryInterface $logger, ApiService $apiService) {
    parent::__construct($entityTypeManager, $configFactory, $civicrm, $logger, $apiService);
  }

  /**
   * Create an event from Drupal in CiviCRM.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   *   The entity of type node.
   *
   * @throws \Exception
   */
  public function eventSyncDrupalCreateCivicrm(Entity $entity): void {
    // Check if event already exists in Civicrm, only continue if not.
    if (!$this->existsInCivicrm($entity->id())) {
      // First: create the event in civicrm as wel.
      $result = $this->apiService->api('Event', 'create', [
        'title' => $entity->getTitle(),
        'start_date' => $entity->get('field_start_date')->getString(),
        'end_date' => $entity->get('field_end_date')->getString(),
        'event_type_id' => "Conference",
        'custom_10' => $entity->id(),
      ]);

      // Second: update the current node to include the civicrm event id.
      if (isset($result['id'])) {
        $entity->set($this->civicrmRefField, $result['id']);
        $entity->save();
      }

      $this->logger->get('EventSync')
        ->error('Created CiviCRM event with id: %id.', ['%id' => $result['id']]);
    }
  }

  /**
   * Update an event from Drupal in civicrm.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   *   The entity of type node.
   *
   * @throws \Exception
   */
  public function eventSyncDrupalUpdateCivicrm(Entity $entity): void {
    // If a node has no value in event_id field create the event in civicrm.
    $event = $entity->get($this->civicrmRefField)->getString();
    if (empty($event)) {
      $this->eventSyncDrupalCreateCivicrm($entity);
    }
    else if ($this->update < 1) {
      $this->apiService->api('Event', 'create', [
        'id' => $event,
        'title' => $entity->getTitle(),
//        'start_date' => $entity->get('field_start_date')->getString(),
//        'end_date' => $entity->get('field_end_date')->getString(),
        'event_type_id' => "Conference",
      ]);

      $this->update++;

      $this->logger->get('EventSync')
        ->error('Updated CiviCRM event with id: %id.', ['%id' => $event]);
    }
  }

  /**
   * Delete an event from Drupal in civicrm.
   *
   * @param \Drupal\Core\Entity\Entity $entity
   *   The entity of type node.
   *
   * @throws \Exception
   */
  public function eventSyncDrupalDeleteCivicrm(Entity $entity): void {
    if ($entity) {
      $id = $entity->get($this->civicrmRefField)->getString();
      $result = $this->apiService->api('Event', 'delete', [
        'id' => $id,
      ]);

      if (isset($result['is_error']) && !$result['is_error']) {
        $this->logger->get('EventSync')
          ->error('Deleted CiviCRM event with id: %id.', ['%id' => $entity->id()]);
      }
    }
  }

}
