<?php

namespace Drupal\qls_sect6\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\quicklearning_symbol\Service\ReceiptService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class CheckReceiptForm extends FormBase {

  /**
   * Serviceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\ReceiptService
   */
  protected $receiptService;

  /**
   * コンストラクタ
   *
   * @param \Drupal\quicklearning_symbol\Service\ReceiptService $receiptService
   *  
   */
  public function __construct(ReceiptService $receiptService) {
    $this->receiptService = $receiptService;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */  
  public static function create($container) {
    return new static(
      $container->get('quicklearning_symbol.receipt_service')
    );
  }

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'check_receipt_form';
  }

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
      '#markup' => $this->t('レシートの参照'),
    ];

    $form['check_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Check Type'),
      '#description' => $this->t('Select either address or mosaic'),
      '#options' => [
        'address' => $this->t('Address'),
        'mosaic' => $this->t('Mosaic'),
      ],
      '#default_value' => 'address', // デフォルト選択を設定
      '#required' => TRUE,
    ];

    $form['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#description' => $this->t('リンクアナウンスしたトランザクションのブロック高'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check Receipt'),
    ];

    return $form;
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

    $checkType = $form_state->getValue('check_type');
    $height = $form_state->getValue('height');
   
    $receitApi = $this->receiptService->getReceiptApi();
    if($checkType == 'address'){ 
      $result = $receitApi->searchAddressResolutionStatements(height: $height);
    }else if($checkType == 'mosaic'){
      $result = $receitApi->searchMosaicResolutionStatements(height: $height);
    }

    $this->messenger()->addMessage($this->t('ResolutionStatements: <pre>@result</pre>', ['@result' => print_r($result, TRUE)]));

   
  }
}