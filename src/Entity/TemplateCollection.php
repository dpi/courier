<?php


namespace Drupal\courier\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\courier\TemplateCollectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\courier\CourierContextInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\courier\ChannelInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\courier\TokenTrait;

/**
 * Defines a local template collection entity.
 *
 * @ContentEntityType(
 *   id = "courier_template_collection",
 *   label = @Translation("Local template collection"),
 *   base_table = "courier_template_collection",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class TemplateCollection extends ContentEntityBase implements TemplateCollectionInterface {

  use TokenTrait;

  /**
   * {@inheritdoc}
   */
  function getContext() {
    return $this->get('context')->entity;
  }

  /**
   * {@inheritdoc}
   */
  function setContext(CourierContextInterface $entity) {
    $this->set('context', ['entity' => $entity]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  function getOwner() {
    return $this->get('owner')->entity;
  }

  /**
   * {@inheritdoc}
   */
  function setOwner(EntityInterface $entity) {
    $this->set('owner', ['entity' => $entity]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  function getTemplate($channel_type_id) {
    foreach ($this->getTemplates() as $template) {
      if ($template->getEntityTypeId() == $channel_type_id) {
        return $template;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  function getTemplates() {
    return $this->templates->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  function setTemplate(ChannelInterface $template) {
    // Remove any existing templates with the same channel.
    $this->removeTemplate($template->getEntityTypeId());
    // Replace this line after https://www.drupal.org/node/2473931 :
    $this->templates[] = $template;
    // $this->templates->appendItem($template);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  function removeTemplate($channel_type_id) {
    foreach ($this->getTemplates() as $key => $template) {
      if ($channel_type_id == $template->getEntityTypeId()) {
        $this->templates->removeItem($key);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  function validateTokenValues() {
    if ($this->getContext()) {
      $required_tokens = $this->getContext()->getTokens();
      $existing_tokens = array_keys($this->getTokenValues());
      // Remove identity as it is supplied by courier.
      $key = array_search('identity', $required_tokens);
      unset($required_tokens[$key]);
      $missing_tokens = array_diff_key($required_tokens, $existing_tokens);
      if ($missing_tokens) {
        throw new \Exception(sprintf('Missing tokens required by courier context: %s', implode(', ', $missing_tokens)));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getTemplateCollectionForTemplate(ChannelInterface $template) {
    $ids = \Drupal::entityManager()
      ->getStorage('courier_template_collection')
      ->getQuery()
      ->condition('templates.target_type', $template->getEntityTypeId(), '=')
      ->condition('templates.target_id', $template->id(), '=')
      ->execute();

    return $ids ? static::load(reset($ids)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Template collection ID'))
      ->setDescription(t('The template collection ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    // Reference to a courier_context entity.
    // DER does not support string IDs (config entities).
    $fields['context'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Context for templates.'))
      ->setSetting('target_type', 'courier_context')
      ->setCardinality(1)
      ->setReadOnly(TRUE);

    // Owner entity, or null if it is a default template.
    $fields['owner'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The owner entity.'))
      ->setCardinality(1)
      ->setReadOnly(TRUE);

    $fields['templates'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Templates'))
      ->setDescription(t('Templates for this this collection.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setReadOnly(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    /** @var static[] $entities */
    foreach ($entities as $template_collection) {
      foreach ($template_collection->getTemplates() as $template) {
        $template->delete();
      }
    }

    parent::preDelete($storage, $entities);
  }

}
