<?php

namespace Drupal\qls_ch6\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use SymbolSdk\Symbol\Models\NamespaceId;
use SymbolSdk\Symbol\IdGenerator;

use Drupal\quicklearning_symbol\Service\NamespaceService;

/**
 * @see \Drupal\Core\Form\FormBase
 */
class CheckNamespaceForm extends FormBase {

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
   *   NamespaceServiceのインスタンス
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
    return 'check_namespace_form';
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
      '#markup' => $this->t('参照'),
    ];

    $form['namespace'] = [
      '#title' => $this->t('Namespace'),
      '#type' => 'textfield',
      '#description' => $this->t('Enter the Namespace. ex) xembook or xembook.tomato'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check Namespaces'),
    ];

    return $form;
  }

  

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  // public function validateForm(array &$form, FormStateInterface $form_state) {
  //   $title = $form_state->getValue('network_type');
  //   if (strlen($title) < 5) {
  //     // Set an error for the form element with a key of "title".
  //     $form_state->setErrorByName('title', $this->t('The title must be at least 5 characters long.'));
  //   }
  // }
  // public function validateForm(array &$form, FormStateInterface $form_state) {
  //   $pvtKey = $form_state->getValue('account_pvtKey');
  //   if (strlen($pvtKey) !=  64) {
  //     // Set an error for the form element with a key of "title".
  //     $form_state->setErrorByName('account_pvtKey', $this->t('The private key must be 64 characters long.'));
  //   }
  // }



  /**
   * Implements a form submit handler.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $namespace = $form_state->getValue('namespace');
    // root namespaceの情報を取得
    // $namespaceId = new NamespaceId(IdGenerator::generateNamespaceId("xembook"));

    $namespaceIds = IdGenerator::generateNamespacePath($namespace); // 配列を取得
    $namespaceId = new NamespaceId($namespaceIds[count($namespaceIds) - 1]); // 最後の要素を取得
    try{
      $namespaceInfoDTO = $this->namespaceService->getNamespace(substr($namespaceId, 2)); //先頭2文字を削除（0xプレフィックスを除去）

    // $namespaceDTO = $namespaceInfoDTO->getNamespace();
    // $data = $namespaceDTO->getData();
    $this->messenger()->addMessage($this->t('NamespaceInfoDTO: <pre>@result</pre>', ['@result' => print_r($namespaceInfoDTO, TRUE)]));
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()]));
    }
   
  }
}