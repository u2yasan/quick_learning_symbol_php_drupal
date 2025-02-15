<?php
namespace Drupal\qls_sect9\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolSdk\Symbol\Models\Hash256;
use SymbolSdk\Symbol\Models\Signature;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;

/**
 * @see \Drupal\Core\Form\FormBase
 */
class MultiSigCosigForm extends FormBase {

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
    return 'multi_sig_cosig_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'qls_sect9/multi_sig_cosig';


    $form['description'] = [
      '#type' => 'item',
      '#title' => $this->t('連署'),
    ];

    $form['cosigner_pvtKey'] = [
      '#type' => 'password',
      '#title' => $this->t('Co-signer Private Key'),
      '#description' => $this->t('Enter the private key of the co-signer.'),
      '#required' => TRUE,
    ];

    $form['aggregateTxHash'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Aggregate Transaction Hash'),
      '#description' => $this->t('Enter the hash of the aggregate transaction hash.'),
      '#required' => TRUE,
      // '#default_value' => $aggregateTxHash,
    ];

    $form['expires_in'] = [
      '#type' => 'item',
      '#title' => $this->t('Expires In'),
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
    // $networkType = $this->facadeService->getNetworkTypeObject();
    $transactionApi = $this->transactionService->getTransactionApi();

    // トランザクションの取得
    $aggregateTxHash = $form_state->getValue('aggregateTxHash');
    $cosigner_pvtKey = $form_state->getValue('cosigner_pvtKey');
    $cosignerKey = $facade->createAccount(new PrivateKey($cosigner_pvtKey));

    $txInfo = $transactionApi->getPartialTransaction($aggregateTxHash);

    // 連署者の連署
    $signTxHash = new Hash256($txInfo->getMeta()->getHash());
    $signature = new Signature($cosignerKey->keyPair->sign($signTxHash->binaryData));
    $body = [
        'parentHash' => $signTxHash->__toString(),
        'signature' => $signature->__toString(),
        'signerPublicKey' => $cosignerKey->publicKey->__toString(),
        'version' => '0'
    ];

    $result = $transactionApi->announceCosignatureTransaction($body);
    $this->messenger()->addMessage($this->t('Transaction successfully announced: @result', ['@result' => $result]));

    // //アナウンス
    // try {
    //   $result = $apiInstance->announceCosignatureTransaction($body);
    //   // echo $result . PHP_EOL;
    //   $this->messenger()->addMessage($this->t('Cosignature successfully announced: @result', ['@result' => $result]));

    // } catch (Exception $e) {
    //   \Drupal::logger('qls_sect9')->error('Transaction Failed: @message', ['@message' => $e->getMessage()]);
    //   // echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
    // }
    
  }

}
