<?php

namespace Drupal\qls_sect11\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\quicklearning_symbol\Service\RestrictionAccountService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class SearchAccountRestrictionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_account_restrictions_form';
  }

  /**
   * The RestrictionAccountService instance.
   *
   * @var \Drupal\quicklearing_symbol\Service\RestrictionAccountService
   */
  protected $restrictionAccountService;

  /**
   * 
   * @param \Drupal\quicklearing_symbol\Service\RestrictionAccountService $restriction_account_service
   *   The account service.
   */
  public function __construct(RestrictionAccountService $restriction_account_service) {
    $this->restrictionAccountService = $restriction_account_service;
  }

  public static function create(ContainerInterface $container) {
    // AccountService をコンストラクタで注入
    $form = new static(
        $container->get('quicklearning_symbol.restriction_account_service')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form['#attached']['library'][] = 'qls_sect11/account_restriction';
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '11.1.4 '.$this->t('確認'),
    ];

    $form['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
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

    $restrictionAccountApi = $this->restrictionAccountService->getRestrictionAccountApi();
    $address = $form_state->getValue('address'); 
    
    $result = $restrictionAccountApi->searchAccountRestrictions(
      address: $address
    ); 
    $formattedResult = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $this->messenger()->addMessage($this->t('Account Restrictions: <pre>@result</pre>', ['@result' => $formattedResult])); 
  }
}