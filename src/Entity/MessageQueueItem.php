<?php

/**
 * @file
 * Contains \Drupal\courier\Entity\MessageQueueItem.
 */

namespace Drupal\courier\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\courier\MessageQueueItemInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\courier\ChannelInterface;

/**
 * Defines a courier_template_collection entity.
 *
 * @ContentEntityType(
 *   id = "courier_message_queue_item",
 *   label = @Translation("Message queue item"),
 *   base_table = "courier_message_queue_item",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class MessageQueueItem extends ContentEntityBase implements MessageQueueItemInterface {

  /**
   * {@inheritdoc}
   */
  public function getIdentity() {
    return $this->get('identity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setIdentity(EntityInterface $identity) {
    $this->set('identity', ['entity' => $identity]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  function getMessage($entity_type_id) {
    foreach ($this->getMessages() as $message) {
      if ($message->getEntityTypeId() == $entity_type_id) {
        return $message;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    return $this->messages->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function addMessage(ChannelInterface $message) {
    $this->messages[] = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->get('options')->first() ? $this->get('options')->first()->getValue() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->set('options', $options);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage() {
    $options = $this->getOptions();
    $channel_options = array_key_exists('channels', $options) ? $options['channels'] : [];
    unset($options['channels']);

    /** @var \Drupal\courier\IdentityChannelManagerInterface $icm */
    $icm = \Drupal::service('plugin.manager.identity_channel');

    // Instead of iterating over messages, get the identity' channel preferences
    // again. This ensures preference order is up to date since significant time
    // may have passed since adding to queue.
    $messages = [];
    foreach ($icm->getChannelsForIdentity($this->getIdentity()) as $channel) {
      if ($message = $this->getMessage($channel)) {
        $messages[] = $message;
      }
    }

    /** @var ChannelInterface[] $messages */
    foreach ($messages as $message) {
      $message_options = $options;
      // Transform options based on channel
      $channel = $message->getEntityTypeId();
      if (array_key_exists($channel, $channel_options)) {
        $message_options = array_merge($message_options, $channel_options[$channel]);
      }

      $t_args = [
        '@channel' => $channel,
        '@identity' => $this->getIdentity()->label(),
      ];

      try {
        $message::sendMessages([$message], $message_options);
        \Drupal::logger('courier')->info('Successfully sent @channel to @identity', $t_args);
        return $message;
      }
      catch (\Exception $e) {
        $t_args['@exception'] = $e->getMessage();
        \Drupal::logger('courier')->warning('Failed to send @channel to @identity: @exception', $t_args);
        continue;
      }

      break;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Message queue item ID'))
      ->setDescription(t('Message queue item ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Date of creation'))
      ->setDescription(t('The date the queue item was created.'))
      ->setRequired(TRUE);

    $fields['identity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Identity'))
      ->setDescription(t('Identity to send the message.'))
      ->setCardinality(1)
      ->setReadOnly(TRUE);

    $fields['messages'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Messages'))
      ->setDescription(t('Messages for this this queue item.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setReadOnly(TRUE);

    $fields['options'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Options'))
      ->setDescription(t('Options to pass to channels when sending.'))
      ->setRequired(TRUE);

    return $fields;
  }


  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    /** @var static[] $entities */
    foreach ($entities as $message_queue_item) {
      // Delete messages attached to this queue item.
      foreach ($message_queue_item->getMessages() as $message) {
        $message->delete();
      }
    }

    parent::preDelete($storage, $entities);
  }

}
