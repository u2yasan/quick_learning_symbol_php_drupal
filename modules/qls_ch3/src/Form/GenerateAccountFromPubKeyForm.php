<?php

namespace Drupal\qls_ch3\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use SymbolSdk\Symbol\Models\PublicKey;

use SymbolSdk\Facade\SymbolFacade;

class GenerateAccountFromPubKeyForm extends FormBase {


  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#markup' => '3.1.5 '.$this->t('公開鍵クラスの生成'),
    ];

    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publick Key'),
      '#description' => $this->t('64文字（16進数）'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Public Account From Publick Key'),
    ];

    return $form;
  }

  public function getFormId() {
    return 'generate_account_from_pubkey_form';
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
    $pubKey = $form_state->getValue('public_key');
    if (strlen($pubKey) !=  64) {
      // Set an error for the form element with a key of "public_key".
      $form_state->setErrorByName('publick_key', $this->t('The publick key must be 64 characters long.'));
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

    $config = \Drupal::config('quicklearning_symbol.settings');
    $network_type = $config->get('network_type');
    $pubkey = $form_state->getValue('public_key');

    // SymbolFacadeを使って新しいアカウントを作成
    $facade = new SymbolFacade($network_type);

    //3.1.5 Public key class generation
    $accountPublicAccount = $facade->createPublicAccount(new PublicKey($pubkey));
   
    // 66文字の公開鍵から、先頭の2文字を削除して表示する？ x0 から始まる?
    // $accountPubKey = substr($accountPublicAccount->publicKey, 2, 66);
    $accountPubKey = $accountPublicAccount->publicKey;

    // 出力例
    // /admin/reports/dblog でログを確認
    // \Drupal::logger('qls_ch3')->notice('<pre>@object</pre>', ['@object' => print_r($accountPublicAccount, TRUE)]);
    $this->messenger()->addMessage($this->t('accountPublicAccount:<pre>@object</pre>', ['@object' => print_r($accountPubKey, TRUE)]));
    $this->messenger()->addMessage($this->t('Public Key: @accountPubKey', ['@accountPubKey' => $accountPubKey]));
  }

}
