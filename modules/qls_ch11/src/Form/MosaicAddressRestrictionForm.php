<?php

namespace Drupal\qls_ch11\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolSdk\Symbol\Models\MosaicAddressRestrictionTransactionV1;

use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedAddress;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;
use SymbolSdk\Symbol\Metadata;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;

/**
 * 
 * @see \Drupal\Core\Form\FormBase
 */
class MosaicAddressRestrictionForm extends FormBase {

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
    return 'mosaic_address_restriction_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form['#attached']['library'][] = 'qls_ch11/account_restriction';
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '11.2.2 '.$this->t('アカウントへのモザイク制限適用'),
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

    $form['target_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target Address'),
      '#required' => TRUE,
    ];

    $form['mosaic_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mosaic ID'),
      '#required' => TRUE,
    ];

    $form['restriction_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Restriction Key'),
      '#required' => TRUE,
    ];
    
    $form['new_restriction_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New Restriction Value'),
      '#required' => TRUE,
      '#description' => $this->t('Integer value'),
    ];
    $form['previous_restriction_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous Restriction Value'),
      '#required' => TRUE,
      '#description' => $this->t('Integer value. -1 if not set.'),
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
    // \Drupal::logger('qls_ch11')->info('account_pvtKey: @account_pvtKey', ['@account_pvtKey' => $account_pvtKey]);
    $accountKey = $facade->createAccount(new PrivateKey($account_pvtKey));
    $accountPubKey = $accountKey->publicKey;
    // \Drupal::logger('qls_ch11')->info('accountKey_pubKey: @accountKey', ['@accountKey' => $accountKey->publicKey]);

    $target_address = $form_state->getValue('target_address');
    $targetAddress = new UnresolvedAddress($target_address);

    $mosaic_id = "0x".$form_state->getValue('mosaic_id');
    $mosaicID = new UnresolvedMosaicId($mosaic_id);

    // キーの値と設定
    $restrictionKey = $form_state->getValue('restriction_key');
    $keyId = Metadata::metadataGenerateKey($restrictionKey);

    $previousRestrictionValue = $form_state->getValue('previous_restriction_value');

    $newRestrictionValue = $form_state->getValue('new_restriction_value');

    // グローバルモザイク制限
    $mosaicAddressResTx = new MosaicAddressRestrictionTransactionV1(
      network: $networkType,
      signerPublicKey: $accountPubKey,
      deadline: new Timestamp($facade->now()->addHours(2)),
      mosaicId: $mosaicID,
      restrictionKey: $keyId,
      previousRestrictionValue: $previousRestrictionValue,
      newRestrictionValue: $newRestrictionValue,
      targetAddress: $targetAddress,
    );

    $facade->setMaxFee($mosaicAddressResTx, 100);
    // \Drupal::logger('qls_ch11')->info('tx: @tx', ['@tx' => $tx]);
    // 署名
    $sig = $accountKey->signTransaction($mosaicAddressResTx);
    // \Drupal::logger('qls_ch11')->info('sig: @sig', ['@sig' => $sig]);
    $payload = $facade->attachSignature($mosaicAddressResTx, $sig);
    \Drupal::logger('qls_ch11')->info('Payload: @payload', ['@payload' => print_r($payload, TRUE)]);
    try {
      $result = $transactionApi->announceTransaction($payload);
      $this->messenger()->addMessage($this->t('AccountAddressRestriction Transaction successfully announced: @result', ['@result' => $result]));
    } catch (Exception $e) {
      \Drupal::logger('qls_ch11')->error('Transaction Failed: @message', ['@message' => $e->getMessage()]);
    } 
  }
}