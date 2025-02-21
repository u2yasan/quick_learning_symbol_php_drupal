<?php

namespace Drupal\qls_ch6\Controller;

use Drupal\quicklearning_symbol\Utility\DescriptionTemplateTrait;

/**
 * Simple page controller for drupal.
 */
class Page {

  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  public function getModuleName() {
    return 'qls_ch6';
  }

}
