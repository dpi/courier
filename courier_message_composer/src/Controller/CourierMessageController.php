<?php

namespace Drupal\courier_message_composer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\courier\Service\IdentityChannelManagerInterface;

/**
 * Returns responses for CourierMessageController routes.
 */
class CourierMessageController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManagerInterface
   */
  protected $identityChannelManager;

  /**
   * Constructs a CourierMessageController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, IdentityChannelManagerInterface $identity_channel_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.identity_channel')
    );
  }

  /**
   * Return a list of links to channels.
   */
  public function channelList() {
    $render['channels'] = [
      '#title' => $this->t('Channels'),
      '#theme' => 'item_list',
      '#items' => [],
    ];

    foreach (array_keys($this->identityChannelManager->getChannels()) as $channel) {
      if ($this->composeAnyIdentityForChannel($channel)) {
        $definition = $this->entityTypeManager->getDefinition($channel);
        $item = [];
        $item[] = [
          '#type' => 'link',
          '#title' => $definition->getLabel(),
          '#url' => Url::fromRoute('courier_message_composer.compose', [
            'courier_channel' => $channel,
          ]),
        ];
        $render['channels']['#items'][] = $item;
      }
    }

    return $render;
  }

  /**
   * Determines if the current user can compose a message for any identity type.
   *
   * @param string $channel
   *   A channel entity type ID.
   *
   * @return bool
   *   If the current user can compose a message for any identity type.
   */
  protected function composeAnyIdentityForChannel($channel) {
    $channels = $this->identityChannelManager->getChannels();
    if (array_key_exists($channel, $channels)) {
      foreach ($channels[$channel] as $identity) {
        $permission = "courier_message_composer compose $channel to $identity";
        if ($this->currentUser()->hasPermission($permission)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
