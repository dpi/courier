<?php

/**
 * @file
 * Contains \Drupal\courier_message_composer\Access\ComposeAccessCheck.
 */

namespace Drupal\courier_message_composer\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\courier\Service\IdentityChannelManager;

/**
 * Checks new registrations are permitted on an event.
 */
class ComposeAccessCheck implements AccessInterface {

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManager
   */
  protected $identityChannelManager;

  /**
   * Constructs a ComposeAccessCheck object.
   *
   * @param \Drupal\courier\Service\IdentityChannelManager $identity_channel_manager
   *   The identity channel manager.
   */
  public function __construct(IdentityChannelManager $identity_channel_manager) {
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * Checks new registrations are permitted on an event.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $requirement = $route->getRequirement('_courier_compose');
    $channels_all = $this->identityChannelManager->getChannels();

    $channels = [];
    if ($requirement == '*') {
      $channels = array_keys($channels_all);
    }
    else if ($courier_channel = $route_match->getParameter('courier_channel')) {
      $channels = array_key_exists($courier_channel->id(), $channels_all) ? [$courier_channel->id()] : [];
    }

    foreach ($channels as $channel) {
      foreach ($channels_all[$channel] as $identity) {
        if ($account->hasPermission("courier_message_composer compose $channel to $identity")) {
          return AccessResult::allowed()
            ->cachePerPermissions();
        }
      }
    }

    return AccessResult::forbidden()
      ->cachePerPermissions();
  }

}
