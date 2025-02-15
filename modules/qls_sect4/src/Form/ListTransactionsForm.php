<?php

namespace Drupal\qls_sect4\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\Symbol\Address;

use Drupal\quicklearning_symbol\Service\TransactionService;

class ListTransactionsForm extends FormBase {

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'list_transactions_form';
  }
  /**
   * TransactionServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\TransactionService
   */
  protected $transactionService;

  /**
   * コンストラクタでTransactionServiceを注入
   */
  public function __construct(TransactionService $transaction_service) {
    $this->transactionService = $transaction_service;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.transaction_service')
    );
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#markup' => '4.5 '.$this->t('トランザクション履歴'),
    ];

    $form['raw_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Raw Address'),
      '#description' => 'ex:TAEVFNMAXX32XO5M4CQEA5PZJFYTEACJLXZNPNY '. $this->t('39文字（16進数）'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get Account Info'),
    ];

    return $form;
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
    $raw_address = $form_state->getValue('raw_address');
    if (strlen($raw_address) !=  39) {
      // Set an error for the form element with a key of "public_key".
      $form_state->setErrorByName('raw_address', $this->t('The raw_address must be 39 characters long.'));
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
 
    $raw_address = $form_state->getValue('raw_address');
    $address = new Address($raw_address);

    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->searchConfirmedTransactions(
      address: $address, 
      embedded: "true"
    );
    $this->messenger()->addMessage($this->t('Transaction History: <pre>@result</pre>', ['@result' => $result]));
 
    // try {
    //   $result = $this->transactionService->searchConfirmedTransactions($network_type, $address);

    //   $this->messenger()->addMessage($this->t('Transaction History: @result', ['@result' => $result]));
 
    // } catch (\Exception $e) {
    //   $this->messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()]));
    // }
    
  }

}
