<?php

namespace Drupal\Tests\courier\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\courier\Entity\MessageQueueItem;
use Drupal\courier\Entity\TemplateCollection;
use Drupal\user\Entity\User;

/**
 * Tests Courier manager.
 *
 * @group courier
 */
class CourierManagerTest extends CourierKernelTestBase {

  use AssertMailTrait;

  public static $modules = ['courier_test_message', 'user', 'system'];

  /**
   * @var \Drupal\courier\Service\CourierManagerInterface
   */
  protected $courierManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['courier']);
    $this->installEntitySchema('courier_message_queue_item');
    $this->installEntitySchema('courier_template_collection');
    $this->installEntitySchema('courier_email');
    $this->installEntitySchema('courier_test_message');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['queue']);
    $this->courierManager = $this->container->get('courier.manager');
  }

  /**
   * Test skip queue is off.
   */
  public function testSkipQueueOff() {
    $this->config('courier.settings')
      ->set('skip_queue', FALSE)
      ->set('channel_preferences', ['user' => ['courier_test_message']])
      ->save();

    $template_collection = TemplateCollection::create();
    $this->courierManager
      ->addTemplates($template_collection);
    $template_collection->save();

    $template_collection->getTemplate('courier_test_message')
      ->setMessage($this->randomString())
      ->save();

    $identity = User::create(['uid' => 1, 'name' => $this->randomMachineName(), 'mail' => 'user@email.tld']);
    $identity->save();

    $this->courierManager
      ->sendMessage($template_collection, $identity);

    $this->assertEquals(1, count(MessageQueueItem::loadMultiple()));
    $this->assertEquals(0, count(\Drupal::state()->get('courier_test_message.messages', [])));
  }

  /**
   * Test skip queue is on.
   */
  public function testSkipQueueOn() {
    $this->config('courier.settings')
      ->set('skip_queue', TRUE)
      ->set('channel_preferences', ['user' => ['courier_test_message']])
      ->save();

    $template_collection = TemplateCollection::create();
    $this->courierManager
      ->addTemplates($template_collection);
    $template_collection->save();

    $message = $this->randomString();
    $template_collection->getTemplate('courier_test_message')
      ->setMessage($message)
      ->save();

    $identity = User::create(['uid' => 1, 'name' => $this->randomMachineName(), 'mail' => 'user@email.tld']);
    $identity->save();

    $this->courierManager
      ->sendMessage($template_collection, $identity);

    $this->assertEquals(0, count(MessageQueueItem::loadMultiple()));
    $this->assertEquals(1, count(\Drupal::state()->get('courier_test_message.messages', [])));
  }

}
