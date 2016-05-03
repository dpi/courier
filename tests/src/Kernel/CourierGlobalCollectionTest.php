<?php

namespace Drupal\Tests\courier\Kernel;

use Drupal\courier\Entity\GlobalTemplateCollection;
use Drupal\courier\TemplateCollectionInterface;
use Drupal\courier_test_message\Entity\TestMessage;

/**
 * Tests Courier global template collections interaction with local templates.
 *
 * @group courier
 */
class CourierGlobalCollectionTest extends CourierKernelTestBase {

  public static $modules = ['courier_test_message', 'user'];

  /**
   * @var \Drupal\courier\Service\GlobalTemplateCollectionManagerInterface
   */
  protected $gtcService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('courier_template_collection');
    $this->installEntitySchema('courier_test_message');
    $this->installEntitySchema('courier_email');
    $this->installEntitySchema('user');
    $this->gtcService = $this->container->get('courier.manager.global_template_collection');
  }

  /**
   * Baseline functionality.
   *
   * Ensure referencing a global template collection will automatically create
   * a template collection.
   */
  public function testCreateTemplateCollection() {
    $content = $this->randomString();
    $gtc = GlobalTemplateCollection::create(['id' => $this->randomMachineName()])
      ->setTemplate('courier_test_message', ['message' => $content]);
    $gtc->save();

    $tc = $this->gtcService->getLocalCollection($gtc);
    $this->assertTrue($tc instanceof TemplateCollectionInterface);

    $this->assertEquals(2, count($tc->getTemplates()), 'Templates for courier_email and courier_test_message.');
    $this->assertEquals($content, $tc->getTemplate('courier_test_message')->getMessage());
  }

  /**
   * Make sure getting a template collection throws an exception because global
   * template collection is unsaved. Global template collection needs to be
   * saved, and have an ID, to associate in the key/value store.
   */
  public function testGlobalTemplateCollectionExceptionUnsaved() {
    $gtc = GlobalTemplateCollection::create(['id' => $this->randomMachineName()])
      ->setTemplate('courier_test_message', ['message' => $this->randomString()]);

    $this->setExpectedException('Drupal\courier\Exception\GlobalTemplateCollectionException');
    $this->gtcService->getLocalCollection($gtc);
  }

  /**
   * Ensure active config is loaded into template entity.
   */
  public function testGlobalTemplateCollectionLiveImportTemplate() {
    $old_message = $this->randomString();
    $gtc = GlobalTemplateCollection::create(['id' => $this->randomMachineName()])
      ->setTemplate('courier_test_message', ['message' => $old_message]);
    $gtc->save();

    // Instantiate template collection
    $tc = $this->gtcService->getLocalCollection($gtc);
    $template_id = $tc->getTemplate('courier_test_message')->id();

    $new_message = $this->randomString();
    $gtc
      ->setTemplate('courier_test_message', ['message' => $new_message])
      ->save();

    // Reset entity cache so hook_entity_load is triggered again.
    \Drupal::entityTypeManager()->getStorage('courier_test_message')->resetCache();

    $template = TestMessage::load($template_id);
    $this->assertEquals($new_message, $template->getMessage());
  }

  /**
   * Ensure individual template update saves to global template collection.
   */
  public function testTemplateUpdatesGlobalTemplateCollection() {
    $old_message = $this->randomString();
    $gtc = GlobalTemplateCollection::create(['id' => $this->randomMachineName()])
      ->setTemplate('courier_test_message', ['message' => $old_message]);
    $gtc->save();

    // Instantiate template collection
    $tc = $this->gtcService->getLocalCollection($gtc);
    $template_id = $tc->getTemplate('courier_test_message')->id();

    $new_message = $this->randomString();
    TestMessage::load($template_id)
      ->setMessage($new_message)
      ->save();

    // Reload global template collection.
    $gtc = GlobalTemplateCollection::load($gtc->id());
    $this->assertEquals($new_message, $gtc->getTemplate('courier_test_message')['message']);
  }

}
