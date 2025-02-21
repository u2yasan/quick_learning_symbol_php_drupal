<?php

namespace Drupal\qls_ch8\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolSdk\Symbol\Models\Hash256;
use SymbolSdk\Symbol\Models\LockHashAlgorithm;
use SymbolSdk\Symbol\Models\SecretProofTransactionV1;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedAddress;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;

/**
 * Provides a form with two steps.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class SecretProofForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'secret_proof_form';
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
   * Constructs the form.
   */
  public function __construct(FacadeService $facade_service, 
    TransactionService $transaction_service,) {
      $this->facadeService = $facade_service;
      $this->transactionService = $transaction_service;
  }

  public static function create(ContainerInterface $container) {
    $form = new static(
        $container->get('quicklearning_symbol.facade_service'),
        $container->get('quicklearning_symbol.transaction_service')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'qls_ch8/secret_lock';
  
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '8.2 '.$this->t('シークレットロック・シークレットプルーフ'),
    ];

    $form['step1'] = [
      '#type' => 'fieldset',
      '#title' => '8.2.2 '.$this->t('シークレットプルーフ'),
    ];

    $form['step1']['proof'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proof'),
      '#description' => $this->t('解除用キーワード'),
      '#required' => TRUE,
    ];
    
    $form['step1']['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#description' => $this->t('ロック用キーワード'),
      '#required' => TRUE,
    ];
    $form['step1']['recipientAddress'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipient Address'),
      '#description' => $this->t('Enter the address of the recipient. TESTNET: Start with T / MAINNET: Start with N'),
      '#required' => TRUE,
    ];
    

    $form['step2'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('トランザクション作成・署名・アナウンス'),
    ];

    $form['step2']['signer_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Signer Private Key'),
      '#description' => $this->t('Enter the private key of the signer.'),
      '#required' => TRUE,
    ];

    $form['step2']['symbol_address'] = [
      '#markup' => '<div id="symbol_address">Symbol Address</div>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Make Secret Proof Transaction"),
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
   
    $facade = $this->facadeService->getFacade();
    $networkType = $this->facadeService->getNetworkTypeObject();

    // $apiInstance = new TransactionRoutesApi($client, $config);
    // $receiptApiInstance = new ReceiptRoutesApi($client, $config);
    // $secretAipInstance = new SecretLockRoutesApi($client, $config);

    $signer_pvtKey = $form_state->getValue('signer_pvtKey');
    $signerKey = $facade->createAccount(new PrivateKey($signer_pvtKey));

    $recipientAddStr = $form_state->getValue('recipientAddress');
    $recipientAddress = new UnresolvedAddress($recipientAddStr);
    // $account_info = $this->accountService->getAccountInfo($node_url, $recipientAddStr);
    // $recipientAddress = $account_info['address'];

    $secret = $form_state->getValue(['secret']);
    $proof = $form_state->getValue(['proof']);


    // シークレットプルーフTx作成
    $proofTx = new SecretProofTransactionV1(
      signerPublicKey: $signerKey->publicKey,  // 署名者公開鍵
      deadline: new Timestamp($facade->now()->addHours(2)), // 有効期限
      network: $networkType,
      hashAlgorithm: new LockHashAlgorithm(LockHashAlgorithm::SHA3_256), // ハッシュアルゴリズム
      secret: new Hash256($secret), // ロック用キーワード
      recipientAddress: $recipientAddress, // 解除時の転送先：
      proof: $proof, // 解除用キーワード
    );
    $facade->setMaxFee($proofTx, 100);  // 手数料

     // 署名
    $proofSig = $signerKey->signTransaction($proofTx);
    $payload = $facade->attachSignature($proofTx, $proofSig);

    // try {
    //   $result = $apiInstance->announceTransaction($payload);
    //   // echo $result . PHP_EOL;
    // } catch (Exception $e) {
    //   echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
    // }
    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Lock Transaction successfully announced: @result', ['@result' => $result])); 
    
    // \Drupal::logger('qls_ch8')->notice('payload: @payload', ['@payload' => $payload]);
    // \Drupal::logger('qls_ch8')->notice('Secret Proof TxHash: @hash', ['@hash' => $facade->hashTransaction($proofTx)]);

    // sleep(30);

    /**
     * 結果の確認
     */
    // $txInfo = $apiInstance->getConfirmedTransaction($facade->hashTransaction($proofTx));
    // echo '承認確認' . PHP_EOL;
    // echo $txInfo . PHP_EOL;

  }

  /**
   * Provides custom validation handler for page 1.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function SecretLockFormValidate(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Ajax callback for generating a random keyword.
   */
  // public function generateProofCallback(array &$form, FormStateInterface $form_state) {
  //   // ランダムなキーワードを生成
  //   $proof = random_bytes(20);
  
  //   $form_state->setValue(['proof'], bin2hex($proof));

  //   // 更新対象フィールドの値を設定
  //   $form['step1']['proof']['#value'] = bin2hex($proof);

  //   return $form['step1']['proof']; // 必要な部分だけ返す
  // }

  // public function generateSecretCallback(array &$form, FormStateInterface $form_state) {
  //   // ランダムなキーワードを生成
  //   $proof = $form_state->getValue(['proof']);
  //   $secret = hash('sha3-256', $proof, true); // ロック用キーワード
    
  //   // 更新対象フィールドの値を設定
  //   $form['step1']['secret']['#value'] = bin2hex($secret);

  //   return $form['step1']['secret']; // 必要な部分だけ返す
  // }


}
