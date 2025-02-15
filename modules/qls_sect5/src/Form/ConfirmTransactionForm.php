<?php

namespace Drupal\qls_sect5\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\quicklearning_symbol\Service\TransactionService;

/**
 *
 * This example demonstrates a simple form with a single text input element. We
 * extend FormBase which is the simplest form base class used in Drupal.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ConfirmTransactionForm extends FormBase {

  /**
   * TransactionServiceのインスタンス
   *
   * @var Drupal\quicklearning_symbol\Service\TransactionService
   */
  protected $transactionService;

  /**
   * コンストラクタでServiceを注入
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

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'confirm_transaction_form';
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
    $form['#prefix'] = '<div id="transaction-info-wrapper">';
    $form['#suffix'] = '</div>';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => '5.2.1 '. $this->t('送信確認'),
    ];

    $form['transaction_hash'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Transaction Hash'),
      '#description' => $this->t('Transfer Transaction Hash'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxSubmitCallback',
        'wrapper' => 'transaction-info-wrapper',
      ],
    ];

     // Placeholder for displaying results
     $form['output'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'transaction-info-output'],
      '#markup' => '',
    ];

    return $form;
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  // public function validateForm(array &$form, FormStateInterface $form_state) {
  //   $title = $form_state->getValue('network_type');
  //   if (strlen($title) < 5) {
  //     // Set an error for the form element with a key of "title".
  //     $form_state->setErrorByName('title', $this->t('The title must be at least 5 characters long.'));
  //   }
  // }
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $txHash = $form_state->getValue('transaction_hash');
    if (strlen($txHash) !=  64) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('transaction_hash', $this->t('Transaction Hash key must be 64 characters long.'));
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // AJAX only: No full submit processing required.
  }

  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {

    $txHash = $form_state->getValue('transaction_hash');

    $transactionApi = $this->transactionService->getTransactionApi();
    $txInfo = $transactionApi->getConfirmedTransaction($txHash);
    $this->messenger()->addMessage($this->t('Transaction History: @txInfo', ['@txInfo' => $txInfo]));
   
    // \Drupal::logger('qls_sect5')->notice('txInfo:<pre>@object</pre>', ['@object' => print_r($txInfo, TRUE)]); 

    // $this->messenger()->addMessage($this->t('You specified a network_type of %network_type.', ['%network_type' => $network_type]));

    // フォームステートにデータを設定
    $meta = $txInfo['meta'];
    $meta_container = $this->getProtectedContainer($meta);
    // \Drupal::logger('qls_sect5')->notice('meta container:<pre>@object</pre>', ['@object' => print_r($container, TRUE)]); 
 
    $transaction = $txInfo['transaction'];
    $transaction_container = $this->getProtectedContainer($transaction);
    // \Drupal::logger('qls_sect5')->notice('transaction container:<pre>@object</pre>', ['@object' => print_r($transaction_container, TRUE)]); 
    // $form_state->set('meta', $meta);
    // $form_state->set('transaction', $transaction);
    
    // Format the output
    $output = '<h2>Meta Information</h2><ul>';
    foreach ($meta_container as $key => $value) {
      $output .= '<li>' . $this->safeHtmlspecialchars($key) . ': ' . $this->safeHtmlspecialchars($value) . '</li>';
    }
    $output .= '</ul>';

    $output .= '<h2>Transaction Details</h2><ul>';
   
    $output .= $this->renderTransactionDetails($transaction_container);

    // foreach ($transaction_container as $key => $value) {
    //   if (is_array($value)) {
    //     $output .= '<li>' . htmlspecialchars($key) . ': <pre>' . htmlspecialchars(print_r($value, TRUE)) . '</pre></li>';
    //   } else {
    //     $output .= '<li>' . htmlspecialchars($key) . ': ' . htmlspecialchars($value) . '</li>';
    //   }
    // }
    // $output .= '</ul>';

    // Update the output container
    $form['output']['#markup'] = $output;

    return $form;
  }

  public function getProtectedContainer($object) {
    // リフレクションクラスを使ってプロパティにアクセス
    $reflectionClass = new \ReflectionClass($object);
    $property = $reflectionClass->getProperty('container');
    $property->setAccessible(true); // プロパティのアクセス制限を解除

    // 値を取得
    return $property->getValue($object);
  }

  public function renderTransactionDetails($data) {
    $output = '<ul>';
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            // 配列の場合は再帰的に処理
            $output .= '<li>' . $this->safeHtmlspecialchars($key) . ': <pre>' . $this->safeHtmlspecialchars(print_r($value, TRUE)) . '</pre></li>';
        } elseif (is_object($value)) {
            // オブジェクトの場合はリフレクションでプロパティを取得
            $output .= '<li>' . $this->safeHtmlspecialchars($key) . ': <ul>';
            $output .= $this->renderObjectDetails($value);
            $output .= '</ul></li>';
        } else {
            // 単純な値の場合は直接出力
            $output .= '<li>' . $this->safeHtmlspecialchars($key) . ': ' . $this->safeHtmlspecialchars($value) . '</li>';
        }
    }
    $output .= '</ul>';
    return $output;
}

  public function renderObjectDetails($object) {
    $output = '';
    $reflection = new \ReflectionClass($object);
    $properties = $reflection->getProperties();
    foreach ($properties as $property) {
        $property->setAccessible(true); // アクセス可能にする
        $name = $property->getName();
        $value = $property->getValue($object);

        if (is_array($value)) {
            $output .= '<li>' . $this->safeHtmlspecialchars($name) . ': <pre>' . $this->safeHtmlspecialchars(print_r($value, TRUE)) . '</pre></li>';
        } elseif (is_object($value)) {
            // オブジェクトの場合は再帰的に処理
            $output .= '<li>' . $this->safeHtmlspecialchars($name) . ': <ul>';
            $output .= $this->renderObjectDetails($value);
            $output .= '</ul></li>';
        } else {
            $output .= '<li>' . $this->safeHtmlspecialchars($name) . ': ' . $this->safeHtmlspecialchars($value) . '</li>';
        }
    }
    return $output;
  }

  public function safeHtmlspecialchars($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
  }
}
