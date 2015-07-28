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
    if ($message_queue = MessageQueueItem::load($data['id'])) {
      $message_queue->sendMessage();
      $message_queue->delete();
    }
  }

}
