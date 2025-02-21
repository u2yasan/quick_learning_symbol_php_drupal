<?php

namespace Drupal\qls_ch6\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolSdk\Symbol\Models\AddressAliasTransactionV1;
use SymbolSdk\Symbol\Models\MosaicAliasTransactionV1;
use SymbolSdk\Symbol\Models\Address;
use SymbolSdk\Symbol\Models\AliasAction;
use SymbolSdk\Symbol\Models\MosaicId;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\NamespaceId;
use SymbolSdk\Symbol\IdGenerator;

use SymbolRestClient\Model\NamespaceIds;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\AccountService;
use Drupal\quicklearning_symbol\Service\TransactionService;
use Drupal\quicklearning_symbol\Service\NamespaceService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class LinkNamespaceForm extends FormBase {

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
   * NamespaceServiceのインスタンス
   * 
   * @var \Drupal\quicklearning_symbol\Service\NamespaceService
   */
  protected $namespaceService;

  /**
   * AccountServiceのインスタンス
   * 
   * @var \Drupal\quicklearning_symbol\Service\AccountService
   * 
   */
  protected $accountService;


  /**
   * コンストラクタでServiceを注入
   */
  public function __construct(FacadeService $facade_service, 
    AccountService $account_service, 
    TransactionService $transaction_service, 
    NamespaceService $namespace_service) {
      $this->facadeService = $facade_service;
      $this->accountService = $account_service;
      $this->transactionService = $transaction_service;
      $this->namespaceService = $namespace_service;
  }

   /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),
      $container->get('quicklearning_symbol.account_service'),
      $container->get('quicklearning_symbol.transaction_service'),
      $container->get('quicklearning_symbol.namespace_service')
    );
  }

  /**
   * Getter method for Form ID.
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'link_namespace_form';
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
    $form['#attached']['library'][] = 'qls_ch6/link_namespace';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Link Nemaspace'),
    ];

    $form['ownder_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Owner Private Key'),
      '#description' => $this->t('Enter the private key of the owner.'),
      '#required' => TRUE,
    ];

    $form['symbol_address'] = [
      '#markup' => '<div id="symbol-address">Symbol Address</div>',
    ];
    $form['symbol_address_hidden'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'symbol-address-hidden', 
      ],
    ];

    $form['aliastype_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Alias Type'),
      '#options' => [
        '' => $this->t('Choose Alias Type'),
        '1' => $this->t('Link a mosaic'),
        '2' => $this->t('Link an address'),
      ],
      // The #ajax section tells the AJAX system that whenever this dropdown
      // emits an event, it should call the callback and put the resulting
      // content into the wrapper we specify. The questions-fieldset-wrapper is
      // defined below.
      '#ajax' => [
        'wrapper' => 'aliastype-fieldset-wrapper',
        'callback' => '::promptCallback',
      ],
    ];

    // This fieldset just serves as a container for the part of the form
    // that gets rebuilt. It has a nice line around it so you can see it.
    $form['aliastype_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Alias Type'),
      '#open' => TRUE,
      // We set the ID of this fieldset to fieldset-wrapper so the
      // AJAX command can replace it.
      '#attributes' => [
        'id' => 'aliastype-fieldset-wrapper',
        'class' => ['aliastype-wrapper'],
      ],
    ];

    // When the AJAX request comes in, or when the user hit 'Submit' if there is
    // no JavaScript, the form state will tell us what the user has selected
    // from the dropdown. We can look at the value of the dropdown to determine
    // which secondary form to display.
    $aliastype = $form_state->getValue('aliastype_select');
    if (!empty($aliastype) && $aliastype !== '0') {
      $form['aliastype_fieldset']['type'] = [
        '#markup' => $this->t('Which Alias Type'),
      ];

      $form['aliastype_fieldset']['namespace'] = [
        '#type' => 'select',
        '#title' => $this->t('Choose A Namespace'),
        '#options' => $this->getOwnedNamespaceOptions($form_state) ?? [],
        '#required' => TRUE,
      ];
      // Build up a secondary form, based on the type of question the user
      // chose.
      switch ($aliastype) {
        case '1'://Link a mosaic'
          
          $form['aliastype_fieldset']['mosaic'] = [
            '#type' => 'select',
            '#title' => $this->t('Choose A Mosaic'),
            '#options' => $this->getOwnedMosaicOptions($form_state) ?? [],
            '#required' => TRUE,
          ];

          break;
        case '2'://Link an address'
          $form['aliastype_fieldset']['address'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Link an address'),
            '#description' => $this->t('ex.TCWM5Z7LGSDFGBPWQYGZPKDJH2HGCJCT4NHJUAA'),
          ];

          break;

      }
      $form['aliastype_fieldset']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Send'),
      ]; 
      return $form;
    }
    // // Group submit handlers in an actions element with a key of "actions" so
    // // that it gets styled correctly, and so that other modules may add actions
    // // to the form. This is not required, but is convention.
    // $form['actions'] = [
    //   '#type' => 'actions',
    // ];

    // // Add a submit button that handles the submission of the form.
    // $form['actions']['submit'] = [
    //   '#type' => 'submit',
    //   '#value' => $this->t('Make Namespace'),
    // ];

    return $form;
  }



  /**
   * Implements form validation.
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  private function getOwnedNamespaceOptions(FormStateInterface $form_state) {
    $options = [];

    $ownerAddress = $form_state->getValue(['symbol_address_hidden']);
    
    $namespaceApi = $this->namespaceService->getNamespaceApi(); 
    $response = $namespaceApi->searchNamespaces($ownerAddress);
    $data = $response->getData();

    foreach ($data as $namespaceInfoDTO) {
      $namespaceDTO = $namespaceInfoDTO->getNamespace();
      $depth = $namespaceDTO->getDepth();
      $level0 = $namespaceDTO->getLevel0();
      $level1 = $namespaceDTO->getLevel1();
      $level2 = $namespaceDTO->getLevel2();

      switch ($depth)
      {
        case 1:
          $namespaceIds = new NamespaceIds(['namespace_ids' => [$level0]]);
          $namespace = $namespaceApi->getNamespacesNames($namespaceIds)[0] ?? null;
          $namespaceName = $namespace->getName();
          $options[$namespaceName] = $namespaceName;
          break;
        case 2:
          $namespaceIds1 = new NamespaceIds(['namespace_ids' => [$level1]]);
          $namespace1 = $namespaceApi->getNamespacesNames($namespaceIds1)[0] ?? null;

          $namespaceName1 = $namespace1->getName();
          $parentId1 = $namespace1->getParentId();
          $namespaceIds = new NamespaceIds(['namespace_ids' => [$parentId1]]); 
          $namespace = $namespaceApi->getNamespacesNames($namespaceIds)[0] ?? null; 

          $fullNamespaceName = $namespace->getName() . '.' . $namespaceName1;
          $options[$fullNamespaceName] = $fullNamespaceName;
          break;
        case 3:
          $namespaceIds2 = new NamespaceIds(['namespace_ids' => [$level2]]);
          $namespace2 = $namespaceApi->getNamespacesNames($namespaceIds2)[0] ?? null;
          $namespaceName2 = $namespace2->getName();
          $parentId2 = $namespace2->getParentId();
           

          $namespaceIds1 = new NamespaceIds(['namespace_ids' => [$parentId2]]);
          $namespace1 = $namespaceApi->getNamespacesNames($namespaceIds1)[0] ?? null;
          $namespaceName1 = $namespace1->getName();
          $parentId1 = $namespace1->getParentId();

          $namespaceIds = new NamespaceIds(['namespace_ids' => [$parentId1]]); 
          $namespace = $namespaceApi->getNamespacesNames($namespaceIds)[0] ?? null; 

          $fullNamespaceName = $namespace->getName() . '.' .  $namespaceName1 . '.'. $namespaceName2;
          $options[$fullNamespaceName] = $fullNamespaceName;

          break;
      
      }

    }

    return $options;
  }

  private function getOwnedMosaicOptions(FormStateInterface $form_state) {
    $options = [];

    $ownerAddress = $form_state->getValue(['symbol_address_hidden']);

    $accountApi = $this->accountService->getAccountApi();
    $response = $accountApi->getAccountInfo($ownerAddress);
    $account = $response->getAccount();
    $mosaics = $account->getMosaics();

    foreach ($mosaics as $mosaic) {
      //72C0212E67A08BCE textnet symbol.xym
      $options[$mosaic->getId()] = $mosaic->getId();      
    }

    return $options;
  }

  // private function getNameSpaceNamePostRequest($namespaceid, FormStateInterface $form_state) {
    
  //   $network_type = $form_state->getValue(['network_type']);
  //     if($network_type === 'testnet') {
  //       $node_url = 'http://sym-test-03.opening-line.jp:3000/namespaces/names';
  //     } elseif($network_type === 'mainnet') {
  //       $node_url = 'http://sym-main-03.opening-line.jp:3000/namespaces/names';
  //     }

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

    $ownder_pvtKey = $form_state->getValue('ownder_pvtKey');
    $ownerKey = $facade->createAccount(new PrivateKey($ownder_pvtKey));
    
    $aliastype_select = $form_state->getValue('aliastype_select');
    $namespace = $form_state->getValue('namespace');

    if($aliastype_select == 1){
      $linkmosaic = '0x'.$form_state->getValue('mosaic');
      // $linkmosaic = $form_state->getValue('mosaic');
      /**
       * モザイクへリンク
       */
      \Drupal::logger('qls_ch6')->notice('namespace:<pre>@object</pre>', ['@object' => print_r($namespace, TRUE)]); 
      $namespaceIds = IdGenerator::generateNamespacePath($namespace); // ルートネームスペース
      \Drupal::logger('qls_ch6')->notice('namespaceIds:<pre>@object</pre>', ['@object' => print_r($namespaceIds, TRUE)]); 
      $namespaceId = new NamespaceId($namespaceIds[count($namespaceIds) - 1]);
      \Drupal::logger('qls_ch6')->notice('namespaceId:<pre>@object</pre>', ['@object' => print_r($namespaceId, TRUE)]); 
      $mosaicId = new MosaicId($linkmosaic);
      // \Drupal::logger('qls_ch6')->notice('mosaicId:<pre>@object</pre>', ['@object' => print_r($mosaicId, TRUE)]);

      //Tx作成
      $tx = new MosaicAliasTransactionV1(
        network: $networkType,
        signerPublicKey: $ownerKey->publicKey,
        deadline: new Timestamp($facade->now()->addHours(2)),
        namespaceId: new NamespaceId($namespaceId),
        mosaicId: $mosaicId,
        aliasAction: new AliasAction(AliasAction::LINK),
      );

    }else if($aliastype_select == 2){
      $linkaddress = $form_state->getValue('address');
      // \Drupal::logger('qls_ch6')->notice('linkaddress:<pre>@object</pre>', ['@object' => print_r($linkaddress, TRUE)]);

      /**
       * アカウントへのリンク
       */
      \Drupal::logger('qls_ch6')->notice('namespace:<pre>@object</pre>', ['@object' => print_r($namespace, TRUE)]);
      $namespaceId = IdGenerator::generateNamespaceId($namespace);
      // $namespaceIds = IdGenerator::generateNamespacePath($namespace); // ルートネームスペース
      // \Drupal::logger('qls_ch6')->notice('namespaceIds:<pre>@object</pre>', ['@object' => print_r($namespaceIds, TRUE)]);
      // $namespaceId = new NamespaceId($namespaceIds[count($namespaceIds) - 1]);
      // \Drupal::logger('qls_ch6')->notice('namespaceId:<pre>@object</pre>', ['@object' => print_r($namespaceId, TRUE)]);
      // $ownder_pvtKey = $form_state->get('ownder_pvtKey');
      // $ownerKey = $facade->createAccount(new PrivateKey($ownder_pvtKey));
      $linkaddress = $ownerKey->address;
      // \Drupal::logger('qls_ch6')->notice('address:<pre>@object</pre>', ['@object' => print_r($address, TRUE)]);
      // \Drupal::logger('qls_ch6')->notice('new address:<pre>@object</pre>', ['@object' => print_r(new Address($linkaddress), TRUE)]);
      
      //Tx作成
      $tx = new AddressAliasTransactionV1(
        network: $networkType,
        signerPublicKey: $ownerKey->publicKey,
        deadline: new Timestamp($facade->now()->addHours(2)),
        namespaceId: new NamespaceId($namespaceId),
        address: new Address($linkaddress),
        aliasAction: new AliasAction(AliasAction::LINK),
      );

    }
    
    $facade->setMaxFee($tx, 100);

    //署名
    $sig = $ownerKey->signTransaction($tx);
    $payload = $facade->attachSignature($tx, $sig);
    // \Drupal::logger('qls_ch6')->notice('payload:<pre>@object</pre>', ['@object' => print_r($payload, TRUE)]);

    // $config = new Configuration();
    // $config->setHost($node_url);
    // $client = \Drupal::httpClient();
    // $apiInstance = new TransactionRoutesApi($client, $config);

// try {
//   $result = $apiInstance->announceTransaction($payload);
//   echo $result . PHP_EOL;
// } catch (Exception $e) {
//   echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
// }
// $hash = $facade->hashTransaction($tx);
// echo "\n===トランザクションハッシュ===" . PHP_EOL;
// echo $hash . PHP_EOL;
   
    // $tx = new NamespaceRegistrationTransactionV1(
    //   network: $networkType,
    //   signerPublicKey: $ownerKey->publicKey, // 署名者公開鍵
    //   deadline: new Timestamp($facade->now()->addHours(2)),
    //   duration: $blockDuration, // 有効期限
    //   id: new NamespaceId(IdGenerator::generateNamespaceId($root_namespace_name)), //必須
    //   name: $root_namespace_name,
    // );
    // $facade->setMaxFee($tx, 100);

    // // 署名
    // $sig = $ownerKey->signTransaction($tx);
    // $payload = $facade->attachSignature($tx, $sig);

    // // アナウンス
    // $config = new Configuration();
    // $config->setHost($node_url);
    // $client = \Drupal::httpClient();
    // $apiInstance = new TransactionRoutesApi($client, $config);
    
    // try {
    //   $result = $apiInstance->announceTransaction($payload);
    //   // echo $result . PHP_EOL;
    //   $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));
    // } catch (Exception $e) {
    //   \Drupal::logger('qls_ch6')->error('トランザクションの発行中にエラーが発生しました: @message', ['@message' => $e->getMessage()]);
    // }
    // $result = $this->transactionService->announceTransaction($payload);
    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));
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
    return $form['aliastype_fieldset'];
  }

}