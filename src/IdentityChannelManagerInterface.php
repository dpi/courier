<?php

/**
 * @file
 * Contains \Drupal\courier\IdentityChannelManagerInterface.
 */

namespace Drupal\courier;

/**
 * Interface for identity channel manager.
 */
interface IdentityChannelManagerInterface {

  /**
   * Get IdentityChannel plugin ID bridging a identity and message combination.
   *
   * @param string $channel
   *   An entity type ID implementing \Drupal\courier\ChannelInterface.
   * @param string $identity
   *   An identity entity type ID.
   *
   * @return string|NULL
   *   IdentityChannel plugin ID, or NULL if no plugin was found.
   */
  public function getCourierIdentity($channel, $identity);

  /**
   * Gets all channel implementations.
   *
   * @return array
   *   Array of channel entity type IDs keyed by identity entity type ID.
   */
  public function getChannels();

  /**
   * Get channels supported for an identity type..
   *
   * @param string $identity
   *   An identity entity type ID.
   *
   * @return array
   *   An array of channel entity type IDs.
   */
  public function getIdentityChannels($identity);

}
