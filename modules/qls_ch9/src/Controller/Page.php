<?php

namespace Drupal\qls_ch9\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\quicklearning_symbol\Utility\DescriptionTemplateTrait;

/**
 * Simple page controller for drupal.
 */
class Page implements ContainerInjectionInterface {

  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  public function getModuleName() {
    return 'qls_ch9';
  }

}
