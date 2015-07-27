<?php

/**
 * @file
 * Contains \Drupal\courier\ChannelBase.
 */

namespace Drupal\courier;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines an base class for channels.
 */
abstract class ChannelBase extends ContentEntityBase implements ChannelInterface {

  /**
   * Token values keyed by token type.
   *
   * @var array
   */
  protected $tokens = [];

  /**
   * {@inheritdoc}
   */
  function getTokenValues() {
    return $this->tokens;
  }

  /**
   * {@inheritdoc}
   */
  function setTokenValue($token, $value) {
    $this->tokens[$token] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(array $options = []) {
    $this->sendMessages([$this], $options);
  }

}
