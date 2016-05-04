<?php

namespace Drupal\courier_test_message\Entity;

use Drupal\courier\ChannelBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines storage for a test message.
 *
 * @ContentEntityType(
 *   id = "courier_test_message",
 *   label = @Translation("Test message"),
 *   admin_permission = "administer courier",
 *   base_table = "courier_test_message",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class TestMessage extends ChannelBase implements TestMessageInterface {

  /**
   * {@inheritdoc}
   */
  public function getUid() {
    return $this->get('uid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUid($uid) {
    $this->set('uid', ['value' => $uid]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->get('message')->value = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function applyTokens() {
    $tokens = $this->getTokenValues();
    $options = $this->getTokenOptions();
    $this->setMessage(\Drupal::token()->replace($this->getMessage(), $tokens, $options));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  function isEmpty() {
    return empty($this->getMessage());
  }

  /**
   * {@inheritdoc}
   */
  static public function sendMessages(array $messages, $options = []) {
    $state = \Drupal::state()->get('courier_test_message.messages', []);
    /* @var static[] $messages */
    foreach ($messages as $message) {
      $state[] = $message;
    }
    \Drupal::state()->set('courier_test_message.messages', $state);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(array $options = []) {
    $this->sendMessages([$this], $options);
  }

  /**
   * {@inheritdoc}
   */
  public function importTemplate($content) {
    $this->setMessage($content['message']);
  }

  /**
   * {@inheritdoc}
   */
  public function exportTemplate() {
    return [
      'message' => $this->getMessage(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Email ID'))
      ->setDescription(t('The email ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID.'))
      ->setSetting('target_type', 'user');

    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message'))
      ->setDescription(t('The message contents.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
      ]);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The message language code.'));

    return $fields;
  }

}
