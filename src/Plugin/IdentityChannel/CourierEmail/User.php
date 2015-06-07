<?php

/**
 * @file
 * Contains \Drupal\courier\Plugin\IdentityChannel\CourierEmail\User.
 */

namespace Drupal\courier\Plugin\IdentityChannel\CourierEmail;


use Drupal\courier\Plugin\IdentityChannel\IdentityChannelPluginInterface;
use Drupal\courier\ChannelInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Supports core user entities.
 *
 * @IdentityChannel(
 *   id = "identity:user:courier_email",
 *   label = @Translation("Drupal user to courier_mail"),
 *   channel = "courier_email",
 *   identity = "user",
 *   weight = 10
 * )
 */
class User implements IdentityChannelPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function applyIdentity(ChannelInterface &$message, EntityInterface $identity) {
    /** @var \Drupal\user\UserInterface $identity */
    /** @var \Drupal\courier\EmailInterface $message */
    $message->setRecipientName($identity->label());
    $message->setEmailAddress($identity->getEmail());
  }

}
