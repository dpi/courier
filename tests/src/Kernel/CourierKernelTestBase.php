<?php

namespace Drupal\Tests\courier\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base kernel test.
 */
class CourierKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dynamic_entity_reference', 'filter', 'field', 'text', 'courier'];

}
