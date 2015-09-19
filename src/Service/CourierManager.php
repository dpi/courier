<?php

/**
 * @file
 * Contains \Drupal\courier\Service\CourierManager.
 */

namespace Drupal\courier\Service;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\courier\TemplateCollectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\courier\Service\IdentityChannelManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\courier\Entity\MessageQueueItem;
use Drupal\courier\Exception\IdentityException;
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
   * @var \Drupal\courier\Service\IdentityChannelManager
   */
  protected $identityChannelManager;

  /**
   * Constructs the Courier Manager.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
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

    $t_args_base = [
      '%identity' => $identity->label(),
      '@template_collection' => $template_collection->id(),
    ];

    // All templates are 'rendered' into messages in case preferred channels
    // fail.
    /** @var \Drupal\courier\ChannelInterface[] $templates */
    $templates = [];
    foreach ($this->identityChannelManager->getChannelsForIdentity($identity) as $channel) {
      if ($template = $template_collection->getTemplate($channel)) {
        $templates[$channel] = $template;
      }
    }

    foreach ($templates as $channel => $template) {
      $t_args = $t_args_base;
      $t_args['@template'] = $template->id();
      $t_args['%channel'] = $channel;
      if ($plugin = $this->identityChannelManager->getCourierIdentity($channel, $identity->getEntityTypeId())) {
        $message = $template->createDuplicate();
        if ($message->id()) {
          throw new \Exception(sprintf('Failed to clone `%s`', $channel));
        }

        try {
          $plugin->applyIdentity($message, $identity);
        }
        catch (IdentityException $e) {
          \Drupal::logger('courier')->debug('Identity %identity could not be applied to %channel.', $t_args);
          continue;
        }

        if ($message->isEmpty()) {
          \Drupal::logger('courier')->debug('Template @template for collection @template_collection was empty.', $t_args);
          continue;
        }

        foreach ($template_collection->getTokenValues() as $token => $value) {
          $message->setTokenValue($token, $value);
        }
        foreach ($template_collection->getTokenOptions() as $token_option => $value) {
          $message->setTokenOption($token_option, $value);
        }

        $message
          ->setTokenValue('identity', $identity)
          ->applyTokens();

        $violations = $message->validate();
        if ($violations->count()) {
          \Drupal::logger('courier')->debug('Template @template for collection @template_collection did not validate.', $t_args);
          continue;
        }

        if ($message->save()) {
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
      return $message_queue;
    }

    \Drupal::logger('courier')->info('No messages could be sent to %identity. No messages were generated.', $t_args);
    return FALSE;
  }

}
