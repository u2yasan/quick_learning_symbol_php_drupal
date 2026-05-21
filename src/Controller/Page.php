<?php

namespace Drupal\quicklearning_symbol\Controller;

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
    return 'quicklearning_symbol';
  }

}
