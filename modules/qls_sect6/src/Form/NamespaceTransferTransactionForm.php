<?php

namespace Drupal\qls_sect6\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Symbol\Models\TransferTransactionV1;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedMosaic;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;
use SymbolSdk\Symbol\Models\Amount;
use SymbolSdk\Symbol\Models\UnresolvedAddress;
use SymbolSdk\Symbol\Models\NamespaceId;
use SymbolSdk\Symbol\Address;
use SymbolSdk\Symbol\IdGenerator;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;

/**
 * Implements the SimpleForm form controller.
 *
 * This example demonstrates a simple form with a single text input element. We
 * extend FormBase which is the simplest form base class used in Drupal.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class NamespaceTransferTransactionForm extends FormBase {

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
   * コンストラクタでSymbolAccountServiceを注入
   */
  public function __construct(TransactionService $transaction_service, FacadeService $facade_service) {
    $this->transactionService = $transaction_service;
    $this->facadeService = $facade_service;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.transaction_service'),      
      $container->get('quicklearning_symbol.facade_service')      
    );
  }

  /**
   * Getter method for Form ID.
   *
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'namespace_transfer_transaction_form';
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

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('6.4 未解決で使用'),
    ];

    $form['sender_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Sender Private Key'),
      '#description' => $this->t('Enter the private key of the sender.'),
      '#required' => TRUE,
    ];

    $form['recipient_namespace'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipient Namespace'),
      '#description' => $this->t('Namespace of the recipient address.'),
      '#required' => TRUE,
    ];
    
    $form['mosaic_namespace'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Mosaic Namespace'),
        '#required' => TRUE,
        '#description' => $this->t('Enter the Mosaic Namespace.e.g., symbol.xym'),
    ];
    $form['mosaic_amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the amount of the mosaic (e.g., 1000000).'),
      '#default_value' => '1000000',
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
   * Implements form validation.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {   
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
 
    $recipient_namespace = $form_state->getValue('recipient_namespace');
    // \Drupal::logger('qls_sect6')->notice('recipient_namespace:<pre>@object</pre>', ['@object' => print_r($recipient_namespace, TRUE)]); 
    // UnresolvedAccount 導出
    $namespaceId = IdGenerator::generateNamespaceId($recipient_namespace); // ルートネームスペースのIDを取得
    // \Drupal::logger('qls_sect6')->notice('namespaceId:<pre>@object</pre>', ['@object' => print_r($namespaceId, TRUE)]); 

    $address = Address::fromNamespaceId(
      new NamespaceId($namespaceId),
      $facade->network->identifier
    );

    $sender_pvtKey = $form_state->getValue('sender_pvtKey');
    // 秘密鍵からアカウント生成
    $senderKey = $facade->createAccount(new PrivateKey($sender_pvtKey));

    $mosaic_namespace = $form_state->getValue(['mosaic_namespace']);
    $mosaicnamespaceIds = IdGenerator::generateNamespacePath($mosaic_namespace); // ルートネームスペースのIDを取得
    $mosaicnamespaceId = new NamespaceId($mosaicnamespaceIds[count($mosaicnamespaceIds) - 1]);
    
    $mosaic_amount = $form_state->getValue(['mosaic_amount']);
    // トランザクション
    // Tx作成
    $tx = new TransferTransactionV1(
      signerPublicKey: $senderKey->publicKey,
      network: $networkType,
      deadline: new Timestamp($facade->now()->addHours(2)),
      recipientAddress: new UnresolvedAddress($address),
      message: '',
      mosaics: [
          new UnresolvedMosaic(
            mosaicId: new UnresolvedMosaicId($mosaicnamespaceId),
            amount: new Amount($mosaic_amount)
          ),
        ],
      );
    $facade->setMaxFee($tx, 100);

    // //署名
    $sig = $senderKey->signTransaction($tx);
    $payload = $facade->attachSignature($tx, $sig);

    $transactionApi = $this->transactionService->getTransactionApi();
    $result = $transactionApi->announceTransaction($payload);
    // $result = $this->transactionService->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));

  }

}
