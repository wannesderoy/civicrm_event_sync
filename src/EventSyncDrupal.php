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
   * {@inheritdoc}
   */
  public function create($objectRef): void {
    // Check if event already exists in Drupal, only continue if not.
    // Check if is template, don't sync if so.
    if (!$this->existsInDrupal($objectRef->id) && !$this->isEventTemplate($objectRef->id)) {
      // First: create the node in Drupal as wel.
      $event = $this->entityTypeManager->getStorage('node')
        ->create([
          'type' => $this->configFactory->get('civicrm_event_sync.settings')
            ->get('content_type'),
        ]);

      $event->set('title', $objectRef->title);
      $event->set($this->civicrmRefField, $objectRef->id);
      $event->status = 0;
      $event->enforceIsNew();
      $event->save();

      // Second: update the current civicrm even to include the Drupal node id.
      $this->apiService->api('Event', 'create', [
        'id' => $objectRef->id,
        $this->drupalRefField => $event->id(),
      ]);

      $this->logger->get('EventSync')
        ->error('Created Drupal event with id %id', ['%id' => $event->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function update($objectRef): void {
    // If a event has no value in node id field create the event in Drupal.
    if (!$this->isEventTemplate($objectRef->id)) {
      $event = $this->getCivicrmEventDrupalId($objectRef->id);
      if (empty($event)) {
        $this->create($objectRef->id, $objectRef);
      }
      else {
        if ($this->update < 1) {
          $this->update++;

          $event = $this->entityTypeManager->getStorage('node')->load($event);
          $event->set('title', $objectRef->title);
          $event->save();

          $this->logger->get('EventSync')
            ->error('Updated Drupal event with id %id', ['%id' => $event->id()]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($objectRef): void {
    $events = $this->getNodesFromCivicrmEventId($objectRef->id);
    if ($events) {
      $this->entityTypeManager->getStorage('node')
        ->loadMultiple($events)
        ->delete();

      $this->logger->get('EventSync')
        ->error('Deleted Drupal node(s) with id(\'s) %ids', ['%ids' => implode(', ', $events)]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function existsInDrupal(int $event_id): bool {
    $events = $this->getNodesFromCivicrmEventId($event_id);
    if (!empty($events)) {
      return TRUE;
    }
    return FALSE;
  }

}
