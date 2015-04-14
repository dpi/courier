<?php

/**
 * @file
 * Contains \Drupal\courier\ChannelInterface.
 */

namespace Drupal\courier;

/**
 * Interface for message types.
 */
interface ChannelInterface {

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
