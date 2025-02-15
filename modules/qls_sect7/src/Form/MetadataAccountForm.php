<?php
namespace Drupal\qls_sect7\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedAddress;

use SymbolSdk\Symbol\Models\EmbeddedAccountMetadataTransactionV1;
use SymbolSdk\Symbol\Models\AggregateCompleteTransactionV2;
use SymbolSdk\Symbol\Metadata;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;
use Drupal\quicklearning_symbol\Service\MetadataService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class MetadataAccountForm extends FormBase {

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
   * コンストラクタでSymbolAccountServiceを注入
   */
  public function __construct(
    FacadeService $facade_service,
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
    return 'metadata_account_form';
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
    $form['#attached']['library'][] = 'qls_sect7/metadata_account';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => '7.1 '. $this->t('アカウントに登録'),
    ];

    $form['source_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Source Private Key'),
      '#description' => $this->t('Metadata Source Owner Private Key.'),
      '#required' => TRUE,
    ];
    $form['source_symbol_address'] = [
      '#markup' => '<div id="source-symbol-address">Source Symbol Address</div>',
    ];

    $form['target_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Target Private Key'),
      '#description' => $this->t('Metadata Target Owner Private Key.'),
      '#required' => TRUE,
    ];
    $form['target_symbol_address'] = [
      '#markup' => '<div id="target-symbol-address">Target Symbol Address</div>',
    ];

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
     // メタデータ値を取得
    $metadata_value = $form_state->getValue('metadata_value');
    
    // バイト数を取得
    $byte_length = strlen($metadata_value);

    // 1024バイトを超える場合はエラーを設定
    if ($byte_length > 1024) {
      $form_state->setErrorByName('metadata_value', $this->t('The metadata value must not exceed 1024 bytes. It is currently %length bytes.', [
        '%length' => $byte_length,
      ]));
    }
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

    $target_pvtKey = $form_state->getValue('target_pvtKey');
    $targetKey = $facade->createAccount(new PrivateKey($target_pvtKey));
    $targetAddress = $targetKey->address; // メタデータ記録先アドレス

    // キーと値の設定
    $metadata_key = $form_state->getValue('metadata_key');
    $metadata_value = $form_state->getValue('metadata_value');

    $keyId = Metadata::metadataGenerateKey($metadata_key);
    $newValue = $metadata_value;

    $metadataApi = $this->metadataService->getMetadataApi();
    // // 同じキーのメタデータが登録されているか確認
    if($source_pvtKey === $target_pvtKey) {
      $metadataInfo = $metadataApi->searchMetadataEntries(
        source_address: $sourceAddress,
        // target_address: null,
        scoped_metadata_key: strtoupper(dechex($keyId)), // 16進数の大文字の文字列に変換
      );
    } else {
      $metadataInfo = $metadataApi->searchMetadataEntries(
        source_address: $sourceAddress,
        target_address: $targetAddress,
        scoped_metadata_key: strtoupper(dechex($keyId)), // 16進数の大文字の文字列に変換
      );
    }
    // \Drupal::logger('qls_sect7')->notice('metadataInfo:<pre>@object</pre>', ['@object' => print_r($metadataInfo, TRUE)]);


    // if($source_pvtKey === $target_pvtKey) {
    //   $metadataInfo = $this->metadataService->searchMetadataEntries(
    //     $sourceAddress,
    //     null,
    //     strtoupper(dechex($keyId)), // 16進数の大文字の文字列に変換
    //   );
    // } else {
    //   $metadataInfo = $this->metadataService->searchMetadataEntries(
    //     source_address: $sourceAddress,
    //     target_address: $targetAddress,
    //     scoped_metadata_key: strtoupper(dechex($keyId)), // 16進数の大文字の文字列に変換
    //   );
    // }
    $oldValue = '';
    if($metadataInfo !== null){
      $data = $metadataInfo->getData();
      \Drupal::logger('qls_sect7')->notice('metadataInfo->getData():<pre>@object</pre>', ['@object' => print_r($data, TRUE)]);
      // $oldValue = hex2bin($metadataInfo['data'][0]['metadata_entry']['value']); //16進エンコードされたバイナリ文字列をデコード
      if (!empty($data)) {
        $metadataInfo = $data[0];
        $metadataEntry = $metadataInfo->getMetadataEntry();
        $value = $metadataEntry->getValue();
        $oldValue = hex2bin($value);
      }
    }

    $updateValue = Metadata::metadataUpdateValue($oldValue, $newValue, true);

    $tx = new EmbeddedAccountMetadataTransactionV1(
      network: $networkType,
      signerPublicKey: $sourceKey->publicKey,  // 署名者公開鍵
      targetAddress: new UnresolvedAddress($targetAddress),  // メタデータ記録先アドレス
      scopedMetadataKey: $keyId,
      valueSizeDelta: strlen($newValue) - strlen($oldValue),
      value: $updateValue,
    );
   
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
    

    if($source_pvtKey === $target_pvtKey) {
      // 手数料
      $facade->setMaxFee($aggregateTx, 100);
      // トランザクションの署名
      $sig = $sourceKey->signTransaction($aggregateTx);
      $payload = $facade->attachSignature($aggregateTx, $sig);
    } else {
      $facade->setMaxFee($aggregateTx, 100, 1);
      // 作成者による署名
      $sig = $sourceKey->signTransaction($aggregateTx);
      $facade->attachSignature($aggregateTx, $sig);
      // 記録先アカウントによる連署
      $coSig = $targetKey->cosignTransaction($aggregateTx);
      array_push($aggregateTx->cosignatures, $coSig);
      $payload = ['payload' => strtoupper(bin2hex($aggregateTx->serialize()))];
    }

    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->announceTransaction($payload);
    // $result = $this->transactionService->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result])); 
  }

}