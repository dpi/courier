<?php

namespace Drupal\courier\Tests;

use Drupal\Core\Url;
use Drupal\courier\Entity\MessageQueueItem;
use Drupal\simpletest\WebTestBase;

/**
 * Courier maintenance form web test.
 *
 * @group courier
 */
class CourierMaintenanceFormTest extends WebTestBase {

  public static $modules = ['courier'];

  /**
   * Test message queue items are deleted.
   */
  public function testMessageDelete() {
    $user = $this->drupalCreateUser(['administer courier']);
    $this->drupalLogin($user);

    MessageQueueItem::create(['created' => REQUEST_TIME + 3600])->save();
    MessageQueueItem::create(['created' => REQUEST_TIME + 3600])->save();
    MessageQueueItem::create(['created' => REQUEST_TIME + 3600])->save();
    MessageQueueItem::create(['created' => REQUEST_TIME - 3600])->save();
    MessageQueueItem::create(['created' => REQUEST_TIME - 3600])->save();

    $edit = [
      'delete_age' => 60,
    ];
    $this->drupalPostForm(Url::fromRoute('courier.admin.maintenance'), $edit, t('Delete messages'));
    $this->assertRaw('2 messages deleted.');
  }

}
