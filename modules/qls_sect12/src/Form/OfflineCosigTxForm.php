<?php

namespace Drupal\qls_sect12\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;
// use SymbolSdk\Facade\SymbolFacade;

use SymbolSdk\Symbol\Models\Hash256; //as SymbolHash256;
// use SymbolSdk\CryptoTypes\Hash256;
use SymbolSdk\CryptoTypes\Signature;
use SymbolSdk\Symbol\Models\Cosignature;
// use SymbolSdk\Symbol\Models\NetworkType;

use SymbolSdk\Symbol\Models\TransactionFactory;

use Drupal\quicklearning_symbol\Service\FacadeService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class OfflineCosigTxForm extends FormBase {

  /**
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;

  /**
   * コンストラクタでServiceを注入
   */
  public function __construct(
    FacadeService $facade_service
    ) {
      $this->facadeService = $facade_service;
  }

  /**
   * createでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'offline_cosig_tx_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form['#attached']['library'][] = 'qls_sect12/account_restriction';
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '12.2 '.$this->t('連署'),
    ];

    $form['#tree'] = TRUE;

    $form['payload_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Verify Payload'),
      '#prefix' => '<div id="payload-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['payload_fieldset']['payload'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Payload'),
      '#description' => $this->t('Enter the payload.'),
      '#required' => TRUE,
    ];

    $form['payload_fieldset']['verify_signature'] = [
      '#type' => 'button',
      '#value' => $this->t('Verify Signature'),
      '#description' => $this->t('Verify the signature.'),
      '#ajax' => [
        'callback' => '::verifySignatureCallback',
        'wrapper' => 'sig-field-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    $form['sign_tx_hash'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sign Tx Hash'),
      '#description' => $this->t('Enter the aggregate tx hash.'),
      '#required' => TRUE,
    ];

    $form['sig_field'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'sig-field-wrapper'],
    ];
    // $sig_verified = $form_state->getValue('sig_verified');
    // if ($sig_verified) {
      $form['sig_field']['account_pvtKey'] = [
        '#type' => 'password',
        '#title' => $this->t('Cosigning Account Private Key'),
        '#description' => $this->t('Enter the private key of the account.'),
        '#required' => TRUE,
      ];

      $form['sig_field']['symbol_address'] = [
        '#markup' => '<div id="symbol_address">Symbol Address</div>',
      ];

      $form['sig_field']['actions'] = [
        '#type' => 'actions',
      ];
      $form['sig_field']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];
    // }

    return $form;
  }

  
  public function verifySignatureCallback(array &$form, FormStateInterface $form_state) {
    // while (ob_get_level()) {
    //   ob_end_clean();
    //   }
    // $network_type = $form_state->getValue('network_type');
    // $facade = new SymbolFacade($network_type);
    $facade = $this->facadeService->getFacade();
    // $networkType = $this->facadeService->getNetworkTypeObject();
  
    $payload = $form_state->getValue(['payload_fieldset', 'payload']);
    // \Drupal::logger('qls_sect12')->info('payload: @payload', ['@payload' => $payload]);
    $tx = TransactionFactory::deserialize(hex2bin($payload)); // バイナリデータにする
    \Drupal::logger('qls_sect12')->info('tx: @tx', ['@tx' => print_r($tx, TRUE)]);
    $signature = new Signature($tx->signature);
    $res = $facade->verifyTransaction($tx, $signature);
    \Drupal::logger('qls_sect12')->info('verify: @res', ['@res' => $res]);
    if ($res) {
      $this->messenger()->addMessage($this->t('Verify: Success'));
      $form_state->setValue('sig_verified', TRUE);
    } else {
      $this->messenger()->addMessage($this->t('Verify: Failed'));
    }
  
    $form_state->setRebuild();
    return $form;
  }

  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // $network_type = $form_state->getValue('network_type');
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
    // $networkType = $this->facadeService->getNetworkTypeObject();
    

    $payload = $form_state->getValue(['payload_fieldset', 'payload']);
    $tx = TransactionFactory::deserialize(hex2bin($payload)); // バイナリデータにする

    $account_pvtKey = $form_state->getValue(['sig_field','account_pvtKey']);
    \Drupal::logger('qls_sect12')->info('account_pvtKey: @account_pvtKey', ['@account_pvtKey' => $account_pvtKey]);
    $accountKey = $facade->createAccount(new PrivateKey($account_pvtKey));
    // $accountPubKey = $accountKey->publicKey;
    // $cosignature = $facade->cosignTransaction($accountKey->keyPair, $tx, true);
    $cosignature = $facade->cosignTransaction($accountKey->keyPair, $tx);
    $cosig_siner_pubkey = $cosignature->signerPublicKey;
    $signedTxSignature = $cosignature->signature;

    $signTxHash = $form_state->getValue('sign_tx_hash');

    // $recreatedTx = TransactionFactory::deserialize(hex2bin($signedPayload['payload']));
    // 連署者の署名を追加
    $cosig = new Cosignature();
    // $signTxHash = $facade->hashTransaction($aggregateTx);
    // $cosig->parentHash = new SymbolHash256($signTxHash);
    // $cosig->parentHash = new SymbolHash256(hex2bin($signTxHash));
    $cosig->parentHash = new Hash256($signTxHash);
    // $cosig->parentHash = new Hash256(hex2bin($signTxHash));
    $cosig->version = 0;
    $cosig->signerPublicKey = $cosig_siner_pubkey;
    $cosig->signature = $signedTxSignature;
    array_push($tx->cosignatures, $cosig);

    $signedPayload = ["payload" => strtoupper(bin2hex($tx->serialize()))];
    \Drupal::logger('qls_sect12')->info('signedPayload: @signedPayload', ['@signedPayload' => print_r($signedPayload, TRUE)]);
    $this->messenger()->addMessage($this->t('signedPayload: @signedPayload', ['@signedPayload' => $signedPayload['payload']])); 

    // \Drupal::logger('qls_sect12')->info('signedTxSignature: @signedTxSignature', ['@signedTxSignature' => $signedTxSignature]);
    // $this->messenger()->addMessage($this->t('signedTxSignature: <pre>@signedTxSignature</pre>', ['@signedTxSignature' => $signedTxSignature]));
    // $signedTxSignerPublicKey = $cosignature->signerPublicKey;
    // \Drupal::logger('qls_sect12')->info('signedTxSignerPublicKey: @signedTxSignerPublicKey', ['@signedTxSignerPublicKey' => $signedTxSignerPublicKey]);
    // $this->messenger()->addMessage($this->t('signedTxSignerPublicKey: <pre>@signedTxSignerPublicKey</pre>', ['@signedTxSignerPublicKey' => $signedTxSignerPublicKey]));

  }
}