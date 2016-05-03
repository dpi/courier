<?php

namespace Drupal\courier\Service;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\courier\Entity\GlobalTemplateCollection;
use Drupal\courier\Entity\TemplateCollection;
use Drupal\courier\ChannelInterface;
use Drupal\courier\Exception\GlobalTemplateCollectionException;
use Drupal\courier\TemplateCollectionInterface;
use Drupal\courier\Entity\GlobalTemplateCollectionInterface;

/**
 * The global template collection manager.
 */
class GlobalTemplateCollectionManager implements GlobalTemplateCollectionManagerInterface {

  /**
   * The key value store to use.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * The courier manager.
   *
   * @var \Drupal\courier\Service\CourierManagerInterface
   */
  protected $courierManager;

  /**
   * Constructs a global template collection manager.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key value store to use.
   * @param \Drupal\courier\Service\CourierManagerInterface $courier_manager
   *   The courier manager.
   */
  function __construct(KeyValueFactoryInterface $key_value_factory, CourierManagerInterface $courier_manager) {
    $this->keyValueStore = $key_value_factory->get('courier.template_collection_global');
    $this->courierManager = $courier_manager;
  }

  /**
   * {@inheritdoc}
   */
  function getGlobalCollectionForTemplate(ChannelInterface $template) {
    $template_collection = TemplateCollection::getTemplateCollectionForTemplate($template);
    if ($template_collection) {
      return $this->getGlobalCollectionForLocalCollection($template_collection);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function getGlobalCollectionForLocalCollection(TemplateCollectionInterface $template_collection) {
    $key = array_search($template_collection->id(), $this->keyValueStore->getAll(), TRUE);
    if ($key !== FALSE) {
      $gtc = GlobalTemplateCollection::load($key);
      if ($gtc) {
        return $gtc;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function createGlobalCollectionForLocalCollection(TemplateCollectionInterface $template_collection, $defaults = []) {
    if ($template_collection->isNew()) {
      throw new GlobalTemplateCollectionException('Template collection must be saved to instantiate a new global template collection.');
    }

    $global_template_collection = GlobalTemplateCollection::create($defaults);
    foreach ($template_collection->getTemplates() as $template) {
      $contents = $template->exportTemplate();
      $global_template_collection->setTemplate($template->getEntityTypeId(), $contents);
    }

    $global_template_collection->save();
    $this->keyValueStore->set($global_template_collection->id(), $template_collection->id());

    return $global_template_collection;
  }

  /**
   * {@inheritdoc}
   */
  public function createLocalCollectionForGlobalCollection(GlobalTemplateCollectionInterface $global_template_collection) {
    if ($global_template_collection->isNew()) {
      throw new GlobalTemplateCollectionException('Global template collection must be saved to instantiate a new template collection.');
    }

    $template_collection = TemplateCollection::create();
    $this->courierManager->addTemplates($template_collection);

    // Load in the configuration from this config.
    foreach ($global_template_collection->getTemplates() as $template_config) {
      $template = $template_collection->getTemplate($template_config['type']);
      if ($template) {
        $template->importTemplate($template_config['content']);
      }
    }

    $template_collection->save();
    $this->keyValueStore->set($global_template_collection->id(), $template_collection->id());

    return $template_collection;
  }

  /**
   * {@inheritdoc}
   */
  function getLocalCollection(GlobalTemplateCollectionInterface $global_template_collection) {
    $template_collection_id = $this->keyValueStore
      ->get($global_template_collection->id());

    if ($template_collection_id) {
      $template_collection = TemplateCollection::load($template_collection_id);
      if ($template_collection) {
        return $template_collection;
      }
    }

    return $this->createLocalCollectionForGlobalCollection($global_template_collection);
  }

  /**
   * {@inheritdoc}
   */
  function notifyTemplateChanged(ChannelInterface $template) {
    $gtc = $this->getGlobalCollectionForTemplate($template);
    if ($gtc) {
      $contents = $template->exportTemplate();
      $gtc->setTemplate($template->getEntityTypeId(), $contents);
      $gtc->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  function importFromGlobalCollection(ChannelInterface $template) {
    $gtc = $this->getGlobalCollectionForTemplate($template);
    if ($gtc) {
      $content = $gtc->getTemplate($template->getEntityTypeId());
      if (isset($content)) {
        $template->importTemplate($content);
      }
    }
  }

}
