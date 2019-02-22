<?php

namespace Drupal\civicrm_event_sync\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civicrm_event_sync_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'civicrm_event_sync.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('civicrm_event_sync.settings');
    $form['#tree'] = TRUE;

    $form['civicrm_event_sync'] = [
      '#id' => 'civicrm_event_sync',
      '#type' => 'details',
      '#title' => $this->t('Mapping settings'),
      '#description' => $this->t('Configure which content type and which field on the content type should the sync use.'),
      '#open' => TRUE,
      '#collapsible' => FALSE,
    ];

    $options = $this->getNodeTypes();

    $form['civicrm_event_sync']['content_type'] = [
      '#title' => $this->t('Which content type?'),
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $form_state->getValue([
          'civicrm_event_sync',
          'content_type',
        ]) ?? $config->get('content_type'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'civicrm_event_sync_configuration',
        'method' => 'replace',
        'effect' => 'fade',
        'speed' => 'fast',
      ],
    ];

    $form['civicrm_event_sync']['configuration'] = [
      '#type' => 'item',
      '#id' => 'civicrm_event_sync_configuration',
    ];

    $options = $this->getNodeFieldDefinitions(($form_state->getValue([
        'civicrm_event_sync',
        'content_type',
      ]) ?? $config->get('content_type')));

    $form['civicrm_event_sync']['configuration']['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Which field?'),
      '#options' => $options,
      '#default_value' => $form_state->getValue([
          'civicrm_event_sync',
          'configuration',
          'field',
        ]) ?? $config->get('field'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('civicrm_event_sync.settings');

    $config->set('content_type', $form_state->getValue([
      'civicrm_event_sync',
      'content_type',
    ]));
    $config->set('field', $form_state->getValue([
      'civicrm_event_sync',
      'configuration',
      'field',
    ]));
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Ajax callback for the configuration options.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   *   The form element containing the configuration options.
   */
  public static function ajaxCallback($form, FormStateInterface $form_state) {
    return $form['civicrm_event_sync']['configuration'];
  }

  /**
   * Helper function that retrieves all the node types of the site.
   *
   * @return array
   *   Array containing the id & label of all the node type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getNodeTypes() {
    $types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();

    $options = [];
    foreach ($types as $type) {
      $options[$type->id()] = $type->label();
    }

    return $options;
  }

  /**
   * Helper function that retrieves all the field definitions of a node type.
   *
   * @param $type
   *   The id of the node type to get all the field definitions from.
   * @return array
   *   Array containing the id and label of the fields of a node type.
   */
  private function getNodeFieldDefinitions($type) {
    $fields = $this->entityFieldManager
      ->getFieldDefinitions("node", $type);

    $options = [];
    foreach ($fields as $key => $field) {
      $options[$key] = $key;
    }

    return $options;
  }

}
