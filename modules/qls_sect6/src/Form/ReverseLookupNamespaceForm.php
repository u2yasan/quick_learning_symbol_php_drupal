<?php

namespace Drupal\qls_sect6\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\quicklearning_symbol\Service\NamespaceService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class ReverseLookupNamespaceForm extends FormBase {

  /**
   * NamespaceServiceのインスタンス
   *
   * @var \Drupal\quicklearning_symbol\Service\NamespaceService
   */
  protected $namespaceService;

  /**
   * コンストラクタ
   *
   * @param \Drupal\quicklearning_symbol\Service\NamespaceService $namespaceService
   *   
   */
  public function __construct(NamespaceService $namespaceService) {
    $this->namespaceService = $namespaceService;
  }

  /**
   * createメソッドでサービスコンテナから依存性を注入
   */  
  public static function create($container) {
    return new static(
      $container->get('quicklearning_symbol.namespace_service')
    );
  }

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'reverse_lookup_namespace_form';
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
      '#markup' => $this->t('逆引き'),
    ];

    $form['address'] = [
      '#title' => $this->t('Address'),
      '#type' => 'textfield',
      '#description' => $this->t('Enter the Address. ex) TCWM5Z7LGSDFGBPWQYGZPKDJH2HGCJCT4NHJUAA'),
    ];

    $form['submit_address'] = [
      '#type' => 'submit',
      '#value' => t('Submit Address'),
      '#submit' => ['::submitAddressHandler'],
    ];

    $form['mosaic'] = [
      '#title' => $this->t('Mosaic'),
      '#type' => 'textfield',
      '#description' => $this->t('Enter the Mosaic. ex) 72C0212E67A08BCE'),
    ];

    $form['submit_mosaic'] = [
      '#type' => 'submit',
      '#value' => t('Submit Mosaic'),
      '#submit' => ['::submitMosaicHandler'],
    ];

    return $form;
  }

  /**
   * Implements form validation.
   *
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  // public function validateForm(array &$form, FormStateInterface $form_state) {
  //   $pvtKey = $form_state->getValue('account_pvtKey');
  //   if (strlen($pvtKey) !=  64) {
  //     // Set an error for the form element with a key of "title".
  //     $form_state->setErrorByName('account_pvtKey', $this->t('The private key must be 64 characters long.'));
  //   }
  // }


  /**
   * **デフォルトの submitForm メソッド（必須）**
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Submitボタン1の処理
   */
  public function submitAddressHandler(array &$form, FormStateInterface $form_state) {
    
    $address = $form_state->getValue('address');
    $addresses = ["addresses"=> [$address]];
    $namespaceApi = $this->namespaceService->getNamespaceApi();
    $accountNames = $namespaceApi->getAccountsNames($addresses);
    $this->messenger()->addMessage($this->t('AccountNames: <pre>@result</pre>', ['@result' => print_r($accountNames, TRUE)]));
  }

  /**
   * Submitボタン2の処理
   */
  public function submitMosaicHandler(array &$form, FormStateInterface $form_state) {
    $mosaicid = $form_state->getValue('mosaic');
    $mosaicIds = ["mosaicIds"=> [$mosaicid]];
    $namespaceApi = $this->namespaceService->getNamespaceApi();
    $mosaicNames = $namespaceApi->getMosaicsNames($mosaicIds);
    $this->messenger()->addMessage($this->t('MosaicNames: <pre>@result</pre>', ['@result' => print_r($mosaicNames, TRUE)]));
  }

  /**
   * Implements a form submit handler.
   * 
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */

  // public function submitForm(array &$form, FormStateInterface $form_state) {

  //   $namespace = $form_state->getValue('namespace');
  //   // root namespaceの情報を取得
  //   // $namespaceId = new NamespaceId(IdGenerator::generateNamespaceId("xembook"));

  //   $namespaceIds = IdGenerator::generateNamespacePath($namespace); // 配列を取得
  //   $namespaceId = new NamespaceId($namespaceIds[count($namespaceIds) - 1]); // 最後の要素を取得
  //   $namespaceInfoDTO = $this->namespaceService->getNamespace(substr($namespaceId, 2)); //先頭2文字を削除（0xプレフィックスを除去）

  //   // $namespaceDTO = $namespaceInfoDTO->getNamespace();
  //   // $data = $namespaceDTO->getData();
  //   $this->messenger()->addMessage($this->t('NamespaceInfoDTO: <pre>@result</pre>', ['@result' => print_r($namespaceInfoDTO, TRUE)]));

   
  // }
}