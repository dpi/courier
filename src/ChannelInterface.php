<?php

/**
 * @file
 * Contains \Drupal\courier\ChannelInterface.
 */

namespace Drupal\courier;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Defines an interface for channels (templates).
 */
interface ChannelInterface extends FieldableEntityInterface, TokenInterface {

  /**
   * Applies tokens to relevant fields.
   *
   * @return static
   *   Return this instance for chaining.
   */
  public function applyTokens();

  /**
   * Sends messages in bulk.
   *
   * @param \Drupal\courier\ChannelInterface[] $messages
   *   An array of messages.
   * @param array $options
   *   Miscellaneous options.
   *
   * @throws \Drupal\courier\Exception\ChannelFailure
   *   Throw if the message cannot be sent.
   */
  static public function sendMessages(array $messages, $options = []);

  /**
   * Sends this message.
   *
   * @param array $options
   *   Miscellaneous options to pass to the sender.
   */
  public function sendMessage(array $options = []);

  /**
   * Determine if there is enough data to transmit a message.
   *
   * Ideally some validation should also be done on the entity form.
   *
   * @return bool
   */
  public function isEmpty();

}
