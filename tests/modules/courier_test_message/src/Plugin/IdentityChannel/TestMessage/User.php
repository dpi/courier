<?php

namespace Drupal\courier_test_message\Plugin\IdentityChannel\TestMessage;


use Drupal\courier\Plugin\IdentityChannel\IdentityChannelPluginInterface;
use Drupal\courier\ChannelInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Supports core user entities.
 *
 * @IdentityChannel(
 *   id = "identity:user:test_message",
 *   label = @Translation("Drupal user to courier_test_message"),
 *   channel = "courier_test_message",
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
    /** @var \Drupal\courier_test_message\Entity\TestMessageInterface $message */
    $message->setUid($identity->id());
  }

}
