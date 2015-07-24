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
  public function applyTokens(array $tokens) {
    foreach ($tokens as $token => $value) {
      $this->tokens[$token] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  function getTokenValues($token = NULL) {
    if ($token) {
      return isset($this->tokens[$token]) ? $this->tokens[$token] : NULL;
    }
    else {
      return $this->tokens;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(array $options = []) {
    $this->sendMessages([$this], $options);
  }

}
