<?php

namespace Drupal\Tests\courier_system\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\courier\Entity\GlobalTemplateCollection;
use Drupal\Tests\courier\Kernel\CourierKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests Courier system.
 *
 * @group courier_system
 */
class CourierSystemTest extends CourierKernelTestBase {

  use AssertMailTrait;
  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'system', 'courier_system'];

  /**
   * @var \Drupal\Core\Cron $cron
   */
  protected $cron;

  /**
   * List of supported mail ID's.
   * @var array
   */
  protected $mailIds;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['courier', 'courier_system']);
    $this->installSchema('system', ['sequences', 'queue']);
    $this->installEntitySchema('courier_message_queue_item');
    $this->installEntitySchema('courier_template_collection');
    $this->installEntitySchema('courier_email');
    $this->installEntitySchema('user');
    $this->config('system.mail')
      ->set('interface.default', 'test_mail_collector')
      ->save();
    $this->cron = $this->container->get('cron');
    $this->mailIds = [
      'register_admin_created',
      'register_no_approval_required',
      'register_pending_approval',
      'password_reset',
      'status_activated',
      'status_blocked',
      'cancel_confirm',
      'status_canceled',
    ];
  }

  /**
   * Test courier system override is off.
   */
  public function testNonOverride() {
    foreach ($this->mailIds as $id) {
      $this->config('user.settings')
        ->set('notify.' . $id, TRUE)
        ->save();
      $default_body = $this->randomMachineName();
      // Override the user.module template.
      $this->config('user.mail')
        ->set($id . '.body', $default_body)
        ->save();
      // Turn off Courier override.
      $this->config('courier_system.settings')
        ->set('override.user_' . $id, FALSE)
        ->save();

      $body = $this->randomMachineName();
      $this->createGlobalTemplateCollection('courier_system.user_' . $id, $body);

      // Simulate.
      _user_mail_notify($id, $this->createUser());

      $this->cron->run();

      // Email depth is two emails since some triggers will send email for user
      // and admin.
      $this->assertMailString('body', $default_body, 2, 'Body found in non override for ' . $id);
    }
  }

  /**
   * Test courier system override is on.
   */
  public function testOverride() {
    foreach ($this->mailIds as $id) {
      // Turn on the email.
      $this->config('user.settings')
        ->set('notify.' . $id, TRUE)
        ->save();
      $default_body = $this->randomMachineName();
      // Override the user.module template.
      $this->config('user.mail')
        ->set($id . '.body', $default_body)
        ->save();
      // Turn on Courier override.
      $this->config('courier_system.settings')
        ->set('override.user_' . $id, TRUE)
        ->save();

      $body = $this->randomMachineName();
      $this->createGlobalTemplateCollection('courier_system.user_' . $id, $body);

      // Simulate.
      _user_mail_notify($id, $this->createUser());

      $this->cron->run();
      $this->assertMailString('body', $body, 2, 'Body found in override for ' . $id);
    }
  }

  /**
   * Create a random user for testing.
   *
   * @return \Drupal\user\UserInterface
   *   A user entity.
   */
  protected function createUser() {
    $mail = $this->randomMachineName() . '@' . $this->randomMachineName();
    $account = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $mail,
    ]);
    $account->save();
    return $account;
  }

  /**
   * Create a global template collection and change its email body.
   *
   * @param $id
   *   The ID for the new global template collection.
   * @param $email_body
   *   Change the email template body.
   *
   * @return \Drupal\courier\Entity\GlobalTemplateCollectionInterface
   */
  protected function createGlobalTemplateCollection($id, $email_body) {
    $gtc = GlobalTemplateCollection::create(['id' => $id]);
    $gtc->save();
    $gtc->getTemplateCollection()
      ->getTemplate('courier_email')
      ->setSubject($this->randomMachineName())
      ->setBody($email_body)->save();
    return $gtc;
  }

}
