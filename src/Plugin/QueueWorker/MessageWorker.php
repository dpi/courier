<?php

/**
 * @file
 * Contains \Drupal\courier\Plugin\QueueWorker\MessageWorker.
 */

namespace Drupal\courier\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\courier\Entity\MessageQueueItem;

/**
 * Triggers scheduled rules.
 *
 * @QueueWorker(
 *   id = "courier_message",
 *   title = @Translation("Courier message processor"),
 *   cron = {"time" = 30}
 * )
 */
class MessageWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   *
   * @param $data
   *   - integer $id: ID of a courier_message_queue_item entity.
   */
  public function processItem($data) {
    $message_queue = MessageQueueItem::load($data['id']);
    if ($message_queue) {
      /** @var \Drupal\courier\Service\MessageQueueManagerInterface $service */
      $service = \Drupal::service('courier.manager.message_queue');
      $service->sendMessage($message_queue);
    }
  }

}
