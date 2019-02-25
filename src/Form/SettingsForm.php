<?php

namespace Drupal\civicrm_event_sync\Form;

use Drupal\civicrm_event_sync\ApiService;
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
   * The civicrm_event_sync api service.
   *
   * @var \Drupal\civicrm_event_sync\ApiService
   */
  protected $apiService;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\civicrm_event_sync\ApiService $apiService
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, ApiService $apiService) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->apiService = $apiService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('civicrm_event_sync.api')
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

    $form['civicrm_event_sync_drupal'] = [
      '#id' => 'civicrm_event_sync_drupal',
      '#type' => 'details',
      '#title' => $this->t('Mapping settings Drupal'),
      '#description' => $this->t('Configure which content type and which field on the content type should the sync use.'),
      '#open' => TRUE,
      '#collapsible' => FALSE,
    ];

    $options = $this->getNodeTypes();

    $form['civicrm_event_sync_drupal']['content_type'] = [
      '#title' => $this->t('Which content type?'),
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $form_state->getValue([
          'civicrm_event_sync_drupal',
          'content_type',
        ]) ?? $config->get('content_type'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'civicrm_event_sync_drupal_configuration',
        'method' => 'replace',
        'effect' => 'fade',
        'speed' => 'fast',
      ],
    ];

    $form['civicrm_event_sync_drupal']['configuration'] = [
      '#type' => 'item',
      '#id' => 'civicrm_event_sync_drupal_configuration',
    ];

    $options = $this->getNodeFieldDefinitions(($form_state->getValue([
        'civicrm_event_sync',
        'content_type',
      ]) ?? $config->get('content_type')));

    $form['civicrm_event_sync_drupal']['configuration']['drupal_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Which field?'),
      '#options' => $options,
      '#default_value' => $form_state->getValue([
          'civicrm_event_sync_drupal',
          'configuration',
          'drupal_field',
        ]) ?? $config->get('drupal_field'),
      '#required' => TRUE,
    ];

    //------------------------------------------------------------------------\\

    $form['civicrm_event_sync_civicrm'] = [
      '#id' => 'civicrm_event_sync_civicrm',
      '#type' => 'details',
      '#title' => $this->t('Mapping settings CiviCRM'),
      '#description' => $this->t('Configure which custom field group and field in CiviCRM to use for the sync.'),
      '#open' => TRUE,
      '#collapsible' => FALSE,
    ];

    $options = $this->getCustomGroups();

    $form['civicrm_event_sync_civicrm']['custom_group'] = [
      '#title' => $this->t('Which custom group type?'),
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $form_state->getValue([
          'civicrm_event_sync',
          'content_type',
        ]) ?? $config->get('custom_group'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'ajaxCallbackCiviCRM'],
        'wrapper' => 'civicrm_event_sync_civicrm_configuration',
        'method' => 'replace',
        'effect' => 'fade',
        'speed' => 'fast',
      ],
    ];

    $form['civicrm_event_sync_civicrm']['configuration'] = [
      '#type' => 'item',
      '#id' => 'civicrm_event_sync_civicrm_configuration',
    ];

    $options = $this->getCustomFields(($form_state->getValue([
        'civicrm_event_sync_civicrm',
        'custom_group',
      ]) ?? $config->get('custom_group', '')));

    $form['civicrm_event_sync_civicrm']['configuration']['custom_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Which field?'),
      '#options' => $options,
      '#default_value' => $form_state->getValue([
          'civicrm_event_sync_civicrm',
          'configuration',
          'custom_field',
        ]) ?? $config->get('custom_field', ''),
      '#required' => TRUE,
    ];

    //------------------------------------------------------------------------\\

    $form['civicrm_event_sync'] = [
      '#id' => 'civicrm_event_sync',
      '#type' => 'details',
      '#title' => $this->t('Sync settings'),
      '#description' => $this->t('Configure which ways the sync should go.'),
      '#open' => TRUE,
      '#collapsible' => FALSE,
    ];

    $form['civicrm_event_sync']['ops_to_civicrm'] = [
      '#title' => $this->t('Which operations from drupal to civicrm?'),
      '#type' => 'checkboxes',
      '#options' => [
        'create' => $this->t('Save from drupal to civicrm'),
        'update' => $this->t('Update from drupal to civicrm'),
        'delete' => $this->t("Delete from drupal to civicrm"),
      ],
      '#default_value' => $form_state->getValue([
          'civicrm_event_sync',
          'ops_to_civicrm',
        ]) ?? $config->get('ops_to_civicrm', []),
    ];

    $form['civicrm_event_sync']['ops_to_drupal'] = [
      '#title' => $this->t('Which operations from civicrm to drupal?'),
      '#type' => 'checkboxes',
      '#options' => [
        'create' => $this->t('Save from civicrm to drupal'),
        'update' => $this->t('Update from civicrm to drupal'),
        'delete' => $this->t('Delete from civicrm to drupal'),
      ],
      '#default_value' => $form_state->getValue([
          'civicrm_event_sync',
          'ops_to_drupal',
        ]) ?? $config->get('ops_to_drupal', []),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('civicrm_event_sync.settings');

    $config->set('content_type', $form_state->getValue([
      'civicrm_event_sync_drupal',
      'content_type',
    ]));
    $config->set('drupal_field', $form_state->getValue([
      'civicrm_event_sync_drupal',
      'configuration',
      'drupal_field',
    ]));
    $config->set('custom_group', $form_state->getValue([
      'civicrm_event_sync_civicrm',
      'custom_group',
    ]));
    $config->set('custom_field', $form_state->getValue([
      'civicrm_event_sync_civicrm',
      'configuration',
      'custom_field',
    ]));
    $config->set('ops_to_civicrm', array_filter(array_values($form_state->getValue([
      'civicrm_event_sync',
      'ops_to_civicrm',
    ]))));
    $config->set('ops_to_drupal', array_filter(array_values($form_state->getValue([
      'civicrm_event_sync',
      'ops_to_drupal',
    ]))));

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
  public static function ajaxCallbackDrupal($form, FormStateInterface $form_state) {
    return $form['civicrm_event_sync_drupal']['configuration'];
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
  public static function ajaxCallbackCiviCRM($form, FormStateInterface $form_state) {
    return $form['civicrm_event_sync_civicrm']['configuration'];
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
   *
   * @return array
   *   Array containing the id and label of the fields of a node type.
   */
  private function getNodeFieldDefinitions($type) {
    if (!$type) {
      return [];
    }

    $fields = $this->entityFieldManager
      ->getFieldDefinitions("node", $type);

    $options = [];
    foreach ($fields as $key => $field) {
      $options[$key] = $key;
    }

    return $options;
  }

  /**
   * Helper function that retrieves all the custom field groups of CiviCRM.
   *
   * @return array
   * @throws \Exception
   */
  private function getCustomGroups(): array {
    $groups = $this->apiService->api('CustomGroup', 'get', [
      'return' => ["id", "name"],
    ]);

    $options = [];
    foreach ($groups['values'] as $group) {
      $options[$group['name']] = $group['name'];
    }

    return $options;
  }

  /**
   * Helper function that retrieves all the custom fields of a custom group
   * in CiviCRM.
   *
   * @param $group
   *
   * @return array
   * @throws \Exception
   */
  private function getCustomFields($group): array {
    if (!$group) {
      return [];
    }

    $fields = $this->apiService->api('CustomField', 'get', [
      'return' => ["id", "name"],
      'custom_group_id' => $group,
    ]);

    $options = [];
    foreach ($fields['values'] as $field) {
      $options["custom_" . $field['id']] = $field['name'];
    }

    return $options;
  }

}
