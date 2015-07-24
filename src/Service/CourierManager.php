<?php

/**
 * @file
 * Contains \Drupal\courier\Service\CourierManager.
 */

namespace Drupal\courier\Service;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\courier\TemplateCollectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\courier\IdentityChannelManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Courier manager.
 */
class CourierManager implements CourierManagerInterface {

  use ContainerAwareTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\IdentityChannelManager
   */
  protected $identityChannelManager;

  /**
   * Constructs the Courier Manager.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\courier\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, IdentityChannelManagerInterface $identity_channel_manager) {
    $this->entityManager = $entity_manager;
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function addTemplates(TemplateCollectionInterface &$template_collection) {
    foreach (array_keys($this->identityChannelManager->getChannels()) as $entity_type_id) {
      /** @var $template \Drupal\courier\ChannelInterface */
      $template = $this->entityManager
        ->getStorage($entity_type_id)
        ->create();
      $template_collection->setTemplate($template);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(TemplateCollectionInterface $template_collection, EntityInterface $identity, array $options = []) {
    // todo move to general CourierManager
    $template_collection->validateTokenValues();
    $channel_options = isset($options['channels']) ? $options['channels'] : [];
    unset($options['channels']);
    foreach ($this->identityChannelManager->getChannelsForIdentity($identity) as $channel) {
      if ($template = $template_collection->getTemplate($channel)) {
        if ($plugin = $this->identityChannelManager->getCourierIdentity($channel, $identity->getEntityTypeId())) {
          $template->applyTokens($template_collection->getTokenValues());
          // Identity
          $template->applyTokens([
            'identity' => $identity,
          ]);
          $plugin->applyIdentity($template, $identity);

          // Transform options based on channel
          $options_new = $options;
          if (array_key_exists($channel, $channel_options)) {
            $options_new = array_merge($options, $channel_options[$channel]);
          }
          $template->sendMessage($options_new);
        }
      }
    }
  }

}