<?php

namespace Drupal\courier\Service;

use Drupal\courier\TemplateCollectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\courier\Entity\MessageQueueItem;
use Drupal\courier\Exception\IdentityException;

/**
 * The courier manager.
 */
class CourierManager implements CourierManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger for the Courier channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManager
   */
  protected $identityChannelManager;

  /**
   * Constructs the Courier Manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, IdentityChannelManagerInterface $identity_channel_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('courier');
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function addTemplates(TemplateCollectionInterface &$template_collection) {
    foreach (array_keys($this->identityChannelManager->getChannels()) as $entity_type_id) {
      if (!$template_collection->getTemplate($entity_type_id)) {
        /** @var $template \Drupal\courier\ChannelInterface */
        $template = $this->entityTypeManager
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
          $this->logger->notice(
            'Identity %identity could not be applied to %channel: @message.',
            $t_args + ['@message' => $e->getMessage()]
          );
          continue;
        }

        if ($message->isEmpty()) {
          $this->logger->debug('Template @template for collection @template_collection was empty.', $t_args);
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
          $this->logger->debug('Template @template for collection @template_collection did not validate.', $t_args);
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

    $this->logger->info('No messages could be sent to %identity. No messages were generated.', $t_args_base);
    return FALSE;
  }

}
