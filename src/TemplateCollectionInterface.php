<?php

/**
 * @file
 * Contains \Drupal\courier\TemplateCollectionInterface.
 */

namespace Drupal\courier;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a courier_template_collection entity.
 */
interface TemplateCollectionInterface extends ContentEntityInterface, TokenInterface {

  /**
   * Gets the context entity.
   *
   * @return \Drupal\courier\CourierContextInterface|NULL
   *   The context entity, or NULL if it does not exist.
   */
  function getContext();

  /**
   * Sets the context entity.
   *
   * @param \Drupal\courier\CourierContextInterface|NULL $entity
   *   A courier_context entity, or NULL to remove context.
   *
   * @return \Drupal\courier\TemplateCollectionInterface
   *   Return this object for chaining.
   */
  function setContext(CourierContextInterface $entity);

  /**
   * Gets the owner entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|NULL
   *   The owner entity, or NULL if it does not exist.
   */
  function getOwner();

  /**
   * Sets the owner entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|NULL $entity
   *   An entity, or NULL to set as global.
   *
   * @return \Drupal\courier\TemplateCollectionInterface
   *   Return this object for chaining.
   */
  function setOwner(EntityInterface $entity);

  /**
   * Get template with a channel entity type.
   *
   * @param string $channel_type_id
   *   A channel entity type ID.
   *
   * @return \Drupal\courier\ChannelInterface|NULL
   *   A message, or NULL.
   */
  function getTemplate($channel_type_id);

  /**
   * Get all templates associated with this collections.
   *
   * @return \Drupal\courier\ChannelInterface[]
   *   An array of template entities.
   */
  function getTemplates();

  /**
   * Sets a template for this collection.
   *
   * Collections can accept one of each channel entity type.
   *
   * @param \Drupal\courier\ChannelInterface $template
   *   A template entity.
   *
   * @return \Drupal\courier\TemplateCollectionInterface
   *   Return this object for chaining.
   */
  function setTemplate(ChannelInterface $template);

  /**
   * Removes a template with the channel entity type.
   *
   * @param string $channel_type_id
   *   A channel entity type ID.
   *
   * @return \Drupal\courier\TemplateCollectionInterface
   *   Return this object for chaining.
   */
  function removeTemplate($channel_type_id);

  /**
   * Ensures tokens specified by context have values in this collection.
   *
   * @throws \Exception
   *   Throws exception if there are missing values.
   */
  function validateTokenValues();

  /**
   * Locates the template collection which references a template.
   *
   * @param \Drupal\courier\ChannelInterface $template
   *   A template entity.
   *
   * @return \Drupal\courier\Entity\TemplateCollection|NULL
   *   A template collection entity, or NULL if the template is an orphan.
   */
  public static function getTemplateCollectionForTemplate(ChannelInterface $template);

}
