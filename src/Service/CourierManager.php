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
use Drupal\courier\Entity\MessageQueueItem;

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
      if (!$template_collection->getTemplate($entity_type_id)) {
        /** @var $template \Drupal\courier\ChannelInterface */
        $template = $this->entityManager
          ->getStorage($entity_type_id)
          ->create();
        $template_collection->setTemplate($template);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(TemplateCollectionInterface $template_collection, EntityInterface $identity, array $options = []) {
    $template_collection->validateTokenValues();

    $message_queue = MessageQueueItem::create()
      ->setOptions($options)
      ->setIdentity($identity);

    // All templates are 'rendered' into messages in case preferred channels
    // fail.
    foreach ($this->identityChannelManager->getChannelsForIdentity($identity) as $channel) {
      if ($template = $template_collection->getTemplate($channel)) {
        if ($plugin = $this->identityChannelManager->getCourierIdentity($channel, $identity->getEntityTypeId())) {
          $message = $template->createDuplicate();
          if ($message->id()) {
            throw new \Exception(sprintf('Failed to clone `%s`', $channel));
          }

          $plugin->applyIdentity($message, $identity);
          foreach ($template_collection->getTokenValues() as $token => $value) {
            $message->setTokenValue($token, $value);
          }
          $message
            ->setTokenValue('identity', $identity)
            ->applyTokens()
            ->save();

          $message_queue->addMessage($message);
        }
      }
    }

    if ($message_queue->getMessages()) {
      $message_queue->save();
      $queue = \Drupal::queue('courier_message');
      $queue->createItem([
        'id' => $message_queue->id(),
      ]);
    }
    else {
      \Drupal::logger('courier')->info('No message was sent to @label. Identity type: @entity_type_id: @entity_id. No valid messages were generated.', [
        '@label' => $identity->label(),
        '@entity_type_id' => $identity->getEntityTypeId(),
        '@entity_id' => $identity->id(),
      ]);
    }
  }

}