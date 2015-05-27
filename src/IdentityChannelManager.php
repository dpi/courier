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
use Drupal\Core\Entity\EntityInterface;

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
  public function getCourierIdentityPluginID($channel_type_id, $identity_type_id) {
    $definitions = $this->getDefinitions();

    foreach ($definitions as $plugin_id => $plugin) {
      if ($plugin_id != 'broken') {
        if (($plugin['channel'] == $channel_type_id) && ($identity_type_id == $plugin['identity'])) {
          return $plugin_id;
        }
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCourierIdentity($channel_type_id, $identity_type_id) {
    if ($plugin_id = $this->getCourierIdentityPluginID($channel_type_id, $identity_type_id)) {
      return $this->createInstance($plugin_id);
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
  public function getChannelsForIdentityType($identity_type_id) {
    $channel_type_ids = $this->getChannels();
    return isset($channel_type_ids[$identity_type_id]) ? $channel_type_ids[$identity_type_id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelsForIdentity(EntityInterface $identity) {
    // @todo: Determine channel preference for $identity, or site default.
    // GH-2 | https://github.com/dpi/courier/issues/2
    return ['courier_email'];
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(TemplateCollectionInterface $template_collection, EntityInterface $identity) {
    foreach ($this->getChannelsForIdentity($identity) as $channel) {
      if ($template = $template_collection->getTemplate($channel)) {
        if ($plugin = $this->getCourierIdentity($channel, $identity->getEntityTypeId())) {
          $plugin->applyIdentity($template, $identity);
          $template->sendMessage();
        }
      }
    }
  }

}
