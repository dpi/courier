<?php

/**
 * @file
 * Contains \Drupal\courier\MessageQueueItemInterface.
 */

namespace Drupal\courier;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface defining a courier_message_queue_item entity.
 */
interface MessageQueueItemInterface extends ContentEntityInterface {

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getIdentity();
  public function setIdentity(EntityInterface $identity);

  /**
   * The order of the messages is meaningful. The first successful message in
   * the list will terminate the remaining messages.
   *
   * @return \Drupal\courier\ChannelInterface[]
   */
  public function getMessages();
  public function addMessage(ChannelInterface $message);
  public function getOptions();
  public function setOptions(array $options);
  public function getCreatedTime();
  public function setCreatedTime($timestamp);

  /**
   * Attempts to send the messages on the entity.
   *
   * Attempts will halt as soon as a message is sent successfully.
   *
   * This entity can be deleted as soon as the message is sent.
   *
   * @return \Drupal\courier\ChannelInterface|FALSE
   *   The message that was sent, or FALSE if all messages failed to send.
   */
  public function sendMessage();

}