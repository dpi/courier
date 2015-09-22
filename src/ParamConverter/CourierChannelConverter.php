<?php

/**
 * @file
 * Contains \Drupal\courier\ParamConverter\CourierChannelConverter.
 */

namespace Drupal\courier\ParamConverter;

use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Provides upcasting for a courier channel entity type ID.
 */
class CourierChannelConverter implements ParamConverterInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EntityConverter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if ($definition = $this->entityManager->getDefinition($value, FALSE)) {
      if ($definition->isSubclassOf('\Drupal\courier\ChannelInterface')) {
        return $definition;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'courier_channel');
  }

}
