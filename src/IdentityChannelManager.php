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
    $channels = [];
    foreach ($this->getDefinitions() as $plugin_id => $plugin) {
      if ($plugin_id != 'broken') {
        $channel = $plugin['channel'];
        $identity_type = $plugin['identity'];
        if (!isset($channels[$channel]) || !in_array($identity_type, $channels[$channel])) {
          $channels[$channel][] = $identity_type;
        }
      }
    }
    return $channels;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentityTypes() {
    $identity_types = [];
    foreach ($this->getDefinitions() as $plugin_id => $plugin) {
      if ($plugin_id != 'broken') {
        if (!in_array($plugin['identity'], $identity_types)) {
          $identity_types[] = $plugin['identity'];
        }
      }
    }
    return $identity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelsForIdentityType($identity_type_id) {
    $channels = [];
    foreach ($this->getChannels() as $channel => $identity_types) {
      if (in_array($identity_type_id, $identity_types)) {
        $channels[] = $channel;
      }
    }
    return $channels;
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
    $template_collection->validateTokenValues();
    foreach ($this->getChannelsForIdentity($identity) as $channel) {
      if ($template = $template_collection->getTemplate($channel)) {
        if ($plugin = $this->getCourierIdentity($channel, $identity->getEntityTypeId())) {
          $template->applyTokens($template_collection->getTokenValues());
          // Identity
          $template->applyTokens([
            'identity' => $identity,
          ]);
          $plugin->applyIdentity($template, $identity);
          $template->sendMessage();
        }
      }
    }
  }

}
