<?php

namespace Drupal\civicrm_event_sync;

use Drupal\civicrm\Civicrm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Controller for syncing events into Drupal.
 */
class EventSyncDrupal extends EventSyncBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, Civicrm $civicrm, LoggerChannelFactoryInterface $logger, ApiService $apiService) {
    parent::__construct($entityTypeManager, $configFactory, $civicrm, $logger, $apiService);
  }

  /**
   * Create an event from CiviCRM in Drupal.
   *
   * @param string $objectId
   *   The id of the event.
   * @param object $objectRef
   *   The CiviXRM entity of type event.
   *
   * @throws \Exception
   */
  public function eventSyncCivicrmCreateDrupal(string $objectId, object $objectRef): void {
    // Check if event already exists in Drupal, only continue if not.
    if (!$this->existsInDrupal($objectId)) {
      // First: create the event in Drupal as wel.
      $event = $this->entityTypeManager->getStorage('node')
        ->create(['type' => 'event']);

      $event->set('title', $objectRef->title);
      $body = [
        'value' => $objectRef->description,
        'format' => 'editor',
      ];
      $event->set('body', $body);
      $event->set($this->civicrmRefField, $objectId);
      $event->set('field_start_date', $this->formatDate($objectRef->start_date));
      $event->set('field_end_date', $this->formatDate($objectRef->end_date));
      $event->status = 1;
      $event->enforceIsNew();
      $event->save();

      // Second: update the current civicrm even to include the Drupal node id.
      $this->apiService->api('Event', 'create', [
        'id' => $objectId,
        'custom_10' => $event->id(),
      ]);

      $this->logger->get('EventSync')
        ->error('Created Drupal event with id %id', ['%id' => $event->id()]);
    }
  }

  /**
   * Update an event from CiviCRM in Drupal.
   *
   * @param string $objectId
   *   The id of the event.
   * @param object $objectRef
   *   The CiviXRM entity of type event.
   *
   * @throws \Exception
   */
  public function eventSyncCivicrmUpdateDrupal(string $objectId, object $objectRef): void {
    // If a event has no value in node id field create the event in Drupal.
    $event = $this->getCivicrmEventDrupalId($objectId);
    if (empty($event)) {
      $this->eventSyncCivicrmCreateDrupal($objectId, $objectRef);
    }
    else if ($this->update < 1) {
      $event = $this->entityTypeManager->getStorage('node')->load($event);
      $event->set('title', $objectRef->title);
      $body = [
        'value' => $objectRef->description,
        'format' => 'editor',
      ];
      $event->set('body', $body);
      $event->set('field_start_date', $this->formatDate($objectRef->start_date));
      $event->set('field_end_date', $this->formatDate($objectRef->end_date));
      $event->save();

      $this->update++;

      $this->logger->get('EventSync')
        ->error('Updated Drupal event with id %id', ['%id' => $event->id()]);
    }
  }

  /**
   * Delete an event from CiviCRM in Drupal.
   *
   * @param string $objectId
   *   The id of the event.
   * @param object $objectRef
   *   The CiviXRM entity of type event.
   *
   * @throws \Exception
   */
  public function eventSyncCivicrmDeleteDrupal(string $objectId, object $objectRef): void {
    $event = $this->getNodesFromCivicrmEventId($objectId);
    if ($event) {
      $this->entityTypeManager->getStorage('node')
        ->loadMultiple($event)
        ->delete();

        $this->logger->get('EventSync')
          ->error('Deleted Drupal node(s) with id(\'s) %ids', ['%ids' => implode(', ', $entities)]);
    }
  }

}
