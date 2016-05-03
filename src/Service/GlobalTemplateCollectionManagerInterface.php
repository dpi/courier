<?php

namespace Drupal\courier\Service;

use Drupal\courier\ChannelInterface;
use Drupal\courier\TemplateCollectionInterface;
use Drupal\courier\Entity\GlobalTemplateCollectionInterface;

/**
 * Interface for the global template collection manager.
 */
interface GlobalTemplateCollectionManagerInterface {

  /**
   * Get the global template collection associated with a template.
   *
   * @param \Drupal\courier\ChannelInterface $template
   *   A template entity.
   *
   * @return \Drupal\courier\Entity\GlobalTemplateCollectionInterface|FALSE
   *   A global template collection entity, or FALSE if the template is not
   *   associated with a global template collection.
   */
  function getGlobalCollectionForTemplate(ChannelInterface $template);

  /**
   * Get the global template collection associated with a local template
   * collection.
   *
   * @param \Drupal\courier\TemplateCollectionInterface $template_collection
   *   A local template collection entity.
   *
   * @return \Drupal\courier\Entity\GlobalTemplateCollectionInterface|FALSE
   *   A global template collection entity, or FALSE if the template collection
   *   is not associated with a global template collection.
   */
  function getGlobalCollectionForLocalCollection(TemplateCollectionInterface $template_collection);

  /**
   * Create a global template collection and associate it with a template
   * collection.
   *
   * @param \Drupal\courier\TemplateCollectionInterface $template_collection
   *   A local template collection entity.
   * @param array $defaults
   *   Default values to add to the new global template collection. This value
   *   must contain a 'id' key which does not conflict with existing global
   *   template collections.
   *
   * @return \Drupal\courier\Entity\GlobalTemplateCollectionInterface
   *   A new and saved global template collection.
   *
   * @throws \Drupal\courier\Exception\GlobalTemplateCollectionException
   *   Thrown if passed template collection is unsaved.
   */
  function createGlobalCollectionForLocalCollection(TemplateCollectionInterface $template_collection, $defaults = []);

  /**
   * Create a global template collection and associate it with a template
   * collection.
   *
   * @param \Drupal\courier\Entity\GlobalTemplateCollectionInterface $global_template_collection
   *   A global template collection entity.
   *
   * @return \Drupal\courier\TemplateCollectionInterface
   *   A template collection entity.
   *
   * @throws \Drupal\courier\Exception\GlobalTemplateCollectionException
   *   Thrown if passed global template collection is unsaved.
   */
  function createLocalCollectionForGlobalCollection(GlobalTemplateCollectionInterface $global_template_collection);

  /**
   * Locate, and optionally instantiate, a local template collection to
   * associate a global template collection.
   *
   * @param \Drupal\courier\Entity\GlobalTemplateCollectionInterface $global_template_collection
   *   A global template collection entity.
   *
   * @return \Drupal\courier\TemplateCollectionInterface
   *   A template collection entity.
   *
   * @throws \Drupal\courier\Exception\GlobalTemplateCollectionException
   *   Thrown if passed global template collection is unsaved.
   */
  function getLocalCollection(GlobalTemplateCollectionInterface $global_template_collection);

  /**
   * Notify the manager that a template entity has been saved.
   *
   * This method will resynchronise template contents with the global template
   * collection configuration.
   *
   * @param \Drupal\courier\ChannelInterface $template
   *   A template entity.
   */
  function notifyTemplateChanged(ChannelInterface $template);

  /**
   * Imports active configuration from a global template collection into
   * a template entity at runtime.
   *
   * This will override message values from the database.
   *
   * @param \Drupal\courier\ChannelInterface $template
   *   A template entity.
   */
  function importFromGlobalCollection(ChannelInterface $template);

}
