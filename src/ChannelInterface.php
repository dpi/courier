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
   * Saves tokens internally.
   *
   * Token replacement is done when the message is sent.
   *
   * @param array $tokens
   *   An array of tokens keyed by token type.
   */
  public function applyTokens($tokens);

  /**
   * Gets token values added to this channel.
   *
   * @param string|NULL $token
   *   The token name, or all tokens if set to NULL.
   *
   * @return array|mixed
   *   Token values keyed by token type, or a single token value.
   */
  function getTokenValues($token = NULL);

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
