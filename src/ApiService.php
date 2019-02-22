<?php

namespace Drupal\civicrm_event_sync;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\civicrm\Civicrm;
use CiviCRM_API3_Exception;

/**
 * Class CiviCRMService.
 */
class ApiService implements ApiServiceInterface {

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * Constructs a new CiviCrmApi object.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory service.
   *
   * @throws \Exception
   */
  public function __construct(Civicrm $civicrm, LoggerChannelFactoryInterface $logger) {
    $this->logger = $logger;
    try {
      $this->civicrm = $civicrm;
      $this->civicrm->initialize();
    }
    catch (\Exception $e) {
      $this->logger->get('CivicrmService')
        ->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function api($entity, $action, array $params) {
    $results = [];
    try {
      $results = civicrm_api3($entity, $action, $params);
    }
    catch (CiviCRM_API3_Exception $e) {
      $this->logger->get('CivicrmService')->error($e->getMessage());
    }
    return $results;
  }

}
