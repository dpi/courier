<?php

/**
 * @file
 * Contains \Drupal\courier\CourierContextInterface.
 */

namespace Drupal\courier;

/**
 * Provides an interface defining a courier_context entity.
 */
interface CourierContextInterface {

  /**
   * Get token names.
   *
   * @return array
   *   An array of tokens names.
   */
  public function getTokens();

}
