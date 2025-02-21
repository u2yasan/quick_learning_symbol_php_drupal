<?php
namespace Drupal\qls_ch7\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;

use SymbolSdk\Symbol\Models\EmbeddedMosaicMetadataTransactionV1;
use SymbolSdk\Symbol\Models\AggregateCompleteTransactionV2;
use SymbolSdk\Symbol\Metadata;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\AccountService;
use Drupal\quicklearning_symbol\Service\TransactionService;
use Drupal\quicklearning_symbol\Service\MetadataService;

/**
 *
 *
 * @see \Drupal\Core\Form\FormBase
 */
class MetadataMosaicForm extends FormBase {

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
   * MetadataServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\MetadataService
   */
  protected $metadataService;

  /**
   * AccountServiceのインスタンス
   * 
   * @var \Drupal\quicklearning_symbol\Service\AccountService
   * 
   */
  protected $accountService;

  /**
   * コンストラクタでSymbolAccountServiceを注入
   */
  public function __construct(
    FacadeService $facade_service,
    AccountService $account_service, 
    TransactionService $transaction_service, 
    MetadataService $metadata_service
    ) {
      $this->facadeService = $facade_service;
      $this->transactionService = $transaction_service;
      $this->metadataService = $metadata_service;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),  
      $container->get('quicklearning_symbol.account_service'),
      $container->get('quicklearning_symbol.transaction_service'), 
      $container->get('quicklearning_symbol.metadata_service')
    );
  }

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'metadata_mosaic_form';
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
    $form['#attached']['library'][] = 'qls_ch7/metadata_mosaic';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => '7.2 '. $this->t('モザイクに登録'),
    ];

    $form['source_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Source Private Key'),
      '#description' => $this->t('Metadata Source Owner Private Key.'),
      '#required' => TRUE,
      // '#ajax' => [
      //   'callback' => '::promptCallback', // Ajax コールバック関数
      //   'event' => 'blur',                   // blur イベントで発火
      //   'wrapper' => 'mosaic-fieldset-wrapper', // 更新対象の要素 ID
      // ],
      // '#limit_validation_errors' => [], // バリデーションをスキップ
    ];
    $form['source_symbol_address'] = [
      '#markup' => '<div id="source-symbol-address">Source Symbol Address</div>',
    ];
    // $form['symbol_address_hidden'] = [
    //   '#type' => 'hidden',
    //   '#attributes' => [
    //     'id' => 'symbol-address-hidden', 
    //   ],
    // ];
    // This fieldset just serves as a container for the part of the form
    // that gets rebuilt. It has a nice line around it so you can see it.
    $form['mosaic_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mosaic'),
      // '#open' => TRUE,
      // We set the ID of this fieldset to fieldset-wrapper so the
      // AJAX command can replace it.
      '#attributes' => [
        'id' => 'mosaic-fieldset-wrapper',
        // 'class' => ['mosaic-wrapper'],
      ]
    ];
    $form['mosaic_fieldset']['mosaic'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mosaic ID'),
    ];
    // $form['mosaic_fieldset']['mosaic'] = [
    //   '#type' => 'select',
    //   '#title' => $this->t('Mosaic'),
    //   '#options' => [],
    //   '#required' => TRUE,
    // ];
    // $source_pvtKey = $form_state->getValue('source_pvtKey');
    // \Drupal::logger('qls_ch7')->notice('source_pvtKey:<pre>@object</pre>', ['@object' => print_r($source_pvtKey, TRUE)]);
    // if($source_pvtKey) {
    //   $form['mosaic_fieldset']['mosaic'] = [
    //     '#type' => 'select',
    //     '#title' => $this->t('Mosaic'),
    //     '#options' => $this->getOwnedMosaicOptions($form_state) ?? [],
    //     '#required' => TRUE,
    //   ];
    //   return $form;
    // }

    $form['metadata_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Metadata Key'),
      '#description' => $this->t('Metadata Key.'),
      '#required' => TRUE,
    ];

    $form['metadata_value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Metadata Value'),
      '#description' => $this->t('Metadata Value. (Max 1024 bytes)'),
      '#required' => TRUE,
    ];
    

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Make Metadata'),
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
    //  // メタデータ値を取得
    // $metadata_value = $form_state->getValue('metadata_value');
    
    // // バイト数を取得
    // $byte_length = strlen($metadata_value);

    // // 1024バイトを超える場合はエラーを設定
    // if ($byte_length > 1024) {
    //   $form_state->setErrorByName('metadata_value', $this->t('The metadata value must not exceed 1024 bytes. It is currently %length bytes.', [
    //     '%length' => $byte_length,
    //   ]));
    // }
  }

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
    
    $source_pvtKey = $form_state->getValue('source_pvtKey');
    $sourceKey = $facade->createAccount(new PrivateKey($source_pvtKey));
    $sourceAddress = $sourceKey->address; // メタデータ作成者アドレス

    // mosaicの選択値を取得
    $targetMosaic = $form_state->getValue('mosaic');
    // \Drupal::logger('qls_ch7')->notice('targetMosaic:<pre>@object</pre>', ['@object' => print_r($targetMosaic, TRUE)]);
    // キーと値の設定
    $metadata_key = $form_state->getValue('metadata_key');
    $metadata_value = $form_state->getValue('metadata_value');

    $keyId = Metadata::metadataGenerateKey($metadata_key);
    // \Drupal::logger('qls_ch7')->notice('keyId:<pre>@object</pre>', ['@object' => print_r($keyId, TRUE)]);
    $newValue = $metadata_value;

    // 同じキーのメタデータが登録されているか確認
    $metadataApi = $this->metadataService->getMetadataApi();
    $metadataInfo = $metadataApi->searchMetadataEntries(
      target_id: $targetMosaic,
      // source_address: new UnresolvedAddress($sourceAddress),
      source_address: $sourceAddress,
      scoped_metadata_key: strtoupper(dechex($keyId)), // 16進数の大文字の文字列に変換
      metadata_type: 1,
    );
    $oldValue = '';
    if($metadataInfo !== null){
      $data = $metadataInfo->getData();
      // \Drupal::logger('qls_ch7')->notice('metadataInfo->getData():<pre>@object</pre>', ['@object' => print_r($data, TRUE)]);
    // $oldValue = hex2bin($metadataInfo['data'][0]['metadata_entry']['value']); //16進エンコードされたバイナリ文字列をデコード
      if (!empty($data)) {
        $metadataInfo = $data[0];
        $metadataEntry = $metadataInfo->getMetadataEntry();
        $value = $metadataEntry->getValue();
        $oldValue = hex2bin($value);
      }
    }

    // $oldValue = hex2bin($metadataInfo['data'][0]['metadata_entry']['value']); //16進エンコードされたバイナリ文字列をデコード
    // if (!empty($metadataInfo['data'][0]['metadata_entry']['value'])) {
    //   $oldValue = hex2bin($metadataInfo['data'][0]['metadata_entry']['value']);
    // } else {
    //     $oldValue = ''; // デフォルト値を設定
    // }

    // \Drupal::logger('qls_ch7')->notice('oldValue:<pre>@object</pre>', ['@object' => print_r($oldValue, TRUE)]);
    $updateValue = Metadata::metadataUpdateValue($oldValue, $newValue, true);
    // \Drupal::logger('qls_ch7')->notice('updateValue:<pre>@object</pre>', ['@object' => print_r($updateValue, TRUE)]);
    // $targetMosaicID = new UnresolvedMosaicId(hexdec($targetMosaic)); 
    // \Drupal::logger('qls_ch7')->notice('targetMosaicID:<pre>@object</pre>', ['@object' => print_r($targetMosaicID, TRUE)]);
    // $tx = new EmbeddedAccountMetadataTransactionV1(
    //   network: $networkType,
    //   signerPublicKey: $sourceKey->publicKey,  // 署名者公開鍵
    //   targetMosaicId: new UnresolvedMosaicId(hexdec($targetMosaic)), // モザイクID
    //   targetAddress: $sourceAddress, // メタデータの対象アドレス
    //   scopedMetadataKey: $keyId,
    //   valueSizeDelta: strlen($newValue) - strlen($oldValue),
    //   value: $updateValue,
    // );
    $tx = new EmbeddedMosaicMetadataTransactionV1(
      network: $networkType,
      signerPublicKey: $sourceKey->publicKey,  // 署名者公開鍵
      targetMosaicId: new UnresolvedMosaicId(hexdec($targetMosaic)),
      targetAddress: $sourceAddress,
      scopedMetadataKey: $keyId,
      valueSizeDelta: strlen($newValue) - strlen($oldValue),
      value: $updateValue,
    );
    // \Drupal::logger('qls_ch7')->notice('tx:<pre>@object</pre>', ['@object' => print_r($tx, TRUE)]);
    // マークルハッシュの算出
    $embeddedTransactions = [$tx];
    $merkleHash = $facade->hashEmbeddedTransactions($embeddedTransactions);
    
    // アグリゲートTx作成
    $aggregateTx = new AggregateCompleteTransactionV2(
      network: $networkType,
      signerPublicKey: $sourceKey->publicKey,
      deadline: new Timestamp($facade->now()->addHours(2)),
      transactionsHash: $merkleHash,
      transactions: $embeddedTransactions,
    );
    
    // 手数料
    $facade->setMaxFee($aggregateTx, 100);
    // トランザクションの署名
    $sig = $sourceKey->signTransaction($aggregateTx);
    $payload = $facade->attachSignature($aggregateTx, $sig);

    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->announceTransaction($payload);
    // $result = $this->transactionService->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result])); 

  }

  private function getOwnedMosaicOptions(FormStateInterface $form_state) {
    $source_pvtKey = $form_state->getValue('source_pvtKey');
    if(empty($source_pvtKey) || strlen($source_pvtKey) !== 64) {
      return [];
    }
    // \Drupal::logger('qls_ch7')->notice('384:source_pvtKey:<pre>@object</pre>', ['@object' => print_r($source_pvtKey, TRUE)]);
    $options = [];
    // $network_type = $form_state->getValue(['network_type']);
    // if($network_type === 'testnet') {
    //   $node_url = 'http://sym-test-03.opening-line.jp:3000';
    // } elseif($network_type === 'mainnet') {
    //   $node_url = 'http://sym-main-03.opening-line.jp:3000';
    // }
    // $facade = new SymbolFacade($network_type);
    // $networkType = $this->facadeService->getNetworkTypeObject();

    $facade = $this->facadeService->getFacade();
    $networkType = $this->facadeService->getNetworkTypeObject();

    // $symbol_address = $form_state->getValue(['symbol_address_hidden']);
    $sourceKey = $facade->createAccount(
      new PrivateKey($form_state->getValue('source_pvtKey'))
    );
    $sourceAddress = $sourceKey->address->__tostring();
    // \Drupal::logger('qls_ch7')->notice('393:<pre>@object</pre>', ['@object' => print_r($sourceKey->address->__tostring(), TRUE)]);

    // $config = new Configuration();
    // $config->setHost($node_url);
    // $client = \Drupal::httpClient();

    // $accountApiInstance = new AccountRoutesApi($client, $config);
    $accountApi = $this->accountService->getAccountApi();
    $account = $accountApi->getAccountInfo($sourceAddress);
    $json_data = json_encode($account, JSON_PRETTY_PRINT);
    $array_data = json_decode($json_data, true);
    // 
    // \Drupal::logger('qls_ch7')->notice('406:<pre>@object</pre>', ['@object' => print_r($json_data, TRUE)]); 
    if ($array_data['account']['mosaics']) {
      foreach ($array_data['account']['mosaics'] as $mosaic) {
        if($mosaic['id']!='72C0212E67A08BCE'){ // testnetのsymbol.xym
          $options[$mosaic['id']] = $mosaic['id'];
        }
      }
    }
    // $options = [
    //   '038D8FA7E70B9725' => '038D8FA7E70B9725',
    //   '072181030CE66350' => '072181030CE66350',
    //   '14282E7F67E84FFA' => '14282E7F67E84FFA',
    //   '26A7B9ED28189EF2' => '26A7B9ED28189EF2',
    //   '2CA841D3265DAFBE' => '2CA841D3265DAFBE',
    //   '36AD93030D234194' => '36AD93030D234194',
    //   '384D6370DA4898A9' => '384D6370DA4898A9',
    //   '45F31337EE87422A' => '45F31337EE87422A',
    //   '5601E87AB77B1F80' => '5601E87AB77B1F80',
    //   '5E37B62006CD4B31' => '5E37B62006CD4B31',
    // ];
    // \Drupal::logger('qls_ch7')->notice('415:<pre>@object</pre>', ['@object' => print_r($options, TRUE)]); 
    return $options;
  }

  /**
   * Callback for the select element.
   *
   * Since the questions_fieldset part of the form has already been built during
   * the AJAX request, we can return only that part of the form to the AJAX
   * request, and it will insert that part into questions-fieldset-wrapper.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function promptCallback(array $form, FormStateInterface $form_state) {
    // まず、出力バッファリングを終了
    if (ob_get_level()) {
      ob_end_clean();
    }

    if (!isset($form['mosaic_fieldset'])) {
      throw new \Exception('mosaic_fieldset is not set in the form.');
    }
     // Log the options returned by getOwnedMosaicOptions
    $options = $this->getOwnedMosaicOptions($form_state);
    // \Drupal::logger('qls_ch7')->notice('Mosaic options: <pre>@data</pre>', ['@data' => print_r($options, TRUE)]);
    \Drupal::logger('qls_ch7')->notice('Mosaic options after AJAX: <pre>@data</pre>', ['@data' => print_r($options, TRUE)]);

    // AJAX リクエスト時に `getOwnedMosaicOptions` を呼び出し
    // $form['mosaic_fieldset']['mosaic'] = [
    //   '#type' => 'select',
    //   '#title' => $this->t('Mosaic'),
    //   '#options' => $options,
    //   '#required' => TRUE,
    //   '#attributes' => [
    //     'id' => 'edit-mosaic',
    //     'name' => 'mosaic',
    //   ],
    // ];
    $form['mosaic_fieldset']['mosaic']['#options'] = $options ?? [];
  
    // 出力デバッグ
  // $response = $form['mosaic_fieldset'];
  // \Drupal::logger('qls_ch7')->notice('Response: <pre>@response</pre>', ['@response' => print_r($response, TRUE)]);

  // return $response;

    return $form['mosaic_fieldset'];
    // if (!isset($form['mosaic_fieldset'])) {
    //   throw new \Exception('mosaic_fieldset is not set in the form.');
    // }
    // try {
    //   // \Drupal::logger('qls_ch7')->notice('436:<pre>@data</pre>', ['@data' => print_r($form['mosaic_fieldset'], TRUE)]);
    //   return $form['mosaic_fieldset'];
    // } catch (\Exception $e) {
    //     \Drupal::logger('qls_ch7')->error('Error in promptCallback: @message', ['@message' => $e->getMessage()]);
    //     throw $e;
    // }
  }
}