<?php

/**
 * @file
 * Contains \Drupal\courier\ChannelInterface.
 */

namespace Drupal\courier;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for channels (templates).
 */
interface ChannelInterface extends EntityInterface {

  /**
   * Sends messages in bulk.
   *
   * @param \Drupal\courier\ChannelInterface[] $messages
   *   An array of messages.
   * @param array $options
   *   Miscellaneous options.
   */
  static public function sendMessages(array $messages, $options = []);

  /**
   * Sends this message.
   *
   * @param array $options
   *   Miscellaneous options to pass to the sender.
   */
  public function sendMessage($options = []);

}
