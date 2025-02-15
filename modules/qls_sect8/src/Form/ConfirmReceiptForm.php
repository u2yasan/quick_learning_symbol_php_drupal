<?php

namespace Drupal\qls_sect8\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\Symbol\Models\ReceiptType;

// use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\ReceiptService;


/**
 * @see \Drupal\Core\Form\FormBase
 */
class ConfirmReceiptForm extends FormBase {

  /**
   * FacadeServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  // protected $facadeService;

  /**
   * ReceiptServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\ReceiptService
   */
  protected $receiptService;

  /**
   * Constructs the form.
   */
  public function __construct(//FacadeService $facade_service, 
    ReceiptService $receipt_service,) {
      // $this->facadeService = $facade_service;
      $this->receiptService = $receipt_service;
  }

  public static function create(ContainerInterface $container) {
    $form = new static(
        // $container->get('quicklearning_symbol.facade_service'),
        $container->get('quicklearning_symbol.receipt_service')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'confirm_receipt_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form['#attached']['library'][] = 'qls_sect8/confirm_receipt';
  
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('承認結果を確認'),
    ];

    $form['recipientAddress'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipient Address'),
      '#description' => $this->t('Enter the address of the recipient. TESTNET: Start with T / MAINNET: Start with N'),
      '#required' => TRUE,
    ];

    $form['receipt_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Receipt Type'),
      '#options' => [
        '4685' => 'Mosaic_Rental_Fee',
        '4942' => 'Namespace_Rental_Fee',
        '8515' => 'Harvest_Fee',
        '8776' => 'LockHash_Completed',
        '8786' => 'LockSecret_Completed',
        '9032' => 'LockHash_Expired',
        '9042' => 'LockSecret_Expired',
        '12616' => 'LockHash_Created',
        '12626' => 'LockSecret_Created',
        '16717' => 'Mosaic_Expired',
        '16718' => 'Namespace_Expired',
        '16974' => 'Namespace_Deleted',
        '20803' => 'Inflation',
        '57667' => 'Transaction_Group',
        '61763' => 'Address_Alias_Resolution',
        '62019' => 'Mosaic_Alias_Resolution',
      ],
      '#default_value' => '8786',
    ];
    // 8786: 'LockSecret_Completed' :ロック解除完了
    // 9042: 'LockSecret_Expired' ：ロック期限切れ



    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Check Receipt"),
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
   
    // $network_type = $form_state->getValue('network_type');
    // $facade = new SymbolFacade($network_type);
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
    // $receiptApiInstance = new ReceiptRoutesApi($client, $config);
    // $secretAipInstance = new SecretLockRoutesApi($client, $config);

    // $facade = $this->facadeService->getFacade();
    // $networkType = $this->facadeService->getNetworkTypeObject();

    $recipientAddStr = $form_state->getValue('recipientAddress');
    $receipt_type = $form_state->getValue('receipt_type');

    // $account_info = $this->accountService->getAccountInfo($node_url, $recipientAddStr);
    // $recipientAddress = $account_info['address'];

    /**
     * レシート検索
     */
    $receiptApi = $this->receiptService->getReceiptApi();
    $result = $receiptApi->searchReceipts(
      receipt_type: new ReceiptType($receipt_type),
      target_address:$recipientAddStr
    );
    // echo 'レシート' . PHP_EOL;
    // echo $result . PHP_EOL;
    $this->messenger()->addMessage($this->t('searchReceipts: <pre>@result</pre>', ['@result' => $result]));


  }

  /**
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function ConfirmReceiptFormValidate(array &$form, FormStateInterface $form_state) {
  }

}
