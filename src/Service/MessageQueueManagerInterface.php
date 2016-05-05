<?php

namespace Drupal\courier\Service;

use Drupal\courier\MessageQueueItemInterface;

/**
 * Interface for message queue manager.
 *
 * Notice: this service is internal to Courier. It should not be called outside of
 * the core module.
 */
interface MessageQueueManagerInterface {

  /**
   * Attempts to send the messages in the message queue item.
   *
   * Attempts will halt as soon as a message is sent successfully, then the
   * message queue item will be deleted.
   *
   * @param \Drupal\courier\MessageQueueItemInterface $mqi
   *   A message queue item.
   *
   * @return \Drupal\courier\ChannelInterface|FALSE
   *   The message that was sent, or FALSE if all messages failed to send.
   */
  public function sendMessage(MessageQueueItemInterface $mqi);

}
