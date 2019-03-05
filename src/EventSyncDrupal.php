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
    // Also check if is template, don't sync if so.
    if (!$this->existsInDrupal($objectRef->id) && !$this->isEventTemplate($objectRef->id)) {
      // First: create the node in Drupal as wel.
      $event = $this->entityTypeManager->getStorage('node')
        ->create([
          'type' => $this->drupalContentType,
        ]);

      $event->set('title', $objectRef->title);
      $event->set($this->drupalRefField, $objectRef->id);
      $event->setUnpublished();
      $event->enforceIsNew();
      $event->save();

      // Second: update the current civicrm even to include the Drupal node id.
      $this->apiService->api('Event', 'create', [
        'id' => $objectRef->id,
        $this->civicrmRefField => $event->id(),
      ]);

      $this->logger->get('EventSync')
        ->info('Created Drupal event with id %id', ['%id' => $event->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function update($objectRef): void {
    // If a event has no value in node id field create the event in Drupal.
    $event = $this->apiService->api('Event', 'getSingle', [
      'id' => $objectRef->id,
      'return' => ["custom_10"],
    ]);
    if (isset($event['custom_10']) && empty($event['custom_10'])) {
      $this->create($objectRef);
    }
    else {
      if ($this->update < 1) {
        $this->update++;

        $event = $this->entityTypeManager->getStorage('node')->load($event['custom_10']);
        $event->set('title', $objectRef->title);
        $event->save();

        $this->logger->get('EventSync')
          ->info('Updated Drupal event with id %id', ['%id' => $event->id()]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete($objectRef): void {
    $event = $this->apiService->api('Event', 'get', [
      'id' => $objectRef->id,
      'return' => [$this->civicrmRefField],
    ]);
    if ($event) {
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      $entities = $nodeStorage->loadMultiple($event);
      $nodeStorage->delete($entities);

      $this->logger->get('EventSync')
        ->info('Deleted Drupal node(s) with id(\'s) %ids', ['%ids' => $event]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function existsInDrupal(int $event_id): bool {
    $events = $this->fetchDrupal($event_id);
    if (!empty($events)) {
      return TRUE;
    }
    return FALSE;
  }

}
