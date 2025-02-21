<?php

namespace Drupal\qls_ch4\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Facade\SymbolFacade;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class GenerateAddressForm extends FormBase {

   /**
   *
   * @param array $form
   *   Default form array structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Generate Random Addresses'),
    ];


    $form['number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#description' => $this->t('Enter 1 to 100'),
      '#required' => TRUE,
      '#default_value' => '10',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Make Addresses'),
    ];

    return $form;
  }

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'generate_address_form';
  }

  /**
   * Implements form validation.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
    $number = $form_state->getValue('number');
    if (!is_numeric($number) || $number < 1 || $number > 100) {
      $form_state->setErrorByName('number', $this->t('The number must be between 1 and 100.'));
    } 
  }

  /**
   * Implements a form submit handler.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
     * This would normally be replaced by code that actually does something
     * with the title.
     */
    // $network_type = $form_state->getValue('network_type');
    $config = \Drupal::config('quicklearning_symbol.settings');
    $network_type = $config->get('network_type');
    
    $facade = new SymbolFacade($network_type);
    
    $number = $form_state->getValue('number');
    $addresses = [];
    for ($i = 0; $i < $number; $i++) {
      $aKey = $facade->createAccount(PrivateKey::random());
      $addresses[] = $aKey->address;
    }
     
    // 配列をカンマで結合して1行の文字列を生成
    $csvAddresses = implode(',', $addresses);
    $this->messenger()->addMessage($this->t('Generated Addresses : @result', ['@result' => $csvAddresses]));
  }

}
