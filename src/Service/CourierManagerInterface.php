<?php

/**
 * @file
 * Contains \Drupal\courier\Service\CourierManagerInterface.
 */

namespace Drupal\courier\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\courier\TemplateCollectionInterface;

/**
 * Interface for Courier manager.
 *
 * Handles template collections.
 */
interface CourierManagerInterface {

  /**
   * Adds all available channel types to the template collection.
   *
   * Does not replace templates for existing channel types.
   *
   * @param \Drupal\courier\TemplateCollectionInterface $template_collection
   *   A template collection entity.
   */
  public function addTemplates(TemplateCollectionInterface &$template_collection);

  /**
   * Prepares messages for an identity and and queues them for transmission.
   *
   * Once this method is executed, responsibility for transmission is passed
   * to Courier.
   *
   * @param \Drupal\courier\TemplateCollectionInterface $template_collection
   *   A template collection entity.
   * @param \Drupal\Core\Entity\EntityInterface $identity
   *   An identity entity.
   * @param array $options
   *   Optional options to pass to the channel.
   *   If the 'channels' key is specified, this will find a sub array with the
   *   key of the channel being transmitted to, and merge it into the base
   *   base array. The channels key will then be unset.
   *   e.g: Sending to the courier_email channel:
   * @code
   *   $options = [
   *     'my_option' => 123,
   *     'channels' => [
   *       'courier_email' => ['foo' => 456],
   *       'sms' => ['bar' => 679],
   *     ],
   *   ];
   *   // Will transform options into this array when sending to courier_email:
   *   $options = [
   *     'my_option' => 123,
   *     'foo' => 456,
   *   ];
   * @endcode
   *
   * @return \Drupal\courier\MessageQueueItemInterface|FALSE
   *   A message queue item entity, or FALSE if no messages could be generated.
   */
  public function sendMessage(TemplateCollectionInterface $template_collection, EntityInterface $identity, array $options = []);

}
