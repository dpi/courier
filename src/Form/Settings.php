<?php

namespace Drupal\courier\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\courier\Service\IdentityChannelManagerInterface;

/**
 * Configure Courier settings.
 */
class Settings extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManagerInterface
   */
  protected $identityChannelManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, IdentityChannelManagerInterface $identity_channel_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.identity_channel')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'courier_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'courier.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('courier.settings');
    $preferences = $config->get('channel_preferences');

    $header = [
      'label' => $this->t('Channel'),
      'weight' => $this->t('Weight'),
      'enabled' => [
        'data' => $this->t('Enabled'),
        'class' => ['checkbox'],
      ],
    ];

    $form['identity_channel_preference'] = [
      '#title' => $this->t('Channel preference defaults'),
      '#type' => 'vertical_tabs',
    ];
    $form['identity_types']['#tree'] = TRUE;

    $identity_types = $this->identityChannelManager->getIdentityTypes();
    foreach ($identity_types as $identity_type) {
      $entity_definition = $this->entityTypeManager
        ->getDefinition($identity_type);

      $t_args = [
        '@identity_type' => $entity_definition->getLabel(),
      ];
      $form['identity_types'][$identity_type] = [
        '#type' => 'details',
        '#title' => $entity_definition->getLabel(),
        '#open' => TRUE,
        '#group' => 'identity_channel_preference',
      ];

      $form['identity_types'][$identity_type]['channels'] = [
        '#prefix' => '<p>' . $this->t("The following channels are attempted, in order, for @identity_types who have not set preferences for themselves.
The first successful message for a channel will be transmitted, all subsequent channels are ignored.", $t_args) . '</p>',
        '#type' => 'table',
        '#header' => $header,
        '#empty' => $this->t('No channels found for @identity_type.'),
        '#attributes' => [
          'id' => 'identity-types-' . $identity_type,
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'channel-weight',
          ],
        ],
      ];

      // Ensure channels are ordered correctly and apply is enabled to value.
      $channels = [];
      $channels_all = $this->identityChannelManager->getChannelsForIdentityType($identity_type);
      // Add existing channels in, ensure channels still exist.
      if (array_key_exists($identity_type, $preferences)) {
        $channels = array_fill_keys(
          array_intersect($preferences[$identity_type], $channels_all),
          TRUE
        );
      }
      // Add in channels missing (disabled) from config.
      $channels += array_fill_keys(
        array_diff($channels_all, $channels),
        FALSE
      );

      foreach ($channels as $channel => $enabled) {
        $entity_definition = $this->entityTypeManager
          ->getDefinition($channel);

        $t_args['@channel'] = $channel;
        $row = [];
        $row['#attributes']['class'][] = 'draggable';

        $row['label']['#markup'] = $entity_definition->getLabel();
        $row['weight'] = [
          '#type' => 'weight',
          '#title' => t('Weight for @channel', $t_args),
          '#title_display' => 'invisible',
          '#default_value' => NULL,
          '#attributes' => [
            'class' => ['channel-weight'],
          ],
        ];
        $row['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enabled'),
          '#title_display' => 'invisible',
          '#default_value' => $enabled,
          '#wrapper_attributes' => [
            'class' => [
              'checkbox',
            ],
          ],
        ];

        $form['identity_types'][$identity_type]['channels'][$channel] = $row;
      }
    }

    $form['devel'] = [
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $form['devel']['skip_queue'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip queue'),
      '#description' => $this->t('Whether messages skip the load balancing queue and process in the same request. Only turn on this setting when debugging, do not use it on production sites.'),
      '#default_value' => $config->get('skip_queue'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('courier.settings');

    $channel_preferences = [];
    foreach ($form_state->getValue('identity_types') as $identity_type => $settings) {
      foreach ($settings['channels'] as $channel => $row) {
        if (!empty($row['enabled'])) {
          $channel_preferences[$identity_type][] = $channel;
        }
      }
    }

    $config
      ->set('skip_queue', $form_state->getValue('skip_queue'))
      ->set('channel_preferences', $channel_preferences)
      ->save();

    drupal_set_message(t('Settings saved.'));
  }

}
