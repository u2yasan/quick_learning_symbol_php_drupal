<?php

namespace Drupal\qls_sect11\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\CryptoTypes\PrivateKey;

use SymbolSdk\Symbol\Models\AccountAddressRestrictionTransactionV1;
use SymbolSdk\Symbol\Models\AccountRestrictionFlags;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedAddress;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\TransactionService;

/**
 * @see \Drupal\Core\Form\FormBase
 */
class AccountAddressRestrictionForm extends FormBase {

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
    return 'account_address_restriction_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form['#attached']['library'][] = 'qls_sect11/account_restriction';
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '11.1.1 '.$this->t('指定アドレスからの受信制限・指定アドレスへの送信制限'),
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

    $form['restriction_flag'] = [
      '#type' => 'select',
      '#title' => $this->t('Restriction Flag'),
      '#options' => [
        '0' => $this->t('Allow Incoming Address'),
        '1' => $this->t('Allow Outgoing Address'),
        '2' => $this->t('Block Incoming Address'),
        '3' => $this->t('Block Outgoing Address'),
        ],
      '#required' => TRUE,
    ];
    
    // $form['restrictionAdditions'] = [
    //   '#type' => 'fieldset',
    //   '#title' => $this->t('Restriction Additions'),
    //   '#prefix' => '<div id="restrictionAdditions-fieldset-wrapper">',
    //   '#suffix' => '</div>',
    // ];

    // Gather the number of cosigners in the form already.
    $num_additions = $form_state->get('num_additions');
    // We have to ensure that there is at least one cosigner field.
    if ($num_additions === NULL) {
      $addition_field = $form_state->set('num_additions', 1);
      $num_additions = 1;
    }
    $form['#tree'] = TRUE;
    $form['additions_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additions'),
      '#prefix' => '<div id="additions-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $num_additions; $i++) {
      $form['additions_fieldset']['addition'][$i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Addition Address'),
        '#description' => $this->t('Enter the address of the addition.')
      ];
    }
    $form['additions_fieldset']['actions'] = [
      '#type' => 'actions',
    ];

    $form['additions_fieldset']['actions']['add_addition'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more addition'),
      '#submit' => ['::addOneAddition'],
      '#ajax' => [
        'callback' => '::addMoreAdditionCallback',
        'wrapper' => 'additions-fieldset-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];
    if ($num_additions > 1) {
      $form['additions_fieldset']['actions']['remove_addition'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one addition'),
        '#submit' => ['::removeAdditionCallback'],
        '#ajax' => [
          'callback' => '::addMoreAdditionCallback',
          'wrapper' => 'additions-fieldset-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    $num_deletions = $form_state->get('num_deletions');
    if ($num_deletions === NULL) {
      $deletion_field = $form_state->set('num_deletions', 1);
      $num_deletions = 1;
    }

    $form['deletions_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Deletions'),
      '#prefix' => '<div id="deletions-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $num_deletions; $i++) {
      $form['deletions_fieldset']['deletion'][$i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Deletion Address'),
        '#description' => $this->t('Enter the address of the deletion.')
      ];
    }
    $form['deletions_fieldset']['actions'] = [
      '#type' => 'actions',
    ];

    $form['deletions_fieldset']['actions']['add_deletion'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more deletion'),
      '#submit' => ['::addOneDeletion'],
      '#ajax' => [
        'callback' => '::addMoreDeletionCallback',
        'wrapper' => 'deletions-fieldset-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];
    if ($num_deletions > 1) {
      $form['deletions_fieldset']['actions']['remove_deletion'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one deletion'),
        '#submit' => ['::removeDeletionCallback'],
        '#ajax' => [
          'callback' => '::addMoreDeletionCallback',
          'wrapper' => 'deletions-fieldset-wrapper',
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

  public function addMoreAdditionCallback(array &$form, FormStateInterface $form_state) {
    return $form['additions_fieldset'];
  }

  public function addOneAddition(array &$form, FormStateInterface $form_state) {
    $addition_field = $form_state->get('num_additions');
    $add_button = $addition_field + 1;
    $form_state->set('num_additions', $add_button);
    $form_state->setRebuild();
  }
  public function removeAdditionCallback(array &$form, FormStateInterface $form_state) {
    $addition_field = $form_state->get('num_additions');
    if ($addition_field > 1) {
      $remove_button = $addition_field - 1;
      $form_state->set('num_additions', $remove_button);
    }
    $form_state->setRebuild();
  }

  public function addMoreDeletionCallback(array &$form, FormStateInterface $form_state) {
    return $form['deletions_fieldset'];
  }
  public function addOneDeletion(array &$form, FormStateInterface $form_state) {
    $deletion_field = $form_state->get('num_deletions');
    $add_button = $deletion_field + 1;
    $form_state->set('num_deletions', $add_button);
    $form_state->setRebuild();
  }
  public function removeDeletionCallback(array &$form, FormStateInterface $form_state) {
    $deletion_field = $form_state->get('num_deletions');
    if ($deletion_field > 1) {
      $remove_button = $deletion_field - 1;
      $form_state->set('num_deletions', $remove_button);
    }
    $form_state->setRebuild();
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

    $restriction_flag = $form_state->getValue('restriction_flag');
    // \Drupal::logger('qls_sect11')->info('restriction_flag: @restriction_flag', ['@restriction_flag' => $restriction_flag]);
    switch ($restriction_flag) {
      case 0: // AllowIncomingAddress：指定アドレスからのみ受信許可
        $f = AccountRestrictionFlags::ADDRESS;
        break;
      case 1: // AllowOutgoingAddress：指定アドレス宛のみ送信許可
        $f = AccountRestrictionFlags::ADDRESS;
        $f += AccountRestrictionFlags::OUTGOING;
        break;
      case 2: // BlockIncomingAddress：指定アドレスからの受信拒否
        $f = AccountRestrictionFlags::ADDRESS;
        $f += AccountRestrictionFlags::BLOCK;
        break;
      case 3: // BlockOutgoingAddress：指定アドレス宛への送信禁止
        $f = AccountRestrictionFlags::ADDRESS;
        $f += AccountRestrictionFlags::BLOCK;
        $f += AccountRestrictionFlags::OUTGOING;
        break;
    }
    $flags = new AccountRestrictionFlags($f);
    // \Drupal::logger('qls_sect11')->info('flags: @flags', ['@flags' => $flags]);
    
    $additions = $form_state->getValue(['additions_fieldset', 'addition']);
    // \Drupal::logger('qls_sect11')->info('additions: @additions', ['@additions' => $additions]);
    $addition_addresses = [];
    if (is_array($additions)) {
        foreach ($additions as $addition) {
          // \Drupal::logger('qls_sect11')->info('addition: @addition', ['@addition' => $addition]);
          if($addition !== '') {
            $addition_addresses[] = new UnresolvedAddress($addition);
          }
        }
    }
    $deletions = $form_state->getValue(['deletions_fieldset', 'deletion']);
    // \Drupal::logger('qls_sect11')->info('deletions: @deletions', ['@deletions' => $deletions]);
    $deletion_addresses = [];
    if (is_array($deletions)) {
        foreach ($deletions as $deletion) {
          // \Drupal::logger('qls_sect11')->info('deletion: @deletion', ['@deletion' => $deletion]);
          if($deletion !== '') {
            $deletion_addresses[] = new UnresolvedAddress($deletion);
          }
        }
    }
    // $drupal_config = \Drupal::config('qls_sect11.settings');
    // $restrictAccountKey = new PrivateKey($drupal_config->get('restrict_account_pvtKey'));
    // \Drupal::logger('qls_sect11')->info('deletion_addresses: @deletion_addresses', ['@deletion_addresses' => $deletion_addresses]);
    // アドレス制限設定Tx作成
    $tx = new AccountAddressRestrictionTransactionV1(
      network: $networkType,
      signerPublicKey: $accountPubKey,
      deadline: new Timestamp($facade->now()->addHours(2)),
      restrictionFlags: $flags, // 制限フラグ
      restrictionAdditions:$addition_addresses, // 設定アドレス
      restrictionDeletions:$deletion_addresses // 削除アドレス
    );
    $facade->setMaxFee($tx, 100);
    // \Drupal::logger('qls_sect11')->info('tx: @tx', ['@tx' => $tx]);
    // 署名
    $sig = $accountKey->signTransaction($tx);
    // \Drupal::logger('qls_sect11')->info('sig: @sig', ['@sig' => $sig]);
    $payload = $facade->attachSignature($tx, $sig);

    $result = $transactionApi->announceTransaction($payload);
    $this->messenger()->addMessage($this->t('AccountAddressRestriction Transaction successfully announced: @result', ['@result' => $result]));

    // try {
    //   $result = $apiInstance->announceTransaction($jsonPayload);
    //   $this->messenger()->addMessage($this->t('AccountAddressRestriction Transaction successfully announced: @result', ['@result' => $result]));
    // } catch (Exception $e) {
    //   \Drupal::logger('qls_sect11')->error('Transaction Failed: @message', ['@message' => $e->getMessage()]);
    // } 
  }
}