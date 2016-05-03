<?php

namespace Drupal\Tests\courier\Kernel;

use Drupal\courier\Entity\GlobalTemplateCollection;
use Drupal\courier\TemplateCollectionInterface;

/**
 * Tests Courier global template collections with invalid template types.
 *
 * This is in a different test because it is invalid schema.
 *
 * @group courier
 */
class CourierGlobalCollectionInvalidTemplateTest extends CourierKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

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
    $this->installEntitySchema('courier_email');
    $this->gtcService = $this->container->get('courier.manager.global_template_collection');
  }

  /**
   * Test that a non-existent template type does not cause problems creating a
   * new template collection.
   */
  public function testInvalidTemplate() {
    $gtc = GlobalTemplateCollection::create(['id' => 'foobar'])
      ->setTemplate('courier_email', ['subject' => 'MySubject22', 'body' => 'MyBody432423'])
      // need to turn off strict schema check to test non existent.
      ->setTemplate($this->randomMachineName(), []);
    $gtc->save();

    $tc = $this->gtcService->getLocalCollection($gtc);
    $this->assertTrue($tc instanceof TemplateCollectionInterface);
    $this->assertEquals(1, count($tc->getTemplates()));
  }

}
