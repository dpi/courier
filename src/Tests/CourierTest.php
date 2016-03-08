<?php

/**
 * @file
 * Contains \Drupal\courier\Tests\CourierTest.
 */

namespace Drupal\courier\Tests;

use Drupal\courier\Entity\Email;
use Drupal\simpletest\KernelTestBase;
use Drupal\courier\Entity\TemplateCollection;
use Drupal\user\Entity\User;
use Drupal\courier\Entity\MessageQueueItem;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Courier test.
 *
 * @group courier
 */
class CourierTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'field', 'courier', 'dynamic_entity_reference', 'text', 'filter', 'entity_test'];

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $identity;

  /**
   * @var \Drupal\courier\Service\CourierManagerInterface
   */
  protected $courierManager;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->courierManager = \Drupal::service('courier.manager');
    $this->installSchema('system', ['queue']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('courier_email');
    $this->installEntitySchema('courier_template_collection');
    $this->installEntitySchema('courier_message_queue_item');
    $this->installEntitySchema('entity_test');
    $this->installConfig(['courier']);

    $this->config('system.mail')->set('interface.default', 'test_mail_collector')->save();

    $this->identity = User::create([
      'uid' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $this->identity
      ->setEmail('test@example.local')
      ->save();
  }

  /**
   * General API test.
   *
   * Creates a template collection and checks if user receives a message.
   */
  function testCourier() {
    // Owner is auto saved by entity reference field.
    $owner_entity = EntityTest::create();

    $template_collection = TemplateCollection::create()
      ->setOwner($owner_entity);
    $this->courierManager->addTemplates($template_collection);

    // Saving collection should auto save templates (via entity_reference).
    // See DynamicEntityReferenceItem::preSave().
    $templates = $template_collection->getTemplates();
    $this->assertTrue($templates[0]->isNew(), 'Template is not saved.');
    $this->assertEqual($template_collection->save(), SAVED_NEW, 'Saved template collection');
    $templates = $template_collection->getTemplates();

    /** @var \Drupal\courier\Entity\Email $courier_email */
    $courier_email = $templates[0];
    $this->assertFalse($courier_email->isNew(), 'Template is saved.');
    $this->assertTrue($courier_email instanceof Email, 'Template 0 is a courier_email.');

    // Message will not be added to queue if ->isEmpty()
    $courier_email->setSubject($this->randomMachineName());
    $courier_email->setBody('Greetings, [identity:label]');
    $courier_email->save();

    // MQI.
    $this->assertEqual(count(MessageQueueItem::loadMultiple()), 0, 'There are no message queue items.');

    $options = [];
    $this->courierManager->sendMessage($template_collection, $this->identity, $options);

    /** @var \Drupal\courier\MessageQueueItemInterface[] $mqi */
    $mqi = MessageQueueItem::loadMultiple();
    $this->assertEqual(count($mqi), 1, 'There is one message queue item.');
    /** @var \Drupal\courier\ChannelInterface[] $messages */
    $this->assertTrue($mqi[1]->getIdentity()->id() == $this->identity->id(), 'Identity is identical.');
    $this->assertTrue($mqi[1]->getOptions() == $options, 'Options are identical.');
    $messages = $mqi[1]->getMessages();
    $courier_email = $messages[0];
    $this->assertTrue($courier_email instanceof Email, 'Message 0 is a courier_email.');
    // Token replacement.
    $this->assertEqual($courier_email->getBody(), 'Greetings, ' . $this->identity->label());

    $this->assertTrue(empty(\Drupal::state()->get('system.test_mail_collector')), 'There are no mails.');

    /** @var \Drupal\Core\Cron $cron */
    $cron = \Drupal::service('cron');
    $cron->run();

    $mail_collector = \Drupal::state()->get('system.test_mail_collector');
    $this->assertEqual(count($mail_collector), 1, 'There is a mail.');
    $this->assertEqual($mail_collector[0]['to'], $this->identity->label() . ' <' . $this->identity->getEmail() . '>');
    $this->assertEqual($mail_collector[0]['subject'], $courier_email->getSubject());
    $this->assertEqual(trim($mail_collector[0]['body']), $courier_email->getBody());

    $this->assertEqual(count(MessageQueueItem::loadMultiple()), 0, 'There are no message queue items.');
    $this->assertFalse(entity_load($courier_email->getEntityTypeId(), $courier_email->id()), 'courier_email is deleted.');

    // Deleting owner entity deletes template collections.
    $owner_entity->delete();
    $this->assertEqual(count(TemplateCollection::loadMultiple()), 0, 'Deleted template collection.');
  }

}
