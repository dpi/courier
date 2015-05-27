<?php

/**
 * @file
 * Contains \Drupal\courier\IdentityChannelManagerInterface.
 */

namespace Drupal\courier;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for identity channel manager.
 */
interface IdentityChannelManagerInterface {

  /**
   * Get IdentityChannel plugin ID bridging a identity and message combination.
   *
   * @param string $channel_type_id
   *   An channel entity type ID.
   * @param string $identity_type_id
   *   An identity entity type ID.
   *
   * @return string|NULL
   *   IdentityChannel plugin ID, or NULL if no plugin was found.
   */
  public function getCourierIdentityPluginID($channel_type_id, $identity_type_id);

  /**
   * Instantiate a CourierIdentity plugin instance.
   *
   * @param string $channel_type_id
   *   A channel entity type ID.
   * @param string $identity_type_id
   *   An identity entity type ID.
   *
   * @return \Drupal\courier\Plugin\IdentityChannel\IdentityChannelPluginInterface|NULL
   *   A CourierIdentity plugin instance, or NULL if no plugin was found.
   */
  public function getCourierIdentity($channel_type_id, $identity_type_id);

  /**
   * Gets all channel implementations.
   *
   * @return array
   *   An array of channel entity type IDs, keyed by identity entity type ID.
   */
  public function getChannels();

  /**
   * Get channels supported for an identity entity type.
   *
   * @param string $identity_type_id
   *   An identity entity type ID.
   *
   * @return array
   *   An array of channel entity type IDs.
   */
  public function getChannelsForIdentityType($identity_type_id);

  /**
   * Determine which channels an identity would like a message sent to.
   *
   * @param \Drupal\Core\Entity\EntityInterface $identity
   *   An identity entity.
   *
   * @return string[]
   *   IDs of entity types which implement \Drupal\courier\ChannelInterface.
   */
  public function getChannelsForIdentity(EntityInterface $identity);

  /**
   * Determines channel preference for an identity and sends a message.
   *
   * @param \Drupal\courier\TemplateCollectionInterface $template_collection
   *   A channel entity.
   * @param \Drupal\Core\Entity\EntityInterface $identity
   *   An identity entity.
   */
  public function sendMessage(TemplateCollectionInterface $template_collection, EntityInterface $identity);

}
