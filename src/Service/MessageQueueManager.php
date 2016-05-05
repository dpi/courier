<?php

namespace Drupal\courier\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\courier\MessageQueueItemInterface;

/**
 * The message queue manager.
 */
class MessageQueueManager implements MessageQueueManagerInterface {

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
   * Constructs a message queue manager.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   */
  function __construct(LoggerChannelFactoryInterface $logger_factory, IdentityChannelManagerInterface $identity_channel_manager) {
    $this->logger = $logger_factory->get('courier');
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(MessageQueueItemInterface $mqi) {
    $options = $mqi->getOptions();
    $channel_options = array_key_exists('channels', $options) ? $options['channels'] : [];
    unset($options['channels']);

    // Instead of iterating over messages, get the identity' channel preferences
    // again. This ensures preference order is up to date since significant time
    // may have passed since adding to queue.
    $channels = $this->identityChannelManager
      ->getChannelsForIdentity($mqi->getIdentity());

    $messages = [];
    foreach ($channels as $channel) {
      if ($message = $mqi->getMessage($channel)) {
        $messages[] = $message;
      }
    }

    /** @var \Drupal\courier\ChannelInterface[] $messages */
    foreach ($messages as $message) {
      $message_options = $options;
      // Transform options based on channel.
      $channel = $message->getEntityTypeId();
      if (array_key_exists($channel, $channel_options)) {
        $message_options = array_merge($message_options, $channel_options[$channel]);
      }

      $t_args = [
        '@channel' => $channel,
        '@identity' => $mqi->getIdentity()->label(),
      ];

      try {
        $message::sendMessages([$message], $message_options);
        $this->logger
          ->info('Successfully sent @channel to @identity', $t_args);
        $mqi->delete();
        return $message;
      }
      catch (\Exception $e) {
        $t_args['@exception'] = $e->getMessage();
        $this->logger
          ->warning('Failed to send @channel to @identity: @exception', $t_args);
        continue;
      }

      break;
    }

    return FALSE;
  }

}
