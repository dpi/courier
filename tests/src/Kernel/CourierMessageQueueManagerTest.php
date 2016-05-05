<?php

namespace Drupal\Tests\courier\Kernel;

use Drupal\courier\Entity\MessageQueueItem;
use Drupal\courier_test_message\Entity\TestMessage;
use Drupal\user\Entity\User;

/**
 * Tests message queue manager.
 *
 * @group courier
 */
class CourierMessageQueueManagerTest extends CourierKernelTestBase {

  public static $modules = ['courier_test_message', 'user'];

  /**
   * @var \Drupal\courier\Service\MessageQueueManagerInterface
   */
  protected $messageQueue;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['courier']);
    $this->installEntitySchema('courier_message_queue_item');
    $this->installEntitySchema('courier_test_message');
    $this->installEntitySchema('user');
    $this->messageQueue = $this->container->get('courier.manager.message_queue');

    $this->config('courier.settings')
      ->set('skip_queue', TRUE)
      ->set('channel_preferences', ['user' => ['courier_test_message']])
      ->save();
  }

  /**
   * Test message queue send.
   */
  public function testSendMessage() {
    $identity = User::create(['uid' => 1, 'name' => $this->randomMachineName()]);
    $message = TestMessage::create()
      ->setMessage($this->randomString());

    $mqi = MessageQueueItem::create()
      ->setIdentity($identity)
      ->addMessage($message);

    $result = $this->messageQueue->sendMessage($mqi);
    $this->assertTrue($message === $result);
    $this->assertEquals(1, count(\Drupal::state()->get('courier_test_message.messages', [])));
  }

}
