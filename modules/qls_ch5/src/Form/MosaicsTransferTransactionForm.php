<?php

namespace Drupal\qls_ch5\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Symbol\Models\TransferTransactionV1;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedMosaic;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;
use SymbolSdk\Symbol\Models\Amount;
use SymbolSdk\Symbol\Models\UnresolvedAddress;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\AccountService;
use Drupal\quicklearning_symbol\Service\TransactionService;
use Drupal\quicklearning_symbol\Service\TransactionStatusService;

/**
 * @see \Drupal\Core\Form\FormBase
 */
class MosaicsTransferTransactionForm extends FormBase {

  /**
   * FacadeServiceのインスタンス
   * 
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;

  /**
   * AccountServiceのインスタンス
   *
   * @var Drupal\quicklearning_symbol\Service\AccountService
   */
  protected $accountService;

  /**
   * TransactionServiceのインスタンス
   *
   * @var Drupal\quicklearning_symbol\Service\TransactionService
   */
  protected $transactionService;

  /**
   * TransactionStatusServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\TransactionStatusService
   */
  protected $transactionStatusService;

   /**
   * コンストラクタでSymbolAccountServiceを注入
   */
  public function __construct(
    FacadeService $facade_service,
    TransactionService $transaction_service, 
    TransactionStatusService $transaction_status_service, 
    AccountService $account_service
    ) {
      $this->facadeService = $facade_service;
      $this->transactionService = $transaction_service;
      $this->transactionStatusService = $transaction_status_service;
      $this->accountService = $account_service;
  }

    /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),         
      $container->get('quicklearning_symbol.transaction_service'), 
      $container->get('quicklearning_symbol.transaction_status_service'), 
      $container->get('quicklearning_symbol.account_service')   
    );
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

    $form['description'] = [
      '#type' => 'item',
      '#markup' => '5.2 '. $this->t('モザイク送信'),
    ];

    $form['recipientAddress'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipient Address'),
      '#description' => $this->t('Enter the address of the recipient. TESTNET: Start with T / MAINNET: Start with N'),
      '#required' => TRUE,
      '#default_value' => 'TAJZXDFDOCVYVID4S45BLPGSPLPFUQIAUO5PBIA',
    ];

    // `sender_pvtKey` を復元
    $saved_sender_pvtKey = $form_state->get('sender_pvtKey') ?? '';
    $form['sender_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Sender Private Key'),
      '#description' => $this->t('Enter the private key of the sender.'),
      '#required' => TRUE,
      '#default_value' => $saved_sender_pvtKey,
    ];

    // $form['deadline'] = [
    //   '#type' => 'select',
    //   '#title' => $this->t('Deadline'),
    //   '#description' => $this->t('Select a deadline (Max: 6 hours, Default: 2 hours).'),
    //   '#required' => TRUE,
    //   '#default_value' => '2', // デフォルト値を2に設定
    //   '#options' => [
    //     '1' => $this->t('1 hour'),
    //     '2' => $this->t('2 hours'),
    //     '3' => $this->t('3 hours'),
    //     '4' => $this->t('4 hours'),
    //     '5' => $this->t('5 hours'),
    //     '6' => $this->t('6 hours'),
    //   ],
    // ];

    // $form['message'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('Message'),
    //   '#description' => $this->t('Max: 1023 byte.'),
    //   // '#default_value' => 'Hello, Symbol!',
    // ];

    // $form['is_encrypt_message'] = [
    //   '#type' => 'checkbox',
    //   '#title' => $this->t('Encrypt Message'),
    //   '#description' => $this->t('Check this box to encrypt the message.'),
    //   '#default_value' => 0,
    // ]; 

    // $form['feeMultiprier'] = [
    //   '#type' => 'textfield',
    //   '#title' => $this->t('feeMultiprier'),
    //   '#description' => $this->t('transaction size * feeMultiprier = transaction fee'),
    //   '#required' => TRUE,
    //   '#default_value' => '100',
    // ];

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('You can add up to 10 Mosaic IDs with corresponding amounts.'),
    ];
    // モザイク数を取得または初期化
    $mosaic_count = $form_state->get('mosaic_count') ?? 1;
    $form_state->set('mosaic_count', $mosaic_count);

    // AJAXで更新されるコンテナ
    $form['mosaics_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'mosaics-container'], // コンテナのID
      '#tree' => TRUE, // 階層構造を保持
    ];

    $mosaic_count = $form_state->get('mosaic_count');
    // \Drupal::logger('qls_ch5')->notice('L.182:<pre>@object</pre>', ['@object' => print_r($mosaic_count, TRUE)]); 
    // モザイクIDと対応する金額入力フィールドを動的に生成
    for ($i = 0; $i < $mosaic_count; $i++) {
      // \Drupal::logger('qls_ch5')->notice('L.185:<pre>@object</pre>', ['@object' => print_r($i, TRUE)]); 
      // \Drupal::logger('qls_ch5')->notice('L.187:<pre>@object</pre>', ['@object' => print_r($mosaic_count, TRUE)]); 
      $form['mosaics_container']['mosaics'][$i]['mosaic_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Mosaic ID @num', ['@num' => $i + 1]),
        '#required' => TRUE,
        '#description' => $this->t('Enter the Mosaic ID (e.g., 72C0212E67A08BCE).'),
      ];
      $form['mosaics_container']['mosaics'][$i]['amount'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Amount @num', ['@num' => $i + 1]),
        '#required' => TRUE,
        '#description' => $this->t('Enter the amount of the mosaic (e.g., 1000000).'),
        '#default_value' => '1000000',
      ];
      // \Drupal::logger('qls_ch5')->notice('L.200:<pre>@object</pre>', ['@object' => print_r($form['mosaics_container']['mosaics'][$i]['amount'], TRUE)]); 
      // 特定のプロパティのみをログに出力
      \Drupal::logger('qls_ch5')->notice('L.196: Amount field value: @value', [
        '@value' => $form['mosaics_container']['mosaics'][$i]['amount']['#title'] ?? '',
      ]); 
    }

    // "Add Mosaic ID" ボタン
    if ($mosaic_count < 10) {
      $form['mosaics_container']['add_mosaic'] = [
        '#type' => 'button',
        '#value' => $this->t('Add Mosaic ID'),
        '#ajax' => [
          'callback' => '::addMosaicIdAjax',
          'wrapper' => 'mosaics-container', // 更新するコンテナID
        ],
        // '#limit_validation_errors' => [],
      ];
    }

    // "Remove Mosaic ID" ボタン
    if ($mosaic_count > 1) {
      $form['mosaics_container']['remove_mosaic'] = [
        '#type' => 'button',
        '#value' => $this->t('Remove Mosaic ID'),
        '#ajax' => [
          'callback' => '::removeMosaicIdAjax',
          'wrapper' => 'mosaics-container', // 更新するコンテナID
        ],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    if (isset($form['mosaics_container']['mosaics'][$mosaic_count - 1]['amount'])) {
      $amount_title = $form['mosaics_container']['mosaics'][$mosaic_count - 1]['amount']['#title'] ?? '';
      \Drupal::logger('qls_ch5')->notice('L.235 Amount field value: @value', ['@value' => $amount_title]);
    }

    return $form;
  }

  /**
   * Getter method for Form ID.
   * 
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'mosaics_transfer_transaction_form';
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
    // $message = $form_state->getValue('message');
    // if (mb_strlen($message, '8bit') > 1023) {
    //   $form_state->setErrorByName('message', $this->t('The message must be less equal than 1023 byte.'));
    // }
  }

  public function addMosaicIdAjax(array &$form, FormStateInterface $form_state) {
    // $mosaics = $form_state->getValue('mosaics', []);
    $mosaics = $form_state->getValue(['mosaics_container', 'mosaics']);
    // デバッグログ
    // \Drupal::logger('ajax_debug')->notice('L.309:mosaics:<pre>@data</pre>', ['@data' => print_r($mosaics, TRUE)]);
    foreach ($mosaics as $index => $mosaic) {
      if (empty($mosaic['mosaic_id'])) {
        // $form_state->setErrorByName("mosaics][$index][mosaic_id", $this->t('Mosaic ID @num is required.', ['@num' => $index + 1]));
        $form_state->setError($form, $this->t('Mosaic ID @num is required.', ['@num' => $index + 1]));
      }
      if (empty($mosaic['amount']) || !is_numeric($mosaic['amount']) || $mosaic['amount'] <= 0) {
        // $form_state->setErrorByName("mosaics][$index][amount", $this->t('Amount @num must be a positive number.', ['@num' => $index + 1]));
        $form_state->setError($form, $this->t('Amount @num must be a positive number.', ['@num' => $index + 1]));
      }
    }

    if ($form_state->hasAnyErrors()) {
        return $form['mosaics_container'];
    }

    // モザイク数を1増やす
    $mosaic_count = $form_state->get('mosaic_count');
    $form_state->set('mosaic_count', $mosaic_count + 1);
    // $mosaic_count_debug = $form_state->get('mosaic_count'); 
    // \Drupal::logger('qls_ch5')->notice('L.329:<pre>@object</pre>', ['@object' => print_r($mosaic_count_debug, TRUE)]); 
    
    $form_state->setRebuild(TRUE);
    // \Drupal::logger('qls_ch5')->notice('<pre>@data</pre>', ['@data' => print_r($form['mosaics_container']['mosaics'], TRUE)]);
    // \Drupal::logger('qls_ch5')->notice('L.332: Amount field value: @value', [
    //   '@value' => $form['mosaics_container']['mosaics'][$mosaic_count]['amount']['#title'] ?? '',
    // ]);
    // コンテナ部分の内容をデバッグ
  // \Drupal::logger('ajax_debug')->notice('<pre>@data</pre>', [
  //   '@data' => print_r($form['mosaics_container'], TRUE),
  // ]);
  //   kint($form['mosaics_container']);
  // kint($form_state->getValues());
    return $form['mosaics_container'];
  }

  public function removeMosaicIdAjax(array &$form, FormStateInterface $form_state) {
    // 現在のモザイク数を取得し、1つ減らす
    $mosaic_count = $form_state->get('mosaic_count');
    if ($mosaic_count > 1) {
      $form_state->set('mosaic_count', $mosaic_count - 1);
    }
  
    // モザイクコンテナ部分を返す
    return $form['mosaics_container'];
  }

  // // AJAX コールバック関数
  // public function ajaxRefresh(array &$form, FormStateInterface $form_state) {
  //   return $form['mosaics_container'];
  // }
  
  // public function ajaxRefresh(array &$form, FormStateInterface $form_state) {
  //   return $form;
  // }

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

    $facade = $this->facadeService->getFacade();
    $networkType = $this->facadeService->getNetworkTypeObject();

    // 受取人アドレス(送信先)
    $recipientAddStr = $form_state->getValue('recipientAddress');
    $recipientAddress = new UnresolvedAddress($recipientAddStr);

    $sender_pvtKey = $form_state->getValue('sender_pvtKey');
    // 秘密鍵からアカウント生成
    $senderKey = $facade->createAccount(new PrivateKey($sender_pvtKey));

    // 有効期限
    // sdk ではデフォルトで 2 時間後に設定されます。最大6 時間まで指定可能です。
    // $deadline = intval($form_state->getValue('deadline'));
    $deadline = intval(2);
    
    // メッセージ
    // トランザクションに最大1023 バイトのメッセージを添付することができます。バイナ
    // リデータであってもrawdata として送信することが可能です。
    // ■空メッセージ
    // $messageData = "";?
    // ■平文メッセージ エクスプローラーなどで表示するためには先頭に\0 を付加する必要
    // があります。
    // $message = $form_state->getValue('message');

    // ■暗号文メッセージ MessageEncoder を使用して暗号化すると、自動で暗号文メッセー
    // ジを表すメッセージタイプ0x01 が付加されます。
    // $is_encrypt_message = $form_state->getValue('is_encrypt_message');

    // $feeMultiprier = $form_state->getValue('feeMultiprier');
    // $feeMultiprier = 100;

    $mosaics = $form_state->getValue(['mosaics_container', 'mosaics']);

    // \Drupal::logger('qls_ch5')->notice('424 mosaics:<pre>@data</pre>', ['@data' => print_r($mosaics, TRUE)]);
    foreach ($mosaics as $index => $mosaic) {
      $this->messenger()->addMessage($this->t('Mosaic ID: @id, Amount: @amount', [
        '@id' => '0x'.$mosaic['mosaic_id'],
        '@amount' => $mosaic['amount'],
      ]));
    }
    // Transfer Transactionで一度に送信できるモザイクの種類数には、厳密な制限はありませんが、トランザクションサイズの制約に依存します。
    // 具体的には、送信するモザイクの数が増えると、トランザクションのデータサイズが増加します。
    // このサイズがノードやネットワークで許容される**最大トランザクションサイズ（1024バイト）**を超えると、トランザクションが拒否される可能性があります。
   
    // if($message){
    //   // 平文メッセージ
    //   // メッセージを平文で送信する場合は、そのまま指定します。メッセージはUTF-8 エンコー
    //   // ディングされるため、バイナリデータを送信する場合は、UTF-8 エンコードされたバイナリデー
    //   // タを指定します。
    //   $messageData = "\0".$message;
    //   //$messageData = $message;
    //   // \Drupal::logger('qls_ch5')->notice('messageData:<pre>@object</pre>', ['@object' => print_r($messageData, TRUE)]);  

    //   // 文字列を16進数にエンコード
    //   // $messageData = bin2hex($message);
    //   // メッセージをRawメッセージとして作成
    //   // $messageData = new Message($hexMessage);

    //   if($is_encrypt_message){

    //     $address = new Address($recipientAddress);
      
    //     $accountApi = $this->accountService->getAccountApi(); 
    //     $account_info = $accountApi>getAccountInfo($address);
    //     // \Drupal::logger('qls_ch5')->notice('account_info:<pre>@object</pre>', ['@object' => print_r($account_info, TRUE)]);
    //     sleep(1);
        
    //     if ($account_info) {
    //       // AccountDTOオブジェクトを取得
    //       $accountDTO = $account_info->getAccount();
    //       // 公開鍵を取得
    //       $recipent_publicKey_str = $accountDTO->getPublicKey();
    //       // $recipent_publicKey = new CryptoPublicKey($recipent_publicKey_str);
    //       $recipent_publicKey = new PublicKey($recipent_publicKey_str);
    //     }
    //     else {
    //       \Drupal::messenger()->addMessage($this->t('Failed to retrieve account information.'), 'error');

    //     }
    //     $senderMesgEncoder = new MessageEncoder($senderKey->keyPair);
    //     $messageData = $senderMesgEncoder->encode($recipent_publicKey, $message);
    //   }
    // }
    
    $mosaics_data = [];
    foreach ($mosaics as $mosaic) {
      $mosaic_id = '0x'.$mosaic['mosaic_id'];
      $mosaics_data[] = new UnresolvedMosaic(
        mosaicId: new UnresolvedMosaicId($mosaic_id),
        amount: new Amount($mosaic['amount'])
      );
    }

    // トランザクション
    $transferTx = new TransferTransactionV1(
      network: $networkType,
      signerPublicKey: $senderKey->publicKey,
      deadline: new Timestamp($facade->now()->addHours($deadline)),
      recipientAddress: $recipientAddress,
      mosaics: $mosaics_data,
      // message: $messageData
    );
    
    // $facade->setMaxFee($transferTx, $feeMultiprier); // 手数料
    $facade->setMaxFee($transferTx, 100);

    $signature = $senderKey->signTransaction($transferTx);
    $payload = $facade->attachSignature($transferTx, $signature);

    // $result = $this->transactionService->announceTransaction($payload);
    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));


    sleep(2);
    // アナウンススより先にステータスを確認しに行ってしまいエラーを返す可能性があるためのsleep
    
    $hash = $facade->hashTransaction($transferTx);

    $transactionStatusApi = $this->transactionStatusService->getTransactionStatusApi();
    $result = $transactionStatusApi->getTransactionStatus($hash);
    $this->messenger()->addMessage($this->t('Transaction Status: @result', ['@result' => $result]));
    
    /**
    * 承認確認
    */
    // after 30 seconds
    sleep(30);
    // $result = $this->transactionService->getTransactionStatus($hash);
    $result = $transactionStatusApi->getTransactionStatus($hash); 
    $this->messenger()->addMessage($this->t('Transaction Status: @result', ['@result' => $result]));
  
  }

}
