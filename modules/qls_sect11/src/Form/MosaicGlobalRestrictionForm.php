<?php

namespace Drupal\qls_sect11\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolSdk\Symbol\Models\MosaicRestrictionType;
use SymbolSdk\Symbol\Models\MosaicGlobalRestrictionTransactionV1;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;
use SymbolSdk\Symbol\Metadata;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class MosaicGlobalRestrictionForm extends FormBase {

  /**
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;

  /**
   * @var \Drupal\quicklearning_symbol\Service\TransactionService
   */
  protected $transactionService;
  
  /**
   *
   * @var \Drupal\quicklearning_symbol\Service\AccountService
   */
  protected $accountService;

  /**
   * コンストラクタでServiceを注入
   */
  public function __construct(
    FacadeService $facade_service,
    TransactionService $transaction_service
    ) {
      $this->facadeService = $facade_service;
      $this->transactionService = $transaction_service;
  }

  /**
   * createでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),         
      $container->get('quicklearning_symbol.transaction_service')
    );
  }  

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mosaic_global_restriction_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form['#attached']['library'][] = 'qls_sect11/account_restriction';
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '11.2.1 '.$this->t('グローバル制限機能つきモザイクの作成'),
    ];

    $form['account_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Account Private Key'),
      '#description' => $this->t('Enter the private key of the mosaic owner account.'),
      '#required' => TRUE,
    ];

    $form['symbol_address'] = [
      '#markup' => '<div id="symbol_address">Symbol Address</div>',
    ];

    $form['mosaic_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mosaic ID'),
      '#required' => TRUE,
    ];

    $form['restriction_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Restriction Type'),
      '#description' => $this->t('Select the type of restriction.'),
      '#options' => [
        '0' => $this->t('NONE'),
        '1' => $this->t('EQ:equal to'),
        '2' => $this->t('NE:not equal to'),
        '3' => $this->t('LT:less than'),
        '4' => $this->t('LE:less than or equal to'),
        '5' => $this->t('GT:greater than'),
        '6' => $this->t('GE:greater than or equal to'),
      ],
      '#required' => TRUE,
    ];

    $form['restriction_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Restriction Key'),
      '#required' => TRUE,
    ];
    
    $form['restriction_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Restriction Value'),
      '#required' => TRUE,
      '#description' => $this->t('Integer value'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $facade = $this->facadeService->getFacade();
    $networkType = $this->facadeService->getNetworkTypeObject();
    $transactionApi = $this->transactionService->getTransactionApi();

    // $namespaceIds = IdGenerator::generateNamespacePath('symbol.xym');
    // $namespaceId = new NamespaceId($namespaceIds[count($namespaceIds) - 1]);

    $account_pvtKey = $form_state->getValue('account_pvtKey');
    // \Drupal::logger('qls_sect11')->info('account_pvtKey: @account_pvtKey', ['@account_pvtKey' => $account_pvtKey]);
    $accountKey = $facade->createAccount(new PrivateKey($account_pvtKey));
    $accountPubKey = $accountKey->publicKey;
    // \Drupal::logger('qls_sect11')->info('accountKey_pubKey: @accountKey', ['@accountKey' => $accountKey->publicKey]);

    $mosaic_id = "0x".$form_state->getValue('mosaic_id');
    $mosaicID = new UnresolvedMosaicId($mosaic_id);

    $restriction_type = $form_state->getValue('restriction_type');
    $mosaicRestrictionType = new MosaicRestrictionType($restriction_type);

    // キーの値と設定
    $restrictionKey = $form_state->getValue('restriction_key');
    $keyId = Metadata::metadataGenerateKey($restrictionKey);

    $restrictionValue = $form_state->getValue('restriction_value');
    // Validate $restrictionValue
    if (!is_numeric($restrictionValue)) {
      throw new \InvalidArgumentException('Restriction value must be a well-formed number.');
    }
    // グローバルモザイク制限
    $mosaicGlobalResTx = new MosaicGlobalRestrictionTransactionV1(
      network: $networkType,
      signerPublicKey: $accountPubKey,
      deadline: new Timestamp($facade->now()->addHours(2)),
      mosaicId: $mosaicID,
      restrictionKey: $keyId,
      newRestrictionValue: $restrictionValue,
      newRestrictionType: $mosaicRestrictionType
    );
    // 更新する場合は以下も設定する必要あり
    // - mosaicGlobalResTx.previousRestrictionValue
    // - mosaicGlobalResTx.previousRestrictionType
    // previousRestrictionValue: -1
    // previousRestrictionType: MosaicRestrictionType::NONE

    $facade->setMaxFee($mosaicGlobalResTx, 100);
    // \Drupal::logger('qls_sect11')->info('tx: @tx', ['@tx' => $tx]);
    // 署名
    $sig = $accountKey->signTransaction($mosaicGlobalResTx);
    // \Drupal::logger('qls_sect11')->info('sig: @sig', ['@sig' => $sig]);
    $payload = $facade->attachSignature($mosaicGlobalResTx, $sig);

    try {
      $result = $transactionApi->announceTransaction($payload);
      $this->messenger()->addMessage($this->t('AccountAddressRestriction Transaction successfully announced: @result', ['@result' => $result]));  
    } catch (Exception $e) {
      \Drupal::logger('qls_sect11')->error('Transaction Failed: @message', ['@message' => $e->getMessage()]);
    } 
  }
}