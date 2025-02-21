<?php
namespace Drupal\qls_ch3\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Facade\SymbolFacade;

class GenerateAccountForm extends FormBase {
 
  public function getFormId() {
    return 'generate_account_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#markup' => '3.1.1 '. $this->t('新規生成'). 
        ' 3.1.2 '. $this->t('秘密鍵と公開鍵の導出') . 
        ' 3.1.3 '. $this->t('アカウントアドレスの導出'),
    ];

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'box-container'],
    ];

    $form['container']['box'] = [
      '#type' => 'markup',
      '#markup' => '<h1>'. $this->t('Generate Random Accounts') .'</h1>',
    ];

    $form['num_accounts'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of accounts to generate'),
      '#description' => $this->t('Enter the number of accounts to generate'),
      '#default_value' => 1,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#ajax' => [
        'callback' => '::promptCallback',
        'wrapper' => 'box-container',
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This method is intentionally left empty as we are using AJAX callback.
  }

  public function promptCallback(array &$form, FormStateInterface $form_state) {
    // clear baffer foe ajax error
    while (ob_get_level()) {
      ob_end_clean();
    }

    $config = \Drupal::config('quicklearning_symbol.settings');
    $networkType = $config->get('network_type');

    $facade = new SymbolFacade($networkType);

    $numAccounts = $form_state->getValue('num_accounts');

    $output = '<h1>'. $this->t('Generated Accounts'). '</h1>';
    for ($n = 0; $n < $numAccounts; $n++) {
      $accountKey = $facade->createAccount(PrivateKey::random());
      // \Drupal::logger('qls_ch3')->notice('<pre>@object</pre>', ['@object' => print_r($accountKey, TRUE)]);

      $accountPubKey = $accountKey->publicKey;
      $accountPvtKey = $accountKey->keyPair->privateKey();
      $accountRawAddress = $accountKey->address;

      $output .= '<p>Account ' . ($n + 1) . ':<br>';
      $output .= 'Public Key: ' . $accountPubKey . '<br>';
      $output .= 'Private Key: ' . $accountPvtKey . '<br>';
      $output .= 'Raw Address: ' . $accountRawAddress . '</p>';
    }
    // \Drupal::logger('qls_ch3')->notice('<pre>@object</pre>', ['@object' => print_r($output, TRUE)]);
    
    $form['container']['box']['#markup'] = $output;
    return $form['container'];
  }
}