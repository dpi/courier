<?php

namespace Drupal\courier_message_composer\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\courier\Service\IdentityChannelManagerInterface;

/**
 * Checks if user can send to a channel.
 */
class ComposeAccessCheck implements AccessInterface {

  /**
   * The identity channel manager.
   *
   * @var \Drupal\courier\Service\IdentityChannelManagerInterface
   */
  protected $identityChannelManager;

  /**
   * Constructs a ComposeAccessCheck object.
   *
   * @param \Drupal\courier\Service\IdentityChannelManagerInterface $identity_channel_manager
   *   The identity channel manager.
   */
  public function __construct(IdentityChannelManagerInterface $identity_channel_manager) {
    $this->identityChannelManager = $identity_channel_manager;
  }

  /**
   * Checks if user can send to a channel.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $requirement = $route->getRequirement('_courier_compose');
    $channels_all = $this->identityChannelManager->getChannels();

    $channels = [];
    if ($requirement == '*') {
      // Check if user can send to *any* channel.
      $channels = array_keys($channels_all);
    }
    elseif ($courier_channel = $route_match->getParameter('courier_channel')) {
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
