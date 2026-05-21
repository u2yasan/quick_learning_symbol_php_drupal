<?php

namespace Drupal\quicklearning_symbol\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\quicklearning_symbol\Service\SymbolConfigService;

/**
 * Configure example module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['quicklearning_symbol.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quicklearning_symbol_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'quicklearning_symbol/settings';

    $config = $this->config('quicklearning_symbol.settings');

    $form['network_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Network Type'),
      '#description' => $this->t('Select either testnet or mainnet'),
      '#options' => [
        'testnet' => $this->t('Testnet'),
        'mainnet' => $this->t('Mainnet'),
      ],
      '#default_value' => $config->get('network_type') ?? 'testnet',
      '#required' => TRUE,
    ];

    $form['node_url_test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NODE URL for Testnet'),
      '#default_value' => $config->get('test_node_url'),
      '#description' => 'https://sym-test-01.opening-line.jp:3001',
    ];

    $form['node_url_main'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NODE URL for Mainnet'),
      '#default_value' => $config->get('main_node_url'),
      '#description' => 'http://sym-main-03.opening-line.jp:3000',
    ];

    $form['symbol_address'] = [
      '#markup' => '<div id="symbol_address">Symbol Address</div>',
    ];
    

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach (['node_url_test', 'node_url_main'] as $field_name) {
      $url = $form_state->getValue($field_name);
      if ($url !== '' && !SymbolConfigService::isValidNodeUrl($url)) {
        $form_state->setErrorByName($field_name, $this->t('Enter a valid public http(s) Symbol node URL without credentials, localhost, private IPs, or control characters.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $this->config('quicklearning_symbol.settings')
      ->set('network_type', $form_state->getValue('network_type'))
      ->set('test_node_url', $form_state->getValue('node_url_test'))
      ->set('main_node_url', $form_state->getValue('node_url_main'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
