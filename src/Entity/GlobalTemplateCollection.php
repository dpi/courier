<?php

namespace Drupal\courier\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines a sync-able template collection.
 *
 * @ConfigEntityType(
 *   id = "template_collection_global",
 *   label = @Translation("Global template collection"),
 *   config_prefix = "template_collection",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   }
 * )
 */
class GlobalTemplateCollection extends ConfigEntityBase implements GlobalTemplateCollectionInterface {

  /**
   * The unique identifier for this global template collection.
   *
   * @var integer
   */
  protected $id;

  /**
   * The provider for this global template collection.
   *
   * @var string|NULL
   */
  protected $provider;

  /**
   * The contexts for this global template collection.
   *
   * @todo Contexts are not implemented yet. Pending discussions in
   * https://github.com/dpi/courier/issues/29
   *
   * @var string[]
   */
  protected $contexts = [];

  /**
   * The template contents for this global template collection.
   *
   * @var array
   */
  protected $templates = [];

  /**
   * {@inheritdoc}
   */
  function getTemplateCollection() {
    /** @var \Drupal\courier\Service\GlobalTemplateCollectionManagerInterface $template_collection_manager */
    $template_collection_manager = \Drupal::service('courier.manager.global_template_collection');
    return $template_collection_manager->getLocalCollection($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplates() {
    return $this->templates;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate($entity_type_id) {
    $templates = $this->getTemplates();
    return isset($templates[$entity_type_id]['content']) ? $templates[$entity_type_id]['content'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setTemplate($entity_type_id, $content) {
    $this->templates[$entity_type_id] = [
      'type' => $entity_type_id,
      'content' => $content,
    ];
    return $this;
  }

}
