<?php

namespace Drupal\courier_message_composer;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\courier\Service\IdentityChannelManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define a permission generator for Courier Message Composer.
 */
class Permissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManager
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
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.identity_channel')
    );
  }

  /**
   * Define permissions for each channel + identity combination.
   *
   * @return array
   */
  public function sendMessageToChannels() {
    $permissions = [];

    $t_args = [];
    foreach ($this->identityChannelManager->getChannels() as $channel => $identity_types) {
      $t_args['%channel'] = $this->entityTypeManager->getDefinition($channel)->getLabel();
      foreach ($identity_types as $identity) {
        $t_args['%identity'] = $this->entityTypeManager->getDefinition($identity)->getLabel();
        $permissions["courier_message_composer compose $channel to $identity"] = [
          'title' => $this->t('Send %channel to %identity', $t_args),
          'description' => $this->t('Send individual messages to any %identity.', $t_args),
        ];
      }
    }

    return $permissions;
  }

}
