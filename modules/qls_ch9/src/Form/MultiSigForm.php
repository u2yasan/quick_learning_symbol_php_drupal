<?php

namespace Drupal\qls_ch9\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\CryptoTypes\PublicKey;

use SymbolSdk\Symbol\Models\AggregateCompleteTransactionV2;
use SymbolSdk\Symbol\Models\EmbeddedMultisigAccountModificationTransactionV1;
use SymbolSdk\Symbol\Models\Timestamp;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\AccountService;
use Drupal\quicklearning_symbol\Service\TransactionService;
use Drupal\quicklearning_symbol\Service\MultisigService;

/**
 * @see \Drupal\Core\Form\FormBase
 */
class MultiSigForm extends FormBase {

  /**
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;

  /**
   * @var Drupal\quicklearning_symbol\Service\AccountService
   */
  protected $accountService;

  /**
   * @var \Drupal\quicklearning_symbol\Service\TransactionService
   */
  protected $transactionService;
  
  /**
   * @var \Drupal\quicklearning_symbol\Service\MultisigService
   */
  protected $multisigService;

  /**
   * コンストラクタでServiceを注入
   */
  public function __construct(
    FacadeService $facade_service,
    AccountService $account_service,
    TransactionService $transaction_service, 
    MultisigService $multisig_service
    ) {
      $this->facadeService = $facade_service;
      $this->accountService = $account_service;
      $this->transactionService = $transaction_service;
      $this->multisigService = $multisig_service;
  }
  
  /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),         
      $container->get('quicklearning_symbol.account_service'),
      $container->get('quicklearning_symbol.transaction_service'), 
      $container->get('quicklearning_symbol.multisig_service') 
    );
  }

   /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multi_sig_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'qls_ch9/multi_sig';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => "9.1 ".$this->t('マルチシグの登録'),
    ];

    $form['multisig_account_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Account To Be Converted Private Key'),
      '#description' => $this->t('Enter the private key of the account to be converted.'),
      '#required' => TRUE,
    ];

    $form['symbol_address'] = [
      '#markup' => '<div id="symbol_address">Symbol Address</div>',
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
        '#type' => 'textfield',
        '#title' => $this->t('Cosigner Address'),
        '#description' => $this->t('Enter the raw address of the cosigner.')
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

    $form['mini_approval'] = [
      '#type' => 'textfield',
      '#title' => $this->t('News Min. Approval'),
      '#description' => $this->t('Minimum signature to sign a transaction or to add a cosigner'),
      '#required' => TRUE,
      '#attributes' => [
        'type' => 'number', // HTML5 の number 属性
        'min' => 0,         // 最小値
        'max' => 25,       // 最大値
        'step' => 1,        // ステップ値
      ],
    ];

    $form['mini_removal'] = [
      '#type' => 'textfield',
      '#title' => $this->t('News Min. Removal'),
      '#description' => $this->t('Minimum signature required to remove a cosigner'),
      '#required' => TRUE,
      '#attributes' => [
        'type' => 'number', // HTML5 の number 属性
        'min' => 0,         // 最小値
        'max' => 25,       // 最大値
        'step' => 1,        // ステップ値
      ],
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
    $multisigApi = $this->multisigService->getMultisigApi();

    // $apiInstance = new TransactionRoutesApi($client, $config);
    // $multisigApiInstance = new MultisigRoutesApi($client, $config);

    $multisig_account_pvtKey = $form_state->getValue('multisig_account_pvtKey');
    $multisigKey = $facade->createAccount(new PrivateKey($multisig_account_pvtKey));

    $mini_approval = $form_state->getValue('mini_approval');
    $mini_removal = $form_state->getValue('mini_removal');

    // コサイナーの数を判別し取得
    $cosigners = $form_state->getValue(['cosigners_fieldset', 'cosigner']);
    \Drupal::logger('qls_ch9')->info('cosigners: @cosigners', ['@cosigners' => print_r($cosigners, true)]);
    $cosigner_addresses = [];
    if (is_array($cosigners)) {
        foreach ($cosigners as $cosigner) {
          \Drupal::logger('qls_ch9')->info('cosigner: @cosigner', ['@cosigner' => $cosigner]);

          // $cosigner_addresses[] = new UnresolvedAddress($cosigner);

          // \Drupal::logger('qls_ch9')->info('node_url: @node_url', ['@node_url' => $node_url]);
          $account_info = $accountApi->getAccountInfo($cosigner);
          // \Drupal::logger('qls_ch9')->info('account_info: @account_info', ['@account_info' => $account_info]);
          if ($account_info) {

            $accountDTO = $account_info->getAccount();

            // 公開鍵を取得
            $account_pubKeyStr = $accountDTO->getPublicKey();
            $pubAccount = $facade->createPublicAccount(new PublicKey($account_pubKeyStr));//公開鍵クラス
            $account_address = $pubAccount->address;
            \Drupal::logger('qls_ch9')->info('account_address: @account_address', ['@account_address' => print_r($account_address,true)]); 

            // \Drupal::logger('qls_ch9')->info('account_pubKeyStr: @account_pubKeyStr', ['@account_pubKeyStr' => $account_pubKeyStr]);
            // $recipent_publicKey = new CryptoPublicKey($recipent_publicKey_str);
            // $recipent_publicKey = new PublicKey($account_pubKeyStr);
            // $account_address = $facade->createAccount($recipent_publicKey)->address;
            // $account_address = Address::fromRawAddress($account_pubKeyStr, $networkType);
            // \Drupal::logger('qls_ch9')->info('account_address: @account_address', ['@account_address' => $account_address]);
          }
          else {
            \Drupal::messenger()->addMessage($this->t('Failed to retrieve account information.'), 'error');
  
          }
          $cosigner_addresses[] = $account_address;// アドレスクラス公開鍵
        }
    } 

    // \Drupal::logger('qls_ch9')->info('cosigner_addresses: @cosigner_addresses', ['@cosigner_addresses' => $cosigner_addresses]);
    // \Drupal::logger('qls_ch9')->info('cosigner_addresses: @cosigner_addresses', ['@cosigner_addresses' => print_r($cosigner_addresses, true)]);
    // 配列を文字列に変換
    // $addressAdditions = '[' . implode(',', $cosigner_addresses) . ']';
    /**
     * マルチシグの登録
     */
    $multisigTx =  new EmbeddedMultisigAccountModificationTransactionV1(
      network: $networkType,
      signerPublicKey: $multisigKey->publicKey,  // マルチシグ化したいアカウントの公開鍵を指定
      minApprovalDelta: $mini_approval, // minApproval:承認のために必要な最小署名者数増分
      minRemovalDelta: $mini_removal, // minRemoval:除名のために必要な最小署名者数増分
      addressAdditions: $cosigner_addresses,
      addressDeletions: [] // 除名対象アドレスリスト
    );

    // マークルハッシュの算出
    $embeddedTransactions = [$multisigTx];
    $merkleHash = $facade->hashEmbeddedTransactions($embeddedTransactions);

    // アグリゲートトランザクションの作成
    $aggregateTx = new AggregateCompleteTransactionV2(
      network: $networkType,
      signerPublicKey: $multisigKey->publicKey,  // マルチシグ化したいアカウントの公開鍵を指定
      deadline: new Timestamp($facade->now()->addHours(2)),
      transactionsHash: $merkleHash,
      transactions: $embeddedTransactions
    );
    $facade->setMaxFee($aggregateTx, 100, 3);  // 手数料 第二引数に連署者の数:4

    // マルチシグ化したいアカウントによる署名
    $sig = $multisigKey->signTransaction($aggregateTx);
    $payload = $facade->attachSignature($aggregateTx, $sig);

    $this->messenger()->addWarning($this->t('Co-signer private keys are no longer loaded from Drupal configuration. Use the co-signing form for co-signers.'));

    // アナウンス
    $payload = ["payload" => strtoupper(bin2hex($aggregateTx->serialize()))];

    // try {

    //   $this->messenger()->addMessage($this->t('Aggregate Transaction successfully announced: @result', ['@result' => $result]));
    // } catch (Exception $e) {
    //   \Drupal::logger('qls_ch9')->error('Transaction Failed: @message', ['@message' => $e->getMessage()]);
    //   // echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
    // }
    // echo 'TxHash' . PHP_EOL;
    // echo $facade->hashTransaction($aggregateTx) . PHP_EOL;

    $result = $transactionApi->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));
    $this->messenger()->addMessage($this->t('TxHash: @TxHash', ['@TxHash' => $facade->hashTransaction($aggregateTx)]));
    $this->messenger()->addStatus($this->t('Transaction submitted. Use the confirmation form to check final network status.'));
    /**
     * 確認
     */
    
    $multisigInfo = $multisigApi->getAccountMultisig($multisigKey->address);
    // \Drupal::logger('qls_ch9')->info('multisigInfo: @multisigInfo', ['@multisigInfo' => print_r($multisigInfo, true)]);
    $this->messenger()->addStatus($this->t('Result retrieved. Detailed raw output is suppressed for security.'));
    /**
     * 連署者アカウントの確認
     */
    $multisigInfo = $multisigApi->getAccountMultisig($coSigKey1->address);
    // echo "===連署者1のマルチシグ情報===" . PHP_EOL;
    // echo $multisigInfo . PHP_EOL;
    // \Drupal::logger('qls_ch9')->info('multisigInfo1: @multisigInfo', ['@multisigInfo' => print_r($multisigInfo, true)]);
    $this->messenger()->addStatus($this->t('Result retrieved. Detailed raw output is suppressed for security.'));
  }


}
