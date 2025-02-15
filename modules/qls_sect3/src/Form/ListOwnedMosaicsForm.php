<?php

namespace Drupal\qls_sect3\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\Symbol\Address;

use Drupal\quicklearning_symbol\Service\AccountService;

class ListOwnedMosaicsForm extends FormBase {

  /**
   * Getter method for Form ID.
   *
   * The form ID is used in implementations of hook_form_alter() to allow other
   * modules to alter the render array built by this form controller. It must be
   * unique site wide. It normally starts with the providing module's name.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'list_owned_mosaics_form';
  }
  /**
   * AccountServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\AccountService
   */
  protected $accountService;

  /**
   * コンストラクタでAccountServiceを注入
   */
  public function __construct(AccountService $account_service) {
    $this->accountService = $account_service;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.account_service')
    );
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#markup' => '3.3.1 '. $this->t('Retrieve a list of owned mosaics'),
    ];
    // $form['network_type'] = [
    //   '#type' => 'radios',
    //   '#title' => $this->t('Network Type'),
    //   '#description' => $this->t('Select either testnet or mainnet'),
    //   '#options' => [
    //     'testnet' => $this->t('Testnet'),
    //     'mainnet' => $this->t('Mainnet'),
    //   ],
    //   '#default_value' => 'testnet', // デフォルト選択を設定
    //   '#required' => TRUE,
    // ];

    $form['raw_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Raw Address'),
      '#description' => 'ex:TAEVFNMAXX32XO5M4CQEA5PZJFYTEACJLXZNPNY '. $this->t('39文字（16進数）'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get Account Info'),
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
    $raw_address = $form_state->getValue('raw_address');
    if (strlen($raw_address) !=  39) {
      // Set an error for the form element with a key of "public_key".
      $form_state->setErrorByName('raw_address', $this->t('The raw_address must be 39 characters long.'));
    }
  }

  /**
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
    // $network_type = $form_state->getValue('network_type');
    // $config = \Drupal::config('quicklearning_symbol.settings');
    // $network_type = $config->get('network_type');

    $raw_address = $form_state->getValue('raw_address');
    $address = new Address($raw_address);

    // ノードURLを設定
    // if ($network_type === 'testnet') {
    //   $node_url = 'http://sym-test-03.opening-line.jp:3000';
    // } elseif ($network_type === 'mainnet') {
    //   $node_url = 'http://sym-main-03.opening-line.jp:3000';
    // }
    // SymbolAccountServiceを使ってアカウント情報を取得
    // $account_info = $this->accountService->getAccountInfo($network_type, $address);
    
    // $account_info = $this->accountService->getAccountInfo($address);

    // if ($account_info) {
    //   // JSON形式でアカウント情報を表示
    //   $json_data = json_encode($account_info, JSON_PRETTY_PRINT);
    //   \Drupal::messenger()->addMessage($this->t('Account information: <pre>@data</pre>', ['@data' => $json_data]));
    // }
    // else {
    //   \Drupal::messenger()->addMessage($this->t('Failed to retrieve account information.'), 'error');
    // }

    $accountApi = $this->accountService->getAccountApi();
    $account_info = $accountApi->getAccountInfo($address);
    // \Drupal::messenger()->addMessage($this->t('Account information: <pre>@object</pre>', ['@object' =>  print_r($account_info, TRUE)]))
    $json_data = json_encode($account_info, JSON_PRETTY_PRINT);
    \Drupal::messenger()->addMessage($this->t('Account information: <pre>@object</pre>', ['@object' => $json_data])); 
    

  }

}
