<?php

/**
 * @file
 * Contains \Drupal\courier\Entity\Email.
 */

namespace Drupal\courier\Entity;

use Drupal\courier\ChannelBase;
use Drupal\courier\EmailInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines storage for a composed email.
 *
 * @ContentEntityType(
 *   id = "courier_email",
 *   label = @Translation("Composed Email"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\courier\Form\EmailForm",
 *       "add" = "Drupal\courier\Form\EmailForm",
 *       "edit" = "Drupal\courier\Form\EmailForm",
 *       "delete" = "Drupal\courier\Form\EmailDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer courier_email",
 *   base_table = "courier_email",
 *   data_table = "courier_email_field_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "subject",
 *   },
 *   links = {
 *     "canonical" = "/courier/email/{courier_email}/edit",
 *     "edit-form" = "/courier/email/{courier_email}/edit",
 *     "delete-form" = "/courier/email/{courier_email}/delete",
 *   }
 * )
 */
class Email extends ChannelBase implements EmailInterface {

  /**
   * {@inheritdoc}
   */
  public function getEmailAddress() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmailAddress($mail) {
    $this->set('mail', ['value' => $mail]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipientName($name) {
    $this->set('name', ['value' => $name]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->get('subject')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($subject) {
    $this->set('subject', ['value' => $subject]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->get('body')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBody($body) {
    $this->set('body', ['value' => $body]);
  }

  /**
   * {@inheritdoc}
   *
   * @param array $options
   *   Miscellaneous options.
   *   - reply_to: reply-to email address, or leave unset to use site default.
   */
  static public function sendMessages(array $messages, $options = []) {
    /* @var \Drupal\courier\EmailInterface[] $messages */
    foreach ($messages as $message) {
      $tokens = $message->getTokenValues();
      // @todo: Validate messages (ensure $this->email is set)
      $name = $message->getRecipientName();
      $email = $message->getEmailAddress();
      $email_to = !empty($name) ? "$name <$email>" : $email;

      $message->setSubject(\Drupal::token()->replace($message->getSubject(), $tokens));
      $message->setBody(\Drupal::token()->replace($message->getBody(), $tokens));

      $params = [
        'context' => [
          'subject' => $message->getSubject(),
          'message' => $message->getBody(),
        ],
      ];

      /** @var \Drupal\Core\Mail\MailManagerInterface $mailman */
      $mailman = \Drupal::service('plugin.manager.mail');
      $mailman->mail(
        'system',
        'courier_email',
        $email_to,
        $message->language()->getId(),
        $params,
        array_key_exists('reply_to', $options) ? $options['reply_to'] : NULL
      );
    }
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Email ID'))
      ->setDescription(t('The email ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The email address to send this mail.'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recipient name'))
      ->setDescription(t('Nickname for the recipient.'));

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('The email subject.'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ]);

    $fields['body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Body'))
      ->setDescription(t('The main content of the email.'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 50,
      ]);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The email language code.'));

    return $fields;
  }

}
