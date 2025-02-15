<?php

namespace Drupal\qls_sect5\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolSdk\Symbol\Models\MosaicFlags;
use SymbolSdk\Symbol\Models\MosaicNonce;
use SymbolSdk\Symbol\Models\BlockDuration;
use SymbolSdk\Symbol\Models\Amount;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;
use SymbolSdk\Symbol\Models\MosaicSupplyChangeAction;
use SymbolSdk\Symbol\IdGenerator;
use SymbolSdk\Symbol\Models\EmbeddedMosaicDefinitionTransactionV1;
use SymbolSdk\Symbol\Models\EmbeddedMosaicSupplyChangeTransactionV1;
use SymbolSdk\Symbol\Models\MosaicId;
use SymbolSdk\Symbol\Models\AggregateCompleteTransactionV2;
use SymbolSdk\Symbol\Models\Timestamp;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;

class CreateMosaicForm extends FormBase {
  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'create_mosaic_form';
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
   * コンストラクタでServiceを注入
   */
  public function __construct(FacadeService $facade_service, TransactionService $transaction_service) {
    $this->facadeService = $facade_service;
    $this->transactionService = $transaction_service;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'), 
      $container->get('quicklearning_symbol.transaction_service') 
    );
  }

  /**
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

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('作成するモザイクを定義'),
    ];

    $form['account_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Account Owning Mosaics Private Key'),
      '#description' => $this->t('Enter the private key of the Account.'),
      '#required' => TRUE,
      // '#ajax' => [
      //   'callback' => '::updateSymbolAddress', // Ajaxコールバック関数
      //   'event' => 'blur', // フォーカスが外れたときにトリガー
      //   'wrapper' => 'symbol-address-wrapper', // 書き換え対象の要素ID
      // ],
    ];
    // $form['symbol_address'] = [
    //   // '#title' => $this->t('Account Symbol Address'),
    //   '#markup' => '<div id="symbol-address-wrapper">'.$this->t('Symbol address from private key').'</div>',
    // ];

    $form['supply_units'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Supply Units'),
      '#description' => $this->t('Max: 8,999,999,999'),
      '#required' => TRUE,
      // '#ajax' => [
      //   'callback' => '::updateSupplyUnits',
      //   'event' => 'change', // 値が変更されたときにトリガー
      //   'wrapper' => 'unit-wrapper', // 書き換える要素のID
      // ],
    ];

    $form['divisibility'] = [
      '#type' => 'select',
      '#title' => $this->t('Divisibility'),
      '#description' => $this->t('Select a number between 0 and 6.'),
      '#options' => [
        0 => '0',
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
      ],
      '#default_value' => 0, // 初期選択値
      '#required' => TRUE, // 必須フィールド
      // '#ajax' => [
      //   'callback' => '::updateDivisibilityOptions',
      //   'event' => 'change', // 値が変更されたときにトリガー
      //   'wrapper' => 'unit-wrapper', // 書き換える要素のID
      // ],
    ];

    $form['unit_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'unit-wrapper'],
    ];

    $form['duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Duration'),
      '#description' => $this->t('The Duration must be mainnet:10512000/testnet:315360000 or less. 0 means unlimited.'),
      '#required' => TRUE,
      // '#ajax' => [
      //   'callback' => '::updateDuration',
      //   'event' => 'change', // 値が変更されたときにトリガー
      //   'wrapper' => 'duration-wrapper', // 書き換える要素のID
      // ],
    ];
    
    $form['duration_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'duration-wrapper'],
    ];

    $form['mosaic_flags'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Flags'),
      '#options' => [
        'supplymutable' => $this->t('Supply Mutable'),
        'transferable' => $this->t('Transferable'),
        'restrectable' => $this->t('Restrectable'),
        'revocable' => $this->t('Revocable'),
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Greate Mosaic'),
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
    $pvtKey = $form_state->getValue('account_pvtKey');
    if (strlen($pvtKey) !=  64) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('account_pvtKey', $this->t('The private key must be 64 characters long.'));
    }
  }
  /**
   * Ajaxコールバック関数
   */
  // public function updateSymbolAddress(array &$form, FormStateInterface $form_state) {
    
  //   // 入力されたプライベートキーを取得
  //   $pvtKey = $form_state->getValue('account_pvtKey');
  //   if (!$pvtKey || strlen($pvtKey) !== 64) {
  //     // エラーメッセージをフォームに追加
  //     $form['symbol_address']['#markup'] = '<div id="symbol-address-wrapper" style="color: red;">'
  //         . $this->t('The private key must be 64 characters long.') . '</div>';

  //   }
  //   else{
  //     $network_type = $form_state->getValue('network_type');
  //     $facade = new SymbolFacade($network_type);
  //     try {
  //       $accountKey = $facade->createAccount(new PrivateKey($pvtKey));
  //       $accountRawAddress = $accountKey->address;
        
        
  //     } catch (\Exception $e) {
  //       \Drupal::logger('qls_sect5')->error('Failed to create account: ' . $e->getMessage());
  //       $accountRawAddress = "Error: Unable to generate address.";
  //     }
  //     // $this->messenger()->addMessage($this->t('RawAddress: @rawAddress', ['@rawAddress' => $accountRawAddress]));
  //     //\Drupal::logger('qls_sect5')->notice('<pre>@object</pre>', ['@object' => print_r($accountRawAddress, TRUE)]);
      
  //     // 動的に更新するフィールドの値を設定
  //     $form['symbol_address']['#markup'] = '<div id="symbol-address-wrapper">' . 'test' . '</div>';

  //   }
  //   return $form['symbol_address'];
    
  // }

  /**
   * Implements a form submit handler.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $facade = $this->facadeService->getFacade();
    $networkType = $this->facadeService->getNetworkTypeObject();
    
    $supply_units = $form_state->getValue('supply_units');
    $divisibility = $form_state->getValue('divisibility');

    $duration = $form_state->getValue('duration');
    $mosaic_flags = $form_state->getValue('mosaic_flags');
    // \Drupal::logger('qls_sect5')->notice('<pre>@object</pre>', ['@object' => print_r($mosaic_flags, TRUE)]);  
    $pvtKey = $form_state->getValue('account_pvtKey');
    $accountKey = $facade->createAccount(new PrivateKey($pvtKey));
   
    $blockDuration = new BlockDuration($duration);

    // MosaicFlags の初期化
    $f = MosaicFlags::NONE;
    // 条件に基づいてフラグを加算
    if (!empty($mosaic_flags['supplymutable'])) {
      $f += MosaicFlags::SUPPLY_MUTABLE; // 「供給量変更可能」が選択された場合
    }
    if (!empty($mosaic_flags['transferable'])) {
      $f += MosaicFlags::TRANSFERABLE; // 「譲渡可能」が選択された場合
    }
    if (!empty($mosaic_flags['restrectable'])) {
      $f += MosaicFlags::RESTRICTABLE; // 「制限可能」が選択された場合
    }
    if (!empty($mosaic_flags['revocable'])) {
      $f += MosaicFlags::REVOKABLE; // 「還収可能」が選択された場合
    }
    // MosaicFlagsオブジェクトを作成
    $mosaicFlags = new MosaicFlags($f);
    // \Drupal::logger('qls_sect5')->notice('<pre>@object</pre>', ['@object' => print_r($mosaicFlags, TRUE)]); 
        
    $mosaicId = IdGenerator::generateMosaicId($accountKey->address);
    // 桁数のチェック（15桁なら先頭に0を付ける）
    $hexMosaicId = strtoupper(dechex($mosaicId['id']));
    if (strlen($hexMosaicId) === 15) {
      $hexMosaicId ='0' . $hexMosaicId;
    }

    // モザイク定義
    $mosaicDefTx = new EmbeddedMosaicDefinitionTransactionV1(
      network: $networkType,
      signerPublicKey: $accountKey->publicKey, // 署名者公開鍵
      id: new MosaicId($mosaicId['id']), // モザイクID
      divisibility: $divisibility, // 分割可能性
      duration: $blockDuration, //duration:有効期限
      nonce: new MosaicNonce($mosaicId['nonce']),
      flags: $mosaicFlags,
    );

    //モザイク変更
    $mosaicChangeTx = new EmbeddedMosaicSupplyChangeTransactionV1(
      network: $networkType,
      signerPublicKey: $accountKey->publicKey, // 署名者公開鍵
      mosaicId: new UnresolvedMosaicId($mosaicId['id']),
      delta: new Amount($supply_units),
      action: new MosaicSupplyChangeAction(MosaicSupplyChangeAction::INCREASE),
    );

    // マークルハッシュの算出
    $embeddedTransactions = [$mosaicDefTx, $mosaicChangeTx];
    $merkleHash = $facade->hashEmbeddedTransactions($embeddedTransactions);
    // アグリゲートTx作成
    $aggregateTx = new AggregateCompleteTransactionV2(
      network: $networkType,
      signerPublicKey: $accountKey->publicKey,
      deadline: new Timestamp($facade->now()->addHours(2)),
      transactionsHash: $merkleHash,
      transactions: $embeddedTransactions
    );
    $facade->setMaxFee($aggregateTx, 100); // 手数料
    // 署名
    $sig = $accountKey->signTransaction($aggregateTx);
    $payload = $facade->attachSignature($aggregateTx, $sig);

    // $result = $this->transactionService->announceTransaction($payload);
    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));

  }

}
