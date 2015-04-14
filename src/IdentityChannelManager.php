<?php

/**
 * @file
 * Contains \Drupal\courier\IdentityChannelManager.
 */

namespace Drupal\courier;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of CourierIdentity plugins.
 */
class IdentityChannelManager extends DefaultPluginManager implements IdentityChannelManagerInterface, FallbackPluginManagerInterface {

  /**
   * Constructs a new identity channel manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/IdentityChannel', $namespaces, $module_handler, 'Drupal\courier\Plugin\IdentityChannel\IdentityChannelPluginInterface', 'Drupal\courier\Annotation\IdentityChannel');

    $this->alterInfo('courier_identity_channel_info');
    $this->setCacheBackend($cache_backend, 'courier_identity_channel_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = array()) {
    return 'broken';
  }

  /**
   * {@inheritdoc}
   */
  public function getCourierIdentity($message_type, $identity) {
    $definitions = $this->getDefinitions();

    foreach ($definitions as $plugin_id => $plugin) {
      if ($plugin_id != 'broken') {
        if (($plugin['channel'] == $message_type) && ($identity == $plugin['identity'])) {
          return $plugin_id;
        }
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getChannels() {
    $definitions = $this->getDefinitions();

    $identities = [];
    foreach ($definitions as $plugin_id => $plugin) {
      if ($plugin_id != 'broken') {
        $identities[$plugin['identity']][] = $plugin['channel'];
      }
    }

    return $identities;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentityChannels($identity) {
    $channels = $this->getChannels();
    return isset($channels[$identity]) ? $channels[$identity] : [];
  }

}
