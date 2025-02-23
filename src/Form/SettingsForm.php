<?php

namespace Drupal\quicklearning_symbol\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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

    // NOTE: do NOT save private key in database especially in plain text.
    $form['restrict_account_pvtKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account Private Key to be restricted'),
      '#default_value' => $config->get('restrict_account_pvtKey'),
      '#description' => $this->t('Restrict account'),
    ];
    $form['symbol_address'] = [
      '#markup' => '<div id="symbol_address">Symbol Address</div>',
    ];

    for ($i = 1; $i <= 5; $i++) {
      $form["cosignatory{$i}_pvtKey"] = [
        '#type' => 'textfield',
        '#title' => $this->t("Cosignatory @number", ['@number' => $i]),
        '#default_value' => $config->get("cosignatory{$i}_pvtKey"),
        '#description' => $this->t("Cosignatory @number of multi-signature", ['@number' => $i]),
      ];
    }
    

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $this->config('quicklearning_symbol.settings')
      ->set('network_type', $form_state->getValue('network_type'))
      ->set('test_node_url', $form_state->getValue('node_url_test'))
      ->set('main_node_url', $form_state->getValue('node_url_main'))
      ->set('restrict_account_pvtKey', $form_state->getValue('restrict_account_pvtKey'))
      ->set('cosignatory1_pvtKey', $form_state->getValue('cosignatory1_pvtKey'))
      ->set('cosignatory2_pvtKey', $form_state->getValue('cosignatory2_pvtKey'))
      ->set('cosignatory3_pvtKey', $form_state->getValue('cosignatory3_pvtKey'))
      ->set('cosignatory4_pvtKey', $form_state->getValue('cosignatory4_pvtKey'))
      ->set('cosignatory5_pvtKey', $form_state->getValue('cosignatory5_pvtKey'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}