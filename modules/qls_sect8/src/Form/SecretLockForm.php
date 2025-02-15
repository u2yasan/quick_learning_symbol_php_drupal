<?php

namespace Drupal\qls_sect8\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolSdk\Symbol\Models\Hash256;
use SymbolSdk\Symbol\Models\LockHashAlgorithm;
use SymbolSdk\Symbol\Models\SecretLockTransactionV1;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\Amount;
use SymbolSdk\Symbol\Models\UnresolvedAddress;
use SymbolSdk\Symbol\Models\UnresolvedMosaic;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;
use SymbolSdk\Symbol\Models\BlockDuration;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;
use Drupal\quicklearning_symbol\Service\AccountService;


/**
 * Provides a form with two steps.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class SecretLockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'secret_lock_form';
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
   * The AccountService instance.
   *
   * @var \Drupal\quicklearing_symbol\Service\AccountService
   */
  protected $accountService;

  /**
   * Constructs the form.
   *
   * @param \Drupal\quicklearing_symbol\Service\AccountService $account_service
   *   The account service.
   */
  public function __construct(FacadeService $facade_service, 
    TransactionService $transaction_service, 
    AccountService $account_service) {
      $this->facadeService = $facade_service;
      $this->transactionService = $transaction_service;
      $this->accountService = $account_service;
  }

  public static function create(ContainerInterface $container) {
    // AccountService をコンストラクタで注入
    $form = new static(
        $container->get('quicklearning_symbol.facade_service'), 
        $container->get('quicklearning_symbol.transaction_service'),
        $container->get('quicklearning_symbol.account_service')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'qls_sect8/secret_lock';
  
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '8.2 '. $this->t('シークレットロック・シークレットプルーフ'),
    ];

    $form['step1'] = [
      '#type' => 'fieldset',
      '#title' => '8.2.1 '. $this->t('シークレットロック'),
    ];

    $form['step1']['proof'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proof'),
      '#description' => $this->t('解除用キーワード'),
      '#required' => TRUE,
      '#prefix' => '<div id="edit-proof-wrapper">', // ラッパー追加
      '#suffix' => '</div>',
    ];

    $form['step1']['generate_proof'] = [
      '#type' => 'button',
      '#value' => $this->t('解除用キーワード生成'),
      '#ajax' => [
        'callback' => '::generateProofCallback',
        'wrapper' => 'edit-proof-wrapper', // 更新対象
      ],
      '#limit_validation_errors' => [], // すべてのバリデーションをスキップ
    ];
    
    $form['step1']['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#description' => $this->t('ロック用キーワード'),
      '#required' => TRUE,
      '#prefix' => '<div id="edit-secret-wrapper">', // ラッパー追加
      '#suffix' => '</div>',
    ];
    
    $form['step1']['generate_secret'] = [
      '#type' => 'button',
      '#value' => $this->t('ロック用キーワード生成'),
      '#ajax' => [
        'callback' => '::generateSecretCallback',
        'wrapper' => 'edit-secret-wrapper', // 更新対象
      ],
      '#limit_validation_errors' => [],
    ];


    $form['step2'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('トランザクション作成・署名・アナウンス'),
    ];

    $form['step2']['originator_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Originator Private Key'),
      '#description' => $this->t('Enter the private key of the originator.'),
      '#required' => TRUE,
      // '#input' => TRUE, 
    ];

    $form['step2']['symbol_address'] = [
      '#markup' => '<div id="symbol_address">Symbol Address</div>',
    ];
    $form['step2']['symbol_address_hidden'] = [
      '#type' => 'hidden',
      // '#value' => '', // 初期値は空
      '#attributes' => [
        'id' => 'symbol-address-hidden', // カスタム ID を指定
      ],
    ];

    $form['step2']['recipientAddress'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipient Address'),
      '#description' => $this->t('Enter the address of the recipient. TESTNET: Start with T / MAINNET: Start with N'),
      '#required' => TRUE,
      '#default_value' => 'TAJZXDFDOCVYVID4S45BLPGSPLPFUQIAUO5PBIA',
    ];

    $form['step2']['mosaicid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mosaic ID'),
      '#description' => $this->t('TESTNET XYM:72C0212E67A08BCE / MAINNET XYM:6BED913FA20223F8'),
      '#required' => TRUE,
      '#default_value' => '72C0212E67A08BCE',
    ];

    $form['step2']['amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#description' => $this->t('Enter the amount of the mosaic. (1 XYM = 1000000)'),
      '#required' => TRUE,
      '#default_value' => '1000000',
    ];

    $form['step2']['blockDuration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block Duration'),
      '#description' => $this->t('Enter the Block duration.'),
      '#required' => TRUE,
      '#default_value' => '480',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Make Secret Lock Transaction"),
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $facade = $this->facadeService->getFacade();
    $networkType = $this->facadeService->getNetworkTypeObject();

    $originator_pvtKey = $form_state->getValue('originator_pvtKey');
    // \Drupal::logger('qls_sect8')->notice('originator_pvtKey: @originator_pvtKey', ['@originator_pvtKey' => $originator_pvtKey]); 
    $originatorKey = $facade->createAccount(new PrivateKey($originator_pvtKey));
    // \Drupal::logger('qls_sect8')->notice('signerPublicKey: @signerPublicKey', ['@signerPublicKey' => $originatorKey->publicKey]);
    
    $recipientAddStr = $form_state->getValue('recipientAddress');
    $recipientAddress = new UnresolvedAddress($recipientAddStr);
    // $accountApi = $this->accountService->getAccountApi();
    // $account_info = $accountApi->getAccountInfo($recipientAddStr);
    // // \Drupal::logger('qls_sect8')->notice('Account Info: @account_info', ['@account_info' => $account_info]);
    // $recipientAddress = $account_info['address'];
    // \Drupal::logger('qls_sect8')->notice('Recipient Address: @recipientAddress', ['@recipientAddress' => $recipientAddress]);

    $secret = $form_state->getValue('secret');
    // $proof = $form_state->getValue('proof');
    $mosaicid = '0x'.$form_state->getValue('mosaicid');
    $amount = $form_state->getValue('amount');
    $blockDuration = $form_state->getValue('blockDuration'); // ロック期間

    // シークレットロックTx作成
    $lockTx = new SecretLockTransactionV1(
      signerPublicKey: $originatorKey->publicKey,  // 署名者公開鍵
      deadline: new Timestamp($facade->now()->addHours(2)), 
      network: $networkType,
      mosaic: new UnresolvedMosaic(
        mosaicId: new UnresolvedMosaicId($mosaicid), 
        amount: new Amount($amount) 
      ),
      duration: new BlockDuration($blockDuration), //ロック期間
      hashAlgorithm: new LockHashAlgorithm(LockHashAlgorithm::SHA3_256), // ハッシュアルゴリズム
      secret: new Hash256($secret), // ロック用キーワード
      recipientAddress: $recipientAddress, // 解除時の転送先：
    );
    $facade->setMaxFee($lockTx, 100);  // 手数料

    // \Drupal::logger('qls_sect8')->notice('Secret Lock lockTx: @lockTx', ['@lockTx' => $lockTx]);
    // 署名
    $lockSig = $originatorKey->signTransaction($lockTx);
    $payload = $facade->attachSignature($lockTx, $lockSig);
    // \Drupal::logger('qls_sect8')->notice('Secret Lock Payload: @payload', ['@payload' => $payload]);
    // try {
    //   $result = $apiInstance->announceTransaction($payload);
    //   $this->messenger()->addMessage($this->t('Lock Transaction successfully announced: @result', ['@result' => $result]));
    //   // echo $result . PHP_EOL;
    // } catch (Exception $e) {
    //   echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
    // }
    // echo 'シークレットロックTxHash' . PHP_EOL;
    // echo $facade->hashTransaction($lockTx) . PHP_EOL;

    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Lock Transaction successfully announced: @result', ['@result' => $result])); 
    $this->messenger()->addMessage($this->t('Secret Lock TxHash: @hash', ['@hash' => $facade->hashTransaction($lockTx)]));  
    

    // sleep(1);

    // // シークレットプルーフTx作成
    // $proofTx = new SecretProofTransactionV1(
    //   signerPublicKey: $originatorKey->publicKey,  // 署名者公開鍵
    //   deadline: new Timestamp($facade->now()->addHours(2)), // 有効期限
    //   network: $networkType,
    //   hashAlgorithm: new LockHashAlgorithm(LockHashAlgorithm::SHA3_256), // ハッシュアルゴリズム
    //   secret: new Hash256($secret), // ロック用キーワード
    //   recipientAddress: $recipientAddress, // 解除時の転送先：
    //   proof: $proof, // 解除用キーワード
    // );
    // $facade->setMaxFee($proofTx, 100);  // 手数料

    // // 署名
    // $proofSig = $bobKey->signTransaction($proofTx);
    // $payload = $facade->attachSignature($proofTx, $proofSig);

    // try {
    //   $result = $apiInstance->announceTransaction($payload);
    //   echo $result . PHP_EOL;
    // } catch (Exception $e) {
    //   echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
    // }
    // echo 'シークレットプルーフTxHash' . PHP_EOL;
    // echo $facade->hashTransaction($proofTx) . PHP_EOL;

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
  public function generateProofCallback(array &$form, FormStateInterface $form_state) {
    // ランダムなキーワードを生成
    $proof = random_bytes(20);
  
    $form_state->setValue(['proof'], bin2hex($proof));

    // 更新対象フィールドの値を設定
    $form['step1']['proof']['#value'] = bin2hex($proof);

    return $form['step1']['proof']; // 必要な部分だけ返す
  }

  public function generateSecretCallback(array &$form, FormStateInterface $form_state) {
    // ランダムなキーワードを生成
    $proof = $form_state->getValue(['proof']);
    $secret = hash('sha3-256', $proof, true); // ロック用キーワード
    
    // 更新対象フィールドの値を設定
    $form['step1']['secret']['#value'] = bin2hex($secret);

    return $form['step1']['secret']; // 必要な部分だけ返す
  }

  


}
