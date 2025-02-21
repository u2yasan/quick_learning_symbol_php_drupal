<?php

namespace Drupal\qls_ch9\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;

/**
 * @see \Drupal\Core\Form\FormBase
 */
class MultiSigConfirmForm extends FormBase {

  /**
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;

  /**
   * @var \Drupal\quicklearning_symbol\Service\TransactionService
   */
  protected $transactionService;

  /**
   * コンストラクタでServiceを注入
   */
  public function __construct(
    FacadeService $facade_service,
    TransactionService $transaction_service 
    ) {
      $this->facadeService = $facade_service;
      $this->transactionService = $transaction_service;
  }

  /**
   * createでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),         
      $container->get('quicklearning_symbol.transaction_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multi_sig_confirm_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'qls_ch9/multi_sig_confirm';

    $form['description'] = [
      '#type' => 'item',
      '#title' => '9.4 '.$this->t('マルチシグ送信の確認'),
    ];

    $form['aggregateTxHash'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Aggregate Transaction Hash'),
      '#description' => $this->t('Enter the hash of the aggregate transaction hash.'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    
    $transactionApi = $this->transactionService->getTransactionApi();

    $aggregateTxHash = $form_state->getValue('aggregateTxHash');
   
    // // ノードURLを設定
    // if ($network_type === 'testnet') {
    //   $networkType = new NetworkType(NetworkType::TESTNET);
    //   $node_url = 'http://sym-test-03.opening-line.jp:3000';
    // } elseif ($network_type === 'mainnet') {
    //   $networkType = new NetworkType(NetworkType::MAINNET);
    //   $node_url = 'http://sym-main-03.opening-line.jp:3000';
    // }
    // $config = new Configuration();
    // $config->setHost($node_url);
    // $client = \Drupal::httpClient();

    // $apiInstance = new TransactionRoutesApi($client, $config);

    $txInfo = $transactionApi->getConfirmedTransaction($aggregateTxHash);
    $txInfoArray = json_decode(json_encode($txInfo), true); // オブジェクトを配列に変換
    $prettyJson = json_encode($txInfoArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); // 整形されたJSON文字列を生成 
    $this->messenger()->addMessage($this->t('Tx info of the Aggregated Confirmed Tx: <pre>@result</pre>', ['@result' => $prettyJson]));

    // //アナウンス
    // try {
    //   $txInfo = $apiInstance->getConfirmedTransaction($aggregateTxHash);
    //   $txInfoArray = json_decode(json_encode($txInfo), true); // オブジェクトを配列に変換
    //   $prettyJson = json_encode($txInfoArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); // 整形されたJSON文字列を生成
    //   $this->messenger()->addMessage($this->t('Tx info of the Aggregated Confirmed Tx: <pre>@result</pre>', ['@result' => $prettyJson]));

    // } catch (Exception $e) {
    //   \Drupal::logger('qls_ch9')->error('Transaction Failed: @message', ['@message' => $e->getMessage()]);
    // }
    
  }
}
