<?php

namespace Drupal\qls_sect6\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolSdk\Symbol\Models\NamespaceRegistrationTransactionV1;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\BlockDuration;
use SymbolSdk\Symbol\Models\NamespaceId;
use SymbolSdk\Symbol\IdGenerator;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\NetworkService;
use Drupal\quicklearning_symbol\Service\TransactionService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class CreateNamespaceForm extends FormBase {

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
  protected $networkService;

  /**
   * TransactionServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\TransactionService
   */
  protected $transactionService;


  /**
   * コンストラクタでServiceを注入
   */
  public function __construct(FacadeService $facade_service, 
    NetworkService $network_service, 
    TransactionService $transaction_service) {
      $this->facadeService = $facade_service;
      $this->networkService = $network_service;
      $this->transactionService = $transaction_service;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),    
      $container->get('quicklearning_symbol.network_service'),  
      $container->get('quicklearning_symbol.transaction_service')   
    );
  }

  /**
   * Getter method for Form ID.
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'create_namespace_form';
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
    $form['#attached']['library'][] = 'qls_sect6/create_namespace';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Nemaspace'),
    ];

    $form['ownder_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Owner Private Key'),
      '#description' => $this->t('Enter the private key of the owner.'),
      '#required' => TRUE,
    ];

    $form['symbol_address'] = [
      '#markup' => '<div id="symbol_address">Symbol Address</div>',
    ];

    $form['root_namespace_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Root Namespace Name'),
      '#description' => $this->t('Lowercase alphabet, numbers 0-9, hyphen, and underscore'),
      '#required' => TRUE,
    ];

    $form['duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dulation'),
      '#description' => $this->t('Min:86400. Max:5256000'),
      // '#default_value' => 'Hello, Symbol!',
    ];

    $form['epov'] = [
      '#type' => 'item',
      '#title' => $this->t('Estimated Period Of Validity'),
      '#markup' => '<div id="estimated_period_of_validity">'.$this->t('00d 00h 00m').'</div>',
    ];

    $form['estimated_rental_fee'] = [
      '#type' => 'item', 
      '#title' => $this->t('Estimated Rental Fee'),
      '#markup' => '<div id="estimated_rental_fee">0XYM</div>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Make Namespace'),
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
    
    $root_namespace_name = $form_state->getValue('root_namespace_name');
    if (!preg_match('/^[a-z0-9_-]+$/', $root_namespace_name)) {
      $form_state->setErrorByName('root_namespace_name', $this->t('Root Namespace Name must be lowercase alphabet, numbers 0-9, hyphen, and underscore.'));
    }
    if (strlen($root_namespace_name) > 64) {
      $form_state->setErrorByName('root_namespace_name', $this->t('Root Namespace Name cannot exceed 64 characters.'));
    }
    //The Duration field must be 86400(30day) or more 
    // 期間はブロック数で指定します。1 ブロックを30 秒として計算しました。最低で30 日分はレンタルする必要があります（最大で1825 日分, 5年）。
    $duration = $form_state->getValue('duration');
    if (!is_numeric($duration) || $duration < 86400 || $duration > 5256000) {
      $form_state->setErrorByName('duration', $this->t('The duration must be between 86400 and 5256000.'));
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

    $ownder_pvtKey = $form_state->getValue('ownder_pvtKey');
    $ownerKey = $facade->createAccount(new PrivateKey($ownder_pvtKey));
    $root_namespace_name = $form_state->getValue('root_namespace_name');

    $networkApi = $this->networkService->getNetworkRoutesApi();
    $rootNsperBlock = $networkApi->getRentalFees()->getEffectiveRootNamespaceRentalFeePerBlock();

    $duration = $form_state->getValue('duration');
    $blockDuration = new BlockDuration($duration);
   
    $rootNsRenatalFeeTotal = $duration * $rootNsperBlock;
    // $rentalDays = 365;
    // $rentalBlock = ($rentalDays * 24 * 60 * 60) / 30;
    // $rootNsRenatalFeeTotal = $rentalBlock * $rootNsperBlock;

    $childNamespaceRentalFee = $networkApi->getRentalFees()->getEffectiveChildNamespaceRentalFee();
   
    $tx = new NamespaceRegistrationTransactionV1(
      network: $networkType,
      signerPublicKey: $ownerKey->publicKey, // 署名者公開鍵
      deadline: new Timestamp($facade->now()->addHours(2)),
      duration: $blockDuration, // 有効期限
      id: new NamespaceId(IdGenerator::generateNamespaceId($root_namespace_name)), //必須
      name: $root_namespace_name,
    );
    $facade->setMaxFee($tx, 100);

    // 署名
    $sig = $ownerKey->signTransaction($tx);
    $payload = $facade->attachSignature($tx, $sig);

    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));

  }

}
