<?php

namespace Drupal\qls_sect6\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolRestClient\Model\NamespaceIds;

use SymbolSdk\Symbol\Models\NamespaceRegistrationTransactionV1;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\NamespaceId;
use SymbolSdk\Symbol\Models\NamespaceRegistrationType;
use SymbolSdk\Symbol\IdGenerator;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;
use Drupal\quicklearning_symbol\Service\NamespaceService;

/**
 *
 * This example demonstrates a simple form with a single text input element. We
 * extend FormBase which is the simplest form base class used in Drupal.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class CreateSubNamespaceForm extends FormBase {

  /**
   * FacadeServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;

  /**
   * NetworkServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\NetworkService
   */
  // protected $networkService;

  /**
   * TransactionServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\TransactionService
   */
  protected $transactionService;

  /**
   * NamespaceServiceのインスタンス
   */
  protected $namespaceService;

  /**
   * コンストラクタでServiceを注入
   */
  public function __construct(FacadeService $facade_service, TransactionService $transaction_service, NamespaceService $namespace_service) {
    $this->facadeService = $facade_service;
    $this->transactionService = $transaction_service;
    $this->namespaceService = $namespace_service;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),   
      $container->get('quicklearning_symbol.transaction_service'),   
      $container->get('quicklearning_symbol.namespace_service')  
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
    $form['#attached']['library'][] = 'qls_sect6/create_subnamespace';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Sub-Nemaspace'),
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
          '#title' => $this->t('Step 1: Network Type & Account'),
        ];

        $form['step1']['ownder_pvtKey'] = [
          '#type' => 'password',
          '#title' => $this->t('Owner Private Key'),
          '#description' => $this->t('Enter the private key of the owner.'),
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
        break;

      case 2:
        $limit_validation_errors = [['step'], ['step1']];
        $form['step1'] = [
          '#type' => 'value',
          '#value' => $form_state->getValue('step1'),
        ];
        $form['step2'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Step 2: Sub-Namespace'),
        ];

        $form['step2']['parent_namespace'] = [
          '#type' => 'select',
          '#title' => $this->t('Parent Namespace'),
          '#options' => $this->getRootNamespaceOptions($form_state), // 動的に選択肢を生成
          '#empty_option' => $this->t('- Select a namespace -'), // 初期選択肢
        ];

        $form['step2']['sub_namespace_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Sub Namespace Name'),
          '#default_value' => $form_state->hasValue(['step2', 'sub_namespace_name']) ? $form_state->getValue(['step2', 'sub_namespace_name']) : '',
          '#description' => $this->t('Lowercase alphabet, numbers 0-9, hyphen, and underscore'),
          '#required' => TRUE,
        ];

        $form['expires_in'] = [
          '#type' => 'item',
          '#title' => $this->t('Expires In'),
        ];

        $form['estimated_rental_fee'] = [
          '#type' => 'item', 
          '#title' => $this->t('Estimated Rental Fee'),
          '#markup' => '<div id="estimated_rental_fee">10XYM</div>',
        ];
        // •サブネームスペースのレンタルフィーは、ネットワーク設定（パラメータ）によって管理されています。
        // •メインネットおよびテストネットのデフォルト値は 10 XYM とされています。
        //GET http://<node-url>/network/fees/rental
        // 10XYM
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
          'wrapper' => 'subnamespace-wrapper',
          'callback' => '::prompt',
        ],
      ];
    }
    if ($form['step']['#value'] != 2) {
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next step'),
        '#submit' => ['::nextSubmit'],
        '#ajax' => [
          'wrapper' => 'subnamespace-wrapper',
          'callback' => '::prompt',
        ],
      ];
    }
    if ($form['step']['#value'] == 2) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t("Make Sub-Namespace"),
      ];
    }
    $form['#prefix'] = '<div id="subnamespace-wrapper">';
    $form['#suffix'] = '</div>';

    return $form;
  }

  /**
   * Getter method for Form ID.
   *
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'create_subnamespace_form';
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
    return $form;
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
    $form_state->set('step1_network_type', $form_state->getValue('network_type'));
    $form_state->set('ownder_pvtKey', $form_state->getValue('ownder_pvtKey'));
    
    // $form_state->set('step1_network_type', $form_state->getValue(['step1', 'network_type']));
    // \Drupal::logger('qls_sect6')->notice('243 network type:<pre>@object</pre>', ['@object' => print_r($form_state->getValue(['step1', 'network_type']), TRUE)]);
    // \Drupal::logger('qls_sect6')->notice('244 network type:<pre>@object</pre>', ['@object' => print_r($form_state->getValue('network_type'), TRUE)]);
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
      $sub_namespace_name = $form_state->getValue(['step2','sub_namespace_name']);
      if (!preg_match('/^[a-z0-9_-]+$/', $sub_namespace_name)) {
        $form_state->setErrorByName('sub_namespace_name', $this->t('Sub-Namespace Name must be lowercase alphabet, numbers 0-9, hyphen, and underscore.'));
      }
      if (strlen($sub_namespace_name) > 64) {
        $form_state->setErrorByName('sub_namespace_name', $this->t('Sub-Namespace Name cannot exceed 64 characters.'));
      }
    }
    
  }

  private function getRootNamespaceOptions(FormStateInterface $form_state) {
    $options = [];
    // $namespaceMap = [];   

    $symbol_address_hidden = $form_state->getValue(['symbol_address_hidden']);

    $namespaceApi = $this->namespaceService->getNamespaceApi();
    $response = $namespaceApi->searchNamespaces($symbol_address_hidden);
    $data = $response->getData();
        
    foreach ($data as $namespaceInfoDTO) {
      // NamespaceInfoDTO から NamespaceDTO を取得する
      $namespaceDTO = $namespaceInfoDTO->getNamespace();
    
      // NamespaceDTO から depth を取得する
      $depth = $namespaceDTO->getDepth();
      $level0 = $namespaceDTO->getLevel0();
      $level1 = $namespaceDTO->getLevel1();

      switch ($depth) {
        case 1:
          $namespaceIds = new NamespaceIds(['namespace_ids' => [$level0]]);
          $namespace = $this->namespaceService->getNamespacesNames($namespaceIds)[0] ?? null;
          // $namespaceId = $namespace->getId();
          $namespaceName = $namespace->getName();

          // $namespaceMap[$namespaceId] = $namespaceName;
          
          $options[$namespaceName] = $namespaceName;
            
          break;
    
        case 2:
          $namespaceIds1 = new NamespaceIds(['namespace_ids' => [$level1]]);
          $namespace1 = $this->namespaceService->getNamespacesNames($namespaceIds1)[0] ?? null;
          \Drupal::logger('qls_sect6')->debug('case2 namespace: @namespace1', ['@namespace1' => print_r($namespace1, TRUE)]);
          // $namespaceId1 = $namespace1->getId();
          $namespaceName1 = $namespace1->getName();
          $parentId1 = $namespace1->getParentId();
          $namespaceIds = new NamespaceIds(['namespace_ids' => [$parentId1]]); 
          $namespace = $this->namespaceService->getNamespacesNames($namespaceIds)[0] ?? null; 
          $fullNamespaceName = $namespace->getName() . '.' . $namespaceName1;
          $options[$fullNamespaceName] = $fullNamespaceName;
            // 直接マッピング
            // $namespaceMap[$namespaceId] = $namespaceName;
            // \Drupal::logger('qls_sect6')->debug('case2 namespaceMap: @namespaceMap', ['@namespaceMap' => print_r($namespaceMap, TRUE)]);

            // if (isset($namespaceMap[$parentId])) {
            //     // 親ネームスペースの名前を取得して連結
            //     \Drupal::logger('qls_sect6')->debug('case2 parentId: @parentId', ['@parentId' => $parentId]);
            //     $fullNamespaceName = $namespaceMap[$parentId] . '.' . $namespaceName;
            //     $options[$fullNamespaceName] = $fullNamespaceName;
            // }
            
          break;
        default:
          break;
      }

    }
  
    return $options;
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
    /*
     * This would normally be replaced by code that actually does something
     * with the title.
     */
//     $values = $this->debugRecursive($form_state->getValues('step1'));
// \Drupal::logger('debug')->debug('<pre>@values</pre>', ['@values' => print_r($values, TRUE)]);
    // $network_type = $form_state->getValue(['step1','network_type']);
    // $network_type = $form_state->getValue(['network_type']);
    // $keys = array_keys($form_state->getValues());
    // \Drupal::logger('debug')->debug('Keys: @keys', ['@keys' => print_r($keys, TRUE)]);
    // \Drupal::logger('debug')->debug('Values: @values', ['@values' => print_r($form_state->getValues('step1'), TRUE)]);
    // $network_type = $form_state->getValue(['step1_network_type']);
    // $network_type = $form_state->getValue(['network_type']);
//     $values = $this->debugRecursive($form_state->getValues('step1'), 2);
// \Drupal::logger('qls_sect6')->debug('<pre>@values</pre>', ['@values' => print_r($values, TRUE)]);
    // $network_type = $form_state->getValue('network_type');
    // $network_type = $form_state->get('step1_network_type');
    // \Drupal::logger('qls_sect6')->notice('483 network type:<pre>@object</pre>', ['@object' => print_r($network_type, TRUE)]);
    // $facade = new SymbolFacade($network_type);
    // // ノードURLを設定
    // if ($network_type === 'testnet') {
    //   $networkType = new NetworkType(NetworkType::TESTNET);
    //   $node_url = 'http://sym-test-03.opening-line.jp:3000';
    // } elseif ($network_type === 'mainnet') {
    //   $networkType = new NetworkType(NetworkType::MAINNET);
    //   $node_url = 'http://sym-main-03.opening-line.jp:3000';
    // }
    $facade = $this->facadeService->getFacade();
    $networkType = $this->facadeService->getNetworkTypeObject();

    $ownder_pvtKey = $form_state->get('ownder_pvtKey');
    $ownerKey = $facade->createAccount(new PrivateKey($ownder_pvtKey));
   
    $parent_namespace = $form_state->getValue('parent_namespace');
    // $namespace = explode('.', $parent_namespace);
    $namespace = !empty($parent_namespace) ? explode('.', $parent_namespace) : [];
    // \Drupal::logger('qls_sect6')->debug('502 <pre>@values</pre>', ['@values' => print_r($namespace, TRUE)]);
    if (count($namespace) === 2) {
      $root_namespace = $namespace[0];
      $sub_namespace = $namespace[1];
    } else {
      $root_namespace = $namespace[0];
    }
    // \Drupal::logger('qls_sect6')->debug('508 <pre>@values</pre>', ['@values' => print_r($root_namespace, TRUE)]);
    // $mosaicNames = $namespaceApiInstance->getMosaicsNames($mosaicIds);
    if(empty($sub_namespace)){
      $parnetNameId = IdGenerator::generateNamespaceId($root_namespace); //ルートネームスペース名
    }else{
      $root_namespaceid = IdGenerator::generateNamespaceId($root_namespace);
      $parnetNameId = IdGenerator::generateNamespaceId($sub_namespace, $root_namespaceid);
    }
    // $parnetNameId = $root_namespaceid;
    // $parnetNameId = gmp_intval(gmp_init($root_namespaceid, 16)); // GMPを使用
    $name = $form_state->getValue('sub_namespace_name'); //サブネームスペース名

    // // Tx作成
    // $tx = new NamespaceRegistrationTransactionV1(
    //   network: $networkType,
    //   signerPublicKey: $ownerKey->publicKey, // 署名者公開鍵
    //   deadline: new Timestamp($facade->now()->addHours(2)),
    //   duration: new BlockDuration(86400), // 有効期限
    //   parentId: new NamespaceId($parnetNameId),
    //   id: new NamespaceId(IdGenerator::generateNamespaceId($name, $parnetNameId)),
    //   registrationType: new NamespaceRegistrationType(
    //     NamespaceRegistrationType::CHILD
    //   ),
    //   name: $name,
    // );
    // $facade->setMaxFee($tx, 200);

    // $parnetNameId = IdGenerator::generateNamespaceId("qls"); //ルートネームスペース名
    // $name = "d"; //サブネームスペース名
    
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
    // \Drupal::logger('qls_sect6')->notice('<pre>@object</pre>', ['@object' => print_r($payload, TRUE)]); 

    // \Drupal::logger('qls_sect6')->notice('<pre>@object</pre>', ['@object' => print_r($networkType, TRUE)]); 
    // $config = new Configuration();
    // $config->setHost($node_url);
    // $client = \Drupal::httpClient();
    // $apiInstance = new TransactionRoutesApi($client, $config);
    
    // try {
    //   $result = $apiInstance->announceTransaction($payload);
    //   // echo $result . PHP_EOL;
    //   $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));
    // } catch (Exception $e) {
    //   \Drupal::logger('qls_sect6')->error('トランザクションの発行中にエラーが発生しました: @message', ['@message' => $e->getMessage()]);
    // }

    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));

  }

}
