<?php

/**
 * @file
 * Contains \Drupal\courier\Plugin\IdentityChannel\Broken.
 */

namespace Drupal\courier\Plugin\IdentityChannel;


use Drupal\courier\ChannelInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Fallback plugin for missing IdentityChannel plugins.
 *
 * @IdentityChannel(
 *   id = "broken",
 *   label = @Translation("Broken/Missing")
 * )
 */
class Broken implements IdentityChannelPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function applyIdentity(ChannelInterface &$message, EntityInterface $identity) {}

}
