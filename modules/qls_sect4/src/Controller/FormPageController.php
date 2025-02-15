<?php

namespace Drupal\qls_sect4\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\qls_sect4\Form\GenerateAddressForm;
use Drupal\qls_sect4\Form\AggregateTransferTransactionForm;

class FormPageController extends ControllerBase {

  public function content() {
    return [
      'generate_address_from' => \Drupal::formBuilder()->getForm(GenerateAddressForm::class),
      'aggregete_transfer_transaction_form' => \Drupal::formBuilder()->getForm(AggregateTransferTransactionForm::class),
    ];
  }
}
