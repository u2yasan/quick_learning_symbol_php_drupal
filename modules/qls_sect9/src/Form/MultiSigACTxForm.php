<?php

namespace Drupal\qls_sect9\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolSdk\Symbol\Models\EmbeddedTransferTransactionV1;
use SymbolSdk\Symbol\Models\AggregateCompleteTransactionV2;
use SymbolSdk\Symbol\Models\PublicKey;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\Amount;
use SymbolSdk\Symbol\Models\UnresolvedAddress;
use SymbolSdk\Symbol\Models\UnresolvedMosaic;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;
use Drupal\quicklearning_symbol\Service\AccountService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class MultiSigACTxForm extends FormBase {

  /**
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;

  /**
   * @var \Drupal\quicklearning_symbol\Service\TransactionService
   */
  protected $transactionService;

  /**
   *
   * @var \Drupal\quicklearning_symbol\Service\AccountService
   */
  protected $accountService;

  /**
   * コンストラクタでServiceを注入
   */
  public function __construct(
    FacadeService $facade_service,
    TransactionService $transaction_service,
    AccountService $account_service
    ) {
      $this->facadeService = $facade_service;
      $this->transactionService = $transaction_service;
      $this->accountService = $account_service;
  }

  /**
   * createでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),         
      $container->get('quicklearning_symbol.transaction_service'),
      $container->get('quicklearning_symbol.account_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multi_sig_actx_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'qls_sect9/multi_sig_actx';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => '9.3.1 '.$this->t('アグリゲートコンプリートトランザクションで送信'),
    ];

    $form['multisig_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Multisig Address'),
      '#description' => $this->t('Enter the address of the multisig account. TESTNET: Start with T / MAINNET: Start with N'),
      '#required' => TRUE,
    ];

    $form['originator_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Originator Private Key'),
      '#description' => $this->t('Enter the private key of the originator.'),
      '#required' => TRUE,
    ];

    $form['symbol_address'] = [
      '#markup' => '<div id="symbol_address">Symbol Address</div>',
    ];

    $form['recipientAddress'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipient Address'),
      '#description' => $this->t('Enter the address of the recipient. TESTNET: Start with T / MAINNET: Start with N'),
      '#required' => TRUE,
      '#default_value' => 'TAJZXDFDOCVYVID4S45BLPGSPLPFUQIAUO5PBIA',
    ];
    
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Max: 1023 byte.'),
    ];

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

    // Gather the number of cosigners in the form already.
    $num_cosigners = $form_state->get('num_cosigners');
    // We have to ensure that there is at least one cosigner field.
    if ($num_cosigners === NULL) {
      $cosigner_field = $form_state->set('num_cosigners', 1);
      $num_cosigners = 1;
    }

    $form['#tree'] = TRUE;
    $form['cosigners_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cosigners'),
      '#prefix' => '<div id="cosigners-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $num_cosigners; $i++) {
      $form['cosigners_fieldset']['cosigner'][$i] = [
        '#type' => 'password',
        '#title' => $this->t('Cosigner Private Key'),
        '#description' => $this->t('Enter the Private Key of the cosigner.')
      ];
    }
    $form['cosigners_fieldset']['actions'] = [
      '#type' => 'actions',
    ];

    $form['cosigners_fieldset']['actions']['add_cosigner'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more cosigner'),
      '#submit' => ['::addOneCosigner'],
      '#ajax' => [
        'callback' => '::addMoreCosignerCallback',
        'wrapper' => 'cosigners-fieldset-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];
    // If there is more than one cosigner, add the remove button.
    if ($num_cosigners > 1) {
      $form['cosigners_fieldset']['actions']['remove_cosigner'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one cosigner'),
        '#submit' => ['::removeCosignerCallback'],
        '#ajax' => [
          'callback' => '::addMoreCosignerCallback',
          'wrapper' => 'cosigners-fieldset-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

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
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addMoreCosignerCallback(array &$form, FormStateInterface $form_state) {
    return $form['cosigners_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOneCosigner(array &$form, FormStateInterface $form_state) {
    $cosigner_field = $form_state->get('num_cosigners');
    $add_button = $cosigner_field + 1;
    $form_state->set('num_cosigners', $add_button);
    // Since our buildForm() method relies on the value of 'num_cosigners' to
    // generate 'cosigner' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCosignerCallback(array &$form, FormStateInterface $form_state) {
    $cosigner_field = $form_state->get('num_cosigners');
    if ($cosigner_field > 1) {
      $remove_button = $cosigner_field - 1;
      $form_state->set('num_cosigners', $remove_button);
    }
    // Since our buildForm() method relies on the value of 'num_names' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $facade = $this->facadeService->getFacade();
    $networkType = $this->facadeService->getNetworkTypeObject();
    $transactionApi = $this->transactionService->getTransactionApi();
    $accountApi = $this->accountService->getAccountApi();

    $multisig_address = $form_state->getValue('multisig_address');
    $account_info = $accountApi->getAccountInfo($multisig_address);
    if ($account_info) {
      $accountDTO = $account_info->getAccount();
      $msaccount_pubKeyStr = $accountDTO->getPublicKey();
      $msaccount_pubKey = new PublicKey($msaccount_pubKeyStr);
    } else {
      $this->messenger()->addMessage($this->t('Multisig Account not found.'));
      return;
    }  

    $originator_pvtKey = $form_state->getValue('originator_pvtKey');
    $originatorKey = $facade->createAccount(new PrivateKey($originator_pvtKey));

    // 受取人アドレス(送信先)
    $recipientAddStr = $form_state->getValue('recipientAddress');
    $recipientAddress = new UnresolvedAddress($recipientAddStr);

    $message = $form_state->getValue('message');
    if($message) {
      $messageData = "\0".$message;
    } else {
      $messageData = "";
    }

    $mosaicid = "0x".$form_state->getValue('mosaicid');
    $amount = $form_state->getValue('amount');

    // 連署者数を判別し取得
    $cosigners = $form_state->getValue(['cosigners_fieldset', 'cosigner']);
    // \Drupal::logger('qls_sect9')->info('cosigners: @cosigners', ['@cosigners' => $cosigners]);
    $cosignerKeys = [];
    if (is_array($cosigners)) {
      foreach ($cosigners as $cosigner) {
        $cosignerKey = $facade->createAccount(new PrivateKey($cosigner));
        // \Drupal::logger('qls_sect9')->info('cosigner: @cosigner', ['@cosigner' => $cosigner]);
        $cosignerKeys[] = $cosignerKey;
      }
    } 

    /**
     * マルチシグ署名
     */
    $tx = new EmbeddedTransferTransactionV1(
      network: $networkType,
      signerPublicKey: $msaccount_pubKey,  //マルチシグ化したアカウントの公開鍵
      recipientAddress: $recipientAddress,
      mosaics: [
        new UnresolvedMosaic(
          mosaicId: new UnresolvedMosaicId($mosaicid),
          amount: new Amount($amount)
        )
      ],
      message: $messageData
    );
    // \Drupal::logger('qls_sect9')->info('tx: @tx', ['@tx' => print_r($tx, true)]);
    // // マークルハッシュの算出
    $embeddedTransactions = [$tx];
    $merkleHash = $facade->hashEmbeddedTransactions($embeddedTransactions);

    // アグリゲートトランザクションの作成
    $aggregateTx = new AggregateCompleteTransactionV2(
      network: $networkType,
      signerPublicKey: $originatorKey->publicKey,  // 
      deadline: new Timestamp($facade->now()->addHours(2)),
      transactionsHash: $merkleHash,
      transactions: $embeddedTransactions
    );
    $facade->setMaxFee($aggregateTx, 100, count($cosigners));  // 手数料の設定
    // \Drupal::logger('qls_sect9')->info('aggregateTx: @aggregateTx', ['@aggregateTx' => print_r($aggregateTx, true)]);
    // 起案者アカウントによる署名
    $sig = $originatorKey->signTransaction($aggregateTx);
    $payload = $facade->attachSignature($aggregateTx, $sig);

    // 追加・除外対象として指定したアカウントによる連署
    // \Drupal::logger('qls_sect9')->info('cosignerKeys: @cosignerKeys', ['@cosignerKeys' => print_r($cosignerKeys, true)]);
    foreach ($cosignerKeys as $cosignerKey) {
      $coSig = $facade->cosignTransaction($cosignerKey->keyPair, $aggregateTx);
      array_push($aggregateTx->cosignatures, $coSig);
    }
    
    // アナウンス
    $payload = ["payload" => strtoupper(bin2hex($aggregateTx->serialize()))];

    $result = $transactionApi->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));

    // \Drupal::logger('qls_sect9')->info('payload: @payload', ['@payload' => print_r($payload, true)]);
    // try {
    //   $result = $apiInstance->announceTransaction($payload);
    //   $this->messenger()->addMessage($this->t('Multisig Aggregate Transaction successfully announced: @result', ['@result' => $result]));
    // } catch (Exception $e) {
    //   \Drupal::logger('qls_sect9')->error('Transaction Failed: @message', ['@message' => $e->getMessage()]);
    //   // echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
    // }
    // echo 'TxHash' . PHP_EOL;
    // echo $facade->hashTransaction($aggregateTx) . PHP_EOL;
    // \Drupal::logger('qls_sect9')->info('TxHash: @TxHash', ['@TxHash' => $facade->hashTransaction($aggregateTx)]);
   
    // sleep(35);
    // /**
    //  * 確認
    //  */
    
    // $multisigInfo = $multisigApiInstance->getAccountMultisig($multisigKey->address);
    // // echo "===マルチシグ情報===" . PHP_EOL;
    // // echo $multisigInfo . PHP_EOL;
    // \Drupal::logger('qls_sect9')->info('multisigInfo: @multisigInfo', ['@multisigInfo' => print_r($multisigInfo, true)]);

    // /**
    //  * 連署者アカウントの確認
    //  */
    // $multisigInfo = $multisigApiInstance->getAccountMultisig($coSig1->address);
    // // echo "===連署者1のマルチシグ情報===" . PHP_EOL;
    // // echo $multisigInfo . PHP_EOL;
    // \Drupal::logger('qls_sect9')->info('multisigInfo1: @multisigInfo', ['@multisigInfo' => print_r($multisigInfo, true)]);
  
  }


}
