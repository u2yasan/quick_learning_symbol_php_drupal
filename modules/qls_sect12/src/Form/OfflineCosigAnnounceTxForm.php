<?php

namespace Drupal\qls_sect12\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\Symbol\Models\TransactionFactory;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class OfflineCosigAnnounceTxForm extends FormBase {

  /**
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;

  /**
   * @var \Drupal\quicklearning_symbol\Service\TransactionService
   */
  protected $transactionService;
  
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
    return 'offline_cosig_announce_tx_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '12.3 '.$this->t('アナウンス'),
    ];

    $form['signed_payload'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Signed Payload'),
      '#description' => $this->t('Enter the signed payload.'),
      '#required' => TRUE,
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

    // $facade = $this->facadeService->getFacade();
    // $networkType = $this->facadeService->getNetworkTypeObject();
    $transactionApi = $this->transactionService->getTransactionApi();
    
    $signed_payload = $form_state->getValue('signed_payload');
    $recreatedTx = TransactionFactory::deserialize(hex2bin($signed_payload));

    // $signTxHash = $form_state->getValue('sign_tx_hash');
    // // $aggregateTx = $facade->getTransaction(new Hash256($signTxHash));
    // $cosig_siner_pubkey_str = $form_state->getValue(['sig_field', 'cosig_siner_pubkey']);
    // $cosig_siner_pubkey = new PublicKey($cosig_siner_pubkey_str);
    // $cosig_siner_signature = $form_state->getValue(['sig_field', 'cosig_siner_signature']);
    // $account_pvtKey = $form_state->getValue(['sig_field', 'account_pvtKey']);
    // $accountKey = $facade->createAccount(new PrivateKey($account_pvtKey));
    // $cosigSignerSignatureStr = $form_state->getValue(['sig_field', 'cosig_siner_signature']);
    // $cosigSignerSignature = new Cosignature($cosigSignerSignatureStr);

    // 連署者の署名を追加
    // $cosignature = new Cosignature();
    // // $signTxHash = $facade->hashTransaction($aggregateTx);
    // $cosignature->parentHash = new Hash256($signTxHash);
    // $cosignature->version = 0;
    // $cosignature->signerPublicKey = $cosig_siner_pubkey;
    // $cosignature->signature = $cosig_siner_signature;
    // array_push($recreatedTx->cosignatures, $cosignature);

    $signedPayload = ["payload" => strtoupper(bin2hex($recreatedTx->serialize()))];
    // echo $signedPayload;
    // \Drupal::logger('qls_sect12')->info('signedPayload: @signedPayload', ['@signedPayload' => $signedPayload]);

    try {
      $result = $transactionApi->announceTransaction($signedPayload);
      $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));
    } catch (Exception $e) {
      $this->messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()])); 
    }

  }
}