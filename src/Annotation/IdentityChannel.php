<?php

namespace Drupal\courier\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * A bridge applying identity information to a message.
 *
 * @Annotation
 */
class IdentityChannel extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The ID of an entity type implementing \Drupal\courier\ChannelInterface.
   *
   * @var string
   */
  public $channel;

  /**
   * The ID of an entity type representing an identity.
   *
   * @var string
   */
  public $identity;

  /**
   * The weight of the plugin.
   *
   * @var int
   */
  public $weight;

}
