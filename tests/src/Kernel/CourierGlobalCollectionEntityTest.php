<?php

namespace Drupal\Tests\courier\Kernel;

use Drupal\courier\Entity\GlobalTemplateCollection;
use Drupal\courier\Entity\TemplateCollection;

/**
 * Tests Courier global template collections entities.
 *
 * @group courier
 */
class CourierGlobalCollectionEntityTest extends CourierKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('courier_template_collection');
    $this->installEntitySchema('courier_email');
  }

  /**
   * Test template contents.
   */
  public function testTemplateContents() {
    $gtc = GlobalTemplateCollection::create(['id' => $this->randomMachineName()]);
    $contents = ['message' => $this->randomString()];
    $gtc->setTemplate('courier_test_message', $contents);
    $this->assertEquals($contents, $gtc->getTemplate('courier_test_message'));
  }

  /**
   * Test local template collection was instantiated.
   *
   * Tests the effect of the method. Details of the method are in the global
   * template manager service tests.
   */
  public function testGetTemplateCollection() {
    $gtc = GlobalTemplateCollection::create(['id' => $this->randomMachineName()]);
    $gtc->save();

    $this->assertEquals(0, count(TemplateCollection::loadMultiple()));
    $gtc->getTemplateCollection();
    $this->assertEquals(1, count(TemplateCollection::loadMultiple()));
  }

}
