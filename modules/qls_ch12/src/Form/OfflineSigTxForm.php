<?php

namespace Drupal\qls_ch12\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolSdk\Symbol\Models\EmbeddedTransferTransactionV1;
use SymbolSdk\Symbol\Models\AggregateCompleteTransactionV2;
use SymbolSdk\Symbol\Models\PublicKey;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedAddress;

use Drupal\quicklearning_symbol\Service\FacadeService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class OfflineSigTxForm extends FormBase {

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
    return 'offline_sig_tx_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form['#attached']['library'][] = 'qls_ch12/account_restriction';
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '12.1 '.$this->t('トランザクション作成'),
    ];

    $form['account_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Account Private Key'),
      '#description' => $this->t('Enter the private key of the account.'),
      '#required' => TRUE,
    ];

    $form['symbol_address'] = [
      '#markup' => '<div id="symbol_address">Symbol Address</div>',
    ];

    $num_innertxs = $form_state->get('num_innertxs');
    if ($num_innertxs === NULL) {
      $innertx_field = $form_state->set('num_innertxs', 1);
      $num_innertxs = 1;
    }
    $form['#tree'] = TRUE;
    $form['innertxs_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Inner Transactions'),
      '#prefix' => '<div id="innertxs-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $num_innertxs; $i++) {

      $form['innertxs_fieldset'][$i]['signer_pubkey'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Signer PublicKey'),
        '#description' => $this->t('Enter the publick Key of the addition.')
      ];

      $form['innertxs_fieldset'][$i]['recipient_address'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Recipient Address'),
        '#description' => $this->t('Enter the address of the recipient. TESTNET: Start with T / MAINNET: Start with N'),
        // '#default_value' => 'Hello, Symbol!',
      ];

      $form['innertxs_fieldset'][$i]['message'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Message'),
        '#description' => $this->t('Max: 1023 byte.'),
        // '#default_value' => 'Hello, Symbol!',
      ];

    }
    $form['innertxs_fieldset']['actions'] = [
      '#type' => 'actions',
    ];

    $form['innertxs_fieldset']['actions']['add_innertx'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more inner Tx'),
      '#submit' => ['::addOneInnertx'],
      '#ajax' => [
        'callback' => '::addMoreInnertxCallback',
        'wrapper' => 'innertxs-fieldset-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];
    if ($num_innertxs > 1) {
      $form['innertxs_fieldset']['actions']['remove_innertx'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one inner Tx'),
        '#submit' => ['::removeInnertxCallback'],
        '#ajax' => [
          'callback' => '::addMoreInnertxCallback',
          'wrapper' => 'innertxs-fieldset-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  
  public function addMoreInnertxCallback(array &$form, FormStateInterface $form_state) {
    return $form['innertxs_fieldset'];
  }
  public function addOneInnertx(array &$form, FormStateInterface $form_state) {
    $innertx_field = $form_state->get('num_innertxs');
    $add_button = $innertx_field + 1;
    $form_state->set('num_innertxs', $add_button);
    $form_state->setRebuild();
  }
  public function removeInnertxCallback(array &$form, FormStateInterface $form_state) {
    $innertx_field = $form_state->get('num_innertxs');
    if ($innertx_field > 1) {
      $remove_button = $innertx_field - 1;
      $form_state->set('num_innertxs', $remove_button);
    }
    $form_state->setRebuild();
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $facade = $this->facadeService->getFacade();
    $networkType = $this->facadeService->getNetworkTypeObject();

    $account_pvtKey = $form_state->getValue('account_pvtKey');
    // \Drupal::logger('qls_ch12')->info('account_pvtKey: @account_pvtKey', ['@account_pvtKey' => $account_pvtKey]);
    $accountKey = $facade->createAccount(new PrivateKey($account_pvtKey));
    $accountPubKey = $accountKey->publicKey;
    // \Drupal::logger('qls_ch12')->info('accountKey_pubKey: @accountKey', ['@accountKey' => $accountKey->publicKey]);

    $innerTx = [];
    $num_innertxs = $form_state->get('num_innertxs');
    for ($i = 0; $i < $num_innertxs; $i++) {
      $signer_pubkey = $form_state->getValue(['innertxs_fieldset', $i, 'signer_pubkey']);
      $recipient_address = $form_state->getValue(['innertxs_fieldset', $i, 'recipient_address']);
      $message = "\0".$form_state->getValue(['innertxs_fieldset', $i, 'message']);
      $innerTx[] = new EmbeddedTransferTransactionV1(
        network: $networkType,
        signerPublicKey: new PublicKey($signer_pubkey),
        recipientAddress: new UnresolvedAddress($recipient_address),
        mosaics:[],
        message: $message,
      );
    }
    // \Drupal::logger('qls_ch12')->info('innerTx: @innerTx', ['@innerTx' => print_r($innerTx, TRUE)]);

    // マークルハッシュの算出
    $embeddedTransactions = $innerTx;
    $merkleHash = $facade->hashEmbeddedTransactions($embeddedTransactions);
    // \Drupal::logger('qls_ch12')->info('Merkle Hash: @merkleHash', ['@merkleHash' => $merkleHash]);
    // アグリゲートTx作成
    $aggregateTx = new AggregateCompleteTransactionV2(
      network: $networkType,
      signerPublicKey: $accountPubKey,
      deadline: new Timestamp($facade->now()->addHours(2)),
      transactionsHash: $merkleHash,
      transactions: $embeddedTransactions,
    );
    // \Drupal::logger('qls_ch12')->info('Aggregate Tx: @aggregateTx', ['@aggregateTx' => print_r($aggregateTx, TRUE)]);
    $facade->setMaxFee($aggregateTx, 100, $num_innertxs);

    // 署名
    $signTxHash = $facade->hashTransaction($aggregateTx);
    $signedHash = $accountKey->signTransaction($aggregateTx);
    $signedPayload = $facade->attachSignature($aggregateTx, $signedHash);
    // \Drupal::logger('qls_ch12')->info('Signed Payload: @signedPayload', ['@signedPayload' => print_r($signedPayload['payload'], TRUE)]);
    $this->messenger()->addMessage($this->t('Signe Tx Hash @signTxHash', ['@signTxHash' => $signTxHash]));
    $this->messenger()->addMessage($this->t('Signed Hash @signedHash', ['@signedHash' => $signedHash]));
    $this->messenger()->addMessage($this->t('Signed Payload <pre>@signedPayload</pre>', ['@signedPayload' => print_r($signedPayload['payload'], TRUE)]));
  }
}