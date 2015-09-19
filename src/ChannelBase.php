<?php

/**
 * @file
 * Contains \Drupal\courier\ChannelBase.
 */

namespace Drupal\courier;

use Drupal\Core\Entity\ContentEntityBase;

/**
 * Defines an base class for channels.
 */
abstract class ChannelBase extends ContentEntityBase implements ChannelInterface {

  use TokenTrait;

  /**
   * {@inheritdoc}
   */
  public function sendMessage(array $options = []) {
    $this->sendMessages([$this], $options);
  }

}
