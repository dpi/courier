<?php

/**
 * @file
 * Contains \Drupal\courier\CourierContextInterface.
 */

namespace Drupal\courier;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a courier_context entity.
 */
interface CourierContextInterface extends ConfigEntityInterface {

  /**
   * Get token names.
   *
   * @return array
   *   An array of tokens names.
   */
  public function getTokens();

}
