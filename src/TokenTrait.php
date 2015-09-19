<?php

/**
 * @file
 * Contains \Drupal\courier\TokenTrait.
 */

namespace Drupal\courier;

/**
 * Provides an implementation of TokenInterface.
 *
 * @see \Drupal\courier\TokenInterface
 */
trait TokenTrait {

  /**
   * Token values keyed by token type.
   *
   * @var array
   */
  protected $tokens = [];

  /**
   * Token options to pass to replace calls.
   *
   * @var array
   */
  protected $token_options = [];

  /**
   * Implements \Drupal\courier\TokenInterface::setTokenValue().
   */
  function setTokenValue($token, $value) {
    $this->tokens[$token] = $value;
    return $this;
  }

  /**
   * Implements \Drupal\courier\TokenInterface::getTokenValues().
   */
  function getTokenValues() {
    return $this->tokens;
  }

  /**
   * Implements \Drupal\courier\TokenInterface::getTokenOptions().
   */
  function getTokenOptions() {
    return $this->token_options;
  }

  /**
   * Implements \Drupal\courier\TokenInterface::setTokenOption().
   */
  function setTokenOption($option, $value) {
    $this->token_options[$option] = $value;
    return $this;
  }

}
