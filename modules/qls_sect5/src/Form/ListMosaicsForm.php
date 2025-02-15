<?php

namespace Drupal\qls_sect5\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\AccountService;
use Drupal\quicklearning_symbol\Service\MosaicService;

/**
 * Implements the SimpleForm form controller.
 * @see \Drupal\Core\Form\FormBase
 */
class ListMosaicsForm extends FormBase {

  
  /**
   * FacadeServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;

  /**
   * AccountServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\AccountService
   */
  protected $accountService;
  /**
   * MosaicServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\MosaicService
   */
  protected $mosaicService;

  /**
   * コンストラクタでAccountServiceを注入
   */
  public function __construct(FacadeService $facade_service, AccountService $account_service, MosaicService $mosaic_service) {
    $this->facadeService = $facade_service;
    $this->accountService = $account_service;
    $this->mosaicService = $mosaic_service;
  }
  /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),    
      $container->get('quicklearning_symbol.account_service'),  
      $container->get('quicklearning_symbol.mosaic_service')  
    );
  }

  /**
   * Getter method for Form ID.
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'list_mosaics_form';
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
      '#markup' => $this->t('モザイク作成したアカウントが持つモザイク情報を確認'),
    ];

    $form['account_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Account Private Key'),
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


    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('List Mosaics'),
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
    $data = $form_state->get('mosaic_table_data');
    if ($data) {
      // テーブルヘッダーを定義
      $headers = [
        $this->t('ID'),
        $this->t('Supply'),
        $this->t('Owner Address'),
        $this->t('Divisibility'),
        $this->t('Flags'),
        $this->t('Duration'),
        $this->t('Start Height'),
      ];

      // テーブル行を作成
      $rows = [];
      foreach ($data as $item) {
        $rows[] = [
          $item['id'],
          $item['supply'],
          $item['owner_address'],
          $item['divisibility'],
          $item['flags'],
          $item['duration'],
          $item['start_height'],
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
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  // public function validateForm(array &$form, FormStateInterface $form_state) {
  //   $title = $form_state->getValue('network_type');
  //   if (strlen($title) < 5) {
  //     // Set an error for the form element with a key of "title".
  //     $form_state->setErrorByName('title', $this->t('The title must be at least 5 characters long.'));
  //   }
  // }
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
   * The submitForm method is the default method called for any submit elements.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $facade = $this->facadeService->getFacade();
    // $networkType = $this->facadeService->getNetworkTypeObject();


    $pvtKey = $form_state->getValue('account_pvtKey');
    $accountKey = $facade->createAccount(new PrivateKey($pvtKey));

    $accountApi = $this->accountService->getAccountApi();
    $account = $accountApi->getAccountInfo($accountKey->address);

    // $account = $accountApiInstance->getAccountInfo($accountKey->address);
    foreach($account->getAccount()->getMosaics() as $mosaic) {
      // try {
      //   $mosaicInfo = $this->mosaicService->getMosaic($network_type, $mosaic->getId());
      //   $mosaicInfoArray[] = $mosaicInfo;
      // } catch (\Exception $e) {
      //   $this->messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()]));
      // }
      
      $mosaicApi = $this->mosaicService->getMosaicApi();
      $mosaicInfo = $mosaicApi->getMosaic($mosaic->getId());
      $mosaicInfoArray[] = $mosaicInfo;
      // $mocaisInfo[] = $mosaicApiInstance->getMosaic($mosaic->getId());
    }
    // \Drupal::logger('qls_sect5')->notice('<pre>@object</pre>', ['@object' => print_r($mocaisInfo, TRUE)]);
    $flattenedData = $this->flattenMosaicData($mosaicInfoArray);

    // $form_state->set('view_displayed', TRUE);
    // フォームステートにデータを設定
    $form_state->set('mosaic_table_data', $flattenedData);
    $form_state->setRebuild();
    
  }

  function flattenMosaicData($mosaicInfoArray) {
    $flattenedData = [];

    foreach ($mosaicInfoArray as $mosaicInfo) {
        $mosaic = $mosaicInfo->getMosaic(); // MosaicDTO オブジェクトを取得

        $flattenedData[] = [
            'id' => $mosaic->getId(), // モザイクID
            'supply' => $mosaic->getSupply(), // サプライ量
            'owner_address' => $mosaic->getOwnerAddress(), // 所有者アドレス
            'divisibility' => $mosaic->getDivisibility(), // 分割可能性
            'flags' => $mosaic->getFlags(), // フラグ
            'duration' => $mosaic->getDuration(), // 有効期間
            'start_height' => $mosaic->getStartHeight(), // 開始ブロック高さ
        ];
    }

    return $flattenedData;
  }

}
