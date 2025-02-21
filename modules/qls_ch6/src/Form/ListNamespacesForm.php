<?php

namespace Drupal\qls_ch6\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use SymbolSdk\Symbol\Address;

use SymbolRestClient\Model\NamespaceIds;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\NamespaceService;
use Drupal\quicklearning_symbol\Service\ChainService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ListNamespacesForm extends FormBase {

   /**
   * FacadeServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;

  /**
   * NamespaceServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\NamespaceService
   */
  protected $namespaceService;

  /**
   * ChainServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\ChainService
   */
  protected $chainService;

  /**
   * コンストラクタ
   *
   * @param \Drupal\quicklearning_symbol\Service\FacadeService $facadeService
   *   FacadeServiceのインスタンス
   * @param \Drupal\quicklearning_symbol\Service\NamespaceService $namespaceService
   *   NamespaceServiceのインスタンス
   * @param \Drupal\quicklearning_symbol\Service\ChainService $chainService
   *   ChainServiceのインスタンス 
   */
  public function __construct(FacadeService $facadeService, 
    NamespaceService $namespaceService, 
    ChainService $chainService) {
      $this->facadeService = $facadeService;
      $this->namespaceService = $namespaceService;
      $this->chainService = $chainService;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */  
  public static function create($container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),
      $container->get('quicklearning_symbol.namespace_service'),
      $container->get('quicklearning_symbol.chain_service')
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
      '#markup' => $this->t('ネームスペース一覧'),
    ];

    $form['owner_address'] = [
      '#title' => $this->t('Account Symbol Address'),
      '#type' => 'textfield',
      '#description' => $this->t('Enter the Symbol address of the Account.'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'TCWM5Z7LGSDFGBPWQYGZPKDJH2HGCJCT4NHJUAA',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('List Namespaces'),
    ];

    // // Submit 後に Views を表示
    //  if ($view_displayed) {
    //   $form['view'] = [
    //     '#type' => 'view',
    //     '#name' => 'your_view_machine_name', // ビューのマシン名
    //     '#display_id' => 'owned_mosaic_list',  // ビューのディスプレイ ID
    //     // '#arguments' => [$form_state->getValue('input_text')], // 引数を渡す例
    //   ];
    // }

    // フォーム送信後のデータを取得
    $data = $form_state->get('namespace_table_data');
    if ($data) {
      // テーブルヘッダーを定義
      $headers = [
        $this->t('ID'),
        $this->t('Name'),
        $this->t('Expiration'),
        $this->t('Expired'),
        $this->t('Alias Type'),
        $this->t('Alias'),
        $this->t('Action'),
      ];

      // テーブル行を作成
      $rows = [];
      foreach ($data as $item) {
        $rows[] = [
          $item['id'],
          $item['name'],
          $item['expiration'],
          $item['expired'], 
          $item['aliasType'],
          $item['alias'],
          $item['action'],
        ];
      }

      // テーブルをフォームに追加
      $form['namespace_table'] = [
        '#type' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
        '#empty' => $this->t('No Namaspaces data available.'),
      ];
    }

    return $form;
  }

  /**
   * Getter method for Form ID.
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'list_namespaces_form';
  }

  /**
   * Implements form validation.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  // public function validateForm(array &$form, FormStateInterface $form_state) {
  //   $pvtKey = $form_state->getValue('account_pvtKey');
  //   if (strlen($pvtKey) !=  64) {
  //     // Set an error for the form element with a key of "title".
  //     $form_state->setErrorByName('account_pvtKey', $this->t('The private key must be 64 characters long.'));
  //   }
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

    // $accountAddress = $accountKey->address;
    $ownerAddress = $form_state->getValue('owner_address');

    $namespaceApi = $this->namespaceService->getNamespaceApi();
    $response = $namespaceApi->searchNamespaces($ownerAddress);
    $data = $response->getData();
    
    $chainApi = $this->chainService->getChainApi();
    $chainInfo = $chainApi ->getChainInfo();
    $currentHeight = $chainInfo->getHeight();

    $namespace_table_data[] = [];
    foreach ($data as $index => $namespaceInfoDTO) {
      $namespaceDTO = $namespaceInfoDTO->getNamespace();
      // \Drupal::logger('qls_ch6')->info('namespaceDTO: <pre>@namespaceDTO</pre>', ['@namespaceDTO' => print_r($namespaceDTO, true)]);
      // if($namespace['namespace']['depth']!=3){//2階層までのネームスペースを取得
      // \Drupal::logger('qls_ch6')->info('depth: @depth', ['@depth' => $namespace['namespace']['depth']]); 
      $depth = $namespaceDTO->getDepth();
      $level0 = $namespaceDTO->getLevel0();
      $level1 = $namespaceDTO->getLevel1();
      $level2 = $namespaceDTO->getLevel2();

      $alias = $namespaceDTO->getAlias();
      // $mosaic_id = $alias->getMosaicId();
      // $address = $alias->getAddress();

      // $root_namespaces = []; 
      $root_namespaceid = $level0;
      // \Drupal::logger('qls_ch6')->info('root_namespaceid: @root_namespaceid', ['@root_namespaceid' => $root_namespaceid]);
      // $responseBody = $this->getNameSpaceNamePostRequest($root_namespaceid, $form_state);
      // \Drupal::logger('qls_ch6')->info('responseBody: @response', ['@response' => $responseBody]);
          // responseBody: [{"id":"D52C99BDEE2471AB","name":"qls"}]
      $namespaceIds = new NamespaceIds(['namespace_ids' => [$root_namespaceid]]);
      $namespaceApi = $this->namespaceService->getNamespaceApi();
      $namespaces = $namespaceApi->getNamespacesNames($namespaceIds);   
      // \Drupal::logger('qls_ch6')->info('namespaces: <pre>@namespaces</pre>', ['@namespaces' => print_r($namespaces, true)]);
      $namespace_name = $namespaces[0]->getName();
      // \Drupal::logger('qls_ch6')->info('namespace_name: @namespace_name', ['@namespace_name' => $namespace_name]);

      // $root_namespaces= json_decode($responseBody, true);
      // $starthight = $namespace['namespace']['startHeight'];

      // $endHeight = $namespace['namespace']['endHeight'];
      $endHeight = $namespaceDTO->getEndHeight();
      $expired = ''; 
      if($endHeight < $currentHeight){
        $expired = 'true';
      }else{
        $expired = 'false';
        $expiration = $this->formatSecondsToReadableTime(($endHeight - $currentHeight)*30);
      }
      $namespace_table_data[$index]['expiration'] = $expiration;
      $namespace_table_data[$index]['expired'] = $expired;
      $namespace_table_data[$index]['action'] = 'link alias'; 

      $aliasType = ''; 
      // $alias_type = $namespace['namespace']['alias']['type'];
      $alias_type = $namespaceDTO->getAlias()->getType();
      // \Drupal::logger('qls_ch6')->info('alias_type: @alias_type', ['@alias_type' => $alias_type]);

      if($alias_type == 1){
        $aliasType = 'Mosaic';
        $namespace_table_data[$index]['alias'] = $alias->getMosaicId();
        $namespace_table_data[$index]['action'] = 'unlink mosaic'; 
        
      }elseif($alias_type == 2){
        $aliasType = 'Address';
        $address = Address::fromDecodedAddressHexString($alias->getAddress());
        $namespace_table_data[$index]['alias'] = $address;
        $namespace_table_data[$index]['action'] = 'unlink address'; 
      }else{
        $aliasType = 'None';
        $namespace_table_data[$index]['alias'] = '';
        $namespace_table_data[$index]['action'] = ''; 
      }  
      $namespace_table_data[$index]['aliasType'] = $aliasType;
      

      switch ($depth)
      {
        case 1:
          // \Drupal::logger('qls_ch6')->info('namespaces: @namespaces', ['@namespaces' => print_r($namespaces, true)]);
          // $namespace_table_data[$index]['id'] = $namespace['namespace']['level0'];
          // $namespace_table_data[$index]['name'] = $root_namespaces[0]['name'];
          // $namespace_table_data[$index]['action'] .= 'extend dration';
          $namespace_table_data[$index]['id'] = $level0;
          $namespace_table_data[$index]['name'] = $namespace_name;
          $namespace_table_data[$index]['action'] .= 'extend dration';
          
          break;
        case 2:

          $namespaceid = $level1;
          // $responseBody = $this->getNameSpaceNamePostRequest($namespaceid, $form_state);
          // $namespaces= json_decode($responseBody, true);
          $namespaceIds = new NamespaceIds(['namespace_ids' => [$namespaceid]]);
          $namespaces = $this->namespaceService->getNamespacesNames($namespaceIds); 

          $namespace_table_data[$index]['id'] = $namespaceid;
          $namespace_table_data[$index]['name'] = $namespaces[1]['name']. '.' .$namespaces[0]['name'];
          
          break;
        case 3:
          $namespaceid = $level2;
          // $responseBody = $this->getNameSpaceNamePostRequest($namespaceid, $form_state);
          // $namespaces= json_decode($responseBody, true);
          $namespaceIds = new NamespaceIds(['namespace_ids' => [$namespaceid]]);
          $namespaces = $this->namespaceService->getNamespacesNames($namespaceIds); 

          $namespace_table_data[$index]['id'] = $namespaceid;
          $namespace_table_data[$index]['name'] = $namespaces[2]['name']. '.' .$namespaces[1]['name']. '.' .$namespaces[0]['name'];

          break;
        default:
          break;
        
       
      }
    }
    \Drupal::logger('qls_ch6')->info('namespace_table_data: <pre>@namespace_table_data</pre>', ['@namespace_table_data' => print_r($namespace_table_data, true)]); 

    // フォームステートにデータを設定
    $form_state->set('namespace_table_data', $namespace_table_data);
    $form_state->setRebuild();
   
  }

  

  // function flattenNamespaceData($mosaicInfoArray) {
  //   $flattenedData = [];

  //   foreach ($mosaicInfoArray as $mosaicInfo) {
  //       $mosaic = $mosaicInfo->getMosaic(); // MosaicDTO オブジェクトを取得

  //       $flattenedData[] = [
  //           'id' => $mosaic->getId(), // モザイクID
  //           'supply' => $mosaic->getSupply(), // サプライ量
  //           'owner_address' => $mosaic->getOwnerAddress(), // 所有者アドレス
  //           'divisibility' => $mosaic->getDivisibility(), // 分割可能性
  //           'flags' => $mosaic->getFlags(), // フラグ
  //           'duration' => $mosaic->getDuration(), // 有効期間
  //           'start_height' => $mosaic->getStartHeight(), // 開始ブロック高さ
  //       ];
  //   }

  //   return $flattenedData;
  // }

  //Get readable names for a set of namespaces
  //https://symbol.github.io/symbol-openapi/v0.11.3/#tag/Namespace-routes/operation/getNamespacesNames
  // private function getNameSpaceNamePostRequest($namespaceid, FormStateInterface $form_state) {
    
  //   // $network_type = $form_state->getValue(['network_type']);
  //   //   if($network_type === 'testnet') {
  //   //     $node_url = 'http://sym-test-03.opening-line.jp:3000/namespaces/names';
  //   //   } elseif($network_type === 'mainnet') {
  //   //     $node_url = 'http://sym-main-03.opening-line.jp:3000/namespaces/names';
  //   //   }
  //   $node_url = 'http://sym-test-03.opening-line.jp:3000/namespaces/names';

  //   // 送信するデータ
  //   $data = [
  //     'namespaceIds' => $namespaceid
  //   ];

  //   try {
  //     // HTTP クライアントで POST リクエストを送信
  //     $response = \Drupal::httpClient()->post($node_url, [
  //       'headers' => [
  //         'Content-Type' => 'application/json',
  //         'Accept' => 'application/json',
  //       ],
  //       'json' => $data, // データを JSON として送信
  //     ]);

  //     // レスポンスを取得
  //     // $statusCode = $response->getStatusCode(); // HTTP ステータスコード
  //     $body = $response->getBody()->getContents(); // レスポンス本文

  //     // 結果をログに記録
  //     // \Drupal::logger('qls_ch6')->info('Response: @response', ['@response' => $body]);

  //     return $body; // 必要に応じて処理
  //   }
  //   catch (RequestException $e) {
  //     // エラーハンドリング
  //     \Drupal::logger('qls_ch6')->error('Error during POST request: @error', ['@error' => $e->getMessage()]);
  //     throw $e;
  //   }
  // }

  // private function getNodeinfo(FormStateInterface $form_state) {
    
  //   // $network_type = $form_state->getValue(['network_type']);
  //   //   if($network_type === 'testnet') {
  //   //     $node_url = 'http://sym-test-03.opening-line.jp:3000/chain/info';
  //   //   } elseif($network_type === 'mainnet') {
  //   //     $node_url = 'http://sym-main-03.opening-line.jp:3000/chain/info';
  //   //   }
  //   $node_url = 'http://sym-test-03.opening-line.jp:3000/chain/info';

  //     try {
  //       // HTTP クライアントで GET リクエストを送信
  //       $response = \Drupal::httpClient()->get($node_url, [
  //         'headers' => [
  //             'Accept' => 'application/json',
  //         ],
  //     ]);

  //     // レスポンスをデコード
  //     $body = $response->getBody()->getContents();
  //     $data = json_decode($body, true);

  //     return $data; // 必要に応じて処理
  //   }
  //   catch (RequestException $e) {
  //     // エラーハンドリング
  //     \Drupal::logger('qls_ch6')->error('Error during POST request: @error', ['@error' => $e->getMessage()]);
  //     throw $e;
  //   }
  // }

  private function formatSecondsToReadableTime($seconds) {
    $days = floor($seconds / (24 * 3600));
    $seconds %= (24 * 3600);
    $hours = floor($seconds / 3600);
    $seconds %= 3600;
    $minutes = floor($seconds / 360);

    return sprintf('%d d %d h %d m', $days, $hours, $minutes);
    // Drupalの翻訳対応の形式で文字列を生成
    // return $this->t('@days d @hours h @minutes m', [
    //     '@days' => $days,
    //     '@hours' => $hours,
    //     '@minutes' => $minutes,
    // ]);
  }

}
