<?php
namespace Drupal\qls_ch7\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;
use Drupal\quicklearning_symbol\Service\NamespaceService;
use Drupal\quicklearning_symbol\Service\MetadataService;

/**
 * @see \Drupal\Core\Form\FormBase
 */
class ListMetadataForm extends FormBase {

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'list_metadata_form';
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
   * NamespaceService
   * @var \Drupal\quicklearning_symbol\Service\NamespaceService
   */
  protected $namespaceService;

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
    NamespaceService $namespace_service,
    MetadataService $metadata_service
    ) {
      $this->facadeService = $facade_service;
      $this->transactionService = $transaction_service;
      $this->namespaceService = $namespace_service;
      $this->metadataService = $metadata_service;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),  
      $container->get('quicklearning_symbol.transaction_service'), 
      $container->get('quicklearning_symbol.namespace_service'),
      $container->get('quicklearning_symbol.metadata_service')
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
    $form['#attached']['library'][] = 'qls_ch7/metadata_namespace';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => '7.4 '. $this->t('確認'),
    ];

    $form['source_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Source Private Key'),
      '#description' => $this->t('Source Owner Private Key.'),
      '#required' => TRUE,
    ];
    $form['source_symbol_address'] = [
      '#markup' => '<div id="source-symbol-address">Symbol Address</div>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('List Metadata'),
    ];

    // フォーム送信後のデータを取得
    $data = $form_state->get('metadada_table_data');
    if ($data) {
      // テーブルヘッダーを定義
      $headers = [
        $this->t('Target Address'),
        $this->t('Scoped Metadata Key'),
        $this->t('Metadata Type'),
        $this->t('Value'),
      ];

      // テーブル行を作成
      $rows = [];
      foreach ($data as $item) {
        $rows[] = [
          $item['targetAddress'],
          $item['scopedMetadataKey'],
          $item['metadataType'],
          $item['value'],
        ];
      }

      // テーブルをフォームに追加
      $form['mosaic_table'] = [
        '#type' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
        '#empty' => $this->t('No mosaic data available.'),
      ];
    }

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
  }

  /**
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $facade = $this->facadeService->getFacade();

    $source_pvtKey = $form_state->getValue('source_pvtKey');
    $sourceKey = $facade->createAccount(new PrivateKey($source_pvtKey));   
    $sourceAddress = $sourceKey->address; // メタデータ作成者アドレス
    // \Drupal::logger('qls_ch7')->notice('sourceAddress:<pre>@object</pre>', ['@object' => print_r($sourceAddress, TRUE)]); 
    $metadataApi = $this->metadataService->getMetadataApi();
    $metadataInfo = $metadataApi->searchMetadataEntries(
      target_address: $sourceAddress,
      source_address: $sourceAddress,
    );
    \Drupal::logger('qls_ch7')->notice('metadataInfo:<pre>@object</pre>', ['@object' => print_r($metadataInfo, TRUE)]);
    // {
    //   "id": "66A120C284E82060AFC1E5AE",
    //   "metadataEntry": {
    //   "version": 1,
    //   "compositeHash":
    //   "77B448E5375D16F44FF3C2E35221759B35438D360BD89DB0679003FFD1E7D9F5",
    //   "sourceAddress": "98E521BD0F024F58E670A023BF3A14F3BECAF0280396BED0",
    //   "targetAddress": "98E521BD0F024F58E670A023BF3A14F3BECAF0280396BED0",
    //   "scopedMetadataKey": "8EF1ED391DB8F32F",
    //   "targetId": {},
    //   "metadataType": 0,
    //   "value": "686F6765"
    //   }
    //   },
    $flattenedData = $this->flattenMetadataData($metadataInfo);
    $form_state->set('metadada_table_data', $flattenedData);
    $form_state->setRebuild();

  }
  private function flattenMetadataData($metadataInfo) {
    $flattenedData = [];
    foreach ($metadataInfo->getData() as $metadataItem) {
      // $id = $metadataItem->getId();
      $metadataEntry = $metadataItem->getMetadataEntry();
      $flattenedData[] = [
        // $sourceAddress = $metadataEntry->getSourceAddress();
        'targetAddress' => $metadataEntry->getTargetAddress(),
        'scopedMetadataKey' => $metadataEntry->getScopedMetadataKey(),
        'metadataType' => $metadataEntry->getMetadataType(),
        'value' => hex2bin($metadataEntry->getValue()),// デコードされた値を取得
      ];
      // ログに出力
      // \Drupal::logger('qls_ch7')->notice("ID: $id, Source: $sourceAddress, Target: $targetAddress, Value: $decodedValue"); 
    }
    return $flattenedData;
  }

}  