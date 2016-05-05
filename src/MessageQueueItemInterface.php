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
   * Get associated identity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An identity entity.
   */
  public function getIdentity();

  /**
   * Set associated identity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $identity
   *   The identity to set.
   *
   * @return \Drupal\courier\MessageQueueItemInterface
   *   Returns this message queue item for chaining.
   */
  public function setIdentity(EntityInterface $identity);

  /**
   * Get message with a channel entity type.
   *
   * @param string $entity_type_id
   *   A channel entity type ID.
   *
   * @return \Drupal\courier\ChannelInterface|NULL
   *   A message, or NULL.
   */
  function getMessage($entity_type_id);

  /**
   * Get all messages associated with this message queue item.
   *
   * The order of the messages is meaningful. The first successful message in
   * the list will terminate the remaining messages.
   *
   * @return \Drupal\courier\ChannelInterface[]
   *   An array of template entities.
   */
  public function getMessages();

  /**
   * Add a message to the message queue item.
   *
   * @param \Drupal\courier\ChannelInterface $message
   *   The message to add to the message queue item.
   *
   * @return \Drupal\courier\MessageQueueItemInterface
   *   Returns this message queue item for chaining.
   */
  public function addMessage(ChannelInterface $message);

  /**
   * Get options to pass to the message.
   *
   * @return array
   *   An array of options.
   */
  public function getOptions();

  /**
   * Set options to pass to the message.
   *
   * These options are passed to ChannelInterface::sendMessage() $options param.
   *
   * @param array $options
   *   An array of options.
   *
   * @return \Drupal\courier\MessageQueueItemInterface
   *   Returns this message queue item for chaining.
   */
  public function setOptions(array $options);

  /**
   * Returns the message queue item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the message queue item.
   */
  public function getCreatedTime();

  /**
   * Sets the message queue item creation timestamp.
   *
   * @param int $timestamp
   *   The message queue item creation timestamp.
   *
   * @return \Drupal\courier\MessageQueueItemInterface
   *   Returns this message queue item for chaining.
   */
  public function setCreatedTime($timestamp);

}
