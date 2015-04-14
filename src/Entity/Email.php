<?php

/**
 * @file
 * Contains \Drupal\courier\Entity\Email.
 */

namespace Drupal\courier\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\courier\EmailInterface;
use Drupal\courier\ChannelInterface;
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
class Email extends ContentEntityBase implements EmailInterface, ChannelInterface {

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

  /**
   * {@inheritdoc}
   */
  static public function sendMessages(array $messages, $options = []) {
    /* @var \Drupal\courier\EmailInterface[] $messages */
    foreach ($messages as $message) {
      // @todo: Validate messages (ensure $this->email is set)
      $name = $message->name->value;
      $email = $message->mail->value;
      $email_to = !empty($name) ? "$name <$email>" : $email;

      $params = [
        'context' => [
          'subject' => $message->subject->value,
          'message' => $message->body->value,
        ],
      ];

      \Drupal::service('plugin.manager.mail')->mail(
        'system',
        'courier_email',
        $email_to,
        $message->language()->getId(),
        $params,
        NULL
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage($options = []) {
    return $this->sendMessages([$this], $options);
  }

}
