<?php

namespace Drupal\courier\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for global template collection entities.
 */
interface GlobalTemplateCollectionInterface extends ConfigEntityInterface {

  /**
   * Get the local Template Collection for this global template collection.
   *
   * @return \Drupal\courier\TemplateCollectionInterface
   *   A template collection entity.
   *
   * @throws \Drupal\courier\Exception\GlobalTemplateCollectionException
   *   Thrown if this global template collection is unsaved.
   */
  function getTemplateCollection();

  /**
   * Get configuration for all template types.
   *
   * @return array
   *   Template configuration keyed by template entity type ID's.
   */
  public function getTemplates();

  /**
   * Get configuration for a single template type.
   *
   * @param string $entity_type_id
   *   A template entity type ID.
   *
   * @return mixed
   *   Mixed configuration for the template.
   */
  public function getTemplate($entity_type_id);

  /**
   * Set configuration for a template type.
   *
   * @param string $entity_type_id
   *   A template entity type ID.
   * @param mixed $content
   *   Mixed configuration for the template.
   *
   * @return static
   *   Return this global template collection.
   */
  public function setTemplate($entity_type_id, $content);

}
