<?php

namespace Drupal\qls_sect4\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Symbol\Models\EmbeddedTransferTransactionV1;
use SymbolSdk\Symbol\Models\AggregateCompleteTransactionV2;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedMosaic;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;
use SymbolSdk\Symbol\Models\Amount;
use SymbolSdk\Symbol\Models\UnresolvedAddress;

use Drupal\quicklearning_symbol\Service\TransactionService;
use Drupal\quicklearning_symbol\Service\TransactionStatusService;
use Drupal\quicklearning_symbol\Service\FacadeService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class AggregateTransferTransactionForm extends FormBase {

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'aggregate_transfer_transaction_form';
  }

  /**
   * FacadeServiceのインスタンス
   * 
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;

  /**
   * TransactionServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\TransactionService
   */
  protected $transactionService;

    /**
   * TransactionStatusServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\TransactionStatusService
   */
  protected $transactionStatusService;

  /**
   * コンストラクタでServiceを注入
   */
  public function __construct(
    FacadeService $facade_service,
    TransactionService $transaction_service,
    TransactionStatusService $transaction_status_service) {
      $this->facadeService = $facade_service;
      $this->transactionService = $transaction_service;
      $this->transactionStatusService = $transaction_status_service;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),
      $container->get('quicklearning_symbol.transaction_service'),
      $container->get('quicklearning_symbol.transaction_status_service'),
    );
  }

  /**
   * Build the Aggregate Transfer Transaction form.
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
      '#markup' => '4.6 '. $this->t('アグリゲートトランザクション'),
    ];
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('同一内容を一斉送信するサンプル'),
    ];

    $form['recipientAddresses'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Recipient Addresses'),
      '#description' => $this->t('Enter comma separated addresses.'),
      '#required' => TRUE,
    ];

    $form['sender_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Sender Private Key'),
      '#description' => $this->t('Enter the private key of the sender.'),
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Max: 1023 byte.'),
    ];

    // $form['feeMultiprier'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('feeMultiprier'),
    //   '#description' => $this->t('transaction size * feeMultiprier = transaction fee'),
    //   '#required' => TRUE,
    //   '#default_value' => '100',
    // ];

    $form['mosaicid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mosaic ID'),
      '#description' => $this->t('TESTNET XYM:72C0212E67A08BCE / MAINNET XYM:6BED913FA20223F8'),
      '#required' => TRUE,
      '#default_value' => '72C0212E67A08BCE',
    ];

    $form['amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#description' => $this->t('Enter the amount of the mosaic. (1 XYM = 1000000)'),
      '#required' => TRUE,
      '#default_value' => '1000000',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Make Aggregate Transaction'),
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
    
    $message = $form_state->getValue('message');
    if (mb_strlen($message, '8bit') > 1023) {
      $form_state->setErrorByName('message', $this->t('The message must be less equal than 1023 byte.'));
    }
  }

  /**
   * Implements a form submit handler.
   *
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $facade = $this->facadeService->getFacade();
    $networkType = $this->facadeService->getNetworkTypeObject();

    $message = $form_state->getValue('message');
    if($message){
      $messageData = "\0".$message;
    } else {
      $messageData = "";
    }

    $sender_pvtKey = $form_state->getValue('sender_pvtKey');
    // 秘密鍵からアカウント生成
    $senderKey = $facade->createAccount(new PrivateKey($sender_pvtKey));

    $mosaicid = '0x'. $form_state->getValue('mosaicid');
    $amount = $form_state->getValue('amount');

    // 受取人アドレス(送信先)
    $recipientAddressesCsv = $form_state->getValue('recipientAddresses');
    $recipientAddresses = explode(',', $recipientAddressesCsv);

    $innerTxs = [];
    foreach ($recipientAddresses as $recipientAddStr) {
      $recipientAddress = new UnresolvedAddress($recipientAddStr);
      // アグリゲートTxに含めるTxを作成
      $innerTxs[] = new EmbeddedTransferTransactionV1(
          network: $networkType,
          signerPublicKey: $senderKey->publicKey,
          recipientAddress: $recipientAddress,
          mosaics: [
            new UnresolvedMosaic(
              mosaicId: new UnresolvedMosaicId($mosaicid),
              amount: new Amount($amount)
            )
          ],
          message: $messageData,
        );
    }

    // $feeMultiprier = $form_state->getValue('feeMultiprier');
    
    // マークルハッシュの算出
    $merkleHash = $facade->hashEmbeddedTransactions($innerTxs);
    // アグリゲートTx作成
    $aggregateTx = new AggregateCompleteTransactionV2(
      network: $networkType,
      signerPublicKey: $senderKey->publicKey,
      deadline: new Timestamp($facade->now()->addHours(2)),
      transactionsHash: $merkleHash,
      transactions: $innerTxs
    );
    
    // $facade->setMaxFee($aggregateTx, $feeMultiprier); // 手数料
    
    $requiredCosignatures = 1; // 必要な連署者の数を指定
    if ($requiredCosignatures > count($aggregateTx->cosignatures)) {
      $calculatedCosignatures = $requiredCosignatures;
    } else {
      $calculatedCosignatures = count($aggregateTx->cosignatures);
    } 
    $sizePerCosignature = 8 + 32 + 64;
    $calculatedSize = $aggregateTx->size() -
      count($aggregateTx->cosignatures) * $sizePerCosignature +
      $calculatedCosignatures * $sizePerCosignature;
    $aggregateTx->fee = new Amount($calculatedSize * 100); // 手数料を設定

    // \Drupal::logger('qls_sect4')->notice('<pre>@object</pre>', ['@object' => print_r($aggregateTx, TRUE)]);  

    // 署名
    $sig = $senderKey->signTransaction($aggregateTx);
    $payload = $facade->attachSignature($aggregateTx, $sig);
    // \Drupal::logger('qls_sect4')->notice('<pre>@object</pre>', ['@object' => print_r($payload, TRUE)]); 
    

    // $config = new Configuration();
    // $config->setHost($node_url);
    // $client = \Drupal::httpClient();
    // $apiInstance = new TransactionRoutesApi($client, $config);

    // try {
    //   $result = $apiInstance->announceTransaction($payload);
    //   // return $result;
    //   $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));
    // } catch (\Exception $e) {
    //   \Drupal::logger('qls_sect4')->error('トランザクションの発行中にエラーが発生しました: @message', ['@message' => $e->getMessage()]);
    //   // throw $e;
    // }

    // try {
    //   // TransactionServiceを使ってトランザクションを発行
    //   $result = $this->transactionService->announceTransaction($network_type, $payload);
    //   $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));
 
    // } catch (\Exception $e) {
    //   $this->messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()]));
    // }
    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));

    sleep(3);
    $transactionStatusApi = $this->transactionStatusService->getTransactionStatusApi();
    $hash = $facade->hashTransaction($aggregateTx);
    $result = $transactionStatusApi->getTransactionStatus($hash);
    $this->messenger()->addMessage($this->t('Transaction Status: @result', ['@result' => $result]));

    sleep(30);
    $result = $transactionStatusApi->getTransactionStatus($hash);
    $this->messenger()->addMessage($this->t('Transaction Status: @result', ['@result' => $result]));
 
    // 4.4 確認
    // 4.4.1 ステータスの確認
    // ノードに受理されたトランザクションのステータスを確認
    // sleep(3);
    // アナウンススより先にステータスを確認しに行ってしまいエラーを返す可能性があるためのsleep
    
    
    // $txStatusApi = new TransactionStatusRoutesApi($client, $config);
    // try {
    //   $txStatus = $txStatusApi->getTransactionStatus($merkleHash);
    //   $this->messenger()->addMessage($this->t('Transaction Status: @txStatus', ['@txStatus' => $txStatus])); 
    //   \Drupal::logger('qls_sect4')->notice('<pre>@object</pre>', ['@object' => print_r($txStatus, TRUE)]); 
    // } catch (Exception $e) {
    //   // echo 'Exception when calling TransactionRoutesApi->announceTransaction:';
    //   // $e->getMessage();
    //   $this->messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()])); 
    // }
  
  }

}
