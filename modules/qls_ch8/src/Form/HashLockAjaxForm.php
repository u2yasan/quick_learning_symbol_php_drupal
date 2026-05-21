<?php

namespace Drupal\qls_ch8\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Render\RenderContext;

use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Facade\SymbolFacade;

use SymbolRestClient\Api\TransactionRoutesApi;
use SymbolSdk\Symbol\Models\EmbeddedTransferTransactionV1;
use SymbolSdk\Symbol\Models\NamespaceRegistrationTransactionV1;
use SymbolSdk\Symbol\Models\AggregateBondedTransactionV2;
use SymbolSdk\Symbol\Models\Hash256;
use SymbolSdk\Symbol\Models\HashLockTransactionV1;
use SymbolSdk\Symbol\Models\PublicKey;
use SymbolSdk\Symbol\Models\NetworkType;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\Amount;
use SymbolSdk\Symbol\Models\UnresolvedAddress;
use SymbolSdk\Symbol\Models\UnresolvedMosaic;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;
use SymbolSdk\Symbol\Models\BlockDuration;
use SymbolSdk\Symbol\Models\NamespaceId;
use SymbolSdk\Symbol\Models\NamespaceRegistrationType;
use SymbolSdk\Symbol\IdGenerator;

use Drupal\quicklearning_symbol\Service\AccountService;
use Drupal\quicklearning_symbol\Service\SymbolApiClientFactory;

use GMP;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class HashLockAjaxForm extends FormBase {

  /**
   * The AccountService instance.
   *
   * @var \Drupal\quicklearing_symbol\Service\AccountService
   */
  protected $accountService;

  /**
   * The Symbol API client factory.
   *
   * @var \Drupal\quicklearning_symbol\Service\SymbolApiClientFactory
   */
  protected $apiClientFactory;

  /**
   * Constructs the form.
   *
   * @param \Drupal\quicklearing_symbol\Service\AccountService $account_service
   *   The account service.
   */
  public function __construct(AccountService $account_service, SymbolApiClientFactory $api_client_factory) {
    $this->accountService = $account_service;
    $this->apiClientFactory = $api_client_factory;
  }
  
  // /**
  //  * {@inheritdoc}
  //  */
  // public static function create(ContainerInterface $container) {
  //   return new static(
  //     $container->get('quicklearing_symbol.account_service')
  //   );
  // }
  // /**
  //  * {@inheritdoc}
  //  */
  // public static function create(ContainerInterface $container) {
  //   // Since FormBase uses service traits, we can inject these services without
  //   // adding our own __construct() method.
  //   $form = new static($container);
  //   $form->setStringTranslation($container->get('string_translation'));
  //   $form->setMessenger($container->get('messenger'));
  //   return $form;
  // }

  public static function create(ContainerInterface $container) {
    // AccountService をコンストラクタで注入
    $form = new static(
      $container->get('quicklearning_symbol.account_service'),
      $container->get('quicklearning_symbol.api_client_factory')
    );

    // 他のサービスをセッターメソッドを使って注入
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));

    return $form;
  }


   /**
   * Build the simple form.
   *
   * A build form method constructs an array that defines how markup and
   * other form elements are included in an HTML form.
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
    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['#attached']['library'][] = 'qls_ch8/hash_lock';
    
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('8.1 ハッシュロック'),
    ];

    $form['step'] = [
      '#type' => 'value',
      '#value' => !empty($form_state->getValue('step')) ? $form_state->getValue('step') : 1,
    ];

    switch ($form['step']['#value']) {
      case 1:
        $limit_validation_errors = [['step']];
        $form['step1'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Step 1: アグリゲートボンデッドトランザクションの作成'),
        ];
        $form['step1']['network_type'] = [
          '#type' => 'radios',
          '#title' => $this->t('Network Type'),
          '#description' => $this->t('Select either testnet or mainnet'),
          '#options' => [
            'testnet' => $this->t('Testnet'),
            'mainnet' => $this->t('Mainnet'),
          ],
          '#default_value' => $form_state->hasValue(['step1', 'network_type']) ? $form_state->getValue(['step1', 'network_type']) : 'testnet',
          '#required' => TRUE,
        ];

        $form['step1']['originator_pvtKey'] = [
          '#type' => 'password',
          '#title' => $this->t('Originator Private Key'),
          '#description' => $this->t('Enter the private key of the originator.'),
          '#required' => TRUE,
        ];
    
        $form['step1']['symbol_address'] = [
          '#markup' => '<div id="symbol_address">Symbol Address</div>',
        ];
        $form['step1']['symbol_address_hidden'] = [
          '#type' => 'hidden',
          // '#value' => '', // 初期値は空
          '#attributes' => [
            'id' => 'symbol-address-hidden', // カスタム ID を指定
          ],
        ];

        $form['step1']['recipientAddress'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Recipient Address'),
          '#description' => $this->t('Enter the address of the recipient. TESTNET: Start with T / MAINNET: Start with N'),
          '#required' => TRUE,
          '#default_value' => 'TAJZXDFDOCVYVID4S45BLPGSPLPFUQIAUO5PBIA',
        ];

        $form['step1']['mosaicid'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Mosaic ID'),
          '#description' => $this->t('TESTNET XYM:0x72C0212E67A08BCE / MAINNET XYM:0x6BED913FA20223F8'),
          '#required' => TRUE,
          '#default_value' => '0x72C0212E67A08BCE',
        ];
    
        $form['step1']['amount'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Amount'),
          '#description' => $this->t('Enter the amount of the mosaic. (1 XYM = 1000000)'),
          '#required' => TRUE,
          '#default_value' => '1000000',
        ];

        break;

      case 2:
        $limit_validation_errors = [['step'], ['step1']];
        $form['step1'] = [
          '#type' => 'value',
          '#value' => $form_state->getValue('step1'),
        ];
        $form['step2'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Step 2:連署'),
        ];

        $form['step2']['cosigner_pvtKey'] = [
          '#type' => 'password',
          '#title' => $this->t('Co-signer Private Key'),
          '#description' => $this->t('Enter the private key of the co-signer.'),
          '#required' => TRUE,
        ];

        $form['expires_in'] = [
          '#type' => 'item',
          '#title' => $this->t('Expires In'),
        ];

        break;

      default:
        $limit_validation_errors = [];
    }

    $form['actions'] = ['#type' => 'actions'];
    if ($form['step']['#value'] > 1) {
      $form['actions']['prev'] = [
        '#type' => 'submit',
        '#value' => $this->t('Previous step'),
        '#limit_validation_errors' => $limit_validation_errors,
        '#submit' => ['::prevSubmit'],
        '#ajax' => [
          'wrapper' => 'aggregate-bounded-transaction-wrapper',
          'callback' => '::prompt',
        ],
      ];
    }
    if ($form['step']['#value'] != 2) {
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Make Aggregate Bonded Transaction'),
        '#submit' => ['::nextSubmit'],
        '#ajax' => [
          'wrapper' => 'aggregate-bounded-transaction-wrapper',
          'callback' => '::prompt',
        ],
      ];
    }
    if ($form['step']['#value'] == 2) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t("Make Cosignature Transaction"),
      ];
    }

    $form['#prefix'] = '<div id="aggregate-bounded-transaction-wrapper">';
    $form['#suffix'] = '</div>';


    return $form;
  }

  /**
   * Getter method for Form ID.
   *
   * The form ID is used in implementations of hook_form_alter() to allow other
   * modules to alter the render array built by this form controller. It must be
   * unique site wide. It normally starts with the providing module's name.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'hash_lock_ajax_form';
  }

  /**
   * Wizard callback function.
   *
   * @param array $form
   *   Form API form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form API form.
   *
   * @return array
   *   Form array.
   */
  public function prompt(array $form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
  
    // Renderer サービスを取得
    $renderer = \Drupal::service('renderer'); 

    $render_context = new RenderContext();
    $rendered_form = $renderer->executeInRenderContext($render_context, function () use ($form, $renderer) {
      return $renderer->render($form);
    });

    if (!$render_context->isEmpty()) {
      \Drupal::logger('qls_ch8')->error('Rendering context warnings: @warnings', [
        '@warnings' => json_encode($render_context->pop(), JSON_PRETTY_PRINT),
      ]);
    }

    $response->addCommand(new ReplaceCommand('#aggregate-bounded-transaction-wrapper', $rendered_form));

    return $response;

  }

  /**
   * Ajax callback that moves the form to the next step and rebuild the form.
   *
   * @param array $form
   *   The Form API form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   *
   * @return array
   *   The Form API form.
   */
  public function nextSubmit(array $form, FormStateInterface $form_state) {
    // $form_state->set('step1_network_type', $form_state->getValue(['step1', 'network_type']));
    $network_type = $form_state->getValue('network_type');
    $facade = new SymbolFacade($network_type);
    // ノードURLを設定
    if ($network_type === 'testnet') {
      $networkType = new NetworkType(NetworkType::TESTNET);
      $node_url = 'http://sym-test-03.opening-line.jp:3000';
    } elseif ($network_type === 'mainnet') {
      $networkType = new NetworkType(NetworkType::MAINNET);
      $node_url = 'http://sym-main-03.opening-line.jp:3000';
    }
    $apiInstance = $this->apiClientFactory->createApiForNodeUrl(TransactionRoutesApi::class, $node_url);

    $originator_pvtKey = $form_state->getValue('originator_pvtKey');
    $originatorKey = $facade->createAccount(new PrivateKey($originator_pvtKey));
    
    

    $recipientAddStr = $form_state->getValue('recipientAddress');
    // $recipientAddress = new UnresolvedAddress($recipientAddStr);
    // AccountServiceを使ってアカウント情報を取得
    $account_info = $this->accountService->getAccountInfo($recipientAddStr, $node_url);
    $account = $account_info->getAccount(); // AccountDTO を取得
    $address = $account->getAddress(); // address を取得
    $recipentPublicKeyStr = $account->getPublicKey();
    $recipientAddress = new UnresolvedAddress($address);
    $recipentPublicKey = new PublicKey($recipentPublicKeyStr);

    $mosaicid = $form_state->getValue('mosaicid');
    $amount = $form_state->getValue('amount');
    // // アグリゲートTxに含めるTxを作成
    $tx1 = new EmbeddedTransferTransactionV1(
      network: $networkType,
      signerPublicKey: $originatorKey->publicKey,
      recipientAddress: $recipientAddress,
      mosaics: [
        new UnresolvedMosaic(
          mosaicId: new UnresolvedMosaicId($mosaicid),
          amount: new Amount($amount)
        )
      ],
      message: "",  //メッセージなし
    );

    $tx2 = new EmbeddedTransferTransactionV1(
      network: $networkType,
      signerPublicKey: $recipentPublicKey,
      recipientAddress: $originatorKey->address,
      message: "\0thank you!",
    );
        
    // マークルハッシュの算出
    $embeddedTransactions = [$tx1, $tx2];
    $merkleHash = $facade->hashEmbeddedTransactions($embeddedTransactions); 

    // アグリゲートボンデットTx作成
    $aggregateTx = new AggregateBondedTransactionV2(
      network: $networkType,
      signerPublicKey: $recipentPublicKey,
      deadline: new Timestamp($facade->now()->addHours(2)),
      transactionsHash: $merkleHash,
      transactions: $embeddedTransactions
    );
    $facade->setMaxFee($aggregateTx, 100, 1); 

    // 署名
    $sig = $originatorKey->signTransaction($aggregateTx);
    $payload = $facade->attachSignature($aggregateTx, $sig);

    /**
     * ハッシュロック
     */
    $xym_namespaceIds = IdGenerator::generateNamespacePath('symbol.xym');
    $xym_namespaceId = new NamespaceId($xym_namespaceIds[count($xym_namespaceIds) - 1]);

    $hashLockTx = new HashLockTransactionV1(
      signerPublicKey: $originatorKey->publicKey,
      network: $networkType,
      deadline: new Timestamp($facade->now()->addHours(2)), // 有効期限
      duration: new BlockDuration(480), // 有効期限
      hash: new Hash256($facade->hashTransaction($aggregateTx)), // ペイロードのハッシュ
      mosaic: new UnresolvedMosaic(
        mosaicId: new UnresolvedMosaicId($xym_namespaceId), // モザイクID
        amount: new Amount(10 * 1000000) // 金額(10XYM)
      )
    );
    $facade->setMaxFee($hashLockTx, 100);  // 手数料

    // 署名
    $hashLockSig = $originatorKey->signTransaction($hashLockTx);
    $hashLockJsonPayload = $facade->attachSignature($hashLockTx, $hashLockSig);

    /**
     * ハッシュロックをアナウンス
     */
    try {
      $apiInstance->announceTransaction($hashLockJsonPayload);

    } catch (\Exception $e) {
      \Drupal::logger('qls_ch8')->error('Exception when calling TransactionRoutesApi->announceTransaction: @message', ['@message' => $e->getMessage()]);
    }
    \Drupal::logger('qls_ch8')->debug('hashLockTx: @tx', ['@tx' => $facade->hashTransaction($hashLockTx)]);
    $this->messenger()->addStatus($this->t('Transaction submitted. Use the confirmation form to check final network status.'));

    /**
     * アグリゲートボンデットTxをアナウンス
     */
    try {
      $apiInstance->announcePartialTransaction($payload);

    } catch (\Exception $e) {
      \Drupal::logger('qls_ch8')->error('Exception when calling TransactionRoutesApi->announcePartialTransaction: @message', ['@message' => $e->getMessage()]);
    }

    \Drupal::logger('qls_ch8')->debug('aggregateTx: @tx', ['@tx' => $facade->hashTransaction($aggregateTx)]);

    
    $form_state->set('network_type', $network_type);
    $form_state->set('recipientAddress', $recipientAddress);
    $form_state->setValue('step', $form_state->getValue('step') + 1);

    $form_state->setRebuild();
    return $form;
  }
  /**
   * Ajax callback that moves the form to the previous step.
   *
   * @param array $form
   *   The Form API form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState object.
   *
   * @return array
   *   The Form API form.
   */
  public function prevSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setValue('step', $form_state->getValue('step') - 1);
    $form_state->setRebuild();
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
   
    $current_step = $form_state->get('step');
    if ($current_step == 1) {
      
    } elseif ($current_step == 2) {
      
    }
    
  }

  /**
   * Implements a form submit handler.
   *
   * The submitForm method is the default method called for any submit elements.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $network_type = $form_state->get('step1_network_type');

    $facade = new SymbolFacade($network_type);
    // ノードURLを設定
    if ($network_type === 'testnet') {
      $networkType = new NetworkType(NetworkType::TESTNET);
      $node_url = 'http://sym-test-03.opening-line.jp:3000';
    } elseif ($network_type === 'mainnet') {
      $networkType = new NetworkType(NetworkType::MAINNET);
      $node_url = 'http://sym-main-03.opening-line.jp:3000';
    }

    $originator_pvtKey = $form_state->getValue('originator_pvtKey');
    if (empty($originator_pvtKey)) {
      $this->messenger()->addError($this->t('The originator private key must be entered again. Private keys are not carried between form steps.'));
      return;
    }
    $ownerKey = $facade->createAccount(new PrivateKey($originator_pvtKey));
   
    // $symbol_address_hidden = $form_state->getValue(['symbol_address_hidden']);
    

    
    
    $tx = new NamespaceRegistrationTransactionV1(
      network: $networkType,
      signerPublicKey: $ownerKey->publicKey,  // 署名者公開鍵
      deadline: new Timestamp($facade->now()->addHours(2)),
      parentId: new NamespaceId($parnetNameId),
      id: new NamespaceId(IdGenerator::generateNamespaceId($name, $parnetNameId)),
      registrationType: new NamespaceRegistrationType(NamespaceRegistrationType::CHILD),
      name: $name,
    );
    $facade->setMaxFee($tx, 200);

    // 署名
    $sig = $ownerKey->signTransaction($tx);
    $payload = $facade->attachSignature($tx, $sig);

    $apiInstance = $this->apiClientFactory->createApiForNodeUrl(TransactionRoutesApi::class, $node_url);
    
    try {
      $apiInstance->announceTransaction($payload);
      $this->messenger()->addStatus($this->t('Transaction announce request accepted. Use the confirmation form to check final network status.'));
    } catch (\Exception $e) {
      \Drupal::logger('qls_ch8')->error('トランザクションの発行中にエラーが発生しました: @message', ['@message' => $e->getMessage()]);
    }

  }

}
