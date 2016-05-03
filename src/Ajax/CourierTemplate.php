<?php

namespace Drupal\courier\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines a CourierTemplate AJAX command.
 *
 * Command is received by `Drupal.AjaxCommands.prototype.courierTemplate`
 */
class CourierTemplate implements CommandInterface {

  /**
   * A template collection entity ID.
   *
   * @var int
   */
  protected $template_collection;

  /**
   * Channel entity type ID.
   *
   * @var string
   */
  protected $channel;

  /**
   * Operation to execute on client.
   *
   * @var string
   */
  protected $operation;

  /**
   * Constructs a \Drupal\courier\Ajax\CourierTemplate object.
   *
   * @param int $template_collection
   *   A template collection entity ID.
   * @param string $channel
   *   Channel entity type ID.
   * @param string $operation
   *   Operation to execute on client. Allowed values: 'open', 'close'.
   */
  public function __construct($template_collection, $channel, $operation) {
    $this->template_collection = $template_collection;
    $this->channel = $channel;
    $this->operation = $operation;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface::render().
   */
  public function render() {
    return [
      'command' => 'courierTemplate',
      'template_collection' => $this->template_collection,
      'channel' => $this->channel,
      'operation' => $this->operation,
    ];
  }

}
